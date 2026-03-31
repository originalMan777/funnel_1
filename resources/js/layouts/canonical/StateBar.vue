<template>
  <!-- If page says disabled, render nothing -->
  <div v-if="isVisible" class="state-bar" :class="{ disabled: !enabled }">
    <!-- LEFT: Lifecycle (far left) + QC (immediately after) -->
    <div class="state-left">
      <!-- SECTION 1: LIFECYCLE -->
      <div class="section lifecycle" aria-label="Lifecycle">
        <!-- Buttons mode -->
        <div v-if="lifecycle.mode === 'buttons'" class="btn-group">
          <button
            v-for="btn in lifecycleButtons"
            :key="btn.key"
            :class="{ active: currentLifecycle === btn.key }"
            @click="handleButton(btn)"
            :disabled="!enabled || (btn.lockOnActive && currentLifecycle === btn.key)"
            type="button"
          >
            {{ btn.label }}
          </button>
        </div>

        <!-- Badge mode -->
        <span v-else-if="lifecycle.mode === 'badge'" class="badge">
          {{ lifecycleBadgeText }}
        </span>
      </div>

      <!-- SECTION 2: QC -->
      <div class="section qc" aria-label="QC">
        <!-- Buttons mode -->
        <div v-if="qc.mode === 'buttons'" class="btn-group">
          <button
            v-for="btn in qcButtons"
            :key="btn.key"
            :class="{ active: currentQc === btn.key }"
            @click="handleButton(btn)"
            :disabled="!enabled || (btn.lockOnActive && currentQc === btn.key)"
            type="button"
          >
            {{ btn.label }}
          </button>
        </div>

        <!-- Badge mode -->
        <span v-else-if="qc.mode === 'badge'" class="badge">
          {{ qcBadgeText }}
        </span>
      </div>
    </div>

    <!-- CENTER: intentionally empty (reserved slot) -->
    <div class="state-center" aria-label="StateBar Center Slot"></div>

    <!-- RIGHT: Archive + Delete (far right) -->
    <div class="state-right" aria-label="Danger Controls">
      <button
        class="danger-secondary"
        @click="performTransition(archiveAction)"
        :disabled="!enabled || !danger.canArchive"
        type="button"
      >
        {{ archiveLabel }}
      </button>

      <button
        class="danger"
        @click="performTransition('delete')"
        :disabled="!enabled || !danger.canDelete"
        type="button"
      >
        Delete
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePage, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

type Mode = "buttons" | "badge" | "none";

type ButtonDef = {
  key: string;
  label: string;
  action?: string;
  href?: string;
  request?: {
    method: "get" | "post" | "patch" | "put" | "delete";
    url: string;
    data?: Record<string, any>;
  };
  lockOnActive?: boolean;
};

const props = defineProps({
  itemId: { type: [String, Number], required: false, default: null },
  type: { type: String, required: false, default: null },
  initialState: { type: String, default: "draft" },
});

const page = usePage();

/**
 * Inertia page may provide:
 * page.props.stateBar = {
 *   enabled,
 *   type, itemId, initialState,
 *   lifecycle: { mode, current, label, buttons? },
 *   qc: { mode, current, label, buttons? },
 *   danger: { canArchive, canDelete, archived }
 * }
 */
const sb = computed<any>(() => page.props?.stateBar ?? null);
const pagePost = computed<any>(() => page.props?.post ?? null);

// If page disables stateBar, render nothing
const isVisible = computed(() => {
  const enabledFlag = sb.value?.enabled;
  return enabledFlag === undefined ? true : !!enabledFlag;
});

const ctx = computed(() => {
  const fallbackPostId =
    pagePost.value?.id ??
    pagePost.value?.post?.id ??
    null;

  const resolvedType =
    props.type ??
    sb.value?.type ??
    (fallbackPostId ? "post" : null);

  const resolvedItemId =
    props.itemId ??
    sb.value?.itemId ??
    fallbackPostId;

  return {
    itemId: resolvedItemId,
    type: resolvedType,
    initialState: props.initialState ?? sb.value?.initialState ?? "draft",
  };
});

const enabled = computed(() => !!ctx.value.itemId && !!ctx.value.type);

const lifecycle = computed(() => {
  const fromSb = sb.value?.lifecycle ?? null;

  const inferredDefaultMode: Mode =
    ctx.value.type === "track" ? "buttons" : "none";

  const mode: Mode =
    (fromSb?.mode as Mode | null) ?? inferredDefaultMode;

  return {
    mode,
    current: fromSb?.current ?? null,
    label: fromSb?.label ?? null,
    buttons: Array.isArray(fromSb?.buttons) ? (fromSb.buttons as ButtonDef[]) : null,
  };
});

const qc = computed(() => {
  const fromSb = sb.value?.qc ?? null;

  const mode: Mode =
    (fromSb?.mode as Mode | null) ?? "none";

  return {
    mode,
    current: fromSb?.current ?? null,
    label: fromSb?.label ?? null,
    buttons: Array.isArray(fromSb?.buttons) ? (fromSb.buttons as ButtonDef[]) : null,
  };
});

const danger = computed(() => {
  const fromSb = sb.value?.danger ?? {};
  return {
    canArchive: fromSb?.canArchive ?? true,
    canDelete: fromSb?.canDelete ?? true,
    archived: !!fromSb?.archived,
  };
});

// Track local lifecycle-ish state from /admin/state responses
const currentState = ref<string>(ctx.value.initialState);

watch(
  () => ctx.value.initialState,
  (next) => {
    currentState.value = next || "draft";
  }
);

// prefer explicit lifecycle.current from props; else fall back to currentState
const currentLifecycle = computed(() => {
  return (lifecycle.value.current as string | null) ?? currentState.value;
});

const currentQc = computed(() => {
  return (qc.value.current as string | null) ?? "";
});

/**
 * Default button libraries (overridable per-page via stateBar.lifecycle.buttons / stateBar.qc.buttons)
 */
const lifecycleButtons = computed<ButtonDef[]>(() => {
  if (Array.isArray(lifecycle.value.buttons) && lifecycle.value.buttons.length) {
    return lifecycle.value.buttons;
  }

  return [
    { key: "draft", label: "Draft", action: "draft", lockOnActive: true },
    { key: "ready", label: "Ready", action: "ready", lockOnActive: true },
    { key: "published", label: "Publish", action: "publish", lockOnActive: true },
  ];
});

const qcButtons = computed<ButtonDef[]>(() => {
  if (Array.isArray(qc.value.buttons) && qc.value.buttons.length) {
    return qc.value.buttons;
  }

  // Default QC buttons are OFF unless a page explicitly sets qc.mode='buttons'
  return [
    { key: "needs", label: "Needs QC", href: "#qc-needs", lockOnActive: false },
    { key: "pass", label: "Pass QC", href: "#qc-pass", lockOnActive: false },
    { key: "fail", label: "Fail QC", href: "#qc-fail", lockOnActive: false },
  ];
});

const lifecycleBadgeText = computed(() => {
  if (lifecycle.value.label) return String(lifecycle.value.label);
  return String(currentLifecycle.value || "");
});

const qcBadgeText = computed(() => {
  if (qc.value.label) return String(qc.value.label);
  return String(currentQc.value || "");
});

const archiveLabel = computed(() => (danger.value.archived ? "Unarchive" : "Archive"));
const archiveAction = computed(() => (danger.value.archived ? "unarchive" : "archive"));

const handleButton = (btn: ButtonDef) => {
  if (!enabled.value) return;

  if (btn.href) {
    window.location.hash = btn.href.replace(/^#/, "");
    return;
  }

  if (btn.request?.url && btn.request?.method) {
    router.visit(btn.request.url, {
      method: btn.request.method,
      data: btn.request.data ?? {},
      preserveScroll: true,
    });
    return;
  }

  if (btn.action) {
    performTransition(btn.action);
  }
};

const performTransition = (action: string) => {
  if (!enabled.value) return;

  if (ctx.value.type === "post" && ctx.value.itemId) {
    const id = ctx.value.itemId;

    if (action === "archive" || action === "unarchive") {
      router.patch(`/admin/posts/${id}/archive`, {}, {
        preserveScroll: true,
      });
      return;
    }

    if (action === "delete") {
      const confirmed = window.confirm(
        "Are you sure you want to permanently delete this post?"
      );

      if (!confirmed) return;

      router.delete(`/admin/posts/${id}`, {
        preserveScroll: true,
      });
      return;
    }
  }

  router.visit("/admin/state", {
    method: "patch",
    data: {
      type: ctx.value.type,
      id: ctx.value.itemId,
      action,
    },
    preserveScroll: true,
  });
};
</script>

<style scoped>
.state-bar {
  width: 100%;
  flex: 0 0 auto;

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;

  padding: 0.75rem 1rem;
  background: #f7f7f7;
  border-top: 1px solid #ddd;

  /* Keeps it visible inside scroll/tile containers */
  position: sticky;
  bottom: 0;
  z-index: 50;
}

.state-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.section {
  display: flex;
  align-items: center;
  min-height: 32px;
}

.btn-group {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.state-center {
  flex: 1;
  min-width: 0;
}

.state-right {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

button {
  padding: 0.4rem 0.8rem;
  border: 1px solid #cfcfcf;
  background: #fff;
  border-radius: 6px;
  cursor: pointer;
}

button.active {
  font-weight: 700;
  background-color: #eaeaea;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  border: 1px solid #cfcfcf;
  background: #fff;
  font-size: 0.85rem;
  white-space: nowrap;
}

.danger-secondary,
.danger {
  border-color: #d0d0d0;
}

.disabled {
  opacity: 0.7;
}
</style>
