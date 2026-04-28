<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import LatestEmailsCard from '@/components/admin/dashboard/LatestEmailsCard.vue';
import LiveLeadFlow from '@/components/admin/dashboard/LiveLeadFlow.vue';
import NeedsAttentionPanel from '@/components/admin/dashboard/NeedsAttentionPanel.vue';
import RealtimeNotificationCard from '@/components/admin/dashboard/RealtimeNotificationCard.vue';

type NotificationTone = 'lead' | 'message' | 'email' | 'assessment';

const livePulse = ref(false);

onMounted(() => {
    window.setTimeout(() => {
        livePulse.value = true;
    }, 450);
});

const notificationCards = computed(() => [
    {
        label: 'New Leads',
        value: 12,
        meta: 'today',
        badge: 'New',
        href: route('admin.acquisition.contacts.index'),
        tone: 'lead' as NotificationTone,
        detail: 'Fresh contacts entered from lead boxes, popups, and form captures.',
        isNew: livePulse.value,
    },
    {
        label: 'Messages',
        value: 3,
        meta: 'unread',
        badge: 'Unread',
        href: route('admin.acquisition.contacts.index'),
        tone: 'message' as NotificationTone,
        detail: 'Conversation activity that needs operator review before it cools down.',
        isNew: true,
    },
    {
        label: 'Emails',
        value: 2,
        meta: 'new',
        badge: 'Inbox',
        href: route('admin.communications.index'),
        tone: 'email' as NotificationTone,
        detail: 'Recent communication activity connected to lead follow-up and delivery.',
        isNew: livePulse.value,
    },
    {
        label: 'Submissions',
        value: 5,
        meta: 'review',
        badge: 'Review',
        href: route('admin.qo.index'),
        tone: 'assessment' as NotificationTone,
        detail: 'New assessment and quiz completions ready for qualification review.',
        isNew: false,
    },
]);

const flowItems = [
    {
        key: 'visitors',
        label: 'Visitors',
        count: 148,
        delta: '+18',
        detail: 'People entering from ads, organic search, shared links, and direct visits.',
    },
    {
        key: 'engaged',
        label: 'Engaged',
        count: 64,
        delta: '+9',
        detail: 'Visitors clicking CTAs, opening lead boxes, or starting high-intent flows.',
    },
    {
        key: 'leads',
        label: 'Leads',
        count: 23,
        delta: '+6',
        detail: 'Captured contacts now available for management and follow-up.',
    },
    {
        key: 'converted',
        label: 'Converted',
        count: 4,
        delta: '+1',
        detail: 'Leads that crossed a meaningful conversion point in the system.',
    },
];

const needsAttention = [
    {
        id: 1,
        name: 'Marcus Lee',
        reason: 'Started assessment but stopped before the final result screen.',
        source: 'Buyer readiness assessment',
        level: 'High' as const,
        href: route('admin.acquisition.contacts.index'),
        age: '8 min ago',
    },
    {
        id: 2,
        name: 'Alicia Grant',
        reason: 'Submitted a form and has not received an operator response yet.',
        source: 'Home valuation lead box',
        level: 'Medium' as const,
        href: route('admin.acquisition.contacts.index'),
        age: '22 min ago',
    },
    {
        id: 3,
        name: 'Darren Paul',
        reason: 'Opened follow-up email multiple times without clicking the CTA.',
        source: 'Email follow-up sequence',
        level: 'Watch' as const,
        href: route('admin.communications.index'),
        age: '41 min ago',
    },
];

const latestEmails = [
    {
        id: 1,
        from: 'Marcus Lee',
        subject: 'Question about the assessment result',
        time: '2m',
        unread: true,
    },
    {
        id: 2,
        from: 'Website Lead',
        subject: 'New seller consultation request',
        time: '9m',
        unread: true,
    },
    {
        id: 3,
        from: 'System',
        subject: 'Follow-up email delivered successfully',
        time: '18m',
        unread: false,
    },
    {
        id: 4,
        from: 'Alicia Grant',
        subject: 'Re: Home value estimate',
        time: '34m',
        unread: false,
    },
    {
        id: 5,
        from: 'System',
        subject: 'Assessment completion notification',
        time: '1h',
        unread: false,
    },
];

const domainCards = [
    {
        title: 'Posts',
        description: 'Content engine',
        href: route('admin.posts.index'),
        status: 'Active',
    },
    {
        title: 'Lead Boxes',
        description: 'Capture offers',
        href: route('admin.lead-boxes.index'),
        status: 'Active',
    },
    {
        title: 'QO Engine',
        description: 'Quizzes & assessments',
        href: route('admin.qo.index'),
        status: 'Active',
    },
    {
        title: 'Communications',
        description: 'Email center',
        href: route('admin.communications.index'),
        status: 'Live',
    },
    {
        title: 'Analytics',
        description: 'Signal tracking',
        href: route('admin.analytics.index'),
        status: 'Live',
    },
    {
        title: 'Media',
        description: 'Asset library',
        href: route('admin.media.index'),
        status: 'Ready',
    },
];
</script>

<template>
    <Head title="Admin Command Center" />

    <AdminLayout>
        <div class="min-h-full bg-slate-100 p-4">
            <div class="mx-auto flex max-w-[1600px] flex-col gap-5">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.28em] text-cyan-300">Realtime Hub</p>
                            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">
                                Lead command center
                            </h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">
                                This page is the operator view for acquisition, management, and conversion. It shows what is happening, what is warming up, and what needs attention now.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link
                                :href="route('admin.acquisition.contacts.index')"
                                class="rounded-full bg-white px-4 py-2 text-sm font-black text-slate-950 transition hover:bg-cyan-200"
                            >
                                Open Leads
                            </Link>
                            <Link
                                :href="route('admin.analytics.index')"
                                class="rounded-full border border-white/15 px-4 py-2 text-sm font-black text-white transition hover:bg-white/10"
                            >
                                View Analytics
                            </Link>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-blue-900/30 bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-5 text-white shadow-sm shadow-blue-950/10">
                    <div class="mb-4 flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.28em] text-cyan-300">Priority Queue</p>
                            <h2 class="mt-1 text-2xl font-black tracking-tight">Realtime Command Center</h2>
                        </div>
                        <p class="max-w-2xl text-sm leading-6 text-blue-100/80">
                            Latest lead-generation signals surface here first. New events move forward so operators can see what changed without hunting through the system.
                        </p>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-4">
                        <RealtimeNotificationCard
                            v-for="card in notificationCards"
                            :key="card.label"
                            :card="card"
                        />
                    </div>
                </section>

                <LiveLeadFlow :items="flowItems" />

                <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
                    <NeedsAttentionPanel :items="needsAttention" />
                    <LatestEmailsCard :emails="latestEmails" />
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">System Domains</p>
                            <h2 class="mt-1 text-xl font-black tracking-tight text-slate-950">Fast access</h2>
                        </div>
                        <p class="text-sm text-slate-500">Smaller cards, quick movement, no clutter.</p>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        <Link
                            v-for="domain in domainCards"
                            :key="domain.title"
                            :href="domain.href"
                            class="group rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-black text-slate-950">{{ domain.title }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ domain.description }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-emerald-700">
                                    {{ domain.status }}
                                </span>
                            </div>
                            <div class="mt-4 text-xs font-black uppercase tracking-wide text-slate-400 transition group-hover:text-slate-950">
                                Open →
                            </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
