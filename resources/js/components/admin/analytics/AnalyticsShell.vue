<script setup lang="ts">
defineProps<{
  hasRightRail?: boolean
}>()
</script>

<template>
  <div class="analytics-shell space-y-8">
    <slot name="header" />

    <div
      class="grid grid-cols-12 gap-6"
      :class="{ '': !hasRightRail }"
    >
      <!-- MAIN -->
      <main
        :class="hasRightRail ? 'col-span-9 space-y-8' : 'col-span-12 space-y-8'"
      >
        <slot name="main">
          <slot />
        </slot>
      </main>

      <!-- RIGHT RAIL -->
      <aside
        v-if="hasRightRail"
        class="col-span-3"
      >
        <slot name="rail" />
      </aside>

    </div>

  </div>
</template>
