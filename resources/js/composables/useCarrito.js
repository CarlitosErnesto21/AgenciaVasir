import { ref } from 'vue'
import axios from 'axios'
import { useCarritoStore } from '../stores/carrito'

export function useCarrito() {
    const loading = ref(false)
    const error = ref(null)
    const carritoStore = useCarritoStore()

    // Crear instancia de axios con CSRF para rutas web
    const webAxios = axios.create()

    // Función para obtener el token CSRF actual
    const getCsrfToken = () => {
        const metaToken = document.head.querySelector('meta[name="csrf-token"]')?.content
        return metaToken || null
    }

    // Configurar interceptor para incluir siempre el token CSRF más reciente
    webAxios.interceptors.request.use((config) => {
        const token = getCsrfToken()

        if (token) {
            config.headers['X-CSRF-TOKEN'] = token
        }

        config.headers['X-Requested-With'] = 'XMLHttpRequest'
        config.headers['Accept'] = 'application/json'

        return config
    })

    /**
     * Crear una venta desde el carrito actual
     */
    const createVentaFromCarrito = async (customerEmail, clienteData = null) => {
        loading.value = true
        error.value = null

        try {
            // Transformar items del carrito al formato esperado por la API
            const productos = carritoStore.items.map(item => ({
                id: item.id,
                cantidad: item.cantidad,
                precio: item.precio,
                nombre: item.nombre,
                imagen: item.primera_imagen || item.imagen || null,
                subtotal: item.precio * item.cantidad
            }))

            const payload = {
                productos,
                customer_email: customerEmail,
                cliente_data: clienteData
            }

            let response
            try {
                response = await webAxios.post('/carrito/create-venta', payload)
            } catch (firstAttemptError) {
                // Si es error 419 (CSRF), intentar renovar token
                if (firstAttemptError.response?.status === 419) {
                    console.log('🔄 Token CSRF expirado, renovando...')
                    try {
                        // Hacer una petición GET para obtener nuevo token
                        const freshResponse = await axios.get('/tienda', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html,application/xhtml+xml'
                            }
                        })

                        // Extraer el nuevo token del HTML
                        if (freshResponse.data && typeof freshResponse.data === 'string') {
                            const match = freshResponse.data.match(/name="csrf-token" content="([^"]+)"/);
                            if (match) {
                                const newToken = match[1];
                                // Actualizar el meta tag
                                const metaTag = document.head.querySelector('meta[name="csrf-token"]')
                                if (metaTag) {
                                    metaTag.setAttribute('content', newToken)
                                    console.log('✅ Token CSRF renovado:', newToken)
                                }
                            }
                        }

                        // Reintentar la petición original
                        response = await webAxios.post('/carrito/create-venta', payload)
                    } catch (retryError) {
                        throw retryError
                    }
                } else {
                    throw firstAttemptError
                }
            }

            if (response.data.success) {
                return {
                    success: true,
                    venta: response.data.venta,
                    message: response.data.message
                }
            } else {
                throw new Error(response.data.message || 'Error desconocido')
            }

        } catch (err) {
            console.error('Error creando venta desde carrito:', err)

            let errorMessage = 'Error al crear la venta'

            if (err.response?.data?.message) {
                errorMessage = err.response.data.message
            } else if (err.response?.data?.errors) {
                // Manejar errores de validación
                const errors = Object.values(err.response.data.errors).flat()
                errorMessage = errors.join(', ')
            } else if (err.message) {
                errorMessage = err.message
            }

            error.value = errorMessage

            return {
                success: false,
                error: errorMessage
            }
        } finally {
            loading.value = false
        }
    }

    /**
     * Procesar pago de una venta
     */
    const procesarPagoVenta = async (pagoData) => {
        loading.value = true
        error.value = null

        try {
            const response = await webAxios.post('/pagos/venta', pagoData)

            if (response.data.success) {
                return {
                    success: true,
                    pago: response.data.pago,
                    wompi_data: response.data.wompi_data,
                    message: response.data.message
                }
            } else {
                throw new Error(response.data.message || 'Error en el pago')
            }

        } catch (err) {
            console.error('Error procesando pago:', err)

            let errorMessage = 'Error al procesar el pago'

            if (err.response?.data?.message) {
                errorMessage = err.response.data.message
            } else if (err.response?.data?.errors) {
                const errors = Object.values(err.response.data.errors).flat()
                errorMessage = errors.join(', ')
            } else if (err.message) {
                errorMessage = err.message
            }

            error.value = errorMessage

            return {
                success: false,
                error: errorMessage
            }
        } finally {
            loading.value = false
        }
    }

    /**
     * Consultar estado de un pago
     */
    const consultarEstadoPago = async (pagoId) => {
        try {
            const response = await webAxios.get(`/pagos/${pagoId}/estado`)

            if (response.data.success) {
                return {
                    success: true,
                    pago: response.data.pago
                }
            } else {
                throw new Error(response.data.message || 'Error consultando estado')
            }

        } catch (err) {
            console.error('Error consultando estado del pago:', err)
            return {
                success: false,
                error: err.response?.data?.message || err.message || 'Error consultando estado'
            }
        }
    }

    return {
        loading,
        error,
        createVentaFromCarrito,
        procesarPagoVenta,
        consultarEstadoPago
    }
}
