<script setup>
import { ref, computed, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faXmark, faCheck, faClipboardList } from '@fortawesome/free-solid-svg-icons'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// Importar componentes modulares
import ResumenTour from './ModalReservaTour/ResumenTour.vue'
import FormularioDatosPersonales from './ModalReservaTour/FormularioDatosPersonales.vue'
import SelectorCupos from './ModalReservaTour/SelectorCupos.vue'

// Inicializar toast
const toast = useToast()

// Props del componente
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  tourSeleccionado: {
    type: Object,
    default: null
  },
  user: {
    type: Object,
    default: null
  }
})

// Emits para comunicación con el componente padre
const emit = defineEmits(['update:visible', 'confirmar-reserva', 'actualizar-cupos', 'refrescar-tour'])

// Referencias a componentes hijos
const formularioDatosRef = ref(null)

// Estado local del formulario
const reservaForm = ref({
  correo: '',
  nombres: '',
  tipo_documento: 'DUI', // Usar ENUM directamente
  numero_identificacion: '',
  fecha_nacimiento: '',
  genero: '', // Vacío por defecto
  direccion: '',
  telefono: '',
  cupos_adultos: 1,
  cupos_menores: 0
})

// Estado para el loading del botón
const isConfirmingReserva = ref(false)

// Estado para controlar si hay datos precargados del cliente
const tieneClienteExistente = ref(false)

// Función para cargar datos del cliente existente
const cargarDatosCliente = async () => {
  if (!props.user) return null

  // Verificar primero si el usuario tiene datos usando la API de verificación
  try {
    const verificacionResponse = await fetch('/api/verificar-datos-cliente', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })

    if (verificacionResponse.ok) {
      const verificacion = await verificacionResponse.json()

      // Si no tiene datos completos, no hacer la segunda petición
      if (!verificacion.tiene_datos_completos) {
        return null
      }

      // Si tiene datos completos, obtenerlos
      const response = await fetch('/api/cliente-datos', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      })

      if (response.ok) {
        const data = await response.json()
        return data.cliente || null
      }
    }

    return null
  } catch (error) {
    // Solo logear errores de red/conexión reales
    console.error('[ModalReservaTour] Error de conexión al cargar datos del cliente:', error)
    return null
  }
}

// Función para resetear el formulario
const resetFormularioReserva = async () => {
  const loggedInUser = props.user
  let nombreCompleto = ''
  let correo = ''

  if (loggedInUser) {
    correo = loggedInUser.email || ''
    nombreCompleto = loggedInUser.name || ''
  }

  // Inicializar con datos básicos del usuario
  reservaForm.value = {
    correo: correo,
    nombres: nombreCompleto,
    tipo_documento: 'DUI',
    numero_identificacion: '',
    fecha_nacimiento: '',
    genero: '',
    direccion: '',
    telefono: '',
    cupos_adultos: 1,
    cupos_menores: 0,
  }

  // Resetear estado de cliente existente
  tieneClienteExistente.value = false

  // Cargar datos existentes del cliente si está logueado
  if (loggedInUser) {
    const clienteExistente = await cargarDatosCliente()

    if (clienteExistente) {
      // Marcar que hay datos precargados
      tieneClienteExistente.value = true

      // Formatear fecha de nacimiento para el Calendar
      let fechaNacimientoFormateada = ''
      if (clienteExistente.fecha_nacimiento) {
        try {
          fechaNacimientoFormateada = new Date(clienteExistente.fecha_nacimiento)
        } catch (error) {
          // Ignorar error de formato de fecha
        }
      }

      // Actualizar el formulario con los datos existentes
      reservaForm.value = {
        ...reservaForm.value,
        tipo_documento: clienteExistente.tipo_documento || 'DUI',
        numero_identificacion: clienteExistente.numero_identificacion || '',
        fecha_nacimiento: fechaNacimientoFormateada,
        genero: clienteExistente.genero || '',
        direccion: clienteExistente.direccion || '',
        telefono: clienteExistente.telefono || '',
      }
    }
  }
}

// Función para cerrar el modal
const cerrarModal = () => {
  emit('update:visible', false)
}

// Función para manejar toast desde componentes hijos
const manejarToast = (toastConfig) => {
  toast.add(toastConfig)
}

// Función para confirmar la reserva
const confirmarReserva = async () => {
  // Evitar múltiples envíos si ya se está procesando
  if (isConfirmingReserva.value) return

  // Validar formulario usando el componente hijo
  if (formularioDatosRef.value?.validateForm) {
    if (!formularioDatosRef.value.validateForm()) {
      return
    }
  }

  if (reservaForm.value.cupos_adultos <= 0 && reservaForm.value.cupos_menores <= 0) {
    toast.add({
      severity: 'error',
      summary: 'Error en la reserva',
      detail: 'Debe seleccionar al menos un cupo.',
      life: 4000
    })
    return
  }

  // Activar loading
  isConfirmingReserva.value = true

  try {
    const response = await axios.post('/reservas/tour', {
      tour_id: props.tourSeleccionado.id,
      cliente_data: {
        correo: reservaForm.value.correo,
        nombres: reservaForm.value.nombres,
        tipo_documento: reservaForm.value.tipo_documento,
        numero_identificacion: reservaForm.value.numero_identificacion,
        fecha_nacimiento: reservaForm.value.fecha_nacimiento,
        genero: reservaForm.value.genero?.toUpperCase() || '',
        direccion: reservaForm.value.direccion,
        telefono: reservaForm.value.telefono
      },
      cupos_adultos: reservaForm.value.cupos_adultos,
      cupos_menores: reservaForm.value.cupos_menores,
      precio_total: precios.value.total
    })

    // Emitir cupos actualizados si están disponibles en la respuesta
    if (response.data?.data?.cupos_disponibles_actualizados !== undefined) {
      emit('actualizar-cupos', {
        tourId: props.tourSeleccionado.id,
        cuposDisponibles: response.data.data.cupos_disponibles_actualizados
      })
    }

    // También emitir para refrescar el tour completo como respaldo
    emit('refrescar-tour', props.tourSeleccionado.id)

    emit('confirmar-reserva', response.data)
    cerrarModal()
  } catch (error) {
    console.error('Error al confirmar reserva:', error)
    if (error.response?.status === 419) {
      toast.add({
        severity: 'error',
        summary: 'Sesión expirada',
        detail: 'Su sesión ha expirado. Por favor, recargue la página.',
        life: 5000
      })
      window.location.reload()
    } else if (error.response?.status === 422) {
      // Error de validación - mostrar detalles específicos
      const errors = error.response?.data?.errors || {}
      const errorMessages = Object.values(errors).flat()
      if (errorMessages.length > 0) {
        toast.add({
          severity: 'error',
          summary: 'Errores de validación',
          detail: errorMessages.join(', '),
          life: 5000
        })
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error de validación',
          detail: error.response?.data?.message || 'Datos inválidos',
          life: 4000
        })
      }
    } else if (error.response?.status === 403) {
      toast.add({
        severity: 'error',
        summary: 'Acceso denegado',
        detail: 'No tiene permisos para realizar esta acción. Debe tener rol de cliente.',
        life: 4000
      })
    } else if (error.response?.status === 401) {
      toast.add({
        severity: 'error',
        summary: 'Sesión requerida',
        detail: 'Debe iniciar sesión para realizar una reserva.',
        life: 4000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error al procesar la reserva',
        detail: error.response?.data?.message || 'Inténtelo de nuevo.',
        life: 4000
      })
    }
  } finally {
    // Desactivar loading siempre al final
    isConfirmingReserva.value = false
  }
}

// Computed para calcular precios
const precios = computed(() => {
  if (!props.tourSeleccionado) {
    return {
      total: 0
    }
  }

  const precioTour = props.tourSeleccionado.precio || props.tourSeleccionado.precio_adulto || 0
  const totalCupos = reservaForm.value.cupos_adultos + reservaForm.value.cupos_menores
  const total = precioTour * totalCupos

  return {
    total
  }
})

// Watch para resetear el formulario cuando se abre el modal
watch(() => props.visible, async (newValue) => {
  if (newValue) {
    await resetFormularioReserva()
  }
})
</script>

<template>
  <!-- Toast para notificaciones dentro del modal -->
  <Toast />

  <Dialog
    :visible="visible"
    @update:visible="emit('update:visible', $event)"
    modal
    :closable="false"
    class="w-[95vw] sm:w-[90vw] md:w-[85vw] lg:w-[80vw] xl:max-w-4xl mx-2 sm:mx-4"
    :draggable="false"
  >
    <template #header>
      <h3 class="text-sm sm:text-base lg:text-lg font-bold text-red-700 flex items-center text-center justify-center w-full">
        <span class="mr-1 sm:mr-2">
            <FontAwesomeIcon :icon="faClipboardList" class="h-5 text-blue-500" />
        </span>
        <span class="text-blue-500">Reservando Tour</span>
      </h3>
    </template>

    <div v-if="tourSeleccionado" class="space-y-3 sm:space-y-4 lg:space-y-6 text-xs sm:text-sm text-gray-700">
      <!-- Resumen del tour usando componente modular -->
      <ResumenTour :tour-seleccionado="tourSeleccionado" />

      <!-- Formulario de datos personales usando componente modular -->
      <FormularioDatosPersonales
        ref="formularioDatosRef"
        v-model:formulario="reservaForm"
        :tiene-cliente-existente="tieneClienteExistente"
        :user="user"
        @mostrar-toast="manejarToast"
      />

      <!-- Selector de cupos usando componente modular -->
      <SelectorCupos
        v-model:cupos-adultos="reservaForm.cupos_adultos"
        v-model:cupos-menores="reservaForm.cupos_menores"
        :tour-seleccionado="tourSeleccionado"
        @mostrar-toast="manejarToast"
      />
    </div>

    <template #footer>
      <div class="flex justify-center gap-4 w-full mt-6">
        <button
          @click="confirmarReserva"
          type="button"
          :disabled="isConfirmingReserva"
          class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <FontAwesomeIcon 
            v-if="!isConfirmingReserva"
            :icon="faCheck" 
            class="h-5 text-white" 
          />
          <div 
            v-else
            class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
          ></div>
          {{ isConfirmingReserva ? 'Procesando...' : 'Confirmar' }}
        </button>
        <button
          @click="cerrarModal"
          type="button"
          class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
        >
          <FontAwesomeIcon :icon="faXmark" class="h-5" />
          Cancelar
        </button>
      </div>
    </template>
  </Dialog>
</template>
