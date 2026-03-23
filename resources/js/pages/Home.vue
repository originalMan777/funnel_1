<script setup>
import { Link } from '@inertiajs/vue3'
import FrontLayout from '@/layouts/FrontLayout.vue'
import LeadSlotRenderer from '@/components/public/lead/LeadSlotRenderer.vue'
import coverImage from '@/images/cover-1.png'
</script>

<template>
  <FrontLayout>
    <!-- HERO -->
    <section
      class="relative min-h-[85vh] w-full overflow-hidden bg-cover bg-center"
      :style="{ backgroundImage: `url(${coverImage})` }"
    >
      <div class="absolute inset-0 bg-black/45"></div>

      <div class="relative z-10 mx-auto flex min-h-[85vh] max-w-7xl items-center px-6 py-16 md:px-10">
        <div class="max-w-2xl space-y-6 text-white">
          <h1 class="text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">
            Clear Real Estate Guidance for Your Next Move
          </h1>

          <p class="max-w-xl text-lg leading-relaxed text-white/90 md:text-xl">
            Helping buyers, sellers, and investors make confident property
            decisions with professional insight and strategy.
          </p>

          <div class="flex flex-wrap gap-4">
            <Link
              href="/consultation"
              data-popup-trigger="current-page"
              class="rounded bg-white px-6 py-3 font-medium text-gray-900"
            >
              Book a Consultation
            </Link>

            <Link
              href="/services"
              class="rounded border border-white/70 px-6 py-3 font-medium text-white"
            >
              View Services
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- REST OF PAGE -->
   <div class="mx-auto max-w-7xl space-y-20 px-6 py-16 md:px-10">
      <LeadSlotRenderer slotKey="home_intro" />

      <!-- WHO THIS IS FOR -->
      <!-- WHO THIS IS FOR -->
<section class="space-y-8">
  <div class="mx-auto max-w-3xl space-y-4 text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
      Who We Help
    </p>

    <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
      Real estate guidance for every stage of the journey
    </h2>

    <p class="text-base leading-relaxed text-gray-600 md:text-lg">
      Whether you're buying your first property, preparing to sell, or planning
      your next move, Nojo helps you approach the process with more clarity
      and confidence.
    </p>
  </div>

  <div class="mx-auto grid max-w-7xl gap-5 md:grid-cols-2 xl:grid-cols-4">
    <div class="flex min-h-[360px] flex-col justify-between rounded-3xl bg-white p-8 shadow-sm ring-1 ring-black/5">
      <div class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
          01
        </p>

        <h3 class="text-2xl font-semibold tracking-tight text-gray-900">
          First-time home buyers
        </h3>

        <p class="text-sm leading-relaxed text-gray-600">
          Clear guidance for understanding the process, evaluating options, and
          moving forward with confidence.
        </p>
      </div>

      <div class="pt-8 text-sm font-medium text-gray-900">
        Buy with more confidence
      </div>
    </div>

    <div class="flex min-h-[360px] flex-col justify-between rounded-3xl bg-stone-50 p-8 shadow-sm ring-1 ring-black/5">
      <div class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
          02
        </p>

        <h3 class="text-2xl font-semibold tracking-tight text-gray-900">
          Homeowners preparing to sell
        </h3>

        <p class="text-sm leading-relaxed text-gray-600">
          Strategic support for pricing, positioning, and preparing your property
          before it hits the market.
        </p>
      </div>

      <div class="pt-8 text-sm font-medium text-gray-900">
        Sell with stronger positioning
      </div>
    </div>

    <div class="flex min-h-[360px] flex-col justify-between rounded-3xl bg-white p-8 shadow-sm ring-1 ring-black/5">
      <div class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
          03
        </p>

        <h3 class="text-2xl font-semibold tracking-tight text-gray-900">
          Investors evaluating property
        </h3>

        <p class="text-sm leading-relaxed text-gray-600">
          Insight for assessing opportunities, understanding local conditions,
          and making stronger investment decisions.
        </p>
      </div>

      <div class="pt-8 text-sm font-medium text-gray-900">
        Evaluate opportunities wisely
      </div>
    </div>

    <div class="flex min-h-[360px] flex-col justify-between rounded-3xl bg-stone-50 p-8 shadow-sm ring-1 ring-black/5">
      <div class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
          04
        </p>

        <h3 class="text-2xl font-semibold tracking-tight text-gray-900">
          Families relocating
        </h3>

        <p class="text-sm leading-relaxed text-gray-600">
          Guidance for navigating a move with more direction, less uncertainty,
          and a clearer sense of what comes next.
        </p>
      </div>

      <div class="pt-8 text-sm font-medium text-gray-900">
        Relocate with clarity
      </div>
    </div>
  </div>
</section>

      <LeadSlotRenderer slotKey="home_mid" />

      <!-- SERVICES PREVIEW -->
      <!-- SERVICES PREVIEW -->
<section class="rounded-3xl bg-stone-50 px-6 py-14 md:px-10 md:py-16">
  <div class="mx-auto max-w-5xl space-y-10">
    <div class="max-w-2xl space-y-4">
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
        Services
      </p>

      <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
        Focused guidance for every major real estate move
      </h2>

      <p class="text-base leading-relaxed text-gray-600 md:text-lg">
        Nojo offers clear, strategic support for buyers, sellers, and investors
        who want better direction before making important decisions.
      </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
        <h3 class="text-lg font-semibold text-gray-900">Consultation</h3>
        <p class="mt-2 text-sm leading-relaxed text-gray-600">
          One-on-one guidance tailored to your real estate situation, goals, and
          next steps.
        </p>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
        <h3 class="text-lg font-semibold text-gray-900">Buyer Strategy</h3>
        <p class="mt-2 text-sm leading-relaxed text-gray-600">
          Clear direction for evaluating options, understanding the process, and
          moving toward the right purchase with more confidence.
        </p>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
        <h3 class="text-lg font-semibold text-gray-900">Seller Strategy</h3>
        <p class="mt-2 text-sm leading-relaxed text-gray-600">
          Strategic support for preparing, pricing, and positioning your
          property before entering the market.
        </p>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
        <h3 class="text-lg font-semibold text-gray-900">Market Evaluation</h3>
        <p class="mt-2 text-sm leading-relaxed text-gray-600">
          Insight into local conditions, timing, and market realities so you can
          make your next move with more clarity.
        </p>
      </div>
    </div>
  </div>
</section>
      <!-- WHY NOJO -->
     <section class="space-y-8">
  <div class="mx-auto max-w-3xl space-y-4 text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
      Why Nojo
    </p>

    <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
      Clear guidance, grounded strategy, and real direction
    </h2>

    <p class="text-base leading-relaxed text-gray-600 md:text-lg">
      Nojo is built to help clients move with more confidence by offering
      practical insight, thoughtful strategy, and honest support at every step.
    </p>
  </div>

  <div class="mx-auto max-w-5xl space-y-4">
    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <p class="text-base font-medium text-gray-900">
        Clear, honest guidance
      </p>
    </div>

    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <p class="text-base font-medium text-gray-900">
        Focused on your specific situation
      </p>
    </div>

    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <p class="text-base font-medium text-gray-900">
        Local market understanding
      </p>
    </div>

    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <p class="text-base font-medium text-gray-900">
        Strategy before action
      </p>
    </div>

    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <p class="text-base font-medium text-gray-900">
        No pressure — just direction you can trust
      </p>
    </div>
  </div>
</section>

      <!-- BLOG PREVIEW -->
     <section class="space-y-8">
  <div class="mx-auto max-w-3xl space-y-4 text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
      Insights
    </p>

    <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
      Real Estate Insights & Guidance
    </h2>

    <p class="text-base leading-relaxed text-gray-600 md:text-lg">
      Helpful articles and practical guidance for buyers, sellers, and anyone
      trying to better understand the New Jersey real estate market.
    </p>
  </div>

  <div class="mx-auto grid max-w-6xl gap-6 md:grid-cols-3">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <h3 class="mb-3 text-xl font-semibold tracking-tight text-gray-900">
        Buying in New Jersey
      </h3>
      <p class="leading-relaxed text-gray-600">
        Helpful guidance for buyers preparing to make a move in the New Jersey
        market with more clarity and confidence.
      </p>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <h3 class="mb-3 text-xl font-semibold tracking-tight text-gray-900">
        Selling Tips
      </h3>
      <p class="leading-relaxed text-gray-600">
        Practical advice for homeowners getting ready to position, prepare, and
        sell their property effectively.
      </p>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
      <h3 class="mb-3 text-xl font-semibold tracking-tight text-gray-900">
        Market Trends
      </h3>
      <p class="leading-relaxed text-gray-600">
        Stay informed about changing market conditions and what they may mean
        for your next real estate decision.
      </p>
    </div>
  </div>
</section>

      <LeadSlotRenderer slotKey="home_bottom" />


    </div>
  </FrontLayout>
</template>
