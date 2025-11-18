<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUser, faXmark, faSave, faIdCard, faCalendarAlt, faMapMarkerAlt, faPhone, faVenusMars, faSpinner } from '@fortawesome/free-solid-svg-icons'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
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
  tipo_documento: 'DUI'
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

// Estado de validación del documento
const documentoValidation = ref({
  isValid: true,
  mensaje: ''
})

// Ya no necesitamos cargar tipos de documento
onMounted(async () => {
  // Componente listo
})

// Ya no necesitamos cargar tipos de documento desde API

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
    tipo_documento: 'DUI'
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

// Validar que el documento no esté duplicado
const validarDocumentoUnico = async (numeroIdentificacion) => {
  if (!numeroIdentificacion || numeroIdentificacion.length < 3) return

  try {
    const response = await axios.post('/api/clientes/validar-documento', {
      numero_identificacion: numeroIdentificacion
    })

    if (response.data.disponible) {
      documentoValidation.value.mensaje = '✓ Número de identificación disponible'
      documentoValidation.value.isValid = true
    } else {
      documentoValidation.value.isValid = false
      documentoValidation.value.mensaje = response.data.message || 'Este número de identificación ya está registrado'
    }
  } catch (error) {
    console.error('[ModalDatosCliente] Error validando documento:', error)
    documentoValidation.value.isValid = false
    if (error.response?.status === 403) {
      documentoValidation.value.mensaje = 'No tienes permisos para validar este documento'
    } else if (error.response?.status === 401) {
      documentoValidation.value.mensaje = 'Error de autenticación'
    } else {
      documentoValidation.value.mensaje = 'Error al validar documento'
    }
  }
}

// Función para obtener fecha máxima de nacimiento (debe tener al menos 18 años)
const getFechaMaximaNacimiento = () => {
  const fechaMaxima = new Date()
  fechaMaxima.setFullYear(fechaMaxima.getFullYear() - 18)
  return fechaMaxima
}

// Función para validar edad mínima de 18 años
const validarEdadMinima = (fechaNacimiento) => {
  if (!fechaNacimiento) return { esValido: true, mensaje: '' }

  const hoy = new Date()
  const fechaNac = new Date(fechaNacimiento)
  const edad = hoy.getFullYear() - fechaNac.getFullYear()
  const mesNacimiento = fechaNac.getMonth()
  const diaNacimiento = fechaNac.getDate()
  const mesActual = hoy.getMonth()
  const diaActual = hoy.getDate()

  // Ajustar la edad si aún no ha cumplido años este año
  const edadReal = edad - ((mesActual < mesNacimiento || (mesActual === mesNacimiento && diaActual < diaNacimiento)) ? 1 : 0)

  if (edadReal < 18) {
    return {
      esValido: false,
      mensaje: `Debe ser mayor de edad (18 años). Edad actual: ${edadReal} años`
    }
  }

  return { esValido: true, mensaje: '' }
}

// Validar formulario antes de enviar
const validarFormulario = () => {
  // Limpiar errores previos de validación del formulario
  const erroresFormulario = {}

  // Validar número de identificación
  if (!formData.value.numero_identificacion) {
    erroresFormulario.numero_identificacion = 'El número de identificación es requerido'
  } else {
    // Validar formato en tiempo real y preservar error si existe
    validarNumeroIdentificacion()
    if (errores.value.numero_identificacion) {
      erroresFormulario.numero_identificacion = errores.value.numero_identificacion
    }
  }

  // Validar fecha de nacimiento
  if (!formData.value.fecha_nacimiento) {
    erroresFormulario.fecha_nacimiento = 'La fecha de nacimiento es requerida'
  } else {
    const validacionEdad = validarEdadMinima(formData.value.fecha_nacimiento)
    if (!validacionEdad.esValido) {
      erroresFormulario.fecha_nacimiento = validacionEdad.mensaje
    }
  }

  // Validar género
  if (!formData.value.genero) {
    erroresFormulario.genero = 'El género es requerido'
  }

  // Validar dirección
  if (!formData.value.direccion) {
    erroresFormulario.direccion = 'La dirección es requerida'
  }

  // Validar teléfono
  if (!formData.value.telefono) {
    erroresFormulario.telefono = 'El número de teléfono es requerido'
  } else if (telefonoValidation.value.isValid === false) {
    erroresFormulario.telefono = telefonoValidation.value.mensaje || 'Por favor, ingrese un número de teléfono válido'
  }

  // Validar documento duplicado
  if (!props.tieneClienteExistente && formData.value.numero_identificacion && !documentoValidation.value.isValid) {
    erroresFormulario.numero_identificacion = documentoValidation.value.mensaje || 'Este número de identificación no está disponible'
  }

  // Validar tipo de documento
  if (!formData.value.tipo_documento) {
    erroresFormulario.tipo_documento = 'El tipo de documento es requerido'
  }

  // Reemplazar errores completamente con solo los errores actuales
  errores.value = erroresFormulario

  // Verificar si hay errores
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

    // Si hay un mensaje específico del servidor, mostrarlo también como error de campo
    if (error.response?.data?.message && error.response?.data?.message.includes('formato')) {
      errores.value.numero_identificacion = error.response.data.message
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

// Función para formatear DUI automáticamente
const formatearDUI = (valor) => {
  // Solo permitir números (eliminar TODO lo que no sea dígito)
  const soloNumeros = valor.replace(/[^0-9]/g, '')

  // Limitar a 9 dígitos máximo
  const numerosLimitados = soloNumeros.substring(0, 9)

  // Agregar guión automáticamente después del 8vo dígito
  if (numerosLimitados.length > 8) {
    return numerosLimitados.substring(0, 8) + '-' + numerosLimitados.substring(8)
  }

  return numerosLimitados
}

// Función para formatear PASAPORTE automáticamente
const formatearPasaporte = (valor) => {
  // Solo permitir A-Z y 0-9, convertir a mayúsculas, máximo 9 caracteres
  return valor.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 9)
}

// Función para manejar entrada de texto según tipo de documento
const manejarEntradaDocumento = (event) => {
  const valor = event.target.value
  let valorFormateado = ''

  if (formData.value.tipo_documento === 'DUI') {
    valorFormateado = formatearDUI(valor)
    formData.value.numero_identificacion = valorFormateado
    // Actualizar el valor del input inmediatamente
    event.target.value = valorFormateado
  } else if (formData.value.tipo_documento === 'PASAPORTE') {
    valorFormateado = formatearPasaporte(valor)
    formData.value.numero_identificacion = valorFormateado
    // Actualizar el valor del input inmediatamente
    event.target.value = valorFormateado
  }

  // Validar después de formatear
  validarNumeroIdentificacion()

  // Validar duplicados si el formato es válido
  if (formData.value.numero_identificacion && formData.value.numero_identificacion.length >= 3) {
    const isValidFormat = (formData.value.tipo_documento === 'DUI' && /^\d{8}-\d{1}$/.test(formData.value.numero_identificacion)) ||
                         (formData.value.tipo_documento === 'PASAPORTE' && /^[A-Z0-9]{6,9}$/.test(formData.value.numero_identificacion))

    if (isValidFormat && !props.tieneClienteExistente) {
      validarDocumentoUnico(formData.value.numero_identificacion)
    } else {
      documentoValidation.value.mensaje = ''
      documentoValidation.value.isValid = true
    }
  }
}

// Validación en tiempo real del número de identificación
const validarNumeroIdentificacion = () => {
  if (!formData.value.numero_identificacion) {
    delete errores.value.numero_identificacion
    return
  }

  if (formData.value.tipo_documento === 'DUI') {
    const duiRegex = /^\d{8}-\d{1}$/
    if (!duiRegex.test(formData.value.numero_identificacion)) {
      errores.value.numero_identificacion = 'El DUI debe tener 9 dígitos (formato: 12345678-9)'
    } else {
      delete errores.value.numero_identificacion
    }
  } else if (formData.value.tipo_documento === 'PASAPORTE') {
    // Validar formato: solo A-Z y 0-9, entre 6 y 9 caracteres
    const pasaporteRegex = /^[A-Z0-9]{6,9}$/
    if (!pasaporteRegex.test(formData.value.numero_identificacion)) {
      errores.value.numero_identificacion = 'El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)'
    } else {
      delete errores.value.numero_identificacion
    }
  }
}

// Watchers para validación en tiempo real
watch(() => formData.value.numero_identificacion, (newValue) => {
  validarNumeroIdentificacion()

  // Validar duplicados si el formato es válido
  if (newValue && newValue.length >= 3) {
    const isValidFormat = (formData.value.tipo_documento === 'DUI' && /^\d{8}-\d{1}$/.test(newValue)) ||
                         (formData.value.tipo_documento === 'PASAPORTE' && /^[A-Z0-9]{6,9}$/.test(newValue))

    if (isValidFormat && !props.tieneClienteExistente) {
      validarDocumentoUnico(newValue)
    } else {
      documentoValidation.value.mensaje = ''
      documentoValidation.value.isValid = true
    }
  } else {
    documentoValidation.value.mensaje = ''
    documentoValidation.value.isValid = true
  }
})

watch(() => formData.value.tipo_documento, () => {
  // Limpiar número cuando cambia el tipo de documento
  formData.value.numero_identificacion = ''
  delete errores.value.numero_identificacion
  documentoValidation.value.mensaje = ''
  documentoValidation.value.isValid = true
})

// Watchers para limpiar errores cuando se completan los campos
watch(() => formData.value.fecha_nacimiento, (newValue) => {
  if (newValue) {
    const validacionEdad = validarEdadMinima(newValue)
    if (!validacionEdad.esValido) {
      errores.value.fecha_nacimiento = validacionEdad.mensaje
      toast.add({
        severity: 'error',
        summary: 'Edad insuficiente',
        detail: validacionEdad.mensaje,
        life: 4000
      })
    } else {
      delete errores.value.fecha_nacimiento
    }
  }
})

watch(() => formData.value.genero, (newValue) => {
  if (newValue && errores.value.genero) {
    delete errores.value.genero
  }
})

watch(() => formData.value.direccion, (newValue) => {
  if (newValue && newValue.trim() && errores.value.direccion) {
    delete errores.value.direccion
  }
})

watch(() => formData.value.telefono, (newValue) => {
  if (newValue && errores.value.telefono && telefonoValidation.value.isValid) {
    delete errores.value.telefono
  }
})
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
              :value="formData.numero_identificacion"
              @input="manejarEntradaDocumento"
              type="text"
              required
              :maxlength="formData.tipo_documento === 'DUI' ? 10 : 9"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errores.numero_identificacion }"
              :placeholder="formData.tipo_documento === 'DUI' ? 'Ingrese 9 dígitos (ej: 123456789)' : 'Ingrese su PASAPORTE (ej: A1B2C3D4)'"
            />
            <!-- Mensaje de validación en tiempo real del documento -->
            <p
              v-if="documentoValidation.mensaje && !props.tieneClienteExistente"
              :class="[
                'text-xs mt-1',
                documentoValidation.isValid ? 'text-green-600' : 'text-red-600'
              ]"
            >
              {{ documentoValidation.mensaje }}
            </p>
            <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
            <small v-if="errores.numero_identificacion && !documentoValidation.mensaje" class="text-red-500 text-xs">
              {{ errores.numero_identificacion }}
            </small>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento</label>
            <select
              v-model="formData.tipo_documento"
              required
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errores.tipo_documento }"
            >
              <option value="" disabled>Seleccione un tipo</option>
              <option value="DUI">DUI</option>
              <option value="PASAPORTE">PASAPORTE</option>
            </select>
            <small v-if="errores.tipo_documento" class="text-red-500 text-xs">
              {{ errores.tipo_documento }}
            </small>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
            <DatePicker
              v-model="formData.fecha_nacimiento"
              :maxDate="getFechaMaximaNacimiento()"
              date-format="dd/mm/yy"
              placeholder="Seleccionar fecha de nacimiento (debe ser mayor de 18 años)"
              showIcon
              yearNavigator
              yearRange="1920:2006"
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
