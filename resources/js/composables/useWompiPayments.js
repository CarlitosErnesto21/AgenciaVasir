import { ref, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

export function useWompiPayments() {
  const toast = useToast()
  
  // Estado reactivo
  const loading = ref(false)
  const wompiConfig = ref(null)
  const acceptanceToken = ref('')
  
  // Computed
  const isReady = computed(() => {
    return wompiConfig.value && acceptanceToken.value
  })
  
  /**
   * Inicializar configuración de Wompi
   */
  const initializeWompi = async () => {
    if (isReady.value) return // Ya está inicializado
    
    loading.value = true
    
    try {
      // Primero obtener la configuración
      const configResponse = await axios.get('/api/wompi/config')
      wompiConfig.value = configResponse.data
      
      // Intentar obtener el token de aceptación, pero no fallar si hay error
      try {
        const tokenResponse = await axios.get('/api/wompi/acceptance-token')
        acceptanceToken.value = tokenResponse.data.acceptance_token
      } catch (tokenError) {
        console.warn('Token de aceptación no disponible (normal en pruebas):', tokenError.message)
        // Usar un token de prueba para desarrollo
        acceptanceToken.value = 'sandbox_acceptance_token'
      }
      
      // Cargar script de Wompi si no está cargado
      await loadWompiScript()
      
    } catch (error) {
      console.error('Error inicializando Wompi:', error)
      toast.add({
        severity: 'error',
        summary: 'Error de Configuración',
        detail: 'No se pudo cargar la configuración de pagos',
        life: 5000
      })
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Cargar script de Wompi dinámicamente
   */
  const loadWompiScript = () => {
    return new Promise((resolve, reject) => {
      if (window.WidgetCheckout) {
        resolve()
        return
      }
      
      const script = document.createElement('script')
      script.src = wompiConfig.value.widget_url
      script.onload = resolve
      script.onerror = reject
      document.head.appendChild(script)
    })
  }
  
  /**
   * Procesar pago para una venta
   */
  const processVentaPayment = async (ventaId, paymentData) => {
    try {
      loading.value = true
      
      const response = await axios.post('/api/pagos/venta', {
        venta_id: ventaId,
        ...paymentData
      })
      
      if (response.data.success) {
        toast.add({
          severity: 'success',
          summary: 'Pago Exitoso',
          detail: 'El pago de la venta ha sido procesado correctamente',
          life: 5000
        })
        
        return {
          success: true,
          data: response.data
        }
      } else {
        throw new Error(response.data.message || 'Error procesando el pago')
      }
      
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Error procesando el pago'
      
      toast.add({
        severity: 'error',
        summary: 'Error en el Pago',
        detail: message,
        life: 5000
      })
      
      return {
        success: false,
        message
      }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Procesar pago para una reserva
   */
  const processReservaPayment = async (reservaId, paymentData) => {
    try {
      loading.value = true
      
      const response = await axios.post('/api/pagos/reserva', {
        reserva_id: reservaId,
        ...paymentData
      })
      
      if (response.data.success) {
        toast.add({
          severity: 'success',
          summary: 'Pago Exitoso',
          detail: 'El pago de la reserva ha sido procesado correctamente',
          life: 5000
        })
        
        return {
          success: true,
          data: response.data
        }
      } else {
        throw new Error(response.data.message || 'Error procesando el pago')
      }
      
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Error procesando el pago'
      
      toast.add({
        severity: 'error',
        summary: 'Error en el Pago',
        detail: message,
        life: 5000
      })
      
      return {
        success: false,
        message
      }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Consultar estado de un pago
   */
  const checkPaymentStatus = async (pagoId) => {
    try {
      const response = await axios.get(`/api/pagos/${pagoId}/estado`)
      return response.data
    } catch (error) {
      console.error('Error consultando estado del pago:', error)
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'No se pudo consultar el estado del pago',
        life: 3000
      })
      return null
    }
  }
  
  /**
   * Formatear monto para mostrar
   */
  const formatAmount = (amount, currency = 'COP') => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: currency
    }).format(amount)
  }
  
  /**
   * Convertir a centavos (requerido por Wompi)
   */
  const toAmountInCents = (amount) => {
    return Math.round(amount * 100)
  }
  
  /**
   * Generar referencia única
   */
  const generateReference = (type, id) => {
    return `${type.toUpperCase()}_${id}_${Date.now()}`
  }
  
  /**
   * Validar email
   */
  const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }
  
  /**
   * Obtener configuración pública para el frontend
   */
  const getPublicConfig = () => {
    return {
      publicKey: wompiConfig.value?.public_key,
      sandbox: wompiConfig.value?.sandbox,
      widgetUrl: wompiConfig.value?.widget_url
    }
  }
  
  return {
    // Estado
    loading,
    wompiConfig,
    acceptanceToken,
    isReady,
    
    // Métodos principales
    initializeWompi,
    processVentaPayment,
    processReservaPayment,
    checkPaymentStatus,
    
    // Utilidades
    formatAmount,
    toAmountInCents,
    generateReference,
    isValidEmail,
    getPublicConfig
  }
}