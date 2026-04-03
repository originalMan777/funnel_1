<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/AppLayouts/PublicLayout.vue'

type TagDto = { name: string; slug: string }

type PostRow = {
    id: number
    title: string
    slug: string
    excerpt: string | null
    published_at: string
    featured_image_url: string | null
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Pagination<T> = { data: T[]; links: PaginationLink[] }

type SeoDto = { title: string; description: string; canonical_url: string }

const props = defineProps<{
    seo: SeoDto
    tag: TagDto
    posts: Pagination<PostRow>
}>()

const formatDate = (value: string) => {
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return value
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}
</script>

<template>
    <Head :title="seo.title">
        <meta name="description" :content="seo.description" />
        <link rel="canonical" :href="seo.canonical_url" />
    </Head>

    <PublicLayout>
        <div class="mx-auto max-w-7xl px-4 py-16 md:px-6 lg:py-20">
            <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-stone-800 px-8 py-14 text-white shadow-[0_30px_80px_rgba(15,23,42,0.24)] md:px-10 md:py-16">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.14),transparent_32%)]"></div>

                <div class="relative z-10 max-w-3xl space-y-5">
                    <Link
                        :href="route('blog.index')"
                        class="inline-flex items-center text-sm font-medium text-white/70 transition hover:text-white"
                    >
                        ← Back to Blog
                    </Link>

                    <div class="space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/55">
                            Tag Archive
                        </p>

                        <h1 class="text-4xl font-semibold tracking-tight text-white md:text-5xl">
                            {{ tag.name }}
                        </h1>

                        <p class="max-w-2xl text-base leading-7 text-white/78 md:text-lg">
                            {{ seo.description }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="mt-12 space-y-8">
                <div
                    v-if="posts.data.length === 0"
                    class="rounded-[2rem] bg-white px-8 py-12 text-center shadow-[0_18px_50px_rgba(15,23,42,0.05)] ring-1 ring-black/5"
                >
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                        Nothing here yet
                    </p>
                    <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">
                        No published posts with this tag yet
                    </h2>
                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-gray-600 md:text-base">
                        Once articles using this tag are published, they’ll appear here in a more
                        curated archive view.
                    </p>
                </div>

                <div v-else class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="post in posts.data"
                        :key="post.id"
                        class="group overflow-hidden rounded-[2rem] bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(15,23,42,0.10)]"
                    >
                        <Link
                            :href="route('blog.show', post.slug)"
                            class="block"
                        >
                            <div
                                v-if="post.featured_image_url"
                                class="relative overflow-hidden"
                            >
                                <img
                                    :src="post.featured_image_url"
                                    alt=""
                                    class="h-56 w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>
                            </div>

                            <div class="space-y-4 p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-600">
                                        {{ tag.name }}
                                    </span>

                                    <span class="text-xs font-medium uppercase tracking-[0.12em] text-gray-400">
                                        {{ formatDate(post.published_at) }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <h2 class="text-2xl font-semibold tracking-tight text-gray-900 transition group-hover:text-slate-700">
                                        {{ post.title }}
                                    </h2>

                                    <p
                                        v-if="post.excerpt"
                                        class="text-sm leading-7 text-gray-600 whitespace-pre-wrap"
                                    >
                                        {{ post.excerpt }}
                                    </p>
                                </div>

                                <div class="pt-2">
                                    <span class="text-sm font-semibold text-slate-900 transition group-hover:text-slate-700">
                                        Read article →
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </article>
                </div>

                <div
                    v-if="posts.links?.length"
                    class="pt-4"
                >
                    <div class="flex flex-wrap items-center gap-2 rounded-2xl bg-white p-4 shadow-[0_12px_35px_rgba(15,23,42,0.04)] ring-1 ring-black/5">
                        <template v-for="link in posts.links" :key="link.label">
                            <span
                                v-if="!link.url"
                                class="rounded-xl px-3 py-2 text-sm text-gray-400"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-xl px-3 py-2 text-sm font-medium transition"
                                :class="
                                    link.active
                                        ? 'bg-slate-900 text-white shadow-sm'
                                        : 'text-gray-700 hover:bg-stone-100'
                                "
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
