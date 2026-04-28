<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type PostRow = {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published';
    category_name: string | null;
    published_at: string | null;
    updated_at: string;
    featured_image_url: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Pagination<T> = {
    data: T[];
    links: PaginationLink[];
    meta?: any;
};

const props = defineProps<{
    posts: Pagination<PostRow>;
    filters: {
        search: string;
        status: 'all' | 'draft' | 'published';
    };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');

let searchTimer: number | undefined;

const applyFilters = (immediate = false) => {
    if (searchTimer) window.clearTimeout(searchTimer);

    const run = () => {
        router.get(
            route('admin.posts.index'),
            {
                search: search.value || undefined,
                status: status.value !== 'all' ? status.value : undefined,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    if (immediate) run();
    else searchTimer = window.setTimeout(run, 250);
};

watch(status, () => applyFilters(true));

const formatDate = (value: string | null) => {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const publishPost = (post: PostRow) => {
    router.post(route('admin.posts.publish', post.id), {}, {
        preserveScroll: true,
    });
};

const unpublishPost = (post: PostRow) => {
    router.post(route('admin.posts.unpublish', post.id), {}, {
        preserveScroll: true,
    });
};

const duplicatePost = (post: PostRow) => {
    if (!window.confirm(`Duplicate "${post.title}" as a draft?`)) return;

    router.post(route('admin.posts.duplicate', post.id), {}, {
        preserveScroll: true,
    });
};

const deletePost = (post: PostRow) => {
    if (!window.confirm(`Delete "${post.title}"? This cannot be undone.`)) return;

    router.delete(route('admin.posts.destroy', post.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Posts" />

    <AdminLayout>
        <div class="h-full p-4">
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-900">Posts</h2>
                            <p class="mt-1 text-sm text-gray-600">Drafts and published posts.</p>
                        </div>

                        <Link
                            :href="route('admin.posts.create')"
                            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            New
                        </Link>
                    </div>
                </div>

                <div class="px-6 pb-6">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                <div class="w-full sm:w-80">
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search title…"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @input="applyFilters(false)"
                                    />
                                </div>

                                <div>
                                    <select
                                        v-model="status"
                                        class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="all">All</option>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Image
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Title
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Category
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Published
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Updated
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-if="posts.data.length === 0">
                                        <td colspan="7" class="px-4 py-6 text-sm text-gray-600">
                                            No posts found.
                                        </td>
                                    </tr>

                                    <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex h-14 w-20 items-center justify-center overflow-hidden rounded border bg-gray-100">
                                                <img
                                                    v-if="post.featured_image_url"
                                                    :src="post.featured_image_url"
                                                    alt=""
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                    decoding="async"
                                                />
                                                <span v-else class="text-[11px] text-gray-400">No image</span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ post.title }}</div>
                                            <div class="text-xs text-gray-500">/{{ post.slug }}</div>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ post.category_name || '—' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="
                                                    post.status === 'published'
                                                        ? 'bg-green-50 text-green-700'
                                                        : 'bg-gray-100 text-gray-700'
                                                "
                                            >
                                                {{ post.status }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ formatDate(post.published_at) }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ formatDate(post.updated_at) }}
                                        </td>

                                        <td class="px-4 py-3 text-right">
                                            <div class="flex flex-wrap justify-end gap-3">
                                                <Link
                                                    :href="route('admin.posts.show', post.id)"
                                                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                                                >
                                                    View
                                                </Link>

                                                <Link
                                                    :href="route('admin.posts.edit', post.id)"
                                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                                >
                                                    Edit
                                                </Link>

                                                <button
                                                    type="button"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-700"
                                                    @click="duplicatePost(post)"
                                                >
                                                    Duplicate
                                                </button>

                                                <button
                                                    v-if="post.status === 'draft'"
                                                    type="button"
                                                    class="text-sm font-medium text-green-600 hover:text-green-700"
                                                    @click="publishPost(post)"
                                                >
                                                    Publish
                                                </button>

                                                <button
                                                    v-else
                                                    type="button"
                                                    class="text-sm font-medium text-amber-600 hover:text-amber-700"
                                                    @click="unpublishPost(post)"
                                                >
                                                    Unpublish
                                                </button>

                                                <button
                                                    type="button"
                                                    class="text-sm font-medium text-red-600 hover:text-red-700"
                                                    @click="deletePost(post)"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="posts.links?.length" class="border-t border-gray-200 p-4">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="link in posts.links" :key="link.label">
                                    <span
                                        v-if="!link.url"
                                        class="px-2 py-1 text-sm text-gray-400"
                                        v-html="link.label"
                                    />
                                    <Link
                                        v-else
                                        :href="link.url"
                                        class="rounded-md px-2 py-1 text-sm"
                                        :class="link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                        v-html="link.label"
                                        preserve-scroll
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
