<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUserPlus, faXmark, faSignInAlt } from '@fortawesome/free-solid-svg-icons'
import Dialog from 'primevue/dialog'

// Props del componente
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  tourInfo: {
    type: Object,
    default: null
  },
  productoInfo: {
    type: Object,
    default: null
  }
})

// Emits para comunicación con el componente padre
const emit = defineEmits(['update:visible'])

// Función para cerrar el modal
const cerrarModal = () => {
  emit('update:visible', false)
}

// Función para ir al login
const irALogin = () => {
  router.visit('/login')
}

// Función para ir al registro
const irARegistro = () => {
  router.visit('/register')
}
</script>

<template>
  <Dialog
    :visible="visible"
    @update:visible="emit('update:visible', $event)"
    modal
    :closable="false"
    class="max-w-md w-full mx-4"
    :draggable="false"
  >
    <template #header>
      <h3 class="text-lg font-bold text-red-700">
        <span v-if="tourInfo && tourInfo.tipo === 'hotel'">🔐 Inicia Sesión para Reservar Hotel</span>
        <span v-else-if="tourInfo">🔐 Inicia Sesión para Reservar</span>
        <span v-else-if="productoInfo">🔐 Inicia Sesión para Comprar</span>
        <span v-else>🔐 Inicia Sesión para Continuar</span>
      </h3>
    </template>

    <div class="text-center py-6">
      <!-- Icono -->
      <div class="mb-4">
        <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>
      </div>

      <!-- Mensaje -->
      <h3 class="text-xl font-semibold text-gray-900 mb-2">
        ¡Inicia sesión para continuar!
      </h3>

      <p class="text-gray-600 mb-2">
        <span v-if="tourInfo && tourInfo.tipo === 'hotel'">Para realizar una reserva de hotel necesitas tener una cuenta en nuestra plataforma.</span>
        <span v-else-if="tourInfo">Para realizar una reserva necesitas tener una cuenta en nuestra plataforma.</span>
        <span v-else-if="productoInfo">Para realizar una compra necesitas tener una cuenta en nuestra plataforma.</span>
        <span v-else>Para continuar necesitas tener una cuenta en nuestra plataforma.</span>
      </p>

      <div v-if="tourInfo && tourInfo.tipo === 'hotel'" class="bg-orange-50 rounded-lg p-3 mb-6 text-left">
        <p class="text-sm text-orange-800">
          <strong>Hotel seleccionado:</strong> {{ tourInfo.nombre }}
        </p>
        <p class="text-xs text-orange-600 mt-1">
          Después del login podrás regresar a realizar tu reserva.
        </p>
      </div>

      <div v-else-if="tourInfo" class="bg-blue-50 rounded-lg p-3 mb-6 text-left">
        <p class="text-sm text-blue-800">
          <strong>Tour seleccionado:</strong> {{ tourInfo.nombre }}
        </p>
        <p class="text-xs text-blue-600 mt-1">
          Después del login podrás regresar a realizar tu reserva.
        </p>
      </div>

      <div v-if="productoInfo" class="bg-green-50 rounded-lg p-3 mb-6 text-left">
        <p class="text-sm text-green-800">
          <strong>Producto seleccionado:</strong> {{ productoInfo.nombre }}
        </p>
        <p class="text-xs text-green-600 mt-1">
          Después del login podrás regresar a realizar tu compra.
        </p>
      </div>

      <!-- Botones -->
      <div class="space-y-3">
        <button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white border border-red-600 w-full py-3 px-6 justify-center rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="irALogin">
            <FontAwesomeIcon :icon="faSignInAlt" class="h-4 text-white" />
            Iniciar Sesión
        </button>

        <div class="text-sm text-gray-500">
          ¿No tienes cuenta?
        </div>

        <button
            type="button"
            class="bg-white hover:bg-red-50 text-red-600 border border-red-600 w-full py-3 px-6 justify-center rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="irARegistro">
            <FontAwesomeIcon :icon="faUserPlus" class="h-4 text-red-600" />
            Crear Cuenta Nueva
        </button>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-center items-center w-full">
        <button
            type="button"
            class="bg-white hover:bg-red-50 text-red-600 border border-red-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 mx-auto"
            @click="cerrarModal">
            <FontAwesomeIcon :icon="faXmark" class="h-5 text-red-600" />
            Cerrar
        </button>
      </div>
    </template>
  </Dialog>
</template>
