<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth?.user as any;
</script>

<template>
    <header class="border-b border-white/10 bg-gray-900 px-4 py-2.5 text-white">
        <div class="flex items-center justify-between gap-4">
            <nav class="flex items-center gap-2 text-sm font-semibold">
                <Link
                    href="/"
                    class="rounded-lg px-3 py-2 text-gray-200 transition hover:bg-white/10 hover:text-white"
                >
                    Home
                </Link>
                <Link
                    href="/blog"
                    class="rounded-lg px-3 py-2 text-gray-200 transition hover:bg-white/10 hover:text-white"
                >
                    Blog
                </Link>
            </nav>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <img
                        v-if="user?.avatar"
                        :src="user.avatar"
                        alt="Avatar"
                        class="h-8 w-8 rounded-full object-cover ring-1 ring-white/20"
                    />
                    <div
                        v-else
                        class="h-8 w-8 rounded-full bg-white/10 ring-1 ring-white/10"
                    ></div>

                    <span class="text-sm text-gray-100">
                        {{ user?.name }}
                    </span>
                </div>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="rounded-lg px-3 py-2 text-sm text-gray-200 underline-offset-4 transition hover:bg-white/10 hover:text-white hover:underline"
                >
                    Log out
                </Link>
            </div>
        </div>
    </header>
</template>
