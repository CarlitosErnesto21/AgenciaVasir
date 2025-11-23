import axios from 'axios'

export default {
    install(app) {
        // Variables para controlar el estado del plugin
        let isRefreshing = false
        let refreshPromise = null

        // Función para obtener token actual
        const getCurrentToken = () => {
            const metaTag = document.querySelector('meta[name="csrf-token"]')
            return metaTag?.getAttribute('content') || ''
        }

        // Función para actualizar el token en todos los lugares necesarios
        const updateTokenEverywhere = (newToken) => {
            // Meta tag
            const metaTag = document.querySelector('meta[name="csrf-token"]')
            if (metaTag) {
                metaTag.setAttribute('content', newToken)
            }

            // Axios headers
            if (window.axios) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken
            }
            if (axios.defaults.headers.common) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken
            }

            // Formularios con token oculto (para Inertia)
            const tokenInputs = document.querySelectorAll('input[name="_token"]')
            tokenInputs.forEach(input => {
                input.value = newToken
            })
        }

        // Función para refrescar token con prevención de múltiples llamadas
        const refreshCSRFToken = async () => {
            if (isRefreshing) {
                return refreshPromise
            }

            isRefreshing = true
            refreshPromise = (async () => {
                try {
                    // Usar fetch para evitar recursión con axios
                    const response = await fetch('/api/csrf-token', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`)
                    }

                    const data = await response.json()
                    if (data && data.csrf_token) {
                        updateTokenEverywhere(data.csrf_token)
                        return data.csrf_token
                    }

                    throw new Error('No token received')
                } catch (error) {
                    console.error('Error refreshing CSRF token:', error)
                    // En caso de error, recargar la página como último recurso
                    setTimeout(() => {
                        window.location.reload()
                    }, 1000)
                    throw error
                } finally {
                    isRefreshing = false
                    refreshPromise = null
                }
            })()

            return refreshPromise
        }

        // Configurar interceptor de axios para manejo automático de CSRF
        if (window.axios || axios) {
            const axiosInstance = window.axios || axios

            axiosInstance.interceptors.request.use(
                (config) => {
                    const token = getCurrentToken()
                    if (token) {
                        config.headers['X-CSRF-TOKEN'] = token
                    }
                    return config
                },
                (error) => Promise.reject(error)
            )

            axiosInstance.interceptors.response.use(
                (response) => response,
                async (error) => {
                    if (error.response?.status === 419) {
                        try {
                            await refreshCSRFToken()
                            // Reintentar request original
                            const originalRequest = error.config
                            originalRequest.headers['X-CSRF-TOKEN'] = getCurrentToken()
                            return axiosInstance(originalRequest)
                        } catch (refreshError) {
                            console.error('Failed to refresh CSRF token:', refreshError)
                            return Promise.reject(refreshError)
                        }
                    }
                    return Promise.reject(error)
                }
            )
        }

        // Interceptor para Inertia.js requests
        if (window.Inertia) {
            window.Inertia.on('before', (event) => {
                const token = getCurrentToken()
                if (token && event.detail.visit.data) {
                    event.detail.visit.data._token = token
                }
            })

            window.Inertia.on('error', async (event) => {
                if (event.detail.response?.status === 419) {
                    try {
                        await refreshCSRFToken()
                        // Reintentar la petición automáticamente
                        setTimeout(() => {
                            const form = event.detail.form
                            if (form) {
                                form.submit()
                            }
                        }, 100)
                    } catch (error) {
                        console.error('Error refreshing token for Inertia:', error)
                    }
                }
            })
        }

        // Refrescar token automáticamente cada 10 minutos para prevenir expiración
        setInterval(async () => {
            try {
                await refreshCSRFToken()
            } catch (error) {
                console.error('Error in preventive token refresh:', error)
            }
        }, 10 * 60 * 1000) // 10 minutos

        // Métodos globales simplificados
        app.config.globalProperties.$csrf = {
            refresh: refreshCSRFToken,
            getToken: getCurrentToken
        }

        // Hacer disponible globalmente para debugging
        window.csrfManager = {
            refreshCSRFToken,
            getCurrentToken,
            updateTokenEverywhere
        }

        // Inicializar token al cargar la página
        const initToken = getCurrentToken()
        if (initToken) {
            updateTokenEverywhere(initToken)
        }
    }
}
