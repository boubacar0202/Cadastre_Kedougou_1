import { reactive } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

export const savingState = reactive({
    active: false,
    label: "Enregistrement en cours…",
});

const MUTATING = new Set(["post", "put", "patch", "delete"]);
const MIN_VISIBLE_MS = 560;

let count = 0;
let shownAt = 0;
let hideTimer = null;
let installed = false;

function headerValue(headers, name) {
    if (!headers) return undefined;
    if (typeof headers.get === "function") {
        const value = headers.get(name);
        if (value != null && value !== "") return value;
    }
    return headers[name] ?? headers[name.toLowerCase()];
}

function isInertiaRequest(config) {
    const value = headerValue(config?.headers, "X-Inertia");
    return value === true || value === "true";
}

function isLookup(url) {
    return /fetch|verify|search|markread|previsualiser|verificationenquete/i.test(
        String(url || "")
    );
}

function isVisibilityToggle(config) {
    const method = String(config?.method || "").toLowerCase();
    if (method !== "patch") return false;

    let data = config.data;
    if (typeof data === "string") {
        try {
            data = JSON.parse(data);
        } catch {
            return false;
        }
    }
    if (!data || typeof data !== "object" || data instanceof FormData) return false;

    const keys = Object.keys(data);
    return keys.length === 1 && keys[0] === "visible";
}

export function labelForSaving(method, url = "") {
    const verb = String(method || "").toLowerCase();
    const target = String(url || "");

    if (/\/(login|logout|password|register)(\/|$|\?)/i.test(target)) {
        return "Veuillez patienter…";
    }
    if (verb === "delete" || /\/(destroy|delete|supprim)/i.test(target)) {
        return "Suppression en cours…";
    }
    if (
        verb === "put" ||
        verb === "patch" ||
        /\/(update|edit)/i.test(target) ||
        (verb === "post" && /\/\d+(\?|$)/.test(target))
    ) {
        return "Modification en cours…";
    }
    return "Enregistrement en cours…";
}

function begin(label) {
    if (hideTimer) {
        clearTimeout(hideTimer);
        hideTimer = null;
    }
    if (count === 0) shownAt = Date.now();
    count += 1;
    savingState.label = label;
    savingState.active = true;
}

function end() {
    count = Math.max(0, count - 1);
    if (count > 0) return;

    const wait = Math.max(120, MIN_VISIBLE_MS - (Date.now() - shownAt));
    hideTimer = setTimeout(() => {
        if (count === 0) savingState.active = false;
    }, wait);
}

function shouldTrackAxios(config) {
    const method = String(config?.method || "get").toLowerCase();
    if (!MUTATING.has(method)) return false;
    if (config.skipSavingOverlay) return false;
    if (isInertiaRequest(config)) return false;
    if (isLookup(config.url)) return false;
    if (isVisibilityToggle(config)) return false;
    return true;
}

export function installSavingOverlay() {
    if (installed || typeof window === "undefined") return;
    if (window.__cadastreSavingOverlay) return;
    installed = true;
    window.__cadastreSavingOverlay = true;

    router.on("start", (event) => {
        const visit = event.detail?.visit;
        if (!visit || visit.prefetch) return;

        const method = String(visit.method || "get").toLowerCase();
        if (!MUTATING.has(method)) return;

        visit.__savingTracked = true;
        begin(labelForSaving(method, visit.url?.href || visit.url));
    });

    router.on("finish", (event) => {
        const visit = event.detail?.visit;
        if (!visit?.__savingTracked) return;
        visit.__savingTracked = false;
        end();
    });

    axios.interceptors.request.use((config) => {
        if (shouldTrackAxios(config)) {
            config.__savingTracked = true;
            begin(labelForSaving(config.method, config.url));
        }
        return config;
    });

    const settle = (config) => {
        if (!config?.__savingTracked) return;
        config.__savingTracked = false;
        end();
    };

    axios.interceptors.response.use(
        (response) => {
            settle(response.config);
            return response;
        },
        (error) => {
            settle(error.config);
            return Promise.reject(error);
        }
    );
}
