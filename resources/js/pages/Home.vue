<script setup lang="ts">
import FrontLayout from '@/layouts/FrontLayout.vue'
</script>

<template>
  <FrontLayout>
    <section class="relative overflow-hidden bg-[#204f9d] px-6 pt-16 pb-20 text-white">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_#74aee2_0%,_transparent_34%),linear-gradient(135deg,_#204f9d_0%,_#123875_55%,_#071a3a_100%)]"></div>

      <div class="relative mx-auto max-w-6xl">
        <div class="mb-7 text-center">
          <img
            src="/images/brand/awestruk-logo.png"
            alt="Awestruk Multimedia"
            class="mx-auto mb-5 h-18 w-18 object-contain opacity-95"
          />

          <h1 class="mx-auto max-w-4xl text-4xl font-black leading-tight tracking-tight md:text-5xl">
            Websites that generate leads and drive sales.
          </h1>

          <div class="mt-4 text-sm font-semibold uppercase tracking-[0.28em] text-blue-100 md:text-base">
            Acquisition <span class="mx-3 text-blue-300">•</span>
            Management <span class="mx-3 text-blue-300">•</span>
            Conversion
          </div>
        </div>

        <div class="grid grid-cols-1 items-center gap-9 md:grid-cols-2">
          <div class="space-y-5 text-center md:text-left">
            <h2 class="text-xl font-semibold text-blue-50">
              Built for businesses — from lead generation to e-commerce and more.
            </h2>

            <p class="mx-auto max-w-md text-blue-100 md:mx-0">
              Explore different types of websites designed to capture attention, manage leads, and turn visitors into action.
            </p>

            <div class="flex justify-center gap-4 pt-2 md:justify-start">
              <button class="rounded-xl bg-blue-100 px-6 py-3 font-bold text-[#123875] shadow-lg transition hover:-translate-y-0.5 hover:bg-white">
                Explore Website Types
              </button>

              <button class="rounded-xl border border-blue-200/50 px-6 py-3 font-bold text-white transition hover:bg-white/10">
                See Demo
              </button>
            </div>
          </div>

          <div class="flex flex-col items-center gap-5">
            <div class="w-[50%] overflow-hidden rounded-xl border border-blue-200/25 bg-white/10 p-2 opacity-90 shadow-2xl backdrop-blur">
              <img src="/images/home/lead-gen.png" class="w-full rounded-lg object-cover" />
              <div class="mt-2 text-center text-xs font-semibold text-blue-100">Lead Generation</div>
            </div>

            <div class="flex w-full justify-center gap-4">
              <div class="w-[35%] overflow-hidden rounded-xl border border-blue-200/20 bg-white/10 p-2 opacity-80 shadow-xl backdrop-blur">
                <img src="/images/home/construction.png" class="w-full rounded-lg object-cover" />
                <div class="mt-2 text-center text-xs text-blue-100">Construction</div>
              </div>

              <div class="w-[35%] overflow-hidden rounded-xl border border-blue-200/20 bg-white/10 p-2 opacity-80 shadow-xl backdrop-blur">
                <img src="/images/home/real-estate.png" class="w-full rounded-lg object-cover" />
                <div class="mt-2 text-center text-xs text-blue-100">Real Estate</div>
              </div>
            </div>

            <div class="w-[50%] overflow-hidden rounded-xl border border-blue-200/25 bg-white/10 p-2 opacity-90 shadow-2xl backdrop-blur">
              <img src="/images/home/ecommerce.png" class="w-full rounded-lg object-cover" />
              <div class="mt-2 text-center text-xs font-semibold text-blue-100">E-commerce</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="bg-[#eef6ff] px-6 py-24 text-slate-950">
      <div class="mx-auto mb-16 max-w-5xl text-center">
        <h2 class="text-3xl font-black tracking-tight md:text-4xl">
          Built for Different Business Needs
        </h2>
        <p class="mt-4 text-slate-600">
          Choose the type of website that fits your business.
        </p>
      </div>

      <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex w-full flex-col items-center justify-between gap-6 rounded-3xl border border-[#74aee2]/50 bg-white p-8 shadow-xl md:flex-row">
          <div class="max-w-xl">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#204f9d]">Featured</p>
            <h3 class="mt-2 text-2xl font-black">Lead Generation Systems</h3>
            <p class="mt-2 text-slate-600">
              Capture, manage, and convert leads with structured systems built for growth.
            </p>
          </div>

          <button class="rounded-xl bg-[#204f9d] px-6 py-3 font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#123875]">
            Explore
          </button>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
          <div class="rounded-3xl bg-white p-6 shadow-md">
            <h4 class="text-lg font-black">Real Estate</h4>
            <p class="mt-2 text-sm text-slate-600">
              Listings, search, and lead capture for agents.
            </p>
          </div>

          <div class="rounded-3xl bg-white p-6 shadow-md">
            <h4 class="text-lg font-black">Construction</h4>
            <p class="mt-2 text-sm text-slate-600">
              Turn visitors into booked jobs.
            </p>
          </div>

          <div class="rounded-3xl bg-white p-6 shadow-md">
            <h4 class="text-lg font-black">E-commerce</h4>
            <p class="mt-2 text-sm text-slate-600">
              Sell products with clean checkout flows.
            </p>
          </div>
        </div>
      </div>
    </section>

<!-- CAPABILITIES -->
    <section class="w-full px-6 py-20 bg-[#123875] text-white">

      <div class="max-w-5xl mx-auto text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-black tracking-tight">
          Core System Capabilities
        </h2>

        <p class="mt-4 text-blue-200 max-w-2xl mx-auto">
          Every site is built with structured tools designed to capture attention, generate data, and drive action.
        </p>
      </div>

      <div class="max-w-6xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Quizzes</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Assessments</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Content Generators</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Calculators</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Lead Capture Systems</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Automated Workflows</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Analytics Tracking</p>
        </div>

        <div class="bg-white/10 rounded-xl p-5 backdrop-blur">
          <p class="font-semibold">Conversion Funnels</p>
        </div>

      </div>

    </section>

  </FrontLayout>
</template>