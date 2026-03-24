<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import DangerButton from '@/components/DangerButton.vue';
import TextInput from '@/components/TextInput.vue';

type TagRow = {
    id: number;
    name: string;
    slug: string;
};

const props = defineProps<{ tags: TagRow[] }>();

const createForm = useForm({
    name: '',
    slug: '',
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    slug: '',
});

const totalTags = computed(() => props.tags.length);
const longestName = computed(() =>
    props.tags.reduce((max, item) => Math.max(max, item.name.length), 0),
);
const averageSlugLength = computed(() => {
    if (!props.tags.length) return 0;

    const total = props.tags.reduce((sum, item) => sum + item.slug.length, 0);
    return Math.round(total / props.tags.length);
});

const startEdit = (tag: TagRow) => {
    editingId.value = tag.id;
    editForm.clearErrors();
    editForm.name = tag.name;
    editForm.slug = tag.slug;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const createTag = () => {
    createForm.post(route('admin.tags.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const saveEdit = () => {
    if (!editingId.value) return;

    editForm.put(route('admin.tags.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const deleteTag = (tag: TagRow) => {
    if (!confirm(`Delete tag "${tag.name}"?`)) return;

    router.delete(route('admin.tags.destroy', tag.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Tags" />

    <AdminLayout>
        <div class="h-full overflow-auto bg-gray-50 p-4 md:p-6">
            <div class="mx-auto flex max-w-6xl flex-col gap-6">
                <section
                    class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm md:p-8"
                >
                    <div
                        class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div class="max-w-3xl">
                            <p
                                class="text-xs font-semibold tracking-[0.22em] text-gray-500 uppercase"
                            >
                                Taxonomy
                            </p>
                            <h1
                                class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                            >
                                Tags
                            </h1>
                            <p
                                class="mt-3 text-sm leading-7 text-gray-600 md:text-base"
                            >
                                Refine discovery and filtering with a cleaner
                                tag system. This premium pass mirrors the
                                upgraded category experience while keeping tag
                                management fast and direct.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[420px]">
                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <div
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Total
                                </div>
                                <div
                                    class="mt-2 text-2xl font-semibold text-gray-900"
                                >
                                    {{ totalTags }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Tags currently available
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <div
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Longest name
                                </div>
                                <div
                                    class="mt-2 text-2xl font-semibold text-gray-900"
                                >
                                    {{ longestName }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Characters in the longest tag title
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <div
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Avg. slug
                                </div>
                                <div
                                    class="mt-2 text-2xl font-semibold text-gray-900"
                                >
                                    {{ averageSlugLength }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Average slug length
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                    <section
                        class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <div class="mb-5">
                            <p
                                class="text-xs font-semibold tracking-[0.2em] text-gray-500 uppercase"
                            >
                                Create
                            </p>
                            <h2
                                class="mt-2 text-xl font-semibold text-gray-900"
                            >
                                New Tag
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                Add a tag name and, if needed, a custom slug.
                                Leave the slug blank to generate it from the tag
                                name.
                            </p>
                        </div>

                        <form @submit.prevent="createTag" class="space-y-5">
                            <div>
                                <InputLabel for="new_name" value="Name" />
                                <TextInput
                                    id="new_name"
                                    v-model="createForm.name"
                                    type="text"
                                    class="mt-2 block w-full"
                                />
                                <InputError
                                    :message="createForm.errors.name"
                                    class="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="new_slug"
                                    value="Slug (optional)"
                                />
                                <TextInput
                                    id="new_slug"
                                    v-model="createForm.slug"
                                    type="text"
                                    class="mt-2 block w-full"
                                />
                                <InputError
                                    :message="createForm.errors.slug"
                                    class="mt-2"
                                />
                                <p class="mt-2 text-xs text-gray-500">
                                    If blank, the slug is generated from the tag
                                    name.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <PrimaryButton :disabled="createForm.processing"
                                    >Create Tag</PrimaryButton
                                >
                                <span
                                    v-if="createForm.processing"
                                    class="text-sm text-gray-500"
                                    >Saving…</span
                                >
                            </div>
                        </form>
                    </section>

                    <section
                        class="rounded-[28px] border border-gray-200 bg-white shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-5"
                        >
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.2em] text-gray-500 uppercase"
                                >
                                    Tag Directory
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-gray-900"
                                >
                                    All Tags
                                </h2>
                            </div>

                            <div
                                class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold tracking-[0.16em] text-gray-500 uppercase"
                            >
                                {{ totalTags }} items
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                        >
                                            Name
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                        >
                                            Slug
                                        </th>
                                        <th
                                            class="px-6 py-4 text-right text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody
                                    class="divide-y divide-gray-100 bg-white"
                                >
                                    <tr v-if="tags.length === 0">
                                        <td
                                            colspan="3"
                                            class="px-6 py-10 text-sm text-gray-600"
                                        >
                                            No tags yet.
                                        </td>
                                    </tr>

                                    <tr
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        class="align-top transition hover:bg-gray-50/70"
                                    >
                                        <template v-if="editingId === tag.id">
                                            <td class="px-6 py-5">
                                                <TextInput
                                                    :id="`edit_name_${tag.id}`"
                                                    v-model="editForm.name"
                                                    type="text"
                                                    class="block w-full"
                                                />
                                                <InputError
                                                    :message="
                                                        editForm.errors.name
                                                    "
                                                    class="mt-2"
                                                />
                                            </td>

                                            <td class="px-6 py-5">
                                                <TextInput
                                                    :id="`edit_slug_${tag.id}`"
                                                    v-model="editForm.slug"
                                                    type="text"
                                                    class="block w-full"
                                                />
                                                <InputError
                                                    :message="
                                                        editForm.errors.slug
                                                    "
                                                    class="mt-2"
                                                />
                                                <p
                                                    class="mt-2 text-xs text-gray-500"
                                                >
                                                    Blank = generate from the
                                                    tag name.
                                                </p>
                                            </td>

                                            <td class="px-6 py-5 text-right">
                                                <div
                                                    class="flex justify-end gap-2"
                                                >
                                                    <SecondaryButton
                                                        type="button"
                                                        @click="cancelEdit"
                                                        :disabled="
                                                            editForm.processing
                                                        "
                                                    >
                                                        Cancel
                                                    </SecondaryButton>
                                                    <PrimaryButton
                                                        type="button"
                                                        @click="saveEdit"
                                                        :disabled="
                                                            editForm.processing
                                                        "
                                                    >
                                                        Save
                                                    </PrimaryButton>
                                                </div>
                                            </td>
                                        </template>

                                        <template v-else>
                                            <td class="px-6 py-5">
                                                <div
                                                    class="flex items-start gap-3"
                                                >
                                                    <div
                                                        class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-900 text-sm font-semibold text-white"
                                                    >
                                                        #{{
                                                            tag.name
                                                                .slice(0, 1)
                                                                .toUpperCase()
                                                        }}
                                                    </div>
                                                    <div>
                                                        <div
                                                            class="text-sm font-semibold text-gray-900"
                                                        >
                                                            {{ tag.name }}
                                                        </div>
                                                        <div
                                                            class="mt-1 text-xs tracking-[0.16em] text-gray-400 uppercase"
                                                        >
                                                            Tag
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-6 py-5">
                                                <div
                                                    class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-sm text-gray-700"
                                                >
                                                    {{ tag.slug }}
                                                </div>
                                            </td>

                                            <td class="px-6 py-5 text-right">
                                                <div
                                                    class="flex justify-end gap-2"
                                                >
                                                    <SecondaryButton
                                                        type="button"
                                                        @click="startEdit(tag)"
                                                    >
                                                        Edit
                                                    </SecondaryButton>
                                                    <DangerButton
                                                        type="button"
                                                        @click="deleteTag(tag)"
                                                    >
                                                        Delete
                                                    </DangerButton>
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
