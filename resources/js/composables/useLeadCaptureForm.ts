import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trackEvent } from '@/lib/analytics';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

export function useLeadCaptureForm(model: LeadBlockRenderModel) {
    const isFormOpen = ref(false);
    const isSuccess = ref(false);

    const form = useForm({
        lead_box_id: model.leadBoxId,
        lead_slot_key: model.context.slotKey,
        page_key: model.context.pageKey,
        source_url: typeof window !== 'undefined' ? window.location.href : '',
        first_name: '',
        email: '',
        phone: '',
        message: '',
    });

    const canSubmit = computed(() => !form.processing);

    const open = () => {
        trackEvent({
            eventKey: 'lead_form.opened',
            leadBoxId: model.leadBoxId,
            surfaceKey: `lead_box.${model.context.slotKey}`,
            properties: {
                source: 'lead_box',
            },
        });

        isFormOpen.value = true;
    };

    const close = () => {
        isFormOpen.value = false;
    };

    const submit = () => {
        form.post(route('leads.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isSuccess.value = true;
                isFormOpen.value = false;
                window.dispatchEvent(new CustomEvent('lead:captured'));
            },
            onError: () => {
                trackEvent({
                    eventKey: 'lead_form.failed',
                    leadBoxId: model.leadBoxId,
                    surfaceKey: `lead_box.${model.context.slotKey}`,
                    properties: {
                        source: 'lead_box',
                        status: 'validation_failed',
                    },
                });
            },
        });
    };

    return {
        form,
        isFormOpen,
        isSuccess,
        canSubmit,
        open,
        close,
        submit,
    };
}
