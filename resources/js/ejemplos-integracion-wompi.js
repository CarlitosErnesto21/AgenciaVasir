// ====================================================================
// EJEMPLO DE INTEGRACIÓN EN EL CARRITO DE COMPRAS EXISTENTE
// ====================================================================

/*
1. AGREGAR IMPORTS al archivo CarritoCompras.vue:
*/

// Al inicio del <script setup>
import WompiPayButton from '@/Components/WompiPayButton.vue'
import { useWompiPayments } from '@/composables/useWompiPayments'

// Después de las otras declaraciones
const { formatAmount } = useWompiPayments()

/*
2. AGREGAR ESTADO REACTIVO para manejar el pago:
*/

const procesandoPago = ref(false)
const ventaCreada = ref(null)

/*
3. MODIFICAR LA FUNCIÓN procederAlCheckout:
*/

const procederAlCheckout = async () => {
  if (carritoStore.items.length === 0) return

  procesandoPago.value = true

  try {
    // Primero crear la venta en el backend
    const ventaData = {
      fecha: new Date().toISOString().split('T')[0],
      cliente_id: 1, // Reemplaza con el ID del cliente actual
      empleado_id: 1, // Reemplaza con el ID del empleado (si aplica)
      metodo_pago_id: 3, // ID del método "Tarjeta de Crédito" en tu tabla metodos_pagos
      productos: carritoStore.items.map(item => ({
        producto_id: item.id,
        cantidad: item.quantity,
        precio_unitario: item.precio
      }))
    }

    const response = await axios.post('/api/ventas', ventaData)

    if (response.data.venta) {
      ventaCreada.value = response.data.venta
      // El widget de pago se mostrará automáticamente
    }

  } catch (error) {
    console.error('Error creando venta:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo crear la venta',
      life: 5000
    })
  } finally {
    procesandoPago.value = false
  }
}

/*
4. AGREGAR HANDLERS para el pago:
*/

const handlePaymentSuccess = (result) => {
  // Limpiar carrito
  carritoStore.clearCart()

  // Mostrar mensaje de éxito
  toast.add({
    severity: 'success',
    summary: 'Compra Exitosa',
    detail: '¡Tu compra ha sido procesada correctamente!',
    life: 8000
  })

  // Redirigir o mostrar resumen de compra
  // router.visit('/mis-compras') o similar
  console.log('Compra exitosa:', result)
}

const handlePaymentError = (error) => {
  toast.add({
    severity: 'error',
    summary: 'Error en el Pago',
    detail: error.message || 'Ocurrió un error procesando el pago',
    life: 8000
  })
}

/*
5. REEMPLAZAR EL BOTÓN DE CHECKOUT en el template:
*/

// ANTES (botón original):
/*
<button
  @click="procederAlCheckout"
  class="w-full text-white py-3 px-4 rounded-md transition-colors flex items-center justify-center gap-2 font-medium btn-checkout"
>
  <FontAwesomeIcon :icon="faCreditCard" />
  Proceder al Checkout
</button>
*/

// DESPUÉS (con Wompi):
/*
<div v-if="!ventaCreada">
  <button
    @click="procederAlCheckout"
    :disabled="procesandoPago || carritoStore.items.length === 0"
    class="w-full text-white py-3 px-4 rounded-md transition-colors flex items-center justify-center gap-2 font-medium btn-checkout"
  >
    <span v-if="procesandoPago">
      <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      Creando Venta...
    </span>
    <span v-else>
      <FontAwesomeIcon :icon="faCreditCard" />
      Proceder al Checkout
    </span>
  </button>
</div>

<div v-else>
  <WompiPayButton
    :amount="ventaCreada.total"
    currency="COP"
    type="venta"
    :item-id="ventaCreada.id"
    :description="`Compra de ${carritoStore.items.length} producto(s)`"
    button-text="💳 Pagar con Tarjeta"
    class="w-full"
    @payment-success="handlePaymentSuccess"
    @payment-error="handlePaymentError"
  />

  <button
    @click="ventaCreada = null"
    class="w-full mt-2 text-gray-600 py-2 px-4 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
  >
    Volver al Carrito
  </button>
</div>
*/

/*
6. AGREGAR EL COMPONENTE en la sección de components (si usas Options API):
*/

// Si usas <script setup>, no necesitas esto
// Si usas Options API, agregar:
/*
import WompiPayButton from '@/Components/WompiPayButton.vue'

export default {
  components: {
    WompiPayButton
  }
}
*/

// ====================================================================
// EJEMPLO PARA INTEGRAR EN UNA PÁGINA DE DETALLE DE TOUR
// ====================================================================

/*
En DetalleTour.vue, puedes reemplazar la función reservarTour así:
*/

const reservarTour = async () => {
  // ... validaciones existentes ...

  try {
    // Crear la reserva
    const reservaData = {
      tour_id: tour.id,
      cliente_id: page.props.auth.user.id,
      fecha_reserva: new Date().toISOString().split('T')[0],
      cantidad_personas: 1, // o el valor que corresponda
      total: tour.precio
    }

    const response = await axios.post('/api/reservas', reservaData)

    if (response.data.reserva) {
      // Mostrar componente de pago
      mostrarPago.value = true
      reservaCreada.value = response.data.reserva
    }

  } catch (error) {
    // ... manejo de errores ...
  }
}

// Y en el template:
/*
<WompiPayButton
  v-if="reservaCreada"
  :amount="reservaCreada.total"
  currency="COP"
  type="reserva"
  :item-id="reservaCreada.id"
  :description="`Reserva para ${tour.nombre}`"
  button-text="🎫 Confirmar y Pagar"
  @payment-success="handleReservaSuccess"
  @payment-error="handlePaymentError"
/>
*/
