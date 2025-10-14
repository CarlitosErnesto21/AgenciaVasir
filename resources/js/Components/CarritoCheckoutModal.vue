<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <!-- Header del Modal -->
      <div class="modal-header">
        <h3 class="modal-title">
          <FontAwesomeIcon :icon="faCreditCard" class="title-icon" />
          Finalizar Compra
        </h3>
        <button @click="closeModal" class="close-button">
          <FontAwesomeIcon :icon="faTimes" />
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isCreatingVenta" class="loading-state">
        <div class="spinner"></div>
        <p>Procesando tu pedido...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <FontAwesomeIcon :icon="faExclamationTriangle" class="error-icon" />
        <h4>Error al procesar</h4>
        <p>{{ error }}</p>
        <button @click="retry" class="retry-button">
          Intentar de nuevo
        </button>
      </div>

      <!-- Payment Form -->
      <div v-else-if="ventaCreada" class="payment-form">
        <!-- Resumen de la compra -->
        <div class="purchase-summary">
          <h4>Resumen de tu compra</h4>
          <div class="summary-details">
            <div class="total-amount">
              <span>Total a pagar:</span>
              <span class="amount">${{ formatPrice(ventaCreada.total) }}</span>
            </div>
            <div class="order-reference">
              <span>Número de orden:</span>
              <span class="reference">#{{ ventaCreada.id }}</span>
            </div>
          </div>
        </div>

        <!-- Botón de Pago Simplificado -->
        <div class="payment-section">
          <button
            @click="procesarPagoWompi"
            :disabled="procesandoPago"
            class="w-full inline-flex items-center justify-center px-6 py-4 border border-transparent text-lg font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="!procesandoPago" class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <svg v-else class="w-6 h-6 mr-3 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ procesandoPago ? 'Generando enlace de pago...' : 'Pagar con Wompi' }}
          </button>
        </div>

        <!-- Información adicional -->
        <div class="payment-info">
          <div class="info-item">
            <FontAwesomeIcon :icon="faShieldAlt" class="info-icon" />
            <span>Pago seguro con encriptación SSL</span>
          </div>
          <div class="info-item">
            <FontAwesomeIcon :icon="faClock" class="info-icon" />
            <span>Procesamiento inmediato</span>
          </div>
        </div>
      </div>

      <!-- Success State -->
      <div v-else-if="paymentSuccess" class="success-state">
        <FontAwesomeIcon :icon="faCheckCircle" class="success-icon" />
        <h4>¡Pago exitoso!</h4>
        <p>Tu compra ha sido procesada correctamente.</p>
        <div class="success-details">
          <p><strong>Número de orden:</strong> #{{ ventaCreada?.id }}</p>
          <p><strong>Total pagado:</strong> ${{ formatPrice(ventaCreada?.total) }}</p>
        </div>
        <button @click="closeAndClearCart" class="success-button">
          Finalizar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faCreditCard,
  faTimes,
  faExclamationTriangle,
  faShieldAlt,
  faClock,
  faCheckCircle
} from '@fortawesome/free-solid-svg-icons'
import { useCarrito } from '@/composables/useCarrito'
import { useCarritoStore } from '@/stores/carrito'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'payment-completed'])

// Composables
const { createVentaFromCarrito, loading: carritoLoading, error: carritoError } = useCarrito()
const carritoStore = useCarritoStore()
const page = usePage()

// Estado del modal
const isCreatingVenta = ref(false)
const ventaCreada = ref(null)
const paymentSuccess = ref(false)
const error = ref(null)
const procesandoPago = ref(false)

// Datos del usuario
const user = computed(() => page.props.auth?.user)
const customerEmail = computed(() => user.value?.email || '')
const customerName = computed(() => user.value?.name || '')

// Formatear precio
const formatPrice = (price) => {
  if (!price) return '0.00'
  return new Intl.NumberFormat('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

// Crear venta cuando se abre el modal
watch(() => props.isVisible, async (newValue) => {
  if (newValue && !ventaCreada.value && !paymentSuccess.value) {
    await createVenta()
  }
})

// Crear venta desde carrito
const createVenta = async () => {
  if (carritoStore.isEmpty) {
    error.value = 'El carrito está vacío'
    return
  }

  if (!customerEmail.value) {
    error.value = 'No se encontró información del usuario'
    return
  }

  isCreatingVenta.value = true
  error.value = null

  try {
    const result = await createVentaFromCarrito(customerEmail.value)

    if (result.success) {
      ventaCreada.value = result.venta
    } else {
      error.value = result.error || 'Error creando la venta'
    }
  } catch (err) {
    error.value = 'Error inesperado al crear la venta'
    console.error('Error creando venta:', err)
  } finally {
    isCreatingVenta.value = false
  }
}

// Manejar eventos del pago
const handlePaymentSuccess = (paymentData) => {
  console.log('Pago exitoso:', paymentData)
  paymentSuccess.value = true
  emit('payment-completed', {
    success: true,
    venta: ventaCreada.value,
    payment: paymentData
  })
}

const handlePaymentError = (error) => {
  console.error('Error en el pago:', error)
  error.value = `Error en el pago: ${error.message || 'Error desconocido'}`
}

// Procesar pago con Wompi El Salvador
const procesarPagoWompi = async () => {
  if (!ventaCreada.value) {
    error.value = 'No hay venta creada'
    return
  }

  procesandoPago.value = true
  error.value = null

  try {
    // Crear cliente axios con CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    const response = await fetch('/api/wompi/payment-link', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        venta_id: ventaCreada.value.id,
        customer_email: customerEmail.value,
        customer_name: customerName.value
      })
    })

    const data = await response.json()

    if (data.success) {
      // Redirigir al enlace de pago de Wompi
      window.open(data.payment_link, '_blank')

      // Mostrar mensaje de éxito temporal
      paymentSuccess.value = true
      emit('payment-completed', {
        success: true,
        venta: ventaCreada.value,
        payment_link: data.payment_link,
        reference: data.reference
      })
    } else {
      error.value = data.message || 'Error creando enlace de pago'
    }
  } catch (err) {
    console.error('Error procesando pago:', err)
    error.value = 'Error procesando el pago. Inténtalo de nuevo.'
  } finally {
    procesandoPago.value = false
  }
}

const handlePaymentPending = (paymentData) => {
  console.log('Pago pendiente:', paymentData)
  // Podrías mostrar un estado de "pago pendiente" aquí
}

// Cerrar modal
const closeModal = () => {
  emit('close')
  resetModal()
}

// Cerrar y limpiar carrito después del pago exitoso
const closeAndClearCart = () => {
  carritoStore.limpiarCarrito()
  closeModal()
}

// Resetear estado del modal
const resetModal = () => {
  ventaCreada.value = null
  paymentSuccess.value = false
  error.value = null
  isCreatingVenta.value = false
}

// Reintentar crear venta
const retry = () => {
  resetModal()
  createVenta()
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease-out;
}

.modal-container {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  animation: slideInUp 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
}

.title-icon {
  color: #3b82f6;
}

.close-button {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  color: #6b7280;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.close-button:hover {
  background: #f3f4f6;
  color: #374151;
}

/* Loading State */
.loading-state {
  padding: 48px 24px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

/* Error State */
.error-state {
  padding: 48px 24px;
  text-align: center;
}

.error-icon {
  font-size: 3rem;
  color: #ef4444;
  margin-bottom: 16px;
}

.retry-button {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  margin-top: 16px;
  transition: all 0.2s ease;
}

.retry-button:hover {
  background: #2563eb;
}

/* Payment Form */
.payment-form {
  padding: 24px;
}

.purchase-summary {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.purchase-summary h4 {
  margin: 0 0 16px 0;
  color: #1f2937;
  font-weight: 600;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.total-amount,
.order-reference {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: #059669;
}

.reference {
  font-family: monospace;
  background: #e5e7eb;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
}

.payment-section {
  margin-bottom: 24px;
}

.pay-now-button {
  width: 100%;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  padding: 16px 24px;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
}

.pay-now-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 0.875rem;
}

.info-icon {
  color: #3b82f6;
}

/* Success State */
.success-state {
  padding: 48px 24px;
  text-align: center;
}

.success-icon {
  font-size: 4rem;
  color: #059669;
  margin-bottom: 16px;
}

.success-details {
  background: #f0fdf4;
  border-radius: 8px;
  padding: 16px;
  margin: 16px 0;
  border: 1px solid #bbf7d0;
}

.success-button {
  background: #059669;
  color: white;
  border: none;
  padding: 12px 32px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  margin-top: 16px;
  transition: all 0.2s ease;
}

.success-button:hover {
  background: #047857;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    width: 95%;
    margin: 20px;
  }

  .modal-header,
  .payment-form {
    padding: 16px;
  }
}
</style>
