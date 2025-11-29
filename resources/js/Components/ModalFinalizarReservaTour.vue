<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <!-- Header del Modal -->
      <div class="modal-header">
        <h3 class="modal-title">
          <FontAwesomeIcon :icon="faCreditCard" class="title-icon" />
          Finalizar Reserva
        </h3>
        <button @click="closeModal" class="close-button">
          <FontAwesomeIcon :icon="faTimes" />
        </button>
      </div>

      <!-- Error State -->
      <div v-if="error" class="error-state">
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
        <!-- Resumen de la reserva -->
        <div class="purchase-summary">
          <h4>Resumen de Reserva</h4>
          <div class="summary-details">
            <div class="tour-info">
              <div class="tour-item">
                <div class="tour-details">
                  <span class="tour-name">{{ reservaData.tour_nombre }}</span>
                  <div class="tour-participants">
                    <span v-if="reservaData.cupos_adultos > 0">
                      {{ reservaData.cupos_adultos }}x Mayores de edad
                    </span>
                    <span v-if="reservaData.cupos_menores > 0" :class="{ 'ml-2': reservaData.cupos_adultos > 0 }">
                      {{ reservaData.cupos_menores }}x Menores de edad
                    </span>
                  </div>
                </div>
                <span class="tour-price">${{ formatPrice(reservaData.total) }}</span>
              </div>
            </div>
            <div class="total-amount">
              <span>Total a pagar:</span>
              <span class="amount">${{ formatPrice(reservaData.total) }}</span>
            </div>

            <!-- Advertencia de límite de Wompi -->
            <div v-if="reservaData.total > 800" class="mt-3 p-3 rounded-lg" :class="reservaData.total > 1000 ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200'">
              <div class="flex items-start">
                <FontAwesomeIcon
                  :icon="faExclamationTriangle"
                  :class="reservaData.total > 1000 ? 'text-red-500' : 'text-yellow-500'"
                  class="h-5 w-5 mt-0.5 mr-3 flex-shrink-0"
                />
                <div>
                  <p :class="reservaData.total > 1000 ? 'text-red-800 font-semibold' : 'text-yellow-800 font-medium'" class="text-sm">
                    {{ reservaData.total > 1000 ? '⚠️ Límite superado' : '⚠️ Cerca del límite' }}
                  </p>
                  <p :class="reservaData.total > 1000 ? 'text-red-700' : 'text-yellow-700'" class="text-xs mt-1">
                    <span v-if="reservaData.total > 1000">
                      El monto máximo por transacción es $1,000 USD.
                      <strong>Contacta con nosotros</strong> para procesar este pago.
                    </span>
                    <span v-else>
                      Estás cerca del límite máximo de $1,000 USD por transacción.
                      Tu total actual es de <strong>${{ formatPrice(reservaData.total) }} USD</strong>.
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
              :disabled="procesandoPago || reservaData.total > 1000"
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              :class="reservaData.total > 1000 ? 'bg-red-400 hover:bg-red-400 cursor-not-allowed' : ''"
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
                  ? 'Procesando Reserva...'
                  : reservaData.total > 1000
                    ? 'Monto excede límite'
                    : 'Reservar y Pagar'
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
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faCreditCard,
  faTimes,
  faExclamationTriangle,
  faShield,
  faClock,
  faShieldAlt
} from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  reservaData: {
    type: Object,
    required: true,
    default: () => ({
      id: null,
      tour_nombre: '',
      cupos_adultos: 0,
      cupos_menores: 0,
      total: 0,
      cliente_email: ''
    })
  }
})

const emit = defineEmits(['close', 'payment-completed'])

// Estado del modal
const procesandoPago = ref(false)
const error = ref(null)

// Constantes para Wompi
const MAX_WOMPI_AMOUNT = 1000 // USD - Límite máximo por transacción

// Formatear precio
const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

// Validar monto máximo para Wompi
const validarMontoMaximo = () => {
  if (props.reservaData.total > MAX_WOMPI_AMOUNT) {
    error.value = `El monto máximo por reserva es de $${MAX_WOMPI_AMOUNT.toFixed(2)} USD. Total actual: $${props.reservaData.total.toFixed(2)} USD. Por favor, contacta con nosotros para procesar este pago.`
    return false
  }
  return true
}

// Crear la reserva primero
const crearReserva = async () => {
  try {
    const response = await axios.post('/reservas/tour', {
      tour_id: props.reservaData.tour_id,
      cliente_data: props.reservaData.cliente_data,
      cupos_adultos: props.reservaData.cupos_adultos,
      cupos_menores: props.reservaData.cupos_menores,
      precio_total: props.reservaData.total
    })

    // Actualizar el ID de la reserva creada
    return response.data.data.reserva.id
  } catch (error) {
    throw error
  }
}

// Procesar pago con Wompi El Salvador
const procesarPagoWompi = async () => {
  // ✅ VALIDAR MONTO ANTES DE PROCESAR
  if (!validarMontoMaximo()) {
    return
  }

  procesandoPago.value = true
  error.value = null

  try {
    // ✅ PASO 1: Crear la reserva primero
    let reservaId = props.reservaData.id

    if (!reservaId) {
      reservaId = await crearReserva()
    }
    // Obtener token CSRF actual
    const getCsrfToken = () => {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null
    }

    // Función para hacer el request
    const makePaymentRequest = async () => {
      return await fetch('/api/wompi/payment-link-tour', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          customer_email: props.reservaData.cliente_email,
          amount: props.reservaData.total,
          description: `Reserva de tour: ${props.reservaData.tour_nombre} - ${props.reservaData.cupos_adultos + props.reservaData.cupos_menores} cupos`,
          reference: `TOUR-${props.reservaData.id}-${Date.now()}`,
          customer_name: props.reservaData.cliente_nombre || 'Cliente',
          // ✅ NUEVO: Enviar reserva_id para tours
          reserva_id: reservaId,
          tour_data: {
            id: props.reservaData.tour_id,
            nombre: props.reservaData.tour_nombre,
            cupos_adultos: props.reservaData.cupos_adultos,
            cupos_menores: props.reservaData.cupos_menores,
            total: props.reservaData.total
          }
        })
      })
    }

    // Hacer el request principal
    let response = await makePaymentRequest()

    // Si es 419 (CSRF token mismatch), intentar refrescar token y reintentar
    if (response.status === 419) {

      try {
        // Intentar refrescar el token CSRF
        const csrfResponse = await fetch('/api/csrf-token', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        if (csrfResponse.ok) {
          const csrfData = await csrfResponse.json()
          if (csrfData.csrf_token) {
            // Actualizar el meta tag
            const metaTag = document.querySelector('meta[name="csrf-token"]')
            if (metaTag) {
              metaTag.setAttribute('content', csrfData.csrf_token)
            }

            // Reintentar el request original
            response = await makePaymentRequest()
          }
        }
      } catch (csrfError) {
        // Error silencioso al refrescar CSRF token
      }
    }

    const data = await response.json()

    if (response.ok && data.success) {
      // ✅ ÉXITO: Redirigir a Wompi

      if (data.payment_link) {
        // Abrir Wompi en una nueva ventana
        window.open(data.payment_link, '_blank')

        // Emitir evento de éxito y cerrar modal
        emit('payment-completed', {
          success: true,
          reserva: {
            ...props.reservaData,
            id: reservaId  // Asegurar que tiene el ID correcto
          },
          payment_link: data.payment_link,
          reference: data.reference
        })

        closeModal()
      } else {
        throw new Error('No se recibió el enlace de pago')
      }
    } else {
      // ❌ ERROR del servidor
      throw new Error(data.message || data.error || 'Error al crear el enlace de pago')
    }

  } catch (err) {

    let errorMessage = 'Error al procesar la reserva y el pago'

    // Manejar errores específicos de la reserva
    if (err.response?.status === 422) {
      const errors = err.response?.data?.errors || {}
      const errorMessages = Object.values(errors).flat()
      if (errorMessages.length > 0) {
        errorMessage = 'Error de validación: ' + errorMessages.join(', ')
      } else {
        errorMessage = err.response?.data?.message || 'Datos de reserva inválidos'
      }
    } else if (err.response?.status === 419) {
      errorMessage = 'Su sesión ha expirado. Por favor, recargue la página.'
      setTimeout(() => {
        window.location.reload()
      }, 2000)
    } else if (err.message) {
      errorMessage = err.message
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    }

    error.value = errorMessage
  } finally {
    procesandoPago.value = false
  }
}

// Cerrar modal
const closeModal = () => {
  emit('close')
  resetModal()
}

// Resetear estado del modal
const resetModal = () => {
  procesandoPago.value = false
  error.value = null
}

// Reintentar
const retry = () => {
  resetModal()
  procesarPagoWompi()
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

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(50px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 24px 16px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 12px;
}

.title-icon {
  color: #10b981;
  font-size: 1.25rem;
}

.close-button {
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-button:hover {
  background: #e5e7eb;
  color: #374151;
  transform: scale(1.05);
}

.error-state {
  padding: 32px 24px;
  text-align: center;
  color: #dc2626;
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 16px;
  opacity: 0.8;
}

.error-state h4 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 8px;
  color: #991b1b;
}

.error-state p {
  color: #dc2626;
  margin-bottom: 24px;
  line-height: 1.5;
}

.payment-form {
  padding: 24px;
}

.purchase-summary {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid #e2e8f0;
}

.purchase-summary h4 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.summary-details > * + * {
  margin-top: 16px;
}

.tour-info {
  margin-bottom: 16px;
}

.tour-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.tour-details {
  flex: 1;
}

.tour-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
  display: block;
  margin-bottom: 4px;
}

.tour-participants {
  font-size: 0.875rem;
  color: #64748b;
}

.tour-price {
  font-weight: 700;
  color: #059669;
  font-size: 1rem;
}

.total-amount {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 2px solid #e2e8f0;
  font-weight: 600;
  color: #1e293b;
}

.amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: #059669;
}

.payment-section {
  margin: 24px 0;
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  color: #6b7280;
}

.info-icon {
  color: #10b981;
  font-size: 0.875rem;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .modal-container {
    width: 95%;
    margin: 20px;
  }

  .modal-header {
    padding: 20px 16px 12px 16px;
  }

  .modal-title {
    font-size: 1.25rem;
  }

  .payment-form {
    padding: 16px;
  }

  .purchase-summary {
    padding: 16px;
  }

  .tour-item {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }

  .tour-price {
    align-self: flex-end;
  }
}
</style>
