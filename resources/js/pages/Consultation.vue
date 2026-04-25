<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrackedLink from '@/components/analytics/TrackedLink.vue';
import FrontLayout from '@/layouts/FrontLayout.vue';

const page = usePage();

const consultationRequestHref = computed(() => {
    const queryString = page.url.includes('?') ? page.url.split('?')[1] : '';

    if (!queryString) {
        return route('consultation.request');
    }

    return `${route('consultation.request')}?${queryString}`;
});
</script>

<template>
  <FrontLayout>
    <div class="space-y-20 py-16">
      <!-- HERO -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="max-w-4xl space-y-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
              Consultation
            </p>

            <h1 class="text-4xl font-semibold tracking-tight text-gray-900 md:text-5xl lg:text-6xl">
              Clear real estate guidance before your next big decision
            </h1>

            <p class="max-w-3xl text-lg leading-relaxed text-gray-600 md:text-xl">
              Whether you're buying, selling, relocating, or trying to better
              understand your options, Nojo offers focused consultation designed
              to help you move forward with more clarity and confidence.
            </p>

            <div class="flex flex-wrap gap-4">
              <TrackedLink
                :href="consultationRequestHref"
                cta-key="consultation.hero.request"
                surface-key="consultation.hero"
                class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800"
              >
                Request Consultation
              </TrackedLink>

              <TrackedLink
                href="/contact"
                cta-key="consultation.hero.contact"
                surface-key="consultation.hero"
                class="rounded-lg border border-gray-200 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Contact Nojo
              </TrackedLink>
            </div>
          </div>
        </div>
      </section>

      <!-- WHO THIS IS FOR -->
      <section class="w-full bg-stone-50 py-16 md:py-20">
        <div class="mx-auto max-w-6xl px-6">
          <div class="mx-auto max-w-3xl space-y-4 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
              Who This Is For
            </p>

            <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
              For clients who want better direction before they act
            </h2>

            <p class="text-base leading-relaxed text-gray-600 md:text-lg">
              This consultation is designed for people who want a clearer
              understanding of their next move before making important real
              estate decisions.
            </p>
          </div>

          <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <h3 class="text-xl font-semibold tracking-tight text-gray-900">
                First-time buyers
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                Get guidance on the process, your options, and what to focus on
                before making a purchase.
              </p>
            </div>

            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <h3 class="text-xl font-semibold tracking-tight text-gray-900">
                Homeowners preparing to sell
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                Understand how to prepare, position, and think strategically
                before entering the market.
              </p>
            </div>

            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <h3 class="text-xl font-semibold tracking-tight text-gray-900">
                Investors
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                Evaluate opportunities and local conditions with more clarity
                before committing to a move.
              </p>
            </div>

            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <h3 class="text-xl font-semibold tracking-tight text-gray-900">
                Families relocating
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                Move forward with clearer guidance, less uncertainty, and a
                better understanding of the road ahead.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- WHAT YOU GET -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="grid gap-10 md:grid-cols-[1fr_1fr] md:items-start">
            <div class="space-y-5">
              <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                <p class="font-medium text-gray-900">Share your situation through the guided intake form.</p>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                  The consultation request is collected through our intake popup so the details stay aligned
                  with the active lead-capture setup.
                </p>
              </div>

              <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                <p class="font-medium text-gray-900">Typical topics include:</p>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-gray-600">
                  <li>Buying strategy and next steps</li>
                  <li>Selling preparation and positioning</li>
                  <li>Relocation questions</li>
                  <li>General clarity before making a move</li>
                </ul>
              </div>

              <TrackedLink
                :href="consultationRequestHref"
                cta-key="consultation.mid.request"
                surface-key="consultation.content"
                class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-6 py-3.5 font-medium text-white transition hover:bg-gray-800"
              >
                Request Consultation
              </TrackedLink>

              <p class="text-sm leading-relaxed text-gray-500">
                A guided intake popup will open so you can submit your information.
              </p>
            </div>

            <div class="rounded-3xl bg-stone-50 p-8 shadow-sm ring-1 ring-black/5">
              <div class="space-y-4">
                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">A clearer view of your options</p>
                </div>

                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Better understanding of the market</p>
                </div>

                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Guidance on risks and next steps</p>
                </div>

                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Direction that fits your situation</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- HOW IT WORKS -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="mx-auto max-w-3xl space-y-4 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
              Process
            </p>

            <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
              A simple process designed to give you clarity quickly
            </h2>

            <p class="text-base leading-relaxed text-gray-600 md:text-lg">
              Nojo keeps the process straightforward so you can get focused
              guidance without unnecessary confusion.
            </p>
          </div>

          <div class="mt-12 grid gap-6 md:grid-cols-3">
            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
                Step 01
              </p>
              <h3 class="mt-5 text-2xl font-semibold tracking-tight text-gray-900">
                Reach out
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                Share a little about your situation and what you need help with.
              </p>
            </div>

            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
                Step 02
              </p>
              <h3 class="mt-5 text-2xl font-semibold tracking-tight text-gray-900">
                Review your options
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                We look at your goals, position, and the decisions in front of you.
              </p>
            </div>

            <div class="rounded-[28px] bg-white p-8 shadow-sm ring-1 ring-black/5">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
                Step 03
              </p>
              <h3 class="mt-5 text-2xl font-semibold tracking-tight text-gray-900">
                Move forward with clarity
              </h3>
              <p class="mt-3 leading-relaxed text-gray-600">
                Leave with stronger direction, clearer thinking, and practical
                next steps.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- FORM -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="grid gap-10 md:grid-cols-[0.95fr_1.05fr] md:items-start">
            <div class="space-y-5">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                Request a Consultation
              </p>

              <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
                Let’s talk through your next move
              </h2>

              <p class="leading-relaxed text-gray-600">
                Fill out the form and share a little about your situation. This
                helps Nojo understand your goals and prepare for a more useful,
                focused conversation.
              </p>

              <p class="leading-relaxed text-gray-600">
                Whether you are buying, selling, or simply trying to figure out
                your options, this is the first step toward making a more
                confident decision.
              </p>
            </div>

            <div class="rounded-[32px] bg-stone-50 p-8 shadow-sm ring-1 ring-black/5 md:p-10">
              <form class="space-y-5">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-900">Full Name</label>
                  <input
                    type="text"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                    placeholder="Your name"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-900">Email Address</label>
                  <input
                    type="email"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                    placeholder="you@example.com"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-900">Phone Number</label>
                  <input
                    type="text"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                    placeholder="(000) 000-0000"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-900">
                    What do you need help with?
                  </label>
                  <textarea
                    rows="5"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                    placeholder="Tell us a little about your situation..."
                  ></textarea>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-900">Are you buying, selling, or both?</label>
                  <select
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                  >
                    <option>Select one</option>
                    <option>Buying</option>
                    <option>Selling</option>
                    <option>Both</option>
                    <option>Not sure yet</option>
                  </select>
                </div>

                <button
                  type="submit"
                  class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-6 py-3.5 font-medium text-white transition hover:bg-gray-800"
                >
                  Submit Request
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="rounded-[32px] bg-gray-900 px-8 py-12 text-white md:px-12 md:py-16">
            <div class="max-w-3xl space-y-5">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/60">
                Next Step
              </p>

              <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                Better decisions start with clearer guidance
              </h2>

              <p class="max-w-2xl text-base leading-relaxed text-white/80 md:text-lg">
                If you're planning your next move and want a clearer view of your
                options, request a consultation and start with focused direction.
              </p>

              <div class="pt-2">
                <Link
                  :href="consultationRequestHref"
                  class="inline-flex rounded-md bg-white px-6 py-3 font-medium text-gray-900 transition hover:bg-white/90"
                >
                  Get Started
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </FrontLayout>
</template>
