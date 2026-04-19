<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

type SelectOption = {
    value: string | number;
    label: string;
    description?: string;
};

type StepFormRow = {
    id?: number;
    step_order: number;
    delay_amount: number;
    delay_unit: string;
    send_mode: string;
    template_id: number | '' | null;
    subject: string;
    html_body: string;
    text_body: string;
    is_enabled: boolean;
};

type CampaignFormData = {
    name: string;
    status: string;
    audience_type: string;
    entry_trigger: string;
    description: string;
    steps: StepFormRow[];
};

const props = defineProps<{
    mode: 'create' | 'edit';
    submitUrl: string;
    campaign?: Partial<CampaignFormData> & { id?: number };
    formOptions: {
        statusOptions: SelectOption[];
        audienceOptions: SelectOption[];
        entryTriggerOptions: SelectOption[];
        templateOptions: Array<SelectOption & { description?: string }>;
        delayUnitOptions: SelectOption[];
        sendModeOptions: SelectOption[];
    };
}>();

const nextStep = (stepOrder: number): StepFormRow => ({
    step_order: stepOrder,
    delay_amount: 0,
    delay_unit: 'days',
    send_mode: 'template',
    template_id: '',
    subject: '',
    html_body: '',
    text_body: '',
    is_enabled: true,
});

const form = useForm<CampaignFormData>({
    name: props.campaign?.name ?? '',
    status: props.campaign?.status ?? 'draft',
    audience_type: props.campaign?.audience_type ?? 'leads',
    entry_trigger: props.campaign?.entry_trigger ?? '',
    description: props.campaign?.description ?? '',
    steps: props.campaign?.steps?.map((step) => ({
        id: step.id,
        step_order: Number(step.step_order ?? 1),
        delay_amount: Number(step.delay_amount ?? 0),
        delay_unit: step.delay_unit ?? 'days',
        send_mode: step.send_mode ?? 'template',
        template_id: step.template_id ?? '',
        subject: step.subject ?? '',
        html_body: step.html_body ?? '',
        text_body: step.text_body ?? '',
        is_enabled: Boolean(step.is_enabled ?? true),
    })) ?? [],
});

if (form.steps.length === 0) {
    form.steps.push(nextStep(1));
}

const addStep = () => {
    form.steps.push(nextStep(form.steps.length + 1));
};

const removeStep = (index: number) => {
    form.steps.splice(index, 1);
    form.steps.forEach((step, stepIndex) => {
        if (!step.step_order || step.step_order < 1) {
            step.step_order = stepIndex + 1;
        }
    });
};

const updateSendMode = (index: number, value: string) => {
    form.steps[index].send_mode = value;

    if (value === 'template') {
        form.steps[index].subject = '';
        form.steps[index].html_body = '';
        form.steps[index].text_body = '';
    }

    if (value === 'custom') {
        form.steps[index].template_id = '';
    }
};

const templateLabelFor = (templateId: number | '' | null) =>
    props.formOptions.templateOptions.find((option) => option.value === templateId)?.label ?? 'Choose template';

const triggerLabelFor = (entryTrigger: string) =>
    props.formOptions.entryTriggerOptions.find((option) => option.value === entryTrigger)?.label ?? 'Choose trigger';

const submit = () => {
    if (props.mode === 'create') {
        form.post(props.submitUrl);

        return;
    }

    form.put(props.submitUrl);
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <section class="rounded-2xl border border-gray-200 bg-white p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Campaign Details</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Define when this campaign starts, who enters it, and how it should be described in admin.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm text-gray-700 md:col-span-2">
                    <span class="mb-1 block font-medium">Campaign Name</span>
                    <input v-model="form.name" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </label>

                <label class="text-sm text-gray-700">
                    <span class="mb-1 block font-medium">Status</span>
                    <select v-model="form.status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                        <option v-for="option in formOptions.statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                </label>

                <label class="text-sm text-gray-700">
                    <span class="mb-1 block font-medium">Audience</span>
                    <select v-model="form.audience_type" class="w-full rounded-md border border-gray-300 px-3 py-2">
                        <option v-for="option in formOptions.audienceOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.audience_type" class="mt-1 text-sm text-red-600">{{ form.errors.audience_type }}</p>
                </label>

                <label class="text-sm text-gray-700 md:col-span-2">
                    <span class="mb-1 block font-medium">Entry Trigger</span>
                    <select v-model="form.entry_trigger" class="w-full rounded-md border border-gray-300 px-3 py-2">
                        <option value="">Choose trigger</option>
                        <option v-for="option in formOptions.entryTriggerOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ form.entry_trigger ? triggerLabelFor(form.entry_trigger) : 'Choose the event that enrolls someone into this campaign.' }}
                    </p>
                    <p v-if="form.errors.entry_trigger" class="mt-1 text-sm text-red-600">{{ form.errors.entry_trigger }}</p>
                </label>

                <label class="text-sm text-gray-700 md:col-span-2">
                    <span class="mb-1 block font-medium">Description</span>
                    <textarea v-model="form.description" rows="4" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Campaign Steps</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Build the step sequence using an existing template or a custom message for each send.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="addStep"
                >
                    Add step
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <div
                    v-for="(step, index) in form.steps"
                    :key="step.id ?? index"
                    class="rounded-xl border border-gray-200 p-4"
                >
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Step {{ index + 1 }}</p>
                            <p class="text-xs text-gray-500">
                                {{
                                    step.send_mode === 'template'
                                        ? templateLabelFor(step.template_id)
                                        : 'Custom message'
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                            :disabled="form.steps.length === 1"
                            @click="removeStep(index)"
                        >
                            Remove step
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Step Order</span>
                            <input v-model="step.step_order" type="number" min="1" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors[`steps.${index}.step_order`]" class="mt-1 text-sm text-red-600">
                                {{ form.errors[`steps.${index}.step_order`] }}
                            </p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Delay</span>
                            <input v-model="step.delay_amount" type="number" min="0" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors[`steps.${index}.delay_amount`]" class="mt-1 text-sm text-red-600">
                                {{ form.errors[`steps.${index}.delay_amount`] }}
                            </p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Delay Unit</span>
                            <select v-model="step.delay_unit" class="w-full rounded-md border border-gray-300 px-3 py-2">
                                <option v-for="option in formOptions.delayUnitOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="form.errors[`steps.${index}.delay_unit`]" class="mt-1 text-sm text-red-600">
                                {{ form.errors[`steps.${index}.delay_unit`] }}
                            </p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Send Mode</span>
                            <select
                                :value="step.send_mode"
                                class="w-full rounded-md border border-gray-300 px-3 py-2"
                                @change="updateSendMode(index, ($event.target as HTMLSelectElement).value)"
                            >
                                <option v-for="option in formOptions.sendModeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="form.errors[`steps.${index}.send_mode`]" class="mt-1 text-sm text-red-600">
                                {{ form.errors[`steps.${index}.send_mode`] }}
                            </p>
                        </label>

                        <label v-if="step.send_mode === 'template'" class="text-sm text-gray-700 md:col-span-2">
                            <span class="mb-1 block font-medium">Template</span>
                            <select v-model="step.template_id" class="w-full rounded-md border border-gray-300 px-3 py-2">
                                <option value="">Choose template</option>
                                <option v-for="option in formOptions.templateOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                {{
                                    formOptions.templateOptions.find((option) => option.value === step.template_id)?.description
                                        ?? 'Choose an active published template.'
                                }}
                            </p>
                            <p v-if="form.errors[`steps.${index}.template_id`]" class="mt-1 text-sm text-red-600">
                                {{ form.errors[`steps.${index}.template_id`] }}
                            </p>
                        </label>

                        <template v-else>
                            <label class="text-sm text-gray-700 md:col-span-2">
                                <span class="mb-1 block font-medium">Subject</span>
                                <input v-model="step.subject" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors[`steps.${index}.subject`]" class="mt-1 text-sm text-red-600">
                                    {{ form.errors[`steps.${index}.subject`] }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700 md:col-span-2">
                                <span class="mb-1 block font-medium">HTML Body</span>
                                <textarea v-model="step.html_body" rows="6" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors[`steps.${index}.html_body`]" class="mt-1 text-sm text-red-600">
                                    {{ form.errors[`steps.${index}.html_body`] }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700 md:col-span-2">
                                <span class="mb-1 block font-medium">Text Body</span>
                                <textarea v-model="step.text_body" rows="4" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors[`steps.${index}.text_body`]" class="mt-1 text-sm text-red-600">
                                    {{ form.errors[`steps.${index}.text_body`] }}
                                </p>
                            </label>
                        </template>

                        <label class="inline-flex items-center gap-3 text-sm text-gray-700 md:col-span-2">
                            <input v-model="step.is_enabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-300" />
                            <span class="font-medium">Step enabled</span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ mode === 'create' ? 'Create campaign' : 'Save campaign' }}
            </button>
        </div>
    </form>
</template>
