<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import MediaLibraryModal from '@/components/admin/MediaLibraryModal.vue';
import PostMediaTiles from '@/components/admin/PostMediaTiles.vue';

import Quill from 'quill';
import 'quill/dist/quill.snow.css';

type OptionRow = {
    id: number;
    name: string;
    slug: string;
};

type PostDto = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    category_id: number | null;
    tag_ids: number[];
    sources: string | null;
    featured_image_path: string | null;
    featured_image_url: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    meta_title: string | null;
    meta_description: string | null;
    canonical_url: string | null;
    og_title: string | null;
    og_description: string | null;
    og_image_path: string | null;
    noindex: boolean;
};

const props = defineProps<{
    post: PostDto;
    categories: OptionRow[];
    tags: OptionRow[];
}>();

const page = usePage<any>();
const flashSuccess = computed(() => page.props?.flash?.success ?? '');
const isPublished = computed(() => props.post.status === 'published');

const formatPublishedDate = (value: string | null) => {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const categoriesOptions = ref<OptionRow[]>([...props.categories]);
const tagsOptions = ref<OptionRow[]>([...props.tags]);
const isCreatingCategory = ref(false);
const isCreatingTag = ref(false);
const categoryCreateError = ref('');
const tagCreateError = ref('');
const showMediaLibrary = ref(false);
const tagInput = ref('');
const categorySearch = ref('');
const tagSearch = ref('');
const QUICK_PICK_LIMIT = 6;
const showMoreCategories = ref(false);
const showMoreTags = ref(false);

const form = useForm({
    title: props.post.title ?? '',
    slug: props.post.slug ?? '',
    excerpt: props.post.excerpt ?? '',
    content: props.post.content ?? '',
    sources: props.post.sources ?? '',
    category_id: props.post.category_id ?? null,
    tag_ids: (props.post.tag_ids ?? []) as number[],
    new_category: '',
    new_tags: [] as string[],
    meta_title: props.post.meta_title ?? '',
    meta_description: props.post.meta_description ?? '',
    canonical_url: props.post.canonical_url ?? '',
    og_title: props.post.og_title ?? '',
    og_description: props.post.og_description ?? '',
    og_image_path: props.post.og_image_path ?? '',
    noindex: !!props.post.noindex,
    featured_image: null as File | null,
    featured_image_path: props.post.featured_image_path ?? props.post.featured_image_url ?? '',
    remove_featured_image: false,
});

const displayTitle = computed(() => form.title?.trim() || 'New Post');

const quillEditorRef = ref<Quill | null>(null);
const editorContainerRef = ref<HTMLDivElement | null>(null);

const featuredImageInputRef = ref<HTMLInputElement | null>(null);
const featuredImagePreviewUrl = ref<string | null>(null);

const featuredImageDisplayUrl = computed(() => {
    return featuredImagePreviewUrl.value || form.featured_image_path || null;
});

const selectedTags = computed(() =>
    tagsOptions.value.filter((tag) => form.tag_ids.includes(tag.id)),
);


const filteredCategoriesOptions = computed(() => {
    const search = categorySearch.value.trim().toLowerCase();

    if (!search) return categoriesOptions.value;

    return categoriesOptions.value.filter((category) => {
        return (
            category.name.toLowerCase().includes(search) ||
            category.slug.toLowerCase().includes(search)
        );
    });
});

const visibleCategories = computed(() =>
    filteredCategoriesOptions.value.slice(0, QUICK_PICK_LIMIT),
);

const overflowCategories = computed(() =>
    filteredCategoriesOptions.value.slice(QUICK_PICK_LIMIT),
);

const filteredTagsOptions = computed(() => {
    const search = tagSearch.value.trim().toLowerCase();

    if (!search) return tagsOptions.value;

    return tagsOptions.value.filter((tag) => {
        return (
            tag.name.toLowerCase().includes(search) ||
            tag.slug.toLowerCase().includes(search)
        );
    });
});

const visibleTags = computed(() =>
    filteredTagsOptions.value.slice(0, QUICK_PICK_LIMIT),
);

const overflowTags = computed(() =>
    filteredTagsOptions.value.slice(QUICK_PICK_LIMIT),
);

const overflowAvailableTags = computed(() =>
    overflowTags.value.filter((tag) => !form.tag_ids.includes(tag.id)),
);

const selectedTagSummary = computed(() => {
    if (!selectedTags.value.length) return 'Choose tags';

    if (selectedTags.value.length === 1) {
        return selectedTags.value[0].name;
    }

    return `${selectedTags.value.length} tags selected`;
});

const toggleTag = (tagId: number) => {
    if (form.tag_ids.includes(tagId)) {
        form.tag_ids = form.tag_ids.filter((id) => id !== tagId);
        return;
    }

    form.tag_ids = [...form.tag_ids, tagId];
};

const removeSelectedTag = (tagId: number) => {
    form.tag_ids = form.tag_ids.filter((id) => id !== tagId);
};


const selectOverflowCategory = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const value = target.value;

    if (!value) return;

    form.category_id = Number(value);
    target.value = '';
    showMoreCategories.value = false;
};

const selectOverflowTag = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const value = target.value;

    if (!value) return;

    const tagId = Number(value);

    if (!form.tag_ids.includes(tagId)) {
        form.tag_ids = [...form.tag_ids, tagId];
    }

    target.value = '';
    showMoreTags.value = false;
};

const setPreview = (file: File | null) => {
    if (featuredImagePreviewUrl.value) {
        URL.revokeObjectURL(featuredImagePreviewUrl.value);
        featuredImagePreviewUrl.value = null;
    }

    if (file) {
        featuredImagePreviewUrl.value = URL.createObjectURL(file);
    }
};

const openFilePicker = () => {
    featuredImageInputRef.value?.click();
};

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.featured_image = file;
    form.featured_image_path = '';
    form.remove_featured_image = false;

    setPreview(file);

    if (file && !form.og_image_path) {
        form.og_image_path = '';
    }
};

const selectFeaturedImage = (path: string) => {
    form.featured_image = null;
    form.featured_image_path = path;
    form.remove_featured_image = false;

    if (featuredImageInputRef.value) {
        featuredImageInputRef.value.value = '';
    }

    setPreview(null);

    if (!form.og_image_path) {
        form.og_image_path = path;
    }
};

const removeFeaturedImage = () => {
    form.featured_image = null;
    form.featured_image_path = '';
    form.remove_featured_image = true;

    if (featuredImageInputRef.value) {
        featuredImageInputRef.value.value = '';
    }

    setPreview(null);
};

const createTagNow = async () => {
    const name = tagInput.value.trim();

    if (!name || isCreatingTag.value) return;

    isCreatingTag.value = true;
    tagCreateError.value = '';

    try {
        const response = await axios.post(route('admin.tags.store'), { name }, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const created = response.data?.tag as OptionRow | undefined;

        if (!created) {
            throw new Error('Tag payload missing.');
        }

        if (!tagsOptions.value.some((tag) => tag.id === created.id)) {
            tagsOptions.value = [...tagsOptions.value, created].sort((a, b) =>
                a.name.localeCompare(b.name),
            );
        }

        if (!form.tag_ids.includes(created.id)) {
            form.tag_ids = [...form.tag_ids, created.id];
        }

        tagInput.value = '';
        tagSearch.value = '';
        delete (form.errors as Record<string, string>).new_tags;
    } catch (error: any) {
        tagCreateError.value =
            error?.response?.data?.errors?.name?.[0] ?? 'Unable to save tag right now.';
    } finally {
        isCreatingTag.value = false;
    }
};

const createCategoryNow = async () => {
    const name = form.new_category.trim();

    if (!name || isCreatingCategory.value) return;

    isCreatingCategory.value = true;
    categoryCreateError.value = '';

    try {
        const response = await axios.post(route('admin.categories.store'), { name }, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const created = response.data?.category as OptionRow | undefined;

        if (!created) {
            throw new Error('Category payload missing.');
        }

        if (!categoriesOptions.value.some((category) => category.id === created.id)) {
            categoriesOptions.value = [...categoriesOptions.value, created].sort((a, b) =>
                a.name.localeCompare(b.name),
            );
        }

        form.category_id = created.id;
        form.new_category = '';
        categorySearch.value = '';
        delete (form.errors as Record<string, string>).new_category;
    } catch (error: any) {
        categoryCreateError.value =
            error?.response?.data?.errors?.name?.[0] ?? 'Unable to save category right now.';
    } finally {
        isCreatingCategory.value = false;
    }
};

const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (!form.isDirty) return;
    e.preventDefault();
    e.returnValue = '';
};

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);

    if (editorContainerRef.value) {
        quillEditorRef.value = new Quill(editorContainerRef.value, {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['link', 'image'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ header: [1, 2, 3, false] }],
                    ['clean'],
                ],
            },
        });

        quillEditorRef.value.root.innerHTML = form.content;

        quillEditorRef.value.on('text-change', () => {
            form.content = quillEditorRef.value?.root.innerHTML || '';
        });
    }
});

onBeforeUnmount(() => {
    quillEditorRef.value = null;
    window.removeEventListener('beforeunload', beforeUnloadHandler);

    if (featuredImagePreviewUrl.value) {
        URL.revokeObjectURL(featuredImagePreviewUrl.value);
        featuredImagePreviewUrl.value = null;
    }
});

const backToPosts = () => {
    if (form.isDirty && !confirm('You have unsaved changes. Are you sure you want to leave?')) {
        return;
    }

    router.visit(route('admin.posts.index'));
};

const save = () => {
    form.put(route('admin.posts.update', props.post.id), {
        forceFormData: true,
    });
};

const publishing = ref(false);

const publish = () => {
    publishing.value = true;

    router.post(
        route('admin.posts.publish', props.post.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                publishing.value = false;
            },
        },
    );
};

const unpublish = () => {
    publishing.value = true;

    router.post(
        route('admin.posts.unpublish', props.post.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                publishing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Edit: ${post.title}`" />

    <AdminLayout>
        <div class="p-6">
            <form @submit.prevent="save" class="space-y-6">
                <div class="sticky top-0 z-40 border-b bg-white">
                    <div class="space-y-2 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ displayTitle }}
                                </div>
                                <div class="mt-1 text-sm text-gray-600">
                                    Status:
                                    <span class="font-medium">{{ isPublished ? 'Published' : 'Draft' }}</span>
                                    <template v-if="isPublished && post.published_at">
                                        <span class="mx-2 text-gray-400">•</span>
                                        <span>Published {{ formatPublishedDate(post.published_at) }}</span>
                                    </template>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <PrimaryButton type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Saving…' : 'Save' }}
                                </PrimaryButton>

                                <SecondaryButton
                                    v-if="isPublished"
                                    type="button"
                                    :disabled="publishing || form.processing"
                                    @click="unpublish"
                                >
                                    {{ publishing ? 'Working…' : 'Unpublish' }}
                                </SecondaryButton>

                                <SecondaryButton
                                    v-else
                                    type="button"
                                    :disabled="publishing || form.processing"
                                    @click="publish"
                                >
                                    {{ publishing ? 'Working…' : 'Publish' }}
                                </SecondaryButton>

                                <SecondaryButton type="button" @click="backToPosts">
                                    Back to Posts
                                </SecondaryButton>
                            </div>
                        </div>

                        <div v-if="flashSuccess" class="flex items-center gap-2 text-sm text-green-700">
                            <span class="font-semibold">✓</span>
                            <span>{{ flashSuccess }}</span>
                        </div>
                    </div>
                </div>

                <section class="space-y-4 rounded-lg border bg-white p-4">
                    <div class="text-sm font-semibold text-gray-900">Content</div>

                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            autofocus
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug (optional)" />
                        <TextInput
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.slug" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">
                            If blank, it will be generated from the title.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Content" />
                        <div id="editor" ref="editorContainerRef" class="mt-1 h-96"></div>
                        <InputError :message="form.errors.content" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="excerpt" value="Excerpt (optional)" />
                        <textarea
                            id="excerpt"
                            v-model="form.excerpt"
                            rows="3"
                            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError :message="form.errors.excerpt" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="sources" value="Sources (optional)" />
                        <textarea
                            id="sources"
                            v-model="form.sources"
                            rows="6"
                            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Plain text references…"
                        />
                        <InputError :message="form.errors.sources" class="mt-2" />
                    </div>
                </section>

                <section class="space-y-4 rounded-lg border bg-white p-4">
                    <div class="text-sm font-semibold text-gray-900">Media</div>

                    <div>
                        <div class="text-sm font-medium text-gray-900">Featured Image</div>

                        <input
                            ref="featuredImageInputRef"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onFileChange"
                        />

                        <div class="mt-3 grid gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
                            <div class="space-y-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div v-if="featuredImageDisplayUrl">
                                    <img
                                        :src="featuredImageDisplayUrl"
                                        alt=""
                                        class="h-48 w-full rounded-lg border object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                    <div class="mt-2 break-all text-xs text-gray-500">
                                        {{ form.featured_image_path || 'New upload selected' }}
                                    </div>
                                </div>

                                <div v-else class="flex h-48 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white text-sm text-gray-600">
                                    No featured image selected.
                                </div>

                                <div class="flex flex-col gap-2">
                                    <SecondaryButton type="button" @click="showMediaLibrary = true">
                                        Choose From Library
                                    </SecondaryButton>

                                    <SecondaryButton type="button" @click="openFilePicker">
                                        Upload New
                                    </SecondaryButton>

                                    <SecondaryButton
                                        v-if="featuredImageDisplayUrl"
                                        type="button"
                                        @click="removeFeaturedImage"
                                    >
                                        Remove Image
                                    </SecondaryButton>
                                </div>
                            </div>

                            <PostMediaTiles
                                :selected-path="form.featured_image_path || null"
                                @select="selectFeaturedImage"
                            />
                        </div>

                        <InputError :message="(form.errors as any).featured_image" class="mt-2" />
                        <InputError :message="(form.errors as any).featured_image_path" class="mt-2" />
                    </div>
                </section>

                
<section class="space-y-4 rounded-lg border bg-white p-4">
    <div class="text-sm font-semibold text-gray-900">Taxonomy</div>

    <div class="space-y-6">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <InputLabel value="Category (optional)" />
                    <button
                        v-if="overflowCategories.length"
                        type="button"
                        class="text-sm font-medium text-gray-500 underline-offset-2 hover:text-gray-900 hover:underline"
                        @click="showMoreCategories = !showMoreCategories"
                    >
                        {{ showMoreCategories ? 'Less' : 'More…' }}
                    </button>
                </div>

                <TextInput
                    v-model="categorySearch"
                    type="text"
                    class="block h-9 w-full"
                    placeholder="Search categories"
                    
                />

                <div class="max-h-40 overflow-y-auto pr-1">
                    <div class="flex flex-wrap gap-2">
                        <button
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm transition"
                        :class="form.category_id === null ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:text-gray-900'"
                        @click="form.category_id = null"
                    >
                        None
                    </button>

                    <button
                        v-for="category in visibleCategories"
                        :key="category.id"
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm transition"
                        :class="form.category_id === category.id ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:text-gray-900'"
                        @click="form.category_id = category.id"
                    >
                        {{ category.name }}
                    </button>
                                    </div>
                </div>

                <div v-if="showMoreCategories && overflowCategories.length" class="max-w-md">
                    <select
                        class="block h-9 w-full rounded-md border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="selectOverflowCategory"
                    >
                        <option value="">Choose more categories</option>
                        <option
                            v-for="category in overflowCategories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div class="max-w-md">
                    <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                        Add Category
                    </div>
                    <div class="flex items-center gap-2">
                        <TextInput
                            v-model="form.new_category"
                            type="text"
                            class="block h-9 w-full"
                            placeholder="New category name"
                            @keydown.enter.prevent="createCategoryNow"
                        />
                        <PrimaryButton
                            type="button"
                            class="h-9 shrink-0 px-3 text-sm"
                            :disabled="isCreatingCategory || !form.new_category.trim()"
                            @click="createCategoryNow"
                        >
                            {{ isCreatingCategory ? 'Adding…' : 'Add' }}
                        </PrimaryButton>
                    </div>
                    <InputError :message="categoryCreateError || form.errors.new_category" class="mt-2" />
                </div>

                <InputError :message="form.errors.category_id" class="mt-2" />
            </div>

            <div class="space-y-3 border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between gap-3">
                    <InputLabel value="Tags" />
                    <button
                        v-if="overflowAvailableTags.length"
                        type="button"
                        class="text-sm font-medium text-gray-500 underline-offset-2 hover:text-gray-900 hover:underline"
                        @click="showMoreTags = !showMoreTags"
                    >
                        {{ showMoreTags ? 'Less' : 'More…' }}
                    </button>
                </div>

                <TextInput
                    v-model="tagSearch"
                    type="text"
                    class="block h-9 w-full"
                    placeholder="Search tags"
                    
                />

                <div class="max-h-40 overflow-y-auto pr-1">
                    <div class="flex flex-wrap gap-2">
                        <button
                        v-for="tag in visibleTags"
                        :key="tag.id"
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm transition"
                        :class="form.tag_ids.includes(tag.id) ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:text-gray-900'"
                        @click="toggleTag(tag.id)"
                    >
                        {{ tag.name }}
                    </button>
                                    </div>
                </div>

                <div v-if="showMoreTags && overflowAvailableTags.length" class="max-w-md">
                    <select
                        class="block h-9 w-full rounded-md border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="selectOverflowTag"
                    >
                        <option value="">Choose more tags</option>
                        <option
                            v-for="tag in overflowAvailableTags"
                            :key="tag.id"
                            :value="tag.id"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                </div>

                <div class="max-w-md">
                    <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                        Add Tag
                    </div>
                    <div class="flex items-center gap-2">
                        <TextInput
                            v-model="tagInput"
                            type="text"
                            class="block h-9 w-full"
                            placeholder="New tag name"
                            @keydown.enter.prevent="createTagNow"
                        />
                        <PrimaryButton
                            type="button"
                            class="h-9 shrink-0 px-3 text-sm"
                            :disabled="isCreatingTag || !tagInput.trim()"
                            @click="createTagNow"
                        >
                            {{ isCreatingTag ? 'Adding…' : 'Add' }}
                        </PrimaryButton>
                    </div>
                    <InputError :message="tagCreateError || (form.errors as any).new_tags" class="mt-2" />
                </div>

                <div v-if="selectedTags.length" class="flex flex-wrap gap-2 pt-1">
                    <button
                        v-for="tag in selectedTags"
                        :key="tag.id"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200"
                        @click="removeSelectedTag(tag.id)"
                    >
                        <span>{{ tag.name }}</span>
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <InputError :message="form.errors.tag_ids" class="mt-2" />
            </div>
        </div>
</section>


                <section class="space-y-4 rounded-lg border bg-white p-4">
                    <div class="text-sm font-semibold text-gray-900">SEO</div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <InputLabel for="meta_title" value="Meta Title" />
                            <TextInput
                                id="meta_title"
                                v-model="form.meta_title"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.meta_title" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="canonical_url" value="Canonical URL" />
                            <TextInput
                                id="canonical_url"
                                v-model="form.canonical_url"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.canonical_url" class="mt-2" />
                        </div>

                        <div class="lg:col-span-2">
                            <InputLabel for="meta_description" value="Meta Description" />
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="form.errors.meta_description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="og_title" value="OG Title (Share Preview)" />
                            <TextInput
                                id="og_title"
                                v-model="form.og_title"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.og_title" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="og_image_path" value="OG Image (Share Preview)" />
                            <TextInput
                                id="og_image_path"
                                v-model="form.og_image_path"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.og_image_path" class="mt-2" />
                        </div>

                        <div class="lg:col-span-2">
                            <InputLabel for="og_description" value="OG Description (Share Preview)" />
                            <textarea
                                id="og_description"
                                v-model="form.og_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="form.errors.og_description" class="mt-2" />
                        </div>

                        <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-3">
                            <input
                                v-model="form.noindex"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700">Noindex this post</span>
                        </label>
                    </div>
                </section>
            </form>

            <MediaLibraryModal
                v-model:show="showMediaLibrary"
                :selected-path="form.featured_image_path || null"
                @select="selectFeaturedImage"
            />
        </div>
    </AdminLayout>
</template>
