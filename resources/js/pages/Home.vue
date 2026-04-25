<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import TrackedLink from '@/components/analytics/TrackedLink.vue'
import FrontLayout from '@/layouts/FrontLayout.vue'
import LeadSlotRenderer from '@/components/public/lead/LeadSlotRenderer.vue'
import buyingIcon from '@/images/icons/who-we-help/buying.svg'
import sellingIcon from '@/images/icons/who-we-help/selling.svg'
import rentingIcon from '@/images/icons/who-we-help/renting.svg'
import investingIcon from '@/images/icons/who-we-help/investing.svg'

const activeWhoWeHelp = ref(null)

const props = defineProps({
    featuredSliderCategories: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()

const defaultWhyNojoPoints = [
    { icon: '◔', text: 'Clear, honest guidance' },
    { icon: '◔', text: 'Focused on your specific situation' },
    { icon: '◔', text: 'Local market understanding' },
    { icon: '◔', text: 'Strategy before action' },
    { icon: '◔', text: 'No pressure — just direction you can trust' },
]

const defaultInsights = [
    {
        title: 'Buying in NJ',
        body: 'Know how to approach buying, what to watch for, and how to move forward with confidence.',
        href: '/buyers-strategy',
        label: 'Buyers',
        cta: 'See how to approach buying →',
    },
    {
        title: 'Selling Tips',
        body: 'Learn how to position, prepare, and sell your property with a clear strategy behind you.',
        href: '/sellers-strategy',
        label: 'Sellers',
        cta: 'Learn how to sell strategically →',
    },
    {
        title: 'Market Trends',
        body: 'Understand what’s happening in the market and how it impacts your next move.',
        href: '/blog?category=market-trends',
        label: 'Market',
        cta: 'Understand where the market is going →',
    },
]

const siteContent = computed(() => page.props.siteContent ?? {})
const whyNojoPoints = computed(() => siteContent.value?.home?.why_points ?? defaultWhyNojoPoints)
const insights = computed(() => siteContent.value?.home?.insights ?? defaultInsights)

const realFeaturedCategories = computed(() => props.featuredSliderCategories ?? [])

const featuredRepeatCount = 5

const denseFeaturedCategories = computed(() => {
    const items = realFeaturedCategories.value
    if (!items.length) return []

    return Array.from({ length: featuredRepeatCount }, () => items).flat()
})

const featuredViewport = ref(null)
const featuredIndex = ref(0)
const featuredAnimating = ref(false)
const featuredCardWidth = ref(352)
const featuredGap = 16

const featuredRealCount = computed(() => realFeaturedCategories.value.length)
const featuredMiddleCopyStart = computed(() => featuredRealCount.value * 2)

const featuredTrackStyle = computed(() => {
    const offset = featuredIndex.value * (featuredCardWidth.value + featuredGap)

    return {
        transform: `translate3d(-${offset}px, 0, 0)`,
        transition: featuredAnimating.value ? 'transform 450ms ease' : 'none',
        gap: `${featuredGap}px`,
    }
})

function measureFeaturedCardWidth() {
    if (!featuredViewport.value) return

    const probe = featuredViewport.value.querySelector('[data-featured-card]')
    if (!probe) return

    featuredCardWidth.value = probe.getBoundingClientRect().width
}

function jumpToFeaturedIndex(index) {
    featuredAnimating.value = false
    featuredIndex.value = index
}

function normalizeFeaturedIndex() {
    const realCount = featuredRealCount.value
    if (realCount <= 0) return

    const middleStart = featuredMiddleCopyStart.value
    const relative = ((featuredIndex.value % realCount) + realCount) % realCount

    jumpToFeaturedIndex(middleStart + relative)
}

function scrollFeatured(direction) {
    const realCount = featuredRealCount.value
    if (realCount <= 0 || featuredAnimating.value) return

    featuredAnimating.value = true
    featuredIndex.value += direction
}

async function handleFeaturedTransitionEnd() {
    const realCount = featuredRealCount.value
    if (realCount <= 0) {
        featuredAnimating.value = false
        return
    }

    const middleStart = featuredMiddleCopyStart.value
    const middleEnd = middleStart + realCount - 1

    if (featuredIndex.value < middleStart || featuredIndex.value > middleEnd) {
        const relative = ((featuredIndex.value % realCount) + realCount) % realCount

        featuredAnimating.value = false
        featuredIndex.value = middleStart + relative
        await nextTick()
        return
    }

    featuredAnimating.value = false
}

function onResize() {
    measureFeaturedCardWidth()
}

onMounted(async () => {
    await nextTick()
    measureFeaturedCardWidth()

    if (featuredRealCount.value > 0) {
        featuredIndex.value = featuredMiddleCopyStart.value
    }

    window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize)
})
</script>

<template>
    <FrontLayout>
        <section
            class="relative min-h-[68vh] w-full overflow-hidden bg-cover bg-[position:70%_center]"
            :style="{ backgroundImage: `url('/images/modern_kitchen_img1.jpg')` }"
        >
            <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/50 to-black/20"></div>

            <div
                class="relative z-10 mx-auto flex min-h-[68vh] max-w-[90rem] items-center px-6 py-16 md:px-10"
            >
                <div class="max-w-2xl space-y-6 text-white md:pl-4 lg:pl-8">
                    <h1
                        class="max-w-[13ch] text-4xl font-bold leading-[1.02] tracking-[-0.03em] md:text-5xl lg:text-[4.25rem]"
                    >
                        Make the Right Move — With Clear Guidance Behind You
                    </h1>

                    <p
                        class="max-w-xl text-lg leading-relaxed text-white/90 md:text-xl"
                    >
                        Before you buy, sell, or make your next move, get clear direction on what actually makes sense for your situation.
                    </p>

                    <div class="flex flex-wrap items-center gap-5 pt-2">
                        <TrackedLink
                            href="/consultation"
                            cta-key="home.hero.consultation"
                            surface-key="home.hero"
                            data-popup-trigger="current-page"
                            class="rounded-xl bg-white px-7 py-3.5 text-base font-semibold text-gray-900 shadow-md transition hover:bg-gray-100"
                        >
                            Book a Consultation
                        </TrackedLink>

                        <TrackedLink
                            href="/services"
                            cta-key="home.hero.services"
                            surface-key="home.hero"
                            class="rounded-xl border border-white/40 px-5 py-3 text-sm font-medium text-white/80 transition hover:border-white hover:bg-white/10 hover:text-white"
                        >
                            See How It Works
                        </TrackedLink>
                    </div>
                </div>
            </div>
        </section>

        <div class="space-y-14 py-14">
            <section class="w-full">
                <div class="mx-auto max-w-[90rem] px-6 py-20 md:px-10 md:py-22">
                    <div class="mx-auto max-w-3xl space-y-4 text-center">
                        <p class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase">
                            Who We Help
                        </p>

                        <h2 class="text-3xl font-semibold text-gray-900 md:text-4xl">
                            Guidance tailored to where you are right now
                        </h2>

                        <p class="text-lg text-gray-600">
                            No matter where you are in the process, you don’t need more information — you need to know what to do next.
                        </p>
                    </div>

                    <div class="mt-14">
                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div
                                class="group rounded-2xl border border-green-900/10 bg-white p-6 transition duration-300 hover:-translate-y-[3px] hover:border-green-900/20 hover:shadow-lg"
                                @mouseenter="activeWhoWeHelp = 'buying'"
                                @mouseleave="activeWhoWeHelp = null"
                                @focusin="activeWhoWeHelp = 'buying'"
                                @focusout="activeWhoWeHelp = null"
                            >
                                <h3 class="text-lg font-semibold text-green-900">
                                    Buying
                                </h3>

                                <p class="mt-4 text-sm leading-relaxed text-gray-600">
                                    Know what to look for, what to avoid, and how to move forward with confidence.
                                </p>

                                <div class="mt-8">
                                    <TrackedLink
                                        href="/buyers-strategy"
                                        cta-key="home.path.buyers"
                                        surface-key="home.who_we_help"
                                        class="text-sm font-medium text-gray-900 transition hover:text-green-900"
                                    >
                                        Start with buying →
                                    </TrackedLink>
                                </div>
                            </div>

                            <div
                                class="group rounded-2xl border border-gray-200 bg-white p-6 transition duration-300 hover:-translate-y-[3px] hover:border-green-900/20 hover:shadow-lg"
                                @mouseenter="activeWhoWeHelp = 'selling'"
                                @mouseleave="activeWhoWeHelp = null"
                                @focusin="activeWhoWeHelp = 'selling'"
                                @focusout="activeWhoWeHelp = null"
                            >
                                <h3 class="text-lg font-semibold text-green-900">
                                    Selling
                                </h3>

                                <p class="mt-4 text-sm leading-relaxed text-gray-600">
                                    Understand how to position, price, and prepare your property before it hits the market.
                                </p>

                                <div class="mt-8">
                                    <TrackedLink
                                        href="/sellers-strategy"
                                        cta-key="home.path.sellers"
                                        surface-key="home.who_we_help"
                                        class="text-sm font-medium text-gray-900 transition hover:text-green-900"
                                    >
                                        Plan your sale →
                                    </TrackedLink>
                                </div>
                            </div>

                            <div
                                class="group rounded-2xl border border-gray-200 bg-white p-6 transition duration-300 hover:-translate-y-[3px] hover:border-green-900/20 hover:shadow-lg"
                                @mouseenter="activeWhoWeHelp = 'renting'"
                                @mouseleave="activeWhoWeHelp = null"
                                @focusin="activeWhoWeHelp = 'renting'"
                                @focusout="activeWhoWeHelp = null"
                            >
                                <h3 class="text-lg font-semibold text-green-900">
                                    Renting
                                </h3>

                                <p class="mt-4 text-sm leading-relaxed text-gray-600">
                                    Make smarter decisions before committing to your next place.
                                </p>

                                <div class="mt-8">
                                    <Link
                                        href="/blog?category=renting"
                                        class="text-sm font-medium text-gray-900 transition hover:text-green-900"
                                    >
                                        Explore renting →
                                    </Link>
                                </div>
                            </div>

                            <div
                                class="group rounded-2xl border border-gray-200 bg-white p-6 transition duration-300 hover:-translate-y-[3px] hover:border-green-900/20 hover:shadow-lg"
                                @mouseenter="activeWhoWeHelp = 'investing'"
                                @mouseleave="activeWhoWeHelp = null"
                                @focusin="activeWhoWeHelp = 'investing'"
                                @focusout="activeWhoWeHelp = null"
                            >
                                <h3 class="text-lg font-semibold text-green-900">
                                    Investing
                                </h3>

                                <p class="mt-4 text-sm leading-relaxed text-gray-600">
                                    Understand opportunities, risks, and timing before putting money into the market.
                                </p>

                                <div class="mt-8">
                                    <Link
                                        href="/blog?category=investing"
                                        class="text-sm font-medium text-gray-900 transition hover:text-green-900"
                                    >
                                        Understand investing →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div class="flex min-h-[120px] items-center justify-center">
                                <img
                                    :src="buyingIcon"
                                    alt=""
                                    class="h-28 w-28 md:h-32 md:w-32 transition-opacity duration-200 ease-out"
                                    :class="activeWhoWeHelp === 'buying' ? 'opacity-20' : 'opacity-0'"
                                />
                            </div>

                            <div class="flex min-h-[120px] items-center justify-center">
                                <img
                                    :src="sellingIcon"
                                    alt=""
                                    class="h-28 w-28 md:h-32 md:w-32 transition-opacity duration-200 ease-out"
                                    :class="activeWhoWeHelp === 'selling' ? 'opacity-20' : 'opacity-0'"
                                />
                            </div>

                            <div class="flex min-h-[120px] items-center justify-center">
                                <img
                                    :src="rentingIcon"
                                    alt=""
                                    class="h-28 w-28 md:h-32 md:w-32 transition-opacity duration-200 ease-out"
                                    :class="activeWhoWeHelp === 'renting' ? 'opacity-20' : 'opacity-0'"
                                />
                            </div>

                            <div class="flex min-h-[120px] items-center justify-center">
                                <img
                                    :src="investingIcon"
                                    alt=""
                                    class="h-28 w-28 md:h-32 md:w-32 transition-opacity duration-200 ease-out"
                                    :class="activeWhoWeHelp === 'investing' ? 'opacity-20' : 'opacity-0'"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full bg-white py-20 md:py-22">
                <div class="mx-auto max-w-[96rem] px-6 md:px-10">
                    <div class="mb-8 flex items-end justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-green-900/70">
                                Featured Articles
                            </p>

                            <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
                                Strong starting points
                            </h2>
                        </div>

                        <div class="hidden items-center gap-2 md:flex">
                            <button
                                type="button"
                                @click="scrollFeatured(-1)"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-green-900/15 bg-white text-green-900 transition hover:border-green-900/25 hover:bg-green-50"
                                aria-label="Previous featured categories"
                            >
                                ←
                            </button>

                            <button
                                type="button"
                                @click="scrollFeatured(1)"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-green-900/15 bg-white text-green-900 transition hover:border-green-900/25 hover:bg-green-50"
                                aria-label="Next featured categories"
                            >
                                →
                            </button>
                        </div>
                    </div>

                    <div ref="featuredViewport" class="overflow-hidden">
                        <div
                            class="flex will-change-transform"
                            :style="featuredTrackStyle"
                            @transitionend="handleFeaturedTransitionEnd"
                        >
                            <div
                                v-for="(category, index) in denseFeaturedCategories"
                                :key="`${category.key}-${index}`"
                                data-featured-card
                                class="w-[20rem] shrink-0 rounded-3xl border border-green-950/20 bg-green-950 p-5 text-white shadow-sm transition duration-300 hover:-translate-y-[3px] hover:shadow-xl md:w-[21rem] xl:w-[22rem]"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-green-300/90">
                                            Category
                                        </p>

                                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-white">
                                            {{ category.title }}
                                        </h3>
                                    </div>

                                    <Link
                                        :href="category.href"
                                        class="text-sm font-medium text-green-200 transition hover:text-white"
                                    >
                                        View all →
                                    </Link>
                                </div>

                                <div class="mt-5 space-y-3">
                                    <Link
                                        v-for="article in category.articles"
                                        :key="article.id ?? article.title"
                                        :href="article.href"
                                        class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 transition duration-300 hover:border-white/20 hover:bg-white/10"
                                    >
                                        <img
                                            :src="article.image_url || '/images/blog/default-thumb.jpg'"
                                            :alt="article.title"
                                            class="h-14 w-14 shrink-0 rounded-lg object-cover"
                                        />

                                        <div class="min-w-0">
                                            <p class="line-clamp-2 text-sm font-medium leading-5 text-white transition group-hover:text-green-200">
                                                {{ article.title }}
                                            </p>
                                        </div>
                                    </Link>

                                    <div
                                        v-if="!category.articles.length"
                                        class="rounded-2xl border border-dashed border-white/15 bg-white/5 px-4 py-5 text-sm text-white/65"
                                    >
                                        No articles available in this category yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2 md:hidden">
                        <button
                            type="button"
                            @click="scrollFeatured(-1)"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-green-900/15 bg-white text-green-900 transition hover:border-green-900/25 hover:bg-green-50"
                            aria-label="Previous featured categories"
                        >
                            ←
                        </button>

                        <button
                            type="button"
                            @click="scrollFeatured(1)"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-green-900/15 bg-white text-green-900 transition hover:border-green-900/25 hover:bg-green-50"
                            aria-label="Next featured categories"
                        >
                            →
                        </button>
                    </div>
                </div>
            </section>

            <section class="w-full">
                <div class="mx-auto max-w-7xl px-6 py-20 md:px-10 md:py-22">
                    <div class="space-y-8">
                        <div class="mx-auto max-w-3xl space-y-4 text-center">
                            <p
                                class="text-sm font-semibold tracking-[0.18em] text-green-900/70 uppercase"
                            >
                                Why Nojo
                            </p>

                            <h2
                                class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                            >
                                Clear guidance, grounded strategy, and real direction
                            </h2>

                            <p
                                class="text-base leading-relaxed text-gray-600 md:text-lg"
                            >
                                Nojo is built for people who want clarity before they commit — not after they’ve already made a costly decision.
                            </p>
                        </div>

                        <div class="mx-auto max-w-5xl">
                            <div
                                class="rounded-3xl bg-white px-6 py-10 shadow-sm ring-1 ring-green-900/10 transition duration-300 hover:ring-green-900/20 md:px-8"
                            >
                                <div class="space-y-5">
                                    <div
                                v-for="point in whyNojoPoints"
                                        :key="point.text"
                                        class="flex items-start gap-4"
                                    >
                                        <span
                                            class="mt-[6px] h-[6px] w-[6px] rounded-full bg-green-900/70"
                                            aria-hidden="true"
                                        ></span>

                                        <p class="text-base font-medium text-gray-900">
                                            {{ point.text }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full">
                <div class="mx-auto max-w-[90rem] px-6 md:px-10">
                    <LeadSlotRenderer slotKey="home_mid" />
                </div>
            </section>

            <section class="w-full">
                <div class="mx-auto max-w-[90rem] px-6 py-20 md:px-10 md:py-22">
                    <div class="space-y-8">
                        <div class="mx-auto max-w-3xl space-y-4 text-center">
                            <p
                                class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
                            >
                                Insights
                            </p>

                            <h2
                                class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                            >
                                Real Estate Insights & Guidance
                            </h2>

                            <p
                                class="text-base leading-relaxed text-gray-600 md:text-lg"
                            >
                                Clear, practical insights designed to help you think better, avoid mistakes, and move with confidence.
                            </p>
                        </div>

                        <div class="mx-auto grid max-w-[90rem] gap-6 md:grid-cols-3">
                            <Link
                        v-for="item in insights"
                                :key="item.title"
                                :href="item.href"
                                class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-[3px] hover:border-green-900/20 hover:shadow-lg"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-green-900">
                                    {{ item.label }}
                                </p>

                                <h3
                                    class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 transition group-hover:text-green-900"
                                >
                                    {{ item.title }}
                                </h3>

                                <p class="mt-4 leading-relaxed text-gray-600">
                                    {{ item.body }}
                                </p>

                                <div class="mt-6">
                                    <span class="text-sm font-medium text-gray-900 transition group-hover:text-green-900">
                                        {{ item.cta }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </FrontLayout>
</template>
