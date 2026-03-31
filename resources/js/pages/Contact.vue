<script setup lang="ts">
import FrontLayout from '@/layouts/FrontLayout.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  message: '',
})

function submit() {
  form.post(route('contact.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      alert('Message sent successfully.')
    },
  })
}
</script>

<template>
  <FrontLayout>
    <div class="space-y-20 py-16">
      <!-- HERO -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="max-w-4xl space-y-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
              Contact
            </p>

            <h1 class="text-4xl font-semibold tracking-tight text-gray-900 md:text-5xl lg:text-6xl">
              Let’s talk about your next real estate move
            </h1>

            <p class="max-w-3xl text-lg leading-relaxed text-gray-600 md:text-xl">
              Reach out with your questions, share a little about your situation,
              or take the first step toward clearer guidance and more confident
              decision-making.
            </p>
          </div>
        </div>
      </section>

      <!-- CONTACT INTRO -->
      <section class="w-full bg-stone-50 py-16 md:py-20">
        <div class="mx-auto max-w-6xl px-6">
          <div class="grid gap-10 md:grid-cols-[0.95fr_1.05fr] md:items-start">
            <div class="space-y-5">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                Get In Touch
              </p>

              <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
                A simple way to start the conversation
              </h2>

              <p class="leading-relaxed text-gray-600">
                Whether you’re buying, selling, relocating, or just trying to
                better understand your options, reaching out is the first step
                toward getting clearer direction.
              </p>

              <p class="leading-relaxed text-gray-600">
                Use the contact form to share a little about your situation.
                This page is ideal for general inquiries, early questions, or
                clients who want to connect before booking a full consultation.
              </p>
            </div>

            <div class="rounded-[32px] bg-white p-8 shadow-sm ring-1 ring-black/5 md:p-10">
              <div class="space-y-5">
                <div class="rounded-2xl bg-stone-50 px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Start with the contact form.</p>
                  <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Use this page to send a direct message with your question or situation.
                  </p>
                </div>

                <div class="rounded-2xl bg-stone-50 px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Good for:</p>
                  <ul class="mt-3 space-y-2 text-sm leading-relaxed text-gray-600">
                    <li>General real estate questions</li>
                    <li>Early-stage inquiries</li>
                    <li>Questions before booking a consultation</li>
                    <li>Requests for more information</li>
                  </ul>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Full Name</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="Your name"
                    />
                    <p v-if="form.errors.name" class="text-sm text-red-600">
                      {{ form.errors.name }}
                    </p>
                  </div>

                  <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Email Address</label>
                    <input
                      v-model="form.email"
                      type="email"
                      class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="you@example.com"
                    />
                    <p v-if="form.errors.email" class="text-sm text-red-600">
                      {{ form.errors.email }}
                    </p>
                  </div>

                  <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Message</label>
                    <textarea
                      v-model="form.message"
                      rows="5"
                      class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="Tell us a little about what you need help with..."
                    ></textarea>
                    <p v-if="form.errors.message" class="text-sm text-red-600">
                      {{ form.errors.message }}
                    </p>
                  </div>

                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-6 py-3.5 font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    {{ form.processing ? 'Sending...' : 'Send Message' }}
                  </button>

                  <div v-if="form.recentlySuccessful" class="text-sm text-green-600">
                    Message sent successfully.
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CONTACT SUPPORT BLOCK -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="mx-auto max-w-3xl space-y-4 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
              What You Can Reach Out About
            </p>

            <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
              Questions, next steps, and early guidance
            </h2>

            <p class="text-base leading-relaxed text-gray-600 md:text-lg">
              The contact page is here to make the first step easy. Reach out if
              you need general guidance, want to ask a question, or need help
              deciding what kind of support makes the most sense.
            </p>
          </div>

          <div class="mx-auto mt-12 max-w-5xl space-y-4">
            <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5">
              <p class="text-base font-medium text-gray-900">
                General questions about buying or selling
              </p>
            </div>

            <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5">
              <p class="text-base font-medium text-gray-900">
                Guidance on whether a consultation is the right next step
              </p>
            </div>

            <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5">
              <p class="text-base font-medium text-gray-900">
                Early questions about your current real estate situation
              </p>
            </div>

            <div class="rounded-2xl bg-white px-6 py-5 shadow-sm ring-1 ring-black/5">
              <p class="text-base font-medium text-gray-900">
                Requests for more information before moving forward
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- CONTACT REASSURANCE -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="grid gap-10 md:grid-cols-[1fr_1fr] md:items-start">
            <div class="space-y-5">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">
                Clear Communication
              </p>

              <h2 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
                Thoughtful guidance starts with a simple conversation
              </h2>

              <p class="leading-relaxed text-gray-600">
                Nojo is built around clarity, steady communication, and helping
                clients feel more informed before they make important decisions.
              </p>

              <p class="leading-relaxed text-gray-600">
                Reaching out doesn’t need to feel complicated. This page is here
                to make it easier to connect, ask questions, and begin with a
                more grounded sense of direction.
              </p>
            </div>

            <div class="rounded-3xl bg-stone-50 p-8 shadow-sm ring-1 ring-black/5">
              <div class="space-y-4">
                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Clear and honest communication</p>
                </div>

                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">A simple and welcoming first step</p>
                </div>

                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Guidance centered on your situation</p>
                </div>

                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-black/5">
                  <p class="font-medium text-gray-900">Direction without unnecessary pressure</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="w-full">
        <div class="mx-auto max-w-6xl px-6">
          <div class="rounded-[32px] bg-gray-900 px-8 py-12 text-white md:px-12 md:py-16">
            <div class="max-w-3xl space-y-5">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/60">
                Consultation
              </p>

              <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                Ready for more focused guidance?
              </h2>

              <p class="max-w-2xl text-base leading-relaxed text-white/80 md:text-lg">
                If you already know you need more tailored direction, move to the
                next step and book a consultation designed around your situation.
              </p>

              <div class="pt-2">
                <a
                  href="/consultation"
                  class="inline-flex rounded-md bg-white px-6 py-3 font-medium text-gray-900 transition hover:bg-white/90"
                >
                  Book a Consultation
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </FrontLayout>
</template>
