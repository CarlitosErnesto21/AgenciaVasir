<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'
import DatePicker from 'primevue/datepicker'
import { router } from '@inertiajs/vue3'

// Props del componente
const props = defineProps({
  formulario: {
    type: Object,
    required: true
  },
  tieneClienteExistente: {
    type: Boolean,
    default: false
  },
  user: {
    type: Object,
    default: null
  }
})

// Emits para comunicación con el componente padre
const emit = defineEmits(['update:formulario', 'mostrar-toast'])

// Tipos de documento como ENUM
const tiposDocumentos = ref([
  { id: 'DUI', nombre: 'DUI' },
  { id: 'PASAPORTE', nombre: 'PASAPORTE' }
])
const cargandoTipos = ref(false)

// Estado de validación del teléfono
const telefonoValidation = ref({
  isValid: false,
  country: null,
  formattedNumber: '',
  mensaje: '',
  mostrarMensaje: false
})

// Estado de validación del documento
const documentoValidation = ref({
  isValid: false,
  mensaje: '',
  mostrarMensaje: false
})

// Estado de validación de formato en tiempo real
const formatoValidation = ref({
  isValid: true,
  mensaje: '',
  mostrarMensaje: false
})

// Computed para el formulario reactivo
const formularioLocal = computed({
  get() {
    return props.formulario
  },
  set(value) {
    emit('update:formulario', value)
  }
})

// Ya no necesitamos cargar tipos de documento desde API

// Función de validación del teléfono
const onValidate = async (phoneObject) => {
  // Evitar múltiples validaciones rápidas
  if (!phoneObject || typeof phoneObject !== 'object') return

  try {
    telefonoValidation.value.isValid = phoneObject.valid === true
    telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode }
    telefonoValidation.value.formattedNumber = phoneObject.formatted || ''

    // Si los datos están precargados, no validar duplicados
    if (props.tieneClienteExistente) {
      telefonoValidation.value.mensaje = phoneObject.valid ? 'Número válido (guardado previamente)' : ''
    } else {
      // Solo validar duplicados si el número es válido y completo
      if (phoneObject.valid === true) {
        await validarTelefonoUnico(phoneObject.formatted)
      } else {
        telefonoValidation.value.mensaje = ''
      }
    }
  } catch (error) {
    console.error('Error en validación:', error)
    telefonoValidation.value.mensaje = 'Error en validación'
  }
}

// Validar que el teléfono no esté duplicado
const validarTelefonoUnico = async (telefono) => {
  if (!telefono || telefono.length < 8) return

  try {
    const response = await fetch('/api/clientes/validar-telefono', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        telefono: telefono
      })
    })

    const data = await response.json()

    if (data.disponible) {
      telefonoValidation.value.mensaje = 'Número válido para ' + telefonoValidation.value.country?.name
    } else {
      telefonoValidation.value.isValid = false
      telefonoValidation.value.mensaje = data.message || 'Este teléfono ya está registrado'
    }
  } catch (error) {
    console.error('[FormularioDatosPersonales] Error validando teléfono:', error)
    telefonoValidation.value.mensaje = 'Error al validar teléfono'
  }
}

// Función para validar formato de documento en tiempo real (sin toasts)
const validarFormatoDocumento = (numeroIdentificacion, tipoDocumento) => {
  if (!numeroIdentificacion || !tipoDocumento) {
    formatoValidation.value.mensaje = ''
    formatoValidation.value.isValid = true
    return
  }

  if (tipoDocumento === 'DUI') {
    const duiRegex = /^\d{8}-\d{1}$/
    if (!duiRegex.test(numeroIdentificacion)) {
      formatoValidation.value.mensaje = 'El DUI debe tener 9 dígitos (formato: 12345678-9)'
      formatoValidation.value.isValid = false
    } else {
      formatoValidation.value.mensaje = ''
      formatoValidation.value.isValid = true
    }
  } else if (tipoDocumento === 'PASAPORTE') {
    const pasaporteRegex = /^[A-Z0-9]{6,9}$/
    if (!pasaporteRegex.test(numeroIdentificacion)) {
      formatoValidation.value.mensaje = 'El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)'
      formatoValidation.value.isValid = false
    } else {
      formatoValidation.value.mensaje = ''
      formatoValidation.value.isValid = true
    }
  }
}

// Validar que el documento no esté duplicado
const validarDocumentoUnico = async (numeroIdentificacion) => {
  if (!numeroIdentificacion || numeroIdentificacion.length < 3) return

  try {
    const response = await fetch('/api/clientes/validar-documento', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        numero_identificacion: numeroIdentificacion
      })
    })

    const data = await response.json()

    if (data.disponible) {
      documentoValidation.value.mensaje = '✓ Número de identificación disponible'
      documentoValidation.value.isValid = true
    } else {
      documentoValidation.value.isValid = false
      documentoValidation.value.mensaje = data.message || 'Este número de identificación ya está registrado'
    }
  } catch (error) {
    console.error('[FormularioDatosPersonales] Error validando documento:', error)
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

// Función de validación del formulario
const validateForm = () => {
  // Validación del tipo de documento
  if (!formularioLocal.value.tipo_documento) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, seleccione un tipo de documento.',
      life: 4000
    })
    return false
  }

  // Validaciones adicionales
  if (!formularioLocal.value.numero_identificacion) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, ingrese su número de identificación.',
      life: 4000
    })
    return false
  }

  // Validar formato del número de identificación
  if (!validarNumeroIdentificacion()) {
    return false
  }

  if (!formularioLocal.value.fecha_nacimiento) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, seleccione su fecha de nacimiento.',
      life: 4000
    })
    return false
  } else {
    const validacionEdad = validarEdadMinima(formularioLocal.value.fecha_nacimiento)
    if (!validacionEdad.esValido) {
      emit('mostrar-toast', {
        severity: 'error',
        summary: 'Edad insuficiente',
        detail: validacionEdad.mensaje,
        life: 4000
      })
      return false
    }
  }

  if (!formularioLocal.value.direccion) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, ingrese su dirección.',
      life: 4000
    })
    return false
  }

  // Validación del género
  if (!formularioLocal.value.genero) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, seleccione su género.',
      life: 4000
    })
    return false
  }

  // Validación del teléfono
  if (!formularioLocal.value.telefono) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, ingrese su número de teléfono.',
      life: 4000
    })
    return false
  }

  // Solo validar formato si el teléfono fue modificado (no viene de datos precargados)
  if (formularioLocal.value.telefono && telefonoValidation.value.isValid === false && telefonoValidation.value.mensaje !== 'Número válido (guardado previamente)') {
    // Si el mensaje contiene información sobre duplicado, usar mensaje específico
    if (telefonoValidation.value.mensaje && (telefonoValidation.value.mensaje.includes('registrado') || telefonoValidation.value.mensaje.includes('diferente'))) {
      emit('mostrar-toast', {
        severity: 'warn',
        summary: 'Teléfono ya registrado',
        detail: telefonoValidation.value.mensaje,
        life: 5000
      })
    } else {
      emit('mostrar-toast', {
        severity: 'error',
        summary: 'Teléfono inválido',
        detail: 'Por favor, ingrese un número de teléfono válido.',
        life: 4000
      })
    }
    return false
  }

  // Validar documento duplicado
  if (!props.tieneClienteExistente && formularioLocal.value.numero_identificacion && !documentoValidation.value.isValid) {
    emit('mostrar-toast', {
      severity: 'warn',
      summary: 'Documento no disponible',
      detail: documentoValidation.value.mensaje || 'Este número de identificación no está disponible.',
      life: 5000
    })
    return false
  }

  return true
}

// Función para navegar al perfil del usuario
const navegarAlPerfil = () => {
  router.visit('/profile')
}

// Exponer los tipos de documentos y funciones al componente padre
defineExpose({
  tiposDocumentos,
  validateForm
})

// Función para manejar cambio de tipo de documento
const onTipoDocumentoChange = (nuevoTipo) => {
  if (nuevoTipo) {
    const nuevoFormulario = {
      ...formularioLocal.value,
      tipo_documento: nuevoTipo
    }
    emit('update:formulario', nuevoFormulario)
  }
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

  if (formularioLocal.value.tipo_documento === 'DUI') {
    valorFormateado = formatearDUI(valor)
    formularioLocal.value.numero_identificacion = valorFormateado
    // Actualizar el valor del input inmediatamente
    event.target.value = valorFormateado
  } else if (formularioLocal.value.tipo_documento === 'PASAPORTE') {
    valorFormateado = formatearPasaporte(valor)
    formularioLocal.value.numero_identificacion = valorFormateado
    // Actualizar el valor del input inmediatamente
    event.target.value = valorFormateado
  }

  // Validar después de formatear
  validarNumeroIdentificacion()

  // Validar duplicados si el formato es válido y no es cliente existente
  if (formularioLocal.value.numero_identificacion && formularioLocal.value.numero_identificacion.length >= 3) {
    const isValidFormat = (formularioLocal.value.tipo_documento === 'DUI' && /^\d{8}-\d{1}$/.test(formularioLocal.value.numero_identificacion)) ||
                         (formularioLocal.value.tipo_documento === 'PASAPORTE' && /^[A-Z0-9]{6,9}$/.test(formularioLocal.value.numero_identificacion))

    if (isValidFormat && !props.tieneClienteExistente) {
      validarDocumentoUnico(formularioLocal.value.numero_identificacion)
    } else {
      documentoValidation.value.mensaje = ''
      documentoValidation.value.isValid = true
    }
  }
}

// Validación en tiempo real del número de identificación (sin toasts, solo para validación de envío)
const validarNumeroIdentificacion = () => {
  if (!formularioLocal.value.numero_identificacion) {
    return true
  }

  // Usar la validación silenciosa que ya actualiza formatoValidation
  validarFormatoDocumento(
    formularioLocal.value.numero_identificacion,
    formularioLocal.value.tipo_documento
  )

  // Retornar el resultado sin mostrar toasts (solo para validación de envío)
  return formatoValidation.value.isValid
}

// Hook onMounted para cargar datos iniciales
onMounted(() => {
  // Ya no necesitamos cargar tipos de documento
})

// Watchers para validación en tiempo real
watch(() => formularioLocal.value.numero_identificacion, (newValue) => {
  if (newValue && formularioLocal.value.tipo_documento) {
    validarNumeroIdentificacion()

    // Validar formato en tiempo real (sin toasts)
    validarFormatoDocumento(newValue, formularioLocal.value.tipo_documento)

    // Validar duplicados si el formato es válido y no es cliente existente
    if (newValue.length >= 3 && formatoValidation.value.isValid) {
      if (!props.tieneClienteExistente) {
        validarDocumentoUnico(newValue)
      } else {
        documentoValidation.value.mensaje = ''
        documentoValidation.value.isValid = true
      }
    }
  } else {
    documentoValidation.value.mensaje = ''
    documentoValidation.value.isValid = true
  }
})

watch(() => formularioLocal.value.tipo_documento, () => {
  // Solo limpiar número cuando cambia el tipo de documento si NO tiene datos precargados
  // Esto evita que se borren los datos cuando se cargan desde el cliente existente
  if (!props.tieneClienteExistente) {
    formularioLocal.value.numero_identificacion = ''
  }
  documentoValidation.value.mensaje = ''
  documentoValidation.value.isValid = true
  formatoValidation.value.mensaje = ''
  formatoValidation.value.isValid = true
})

// Watch para validación de fecha de nacimiento en tiempo real
watch(() => formularioLocal.value.fecha_nacimiento, (nuevaFecha) => {
  if (nuevaFecha) {
    const validacionEdad = validarEdadMinima(nuevaFecha)
    if (!validacionEdad.esValido) {
      emit('mostrar-toast', {
        severity: 'error',
        summary: 'Edad insuficiente',
        detail: validacionEdad.mensaje,
        life: 4000
      })
    }
  }
})

// Watch para manejar teléfono precargado
watch(() => props.formulario.telefono, (nuevoTelefono, telefonoAnterior) => {
  // Si hay un teléfono precargado y es diferente del anterior
  if (nuevoTelefono && nuevoTelefono !== telefonoAnterior && props.tieneClienteExistente) {
    // Marcar como válido si viene de datos precargados
    telefonoValidation.value = {
      isValid: true,
      country: { name: 'Válido', code: '' },
      formattedNumber: nuevoTelefono,
      mensaje: 'Número válido (guardado previamente)'
    }
  }
}, { immediate: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Información del usuario (si está logueado) -->
    <div v-if="user" class="bg-gradient-to-r from-blue-50 to-purple-50 p-4 rounded-lg">
      <h3 class="font-bold text-lg text-blue-500 mb-2">Información de tu cuenta:</h3>
      <p class="text-sm text-blue-500 mb-1"><strong>Nombre:</strong> {{ user.name }}</p>
      <p class="text-sm text-blue-500"><strong>Email:</strong> {{ user.email }}</p>
    </div>

    <!-- Mensaje informativo para datos precargados -->
    <div v-if="tieneClienteExistente" class="flex items-center justify-between mb-3">
      <h4 class="font-semibold text-gray-800">Información Personal</h4>
      <div class="flex items-center gap-3">
        <div class="flex items-center text-green-600 text-sm">
          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span>Datos precargados</span>
        </div>
        <button
          @click="navegarAlPerfil"
          type="button"
          class="inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          title="Editar mis datos personales"
        >
          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Editar datos
        </button>
      </div>
    </div>

    <!-- Formulario de información personal -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Documento de identidad</label>
        <select
          v-model="formularioLocal.tipo_documento"
          required
          :disabled="tieneClienteExistente"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
        >
          <option value="" disabled>Seleccione un tipo</option>
          <option value="DUI">DUI</option>
          <option value="PASAPORTE">PASAPORTE</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Número de identificación</label>
        <input
          :value="formularioLocal.numero_identificacion"
          @input="manejarEntradaDocumento"
          type="text"
          required
          :maxlength="formularioLocal.tipo_documento === 'DUI' ? 10 : 9"
          :disabled="tieneClienteExistente"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
          :placeholder="formularioLocal.tipo_documento === 'DUI' ? 'Ingrese 9 dígitos (ej: 123456789)' : 'Ingrese su PASAPORTE (ej: A1B2C3D4)'"
        />
        <!-- Mensaje de validación de formato en tiempo real -->
        <small
          v-if="formatoValidation.mensaje && !tieneClienteExistente"
          :class="[
            'block mt-1',
            formatoValidation.isValid ? 'text-green-600' : 'text-red-500'
          ]"
        >
          {{ formatoValidation.mensaje }}
        </small>

        <!-- Mensaje de validación de duplicados -->
        <small
          v-if="documentoValidation.mensaje && !tieneClienteExistente"
          :class="[
            'block mt-1',
            documentoValidation.isValid ? 'text-green-600' : 'text-red-500'
          ]"
        >
          {{ documentoValidation.mensaje }}
        </small>
      </div>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de nacimiento</label>
        <DatePicker
          v-model="formularioLocal.fecha_nacimiento"
          :maxDate="getFechaMaximaNacimiento()"
          date-format="dd/mm/yy"
          placeholder="Seleccionar fecha de nacimiento (debe ser mayor de 18 años)"
          showIcon
          yearNavigator
          yearRange="1920:2006"
          :disabled="tieneClienteExistente"
          class="w-full"
          :inputClass="`w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${tieneClienteExistente ? 'bg-gray-100 cursor-not-allowed' : ''}`"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
        <select
          v-model="formularioLocal.genero"
          required
          :disabled="tieneClienteExistente"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
        >
          <option value="">Seleccione</option>
          <option value="MASCULINO">Masculino</option>
          <option value="FEMENINO">Femenino</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
        <VueTelInput
          v-model="formularioLocal.telefono"
          defaultCountry="SV"
          :preferredCountries="['SV', 'GT', 'HN', 'CR', 'NI', 'PA', 'BZ']"
          :validCharactersOnly="true"
          :disabled="tieneClienteExistente"
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
          :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
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
      </div>
      <div class="sm:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-2">Dirección de residencia</label>
        <input
          v-model="formularioLocal.direccion"
          type="text"
          required
          maxlength="200"
          :disabled="tieneClienteExistente"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
          placeholder="Dirección completa"
        />
      </div>
    </div>
  </div>
</template>
