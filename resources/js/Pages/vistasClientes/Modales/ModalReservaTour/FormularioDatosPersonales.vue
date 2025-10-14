<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'

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
const onValidate = (phoneObject) => {
  try {
    if (phoneObject && typeof phoneObject === 'object') {
      telefonoValidation.value.isValid = phoneObject.valid === true
      telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode }
      telefonoValidation.value.formattedNumber = phoneObject.formatted || ''

      if (formularioLocal.value.telefono && phoneObject.valid === false) {
        telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
      } else if (phoneObject.valid === true) {
        telefonoValidation.value.mensaje = 'Número válido para ' + phoneObject.country
      } else {
        telefonoValidation.value.mensaje = ''
      }
    }
  } catch (error) {
    telefonoValidation.value.mensaje = 'Error en validación'
  }
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
    emit('mostrar-toast', {
      severity: 'error',
      summary: 'Teléfono inválido',
      detail: 'Por favor, ingrese un número de teléfono válido.',
      life: 4000
    })
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
  <div>
    <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 flex items-center text-sm sm:text-base">
      <span class="text-base sm:text-lg mr-1 sm:mr-2">👤</span>
      <span class="hidden sm:inline">Datos personales</span>
      <span class="sm:hidden">Datos</span>
    </h4>

    <!-- Mensaje informativo para datos precargados -->
    <div v-if="tieneClienteExistente" class="mb-3 sm:mb-4 p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
      <p class="text-xs sm:text-sm text-blue-700 flex items-center">
        <span class="mr-1 sm:mr-2">ℹ️</span>
        <span class="hidden sm:inline">Sus datos personales se han cargado automáticamente desde su perfil. Solo puede modificar la cantidad de cupos para esta reserva.</span>
        <span class="sm:hidden">Datos cargados desde su perfil. Solo puede modificar cupos.</span>
      </p>
    </div>

    <form class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 lg:gap-4">
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Correo electrónico</label>
        <InputText
          v-model="formularioLocal.correo"
          type="email"
          :disabled="!!user"
          placeholder="ejemplo@email.com"
          class="w-full"
          :pt="{
            root: {
              class: [
                'w-full border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm transition-colors',
                'focus:ring-2 focus:ring-red-500 focus:border-red-500',
                { 'bg-gray-100 cursor-not-allowed': !!user }
              ]
            }
          }"
        />
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Nombre Completo</label>
        <InputText
          v-model="formularioLocal.nombres"
          :disabled="!!user"
          placeholder="Nombres y apellidos"
          class="w-full"
          :pt="{
            root: {
              class: [
                'w-full border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm transition-colors',
                'focus:ring-2 focus:ring-red-500 focus:border-red-500',
                { 'bg-gray-100 cursor-not-allowed': !!user }
              ]
            }
          }"
        />
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Tipo documento</label>
        <Select
          :model-value="formularioLocal.tipo_documento"
          @update:model-value="onTipoDocumentoChange"
          :options="tiposDocumentos"
          optionLabel="nombre"
          :loading="cargandoTipos"
          :disabled="tieneClienteExistente"
          placeholder="Seleccionar tipo de documento"
          class="w-full border border-gray-300 rounded-lg"
          :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
          :pt="{
            input: { class: 'px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors' },
            trigger: { class: 'text-gray-400' },
            panel: { class: 'border border-gray-300 rounded-lg shadow-lg' },
            item: { class: 'px-3 py-2 text-xs sm:text-sm hover:bg-red-50 transition-colors' }
          }"
        />
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Número de identificación</label>
        <InputText
          v-model="formularioLocal.numero_identificacion"
          :disabled="tieneClienteExistente"
          placeholder="Número de documento"
          class="w-full"
          :pt="{
            root: {
              class: [
                'w-full border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm transition-colors',
                'focus:ring-2 focus:ring-red-500 focus:border-red-500',
                { 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }
              ]
            }
          }"
        />
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Fecha de nacimiento</label>
        <DatePicker
          v-model="formularioLocal.fecha_nacimiento"
          dateFormat="dd/mm/yy"
          class="w-full"
          :inputClass="tieneClienteExistente ? 'w-full border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm bg-gray-100 cursor-not-allowed' : 'w-full border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors'"
          placeholder="dd/mm/aaaa"
          :maxDate="new Date()"
          :disabled="tieneClienteExistente"
          showIcon
        />
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Género</label>
        <Select
          v-model="formularioLocal.genero"
          :options="[
            { label: 'Masculino', value: 'MASCULINO' },
            { label: 'Femenino', value: 'FEMENINO' }
          ]"
          optionLabel="label"
          optionValue="value"
          :disabled="tieneClienteExistente"
          placeholder="Seleccionar género"
          class="w-full"
          :pt="{
            root: {
              class: [
                'w-full border border-gray-300 rounded-lg',
                { 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }
              ]
            },
            input: {
              class: 'px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors'
            },
            trigger: { class: 'text-gray-400' },
            panel: { class: 'border border-gray-300 rounded-lg shadow-lg' },
            item: { class: 'px-3 py-2 text-xs sm:text-sm hover:bg-red-50 transition-colors' }
          }"
        />
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Dirección de residencia</label>
        <Textarea
          v-model="formularioLocal.direccion"
          :disabled="tieneClienteExistente"
          rows="2"
          placeholder="Dirección completa de residencia"
          class="w-full"
          :pt="{
            root: {
              class: [
                'w-full border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm transition-colors',
                'focus:ring-2 focus:ring-red-500 focus:border-red-500',
                { 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }
              ]
            }
          }"
        />
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Teléfono</label>
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
    </form>
  </div>
</template>
