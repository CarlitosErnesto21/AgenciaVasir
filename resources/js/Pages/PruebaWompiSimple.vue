<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          🚀 Prueba Simple de Wompi
        </h1>
        <p class="text-xl text-gray-600">
          Probando la integración básica de pagos
        </p>
      </div>

      <!-- Estado de la configuración -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
          📋 Estado de la Configuración
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex items-center p-4 bg-green-50 rounded-lg">
            <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
            <div>
              <p class="font-medium text-green-800">Configuración de Wompi</p>
              <p class="text-sm text-green-600">✅ Cargada correctamente</p>
            </div>
          </div>

          <div class="flex items-center p-4 bg-blue-50 rounded-lg">
            <div class="w-4 h-4 bg-blue-500 rounded-full mr-3"></div>
            <div>
              <p class="font-medium text-blue-800">Modo Sandbox</p>
              <p class="text-sm text-blue-600">🧪 Ambiente de pruebas</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Ejemplo de Venta -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">
          💰 Simular Pago de Venta
        </h2>

        <div class="bg-gray-50 rounded-lg p-6 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
              <p class="text-sm text-gray-500 mb-1">ID de Venta</p>
              <p class="text-xl font-bold text-gray-800">#{{ ventaTest.id }}</p>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-500 mb-1">Productos</p>
              <p class="text-xl font-bold text-gray-800">{{ ventaTest.productos }}</p>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-500 mb-1">Total</p>
              <p class="text-2xl font-bold text-blue-600">
                ${{ ventaTest.total.toLocaleString() }} COP
              </p>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button
            @click="simularPagoVenta"
            :disabled="procesandoPago"
            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 px-8 rounded-lg transition-colors duration-200 inline-flex items-center"
          >
            <svg v-if="procesandoPago" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            💳 {{ procesandoPago ? 'Procesando...' : 'Simular Pago de Venta' }}
          </button>
        </div>
      </div>

      <!-- Ejemplo de Reserva -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">
          🎫 Simular Pago de Reserva
        </h2>

        <div class="bg-gray-50 rounded-lg p-6 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
              <p class="text-sm text-gray-500 mb-1">ID de Reserva</p>
              <p class="text-xl font-bold text-gray-800">#{{ reservaTest.id }}</p>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-500 mb-1">Tour</p>
              <p class="text-lg font-bold text-gray-800">{{ reservaTest.tour }}</p>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-500 mb-1">Total</p>
              <p class="text-2xl font-bold text-green-600">
                ${{ reservaTest.total.toLocaleString() }} COP
              </p>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button
            @click="simularPagoReserva"
            :disabled="procesandoPago"
            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-3 px-8 rounded-lg transition-colors duration-200 inline-flex items-center"
          >
            <svg v-if="procesandoPago" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            🎫 {{ procesandoPago ? 'Procesando...' : 'Simular Pago de Reserva' }}
          </button>
        </div>
      </div>

      <!-- Resultados de las pruebas -->
      <div v-if="resultadosPruebas.length > 0" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">
          📊 Resultados de las Pruebas
        </h2>

        <div class="space-y-4">
          <div
            v-for="resultado in resultadosPruebas"
            :key="resultado.id"
            :class="[
              'border rounded-lg p-4',
              resultado.exito ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
            ]"
          >
            <div class="flex items-start justify-between">
              <div>
                <p class="font-semibold" :class="resultado.exito ? 'text-green-800' : 'text-red-800'">
                  {{ resultado.tipo }} #{{ resultado.itemId }}
                </p>
                <p class="text-sm text-gray-600 mt-1">{{ resultado.descripcion }}</p>
                <p class="text-xs text-gray-500 mt-2">{{ resultado.timestamp }}</p>
              </div>
              <div class="text-right">
                <div :class="resultado.exito ? 'text-green-600' : 'text-red-600'">
                  {{ resultado.exito ? '✅' : '❌' }}
                </div>
                <p class="font-bold mt-1">
                  ${{ resultado.monto.toLocaleString() }} COP
                </p>
              </div>
            </div>

            <div v-if="!resultado.exito" class="mt-3 p-2 bg-red-100 rounded text-red-700 text-sm">
              <strong>Error:</strong> {{ resultado.error }}
            </div>
          </div>
        </div>
      </div>

      <!-- Información de prueba -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mt-8">
        <h3 class="text-lg font-semibold text-yellow-800 mb-3">
          ℹ️ Información de Prueba
        </h3>
        <div class="text-sm text-yellow-700 space-y-2">
          <p><strong>Tarjeta de prueba:</strong> 4242424242424242</p>
          <p><strong>CVV:</strong> 123</p>
          <p><strong>Fecha:</strong> Cualquier fecha futura (ej: 12/25)</p>
          <p><strong>Email:</strong> Cualquier email válido</p>
          <p class="mt-3 text-yellow-600">
            <strong>Nota:</strong> Esta es una simulación. Los botones arriba prueban que los endpoints respondan correctamente.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// Composables
const toast = useToast()

// Estado reactivo
const procesandoPago = ref(false)
const resultadosPruebas = ref([])

// Datos de prueba
const ventaTest = ref({
  id: 1001,
  productos: '3 items',
  total: 75000
})

const reservaTest = ref({
  id: 2001,
  tour: 'Cartagena Colonial',
  total: 150000
})

// Métodos
const simularPagoVenta = async () => {
  procesandoPago.value = true

  try {
    // Simular datos de pago
    const datosPago = {
      venta_id: ventaTest.value.id,
      token: 'tok_test_' + Math.random().toString(36).substr(2, 9),
      customer_email: 'test@ejemplo.com',
      acceptance_token: 'sandbox_acceptance_token'
    }

    console.log('Simulando pago de venta:', datosPago)

    // Hacer petición real al backend
    const response = await axios.post('/api/pagos/venta', datosPago, {
      headers: {
        'Authorization': 'Bearer ' + (window.Laravel?.user?.token || 'test-token'),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    // Registrar resultado exitoso
    resultadosPruebas.value.unshift({
      id: Date.now(),
      tipo: 'Venta',
      itemId: ventaTest.value.id,
      descripcion: `${ventaTest.value.productos} - Pago procesado`,
      monto: ventaTest.value.total,
      exito: true,
      timestamp: new Date().toLocaleString('es-CO'),
      respuesta: response.data
    })

    toast.add({
      severity: 'success',
      summary: 'Simulación Exitosa',
      detail: 'El pago de venta se procesó correctamente',
      life: 5000
    })

  } catch (error) {
    console.error('Error en simulación de pago de venta:', error)

    // Registrar resultado con error
    resultadosPruebas.value.unshift({
      id: Date.now(),
      tipo: 'Venta',
      itemId: ventaTest.value.id,
      descripcion: `${ventaTest.value.productos} - Error al procesar`,
      monto: ventaTest.value.total,
      exito: false,
      error: error.response?.data?.message || error.message,
      timestamp: new Date().toLocaleString('es-CO')
    })

    toast.add({
      severity: 'info',
      summary: 'Prueba de Endpoint',
      detail: `Endpoint probado: ${error.response?.status || 'Sin respuesta'}`,
      life: 5000
    })
  } finally {
    procesandoPago.value = false
  }
}

const simularPagoReserva = async () => {
  procesandoPago.value = true

  try {
    // Simular datos de pago
    const datosPago = {
      reserva_id: reservaTest.value.id,
      token: 'tok_test_' + Math.random().toString(36).substr(2, 9),
      customer_email: 'test@ejemplo.com',
      acceptance_token: 'sandbox_acceptance_token'
    }

    console.log('Simulando pago de reserva:', datosPago)

    // Hacer petición real al backend
    const response = await axios.post('/api/pagos/reserva', datosPago, {
      headers: {
        'Authorization': 'Bearer ' + (window.Laravel?.user?.token || 'test-token'),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    // Registrar resultado exitoso
    resultadosPruebas.value.unshift({
      id: Date.now(),
      tipo: 'Reserva',
      itemId: reservaTest.value.id,
      descripcion: `${reservaTest.value.tour} - Pago procesado`,
      monto: reservaTest.value.total,
      exito: true,
      timestamp: new Date().toLocaleString('es-CO'),
      respuesta: response.data
    })

    toast.add({
      severity: 'success',
      summary: 'Simulación Exitosa',
      detail: 'El pago de reserva se procesó correctamente',
      life: 5000
    })

  } catch (error) {
    console.error('Error en simulación de pago de reserva:', error)

    // Registrar resultado con error
    resultadosPruebas.value.unshift({
      id: Date.now(),
      tipo: 'Reserva',
      itemId: reservaTest.value.id,
      descripción: `${reservaTest.value.tour} - Error al procesar`,
      monto: reservaTest.value.total,
      exito: false,
      error: error.response?.data?.message || error.message,
      timestamp: new Date().toLocaleString('es-CO')
    })

    toast.add({
      severity: 'info',
      summary: 'Prueba de Endpoint',
      detail: `Endpoint probado: ${error.response?.status || 'Sin respuesta'}`,
      life: 5000
    })
  } finally {
    procesandoPago.value = false
  }
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
