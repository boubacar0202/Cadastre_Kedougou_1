import "../css/app.css";
import "./bootstrap";
import axios from 'axios'   
import { createInertiaApp, Head, Link } from "@inertiajs/vue3"; // ✅ Ajout de Head et Link
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, Fragment, h } from "vue";
import AppSavingOverlay from "./Components/AppSavingOverlay.vue";
import { installSavingOverlay } from "./utils/savingOverlay";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import * as MazUI from "maz-ui";
import DefaultLayout from "./Layouts/DefaultLayout.vue";
import { installToaster } from 'maz-ui'; 
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import proj4 from "proj4"; 

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});
 
function gpsToUtm(latitude, longitude) {
    const wgs84 = "EPSG:4326";
    const utm28n = "+proj=utm +zone=28 +datum=WGS84 +units=m +no_defs";
    const [x, y] = proj4(wgs84, utm28n, [longitude, latitude]);
    return { x, y };
}
 
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    }
});

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

installSavingOverlay();

import "maz-ui/styles";

const toasterOptions = {
    position: "bottom-right",
    timeout: 5000,
    persistent: false,
};
 

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        );
        page.default.layout = page.default.layout || DefaultLayout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h(Fragment, [h(App, props), h(AppSavingOverlay)]),
        });
    
        // Gestion globale des erreurs
        app.config.errorHandler = (err, vm, info) => {
            console.error('Erreur Vue attrapée:', err, vm, info);
            alert('Une erreur est survenue. Vérifie la console.');
        }

        // ✅ Enregistrer Head et Link globalement
        app.component('Head', Head);
        app.component('Link', Link);

        // Configuration de MazUI avec la couleur personnalisée
        app.use(MazUI, {
            colors: {
                primary: '#FE5835',
            },
        });
    
        // Ajout des plugins
        app.use(plugin)
            .use(ZiggyVue)
            .use(installToaster, toasterOptions)
            .mount(el);
    },    
      
    progress: {
        color: "#FE5835",
    },
});