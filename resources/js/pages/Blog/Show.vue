<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import FrontLayout from '@/layouts/FrontLayout.vue'
import LeadSlotRenderer from '@/components/public/lead/LeadSlotRenderer.vue'

type CategoryDto = {
  name: string;
  slug: string;
} | null

type TagDto = {
  id: number;
  name: string;
  slug: string;
}

type PostSeoDto = {
  title: string;
  description: string;
  canonical_url: string;
  robots?: string;
  og?: {
    type?: string;
    title?: string;
    description?: string;
    image?: string | null;
    url?: string;
  };
  twitter?: {
    card?: string;
    title?: string;
    description?: string;
    image?: string | null;
  };
}

type PostDto = {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  published_at: string | null;
  featured_image_url: string | null;
  content_html: string;
  sources?: string | null;
  category: CategoryDto;
  tags: TagDto[];
  seo: PostSeoDto;
}

type RelatedPostDto = {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  card_snippet?: string | null;
  published_at: string | null;
  featured_image_url: string | null;
  category: CategoryDto;
}

type AdjacentPostDto = {
  title: string;
  slug: string;
  featured_image_url: string | null;
} | null

type ArticleBlock =
  | { type: 'content'; html: string }
  | { type: 'lead'; slot: string }

  const relatedSidebarArticle = computed(() => relatedPosts.value[0] ?? null)

const props = defineProps<{
  post: PostDto;
  relatedPosts?: RelatedPostDto[];
  previousPost?: AdjacentPostDto;
  nextPost?: AdjacentPostDto;
}>()

const relatedPosts = computed(() => props.relatedPosts ?? [])
const previousPost = computed(() => props.previousPost ?? null)
const nextPost = computed(() => props.nextPost ?? null)

const page = usePage()
const siteContent = computed(() => page.props.siteContent ?? {})
const authorLabel = computed(() => siteContent.value?.blog?.author_label ?? 'Written by Awestruk.')

const authUser = page.props.auth?.user as
  | {
      is_admin?: boolean;
      role?: string | null;
    }
  | null
  | undefined

const canManagePosts = (() => {
  if (!authUser) return false
  if (authUser.is_admin) return true

  return ['super_admin', 'admin', 'editor'].includes(String(authUser.role ?? ''))
})()

const formatDate = (value: string | null) => {
  if (!value) return ''

  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value

  return d.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const isParagraphToken = (html: string) => {
  return /^<p\b[^>]*>[\s\S]*<\/p>$/i.test(html.trim())
}

const countWords = (html: string) => {
  const text = html
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim()

  if (!text) return 0

  return text.split(' ').filter(Boolean).length
}

const articleBlocks = computed<ArticleBlock[]>(() => {
  const html = String(props.post.content_html ?? '').trim()

  if (!html) {
    return []
  }

  const tokens = html
    .split(/(<p\b[^>]*>[\s\S]*?<\/p>)/gi)
    .filter((token) => token && token.trim() !== '')

  const blocks: ArticleBlock[] = []

  let currentHtml = ''
  let totalWords = 0
  let nextInsertionAt = 600
  let slotIndex = 1

  const flushContent = () => {
    if (!currentHtml.trim()) return

    blocks.push({
      type: 'content',
      html: currentHtml,
    })

    currentHtml = ''
  }

  for (const token of tokens) {
    currentHtml += token

    if (!isParagraphToken(token)) {
      continue
    }

    totalWords += countWords(token)
    flushContent()

    if (totalWords >= nextInsertionAt && slotIndex <= 4) {
      blocks.push({
        type: 'lead',
        slot: `blog_post_inline_${slotIndex}`,
      })

      slotIndex += 1
      nextInsertionAt += 500
    }
  }

  flushContent()

  return blocks
})
</script>

<template>
  <FrontLayout>
    <Head :title="post.seo?.title || post.title">
      <meta
        v-if="post.seo?.description"
        name="description"
        :content="post.seo.description"
      />
      <link
        v-if="post.seo?.canonical_url"
        rel="canonical"
        :href="post.seo.canonical_url"
      />
      <meta
        v-if="post.seo?.robots"
        name="robots"
        :content="post.seo.robots"
      />

      <meta
        v-if="post.seo?.og?.title"
        property="og:title"
        :content="post.seo.og.title"
      />
      <meta
        v-if="post.seo?.og?.description"
        property="og:description"
        :content="post.seo.og.description"
      />
      <meta
        v-if="post.seo?.og?.type"
        property="og:type"
        :content="post.seo.og.type"
      />
      <meta
        v-if="post.seo?.og?.url"
        property="og:url"
        :content="post.seo.og.url"
      />
      <meta
        v-if="post.seo?.og?.image"
        property="og:image"
        :content="post.seo.og.image"
      />

      <meta
        v-if="post.seo?.twitter?.card"
        name="twitter:card"
        :content="post.seo.twitter.card"
      />
      <meta
        v-if="post.seo?.twitter?.title"
        name="twitter:title"
        :content="post.seo.twitter.title"
      />
      <meta
        v-if="post.seo?.twitter?.description"
        name="twitter:description"
        :content="post.seo.twitter.description"
      />
      <meta
        v-if="post.seo?.twitter?.image"
        name="twitter:image"
        :content="post.seo.twitter.image"
      />
    </Head>

    <div class="mx-auto max-w-7xl lg:grid lg:grid-cols-[minmax(0,1fr)_240px] lg:gap-12 px-6 py-16">
      <div class="min-w-0">
        <section class="max-w-4xl">
          <div v-if="post.category" class="mb-5">
            <Link
              :href="route('blog.category', post.category.slug)"
              class="inline-flex items-center rounded-full border-2 border-slate-700 bg-white px-4 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-slate-800"
            >
              {{ post.category.name }}
            </Link>
          </div>

          <h1
            class="font-display text-4xl font-extrabold uppercase leading-[0.95] tracking-tight text-gray-950 md:text-5xl lg:text-6xl"
          >
            {{ post.title }}
          </h1>

          <p
            v-if="post.excerpt"
            class="mt-6 max-w-3xl font-sans text-lg leading-relaxed text-gray-600 md:text-xl"
          >
            {{ post.excerpt }}
          </p>

          <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 font-sans text-sm text-gray-500">
            <span v-if="post.published_at">{{ formatDate(post.published_at) }}</span>
            <template v-if="post.category">
              <span class="text-gray-300">•</span>
              <Link
                :href="route('blog.category', post.category.slug)"
                class="font-medium text-gray-600 hover:text-gray-900"
              >
                {{ post.category.name }}
              </Link>
            </template>
          </div>

          <div v-if="canManagePosts" class="mt-6">
            <Link
              :href="route('admin.posts.edit', post.id)"
              class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
            >
              Edit Article
            </Link>
          </div>
        </section>

        <section
          v-if="post.featured_image_url"
          class="mt-10 max-w-5xl"
        >
          <img
            :src="post.featured_image_url"
            :alt="post.title"
            class="w-full rounded-2xl object-cover shadow-[0_8px_16px_rgba(0,0,0,0.18)]"
          />
        </section>

        <section class="mt-14 max-w-3xl">
          <article class="min-w-0">
            <template v-for="(block, index) in articleBlocks" :key="index">
              <div
                v-if="block.type === 'content'"
                class="post-content font-sans text-gray-800"
                v-html="block.html"
              />

              <div v-else class="my-10">
                <LeadSlotRenderer :slot-key="block.slot" />
              </div>
            </template>
          </article>
        </section>

        <section class="mt-10 max-w-3xl">
          <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-[0_4px_10px_rgba(0,0,0,0.08)]">
            <div class="flex items-center justify-between gap-6">
              <Link
                v-if="previousPost"
                :href="route('blog.show', previousPost.slug)"
                class="flex items-center gap-3 max-w-[48%] group"
              >
                <img
                  v-if="previousPost.featured_image_url"
                  :src="previousPost.featured_image_url"
                  class="w-14 h-14 object-cover rounded-md shrink-0"
                />

                <div class="min-w-0">
                  <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">
                    Previous
                  </p>

                  <p class="mt-1 text-sm font-semibold text-gray-900 leading-tight line-clamp-2 group-hover:underline">
                    {{ previousPost.title }}
                  </p>
                </div>
              </Link>

              <Link
                v-if="nextPost"
                :href="route('blog.show', nextPost.slug)"
                class="flex items-center gap-3 max-w-[48%] ml-auto text-right group"
              >
                <div class="min-w-0">
                  <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">
                    Next
                  </p>

                  <p class="mt-1 text-sm font-semibold text-gray-900 leading-tight line-clamp-2 group-hover:underline">
                    {{ nextPost.title }}
                  </p>
                </div>

                <img
                  v-if="nextPost.featured_image_url"
                  :src="nextPost.featured_image_url"
                  class="w-14 h-14 object-cover rounded-md shrink-0"
                />
              </Link>
            </div>
          </div>
        </section>

        <section v-if="post.tags?.length" class="mt-14 max-w-3xl">
          <div class="border-t border-gray-200 pt-8">
            <h2 class="font-display text-lg font-extrabold uppercase tracking-wide text-gray-900">
              Tags
            </h2>

            <div class="mt-4 flex flex-wrap gap-2">
              <Link
                v-for="tag in post.tags"
                :key="tag.id"
                :href="route('blog.tag', tag.slug)"
                class="rounded-full bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
              >
                {{ tag.name }}
              </Link>
            </div>
          </div>
        </section>

        <section class="mt-14 max-w-3xl">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_4px_10px_rgba(0,0,0,0.10)]">
            <h2 class="font-display text-lg font-extrabold uppercase tracking-wide text-gray-900">
              About the Author
            </h2>

            <p class="mt-3 font-sans text-base leading-relaxed text-gray-600">
              {{ authorLabel }}
            </p>
          </div>
        </section>

        <section class="mt-14 max-w-5xl">
          <LeadSlotRenderer slot-key="blog_post_before_related" />
        </section>

        <section class="mt-14 max-w-6xl pb-8">
          <div class="border-t border-gray-200 pt-8">
            <h2 class="font-display text-2xl font-extrabold uppercase tracking-tight text-gray-900">
              Related Posts
            </h2>

            <div
              v-if="relatedPosts.length"
              class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3"
            >
              <article
                v-for="relatedPost in relatedPosts"
                :key="relatedPost.id"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-[0_8px_18px_rgba(0,0,0,0.08)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(0,0,0,0.10)]"
              >
                <Link
                  :href="route('blog.show', relatedPost.slug)"
                  class="block"
                >
                  <img
                    v-if="relatedPost.featured_image_url"
                    :src="relatedPost.featured_image_url"
                    :alt="relatedPost.title"
                    class="h-48 w-full object-cover"
                  />

                  <div class="p-5">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">
                      <span v-if="relatedPost.published_at">{{ formatDate(relatedPost.published_at) }}</span>
                      <template v-if="relatedPost.category">
                        <span class="text-gray-300">•</span>
                        <span>{{ relatedPost.category.name }}</span>
                      </template>
                    </div>

                    <h3 class="mt-3 font-display text-xl font-extrabold uppercase leading-tight text-gray-900">
                      {{ relatedPost.title }}
                    </h3>

                    <p
                      v-if="relatedPost.card_snippet || relatedPost.excerpt"
                      class="mt-3 font-sans text-sm leading-relaxed text-gray-600"
                    >
                      {{ relatedPost.card_snippet || relatedPost.excerpt }}
                    </p>
                  </div>
                </Link>
              </article>
            </div>

            <div
              v-else
              class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500"
            >
              No related posts available yet.
            </div>
          </div>
        </section>
      </div>

      <aside class="mt-12 lg:mt-0">
        <div class="lg:sticky lg:top-24">
          <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">
                In this post
              </p>
            </div>

            <div v-if="post.category" class="mt-5">
              <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-gray-900">
                Category
              </h3>
              <Link
                :href="route('blog.category', post.category.slug)"
                class="mt-2 inline-flex rounded-full bg-white px-3 py-1 text-sm font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100"
              >
                {{ post.category.name }}
              </Link>
            </div>

            <div v-if="post.published_at" class="mt-6">
              <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-gray-900">
                Published
              </h3>
              <p class="mt-2 font-sans text-sm text-gray-600">
                {{ formatDate(post.published_at) }}
              </p>
            </div>

            <div v-if="post.tags?.length" class="mt-6">
              <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-gray-900">
                Tags
              </h3>

              <div class="mt-3 flex flex-wrap gap-2">
                <Link
                  v-for="tag in post.tags"
                  :key="tag.id"
                  :href="route('blog.tag', tag.slug)"
                  class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100"
                >
                  {{ tag.name }}
                </Link>
              </div>
            </div>

            <div v-if="relatedSidebarArticle" class="mt-6">
        <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-gray-900">
            Related Article
        </h3>

        <Link
            :href="route('blog.show', relatedSidebarArticle.slug)"
            class="mt-3 block overflow-hidden rounded-2xl border border-gray-200 bg-white transition hover:bg-gray-50"
        >
            <img
            v-if="relatedSidebarArticle.featured_image_url"
            :src="relatedSidebarArticle.featured_image_url"
            :alt="relatedSidebarArticle.title"
            class="h-28 w-full object-cover"
            />

            <div class="p-3">
            <p class="text-sm font-semibold leading-snug text-gray-900">
                {{ relatedSidebarArticle.title }}
            </p>
            </div>
        </Link>
        </div>

            <div class="mt-6">
              <Link
                :href="route('blog.index')"
                class="inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700"
              >
                ← Back to blog
              </Link>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </FrontLayout>
</template>

<style scoped>
.post-content {
  font-size: 1.125rem;
  line-height: 1.85;
}

.post-content :deep(p) {
  margin: 1.25rem 0 0;
  color: rgb(55 65 81);
}

.post-content :deep(h2) {
  margin-top: 3rem;
  margin-bottom: 1rem;
  font-family: 'Montserrat', sans-serif;
  font-size: 1.875rem;
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
  text-transform: uppercase;
  color: rgb(17 24 39);
}

.post-content :deep(h3) {
  margin-top: 2rem;
  margin-bottom: 0.75rem;
  font-family: 'Montserrat', sans-serif;
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1.2;
  text-transform: uppercase;
  color: rgb(31 41 55);
}

.post-content :deep(ul),
.post-content :deep(ol) {
  margin: 1.25rem 0;
  padding-left: 1.5rem;
  color: rgb(55 65 81);
}

.post-content :deep(li) {
  margin: 0.5rem 0;
}

.post-content :deep(blockquote) {
  margin: 2rem 0;
  border-left: 4px solid rgb(156 163 175);
  padding-left: 1rem;
  font-size: 1.125rem;
  font-style: italic;
  color: rgb(75 85 99);
}

.post-content :deep(a) {
  color: rgb(79 70 229);
  text-decoration: underline;
  text-underline-offset: 3px;
}

.post-content :deep(strong) {
  font-weight: 700;
  color: rgb(17 24 39);
}

.post-content :deep(img) {
  margin: 2rem 0;
  width: 100%;
  border-radius: 1rem;
}

.post-content :deep(hr) {
  margin: 3rem 0;
  border: 0;
  border-top: 1px solid rgb(229 231 235);
}

.post-content :deep(pre) {
  margin: 2rem 0;
  overflow-x: auto;
  border-radius: 1rem;
  background: rgb(17 24 39);
  padding: 1rem 1.25rem;
  color: white;
}

.post-content :deep(code) {
  font-size: 0.95em;
}

@media (max-width: 1023px) {
  .post-content {
    font-size: 1.0625rem;
    line-height: 1.8;
  }
}
</style>
