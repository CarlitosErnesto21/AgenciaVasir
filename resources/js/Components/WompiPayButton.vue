<template>
  <div class="wompi-pay-button">
    <button
      @click="openPaymentModal"
      :disabled="disabled"
      :class="[
        'inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md transition-colors',
        disabled
          ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
          : 'bg-blue-600 hover:bg-blue-700 text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
      ]"
    >
      <svg v-if="!disabled" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
      </svg>
      {{ buttonText }}
    </button>

    <!-- Modal de pago -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closePaymentModal"
        ></div>

        <!-- Contenido del modal -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex items-start justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Pagar con Tarjeta
              </h3>
              <button
                @click="closePaymentModal"
                class="text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <WompiPaymentWidget
              :amount="amount"
              :currency="currency"
              :type="type"
              :item-id="itemId"
              :description="description"
              @success="handlePaymentSuccess"
              @error="handlePaymentError"
              @cancel="closePaymentModal"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import WompiPaymentWidget from './WompiPaymentWidget.vue'

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
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  buttonText: {
    type: String,
    default: 'Pagar con Tarjeta'
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['payment-success', 'payment-error'])

// Composables
const toast = useToast()

// Estado reactivo
const showModal = ref(false)

// Métodos
const openPaymentModal = () => {
  if (props.disabled) return
  showModal.value = true
}

const closePaymentModal = () => {
  showModal.value = false
}

const handlePaymentSuccess = (result) => {
  closePaymentModal()
  emit('payment-success', result)

  toast.add({
    severity: 'success',
    summary: 'Pago Exitoso',
    detail: '¡Tu pago ha sido procesado correctamente!',
    life: 5000
  })
}

const handlePaymentError = (error) => {
  // No cerramos el modal automáticamente en caso de error
  // para que el usuario pueda intentar de nuevo
  emit('payment-error', error)
}
</script>

<style scoped>
/* Los estilos están incluidos en las clases de Tailwind */
</style>
