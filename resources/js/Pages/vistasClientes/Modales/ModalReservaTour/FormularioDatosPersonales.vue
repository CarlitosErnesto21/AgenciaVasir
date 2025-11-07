<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'

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

// Estado para tipos de documentos
const tiposDocumentos = ref([])
const cargandoTipos = ref(false)

// Estado de validación del teléfono
const telefonoValidation = ref({
  isValid: false,
  country: null,
  formattedNumber: '',
  mensaje: ''
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

// Función para cargar tipos de documentos
const cargarTiposDocumentos = async () => {
  try {
    cargandoTipos.value = true
    const response = await fetch('/api/tipo-documentos', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      tiposDocumentos.value = data.tipos || data
    } else {
      console.error('Error al cargar tipos de documentos:', response.status)
      // Fallback a opciones predeterminadas
      tiposDocumentos.value = [
        { id: 1, nombre: 'DUI' },
        { id: 2, nombre: 'CÉDULA' },
        { id: 3, nombre: 'PASAPORTE' }
      ]
    }
  } catch (error) {
    console.error('Error al cargar tipos de documentos:', error)
    // Fallback a opciones predeterminadas
    tiposDocumentos.value = [
      { id: 1, nombre: 'DUI' },
      { id: 2, nombre: 'CÉDULA' },
      { id: 3, nombre: 'PASAPORTE' }
    ]
  } finally {
    cargandoTipos.value = false
  }
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
        formularioLocal.value.telefono = phoneObject.formatted
      }

      // Si los datos están precargados, no validar duplicados (ya son del mismo cliente)
      if (props.tieneClienteExistente) {
        if (phoneObject.valid === true) {
          telefonoValidation.value.mensaje = 'Número válido (guardado previamente)'
        } else if (formularioLocal.value.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else {
          telefonoValidation.value.mensaje = ''
        }
      } else {
        // Solo validar duplicados para nuevos clientes
        if (formularioLocal.value.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else if (phoneObject.valid === true) {
          // Ahora validar con el teléfono ya actualizado en el modelo
          await validarTelefonoUnico(formularioLocal.value.telefono)
        } else {
          telefonoValidation.value.mensaje = ''
        }
      }
    }
  } catch (error) {
    console.error('[FormularioDatosPersonales] Error en validación:', error)
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

// Función para obtener fecha máxima de nacimiento (ayer)
const getFechaMaximaNacimiento = () => {
  const ayer = new Date()
  ayer.setDate(ayer.getDate() - 1)
  return ayer
}

// Función de validación del formulario
const validateForm = () => {
  // Validación del tipo de documento
  if (!formularioLocal.value.tipo_documento && !formularioLocal.value.tipo_documento_id) {
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

  if (!formularioLocal.value.fecha_nacimiento) {
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Campo requerido',
      detail: 'Por favor, seleccione su fecha de nacimiento.',
      life: 4000
    })
    return false
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

  return true
}

// Exponer los tipos de documentos y funciones al componente padre
defineExpose({
  tiposDocumentos,
  validateForm
})

// Función para manejar cambio de tipo de documento
const onTipoDocumentoChange = (nuevoTipo) => {
  if (nuevoTipo && nuevoTipo.id) {
    const nuevoFormulario = {
      ...formularioLocal.value,
      tipo_documento: nuevoTipo,
      tipo_documento_id: nuevoTipo.id
    }
    emit('update:formulario', nuevoFormulario)
  }
}

// Hook onMounted para cargar datos iniciales
onMounted(() => {
  cargarTiposDocumentos()
})

// Watch para seleccionar automáticamente el tipo de documento cuando se cargan los tipos
watch([tiposDocumentos, () => props.formulario.tipo_documento_id], ([tipos, tipoId]) => {
  if (tipos.length > 0 && tipoId && !props.formulario.tipo_documento) {
    const tipoEncontrado = tipos.find(tipo => tipo.id === tipoId)
    if (tipoEncontrado) {
      // Actualizar el formulario con el tipo de documento encontrado usando la función segura
      onTipoDocumentoChange(tipoEncontrado)
    }
  }
}, { immediate: true, flush: 'post' })

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
      <h3 class="font-bold text-lg text-gray-800 mb-2">Información de tu cuenta:</h3>
      <p class="text-sm text-gray-600 mb-1"><strong>Nombre:</strong> {{ user.name }}</p>
      <p class="text-sm text-gray-600"><strong>Email:</strong> {{ user.email }}</p>
    </div>

    <!-- Mensaje informativo para datos precargados -->
    <div v-if="tieneClienteExistente" class="flex items-center justify-between mb-3">
      <h4 class="font-semibold text-gray-800">Información Personal</h4>
      <div class="flex items-center text-green-600 text-sm">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <span>Datos precargados</span>
      </div>
    </div>

    <!-- Formulario de información personal -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Número de Identificación</label>
        <input
          v-model="formularioLocal.numero_identificacion"
          type="text"
          required
          maxlength="25"
          :disabled="tieneClienteExistente"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
          placeholder="Ingrese su DUI o documento"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento</label>
        <select
          :value="formularioLocal.tipo_documento_id"
          @change="onTipoDocumentoChange(tiposDocumentos.find(t => t.id === parseInt($event.target.value)))"
          required
          :disabled="cargandoTipos || tieneClienteExistente"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
        >
          <option v-if="cargandoTipos" value="" disabled>Cargando tipos...</option>
          <option v-else-if="tiposDocumentos.length === 0" value="" disabled>No hay tipos disponibles</option>
          <template v-else>
            <option value="" disabled>Seleccione un tipo</option>
            <option v-for="tipo in tiposDocumentos" :key="tipo.id" :value="tipo.id">
              {{ tipo.nombre }}
            </option>
          </template>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
        <Calendar
          v-model="formularioLocal.fecha_nacimiento"
          :maxDate="getFechaMaximaNacimiento()"
          date-format="dd/mm/yy"
          placeholder="Seleccionar fecha de nacimiento"
          showIcon
          yearNavigator
          yearRange="1920:2010"
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
        <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
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
