import { router } from '@inertiajs/vue3'

export function useCSRF() {
    // Función para refrescar el token CSRF
    const refreshCSRFToken = async () => {
        try {
            const response = await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                // Actualizar el token en el meta tag
                const newToken = document.head.querySelector('meta[name="csrf-token"]');
                if (newToken && window.axios) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken.content;
                }
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error refreshing CSRF token:', error);
            return false;
        }
    };

    // Función para manejar errores 419
    const handle419Error = async (error) => {
        if (error.response?.status === 419) {
            console.warn('CSRF token expired, refreshing...');
            
            // Intentar refrescar el token
            const refreshed = await refreshCSRFToken();
            
            if (!refreshed) {
                // Si no se pudo refrescar, redirigir al login
                router.visit('/login', {
                    method: 'get',
                    data: { 
                        message: 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.' 
                    }
                });
                return false;
            }
            
            return true; // Token refrescado exitosamente
        }
        return false; // No era un error 419
    };

    // Interceptor para Axios (si se usa)
    const setupAxiosInterceptor = () => {
        if (window.axios) {
            window.axios.interceptors.response.use(
                (response) => response,
                async (error) => {
                    const wasHandled = await handle419Error(error);
                    if (wasHandled) {
                        // Reintentar la petición original
                        return window.axios.request(error.config);
                    }
                    return Promise.reject(error);
                }
            );
        }
    };

    // Interceptor para Inertia
    const setupInertiaInterceptor = () => {
        router.on('error', async (event) => {
            if (event.detail.response?.status === 419) {
                event.preventDefault();
                
                const wasHandled = await handle419Error({
                    response: { status: 419 }
                });
                
                if (wasHandled) {
                    // Reintentar la petición original de Inertia
                    router.reload({ only: [] });
                }
            }
        });
    };

    return {
        refreshCSRFToken,
        handle419Error,
        setupAxiosInterceptor,
        setupInertiaInterceptor
    };
}