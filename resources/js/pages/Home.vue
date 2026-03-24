<script setup>
import { Link } from '@inertiajs/vue3';
import FrontLayout from '@/layouts/FrontLayout.vue';
import LeadSlotRenderer from '@/components/public/lead/LeadSlotRenderer.vue';
import coverImage from '@/images/cover-1.png';

const featuredRows = [
    {
        eyebrow: 'Featured Articles',
        title: 'A few strong starting points',
        description:
            'Six quick article entry points organized as three featured category groups.',
        categories: [
            {
                title: 'Buying',
                links: [
                    {
                        title: 'Offer strategy basics',
                        summary:
                            'How to stay competitive without overreaching.',
                    },
                    {
                        title: 'Inspection mindset',
                        summary: 'What to expect and what actually matters.',
                    },
                ],
            },
            {
                title: 'Selling',
                links: [
                    {
                        title: 'Prep checklist',
                        summary: 'The changes that move the needle fastest.',
                    },
                    {
                        title: 'Pricing signals',
                        summary: 'What your early showing data is telling you.',
                    },
                ],
            },
            {
                title: 'Market',
                links: [
                    {
                        title: 'Rates + demand',
                        summary: 'A simple framework for reading momentum.',
                    },
                    {
                        title: 'Seasonality',
                        summary: 'Why timing shifts and how to plan around it.',
                    },
                ],
            },
        ],
    },
    {
        eyebrow: 'Featured Articles',
        title: 'Next reads, if you have five minutes',
        description:
            'Another featured row with three more categories and two article tiles in each.',
        categories: [
            {
                title: 'Neighborhoods',
                links: [
                    {
                        title: 'Commute tradeoffs',
                        summary: 'How to balance schools, space, and time.',
                    },
                    {
                        title: 'Shortlisting',
                        summary: 'A clean way to narrow choices quickly.',
                    },
                ],
            },
            {
                title: 'Financing',
                links: [
                    {
                        title: 'Pre-approval timing',
                        summary: 'When to lock and what documents matter.',
                    },
                    {
                        title: 'Closing costs',
                        summary: 'What to budget for beyond the down payment.',
                    },
                ],
            },
            {
                title: 'Process',
                links: [
                    {
                        title: 'Timeline planning',
                        summary: 'A simple way to map milestones to dates.',
                    },
                    {
                        title: 'Negotiation clarity',
                        summary: 'What to push on, what to let go.',
                    },
                ],
            },
        ],
    },
];

const whoWeHelpTiles = [
    {
        title: 'First-time buyers',
        body: 'A clearer plan for your search, offers, and next steps without the overwhelm.',
        kicker: 'Start buying smarter',
    },
    {
        title: 'Sellers',
        body: 'Pricing, prep, positioning, and timing built around your goals and timeline.',
        kicker: 'Sell with confidence',
    },
    {
        title: 'Investors',
        body: 'Evaluate opportunities with sharper comps, rent math, and a more grounded strategy.',
        kicker: 'Run the numbers',
    },
    {
        title: 'Relocating clients',
        body: 'Neighborhood guidance, commute tradeoffs, and a cleaner shortlist faster.',
        kicker: 'Relocate with clarity',
    },
];

const services = [
    {
        title: 'Consultation',
        body: 'One-on-one guidance tailored to your real estate situation, goals, and next steps.',
    },
    {
        title: 'Buyer Strategy',
        body: 'Clear direction for evaluating options, understanding the process, and moving toward the right purchase.',
    },
    {
        title: 'Seller Strategy',
        body: 'Strategic support for preparing, pricing, and positioning your property before entering the market.',
    },
    {
        title: 'Market Evaluation',
        body: 'Insight into local conditions, timing, and market realities so you can move with more clarity.',
    },
];

const whyNojoPoints = [
    'Clear, honest guidance',
    'Focused on your specific situation',
    'Local market understanding',
    'Strategy before action',
    'No pressure — just direction you can trust',
];

const insights = [
    {
        title: 'Buying in New Jersey',
        body: 'Helpful guidance for buyers preparing to make a move in the New Jersey market with more clarity and confidence.',
    },
    {
        title: 'Selling Tips',
        body: 'Practical advice for homeowners getting ready to position, prepare, and sell their property effectively.',
    },
    {
        title: 'Market Trends',
        body: 'Stay informed about changing market conditions and what they may mean for your next real estate decision.',
    },
];
</script>

<template>
    <FrontLayout>
        <section
            class="relative min-h-[85vh] w-full overflow-hidden rounded-[2rem] bg-cover bg-center"
            :style="{ backgroundImage: `url(${coverImage})` }"
        >
            <div class="absolute inset-0 bg-black/45"></div>

            <div
                class="relative z-10 mx-auto flex min-h-[85vh] max-w-7xl items-center px-6 py-16 md:px-10"
            >
                <div class="max-w-2xl space-y-6 text-white">
                    <h1
                        class="text-4xl leading-tight font-bold md:text-5xl lg:text-6xl"
                    >
                        Clear Real Estate Guidance for Your Next Move
                    </h1>

                    <p
                        class="max-w-xl text-lg leading-relaxed text-white/90 md:text-xl"
                    >
                        Helping buyers, sellers, and investors make confident
                        property decisions with professional insight and
                        strategy.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <Link
                            href="/consultation"
                            data-popup-trigger="current-page"
                            class="rounded-xl bg-white px-6 py-3 font-medium text-gray-900 shadow-sm"
                        >
                            Book a Consultation
                        </Link>

                        <Link
                            href="/services"
                            class="rounded-xl border border-white/70 px-6 py-3 font-medium text-white"
                        >
                            View Services
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl space-y-20 px-2 py-16 md:px-4">
            <section class="space-y-8">
                <div class="mx-auto max-w-3xl space-y-4 text-center">
                    <p
                        class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
                    >
                        Who We Help
                    </p>

                    <h2
                        class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                    >
                        Real estate guidance for every stage of the journey
                    </h2>

                    <p
                        class="text-base leading-relaxed text-gray-600 md:text-lg"
                    >
                        Whether you're buying your first property, preparing to
                        sell, or planning your next move, Nojo helps you
                        approach the process with more clarity and confidence.
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div
                        v-for="tile in whoWeHelpTiles"
                        :key="tile.title"
                        class="flex aspect-[10/11] flex-col justify-between rounded-3xl bg-white p-7 shadow-sm ring-1 ring-black/5"
                    >
                        <div class="space-y-3">
                            <h3
                                class="text-xl font-semibold tracking-tight text-gray-900"
                            >
                                {{ tile.title }}
                            </h3>
                            <p class="text-sm leading-relaxed text-gray-600">
                                {{ tile.body }}
                            </p>
                        </div>
                        <div class="pt-6 text-sm font-medium text-gray-900">
                            {{ tile.kicker }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-8">
                <div class="mx-auto max-w-3xl space-y-4 text-center">
                    <p
                        class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
                    >
                        {{ featuredRows[0].eyebrow }}
                    </p>

                    <h2
                        class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ featuredRows[0].title }}
                    </h2>

                    <p
                        class="text-base leading-relaxed text-gray-600 md:text-lg"
                    >
                        {{ featuredRows[0].description }}
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div
                        v-for="category in featuredRows[0].categories"
                        :key="category.title"
                        class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5"
                    >
                        <div class="mb-4 flex items-baseline justify-between">
                            <h3 class="text-base font-semibold text-gray-900">
                                {{ category.title }}
                            </h3>
                            <Link
                                href="/blog"
                                class="text-xs font-semibold text-gray-500 hover:text-gray-900"
                                >View</Link
                            >
                        </div>

                        <div class="space-y-3">
                            <Link
                                v-for="article in category.links"
                                :key="article.title"
                                href="/blog"
                                class="block rounded-2xl bg-stone-50 p-4 ring-1 ring-black/5 transition hover:bg-stone-100"
                            >
                                <div
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{ article.title }}
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    {{ article.summary }}
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <LeadSlotRenderer slotKey="home_intro" />

            <section
                class="rounded-3xl bg-stone-50 px-6 py-14 md:px-10 md:py-16"
            >
                <div class="mx-auto max-w-5xl space-y-10">
                    <div class="max-w-2xl space-y-4">
                        <p
                            class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Services
                        </p>

                        <h2
                            class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                        >
                            Focused guidance for every major real estate move
                        </h2>

                        <p
                            class="text-base leading-relaxed text-gray-600 md:text-lg"
                        >
                            Nojo offers clear, strategic support for buyers,
                            sellers, and investors who want better direction
                            before making important decisions.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div
                            v-for="service in services"
                            :key="service.title"
                            class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5"
                        >
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ service.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-gray-600"
                            >
                                {{ service.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-8">
                <div class="mx-auto max-w-3xl space-y-4 text-center">
                    <p
                        class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
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
                        Nojo is built to help clients move with more confidence
                        by offering practical insight, thoughtful strategy, and
                        honest support at every step.
                    </p>
                </div>

                <div class="mx-auto max-w-5xl space-y-4">
                    <div
                        v-for="point in whyNojoPoints"
                        :key="point"
                        class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md"
                    >
                        <p class="text-base font-medium text-gray-900">
                            {{ point }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="space-y-8">
                <div class="mx-auto max-w-3xl space-y-4 text-center">
                    <p
                        class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
                    >
                        {{ featuredRows[1].eyebrow }}
                    </p>

                    <h2
                        class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ featuredRows[1].title }}
                    </h2>

                    <p
                        class="text-base leading-relaxed text-gray-600 md:text-lg"
                    >
                        {{ featuredRows[1].description }}
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div
                        v-for="category in featuredRows[1].categories"
                        :key="category.title"
                        class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5"
                    >
                        <div class="mb-4 flex items-baseline justify-between">
                            <h3 class="text-base font-semibold text-gray-900">
                                {{ category.title }}
                            </h3>
                            <Link
                                href="/blog"
                                class="text-xs font-semibold text-gray-500 hover:text-gray-900"
                                >View</Link
                            >
                        </div>

                        <div class="space-y-3">
                            <Link
                                v-for="article in category.links"
                                :key="article.title"
                                href="/blog"
                                class="block rounded-2xl bg-stone-50 p-4 ring-1 ring-black/5 transition hover:bg-stone-100"
                            >
                                <div
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{ article.title }}
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    {{ article.summary }}
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <LeadSlotRenderer slotKey="home_mid" />

            <section class="space-y-8">
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
                        Helpful articles and practical guidance for buyers,
                        sellers, and anyone trying to better understand the New
                        Jersey real estate market.
                    </p>
                </div>

                <div class="mx-auto grid max-w-6xl gap-6 md:grid-cols-3">
                    <div
                        v-for="item in insights"
                        :key="item.title"
                        class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 transition hover:shadow-md"
                    >
                        <h3
                            class="mb-3 text-xl font-semibold tracking-tight text-gray-900"
                        >
                            {{ item.title }}
                        </h3>
                        <p class="leading-relaxed text-gray-600">
                            {{ item.body }}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </FrontLayout>
</template>
