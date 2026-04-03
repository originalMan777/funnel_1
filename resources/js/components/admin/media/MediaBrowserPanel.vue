<script setup lang="ts">
import { computed, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { useMediaBrowser } from '@/composables/useMediaBrowser';

type FolderOption = {
    value: string;
    label: string;
};

type MediaItem = {
    name: string;
    filename: string;
    folder: string;
    path: string;
    url: string;
    size_kb: number;
    modified_at: string;
    extension: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Pagination<T> = {
    data: T[];
    links: PaginationLink[];
    current_page?: number;
    last_page?: number;
    from?: number | null;
    to?: number | null;
    total?: number;
    per_page?: number;
};

const props = defineProps<{
    title: string;
    description: string;
    routeName: string;
    folders: FolderOption[];
    filters: {
        folder: string;
        search: string;
        per_page: number;
    };
    media: Pagination<MediaItem>;
}>();

const previewItem = ref<MediaItem | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadFiles = ref<File[]>([]);
const isUploading = ref(false);
const isDeletingPath = ref<string | null>(null);
const notice = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const {
    folder,
    search,
    perPage,
    applyFilters,
    syncUploadFolder,
    uploadFolder,
} = useMediaBrowser({
    routeName: props.routeName,
    initialFilters: props.filters,
});

syncUploadFolder(folder);

const resetNotice = () => {
    window.setTimeout(() => {
        notice.value = null;
    }, 3000);
};

const pickFile = () => {
    fileInput.value?.click();
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    uploadFiles.value = Array.from(target.files ?? []);
};

const uploadImage = async () => {
    if (uploadFiles.value.length === 0) {
        notice.value = { type: 'error', text: 'Choose at least one image first.' };
        resetNotice();
        return;
    }

    isUploading.value = true;
    notice.value = null;

    try {
        for (const file of uploadFiles.value) {
            const form = new FormData();
            form.append('folder', uploadFolder.value);
            form.append('image', file);

            await axios.post(route('admin.media.store'), form, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
        }

        notice.value = {
            type: 'success',
            text:
                uploadFiles.value.length === 1
                    ? '1 image uploaded.'
                    : `${uploadFiles.value.length} images uploaded.`,
        };

        uploadFiles.value = [];
        if (fileInput.value) fileInput.value.value = '';

        resetNotice();
        applyFilters(true);
    } catch (error: any) {
        notice.value = {
            type: 'error',
            text: error?.response?.data?.message || 'Upload failed.',
        };
        resetNotice();
    } finally {
        isUploading.value = false;
    }
};

const deleteImage = async (item: MediaItem) => {
    if (!window.confirm(`Delete ${item.filename}?`)) return;

    isDeletingPath.value = item.path;
    notice.value = null;

    try {
        await axios.delete(route('admin.media.destroy'), {
            data: { path: item.path },
        });

        if (previewItem.value?.path === item.path) {
            previewItem.value = null;
        }

        notice.value = { type: 'success', text: 'Image deleted.' };
        resetNotice();
        applyFilters(true);
    } catch (error: any) {
        notice.value = {
            type: 'error',
            text: error?.response?.data?.message || 'Delete failed.',
        };
        resetNotice();
    } finally {
        isDeletingPath.value = null;
    }
};

const copyUrl = async (item: MediaItem) => {
    const absolute = item.url.startsWith('http')
        ? item.url
        : `${window.location.origin}${item.url}`;

    try {
        await navigator.clipboard.writeText(absolute);
        notice.value = { type: 'success', text: 'Image URL copied.' };
        resetNotice();
    } catch {
        notice.value = { type: 'error', text: 'Could not copy URL.' };
        resetNotice();
    }
};

const openPreview = (item: MediaItem) => {
    previewItem.value = item;
};

const closePreview = () => {
    previewItem.value = null;
};

const previewIndex = computed(() => {
    if (!previewItem.value) return -1;
    return props.media.data.findIndex((item) => item.path === previewItem.value?.path);
});

const hasPreviousPreview = computed(() => previewIndex.value > 0);
const hasNextPreview = computed(() => {
    return previewIndex.value >= 0 && previewIndex.value < props.media.data.length - 1;
});

const openPreviousPreview = () => {
    if (!hasPreviousPreview.value) return;
    previewItem.value = props.media.data[previewIndex.value - 1] ?? null;
};

const openNextPreview = () => {
    if (!hasNextPreview.value) return;
    previewItem.value = props.media.data[previewIndex.value + 1] ?? null;
};

const formattedRange = computed(() => {
    if (props.media.total && props.media.total > 0) {
        const from = props.media.from ?? 0;
        const to = props.media.to ?? 0;
        const total = props.media.total;
        return `Showing ${from}-${to} of ${total} images`;
    }

    const count = props.media.data?.length ?? 0;
    return `${count} image${count === 1 ? '' : 's'}`;
});

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
</script>

<template>
    <div class="h-full p-4">
        <div
            class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white"
        >
            <div class="p-6 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">
                            {{ title }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ description }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <select
                            v-model="folder"
                            class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="applyFilters(true)"
                        >
                            <option
                                v-for="option in folders"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>

                        <span>{{ formattedRange }}</span>
                    </div>
                </div>
            </div>

            <div class="px-6 pb-6">
                <div
                    class="space-y-4 overflow-hidden rounded-2xl border border-gray-200 bg-white"
                >
                    <div class="border-b border-gray-200 p-4">
                        <div
                            class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]"
                        >
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-3"
                            >
                                <div class="w-full lg:w-64">
                                    <label
                                        class="mb-1 block text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Folder
                                    </label>
                                    <select
                                        v-model="folder"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option
                                            v-for="option in folders"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="w-full lg:w-72">
                                    <label
                                        class="mb-1 block text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Search
                                    </label>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search filename…"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @input="applyFilters(false)"
                                    />
                                </div>

                                <div class="w-full lg:w-40">
                                    <label
                                        class="mb-1 block text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Per page
                                    </label>
                                    <select
                                        v-model="perPage"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="12">12</option>
                                        <option value="24">24</option>
                                        <option value="48">48</option>
                                        <option value="96">96</option>
                                    </select>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Upload image
                                </h3>
                                <p class="mt-1 text-xs text-gray-500">
                                    Upload images to the selected folder.
                                </p>

                                <div class="mt-4 space-y-3">
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Upload to folder
                                        </label>
                                        <select
                                            v-model="uploadFolder"
                                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option
                                                v-for="option in folders"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        class="hidden"
                                        @change="onFileChange"
                                    />

                                    <div
                                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                            @click="pickFile"
                                        >
                                            Choose file(s)
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="
                                                isUploading || uploadFiles.length === 0
                                            "
                                            @click="uploadImage"
                                        >
                                            {{
                                                isUploading
                                                    ? 'Uploading…'
                                                    : uploadFiles.length > 1
                                                      ? `Upload ${uploadFiles.length} images`
                                                      : 'Upload image'
                                            }}
                                        </button>
                                    </div>

                                    <p
                                        v-if="uploadFiles.length"
                                        class="text-xs text-gray-600"
                                    >
                                        <template v-if="uploadFiles.length === 1">
                                            Selected: {{ uploadFiles[0].name }}
                                        </template>
                                        <template v-else>
                                            Selected: {{ uploadFiles.length }} images
                                        </template>
                                    </p>

                                    <div
                                        v-if="notice"
                                        class="rounded-lg px-3 py-2 text-sm"
                                        :class="
                                            notice.type === 'success'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-red-50 text-red-700'
                                        "
                                    >
                                        {{ notice.text }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div
                            v-if="media.data.length === 0"
                            class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center"
                        >
                            <p class="text-sm font-medium text-gray-700">
                                No images found.
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                Try another folder, search term, or upload a new
                                image.
                            </p>
                        </div>

                        <div
                            v-else
                            class="h-[184px] overflow-x-hidden overflow-y-auto rounded-xl bg-white ring-1 ring-black/5"
                        >
                            <div
                                class="grid grid-cols-4 gap-x-2 gap-y-4 p-2 sm:grid-cols-6 lg:grid-cols-8"
                                style="grid-auto-rows: 80px"
                            >
                                <div
                                    v-for="item in media.data"
                                    :key="item.path"
                                    class="group relative aspect-square overflow-hidden rounded-lg bg-gray-100 ring-1 ring-black/5"
                                >
                                    <button
                                        type="button"
                                        class="relative h-full w-full"
                                        :title="item.filename"
                                        @click="openPreview(item)"
                                    >
                                        <img
                                            :src="item.url"
                                            :alt="item.filename"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                            decoding="async"
                                        />

                                        <div
                                            class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-1 opacity-0 transition group-hover:opacity-100"
                                        >
                                            <div
                                                class="truncate text-[11px] font-medium text-white"
                                            >
                                                {{ item.filename }}
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="media.links?.length"
                            class="border-t border-gray-200 p-4"
                        >
                            <div class="flex flex-wrap gap-1">
                                <template
                                    v-for="link in media.links"
                                    :key="link.label + String(link.url)"
                                >
                                    <span
                                        v-if="!link.url"
                                        class="px-2 py-1 text-sm text-gray-400"
                                        v-html="link.label"
                                    />
                                    <Link
                                        v-else
                                        :href="link.url"
                                        class="rounded-md px-2 py-1 text-sm"
                                        :class="
                                            link.active
                                                ? 'bg-gray-900 text-white'
                                                : 'text-gray-700 hover:bg-gray-100'
                                        "
                                        preserve-scroll
                                        preserve-state
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="previewItem"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8"
                @click.self="closePreview"
            >
                <div
                    class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-5 py-4"
                    >
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                {{ previewItem.filename }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ previewItem.path }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!hasPreviousPreview"
                                @click="openPreviousPreview"
                            >
                                Previous
                            </button>

                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!hasNextPreview"
                                @click="openNextPreview"
                            >
                                Next
                            </button>

                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                @click="closePreview"
                            >
                                Close
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div
                            class="flex items-center justify-center bg-gray-100 p-4"
                        >
                            <img
                                :src="previewItem.url"
                                :alt="previewItem.filename"
                                class="max-h-[70vh] w-auto max-w-full rounded-lg object-contain"
                            />
                        </div>

                        <div class="space-y-4 border-l border-gray-200 p-5">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                >
                                    Folder
                                </p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{
                                        previewItem.folder === '__root__'
                                            ? 'Images Root'
                                            : previewItem.folder
                                    }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                >
                                    URL
                                </p>
                                <p class="mt-1 text-sm break-all text-gray-900">
                                    {{ previewItem.url }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Extension
                                    </p>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ previewItem.extension }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Size
                                    </p>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ previewItem.size_kb }} KB
                                    </p>
                                </div>
                            </div>

                            <div>
                                <p
                                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                >
                                    Modified
                                </p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ formatDate(previewItem.modified_at) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    @click="copyUrl(previewItem)"
                                >
                                    Copy URL
                                </button>

                                <a
                                    :href="previewItem.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Open in new tab
                                </a>

                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="
                                        isDeletingPath === previewItem.path
                                    "
                                    @click="deleteImage(previewItem)"
                                >
                                    {{
                                        isDeletingPath === previewItem.path
                                            ? 'Deleting…'
                                            : 'Delete'
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
