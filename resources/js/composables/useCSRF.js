import { router } from '@inertiajs/vue3'

export function useCSRF() {
    let isHandling419 = false;

    // Interceptor para Axios (si se usa) - solo manejo silencioso
    const setupAxiosInterceptor = () => {
        if (window.axios) {
            window.axios.interceptors.response.use(
                (response) => response,
                async (error) => {
                    // Si es error 419 y no estamos ya manejándolo
                    if (error.response?.status === 419 && !isHandling419) {
                        isHandling419 = true;
                        // Solo recargar la página silenciosamente
                        window.location.reload();
                        return new Promise(() => {}); // Promise que nunca se resuelve para evitar que se propague el error
                    }
                    return Promise.reject(error);
                }
            );
        }
    };

    // Interceptor para Inertia - completamente deshabilitado para evitar interferencias
    const setupInertiaInterceptor = () => {
        // No hacer nada - dejar que Laravel maneje los errores CSRF naturalmente
    };

    return {
        setupAxiosInterceptor,
        setupInertiaInterceptor
    };
}
