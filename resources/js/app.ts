import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import { initializeTheme } from './composables/useTheme';

initializeTheme();

createInertiaApp({
    title: (title) => `${title} · LERN-Bank Mein Geldautomat`,
    resolve: (name) => resolvePageComponent(
        `./pages/${name}.vue`,
        import.meta.glob<DefineComponent>('./pages/**/*.vue'),
    ),
    async setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) }).use(plugin);

        if (props.initialPage.component.startsWith('Operator/')) {
            const [{ default: PrimeVue }, { default: Aura }] = await Promise.all([
                import('primevue/config'),
                import('@primeuix/themes/aura'),
            ]);
            app.use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: { darkModeSelector: false },
                },
            });
        }

        app.mount(el);
    },
    progress: { color: '#b9f277' },
});
