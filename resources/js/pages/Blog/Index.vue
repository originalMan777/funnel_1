<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/layouts/FrontLayout.vue';
import LeadSlotRenderer from '@/components/public/lead/LeadSlotRenderer.vue';

type CategoryDto = {
    name: string;
    slug: string;
};

type PostRow = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    card_snippet: string | null;
    published_at: string;
    category: CategoryDto | null;
    featured_image_url: string | null;
};

type SectionBlock = {
    title: string;
    posts: PostRow[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Pagination<T> = {
    data: T[];
    links: PaginationLink[];
};

const props = defineProps<{
    posts: Pagination<PostRow>;
    categories: Array<{ name: string; slug: string; count: number }>;
    wideSection: SectionBlock | null;
    clusterSection: {
        left: SectionBlock | null;
        right: SectionBlock | null;
    };
}>();

const mainBlocks = computed(() => {
    const blocks: PostRow[][] = [];

    for (let i = 0; i < props.posts.data.length; i += 6) {
        blocks.push(props.posts.data.slice(i, i + 6));
    }

    return blocks;
});

const hasClusterSection = computed(
    () => Boolean(props.clusterSection.left || props.clusterSection.right),
);

const formatDate = (value: string) => {
    const d = new Date(value);

    if (Number.isNaN(d.getTime())) return value;

    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const badgeClasses = (category?: CategoryDto | null) => {
    const value = (category?.slug || category?.name || '').toString().trim().toLowerCase();

    if (value === 'food') {
        return 'border-2 border-green-600 text-green-700 bg-white';
    }

    if (value === 'dogs' || value === 'dog') {
        return 'border-2 border-blue-600 text-blue-700 bg-white';
    }

    if (value === 'trees' || value === 'tree') {
        return 'border-2 border-emerald-600 text-emerald-700 bg-white';
    }

    if (value === 'gold') {
        return 'border-2 border-amber-500 text-amber-700 bg-white';
    }

    if (value === 'news') {
        return 'border-2 border-slate-600 text-slate-700 bg-white';
    }

    if (value === 'guides' || value === 'guide') {
        return 'border-2 border-violet-600 text-violet-700 bg-white';
    }

    return 'border-2 border-rose-600 text-rose-700 bg-white';
};
</script>

<template>
    <FrontLayout>
        <Head title="Blog" />

        <div class="mx-auto max-w-6xl px-6 py-16">
            <section class="space-y-4">
                <p class="font-sans text-sm uppercase tracking-[0.22em] text-gray-500">
                    Journal
                </p>

                <h1
                    class="font-display text-6xl font-extrabold uppercase leading-none tracking-tight text-gray-950 md:text-7xl"
                >
                    Blog
                </h1>

                <p class="max-w-3xl font-sans text-base leading-relaxed text-gray-600 md:text-lg">
                    Real estate insights, guidance, and helpful articles for buyers,
                    sellers, and investors.
                </p>
            </section>

            <section class="mt-10">
                <div class="mx-auto max-w-3xl">
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Search articles..."
                            class="w-full rounded-full border border-gray-200 bg-white px-5 py-4 pr-12 text-base text-gray-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                        />

                        <svg
                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 3.473 9.765l3.63 3.631a.75.75 0 1 0 1.06-1.06l-3.63-3.632A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </section>

            <section class="mt-14 space-y-14">
                <div v-if="posts.data.length === 0" class="font-sans text-sm text-gray-600">
                    No posts yet.
                </div>

                <template v-else>
                    <template v-for="(block, blockIndex) in mainBlocks" :key="`block-${blockIndex}`">
                        <div class="grid grid-cols-1 gap-14 md:grid-cols-2">
                            <Link
                                v-for="post in block"
                                :key="post.id"
                                :href="route('blog.show', post.slug)"
                                class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_10px_45px_rgba(0,0,0,0.40)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_20px_60px_rgba(0,0,0,0.50)]"
                            >
                                <div class="relative overflow-hidden">
                                    <div class="absolute left-4 top-4 z-10">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] shadow-sm"
                                            :class="badgeClasses(post.category)"
                                        >
                                            {{ post.category?.name ?? 'Article' }}
                                        </span>
                                    </div>

                                    <img
                                        v-if="post.featured_image_url"
                                        :src="post.featured_image_url"
                                        :alt="post.title"
                                        class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105"
                                    />

                                    <div
                                        v-else
                                        class="aspect-[16/10] w-full bg-gray-100"
                                    />
                                </div>

                                <div class="p-7">
                                    <div class="flex flex-wrap items-center gap-3 font-sans text-xs text-gray-400">
                                        <span>{{ formatDate(post.published_at) }}</span>

                                        <template v-if="post.category">
                                            <span class="text-gray-300">•</span>
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700">
                                                {{ post.category.name }}
                                            </span>
                                        </template>
                                    </div>

                                    <h2
                                        class="font-display mt-4 text-[26px] font-extrabold uppercase leading-tight tracking-tight text-[#9ca3af] transition group-hover:text-gray-700"
                                    >
                                        {{ post.title }}
                                    </h2>

                                    <p
                                        v-if="post.card_snippet"
                                        class="mt-5 font-sans text-base leading-relaxed text-gray-600"
                                    >
                                        {{ post.card_snippet }}
                                    </p>

                                    <div
                                        class="mt-8 font-display text-sm font-extrabold uppercase tracking-wide text-[#9ca3af] transition group-hover:text-gray-700"
                                    >
                                        Read more »
                                    </div>

                                    <div class="mt-8 border-t border-gray-100 pt-5 font-sans text-sm text-gray-400">
                                        {{ formatDate(post.published_at) }}
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <section
                            v-if="blockIndex === 0 && wideSection"
                            class="space-y-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-display text-xl font-extrabold uppercase tracking-tight text-gray-900 md:text-2xl">
                                    {{ wideSection.title }}
                                </h2>
                            </div>

                            <div class="grid grid-cols-2 gap-5 lg:grid-cols-3 xl:grid-cols-6">
                                <Link
                                    v-for="post in wideSection.posts"
                                    :key="`wide-${post.id}`"
                                    :href="route('blog.show', post.slug)"
                                    class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_10px_35px_rgba(0,0,0,0.18)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_45px_rgba(0,0,0,0.24)]"
                                >
                                    <img
                                        v-if="post.featured_image_url"
                                        :src="post.featured_image_url"
                                        :alt="post.title"
                                        class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105"
                                    />
                                    <div v-else class="aspect-[4/3] w-full bg-gray-100" />

                                    <div class="p-4">
                                        <div class="font-sans text-[11px] uppercase tracking-[0.16em] text-gray-400">
                                            {{ formatDate(post.published_at) }}
                                        </div>
                                        <h3 class="mt-2 text-sm font-semibold leading-6 text-gray-900 transition group-hover:text-gray-700">
                                            {{ post.title }}
                                        </h3>
                                    </div>
                                </Link>
                            </div>
                        </section>

                        <LeadSlotRenderer
                            v-if="blockIndex === 1"
                            slot-key="blog_index_mid_lead"
                        />

                        <section
                            v-if="blockIndex === 1 && hasClusterSection"
                            class="space-y-4"
                        >
                            <div class="grid gap-8 lg:grid-cols-2">
                                <article
                                    v-if="clusterSection.left"
                                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-[0_10px_35px_rgba(0,0,0,0.18)]"
                                >
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <h2 class="font-display text-lg font-extrabold uppercase tracking-tight text-gray-900">
                                            {{ clusterSection.left.title }}
                                        </h2>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <Link
                                            v-for="post in clusterSection.left.posts"
                                            :key="`cluster-left-${post.id}`"
                                            :href="route('blog.show', post.slug)"
                                            class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.12)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_14px_32px_rgba(0,0,0,0.18)]"
                                        >
                                            <img
                                                v-if="post.featured_image_url"
                                                :src="post.featured_image_url"
                                                :alt="post.title"
                                                class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105"
                                            />
                                            <div v-else class="aspect-[4/3] w-full bg-gray-100" />
                                            <div class="p-3">
                                                <h3 class="text-sm font-semibold leading-5 text-gray-900 transition group-hover:text-gray-700">
                                                    {{ post.title }}
                                                </h3>
                                            </div>
                                        </Link>
                                    </div>
                                </article>

                                <article
                                    v-if="clusterSection.right"
                                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-[0_10px_35px_rgba(0,0,0,0.18)]"
                                >
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <h2 class="font-display text-lg font-extrabold uppercase tracking-tight text-gray-900">
                                            {{ clusterSection.right.title }}
                                        </h2>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <Link
                                            v-for="post in clusterSection.right.posts"
                                            :key="`cluster-right-${post.id}`"
                                            :href="route('blog.show', post.slug)"
                                            class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.12)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_14px_32px_rgba(0,0,0,0.18)]"
                                        >
                                            <img
                                                v-if="post.featured_image_url"
                                                :src="post.featured_image_url"
                                                :alt="post.title"
                                                class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105"
                                            />
                                            <div v-else class="aspect-[4/3] w-full bg-gray-100" />
                                            <div class="p-3">
                                                <h3 class="text-sm font-semibold leading-5 text-gray-900 transition group-hover:text-gray-700">
                                                    {{ post.title }}
                                                </h3>
                                            </div>
                                        </Link>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </template>

                    <div v-if="posts.links?.length" class="mt-12">
                        <div class="flex flex-wrap gap-2">
                            <template v-for="link in posts.links" :key="link.label">
                                <span
                                    v-if="!link.url"
                                    class="rounded-md px-3 py-2 text-sm text-gray-400"
                                    v-html="link.label"
                                />
                                <Link
                                    v-else
                                    :href="link.url"
                                    class="rounded-md px-3 py-2 text-sm transition"
                                    :class="link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                            </template>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </FrontLayout>
</template>
