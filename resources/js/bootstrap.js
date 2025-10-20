import axios from 'axios';
window.axios = axios;

// Use API base URL relative to current origin and ensure cookies are sent
window.axios.defaults.baseURL = window.location.origin + '/api';
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar token de autenticación automáticamente desde cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Configurar interceptor para incluir token automáticamente
axios.interceptors.request.use(function (config) {
    const token = getCookie('api_token');

        // 🔧 FIX: Si la URL ya es absoluta, no usar baseURL
        if (config.url && (config.url.startsWith('http://') || config.url.startsWith('https://'))) {
            config.baseURL = ''; // Remover baseURL para URLs absolutas
        }

        // 🔧 FIX: Para URLs que empiecen con /api/, remover baseURL para evitar duplicación
        if (config.url && config.url.startsWith('/api/')) {
            config.baseURL = window.location.origin; // Solo origin, sin /api
        }    // debug helper: prints detailed info about token and request
    try {
        console.group('🔐 Axios Request Debug');
        console.log('URL:', config.url);
        console.log('Method:', config.method?.toUpperCase());
        console.log('BaseURL:', config.baseURL);
        console.log('Full URL:', (config.baseURL || '') + (config.url || ''));

        if (token) {
            console.log('✅ Token found:', token.slice(0, 12) + '...' + token.slice(-8));
            console.log('🔑 Authorization header will be set');
            config.headers.Authorization = `Bearer ${token}`;
        } else {
            console.warn('❌ No api_token cookie found');
            console.log('Available cookies:', document.cookie);
        }

        console.log('Request headers:', {
            'Authorization': config.headers.Authorization ? '✅ Set' : '❌ Missing',
            'X-Requested-With': config.headers['X-Requested-With'],
            'Content-Type': config.headers['Content-Type']
        });
        console.groupEnd();
    } catch (e) {
        console.error('Debug logging error:', e);
    }

    return config;
}, function (error) {
    console.error('🚨 Axios request interceptor error:', error);
    return Promise.reject(error);
});
