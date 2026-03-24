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

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
};

const props = defineProps<{ categories: CategoryRow[] }>();

const createForm = useForm({
    name: '',
    slug: '',
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    slug: '',
});

const totalCategories = computed(() => props.categories.length);
const longestName = computed(() =>
    props.categories.reduce((max, item) => Math.max(max, item.name.length), 0),
);
const averageSlugLength = computed(() => {
    if (!props.categories.length) return 0;

    const total = props.categories.reduce(
        (sum, item) => sum + item.slug.length,
        0,
    );
    return Math.round(total / props.categories.length);
});

const startEdit = (category: CategoryRow) => {
    editingId.value = category.id;
    editForm.clearErrors();
    editForm.name = category.name;
    editForm.slug = category.slug;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const createCategory = () => {
    createForm.post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const saveEdit = () => {
    if (!editingId.value) return;

    editForm.put(route('admin.categories.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const deleteCategory = (category: CategoryRow) => {
    if (!confirm(`Delete category "${category.name}"?`)) return;

    router.delete(route('admin.categories.destroy', category.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Categories" />

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
                                Categories
                            </h1>
                            <p
                                class="mt-3 text-sm leading-7 text-gray-600 md:text-base"
                            >
                                Organize the blog with a cleaner, more premium
                                category structure. This screen gives you fast
                                create, inline editing, and a clearer high-level
                                view of the category system.
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
                                    {{ totalCategories }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Categories currently available
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
                                    Characters in the longest category title
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
                                New Category
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                Add a category name and, if needed, a custom
                                slug. Leave the slug blank to generate it from
                                the name.
                            </p>
                        </div>

                        <form
                            @submit.prevent="createCategory"
                            class="space-y-5"
                        >
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
                                    If blank, the slug is generated from the
                                    category name.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <PrimaryButton :disabled="createForm.processing"
                                    >Create Category</PrimaryButton
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
                                    Category Directory
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-gray-900"
                                >
                                    All Categories
                                </h2>
                            </div>

                            <div
                                class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold tracking-[0.16em] text-gray-500 uppercase"
                            >
                                {{ totalCategories }} items
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
                                    <tr v-if="categories.length === 0">
                                        <td
                                            colspan="3"
                                            class="px-6 py-10 text-sm text-gray-600"
                                        >
                                            No categories yet.
                                        </td>
                                    </tr>

                                    <tr
                                        v-for="category in categories"
                                        :key="category.id"
                                        class="align-top transition hover:bg-gray-50/70"
                                    >
                                        <template
                                            v-if="editingId === category.id"
                                        >
                                            <td class="px-6 py-5">
                                                <TextInput
                                                    :id="`edit_name_${category.id}`"
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
                                                    :id="`edit_slug_${category.id}`"
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
                                                    category name.
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
                                                        {{
                                                            category.name
                                                                .slice(0, 2)
                                                                .toUpperCase()
                                                        }}
                                                    </div>
                                                    <div>
                                                        <div
                                                            class="text-sm font-semibold text-gray-900"
                                                        >
                                                            {{ category.name }}
                                                        </div>
                                                        <div
                                                            class="mt-1 text-xs tracking-[0.16em] text-gray-400 uppercase"
                                                        >
                                                            Category
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-6 py-5">
                                                <div
                                                    class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-sm text-gray-700"
                                                >
                                                    {{ category.slug }}
                                                </div>
                                            </td>

                                            <td class="px-6 py-5 text-right">
                                                <div
                                                    class="flex justify-end gap-2"
                                                >
                                                    <SecondaryButton
                                                        type="button"
                                                        @click="
                                                            startEdit(category)
                                                        "
                                                    >
                                                        Edit
                                                    </SecondaryButton>
                                                    <DangerButton
                                                        type="button"
                                                        @click="
                                                            deleteCategory(
                                                                category,
                                                            )
                                                        "
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
