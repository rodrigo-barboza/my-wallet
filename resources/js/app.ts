import { createApp, DefineComponent, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue', { eager: true })

        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, {
                ...(props.initialPage.props as { ziggy?: Record<string, unknown> }).ziggy,
                location: new URL((props.initialPage.props as { ziggy?: { location: string } }).ziggy?.location ?? window.location.href),
            })
            .mount(el)
    },
})
