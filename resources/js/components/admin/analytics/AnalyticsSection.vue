<script setup lang="ts">
defineProps<{
  title?: string
  subtitle?: string
}>()
</script>

<template>
  <section class="grid grid-cols-12 gap-6">

    <!-- LEFT: RECENT -->
    <div class="col-span-4">
        <RecentlyViewedCard :current-href="currentOverviewHref" />
    </div>

    <!-- RIGHT: KPI -->
    <section class="col-span-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">

        <!-- existing KPI content stays exactly as is -->

    </section>

</section>
</template>
