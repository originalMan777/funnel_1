<script setup lang="ts">
import FrontLayout from '@/layouts/FrontLayout.vue'
</script>

<template>
  <FrontLayout>

    <!-- HERO -->
    <section class="relative overflow-hidden bg-[#0b1f44] px-6 pt-16 pb-20 text-white">

      <div class="hero-glow absolute inset-0 bg-[radial-gradient(circle_at_top_right,_#74aee2_0%,_transparent_30%),linear-gradient(135deg,_#0b1f44_0%,_#123875_55%,_#071a3a_100%)]"></div>

      <div class="relative mx-auto max-w-6xl text-center">

        <p class="text-xs font-bold uppercase tracking-[0.28em] text-blue-300">
          Lead Generation Platform
        </p>

        <h1 class="mt-4 text-4xl md:text-6xl font-black tracking-tight leading-tight">
          The Lead Engine Built to Acquire, Manage, and Convert.
        </h1>

        <p class="mt-6 text-blue-200 max-w-2xl mx-auto">
          A structured system designed to capture attention, organize leads, and drive real conversion.
        </p>

        <div class="mt-6 text-sm font-semibold uppercase tracking-[0.28em] text-blue-200">
          Acquisition • Management • Conversion
        </div>

        <div class="mt-8 flex justify-center gap-4">
          <button class="px-6 py-3 bg-white text-[#123875] font-bold rounded-lg">
            Request Demo
          </button>

          <button class="px-6 py-3 border border-white/40 text-white rounded-lg">
            See How It Works
          </button>
        </div>

      </div>

    </section>

    <!-- CAPABILITIES -->
    <section class="w-full px-6 py-20 bg-[#123875] text-white text-center">
      <h2 class="text-3xl font-black mb-8">Core System Capabilities</h2>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-6xl mx-auto">
        <div class="bg-white/10 p-5 rounded-xl">Interactive Lead Capture</div>
        <div class="bg-white/10 p-5 rounded-xl">Guided Assessments</div>
        <div class="bg-white/10 p-5 rounded-xl">Content Generators</div>
        <div class="bg-white/10 p-5 rounded-xl">Conversion Calculators</div>
        <div class="bg-white/10 p-5 rounded-xl">Funnel Systems</div>
        <div class="bg-white/10 p-5 rounded-xl">Automation</div>
        <div class="bg-white/10 p-5 rounded-xl">Analytics</div>
        <div class="bg-white/10 p-5 rounded-xl">Modular Architecture</div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="w-full px-6 py-24 bg-white text-slate-900 text-center">
      <h2 class="text-3xl font-black mb-12">How the System Works</h2>

      <div class="grid md:grid-cols-3 gap-10 max-w-6xl mx-auto">
        <div>
          <h3 class="font-bold text-xl">Acquire</h3>
          <p class="text-slate-600 mt-2">Capture attention and generate leads.</p>
        </div>

        <div>
          <h3 class="font-bold text-xl">Manage</h3>
          <p class="text-slate-600 mt-2">Organize and understand your leads.</p>
        </div>

        <div>
          <h3 class="font-bold text-xl">Convert</h3>
          <p class="text-slate-600 mt-2">Turn engagement into real action.</p>
        </div>
      </div>
    </section>

    <!-- DEMO -->
    <section class="w-full px-6 py-24 bg-[#0b1f44] text-white text-center">
      <h2 class="text-3xl font-black mb-6">See the System in Action</h2>

      <img src="/images/home/demo-placeholder.png" class="mx-auto rounded-xl shadow-xl max-w-4xl" />
    </section>

    <!-- CTA -->
    <section class="w-full px-6 py-24 bg-[#204f9d] text-white text-center">
      <h2 class="text-4xl font-black mb-6">
        Turn your website into a conversion system.
      </h2>

      <div class="flex justify-center gap-4">
        <button class="bg-white text-[#123875] px-6 py-3 rounded-lg font-bold">
          Request Demo
        </button>

        <button class="border border-white px-6 py-3 rounded-lg">
          Talk Through It
        </button>
      </div>
    </section>

  </FrontLayout>
</template>

<style scoped>
.hero-glow {
  animation: heroGlowDrift 14s ease-in-out infinite alternate;
}

@keyframes heroGlowDrift {
  0% { transform: scale(1); }
  100% { transform: scale(1.05); }
}
</style>
