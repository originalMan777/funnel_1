<script setup lang="ts">
import FrontLayout from '@/layouts/FrontLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const searchParams = new URLSearchParams(page.url.includes('?') ? page.url.split('?')[1] : '');

const form = useForm({
    name: '',
    email: '',
    phone: '',
    details: '',
    page_key: searchParams.get('page_key') || '',
    lead_slot_key: searchParams.get('lead_slot_key') || '',
    source_popup_key: searchParams.get('source_popup_key') || '',
    acquisition_path_key: searchParams.get('acquisition_path_key') || '',
    acquisition_slug: searchParams.get('acquisition_slug') || '',
    service_slug: searchParams.get('service_slug') || '',
});

function submit() {
    form.post(route('consultation.request.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'email', 'phone', 'details');
            alert('Consultation request sent successfully.');
        },
    });
}
</script>

<template>
  <FrontLayout>
    <div class="space-y-20 py-16">
      <!-- HERO -->
      <section class="w-full">
        <div class="mx-auto max-w-5xl px-6">
          <div class="space-y-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
              Consultation Request
            </p>

            <h1 class="text-4xl font-semibold tracking-tight text-gray-900 md:text-5xl">
              Tell us about your situation
            </h1>

            <p class="max-w-3xl text-lg leading-relaxed text-gray-600">
              Share a few details about what you need help with. This helps us
              understand your situation and prepare for a more useful, focused
              consultation.
            </p>
          </div>
        </div>
      </section>

      <!-- FORM -->
      <section class="w-full">
        <div class="mx-auto max-w-5xl px-6">
          <div class="rounded-[32px] bg-stone-50 p-8 shadow-sm ring-1 ring-black/5 md:p-10">
            <form @submit.prevent="submit" class="space-y-6">
              <input v-model="form.page_key" type="hidden" />
              <input v-model="form.lead_slot_key" type="hidden" />
              <input v-model="form.source_popup_key" type="hidden" />
              <input v-model="form.acquisition_path_key" type="hidden" />
              <input v-model="form.acquisition_slug" type="hidden" />
              <input v-model="form.service_slug" type="hidden" />

              <!-- NAME -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-900">Full Name</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3"
                  placeholder="Your name"
                />
                <p v-if="form.errors.name" class="text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- EMAIL -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-900">Email Address</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3"
                  placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="text-sm text-red-600">
                  {{ form.errors.email }}
                </p>
              </div>

              <!-- PHONE -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-900">Phone Number</label>
                <input
                  v-model="form.phone"
                  type="text"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3"
                  placeholder="(000) 000-0000"
                />
                <p v-if="form.errors.phone" class="text-sm text-red-600">
                  {{ form.errors.phone }}
                </p>
              </div>

              <!-- DETAILS -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-900">
                  What do you need help with?
                </label>
                <textarea
                  v-model="form.details"
                  rows="5"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3"
                  placeholder="Tell us about your situation..."
                ></textarea>
                <p v-if="form.errors.details" class="text-sm text-red-600">
                  {{ form.errors.details }}
                </p>
              </div>

              <!-- SUBMIT -->
              <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-6 py-3.5 font-medium text-white transition hover:bg-gray-800 disabled:opacity-60"
              >
                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
              </button>

              <!-- SUCCESS -->
              <div v-if="form.recentlySuccessful" class="text-green-600 text-sm">
                Your consultation request has been submitted.
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>
  </FrontLayout>
</template>
