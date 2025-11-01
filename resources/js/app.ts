import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import * as Sentry from '@sentry/vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Tavira';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        Sentry.init({
            app,
            dsn: 'https://d4ea9896894c9ff980f0b46c73171809@o4509974733848576.ingest.us.sentry.io/4509974735683584',
            sendDefaultPii: true,
            integrations: [],
        });
        // Pass Ziggy config from initial props to ensure correct base URL in K8s/staging
        app.use(plugin).use(ZiggyVue, props.initialPage.props.ziggy).use(i18n).mount(el);
        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
