<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import TextInput from '@/components/TextInput.vue';

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
};

const props = withDefaults(
    defineProps<{
        open: boolean;
        selectedPath?: string | null;
        defaultFolder?: string;
    }>(),
    {
        selectedPath: null,
        defaultFolder: 'blog',
    }
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'select', path: string): void;
}>();

const loading = ref(false);
const deletingPath = ref<string | null>(null);
const uploading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const search = ref('');
const folder = ref(props.defaultFolder);
const perPage = ref(24);

const folders = ref<FolderOption[]>([]);
const items = ref<MediaItem[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const uploadInputRef = ref<HTMLInputElement | null>(null);

const getCsrfToken = () => {
    const metaToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (metaToken) {
        return metaToken;
    }

    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    if (!match) {
        return '';
    }

    return decodeURIComponent(match[1]);
};

const clearMessages = () => {
    errorMessage.value = '';
    successMessage.value = '';
};

const close = () => {
    emit('update:open', false);
};

const openUploadPicker = () => {
    uploadInputRef.value?.click();
};

const loadMedia = async (page = 1) => {
    if (!props.open) return;

    loading.value = true;
    clearMessages();

    try {
        const params = new URLSearchParams({
            page: String(page),
            folder: folder.value,
            search: search.value,
            per_page: String(perPage.value),
        });

        const response = await fetch(`/admin/media/feed?${params.toString()}`, {            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Failed to load media.');
        }

        folders.value = payload.folders || [];
        items.value = payload.media?.data || [];
        currentPage.value = payload.media?.current_page || 1;
        lastPage.value = payload.media?.last_page || 1;
        total.value = payload.media?.total || 0;

        if (!folders.value.find((f) => f.value === folder.value) && folders.value.length) {
            folder.value = folders.value[0].value;
        }
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to load media.';
    } finally {
        loading.value = false;
    }
};

const applySearch = () => {
    loadMedia(1);
};

const selectItem = (item: MediaItem) => {
    emit('select', item.path);
    emit('update:open', false);
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
    if (!window.confirm(`Delete ${item.name}?`)) {
        return;
    }

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

        successMessage.value = payload.message || 'Image deleted.';

        const nextPage =
            items.value.length === 1 && currentPage.value > 1
                ? currentPage.value - 1
                : currentPage.value;

        await loadMedia(nextPage);
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to delete image.';
    } finally {
        deletingPath.value = null;
    }
};

const onUploadChange = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    uploading.value = true;
    clearMessages();

    try {
        const formData = new FormData();
        formData.append('folder', folder.value);
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
            throw new Error(payload.message || 'Failed to upload image.');
        }

        successMessage.value = payload.message || 'Image uploaded.';
        await loadMedia(1);

        if (payload.item?.path) {
            emit('select', payload.item.path);
        }
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to upload image.';
    } finally {
        uploading.value = false;

        if (uploadInputRef.value) {
            uploadInputRef.value.value = '';
        }
    }
};

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

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            folder.value = props.defaultFolder;
            loadMedia(1);
        } else {
            clearMessages();
        }
    }
);

watch(folder, () => {
    if (props.open) {
        loadMedia(1);
    }
});
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    >
        <div class="flex h-[90vh] w-full max-w-7xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b px-5 py-4">
                <div>
                    <div class="text-lg font-semibold text-gray-900">Media Library</div>
                    <div class="text-sm text-gray-500">
                        Search, upload, reuse, copy, and delete images.
                    </div>
                </div>

                <SecondaryButton type="button" @click="close">
                    Close
                </SecondaryButton>
            </div>

            <div class="border-b px-5 py-4">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_140px_auto]">
                    <TextInput
                        v-model="search"
                        type="text"
                        placeholder="Search by filename…"
                        @keydown.enter.prevent="applySearch"
                    />

                    <select
                        v-model="folder"
                        class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option
                            v-for="folderOption in folders"
                            :key="folderOption.value"
                            :value="folderOption.value"
                        >
                            {{ folderOption.label }}
                        </option>
                    </select>

                    <PrimaryButton type="button" @click="applySearch">
                        Search
                    </PrimaryButton>

                    <div class="flex items-center justify-end gap-2">
                        <input
                            ref="uploadInputRef"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onUploadChange"
                        />

                        <SecondaryButton type="button" :disabled="uploading" @click="openUploadPicker">
                            {{ uploading ? 'Uploading…' : 'Upload Image' }}
                        </SecondaryButton>
                    </div>
                </div>

                <div v-if="errorMessage" class="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ errorMessage }}
                </div>

                <div v-if="successMessage" class="mt-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">
                    {{ successMessage }}
                </div>
            </div>

            <div class="flex-1 overflow-auto px-5 py-4">
                <div class="mb-4 flex items-center justify-between text-sm text-gray-600">
                    <div>{{ total }} image<span v-if="total !== 1">s</span></div>
                    <div>
                        Folder: <span class="font-medium text-gray-900">{{ folder }}</span>
                    </div>
                </div>

                <div v-if="loading" class="py-16 text-center text-sm text-gray-500">
                    Loading media…
                </div>

                <div v-else-if="items.length === 0" class="py-16 text-center text-sm text-gray-500">
                    No images found.
                </div>

                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div
                        v-for="item in items"
                        :key="item.path"
                        class="overflow-hidden rounded-lg border bg-white"
                        :class="selectedPath === item.path ? 'border-indigo-500 ring-2 ring-indigo-500' : ''"
                    >
                        <button type="button" class="block w-full" @click="selectItem(item)">
                            <img
                                :src="item.url"
                                :alt="item.name"
                                class="h-40 w-full object-cover"
                                loading="lazy"
                                decoding="async"
                            />
                        </button>

                        <div class="space-y-2 p-3">
                            <div class="truncate text-sm font-medium text-gray-900">
                                {{ item.name }}
                            </div>

                            <div class="text-xs text-gray-500">
                                {{ item.size_kb }} KB
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <SecondaryButton type="button" @click="selectItem(item)">
                                    Use Image
                                </SecondaryButton>

                                <SecondaryButton type="button" @click="copyPath(item.path)">
                                    Copy Path
                                </SecondaryButton>

                                <SecondaryButton
                                    type="button"
                                    :disabled="deletingPath === item.path"
                                    @click="deleteItem(item)"
                                >
                                    {{ deletingPath === item.path ? 'Deleting…' : 'Delete' }}
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="lastPage > 1" class="border-t px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <SecondaryButton
                        type="button"
                        :disabled="currentPage <= 1"
                        @click="loadMedia(currentPage - 1)"
                    >
                        Previous
                    </SecondaryButton>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            type="button"
                            class="rounded-md px-3 py-1 text-sm"
                            :class="
                                page === currentPage
                                    ? 'bg-indigo-600 text-white'
                                    : 'border border-gray-300 bg-white text-gray-700'
                            "
                            @click="loadMedia(page)"
                        >
                            {{ page }}
                        </button>
                    </div>

                    <SecondaryButton
                        type="button"
                        :disabled="currentPage >= lastPage"
                        @click="loadMedia(currentPage + 1)"
                    >
                        Next
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </div>
</template>
