import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;      // 👈 ESSENTIEL
window.axios.defaults.withXSRFToken = true;        // 👈 (axios ≥ 1.6) envoie X-XSRF-TOKEN auto