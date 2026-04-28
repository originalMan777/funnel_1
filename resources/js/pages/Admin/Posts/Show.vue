<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';

type PostNavigatorItem = {
    id: number;
    title: string | null;
};

type PostNavigator = {
    previous: PostNavigatorItem | null;
    next: PostNavigatorItem | null;
};

type PostDto = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    sources: string | null;
    category_name: string | null;
    tag_names: string[];
    featured_image_url: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    updated_at: string | null;
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
    navigator: PostNavigator;
}>();

const isPublished = computed(() => props.post.status === 'published');
const publishing = ref(false);
const duplicateProcessing = ref(false);

const sectionIds = ['content', 'details', 'seo'] as const;
type SectionId = (typeof sectionIds)[number];
const activeSection = ref<SectionId>('content');
let sectionObserver: IntersectionObserver | null = null;
const sectionStorageKey = 'admin-post-show-section';

const normalizeSection = (value: string | null | undefined): SectionId => {
    if (value && sectionIds.includes(value as SectionId)) {
        return value as SectionId;
    }

    return 'content';
};

const readSectionFromUrl = (): SectionId => {
    if (typeof window === 'undefined') return 'content';

    const params = new URL(window.location.href).searchParams;
    return normalizeSection(params.get('section') ?? sessionStorage.getItem(sectionStorageKey));
};

const persistActiveSection = (section: SectionId) => {
    activeSection.value = section;

    if (typeof window !== 'undefined') {
        sessionStorage.setItem(sectionStorageKey, section);
    }
};

const scrollToSection = (section: SectionId) => {
    nextTick(() => {
        const target = document.getElementById(`post-show-section-${section}`);
        target?.scrollIntoView({ block: 'start', behavior: 'auto' });
    });
};

const setupSectionObserver = () => {
    if (typeof window === 'undefined') return;

    sectionObserver?.disconnect();

    const elements = sectionIds
        .map((section) => document.getElementById(`post-show-section-${section}`))
        .filter((element): element is HTMLElement => element instanceof HTMLElement);

    if (!elements.length) return;

    sectionObserver = new IntersectionObserver(
        (entries) => {
            const visibleEntries = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

            const visible = visibleEntries[0];
            const section = visible?.target.getAttribute('data-post-section');

            if (section) {
                persistActiveSection(normalizeSection(section));
            }
        },
        {
            rootMargin: '-120px 0px -55% 0px',
            threshold: [0.15, 0.35, 0.6],
        },
    );

    elements.forEach((element) => sectionObserver?.observe(element));
};


const formatDate = (value: string | null) => {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const backToPosts = () => {
    router.visit(route('admin.posts.index'));
};

const editPost = () => {
    router.visit(
        route('admin.posts.edit', {
            post: props.post.id,
            section: activeSection.value,
        }),
    );
};

const navigateToPost = (postId: number) => {
    persistActiveSection(activeSection.value);

    router.visit(
        route('admin.posts.show', {
            post: postId,
            section: activeSection.value,
        }),
    );
};

const publish = () => {
    publishing.value = true;

    persistActiveSection(activeSection.value);

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

    persistActiveSection(activeSection.value);

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

onMounted(() => {
    persistActiveSection(readSectionFromUrl());
    setupSectionObserver();
    scrollToSection(activeSection.value);
});

onBeforeUnmount(() => {
    sectionObserver?.disconnect();
    sectionObserver = null;
});

const duplicatePost = () => {
    if (!window.confirm(`Duplicate "${props.post.title}" as a draft?`)) return;

    duplicateProcessing.value = true;

    router.post(
        route('admin.posts.duplicate', props.post.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                duplicateProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`View: ${post.title}`" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="sticky top-0 z-40 border-b bg-white">
                <div class="py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ post.title }}
                            </div>

                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        isPublished
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-gray-100 text-gray-700'
                                    "
                                >
                                    {{ post.status }}
                                </span>

                                <span>Slug: /{{ post.slug }}</span>

                                <span v-if="post.category_name">• {{ post.category_name }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <SecondaryButton
                                v-if="navigator.previous"
                                type="button"
                                :disabled="publishing"
                                @click="navigateToPost(navigator.previous.id)"
                            >
                                ← Previous
                            </SecondaryButton>

                            <SecondaryButton
                                v-if="navigator.next"
                                type="button"
                                :disabled="publishing"
                                @click="navigateToPost(navigator.next.id)"
                            >
                                Next →
                            </SecondaryButton>
                            <PrimaryButton type="button" @click="editPost">
                                Edit
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                :disabled="duplicateProcessing || publishing"
                                @click="duplicatePost"
                            >
                                {{ duplicateProcessing ? 'Duplicating…' : 'Duplicate' }}
                            </SecondaryButton>


                            <SecondaryButton
                                v-if="isPublished"
                                type="button"
                                :disabled="publishing"
                                @click="unpublish"
                            >
                                {{ publishing ? 'Working…' : 'Unpublish' }}
                            </SecondaryButton>

                            <SecondaryButton
                                v-else
                                type="button"
                                :disabled="publishing"
                                @click="publish"
                            >
                                {{ publishing ? 'Working…' : 'Publish' }}
                            </SecondaryButton>

                            <SecondaryButton type="button" @click="backToPosts">
                                Back to Posts
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <article id="post-show-section-content" data-post-section="content" class="scroll-mt-28 rounded-lg border bg-white p-6">
                    <div v-if="post.featured_image_url" class="mb-6">
                        <img
                            :src="post.featured_image_url"
                            alt=""
                            class="w-full rounded-lg border object-cover"
                            loading="lazy"
                            decoding="async"
                        />
                    </div>

                    <div v-if="post.excerpt" class="mb-6 text-base leading-7 text-gray-600">
                        {{ post.excerpt }}
                    </div>

                    <div
                        class="prose max-w-none"
                        v-html="post.content"
                    />

                    <div v-if="post.sources" class="mt-10 border-t pt-6">
                        <h2 class="text-base font-semibold text-gray-900">Sources</h2>
                        <pre class="mt-3 whitespace-pre-wrap text-sm text-gray-700">{{ post.sources }}</pre>
                    </div>
                </article>

                <aside class="space-y-6">
                    <section id="post-show-section-details" data-post-section="details" class="scroll-mt-28 rounded-lg border bg-white p-4">
                        <div class="text-sm font-semibold text-gray-900">Post Details</div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Category</dt>
                                <dd class="text-gray-900">{{ post.category_name || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Published</dt>
                                <dd class="text-gray-900">{{ formatDate(post.published_at) }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Updated</dt>
                                <dd class="text-gray-900">{{ formatDate(post.updated_at) }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Tags</dt>
                                <dd class="text-gray-900">
                                    <div v-if="post.tag_names.length" class="mt-1 flex flex-wrap gap-2">
                                        <span
                                            v-for="tag in post.tag_names"
                                            :key="tag"
                                            class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section id="post-show-section-seo" data-post-section="seo" class="scroll-mt-28 rounded-lg border bg-white p-4">
                        <div class="text-sm font-semibold text-gray-900">SEO</div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Meta Title</dt>
                                <dd class="text-gray-900 break-words">{{ post.meta_title || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Meta Description</dt>
                                <dd class="text-gray-900 break-words">{{ post.meta_description || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Canonical URL</dt>
                                <dd class="text-gray-900 break-words">{{ post.canonical_url || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">OG Title</dt>
                                <dd class="text-gray-900 break-words">{{ post.og_title || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">OG Description</dt>
                                <dd class="text-gray-900 break-words">{{ post.og_description || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">OG Image Path</dt>
                                <dd class="text-gray-900 break-words">{{ post.og_image_path || '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Noindex</dt>
                                <dd class="text-gray-900">{{ post.noindex ? 'Yes' : 'No' }}</dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
        </div>
    </AdminLayout>
</template>
