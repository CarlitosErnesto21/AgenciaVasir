<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">
        Ejemplo de Integración - Pagos con Wompi
      </h1>

      <!-- Sección: Pago para Venta -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
          💰 Pago para Venta
        </h2>
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
          <div class="flex justify-between items-center mb-2">
            <span class="text-gray-600">Venta ID:</span>
            <span class="font-mono">#{{ ventaEjemplo.id }}</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="text-gray-600">Cliente:</span>
            <span>{{ ventaEjemplo.cliente }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Total:</span>
            <span class="text-xl font-bold text-blue-600">
              ${{ ventaEjemplo.total.toLocaleString() }} COP
            </span>
          </div>
        </div>
        
        <!-- Botón de pago usando nuestro componente -->
        <WompiPayButton
          :amount="ventaEjemplo.total"
          currency="COP"
          type="venta"
          :item-id="ventaEjemplo.id"
          :description="`Venta #${ventaEjemplo.id} - ${ventaEjemplo.descripcion}`"
          button-text="💳 Pagar Venta"
          @payment-success="handleVentaPaymentSuccess"
          @payment-error="handlePaymentError"
        />
      </div>

      <!-- Sección: Pago para Reserva -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
          🏨 Pago para Reserva
        </h2>
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
          <div class="flex justify-between items-center mb-2">
            <span class="text-gray-600">Reserva ID:</span>
            <span class="font-mono">#{{ reservaEjemplo.id }}</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="text-gray-600">Tour:</span>
            <span>{{ reservaEjemplo.tour }}</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="text-gray-600">Fecha:</span>
            <span>{{ reservaEjemplo.fecha }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Total:</span>
            <span class="text-xl font-bold text-green-600">
              ${{ reservaEjemplo.total.toLocaleString() }} COP
            </span>
          </div>
        </div>
        
        <!-- Botón de pago usando nuestro componente -->
        <WompiPayButton
          :amount="reservaEjemplo.total"
          currency="COP"
          type="reserva"
          :item-id="reservaEjemplo.id"
          :description="`Reserva #${reservaEjemplo.id} - ${reservaEjemplo.tour}`"
          button-text="🎫 Pagar Reserva"
          @payment-success="handleReservaPaymentSuccess"
          @payment-error="handlePaymentError"
        />
      </div>

      <!-- Sección: Uso del Composable -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
          🔧 Uso del Composable
        </h2>
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
          <p class="text-gray-600 mb-4">
            Estado de la inicialización de Wompi:
          </p>
          <div class="flex items-center space-x-4">
            <div class="flex items-center">
              <div 
                :class="[
                  'w-3 h-3 rounded-full mr-2',
                  wompiLoading ? 'bg-yellow-500' : isWompiReady ? 'bg-green-500' : 'bg-red-500'
                ]"
              ></div>
              <span class="text-sm font-medium">
                {{ wompiLoading ? 'Cargando...' : isWompiReady ? 'Listo' : 'No disponible' }}
              </span>
            </div>
            <button
              @click="initializeWompi"
              :disabled="wompiLoading || isWompiReady"
              class="px-4 py-2 bg-blue-600 text-white rounded disabled:bg-gray-400"
            >
              Inicializar Wompi
            </button>
          </div>
        </div>
      </div>

      <!-- Sección: Historial de Pagos (si hay) -->
      <div v-if="pagosRealizados.length > 0" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
          📋 Pagos Realizados en esta Sesión
        </h2>
        <div class="space-y-3">
          <div 
            v-for="pago in pagosRealizados" 
            :key="pago.id"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex justify-between items-start">
              <div>
                <p class="font-semibold">{{ pago.type }} #{{ pago.itemId }}</p>
                <p class="text-sm text-gray-600">{{ pago.description }}</p>
                <p class="text-sm text-gray-500">{{ pago.timestamp }}</p>
              </div>
              <div class="text-right">
                <p class="font-bold text-green-600">{{ formatAmount(pago.amount) }}</p>
                <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                  Exitoso
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import WompiPayButton from '@/Components/WompiPayButton.vue'
import { useWompiPayments } from '@/composables/useWompiPayments'

// Composables
const toast = useToast()
const { 
  loading: wompiLoading, 
  isReady: isWompiReady, 
  initializeWompi,
  formatAmount
} = useWompiPayments()

// Datos de ejemplo
const ventaEjemplo = ref({
  id: 1001,
  cliente: 'Juan Pérez',
  total: 150000,
  descripcion: '3 productos diversos'
})

const reservaEjemplo = ref({
  id: 2001,
  tour: 'Tour Cartagena Colonial',
  fecha: '2025-12-15',
  total: 250000
})

// Estado local
const pagosRealizados = ref([])

// Métodos
const handleVentaPaymentSuccess = (result) => {
  toast.add({
    severity: 'success',
    summary: 'Venta Pagada',
    detail: `La venta #${ventaEjemplo.value.id} ha sido pagada exitosamente`,
    life: 8000
  })
  
  // Agregar al historial local
  pagosRealizados.value.push({
    id: Date.now(),
    type: 'Venta',
    itemId: ventaEjemplo.value.id,
    description: ventaEjemplo.value.descripcion,
    amount: ventaEjemplo.value.total,
    timestamp: new Date().toLocaleString('es-CO'),
    result
  })
  
  console.log('Pago de venta exitoso:', result)
}

const handleReservaPaymentSuccess = (result) => {
  toast.add({
    severity: 'success',
    summary: 'Reserva Pagada',
    detail: `La reserva #${reservaEjemplo.value.id} ha sido pagada exitosamente`,
    life: 8000
  })
  
  // Agregar al historial local
  pagosRealizados.value.push({
    id: Date.now(),
    type: 'Reserva',
    itemId: reservaEjemplo.value.id,
    description: reservaEjemplo.value.tour,
    amount: reservaEjemplo.value.total,
    timestamp: new Date().toLocaleString('es-CO'),
    result
  })
  
  console.log('Pago de reserva exitoso:', result)
}

const handlePaymentError = (error) => {
  toast.add({
    severity: 'error',
    summary: 'Error en el Pago',
    detail: error.message || 'Ocurrió un error procesando el pago',
    life: 8000
  })
  
  console.error('Error en el pago:', error)
}

// Lifecycle
onMounted(() => {
  // Inicializar Wompi automáticamente al cargar la página
  initializeWompi()
})
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
.container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.container > div {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 32px;
}
</style>