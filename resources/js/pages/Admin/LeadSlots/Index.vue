<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type SlotKey =
    | 'home_intro'
    | 'home_mid'
    | 'home_bottom'
    | 'blog_index_mid_lead'
    | 'blog_post_inline_1'
    | 'blog_post_inline_2'
    | 'blog_post_inline_3'
    | 'blog_post_inline_4'
    | 'blog_post_before_related';

type SlotPayload = {
    id: number;
    key: SlotKey;
    is_enabled: boolean;
    required_type: 'resource' | 'service' | 'offer';
    assignment_lead_box_id: number | null;
};

type LeadBoxOption = {
    id: number;
    internal_name: string;
    title: string;
    type: 'resource' | 'service' | 'offer';
};

const props = defineProps<{
    slots: SlotPayload[];
    activeLeadBoxes: LeadBoxOption[];
}>();

const forms = props.slots.map((slot) =>
    useForm({
        is_enabled: slot.is_enabled,
        lead_box_id: slot.assignment_lead_box_id ?? null as number | null,
    }),
);

const slotLabel = (key: SlotKey) => {
    if (key === 'home_intro') return 'Home (intro)';
    if (key === 'home_mid') return 'Home (mid)';
    if (key === 'home_bottom') return 'Home (bottom)';
    if (key === 'blog_index_mid_lead') return 'Blog index (mid)';
    if (key === 'blog_post_inline_1') return 'Blog post inline 1';
    if (key === 'blog_post_inline_2') return 'Blog post inline 2';
    if (key === 'blog_post_inline_3') return 'Blog post inline 3';
    if (key === 'blog_post_inline_4') return 'Blog post inline 4';
    if (key === 'blog_post_before_related') return 'Blog post before related';
    return key;
};

const selectedLabel = (formIndex: number) => {
    const form = forms[formIndex];
    if (!form.lead_box_id) return '— Unassigned —';
    const found = props.activeLeadBoxes.find((b) => b.id === form.lead_box_id);
    return found ? `${found.internal_name} — ${found.title}` : 'Selected';
};

const submit = (slot: SlotPayload, formIndex: number) => {
    forms[formIndex].put(route('admin.lead-slots.update', slot.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Lead Slots" />

    <AdminLayout>
        <div class="h-full p-4">
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                Lead Blocks
                            </p>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                                Lead Slot Assignment
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Assign any Active Lead Box to any slot. Lead Boxes can be reused across multiple slots.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.lead-boxes.index')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Manage Lead Boxes
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <div class="space-y-6">
                        <div
                            v-for="(slot, idx) in props.slots"
                            :key="slot.id"
                            class="rounded-2xl border border-gray-200 p-6"
                        >
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        {{ slotLabel(slot.key) }}
                                    </p>
                                    <h2 class="mt-2 text-xl font-semibold tracking-tight text-gray-900">
                                        {{ slot.key }}
                                    </h2>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                    @click="submit(slot, idx)"
                                    :disabled="forms[idx].processing"
                                >
                                    {{ forms[idx].processing ? 'Saving…' : 'Save' }}
                                </button>
                            </div>

                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Enabled
                                        </p>

                                        <label class="inline-flex cursor-pointer items-center gap-3">
                                            <input
                                                v-model="forms[idx].is_enabled"
                                                type="checkbox"
                                                class="h-5 w-5 rounded border-gray-300 text-gray-900"
                                            />
                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ forms[idx].is_enabled ? 'On' : 'Off' }}
                                            </span>
                                        </label>
                                    </div>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Disabled slots render nothing publicly even if assigned.
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        Assignment
                                    </p>

                                    <select
                                        v-model="forms[idx].lead_box_id"
                                        class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                                    >
                                        <option :value="null">— Unassigned —</option>
                                        <option v-for="box in props.activeLeadBoxes" :key="box.id" :value="box.id">
                                            {{ box.internal_name }} — {{ box.title }} ({{ box.type }})
                                        </option>
                                    </select>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Selected: <span class="font-semibold text-gray-900">{{ selectedLabel(idx) }}</span>
                                    </p>

                                    <p v-if="forms[idx].errors.lead_box_id" class="mt-3 text-xs text-red-600">
                                        {{ forms[idx].errors.lead_box_id }}
                                    </p>

                                    <p v-if="!props.activeLeadBoxes.length" class="mt-3 text-sm text-gray-600">
                                        No Active Lead Boxes yet.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!props.slots.length" class="rounded-2xl border border-dashed border-gray-200 p-10 text-center text-sm text-gray-600">
                            No slots found.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
