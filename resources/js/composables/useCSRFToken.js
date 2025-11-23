import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

export function useCSRFToken() {
    const csrfToken = ref('')
    const isRefreshing = ref(false)
    let refreshInterval = null
    let lastActivity = Date.now()

    // Función para obtener un nuevo token CSRF
    const refreshCSRFToken = async () => {
        if (isRefreshing.value) return csrfToken.value

        try {
            isRefreshing.value = true
            const response = await axios.get('/api/csrf-token')
            
            if (response.data && response.data.csrf_token) {
                csrfToken.value = response.data.csrf_token
                
                // Actualizar el meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]')
                if (metaTag) {
                    metaTag.setAttribute('content', csrfToken.value)
                }
                
                // Actualizar axios defaults
                if (axios.defaults.headers.common) {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.value
                }
                
                console.log('CSRF token refreshed successfully')
                return csrfToken.value
            }
        } catch (error) {
            console.error('Error refreshing CSRF token:', error)
            throw error
        } finally {
            isRefreshing.value = false
        }
        
        return csrfToken.value
    }

    // Función para obtener el token actual
    const getCurrentToken = () => {
        if (!csrfToken.value) {
            const metaTag = document.querySelector('meta[name="csrf-token"]')
            csrfToken.value = metaTag?.getAttribute('content') || ''
        }
        return csrfToken.value
    }

    // Función para marcar actividad del usuario
    const updateActivity = () => {
        lastActivity = Date.now()
    }

    // Función para iniciar el refresh automático
    const startAutoRefresh = (intervalMinutes = 90) => {
        if (refreshInterval) {
            clearInterval(refreshInterval)
        }
        
        // Refrescar el token cada X minutos, pero solo si hay actividad reciente
        refreshInterval = setInterval(async () => {
            const timeSinceLastActivity = Date.now() - lastActivity
            const maxInactivity = 30 * 60 * 1000 // 30 minutos
            
            // Solo refrescar si el usuario ha estado activo recientemente
            if (timeSinceLastActivity < maxInactivity) {
                try {
                    await refreshCSRFToken()
                } catch (error) {
                    console.error('Auto-refresh CSRF token failed:', error)
                }
            }
        }, intervalMinutes * 60 * 1000)
    }

    // Función para detener el refresh automático
    const stopAutoRefresh = () => {
        if (refreshInterval) {
            clearInterval(refreshInterval)
            refreshInterval = null
        }
    }

    // Función para configurar interceptor de axios
    const setupAxiosInterceptor = () => {
        // Interceptor de request para asegurar que siempre tenemos el token más reciente
        axios.interceptors.request.use(
            (config) => {
                const token = getCurrentToken()
                if (token) {
                    config.headers['X-CSRF-TOKEN'] = token
                }
                updateActivity() // Marcar actividad en cada request
                return config
            },
            (error) => Promise.reject(error)
        )

        // Interceptor de response para manejar errores 419 (CSRF token mismatch)
        axios.interceptors.response.use(
            (response) => response,
            async (error) => {
                if (error.response && error.response.status === 419) {
                    console.log('CSRF token expired, attempting to refresh...')
                    try {
                        await refreshCSRFToken()
                        // Reintentar la request original con el nuevo token
                        const originalRequest = error.config
                        originalRequest.headers['X-CSRF-TOKEN'] = getCurrentToken()
                        return axios(originalRequest)
                    } catch (refreshError) {
                        console.error('Failed to refresh CSRF token:', refreshError)
                        // Redirigir al login o mostrar mensaje de error
                        window.location.reload()
                        return Promise.reject(refreshError)
                    }
                }
                return Promise.reject(error)
            }
        )
    }

    // Eventos de actividad del usuario
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']

    const setupActivityTracking = () => {
        activityEvents.forEach(event => {
            document.addEventListener(event, updateActivity, { passive: true })
        })
    }

    const cleanupActivityTracking = () => {
        activityEvents.forEach(event => {
            document.removeEventListener(event, updateActivity)
        })
    }

    // Inicialización
    onMounted(() => {
        getCurrentToken() // Obtener token inicial
        setupAxiosInterceptor()
        setupActivityTracking()
        startAutoRefresh() // Iniciar refresh automático cada 90 minutos
    })

    // Limpieza
    onUnmounted(() => {
        stopAutoRefresh()
        cleanupActivityTracking()
    })

    return {
        csrfToken,
        isRefreshing,
        refreshCSRFToken,
        getCurrentToken,
        startAutoRefresh,
        stopAutoRefresh,
        updateActivity
    }
}