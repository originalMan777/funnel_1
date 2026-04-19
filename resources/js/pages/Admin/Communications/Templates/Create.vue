<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type BindingDefinition = {
    event_key: string;
    label: string;
    actions: Array<{
        action_key: string;
        label: string;
    }>;
};

type BindingFormRow = {
    event_key: string;
    action_key: string;
    is_enabled: boolean;
    priority: number;
};

const props = defineProps<{
    bindingDefinitions: BindingDefinition[];
}>();

const form = useForm({
    key: '',
    name: '',
    status: 'draft',
    description: '',
    from_name_override: '',
    from_email_override: '',
    reply_to_email: '',
    bindings: [] as BindingFormRow[],
});

const blankBinding = (): BindingFormRow => ({
    event_key: '',
    action_key: '',
    is_enabled: true,
    priority: 100,
});

const addBinding = () => {
    form.bindings.push(blankBinding());
};

const removeBinding = (index: number) => {
    form.bindings.splice(index, 1);
};

const actionsForEvent = (eventKey: string) =>
    props.bindingDefinitions.find((definition) => definition.event_key === eventKey)?.actions ?? [];

const updateBindingEvent = (index: number, eventKey: string) => {
    form.bindings[index].event_key = eventKey;

    if (!actionsForEvent(eventKey).some((action) => action.action_key === form.bindings[index].action_key)) {
        form.bindings[index].action_key = '';
    }
};

const eventLabelFor = (eventKey: string) =>
    props.bindingDefinitions.find((definition) => definition.event_key === eventKey)?.label ?? '';

const actionLabelFor = (eventKey: string, actionKey: string) =>
    actionsForEvent(eventKey).find((action) => action.action_key === actionKey)?.label ?? '';

const submit = () => {
    form.post(route('admin.communications.templates.store'));
};
</script>

<template>
    <Head title="Create Communication Template" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communications</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Create Email</h1>
                    </div>

                    <Link
                        :href="route('admin.communications.templates.index')"
                        class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back to emails
                    </Link>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Internal Key</span>
                            <input v-model="form.key" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.key" class="mt-1 text-sm text-red-600">{{ form.errors.key }}</p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Email Name</span>
                            <input v-model="form.name" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Status</span>
                            <select v-model="form.status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                                <option value="draft">draft</option>
                                <option value="active">active</option>
                                <option value="archived">archived</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Reply-To Email</span>
                            <input v-model="form.reply_to_email" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.reply_to_email" class="mt-1 text-sm text-red-600">{{ form.errors.reply_to_email }}</p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">From Name Override</span>
                            <input v-model="form.from_name_override" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.from_name_override" class="mt-1 text-sm text-red-600">{{ form.errors.from_name_override }}</p>
                        </label>

                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">From Email Override</span>
                            <input v-model="form.from_email_override" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.from_email_override" class="mt-1 text-sm text-red-600">{{ form.errors.from_email_override }}</p>
                        </label>

                        <label class="text-sm text-gray-700 md:col-span-2">
                            <span class="mb-1 block font-medium">Description</span>
                            <textarea v-model="form.description" rows="4" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Email Triggers</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Choose when this email should be sent and what it should do.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="addBinding"
                        >
                            Add trigger
                        </button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div v-if="form.bindings.length === 0" class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600">
                            No triggers selected yet.
                        </div>

                        <div
                            v-for="(binding, index) in form.bindings"
                            :key="index"
                            class="rounded-xl border border-gray-200 p-4"
                        >
                            <p class="mb-3 text-sm text-gray-500">
                                {{
                                    binding.event_key
                                        ? eventLabelFor(binding.event_key)
                                        : 'Choose a trigger'
                                }}
                                →
                                {{
                                    binding.action_key
                                        ? actionLabelFor(binding.event_key, binding.action_key)
                                        : 'Choose an effect'
                                }}
                            </p>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium">When this happens</span>
                                    <select
                                        :value="binding.event_key"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                                        @change="updateBindingEvent(index, ($event.target as HTMLSelectElement).value)"
                                    >
                                        <option value="">Select trigger</option>
                                        <option
                                            v-for="definition in bindingDefinitions"
                                            :key="definition.event_key"
                                            :value="definition.event_key"
                                        >
                                            {{ definition.label }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors[`bindings.${index}.event_key`]" class="mt-1 text-sm text-red-600">
                                        {{ form.errors[`bindings.${index}.event_key`] }}
                                    </p>
                                </label>

                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium">Do this</span>
                                    <select
                                        v-model="binding.action_key"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                                        :disabled="!binding.event_key"
                                    >
                                        <option value="">Select effect</option>
                                        <option
                                            v-for="action in actionsForEvent(binding.event_key)"
                                            :key="action.action_key"
                                            :value="action.action_key"
                                        >
                                            {{ action.label }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors[`bindings.${index}.action_key`]" class="mt-1 text-sm text-red-600">
                                        {{ form.errors[`bindings.${index}.action_key`] }}
                                    </p>
                                </label>

                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium">Order</span>
                                    <input v-model="binding.priority" type="number" min="1" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                    <p v-if="form.errors[`bindings.${index}.priority`]" class="mt-1 text-sm text-red-600">
                                        {{ form.errors[`bindings.${index}.priority`] }}
                                    </p>
                                </label>

                                <div class="flex items-end justify-between gap-4">
                                    <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                                        <input v-model="binding.is_enabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-300" />
                                        <span class="font-medium">Trigger enabled</span>
                                    </label>

                                    <button
                                        type="button"
                                        class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                        @click="removeBinding(index)"
                                    >
                                        Remove trigger
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        :disabled="form.processing"
                    >
                        Save email
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
