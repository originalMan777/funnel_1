<script setup lang="ts">
import { onMounted, ref } from 'vue';

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
        selectedPath?: string | null;
        preferredFolders?: string[];
        limit?: number;
    }>(),
    {
        selectedPath: null,
        preferredFolders: () => ['__root__', 'blog'],
        limit: 12,
    }
);

const emit = defineEmits<{
    (e: 'select', path: string): void;
}>();

const loading = ref(false);
const errorMessage = ref('');
const items = ref<MediaItem[]>([]);
const activeFolder = ref('');
const availableFolders = ref<FolderOption[]>([]);

const fetchFolder = async (folder: string) => {
    const params = new URLSearchParams({
        folder,
        page: '1',
        per_page: String(props.limit),
        search: '',
    });

    const response = await fetch(`${route('admin.media.feed')}?${params.toString()}`, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message || 'Failed to load media.');
    }

    return payload;
};

const loadTiles = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const initialPayload = await fetchFolder(props.preferredFolders[0] || '__root__');
        availableFolders.value = initialPayload.folders || [];

        const knownFolders = availableFolders.value.map((option) => option.value);
        const preferredOrder = Array.from(
            new Set([...props.preferredFolders, ...knownFolders].filter(Boolean))
        );

        let chosenItems = initialPayload.media?.data || [];
        let chosenFolder = initialPayload.filters?.folder || props.preferredFolders[0] || '__root__';

        if (chosenItems.length === 0) {
            for (const folder of preferredOrder) {
                if (!folder || folder === chosenFolder) continue;

                const payload = await fetchFolder(folder);
                const candidateItems = payload.media?.data || [];

                if (candidateItems.length > 0) {
                    chosenItems = candidateItems;
                    chosenFolder = payload.filters?.folder || folder;
                    break;
                }
            }
        }

        activeFolder.value = chosenFolder;
        items.value = chosenItems;
    } catch (error: any) {
        errorMessage.value = error?.message || 'Failed to load media.';
        items.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadTiles();
});
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-900">Library Tiles</div>
                <div class="mt-1 text-xs text-gray-500">
                    Click a tile to set the featured image.
                </div>
            </div>

            <button
                type="button"
                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                @click="loadTiles"
            >
                Refresh
            </button>
        </div>

        <div class="mt-3 text-xs text-gray-500">
            Folder: <span class="font-medium text-gray-700">{{ activeFolder || 'Loading…' }}</span>
        </div>

        <div
            v-if="loading"
            class="mt-4 flex h-40 items-center justify-center rounded-lg border border-dashed border-gray-300 text-sm text-gray-500"
        >
            Loading tiles…
        </div>

        <div
            v-else-if="errorMessage"
            class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
        >
            {{ errorMessage }}
        </div>

        <div
            v-else-if="items.length === 0"
            class="mt-4 flex h-40 items-center justify-center rounded-lg border border-dashed border-gray-300 text-sm text-gray-500"
        >
            No images available yet.
        </div>

        <div
            v-else
            class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-6"
        >
            <button
                v-for="item in items"
                :key="item.path"
                type="button"
                class="group relative aspect-square overflow-hidden rounded-lg bg-white ring-1 ring-black/5 transition hover:ring-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                :class="selectedPath === item.path ? 'ring-2 ring-indigo-500' : ''"
                :title="item.filename"
                @click="emit('select', item.path)"
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
                    <div class="truncate text-[11px] font-medium text-white">
                        {{ item.filename }}
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>
