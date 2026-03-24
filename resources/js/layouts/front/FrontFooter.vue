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

<template>
    <footer class="border-t border-black/5 bg-white/95">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div
                class="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] lg:items-start"
            >
                <div class="space-y-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-gray-500 uppercase"
                        >
                            Nojo Consult
                        </p>
                        <h2
                            class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl"
                        >
                            Clear real estate guidance, built for confident
                            decisions.
                        </h2>
                    </div>

                    <p
                        class="max-w-2xl text-sm leading-7 text-gray-600 sm:text-base"
                    >
                        Nojo helps buyers, sellers, and clients move with more
                        clarity through practical insight, focused strategy, and
                        grounded next steps.
                    </p>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <Link
                            href="/consultation"
                            class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                        >
                            Book a Consultation
                        </Link>

                        <Link
                            href="/blog"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-50"
                        >
                            Read the Blog
                        </Link>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-gray-200 bg-gray-50 p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-[0.22em] text-gray-500 uppercase"
                            >
                                Site Links
                            </p>
                            <p class="mt-2 text-sm text-gray-600">
                                Quick access to the main pages on the site.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <Link
                            v-for="item in quickLinks"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-xl bg-white px-4 py-3 font-medium text-gray-700 ring-1 ring-black/5 transition hover:bg-gray-900 hover:text-white"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </div>

            <div
                class="mt-8 flex flex-col gap-3 border-t border-black/5 pt-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>© {{ currentYear }} Nojo Consult. All rights reserved.</p>
                <p>
                    Built to support buyers, sellers, and informed next moves.
                </p>
            </div>
        </div>
    </footer>
</template>
