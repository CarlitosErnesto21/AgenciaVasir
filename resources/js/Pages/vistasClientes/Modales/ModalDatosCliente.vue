<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUser, faXmark, faSave, faIdCard, faCalendarAlt, faMapMarkerAlt, faPhone, faVenusMars, faSpinner } from '@fortawesome/free-solid-svg-icons'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'
import axios from 'axios'

// Props del componente
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  tieneClienteExistente: {
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

// Estado de validación del teléfono
const telefonoValidation = ref({
  isValid: false,
  country: null,
  formattedNumber: '',
  mensaje: ''
})

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

// Función de validación del teléfono
const onValidate = async (phoneObject) => {
  try {
    if (phoneObject && typeof phoneObject === 'object') {
      telefonoValidation.value.isValid = phoneObject.valid === true
      telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode }
      telefonoValidation.value.formattedNumber = phoneObject.formatted || ''

      // CLAVE: Actualizar el modelo inmediatamente como hace EmpleadoController
      if (phoneObject.valid === true && phoneObject.formatted) {
        formData.value.telefono = phoneObject.formatted
      }

      // Si los datos están precargados, no validar duplicados (ya son del mismo cliente)
      if (props.tieneClienteExistente) {
        if (phoneObject.valid === true) {
          telefonoValidation.value.mensaje = 'Número válido (guardado previamente)'
        } else if (formData.value.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else {
          telefonoValidation.value.mensaje = ''
        }
      } else {
        // Solo validar duplicados para nuevos clientes
        if (formData.value.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else if (phoneObject.valid === true) {
          // Ahora validar con el teléfono ya actualizado en el modelo
          await validarTelefonoUnico(formData.value.telefono)
        } else {
          telefonoValidation.value.mensaje = ''
        }
      }
    }
  } catch (error) {
    console.error('[ModalDatosCliente] Error en validación:', error)
    telefonoValidation.value.mensaje = 'Error en validación'
  }
}

// Validar que el teléfono no esté duplicado
const validarTelefonoUnico = async (telefono) => {
  if (!telefono || telefono.length < 8) return

  try {
    const response = await axios.post('/api/clientes/validar-telefono', {
      telefono: telefono
    })

    if (response.data.disponible) {
      telefonoValidation.value.mensaje = 'Número válido para ' + telefonoValidation.value.country?.name
    } else {
      telefonoValidation.value.isValid = false
      telefonoValidation.value.mensaje = response.data.message || 'Este teléfono ya está registrado'
    }
  } catch (error) {
    console.error('[ModalDatosCliente] Error validando teléfono:', error)
    if (error.response?.status === 401) {
      telefonoValidation.value.mensaje = 'Error de autenticación'
    } else {
      telefonoValidation.value.mensaje = 'Error al validar teléfono'
    }
  }
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

  if (!formData.value.telefono) {
    errores.value.telefono = 'El número de teléfono es requerido'
  } else if (telefonoValidation.value.isValid === false) {
    errores.value.telefono = telefonoValidation.value.mensaje || 'Por favor, ingrese un número de teléfono válido'
  }

  if (!formData.value.tipo_documento_id) {
    errores.value.tipo_documento_id = 'El tipo de documento es requerido'
  }

  return Object.keys(errores.value).length === 0
}

// Guardar información del cliente
const guardarCliente = async () => {
  if (!validarFormulario()) {
    // Si el único error es el teléfono duplicado, mostrar mensaje específico
    const erroresKeys = Object.keys(errores.value)
    if (erroresKeys.length === 1 && erroresKeys[0] === 'telefono' && telefonoValidation.value.isValid === false) {
      toast.add({
        severity: 'warn',
        summary: 'Teléfono ya registrado',
        detail: telefonoValidation.value.mensaje || 'Este número de teléfono ya está registrado. Por favor, use un número diferente.',
        life: 5000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Formulario incompleto',
        detail: 'Por favor completa todos los campos requeridos',
        life: 4000
      })
    }
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
    console.error('[ModalDatosCliente] Error al guardar cliente:', error)
    console.error('[ModalDatosCliente] Detalles del error:', error.response?.data)

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
      <form @submit.prevent="guardarCliente" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Número de Identificación</label>
            <input
              v-model="formData.numero_identificacion"
              type="text"
              required
              maxlength="25"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errores.numero_identificacion }"
              placeholder="Ingrese su DUI o documento"
            />
            <small v-if="errores.numero_identificacion" class="text-red-500 text-xs">
              {{ errores.numero_identificacion }}
            </small>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento</label>
            <select
              v-model="formData.tipo_documento_id"
              required
              :disabled="loadingTipos"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
              :class="{ 'border-red-500': errores.tipo_documento_id }"
            >
              <option v-if="loadingTipos" value="" disabled>Cargando tipos...</option>
              <option v-else-if="tiposDocumento.length === 0" value="" disabled>No hay tipos disponibles</option>
              <template v-else>
                <option value="" disabled>Seleccione un tipo</option>
                <option v-for="tipo in tiposDocumento" :key="tipo.id" :value="tipo.id">
                  {{ tipo.nombre }}
                </option>
              </template>
            </select>
            <small v-if="errores.tipo_documento_id" class="text-red-500 text-xs">
              {{ errores.tipo_documento_id }}
            </small>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
            <Calendar
              v-model="formData.fecha_nacimiento"
              :maxDate="new Date()"
              date-format="dd/mm/yy"
              placeholder="Seleccionar fecha de nacimiento"
              showIcon
              yearNavigator
              yearRange="1920:2010"
              class="w-full"
              :inputClass="`w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${errores.fecha_nacimiento ? 'border-red-500' : ''}`"
            />
            <small v-if="errores.fecha_nacimiento" class="text-red-500 text-xs">
              {{ errores.fecha_nacimiento }}
            </small>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
            <select
              v-model="formData.genero"
              required
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errores.genero }"
            >
              <option value="">Seleccione</option>
              <option value="MASCULINO">Masculino</option>
              <option value="FEMENINO">Femenino</option>
            </select>
            <small v-if="errores.genero" class="text-red-500 text-xs">
              {{ errores.genero }}
            </small>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
            <VueTelInput
              v-model="formData.telefono"
              defaultCountry="SV"
              :preferredCountries="['SV', 'GT', 'HN', 'CR', 'NI', 'PA', 'BZ']"
              :validCharactersOnly="true"
              :dropdownOptions="{
                showDialCodeInSelection: true,
                showFlags: true,
                showSearchBox: true,
                showDialCodeInList: true
              }"
              :inputOptions="{
                placeholder: 'Número de teléfono'
              }"
              mode="international"
              class="w-full border border-gray-300 rounded-lg"
              @validate="onValidate"
            />
            <!-- Mensaje de validación -->
            <p
              v-if="telefonoValidation.mensaje"
              :class="[
                'text-xs mt-1 flex items-center',
                telefonoValidation.isValid ? 'text-green-600' : 'text-red-600'
              ]"
            >
              <span class="mr-1">
                {{ telefonoValidation.isValid ? '✓' : '⚠️' }}
              </span>
              {{ telefonoValidation.mensaje }}
            </p>
            <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
            <small v-if="errores.telefono && !telefonoValidation.mensaje" class="text-red-500 text-xs">
              {{ errores.telefono }}
            </small>
          </div>
          <div class="sm:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
            <input
              v-model="formData.direccion"
              type="text"
              required
              maxlength="200"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errores.direccion }"
              placeholder="Dirección completa"
            />
            <small v-if="errores.direccion" class="text-red-500 text-xs">
              {{ errores.direccion }}
            </small>
          </div>
        </div>

        <!-- Nota informativa -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
          <p class="text-sm text-blue-800">
            <strong>Nota:</strong> Esta información es necesaria para procesar tu compra.
          </p>
        </div>
      </form>
    </div>

        <template #footer>
      <div class="flex justify-center gap-4 w-full mt-6">
        <button
          type="button"
          class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          @click="guardarCliente"
          :disabled="loading"
        >
          <FontAwesomeIcon
            :icon="loading ? faSpinner : faSave"
            :class="['h-5', { 'animate-spin': loading }]"
          />
          {{ loading ? 'Guardando...' : 'Guardar y Continuar' }}
        </button>

        <button
          type="button"
          class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          @click="cerrarModal"
          :disabled="loading"
        >
          <FontAwesomeIcon :icon="faXmark" class="h-5" />
          Cancelar
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

/* Estilos específicos para el toast de advertencia - más suave */
.p-toast .p-toast-message-warn {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
  border: 1px solid #f59e0b !important;
  color: #92400e !important;
}

.p-toast .p-toast-message-warn .p-toast-message-icon,
.p-toast .p-toast-message-warn .p-toast-icon-close {
  color: #92400e !important;
}
</style>
