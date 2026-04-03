<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import SecondaryButton from '@/components/SecondaryButton.vue';

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
    extension?: string;
};

const props = withDefaults(
    defineProps<{
        show?: boolean;
        open?: boolean;
        selectedPath?: string | null;
        defaultFolder?: string;
    }>(),
    {
        selectedPath: null,
        defaultFolder: 'blog',
    },
);

const emit = defineEmits<{
    (e: 'update:show', value: boolean): void;
    (e: 'update:open', value: boolean): void;
    (e: 'select', path: string): void;
}>();

const isOpen = computed(() => props.show ?? props.open ?? false);

const setOpen = (value: boolean) => {
    emit('update:show', value);
    emit('update:open', value);
};

const loading = ref(false);
const deletingPath = ref<string | null>(null);
const uploading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const search = ref('');
const folder = ref(props.defaultFolder);
const uploadFolder = ref(props.defaultFolder);
const perPage = ref(24);

const folders = ref<FolderOption[]>([]);
const items = ref<MediaItem[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const normalizeComparablePath = (value: string | null | undefined) => {
    const trimmed = (value ?? '').trim();
    if (!trimmed) return '';

    try {
        const url = new URL(trimmed, window.location.origin);
        return url.pathname.replace(/\/+$/, '');
    } catch {
        return trimmed.replace(/^https?:\/\/[^/]+/i, '').replace(/\/+$/, '');
    }
};

const selectedItem = computed<MediaItem | null>(() => {
    if (!props.selectedPath) return null;

    const selected = normalizeComparablePath(props.selectedPath);

    return (
        items.value.find((item) => {
            const itemPath = normalizeComparablePath(item.path);
            const itemUrl = normalizeComparablePath(item.url);

            return selected === itemPath || selected === itemUrl;
        }) ?? null
    );
});

const isSelected = (item: MediaItem) => {
    const selected = normalizeComparablePath(props.selectedPath);
    const itemPath = normalizeComparablePath(item.path);
    const itemUrl = normalizeComparablePath(item.url);

    return selected !== '' && (selected === itemPath || selected === itemUrl);
};

const previewItem = ref<MediaItem | null>(null);
const uploadInputRef = ref<HTMLInputElement | null>(null);
const uploadFiles = ref<File[]>([]);

const formattedRange = computed(() => {
    if (total.value > 0) {
        const from =
            items.value.length === 0
                ? 0
                : (currentPage.value - 1) * perPage.value + 1;
        const to = Math.min(currentPage.value * perPage.value, total.value);
        return `Showing ${from}-${to} of ${total.value} images`;
    }

    return `${items.value.length} image${items.value.length === 1 ? '' : 's'}`;
});

const visiblePages = computed(() => {
    const totalPages = lastPage.value;
    const current = currentPage.value;

    let start = Math.max(1, current - 2);
    let end = Math.min(totalPages, start + 4);
    start = Math.max(1, end - 4);

    const pages: number[] = [];

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

const getCsrfToken = () => {
    const metaToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (metaToken) return metaToken;

    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (!match) return '';

    return decodeURIComponent(match[1]);
};

const clearMessages = () => {
    errorMessage.value = '';
    successMessage.value = '';
};

const close = () => {
    previewItem.value = null;
    setOpen(false);
};

const openUploadPicker = () => {
    uploadInputRef.value?.click();
};

const openPreview = (item: MediaItem) => {
    previewItem.value = item;
};

const closePreview = () => {
    previewItem.value = null;
};

const previewIndex = computed(() => {
    if (!previewItem.value) return -1;
    return items.value.findIndex((item) => item.path === previewItem.value?.path);
});

const hasPreviousPreview = computed(() => previewIndex.value > 0);
const hasNextPreview = computed(() => {
    return previewIndex.value >= 0 && previewIndex.value < items.value.length - 1;
});

const openPreviousPreview = () => {
    if (!hasPreviousPreview.value) return;
    previewItem.value = items.value[previewIndex.value - 1] ?? null;
};

const openNextPreview = () => {
    if (!hasNextPreview.value) return;
    previewItem.value = items.value[previewIndex.value + 1] ?? null;
};

const selectItem = (item: MediaItem) => {
    emit('select', item.path);
    close();
};

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

const fetchMediaPayload = async (targetFolder: string, page = 1) => {
    const params = new URLSearchParams({
        page: String(page),
        folder: targetFolder,
        search: search.value,
        per_page: String(perPage.value),
    });

    const response = await fetch(
        `${route('admin.media.feed')}?${params.toString()}`,
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        },
    );

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message || 'Failed to load media.');
    }

    return payload;
};

const applyPayload = (payload: any) => {
    folders.value = payload.folders || [];
    items.value = payload.media?.data || [];
    currentPage.value = payload.media?.current_page || 1;
    lastPage.value = payload.media?.last_page || 1;
    total.value = payload.media?.total || items.value.length || 0;

    if (
        !folders.value.some((option) => option.value === uploadFolder.value) &&
        folders.value.length
    ) {
        uploadFolder.value = folder.value;
    }

    if (previewItem.value) {
        previewItem.value =
            items.value.find((item) => item.path === previewItem.value?.path) ??
            null;
    }
};

const loadMedia = async (page = 1, allowFallback = true) => {
    if (!isOpen.value) return;

    loading.value = true;
    clearMessages();

    try {
        let payload = await fetchMediaPayload(folder.value, page);
        applyPayload(payload);

        const availableFolders = folders.value.map((option) => option.value);

        if (
            !availableFolders.includes(folder.value) &&
            availableFolders.length
        ) {
            folder.value = availableFolders[0];
            payload = await fetchMediaPayload(folder.value, 1);
            applyPayload(payload);
        }

        if (
            allowFallback &&
            items.value.length === 0 &&
            availableFolders.length
        ) {
            const preferredOrder = Array.from(
                new Set(
                    [
                        props.defaultFolder,
                        '__root__',
                        'blog',
                        ...availableFolders,
                    ].filter(Boolean),
                ),
            ) as string[];

            for (const candidate of preferredOrder) {
                if (
                    !availableFolders.includes(candidate) ||
                    candidate === folder.value
                )
                    continue;

                const candidatePayload = await fetchMediaPayload(candidate, 1);
                const candidateItems = candidatePayload.media?.data || [];

                if (candidateItems.length > 0) {
                    folder.value = candidate;
                    applyPayload(candidatePayload);
                    break;
                }
            }
        }
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to load media.';
    } finally {
        loading.value = false;
    }
};

const applySearch = () => {
    loadMedia(1, false);
};

const onUploadFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    uploadFiles.value = Array.from(input.files ?? []);
};

const uploadImage = async () => {
    if (uploadFiles.value.length === 0) {
        errorMessage.value = 'Choose at least one image first.';
        successMessage.value = '';
        return;
    }

    uploading.value = true;
    clearMessages();

    try {
        let lastUploadedPath: string | null = null;

        for (const file of uploadFiles.value) {
            const formData = new FormData();
            formData.append('folder', uploadFolder.value);
            formData.append('image', file);

            const response = await fetch(route('admin.media.store'), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || `Failed to upload ${file.name}.`);
            }

            lastUploadedPath = payload.item?.path ?? lastUploadedPath;
        }

        successMessage.value =
            uploadFiles.value.length === 1
                ? '1 image uploaded.'
                : `${uploadFiles.value.length} images uploaded.`;
        folder.value = uploadFolder.value;
        await loadMedia(1, false);

        if (lastUploadedPath) {
            const uploadedItem =
                items.value.find((item) => item.path === lastUploadedPath) ??
                null;

            previewItem.value = uploadedItem;
        }

        uploadFiles.value = [];

        if (uploadInputRef.value) {
            uploadInputRef.value.value = '';
        }
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to upload image.';
    } finally {
        uploading.value = false;
    }
};

const copyPath = async (path: string) => {
    clearMessages();

    try {
        await navigator.clipboard.writeText(path);
        successMessage.value = 'Image path copied.';
    } catch {
        errorMessage.value = 'Could not copy the image path.';
    }
};

const deleteItem = async (item: MediaItem) => {
    if (!window.confirm(`Delete ${item.filename || item.name}?`)) return;

    deletingPath.value = item.path;
    clearMessages();

    try {
        const response = await fetch(route('admin.media.destroy'), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                path: item.path,
            }),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || 'Failed to delete image.');
        }

        if (previewItem.value?.path === item.path) {
            previewItem.value = null;
        }

        successMessage.value = payload.message || 'Image deleted.';

        const nextPage =
            items.value.length === 1 && currentPage.value > 1
                ? currentPage.value - 1
                : currentPage.value;

        await loadMedia(nextPage, false);
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to delete image.';
    } finally {
        deletingPath.value = null;
    }
};

watch(
    () => isOpen.value,
    async (open) => {
        if (open) {
            search.value = '';
            folder.value = props.defaultFolder || '__root__';
            uploadFolder.value = folder.value;
            previewItem.value = null;
            await loadMedia(1, true);
        } else {
            clearMessages();
            uploadFiles.value = [];
            if (uploadInputRef.value) {
                uploadInputRef.value.value = '';
            }
            previewItem.value = null;
        }
    },
);

watch(folder, () => {
    if (isOpen.value) {
        uploadFolder.value = folder.value;
        loadMedia(1, false);
    }
});

watch(perPage, () => {
    if (isOpen.value) {
        loadMedia(1, false);
    }
});
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    >
        <div
            class="flex h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-[#f7f7f4] shadow-2xl"
        >
            <div
                class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4"
            >
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Media Library
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Upload, browse, and manage site images.
                    </p>
                </div>

                <SecondaryButton type="button" @click="close">
                    Close
                </SecondaryButton>
            </div>

            <div class="flex-1 overflow-auto p-4">
                <div
                    class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white"
                >
                    <div class="p-6 pb-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3
                                    class="text-2xl font-semibold text-gray-900"
                                >
                                    Media Library
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    The same browser from the sidebar, now
                                    available inside post editing.
                                </p>
                            </div>

                            <div
                                class="flex items-center gap-2 text-sm text-gray-500"
                            >
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
                                                @input="applySearch"
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
                                                <option :value="12">12</option>
                                                <option :value="24">24</option>
                                                <option :value="48">48</option>
                                                <option :value="96">96</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                                    >
                                        <h3
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            Upload image
                                        </h3>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Upload images to the selected
                                            folder.
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
                                                ref="uploadInputRef"
                                                type="file"
                                                accept="image/*"
                                                multiple
                                                class="hidden"
                                                @change="onUploadFileChange"
                                            />

                                            <div
                                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                                    @click="openUploadPicker"
                                                >
                                                    Choose file(s)
                                                </button>

                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                                                    :disabled="
                                                        uploading || uploadFiles.length === 0
                                                    "
                                                    @click="uploadImage"
                                                >
                                                    {{
                                                        uploading
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
                                                v-if="
                                                    errorMessage ||
                                                    successMessage
                                                "
                                                class="rounded-lg px-3 py-2 text-sm"
                                                :class="
                                                    successMessage
                                                        ? 'bg-emerald-50 text-emerald-700'
                                                        : 'bg-red-50 text-red-700'
                                                "
                                            >
                                                {{
                                                    successMessage ||
                                                    errorMessage
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4">
                                <div
                                    v-if="loading"
                                    class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center"
                                >
                                    <p
                                        class="text-sm font-medium text-gray-700"
                                    >
                                        Loading media…
                                    </p>
                                </div>

                                <div
                                    v-else-if="items.length === 0"
                                    class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center"
                                >
                                    <p
                                        class="text-sm font-medium text-gray-700"
                                    >
                                        No images found.
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Try another folder, search term, or
                                        upload a new image.
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
                                            v-for="item in items"
                                            :key="item.path"
                                            class="group relative aspect-square overflow-hidden rounded-lg bg-gray-100 ring-1 ring-black/5"
                                        >
                                            <button
                                                type="button"
                                                class="relative h-full w-full"
                                                :title="item.filename"
                                                @click="selectItem(item)"
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

                                            <div
                                                v-if="isSelected(item)"
                                                class="pointer-events-none absolute inset-0 ring-2 ring-indigo-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="lastPage > 1"
                                class="border-t border-gray-200 p-4"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-3"
                                >
                                    <SecondaryButton
                                        type="button"
                                        :disabled="currentPage <= 1"
                                        @click="
                                            loadMedia(currentPage - 1, false)
                                        "
                                    >
                                        Previous
                                    </SecondaryButton>

                                    <div class="flex flex-wrap gap-1">
                                        <button
                                            v-for="page in visiblePages"
                                            :key="page"
                                            type="button"
                                            class="rounded-md px-2 py-1 text-sm"
                                            :class="
                                                page === currentPage
                                                    ? 'bg-gray-900 text-white'
                                                    : 'text-gray-700 hover:bg-gray-100'
                                            "
                                            @click="loadMedia(page, false)"
                                        >
                                            {{ page }}
                                        </button>
                                    </div>

                                    <SecondaryButton
                                        type="button"
                                        :disabled="currentPage >= lastPage"
                                        @click="
                                            loadMedia(currentPage + 1, false)
                                        "
                                    >
                                        Next
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="previewItem"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 px-4 py-8"
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
                                Path
                            </p>
                            <p class="mt-1 text-sm break-all text-gray-900">
                                {{ previewItem.path }}
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
                                    {{ previewItem.extension || '—' }}
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
                                class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                @click="selectItem(previewItem)"
                            >
                                Use Image
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="copyPath(previewItem.path)"
                            >
                                Copy Path
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
                                :disabled="deletingPath === previewItem.path"
                                @click="deleteItem(previewItem)"
                            >
                                {{
                                    deletingPath === previewItem.path
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
</template>
