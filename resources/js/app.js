import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js/dist/vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// หน้าเพจหลักของแอป + หน้าเพจที่อยู่ภายในแต่ละโมดูล (nwidart/laravel-modules)
const appPages = import.meta.glob('./Pages/**/*.vue');
const modulePages = import.meta.glob('../../Modules/*/resources/js/Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    // รองรับชื่อเพจ 2 รูปแบบ:
    //   'Dashboard'            -> resources/js/Pages/Dashboard.vue
    //   'Attendance::CheckIn'  -> Modules/Attendance/resources/js/Pages/CheckIn.vue
    resolve: (name) => {
        if (name.includes('::')) {
            const [module, page] = name.split('::');
            return resolvePageComponent(
                `../../Modules/${module}/resources/js/Pages/${page}.vue`,
                modulePages,
            );
        }

        return resolvePageComponent(`./Pages/${name}.vue`, appPages);
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4f46e5',
        showSpinner: false,
    },
});
