<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUser, faXmark, faSave, faIdCard, faCalendarAlt, faMapMarkerAlt, faPhone, faVenusMars } from '@fortawesome/free-solid-svg-icons'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import axios from 'axios'

// Props del componente
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

// Emits para comunicación con el componente padre
const emit = defineEmits(['update:visible', 'cliente-guardado'])

const page = usePage()
const toast = useToast()

// Estados reactivos
const loading = ref(false)
const tiposDocumento = ref([])
const loadingTipos = ref(true)

// Datos del usuario actual
const usuario = computed(() => page.props.auth?.user)

// Formulario de datos del cliente
const formData = ref({
  numero_identificacion: '',
  fecha_nacimiento: null,
  genero: '',
  direccion: '',
  telefono: '',
  tipo_documento_id: null
})

// Opciones para el select de género (deben coincidir con el enum de la BD)
const opcionesGenero = [
  { label: 'Masculino', value: 'MASCULINO' },
  { label: 'Femenino', value: 'FEMENINO' }
]

// Validaciones
const errores = ref({})

// Cargar tipos de documento al montar el componente
onMounted(async () => {
  await cargarTiposDocumento()
})

// Cargar tipos de documento desde la API
const cargarTiposDocumento = async () => {
  try {
    loadingTipos.value = true
    const response = await axios.get('/api/tipo-documentos')

    // La API devuelve los tipos en response.data.tipos
    const tipos = response.data.tipos || response.data.data || response.data || []

    tiposDocumento.value = tipos.map(tipo => ({
      nombre: tipo.nombre,
      id: tipo.id
    }))

    // Verificar que tenemos datos válidos
    if (tiposDocumento.value.length === 0) {
      console.warn('No se encontraron tipos de documento')
    }

    // Seleccionar el primer tipo de documento por defecto si existe
    if (tiposDocumento.value.length > 0) {
      formData.value.tipo_documento_id = tiposDocumento.value[0].id
    }

  } catch (error) {
    console.error('Error al cargar tipos de documento:', error)
    console.error('Response data:', error.response?.data)

    // Fallback con tipos comunes si no se pueden cargar
    tiposDocumento.value = [
      { id: 1, nombre: 'DUI' },
      { id: 2, nombre: 'CÉDULA' },
      { id: 3, nombre: 'PASAPORTE' }
    ]

    let errorMessage = 'No se pudieron cargar los tipos de documento. Usando valores por defecto.'
    if (error.response?.status === 401) {
      errorMessage = 'No estás autorizado para acceder a esta información'
    } else if (error.response?.status === 404) {
      errorMessage = 'Servicio no encontrado'
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    })
  } finally {
    loadingTipos.value = false
  }
}

// Función para cerrar el modal
const cerrarModal = () => {
  emit('update:visible', false)
  limpiarFormulario()
}

// Limpiar formulario
const limpiarFormulario = () => {
  formData.value = {
    numero_identificacion: '',
    fecha_nacimiento: null,
    genero: '',
    direccion: '',
    telefono: '',
    tipo_documento_id: tiposDocumento.value.length > 0 ? tiposDocumento.value[0].id : null
  }
  errores.value = {}
}

// Validar formulario
const validarFormulario = () => {
  errores.value = {}

  if (!formData.value.numero_identificacion.trim()) {
    errores.value.numero_identificacion = 'El número de identificación es requerido'
  }

  if (!formData.value.fecha_nacimiento) {
    errores.value.fecha_nacimiento = 'La fecha de nacimiento es requerida'
  }

  if (!formData.value.genero) {
    errores.value.genero = 'El género es requerido'
  }

  if (!formData.value.direccion.trim()) {
    errores.value.direccion = 'La dirección es requerida'
  }

  if (!formData.value.telefono.trim()) {
    errores.value.telefono = 'El teléfono es requerido'
  }

  if (!formData.value.tipo_documento_id) {
    errores.value.tipo_documento_id = 'El tipo de documento es requerido'
  }

  return Object.keys(errores.value).length === 0
}

// Guardar información del cliente
const guardarCliente = async () => {
  if (!validarFormulario()) {
    toast.add({
      severity: 'warn',
      summary: 'Formulario incompleto',
      detail: 'Por favor completa todos los campos requeridos',
      life: 3000
    })
    return
  }

  try {
    loading.value = true

    const response = await axios.post('/api/registro-cliente', formData.value)

    toast.add({
      severity: 'success',
      summary: 'Información guardada',
      detail: 'Tus datos han sido guardados correctamente',
      life: 3000
    })

    // Emitir evento con los datos del cliente guardado
    emit('cliente-guardado', response.data.data || response.data)

    // Cerrar modal
    cerrarModal()

  } catch (error) {
    console.error('Error al guardar cliente:', error)

    if (error.response?.data?.errors) {
      errores.value = error.response.data.errors
    }

    toast.add({
      severity: 'error',
      summary: 'Error al guardar',
      detail: error.response?.data?.message || 'No se pudo guardar la información',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

// Formatear fecha para mostrar
const formatearFecha = (fecha) => {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-ES')
}
</script>

<template>
  <Dialog
    :visible="visible"
    @update:visible="emit('update:visible', $event)"
    modal
    :closable="false"
    class="max-w-2xl w-full mx-4"
    :draggable="false"
  >
    <template #header>
      <h3 class="text-xl font-bold text-red-700 flex items-center gap-2">
        <FontAwesomeIcon :icon="faUser" />
        Completar información de cliente
      </h3>
    </template>

    <div class="py-4">
      <!-- Información del usuario actual -->
      <div class="bg-blue-50 rounded-lg p-4 mb-6">
        <h4 class="font-semibold text-blue-800 mb-2">Información de tu cuenta:</h4>
        <div class="text-sm text-blue-700">
          <p><strong>Nombre:</strong> {{ usuario?.name }}</p>
          <p><strong>Email:</strong> {{ usuario?.email }}</p>
        </div>
      </div>

      <!-- Formulario de datos del cliente -->
      <form @submit.prevent="guardarCliente" class="space-y-6">
        <!-- Tipo de documento y número -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <FontAwesomeIcon :icon="faIdCard" class="mr-2" />
              Tipo de documento *
            </label>
            <Select
              v-model="formData.tipo_documento_id"
              :options="tiposDocumento"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Selecciona el tipo"
              :loading="loadingTipos"
              class="w-full"
            />
            <small v-if="errores.tipo_documento_id" class="p-error">
              {{ errores.tipo_documento_id }}
            </small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Número de identificación *
            </label>
            <InputText
              v-model="formData.numero_identificacion"
              placeholder="Ingresa tu número de identificación"
              class="w-full"
              :class="{ 'p-invalid': errores.numero_identificacion }"
            />
            <small v-if="errores.numero_identificacion" class="p-error">
              {{ errores.numero_identificacion }}
            </small>
          </div>
        </div>

        <!-- Fecha de nacimiento y género -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <FontAwesomeIcon :icon="faCalendarAlt" class="mr-2" />
              Fecha de nacimiento *
            </label>
            <DatePicker
              v-model="formData.fecha_nacimiento"
              placeholder="Selecciona tu fecha de nacimiento"
              dateFormat="dd/mm/yy"
              :showIcon="true"
              :maxDate="new Date()"
              class="w-full"
              :class="{ 'p-invalid': errores.fecha_nacimiento }"
            />
            <small v-if="errores.fecha_nacimiento" class="p-error">
              {{ errores.fecha_nacimiento }}
            </small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <FontAwesomeIcon :icon="faVenusMars" class="mr-2" />
              Género *
            </label>
            <Select
              v-model="formData.genero"
              :options="opcionesGenero"
              optionLabel="label"
              optionValue="value"
              placeholder="Selecciona tu género"
              class="w-full"
            />
            <small v-if="errores.genero" class="p-error">
              {{ errores.genero }}
            </small>
          </div>
        </div>

        <!-- Dirección -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <FontAwesomeIcon :icon="faMapMarkerAlt" class="mr-2" />
            Dirección *
          </label>
          <Textarea
            v-model="formData.direccion"
            placeholder="Ingresa tu dirección completa"
            rows="3"
            class="w-full"
            :class="{ 'p-invalid': errores.direccion }"
          />
          <small v-if="errores.direccion" class="p-error">
            {{ errores.direccion }}
          </small>
        </div>

        <!-- Teléfono -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <FontAwesomeIcon :icon="faPhone" class="mr-2" />
            Teléfono *
          </label>
          <InputText
            v-model="formData.telefono"
            placeholder="Ingresa tu número de teléfono"
            class="w-full"
            :class="{ 'p-invalid': errores.telefono }"
          />
          <small v-if="errores.telefono" class="p-error">
            {{ errores.telefono }}
          </small>
        </div>

        <!-- Nota informativa -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <p class="text-sm text-yellow-800">
            <strong>Nota:</strong> Esta información es necesaria para procesar tu compra y generar la factura correspondiente.
          </p>
        </div>
      </form>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          @click="cerrarModal"
          :disabled="loading"
        >
          <FontAwesomeIcon :icon="faXmark" />
          Cancelar
        </button>

        <button
          type="button"
          class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50"
          @click="guardarCliente"
          :disabled="loading"
        >
          <FontAwesomeIcon :icon="faSave" />
          {{ loading ? 'Guardando...' : 'Guardar y Continuar' }}
        </button>
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.p-invalid {
  border-color: #dc3545 !important;
}

.p-error {
  color: #dc3545;
  font-size: 0.875rem;
}
</style>

<style>
/* Asegurar que el toast esté por encima de todos los modales */
.p-toast {
  z-index: 999999 !important;
  position: fixed !important;
}

/* Estilos específicos para el toast de éxito */
.p-toast .p-toast-message-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
  border: 1px solid #059669 !important;
  color: white !important;
}

.p-toast .p-toast-message-success .p-toast-message-icon,
.p-toast .p-toast-message-success .p-toast-icon-close {
  color: white !important;
}

/* Estilos específicos para el toast de error */
.p-toast .p-toast-message-error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
  border: 1px solid #dc2626 !important;
  color: white !important;
}

.p-toast .p-toast-message-error .p-toast-message-icon,
.p-toast .p-toast-message-error .p-toast-icon-close {
  color: white !important;
}

/* Estilos específicos para el toast de advertencia */
.p-toast .p-toast-message-warn {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
  border: 1px solid #d97706 !important;
  color: white !important;
}

.p-toast .p-toast-message-warn .p-toast-message-icon,
.p-toast .p-toast-message-warn .p-toast-icon-close {
  color: white !important;
}
</style>
