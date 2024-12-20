import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Debugging the available pages
        console.log("Resolving page:", name);

        // Get all the available pages via import.meta.glob
        const pages = import.meta.glob("./Pages/**/*.vue");
        console.log("Available pages:", pages);

        // Resolve the component using resolvePageComponent
        const resolved = resolvePageComponent(`./Pages/${name}.vue`, pages);

        // Debugging the resolved component
        console.log("Resolved component:", resolved);
        return resolved;
    },
    setup({ el, App, props, plugin }) {
        // Create the Vue app and mount it
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
