import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';

const appName = import.meta.env.VITE_APP_NAME ?? 'WeatherLens';
const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');

createInertiaApp({
  title: (title) => (title ? `${title} — ${appName}` : appName),
  resolve: (name) => resolvePageComponent<DefineComponent>(`./Pages/${name}.vue`, pages),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});
