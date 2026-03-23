<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = page.props.auth?.user as any
</script>

<template>
  <header class="bg-gray-800 text-white px-4 py-2">
    <div class="flex items-center justify-between">
      <!-- Left: Primary nav -->
      <nav class="flex items-center gap-4">
        <Link href="/" class="text-sm font-semibold hover:underline">
          Home
        </Link>
        <Link href="/admin/posts" class="text-sm font-semibold text-gray-200 hover:text-white hover:underline">
          Posts
        </Link>
      </nav>

      <!-- Right: User profile + logout -->
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <!-- Avatar (fallback circle if no avatar url) -->
          <img
            v-if="user?.avatar"
            :src="user.avatar"
            alt="Avatar"
            class="h-7 w-7 rounded-full object-cover"
          />
          <div v-else class="h-7 w-7 rounded-full bg-gray-600"></div>

          <span class="text-sm text-gray-100">
            {{ user?.name }}
          </span>
        </div>

        <!-- Logout (Laravel expects POST) -->
        <Link
          href="/logout"
          method="post"
          as="button"
          class="text-sm text-gray-200 hover:text-white underline"
        >
          Log out
        </Link>
      </div>
    </div>
  </header>
</template>
