<template>
  <div class="wompi-payment-widget">
    <!-- Loader mientras se inicializa -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Cargando formulario de pago...</p>
    </div>

    <!-- Formulario de pago -->
    <div v-else-if="!paymentCompleted" class="payment-form">
      <div class="mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Procesar Pago</h3>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-700">Total a pagar:</span>
            <span class="text-2xl font-bold text-blue-600">{{ formattedAmount }}</span>
          </div>
          <div v-if="description" class="text-sm text-gray-600 mt-1">
            {{ description }}
          </div>
        </div>
      </div>

      <!-- Formulario de datos del cliente -->
      <div class="mb-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email del cliente *
          </label>
          <input
            v-model="customerEmail"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="cliente@ejemplo.com"
          />
        </div>
      </div>

      <!-- Contenedor para el widget de Wompi -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Información de la tarjeta
        </label>
        <div
          id="wompi-widget-container"
          class="border border-gray-300 rounded-md p-4"
        ></div>
      </div>

      <!-- Términos y condiciones -->
      <div class="mb-6">
        <label class="flex items-start space-x-3">
          <input
            v-model="acceptedTerms"
            type="checkbox"
            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <span class="text-sm text-gray-700">
            Acepto los
            <a href="#" class="text-blue-600 hover:underline">términos y condiciones</a>
            y autorizo el procesamiento de mis datos para realizar este pago.
          </span>
        </label>
      </div>

      <!-- Botones de acción -->
      <div class="flex space-x-4">
        <button
          @click="processPayment"
          :disabled="!canProcessPayment"
          :class="[
            'flex-1 py-3 px-4 rounded-md font-medium transition-colors',
            canProcessPayment
              ? 'bg-blue-600 hover:bg-blue-700 text-white'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          ]"
        >
          <span v-if="processing" class="flex items-center justify-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Procesando...
          </span>
          <span v-else>Procesar Pago</span>
        </button>

        <button
          @click="$emit('cancel')"
          class="px-6 py-3 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
        >
          Cancelar
        </button>
      </div>
    </div>

    <!-- Resultado del pago -->
    <div v-else class="payment-result text-center py-8">
      <div v-if="paymentResult.success" class="success">
        <div class="text-green-600 text-6xl mb-4">✓</div>
        <h3 class="text-2xl font-bold text-green-600 mb-2">¡Pago Exitoso!</h3>
        <p class="text-gray-600 mb-4">Tu pago ha sido procesado correctamente.</p>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
          <p class="text-sm text-green-800">
            <strong>Referencia:</strong> {{ paymentResult.reference }}
          </p>
          <p class="text-sm text-green-800">
            <strong>Monto:</strong> {{ formattedAmount }}
          </p>
        </div>
        <button
          @click="$emit('success', paymentResult)"
          class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-medium"
        >
          Continuar
        </button>
      </div>

      <div v-else class="error">
        <div class="text-red-600 text-6xl mb-4">✗</div>
        <h3 class="text-2xl font-bold text-red-600 mb-2">Error en el Pago</h3>
        <p class="text-gray-600 mb-4">{{ paymentResult.message }}</p>
        <div class="flex space-x-4 justify-center">
          <button
            @click="resetPayment"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium"
          >
            Intentar de Nuevo
          </button>
          <button
            @click="$emit('error', paymentResult)"
            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-md font-medium"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// Props
const props = defineProps({
  amount: {
    type: Number,
    required: true
  },
  currency: {
    type: String,
    default: 'COP'
  },
  type: {
    type: String,
    required: true, // 'venta' o 'reserva'
    validator: (value) => ['venta', 'reserva'].includes(value)
  },
  itemId: {
    type: Number,
    required: true // ID de la venta o reserva
  },
  description: {
    type: String,
    default: ''
  }
})

// Emits
const emit = defineEmits(['success', 'error', 'cancel'])

// Composables
const toast = useToast()

// Estado reactivo
const loading = ref(true)
const processing = ref(false)
const paymentCompleted = ref(false)
const customerEmail = ref('')
const acceptedTerms = ref(false)
const paymentResult = ref(null)

// Variables de Wompi
const wompiConfig = ref(null)
const wompiWidget = ref(null)
const acceptanceToken = ref('')

// Computed
const formattedAmount = computed(() => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: props.currency
  }).format(props.amount)
})

const canProcessPayment = computed(() => {
  return !processing.value &&
         customerEmail.value &&
         acceptedTerms.value &&
         wompiWidget.value
})

// Métodos
const loadWompiConfig = async () => {
  try {
    // Obtener configuración
    const configResponse = await axios.get('/api/wompi/config')
    wompiConfig.value = configResponse.data
    
    // Intentar obtener token de aceptación
    try {
      const tokenResponse = await axios.get('/api/wompi/acceptance-token')
      acceptanceToken.value = tokenResponse.data.acceptance_token
    } catch (tokenError) {
      console.warn('Usando token de prueba:', tokenError.message)
      acceptanceToken.value = 'sandbox_acceptance_token'
    }
    
    await initializeWompiWidget()
  } catch (error) {
    console.error('Error cargando configuración de Wompi:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar el formulario de pago',
      life: 5000
    })
  }
}

const initializeWompiWidget = async () => {
  try {
    // Cargar el script de Wompi si no está cargado
    if (!window.WidgetCheckout) {
      await loadWompiScript()
    }

    await nextTick()

    // Inicializar el widget de Wompi
    wompiWidget.value = new window.WidgetCheckout({
      currency: props.currency,
      amountInCents: Math.round(props.amount * 100),
      reference: `${props.type.toUpperCase()}_${props.itemId}_${Date.now()}`,
      publicKey: wompiConfig.value.public_key,
      redirectUrl: window.location.origin + '/payment/success'
    })

    // Renderizar el widget
    wompiWidget.value.render('wompi-widget-container')

    loading.value = false
  } catch (error) {
    console.error('Error inicializando widget de Wompi:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo inicializar el formulario de pago',
      life: 5000
    })
  }
}

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

const processPayment = async () => {
  if (!canProcessPayment.value) return

  processing.value = true

  try {
    // Obtener token de la tarjeta del widget
    const cardToken = await wompiWidget.value.createToken()

    if (!cardToken || cardToken.status !== 'CREATED') {
      throw new Error('No se pudo procesar la información de la tarjeta')
    }

    // Preparar datos para el backend
    const paymentData = {
      [`${props.type}_id`]: props.itemId,
      token: cardToken.id,
      customer_email: customerEmail.value,
      acceptance_token: acceptanceToken.value
    }

    // Procesar pago en el backend
    const response = await axios.post(`/api/pagos/${props.type}`, paymentData)

    if (response.data.success) {
      paymentResult.value = {
        success: true,
        reference: response.data.pago.referencia_wompi,
        data: response.data
      }

      toast.add({
        severity: 'success',
        summary: 'Pago Exitoso',
        detail: 'Tu pago ha sido procesado correctamente',
        life: 5000
      })
    } else {
      throw new Error(response.data.message || 'Error procesando el pago')
    }

  } catch (error) {
    console.error('Error procesando pago:', error)

    paymentResult.value = {
      success: false,
      message: error.response?.data?.message || error.message || 'Error procesando el pago'
    }

    toast.add({
      severity: 'error',
      summary: 'Error en el Pago',
      detail: paymentResult.value.message,
      life: 5000
    })
  } finally {
    processing.value = false
    paymentCompleted.value = true
  }
}

const resetPayment = () => {
  paymentCompleted.value = false
  paymentResult.value = null
  processing.value = false
  // Reinicializar el widget si es necesario
  if (wompiWidget.value) {
    wompiWidget.value.render('wompi-widget-container')
  }
}

// Lifecycle
onMounted(() => {
  loadWompiConfig()
})
</script>

<style scoped>
.wompi-payment-widget {
  max-width: 500px;
  margin: 0 auto;
}

.payment-form {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.payment-result {
  background: white;
  border-radius: 8px;
  padding: 32px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

#wompi-widget-container {
  min-height: 120px;
}
</style>
