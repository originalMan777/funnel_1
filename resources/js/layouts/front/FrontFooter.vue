<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const quickLinks = [
    { label: 'Home', href: '/' },
    { label: 'About', href: '/about' },
    { label: 'Services', href: '/services' },
    { label: 'Consultation', href: '/consultation' },
    { label: 'Resources', href: '/resources' },
    { label: 'Blog', href: '/blog' },
    { label: 'Contact', href: '/contact' },
];

const currentYear = new Date().getFullYear();
</script>

<template><footer class="border-t border-black/5 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div
            class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-start"
        >
            <div class="space-y-5">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-green-900/70 uppercase"
                    >
                        Nojo Consult
                    </p>

                    <h2
                        class="mt-3 max-w-2xl text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl"
                    >
                        Clear real estate guidance for people who want to make the right move.
                    </h2>
                </div>

                <p
                    class="max-w-2xl text-sm leading-7 text-gray-600 sm:text-base"
                >
                    Nojo helps buyers, sellers, and clients move with more clarity through practical insight, focused strategy, and grounded next steps.
                </p>

                <div class="flex flex-wrap gap-3 pt-2">
                    <Link
                        href="/consultation"
                        class="inline-flex items-center justify-center rounded-xl bg-green-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800"
                    >
                        Book a Consultation
                    </Link>

                    <Link
                        href="/blog"
                        class="inline-flex items-center justify-center rounded-xl border border-green-900/15 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm transition hover:border-green-900/25 hover:bg-green-50/50 hover:text-green-900"
                    >
                        Read the Blog
                    </Link>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-green-900/70 uppercase"
                    >
                        Site Links
                    </p>

                    <p class="mt-2 max-w-sm text-sm leading-6 text-gray-600">
                        Quick access to the main pages people use most.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <Link
                        v-for="item in quickLinks"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-xl border border-black/5 bg-white px-4 py-3 font-medium text-gray-700 transition hover:border-green-900/20 hover:bg-green-50/50 hover:text-green-900"
                    >
                        {{ item.label }}
                    </Link>
                </div>
            </div>
        </div>

        <div
            class="mt-10 flex flex-col gap-3 border-t border-black/5 pt-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between"
        >
            <p>© {{ currentYear }} Nojo Consult. All rights reserved.</p>
            <p>Built to support buyers, sellers, and informed next moves.</p>
        </div>
    </div>
</footer>
</template>
