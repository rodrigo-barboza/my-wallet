/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue'
    const component: DefineComponent<object, object, unknown>
    export default component
}

import { route as ziggyRoute } from 'ziggy-js';

declare global {
    /* eslint-disable no-var */
    var route: typeof ziggyRoute;
}
