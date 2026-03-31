<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import MediaBrowserPanel from '@/components/admin/media/MediaBrowserPanel.vue'

type FolderOption = {
  value: string
  label: string
}

type MediaItem = {
  name: string
  filename: string
  folder: string
  path: string
  url: string
  size_kb: number
  modified_at: string
  extension: string
}

type PaginationLink = {
  url: string | null
  label: string
  active: boolean
}

type Pagination<T> = {
  data: T[]
  links: PaginationLink[]
  current_page?: number
  last_page?: number
  from?: number | null
  to?: number | null
  total?: number
  per_page?: number
}

defineProps<{
  folders: FolderOption[]
  filters: {
    folder: string
    search: string
    per_page: number
  }
  media: Pagination<MediaItem>
}>()
</script>

<template>
  <Head title="Media Library" />

  <AdminLayout>
    <MediaBrowserPanel
      title="Media Library"
      description="Upload, browse, and manage site images."
      route-name="admin.media.index"
      :folders="folders"
      :filters="filters"
      :media="media"
    />
  </AdminLayout>
</template>
