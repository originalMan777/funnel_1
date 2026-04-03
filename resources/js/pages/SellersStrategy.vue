<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import FrontLayout from '@/layouts/FrontLayout.vue'

type StrategyArticle = {
    id: number
    title: string
    slug: string
    excerpt: string | null
    published_at: string | null
    category: {
        name: string
        slug: string
    } | null
    featured_image_url: string | null
}

withDefaults(
    defineProps<{
        articles?: StrategyArticle[]
    }>(),
    {
        articles: () => [],
    }
)
</script>

<template>
    <FrontLayout>
        <Head title="Sellers Strategy" />

        <div class="mx-auto max-w-7xl space-y-20 px-4 py-16 md:px-6 lg:space-y-24">
            <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-stone-950 via-slate-900 to-stone-800 px-8 py-16 text-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] md:px-12 md:py-20">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.14),transparent_32%)]"></div>
                <div class="absolute inset-y-0 right-0 hidden w-[42%] bg-[linear-gradient(135deg,rgba(255,255,255,0.08),transparent)] lg:block"></div>

                <div class="relative z-10 max-w-3xl space-y-7">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/60">
                        Sellers Strategy
                    </p>

                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-white md:text-5xl lg:text-6xl">
                        Sell with better preparation, stronger positioning, and clearer intent
                    </h1>

                    <p class="max-w-2xl text-lg leading-8 text-white/78 md:text-xl">
                        Stronger results usually start before the listing goes live. Sellers need
                        structure around timing, pricing, preparation, and how the property is
                        presented to the market.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <Link
                            href="/consultation"
                            class="inline-flex items-center rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-stone-100"
                        >
                            Book a Consultation
                        </Link>

                        <Link
                            href="/blog?category=selling"
                            class="inline-flex items-center rounded-xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/10"
                        >
                            Explore Selling Articles
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-10 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)] lg:items-start">
                <article class="rounded-[2rem] bg-white px-7 py-8 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 md:px-10 md:py-10">
                    <div class="mb-8 max-w-2xl border-b border-stone-200 pb-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Seller Mindset
                        </p>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl">
                            A better sale usually begins before the property ever goes live
                        </h2>
                    </div>

                    <div class="max-w-3xl space-y-6 text-[15px] leading-8 text-gray-700 md:text-[16px]">
                        <p>
                            Selling a property is not just about putting a listing online and
                            waiting for interest. The strongest outcomes usually come from
                            preparation, positioning, and the ability to understand how buyers are
                            reading the market. Sellers who move with a plan tend to make clearer
                            decisions than sellers who rely only on instinct or urgency.
                        </p>

                        <p>
                            The first part of a strong sellers strategy is understanding the role of
                            preparation. Presentation shapes perception. Buyers are not only judging
                            square footage or location — they are judging how ready a property
                            feels, how it compares to competing options, and whether it looks like a
                            confident listing or a rushed one. A seller strategy should begin with
                            identifying what can be improved, what should be left alone, and what
                            will actually influence buyer response.
                        </p>

                        <p>
                            Pricing is another major area where strategy matters. Many sellers think
                            the goal is simply to “get the most possible,” but pricing too
                            aggressively can create the wrong kind of attention or reduce momentum
                            entirely. On the other hand, pricing too low without purpose can leave
                            money on the table. A strong strategy looks at the market honestly,
                            considers local demand, and positions the property in a way that
                            supports the type of response you want.
                        </p>

                        <p>
                            Timing matters more than many people realize. Sellers often feel
                            pressure to list immediately, but immediate is not always optimal.
                            Market conditions, competing inventory, local trends, and buyer behavior
                            all shape how timing affects the strength of a launch. Strategy helps
                            sellers understand whether the current moment supports their goals or
                            whether a more deliberate timeline makes more sense.
                        </p>

                        <p>
                            Marketing and positioning also deserve more attention than they usually
                            get. The quality of the listing, the language used, the photography, and
                            the overall presentation all influence how a property is perceived.
                            Buyers make fast judgments. If the listing feels careless, they assume
                            the opportunity is weaker. If it feels thoughtfully presented, they
                            assume value and confidence. A strategy page like this exists because
                            sellers need to understand that listing quality is part of negotiation
                            power.
                        </p>

                        <p>
                            Once interest starts coming in, the process becomes less about
                            visibility and more about decision-making. Offers are not just about
                            price. Terms, timing, financing strength, contingencies, and
                            flexibility all matter. A strategic seller does not just react to the
                            biggest number on paper. A strategic seller evaluates the full structure
                            of the offer and how it supports the outcome they are actually trying to
                            achieve.
                        </p>

                        <p>
                            Negotiation becomes much easier when you already know your priorities.
                            What matters most: speed, certainty, price, convenience, or
                            flexibility? Without those answers, sellers can get pulled into
                            avoidable stress. With those answers, decisions become much cleaner.
                            Strategy helps define your boundaries before the pressure arrives.
                        </p>

                        <p>
                            In the end, a good sellers strategy is about control. Not controlling
                            the market, but controlling how you prepare, how you position your
                            property, and how you respond to what the market gives you. That is what
                            creates stronger outcomes: not panic, not guesswork, but preparation
                            backed by clear decision-making.
                        </p>
                    </div>
                </article>

                <aside class="space-y-6">
                    <div class="rounded-[2rem] bg-stone-50 p-6 shadow-[0_18px_50px_rgba(15,23,42,0.04)] ring-1 ring-black/5">
                        <div class="flex items-center justify-between gap-4 border-b border-stone-200 pb-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Selling Articles
                                </p>
                                <p class="mt-2 text-sm leading-6 text-gray-600">
                                    Recent posts from the selling category.
                                </p>
                            </div>

                            <Link
                                href="/blog?category=selling"
                                class="shrink-0 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 transition hover:text-gray-900"
                            >
                                View all
                            </Link>
                        </div>

                        <div class="mt-5 space-y-4">
                            <Link
                                v-for="article in articles"
                                :key="article.id"
                                :href="`/blog/${article.slug}`"
                                class="group block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <p class="text-base font-semibold leading-6 text-gray-900 transition group-hover:text-slate-700">
                                    {{ article.title }}
                                </p>

                                <p
                                    v-if="article.excerpt"
                                    class="mt-2 text-sm leading-6 text-gray-600"
                                >
                                    {{ article.excerpt }}
                                </p>

                                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">
                                    Read article
                                </p>
                            </Link>

                            <div
                                v-if="!articles.length"
                                class="rounded-2xl bg-white p-5 text-sm leading-6 text-gray-600 ring-1 ring-black/5"
                            >
                                Selling articles will appear here once published posts exist in the
                                selling category.
                            </div>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="grid gap-6 md:grid-cols-3">
                <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">
                        Position Your Property Properly
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-gray-600">
                        Better results begin with stronger preparation, cleaner presentation, and a
                        more deliberate market position.
                    </p>
                </div>

                <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">
                        Market With Intent
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-gray-600">
                        Timing, pricing, and visibility all work better when they support one clear
                        selling objective instead of reacting to pressure.
                    </p>
                </div>

                <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">
                        Built To Expand Later
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-gray-600">
                        This page is structured as a real landing page now and can expand later into
                        an even stronger full-service sales funnel.
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] bg-stone-50 px-8 py-10 ring-1 ring-black/5 md:px-10">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Ready to sell with more clarity?
                        </p>
                        <h2 class="text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl">
                            Build a stronger selling plan before you go live
                        </h2>
                        <p class="text-sm leading-7 text-gray-600 md:text-base">
                            If you want a more structured approach to pricing, preparation, and
                            timing, book a consultation and start with a clearer strategy.
                        </p>
                    </div>

                    <div class="flex shrink-0">
                        <Link
                            href="/consultation"
                            class="inline-flex items-center rounded-xl bg-slate-900 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Book a Consultation
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </FrontLayout>
</template>
