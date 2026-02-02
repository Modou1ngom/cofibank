import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Ajouter le token d'authentification aux requêtes
const token = localStorage.getItem('token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Intercepteur pour ajouter le token après connexion
window.axios.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Intercepteur pour gérer les erreurs 401
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('userProfile');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

