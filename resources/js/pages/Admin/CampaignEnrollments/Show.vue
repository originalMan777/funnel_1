<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type EnrollmentDetail = {
    id: number;
    campaign: {
        id: number | null;
        name: string;
        status: string;
        status_label: string;
        entry_trigger: string;
        entry_trigger_label: string;
    };
    recipient: {
        name: string;
        email: string | null;
    };
    source: {
        label: string;
        identity: string;
        route: string | null;
    };
    current_step: {
        order: number | null;
        label: string;
        send_mode: string | null;
        delay: string | null;
    };
    status: string;
    status_label: string;
    next_run_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    exit_reason: string;
    can_pause: boolean;
    can_resume: boolean;
    can_exit: boolean;
};

const props = defineProps<{
    enrollment: EnrollmentDetail;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const pauseEnrollment = () => {
    router.post(route('admin.campaign-enrollments.pause', props.enrollment.id), {}, {
        preserveScroll: true,
    });
};

const resumeEnrollment = () => {
    router.post(route('admin.campaign-enrollments.resume', props.enrollment.id), {}, {
        preserveScroll: true,
    });
};

const exitEnrollment = () => {
    router.post(route('admin.campaign-enrollments.exit', props.enrollment.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Campaign Enrollment #${enrollment.id}`" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Campaign Enrollment</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Enrollment #{{ enrollment.id }}</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Monitor one enrolled recipient and perform narrow operational actions without changing campaign runtime behavior.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('admin.campaign-enrollments.index')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Back to enrollments
                        </Link>
                        <button
                            v-if="enrollment.can_pause"
                            type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="pauseEnrollment"
                        >
                            Pause
                        </button>
                        <button
                            v-if="enrollment.can_resume"
                            type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="resumeEnrollment"
                        >
                            Resume
                        </button>
                        <button
                            v-if="enrollment.can_exit"
                            type="button"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            @click="exitEnrollment"
                        >
                            Exit
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Campaign</h2>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Campaign name</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.campaign.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Campaign status</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.campaign.status_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Entry trigger</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.campaign.entry_trigger_label }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Recipient And Source</h2>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Recipient name</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.recipient.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Recipient email</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.recipient.email || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Source type</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.source.label }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Source identity</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                <Link
                                    v-if="enrollment.source.route"
                                    :href="enrollment.source.route"
                                    class="underline-offset-2 hover:underline"
                                >
                                    {{ enrollment.source.identity }}
                                </Link>
                                <span v-else>{{ enrollment.source.identity }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Enrollment</h2>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Current step</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.current_step.label }}</dd>
                            <dd v-if="enrollment.current_step.send_mode" class="mt-1 text-sm text-gray-500">
                                {{ enrollment.current_step.send_mode }}<span v-if="enrollment.current_step.delay"> • {{ enrollment.current_step.delay }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.status_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Next run at</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(enrollment.next_run_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Started at</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(enrollment.started_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Completed at</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(enrollment.completed_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Exit reason</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ enrollment.exit_reason }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
