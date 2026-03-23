import { Ref, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

type MediaFilters = {
  folder: string
  search: string
  per_page: number
}

export function useMediaBrowser(options: {
  routeName: string
  initialFilters: MediaFilters
}) {
  const folder = ref(options.initialFilters.folder ?? 'blog')
  const search = ref(options.initialFilters.search ?? '')
  const perPage = ref(String(options.initialFilters.per_page ?? 24))
  const uploadFolder = ref(folder.value)

  let searchTimer: number | undefined

  const applyFilters = (immediate = false) => {
    if (searchTimer) window.clearTimeout(searchTimer)

    const run = () => {
      router.get(
        route(options.routeName),
        {
          folder: folder.value || undefined,
          search: search.value || undefined,
          per_page: perPage.value || undefined,
        },
        {
          preserveState: true,
          preserveScroll: true,
          replace: true,
        },
      )
    }

    if (immediate) run()
    else searchTimer = window.setTimeout(run, 250)
  }

  watch(folder, (value) => {
    uploadFolder.value = value
    applyFilters(true)
  })

  watch(perPage, () => applyFilters(true))

  const syncUploadFolder = (source: Ref<string>) => {
    uploadFolder.value = source.value
  }

  return {
    folder,
    search,
    perPage,
    uploadFolder,
    applyFilters,
    syncUploadFolder,
  }
}
