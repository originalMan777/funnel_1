<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import TrackedLink from '@/components/analytics/TrackedLink.vue'
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
        <Head title="Buyers Strategy" />

        <div class="mx-auto max-w-7xl space-y-20 px-4 py-16 md:px-6 lg:space-y-24">
            <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-stone-800 px-8 py-16 text-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] md:px-12 md:py-20">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.14),transparent_32%)]"></div>
                <div class="absolute inset-y-0 right-0 hidden w-[42%] bg-[linear-gradient(135deg,rgba(255,255,255,0.08),transparent)] lg:block"></div>

                <div class="relative z-10 max-w-3xl space-y-7">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/60">
                        Buyers Strategy
                    </p>

                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-white md:text-5xl lg:text-6xl">
                        Buy with more structure, more clarity, and less pressure
                    </h1>

                    <p class="max-w-2xl text-lg leading-8 text-white/78 md:text-xl">
                        The strongest buying decisions start before the first offer. This page is
                        built to help buyers approach the process with a real plan instead of
                        reacting to noise, pressure, and uncertainty.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <TrackedLink
                            href="/consultation"
                            cta-key="buyers_strategy.hero.consultation"
                            surface-key="buyers_strategy.hero"
                            class="inline-flex items-center rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-stone-100"
                        >
                            Book a Consultation
                        </TrackedLink>

                        <TrackedLink
                            href="/blog?category=buying"
                            cta-key="buyers_strategy.hero.articles"
                            surface-key="buyers_strategy.hero"
                            class="inline-flex items-center rounded-xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/10"
                        >
                            Explore Buying Articles
                        </TrackedLink>
                    </div>
                </div>
            </section>

            <section class="grid gap-10 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)] lg:items-start">
                <article class="rounded-[2rem] bg-white px-7 py-8 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 md:px-10 md:py-10">
                    <div class="mb-8 max-w-2xl border-b border-stone-200 pb-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Buyer Mindset
                        </p>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl">
                            Strategy matters long before the property search feels urgent
                        </h2>
                    </div>

                    <div class="max-w-3xl space-y-6 text-[15px] leading-8 text-gray-700 md:text-[16px]">
                        <p>
                            Buying a property is one of the biggest financial decisions most people
                            will ever make, but too many buyers enter the process without a real
                            strategy. They browse listings before understanding their numbers, chase
                            properties before clarifying their priorities, and react to pressure
                            before building a plan. That is usually where stress starts. A strong
                            buyers strategy changes that by creating structure before emotion takes
                            over.
                        </p>

                        <p>
                            The first part of any sound strategy is clarity. A buyer needs to know
                            what they are trying to accomplish, what kind of property actually fits
                            their life, and what financial boundaries make sense. It is easy to say
                            you want “the right house,” but the right house means something
                            different depending on your goals. Are you looking for a long-term home,
                            a stepping-stone property, or something that balances lifestyle with
                            investment value? Good decisions become easier once the goal is clear.
                        </p>

                        <p>
                            Financial readiness is another major factor. Buyers often focus only on
                            the purchase price, but the real conversation is broader. Monthly
                            affordability, closing costs, maintenance, location tradeoffs, and
                            future flexibility all matter. A buyers strategy should help reduce
                            surprises by thinking beyond the listing price and into the full cost of
                            ownership. That is where better preparation protects you from rushed or
                            emotionally driven decisions later.
                        </p>

                        <p>
                            Market conditions matter too, but they should not create panic. Buyers
                            do not need a dramatic reading of every rate shift or headline. They
                            need a practical understanding of how market pressure affects timing,
                            competition, and leverage. In some conditions, speed matters more. In
                            others, patience matters more. Strategy is what helps you know the
                            difference. Instead of reacting to noise, you move based on context.
                        </p>

                        <p>
                            The search itself should also be strategic. Too many buyers waste time
                            chasing homes that were never a good fit in the first place. A better
                            process narrows focus early. Location, commute, property condition,
                            layout, resale potential, and neighborhood dynamics should all be
                            considered deliberately. That creates a cleaner search, fewer dead ends,
                            and better emotional discipline when properties start to blur together.
                        </p>

                        <p>
                            Offers are where preparation becomes visible. A buyer who understands
                            the process and has a clear strategy approaches offers differently than a
                            buyer who is improvising under pressure. The best offer is not always
                            the highest one. Terms, timing, financing strength, contingencies, and
                            presentation all shape how an offer is received. A strategy page like
                            this exists because buyers need to understand that an offer is not just
                            a number — it is a structure.
                        </p>

                        <p>
                            Negotiation also becomes clearer when you know your boundaries. What
                            matters most? Price? Timing? Repairs? Closing terms? A buyer without
                            structure can get pulled in every direction. A buyer with strategy stays
                            anchored. That does not mean being rigid — it means knowing where
                            flexibility helps and where it hurts. Confidence in negotiation comes
                            from preparation, not bravado.
                        </p>

                        <p>
                            In the end, a good buyers strategy is not about making the process feel
                            dramatic. It is about making it feel understandable. When buyers have
                            clarity, context, and a real framework for moving forward, the process
                            stops feeling like chaos and starts feeling like a sequence of informed
                            decisions. That is the value of strategy: better decisions, less
                            confusion, and a much stronger sense of control throughout the entire
                            journey.
                        </p>
                    </div>
                </article>

                <aside class="space-y-6">
                    <div class="rounded-[2rem] bg-stone-50 p-6 shadow-[0_18px_50px_rgba(15,23,42,0.04)] ring-1 ring-black/5">
                        <div class="flex items-center justify-between gap-4 border-b border-stone-200 pb-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Buying Articles
                                </p>
                                <p class="mt-2 text-sm leading-6 text-gray-600">
                                    Recent posts from the buying category.
                                </p>
                            </div>

                            <Link
                                href="/blog?category=buying"
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
                                Buying articles will appear here once published posts exist in the
                                buying category.
                            </div>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="grid gap-6 md:grid-cols-3">
                <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">
                        Clarity Before You Move
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-gray-600">
                        Start by understanding what matters most to your finances, your timeline,
                        and your long-term goals before the search begins.
                    </p>
                </div>

                <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">
                        Strategy Over Guesswork
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-gray-600">
                        Better buying decisions come from structure, not pressure. Preparation,
                        context, and timing make the process far more manageable.
                    </p>
                </div>

                <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_50px_rgba(15,23,42,0.06)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">
                        Support That Makes Sense
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-gray-600">
                        You do not need noise. You need practical guidance that helps you move
                        clearly from planning to offer to closing.
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] bg-stone-50 px-8 py-10 ring-1 ring-black/5 md:px-10">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Ready to move with more confidence?
                        </p>
                        <h2 class="text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl">
                            Start the buying process with a clearer strategy
                        </h2>
                        <p class="text-sm leading-7 text-gray-600 md:text-base">
                            If you want a more structured approach before making major decisions,
                            book a consultation and get direct guidance tailored to your situation.
                        </p>
                    </div>

                    <div class="flex shrink-0">
                        <TrackedLink
                            href="/consultation"
                            cta-key="buyers_strategy.footer.consultation"
                            surface-key="buyers_strategy.footer"
                            class="inline-flex items-center rounded-xl bg-slate-900 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Book a Consultation
                        </TrackedLink>
                    </div>
                </div>
            </section>
        </div>
    </FrontLayout>
</template>
