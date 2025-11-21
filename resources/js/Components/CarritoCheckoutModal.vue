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
        <div class="flex justify-center gap-4 w-full mt-4">
          <button
            @click="closeModal"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          >
            <FontAwesomeIcon :icon="faTimes" class="h-5" />
            Cancelar
          </button>
          <button
            @click="retry"
            class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          >
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-5" />
            Intentar de nuevo
          </button>
        </div>
      </div>

      <!-- Payment Form -->
      <div v-else class="payment-form">
        <!-- Resumen de la compra -->
        <div class="purchase-summary">
          <h4>Resumen de tu compra</h4>
          <div class="summary-details">
            <div class="cart-items">
              <div v-for="item in carritoStore.items" :key="item.id" class="cart-item">
                <span class="item-name">{{ item.nombre }}</span>
                <span class="item-quantity">{{ item.cantidad }}x</span>
                <span class="item-price">${{ formatPrice(item.precio * item.cantidad) }}</span>
              </div>
            </div>
            <div class="total-amount">
              <span>Total a pagar:</span>
              <span class="amount">${{ formatPrice(carritoStore.totalPrice) }}</span>
            </div>
            <!-- Advertencia de límite de Wompi -->
            <div v-if="carritoStore.totalPrice > 800" class="mt-3 p-3 rounded-lg" :class="carritoStore.totalPrice > 1000 ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200'">
              <div class="flex items-start">
                <FontAwesomeIcon
                  :icon="faExclamationTriangle"
                  :class="carritoStore.totalPrice > 1000 ? 'text-red-500' : 'text-yellow-500'"
                  class="h-4 w-4 mr-2 mt-0.5"
                />
                <div class="text-sm">
                  <p v-if="carritoStore.totalPrice > 1000" class="text-red-800 font-medium">
                    <strong>Monto excede el límite</strong>
                  </p>
                  <p v-else class="text-yellow-800 font-medium">
                    <strong>Cerca del límite máximo</strong>
                  </p>
                  <p :class="carritoStore.totalPrice > 1000 ? 'text-red-700' : 'text-yellow-700'" class="mt-1">
                    El límite máximo por transacción es de <strong>$1,000.00 USD</strong>.
                    <span v-if="carritoStore.totalPrice > 1000">
                      Por favor, reduce la cantidad de productos o contacta con nosotros para pagos mayores.
                    </span>
                    <span v-else>
                      Tu total actual es de <strong>${{ formatPrice(carritoStore.totalPrice) }} USD</strong>.
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón de Pago Simplificado -->
        <div class="payment-section">
          <div class="flex justify-center gap-4 w-full">
            <button
              @click="closeModal"
              class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
              :disabled="procesandoPago"
            >
              <FontAwesomeIcon :icon="faTimes" class="h-5" />
              Cancelar
            </button>
            <button
              @click="procesarPagoWompi"
              :disabled="procesandoPago || carritoStore.totalPrice > 1000"
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              :class="carritoStore.totalPrice > 1000 ? 'bg-red-400 hover:bg-red-400 cursor-not-allowed' : ''"
            >
              <FontAwesomeIcon
                v-if="!procesandoPago"
                :icon="faCreditCard"
                class="h-5"
              />
              <FontAwesomeIcon
                v-else
                :icon="faCreditCard"
                class="h-5 animate-spin"
              />
              {{
                procesandoPago
                  ? 'Generando enlace...'
                  : carritoStore.totalPrice > 1000
                    ? 'Monto excede límite'
                    : 'Pagar con Wompi'
              }}
            </button>
          </div>
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
      <div v-if="paymentSuccess" class="success-state">
        <FontAwesomeIcon :icon="faCheckCircle" class="success-icon" />
        <h4>¡Enlace de pago creado!</h4>
        <p>Se abrió una nueva ventana con tu enlace de pago seguro.</p>
        <div class="success-details">
          <p v-if="ventaCreada"><strong>Número de orden:</strong> #{{ ventaCreada.id }}</p>
          <p><strong>Total pagado:</strong> ${{ formatPrice(carritoStore.totalPrice) }}</p>
          <p class="cart-cleared-notice">
            <FontAwesomeIcon :icon="faShoppingCart" class="cart-icon" />
            Tu carrito se ha limpiado automáticamente
          </p>
        </div>
        <div class="success-actions">
          <p class="auto-close-notice">Esta ventana se cerrará automáticamente en unos segundos</p>
          <div class="flex justify-center gap-4 w-full mt-4">
            <button
              @click="closeModal"
              class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            >
              <FontAwesomeIcon :icon="faTimes" class="h-5" />
              Cerrar ahora
            </button>
          </div>
        </div>
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
  faCheckCircle,
  faShoppingCart
} from '@fortawesome/free-solid-svg-icons'
import { useCarrito } from '@/composables/useCarrito'
import { useCarritoStore } from '@/stores/carrito'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  clienteData: {
    type: Object,
    default: null
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

// Constantes para Wompi
const MAX_WOMPI_AMOUNT = 1000 // USD - Límite máximo por transacción

// Datos del usuario y cliente
const user = computed(() => page.props.auth?.user)
const customerEmail = computed(() => user.value?.email || '')
const customerName = computed(() => {
  // Usar nombre del usuario autenticado
  if (user.value?.name) {
    return user.value.name
  }
  // Fallback si no hay usuario
  return 'Cliente'
})

// Datos del cliente para la venta
const clienteInfo = computed(() => {
  if (props.clienteData) {
    return {
      id: props.clienteData.id,
      numero_identificacion: props.clienteData.numero_identificacion,
      telefono: props.clienteData.telefono,
      direccion: props.clienteData.direccion,
      tipo_documento_id: props.clienteData.tipo_documento_id
    }
  }
  return null
})

// Formatear precio
const formatPrice = (price) => {
  if (!price) return '0.00'
  return new Intl.NumberFormat('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

// Resetear estado cuando se abre el modal
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    resetModal()
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

  if (!clienteInfo.value) {
    error.value = 'No se encontraron datos del cliente'
    return
  }

  isCreatingVenta.value = true
  error.value = null

  try {
    const result = await createVentaFromCarrito(customerEmail.value, clienteInfo.value)

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

// Validar monto máximo para Wompi
const validarMontoMaximo = () => {
  if (carritoStore.totalPrice > MAX_WOMPI_AMOUNT) {
    error.value = `El monto máximo por compra es de $${MAX_WOMPI_AMOUNT.toFixed(2)} USD. Total actual: $${carritoStore.totalPrice.toFixed(2)} USD. Por favor, reduce la cantidad de productos o contacta con nosotros para pagos mayores.`
    return false
  }
  return true
}

// Procesar pago con Wompi El Salvador
const procesarPagoWompi = async () => {
  // ✅ VALIDAR MONTO ANTES DE PROCESAR
  if (!validarMontoMaximo()) {
    return
  }

  procesandoPago.value = true
  error.value = null

  // ✅ PASO 1: Crear la venta primero
  if (!ventaCreada.value) {
    isCreatingVenta.value = true

    try {
      await createVenta()

      // Si hubo error creando la venta, detener
      if (error.value || !ventaCreada.value) {
        procesandoPago.value = false
        return
      }
    } finally {
      isCreatingVenta.value = false
    }
  }

  try {
    // Obtener token CSRF actual
    const getCsrfToken = () => {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null
    }

    // Función para hacer el request
    const makePaymentRequest = async () => {
      return await fetch('/wompi/payment-link', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          customer_email: customerEmail.value,
          amount: carritoStore.totalPrice,
          description: `Compra de ${carritoStore.items.length} producto(s) - ${customerName.value}`,
          reference: `CART-${Date.now()}`,
          customer_name: customerName.value,
          // ✅ NUEVO: Enviar venta_id si hay una venta creada
          venta_id: ventaCreada.value?.id || null,
          productos: carritoStore.items.map(item => ({
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            cantidad: item.cantidad,
            imagen: item.primera_imagen || item.imagen || null,
            subtotal: item.precio * item.cantidad
          }))
        })
      })
    }

    let response = await makePaymentRequest()

    // Si es error 419, intentar renovar token
    if (response.status === 419) {
      console.log('🔄 Token CSRF expirado, renovando...')

      // Hacer petición para obtener nuevo token
      const freshResponse = await fetch('/tienda', {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html,application/xhtml+xml'
        }
      })

      const html = await freshResponse.text()
      const match = html.match(/name="csrf-token" content="([^"]+)"/)

      if (match) {
        const newToken = match[1]
        const metaTag = document.querySelector('meta[name="csrf-token"]')
        if (metaTag) {
          metaTag.setAttribute('content', newToken)
          console.log('✅ Token CSRF renovado:', newToken)
        }
      }

      // Reintentar con nuevo token
      response = await makePaymentRequest()
    }

    const data = await response.json()

    if (data.success) {
      // Redirigir al enlace de pago de Wompi
      window.open(data.payment_link, '_blank')

      // ✅ NUEVO: Limpiar carrito inmediatamente después de crear el enlace de pago
      carritoStore.limpiarCarrito()

      // Mostrar mensaje de éxito temporal
      paymentSuccess.value = true

      // Cerrar modal automáticamente después de 3 segundos
      setTimeout(() => {
        closeModal()
      }, 3000)

      emit('payment-completed', {
        success: true,
        venta: ventaCreada.value,
        payment_link: data.payment_link,
        reference: data.reference,
        cart_cleared: true
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
  z-index: 10001;
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

.cart-items {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
  max-height: 200px;
  overflow-y: auto;
}

.cart-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
}

.cart-item:last-child {
  border-bottom: none;
}

.item-name {
  flex: 1;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.item-quantity {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 12px;
}

.item-price {
  font-weight: 600;
  color: #059669;
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

/* Estilos para carrito limpiado */
.cart-cleared-notice {
  color: #10B981;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding: 8px 12px;
  background: #F0FDF4;
  border-radius: 6px;
  border: 1px solid #BBF7D0;
}

.cart-icon {
  color: #10B981;
}

.success-actions {
  margin-top: 20px;
}

.auto-close-notice {
  color: #6B7280;
  font-size: 12px;
  margin-bottom: 12px;
  font-style: italic;
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
