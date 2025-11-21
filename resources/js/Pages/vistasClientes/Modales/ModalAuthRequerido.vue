<script setup>
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUserPlus, faXmark, faSignInAlt, faLock } from '@fortawesome/free-solid-svg-icons'
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
      <h3 class="text-lg font-bold text-blue-700 text-center jusfify-center w-full">
            Autenticación Requerida
      </h3>
    </template>

    <div class="text-center py-6">
      <!-- Icono -->
      <div class="mb-4">
        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
          <FontAwesomeIcon :icon="faLock" class="h-8 text-blue-600" />
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

      <!-- Botones -->
      <div class="space-y-3">
       <button
            type="button"
            class="bg-blue-500 hover:bg-blue-600 text-white border border-blue-600 w-full py-3 px-6 justify-center rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="irARegistro">
            <FontAwesomeIcon :icon="faUserPlus" class="h-4 text-white" />
            Crear Cuenta Nueva
        </button>
        <div class="text-sm text-gray-500">
          ¿Ya tienes cuenta?
        </div>
        <button
            type="button"
            class="bg-red-500 hover:bg-red-600 text-white border border-red-600 w-full py-3 px-6 justify-center rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="irALogin">
            <FontAwesomeIcon :icon="faSignInAlt" class="h-4 text-white" />
            Iniciar Sesión
        </button>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-center items-center w-full">
        <button
            type="button"
            class="bg-blue-500 hover:bg-blue-600 text-white border border-blue-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 mx-auto"
            @click="cerrarModal">
            <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />
            Cerrar
        </button>
      </div>
    </template>
  </Dialog>
</template>
