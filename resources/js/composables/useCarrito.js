import { ref } from 'vue'
import axios from 'axios'
import { useCarritoStore } from '../stores/carrito'

export function useCarrito() {
    const loading = ref(false)
    const error = ref(null)
    const carritoStore = useCarritoStore()

    // Crear instancia de axios con CSRF para rutas web
    const webAxios = axios.create()

    // Configurar CSRF token para esta instancia
    const token = document.head.querySelector('meta[name="csrf-token"]')

    console.log('🔒 Debug CSRF:', {
        metaTag: token,
        tokenContent: token?.content,
        documentHead: document.head.innerHTML.substring(0, 500)
    })

    if (token) {
        webAxios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
        console.log('✅ Token CSRF configurado:', token.content)
    } else {
        console.error('❌ Token CSRF NO encontrado')
    }
    webAxios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

    /**
     * Crear una venta desde el carrito actual
     */
    const createVentaFromCarrito = async (customerEmail) => {
        loading.value = true
        error.value = null

        try {
            // Transformar items del carrito al formato esperado por la API
            const productos = carritoStore.items.map(item => ({
                id: item.id,
                cantidad: item.cantidad,
                precio: item.precio,
                nombre: item.nombre
            }))

            const payload = {
                productos,
                customer_email: customerEmail
            }

            console.log('🛒 Datos del carrito a enviar:', {
                payload,
                carritoItems: carritoStore.items,
                customerEmail
            })

            console.log('📡 Headers a enviar:', webAxios.defaults.headers.common)

            const response = await webAxios.post('/carrito/create-venta', payload)

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
            console.error('📋 Detalles del error:', {
                status: err.response?.status,
                statusText: err.response?.statusText,
                data: err.response?.data,
                headers: err.response?.headers
            })
            
            // Mostrar detalles específicos del error de validación
            if (err.response?.data) {
                console.error('🔍 Respuesta del servidor:', err.response.data)
                if (err.response.data.errors) {
                    console.error('❌ Errores de validación:', err.response.data.errors)
                }
                if (err.response.data.message) {
                    console.error('💬 Mensaje del servidor:', err.response.data.message)
                }
            }

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
