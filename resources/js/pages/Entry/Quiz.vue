<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/layouts/FrontLayout.vue';
import LeadSlotRenderer from '@/components/public/lead/LeadSlotRenderer.vue';

type PrimaryOffer = {
    title: string;
    body: string;
    cta_label: string;
    href: string | null;
    is_available: boolean;
};

defineProps<{
    primaryOffer: PrimaryOffer;
}>();
</script>

<template>
    <Head title="Quiz Entry" />

    <FrontLayout>
        <section class="relative overflow-hidden bg-[#071a3a] px-6 py-20 text-white">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(116,174,226,0.38)_0%,_transparent_35%),linear-gradient(135deg,_#204f9d_0%,_#123875_52%,_#071a3a_100%)]"></div>
            <div class="absolute -bottom-28 left-1/2 h-64 w-[780px] -translate-x-1/2 rounded-full bg-blue-300/10 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.32em] text-blue-200">
                        Quiz Entry Point
                    </p>

                    <h1 class="mt-4 max-w-3xl text-4xl font-black leading-tight tracking-tight md:text-6xl">
                        Start with a quick quiz. Leave with a clearer next move.
                    </h1>

                    <p class="mt-6 max-w-2xl text-base leading-8 text-blue-100 md:text-lg">
                        This short entry page gives visitors enough context before the quiz starts. The main goal stays focused: move them into the quiz while keeping a fallback offer available lower on the page.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            v-if="primaryOffer.is_available && primaryOffer.href"
                            :href="primaryOffer.href"
                            class="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-4 text-sm font-black text-[#123875] shadow-2xl shadow-blue-950/30 transition duration-150 hover:-translate-y-0.5 hover:bg-blue-50 active:translate-y-1 active:scale-[0.98] active:shadow-inner"
                        >
                            {{ primaryOffer.cta_label }}
                        </Link>

                        <button
                            v-else
                            type="button"
                            disabled
                            class="inline-flex cursor-not-allowed items-center justify-center rounded-2xl bg-white/40 px-7 py-4 text-sm font-black text-[#123875] opacity-70"
                        >
                            Quiz not published yet
                        </button>

                        <a
                            href="#secondary-offer"
                            class="inline-flex items-center justify-center rounded-2xl border border-white/25 px-7 py-4 text-sm font-black text-white transition duration-150 hover:bg-white/10 active:translate-y-1 active:scale-[0.98]"
                        >
                            See another option
                        </a>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl shadow-blue-950/30 backdrop-blur">
                    <div class="rounded-[1.5rem] bg-white p-6 text-slate-950">
                        <p class="text-xs font-black uppercase tracking-[0.28em] text-[#204f9d]">
                            What this quiz does
                        </p>
                        <h2 class="mt-3 text-2xl font-black">
                            {{ primaryOffer.title }}
                        </h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            {{ primaryOffer.body }}
                        </p>

                        <div class="mt-6 grid gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-black">Fast commitment</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">A low-friction step that helps visitors start without feeling locked in.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-black">Clear direction</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Answers turn into a more useful recommendation, path, or follow-up.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-black">Conversion cycle</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">If the quiz is not the right hook, the bottom offer keeps the visitor moving.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white px-6 py-10">
            <div class="mx-auto max-w-6xl">
                <LeadSlotRenderer slot-key="quiz_entry_top" />
            </div>
        </section>

        <section class="bg-[#eef6ff] px-6 py-20 text-slate-950">
            <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-3">
                <div class="rounded-3xl border border-blue-100 bg-white p-7 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-[#204f9d]">Step 1</p>
                    <h3 class="mt-3 text-xl font-black">Read the promise</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">The visitor understands what the quiz is and why it matters before the first question.</p>
                </div>

                <div class="rounded-3xl border border-blue-100 bg-white p-7 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-[#204f9d]">Step 2</p>
                    <h3 class="mt-3 text-xl font-black">Start the quiz</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">The main CTA moves them into the quiz experience without extra distractions.</p>
                </div>

                <div class="rounded-3xl border border-blue-100 bg-white p-7 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-[#204f9d]">Step 3</p>
                    <h3 class="mt-3 text-xl font-black">Keep a second path</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">A bottom lead box gives them another offer if the quiz is not the hook that lands.</p>
                </div>
            </div>
        </section>

        <section id="secondary-offer" class="bg-white px-6 py-16">
            <div class="mx-auto max-w-6xl">
                <div class="mb-6 max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[0.28em] text-[#204f9d]">
                        Secondary Offer
                    </p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                        Not ready for the quiz? Give them another path.
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        This bottom slot is the fallback conversion layer. Use it for a PDF, assessment, consultation, or another lead magnet that supports the same cycle.
                    </p>
                </div>

                <LeadSlotRenderer slot-key="quiz_entry_bottom" />
            </div>
        </section>
    </FrontLayout>
</template>
