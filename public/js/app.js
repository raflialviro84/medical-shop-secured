/* Minimal JS to run without npm/Vite. Expects axios to be loaded via CDN. */
(function(){
    if (typeof window.axios === 'undefined') {
        console.warn('Axios not detected. Include https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js');
        return;
    }

    window.axios = window.axios || axios;
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    var token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
    }
})();
