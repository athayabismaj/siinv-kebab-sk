import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.response.use(
    response => response,
    error => {
        const status = error?.response?.status;
        const currentPath = window.location.pathname;
        const loginPath = '/';

        const shouldRedirect =
            [401, 419, 500, 502, 503, 504].includes(status) &&
            currentPath !== loginPath;

        if (shouldRedirect) {
            window.location.href = loginPath;
        }

        return Promise.reject(error);
    }
);
