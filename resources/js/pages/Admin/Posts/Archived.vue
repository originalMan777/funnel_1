<template>
  <div class="p-4">
    <h1 class="text-xl font-bold mb-4">Archived Posts</h1>

    <div v-if="posts.data.length === 0">
      No archived posts.
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="post in posts.data"
        :key="post.id"
        class="p-3 border rounded"
      >
        <div class="font-semibold">{{ post.title }}</div>
        <div class="text-sm text-gray-500">
          Archived: {{ post.archived_at }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  posts: Object,
});
</script>
