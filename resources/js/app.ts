import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import { initializeTheme } from '@/composables/useAppearance'
import { flushAnalyticsEvents, initializeAnalyticsContext, trackPageView } from '@/lib/analytics'
import { ZiggyVue } from 'ziggy-js'
import '../css/app.css'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        initializeAnalyticsContext(props.initialPage.props.analytics ?? null)
        trackPageView(props.initialPage)

        window.addEventListener('pagehide', () => {
            flushAnalyticsEvents(true)
        })

        router.on('navigate', (event) => {
            initializeAnalyticsContext(event.detail.page.props.analytics ?? null)
            trackPageView(event.detail.page)
        })

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})

initializeTheme()
