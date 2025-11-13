<template>
  <Catalogo>
    <div class="min-h-screen bg-gray-50 py-4 sm:py-8 mt-32">
      <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-4 sm:mb-8">
          <ol class="flex items-center space-x-1 sm:space-x-2 text-xs sm:text-sm">
            <li>
              <Link
                href="/"
                class="text-blue-600 hover:text-blue-800"
              >
                Inicio
              </Link>
            </li>
            <li class="text-gray-500">/</li>
            <li>
              <Link
                href="/reservaciones"
                class="text-blue-600 hover:text-blue-800"
              >
                Hoteles
              </Link>
            </li>
            <li class="text-gray-500">/</li>
            <li class="text-gray-900 font-medium truncate">{{ hotel.nombre }}</li>
          </ol>
        </nav>

        <!-- Estado de carga -->
        <div v-if="loading" class="bg-white rounded-lg shadow-lg overflow-hidden p-8">
          <div class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Cargando detalles del hotel...</span>
          </div>
        </div>

        <!-- Estado de error -->
        <div v-else-if="error" class="bg-white rounded-lg shadow-lg overflow-hidden p-8">
          <div class="text-center">
            <div class="text-red-600 mb-4">
              <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error al cargar el hotel</h3>
            <p class="text-gray-600 mb-4">{{ error }}</p>
            <button
              @click="obtenerHotel"
              class="bg-blue-600 hover:bg-blue-700 px-4 py-2 text-white rounded-lg transition-colors"
            >
              Reintentar
            </button>
          </div>
        </div>

        <!-- Contenido principal -->
        <div v-else-if="hotel" class="bg-white rounded-lg shadow-lg overflow-hidden">
          <!-- Galería de imágenes -->
          <div
            class="relative w-full h-64 sm:h-72 md:h-80 lg:h-96 bg-gray-100 rounded-t-lg overflow-hidden flex items-center justify-center"
            @mouseenter="detenerCarrusel"
            @mouseleave="iniciarCarrusel"
          >
            <div v-if="hotel.imagenes && hotel.imagenes.length > 0" class="relative w-full h-full flex items-center justify-center">
              <img
                :src="obtenerImagenActual()"
                :alt="hotel.nombre"
                class="max-w-full max-h-full object-contain transition-opacity duration-500"
              />

              <!-- Controles de navegación de imágenes -->
              <div v-if="hotel.imagenes.length > 1" class="absolute inset-0 flex items-center justify-between p-2 sm:p-4 pointer-events-none">
                <button
                  @click="imagenAnterior"
                  class="bg-black/50 text-white hover:bg-black/70 rounded-full p-2 sm:p-3 transition-all duration-200 pointer-events-auto flex items-center justify-center"
                >
                  <i class="pi pi-chevron-left text-sm sm:text-lg"></i>
                </button>
                <button
                  @click="imagenSiguiente"
                  class="bg-black/50 text-white hover:bg-black/70 rounded-full p-2 sm:p-3 transition-all duration-200 pointer-events-auto flex items-center justify-center"
                >
                  <i class="pi pi-chevron-right text-sm sm:text-lg"></i>
                </button>
              </div>

              <!-- Indicadores de imagen -->
              <div v-if="hotel.imagenes.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10">
                <div class="flex space-x-2">
                  <button
                    v-for="(imagen, index) in hotel.imagenes"
                    :key="index"
                    @click="cambiarImagen(index)"
                    :class="[
                      'w-3 h-3 rounded-full transition-all duration-200 hover:scale-110',
                      currentImageIndex === index ? 'bg-white' : 'bg-white/50 hover:bg-white/70'
                    ]"
                  />
                </div>
              </div>
            </div>
            <div v-else class="w-full h-full bg-gray-300 flex items-center justify-center rounded-t-lg">
              <span class="text-gray-500 text-lg">Sin imagen disponible</span>
            </div>
          </div>

          <!-- Información del hotel -->
          <div class="p-4 sm:p-6 md:p-8">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
              <!-- Información principal -->
              <div class="order-2 xl:order-1">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-3 sm:mb-4">{{ hotel.nombre }}</h1>

                <div class="mb-4 sm:mb-6">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-blue-100 text-blue-800">
                    {{ hotel.categoria_hotel?.nombre || 'Sin categoría' }}
                  </span>
                </div>

                <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-map-marker mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Dirección:</strong> {{ hotel.direccion }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-globe mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Provincia:</strong> {{ hotel.provincia?.nombre || 'No especificado' }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-flag mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>País:</strong> {{ hotel.pais?.nombre || 'No especificado' }}</span>
                  </div>
                  <div v-if="hotel.telefono" class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-phone mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Teléfono:</strong> {{ hotel.telefono }}</span>
                  </div>
                  <div v-if="hotel.email" class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-envelope mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Email:</strong> {{ hotel.email }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-info-circle mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Estado:</strong>
                      <span :class="hotel.estado === 'activo' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
                        {{ hotel.estado === 'activo' ? 'Disponible' : 'No disponible' }}
                      </span>
                    </span>
                  </div>
                </div>

                <!-- Descripción -->
                <div v-if="hotel.descripcion" class="mb-4 sm:mb-6 order-1 xl:order-none">
                  <h3 class="text-lg font-semibold text-gray-900 mb-2">Descripción</h3>
                  <div class="text-gray-700 text-sm sm:text-base leading-relaxed">
                    {{ hotel.descripcion }}
                  </div>
                </div>

                <!-- Botón de reserva -->
                <button
                  @click="reservarHotel"
                  :disabled="hotel.estado !== 'activo'"
                  :class="[
                    'w-full font-semibold py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors duration-200 text-sm sm:text-base',
                    hotel.estado !== 'activo'
                      ? 'bg-gray-400 text-white cursor-not-allowed'
                      : 'bg-blue-600 hover:bg-blue-700 text-white'
                  ]"
                >
                  {{ hotel.estado !== 'activo' ? 'Hotel No Disponible' : 'Reservar Hotel' }}
                </button>

                <!-- Botón de contacto por WhatsApp -->
                <button
                  @click="contactarHotel"
                  class="w-full mt-3 font-semibold py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors duration-200 text-sm sm:text-base bg-green-600 hover:bg-green-700 text-white flex items-center justify-center gap-2"
                >
                  <i class="pi pi-phone text-sm sm:text-base"></i>
                  Contactar por WhatsApp
                </button>
              </div>

              <!-- Detalles adicionales -->
              <div class="order-3 xl:order-2">
                <!-- Información del hotel -->
                <div class="mb-4 sm:mb-6">
                  <div class="bg-blue-50 rounded-lg p-4 sm:p-6 border border-blue-200">
                    <h3 class="text-lg sm:text-xl font-semibold text-blue-900 mb-3 flex items-center">
                      <i class="pi pi-building mr-2 text-sm sm:text-base"></i>
                      Información del Hotel
                    </h3>
                    <ul class="text-blue-800 space-y-2 text-sm sm:text-base">
                      <li class="flex items-center">
                        <i class="pi pi-check-circle mr-2 text-green-600"></i>
                        Hotel verificado y seleccionado
                      </li>
                      <li class="flex items-center">
                        <i class="pi pi-shield mr-2 text-green-600"></i>
                        Garantía de calidad en el servicio
                      </li>
                      <li class="flex items-center">
                        <i class="pi pi-users mr-2 text-green-600"></i>
                        Atención personalizada
                      </li>
                      <li class="flex items-center">
                        <i class="pi pi-heart mr-2 text-green-600"></i>
                        Experiencia única garantizada
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Información importante -->
                <div class="bg-yellow-50 rounded-lg p-3 sm:p-4 border border-yellow-200">
                  <h3 class="text-base sm:text-lg font-semibold text-yellow-900 mb-2">
                    <i class="pi pi-info-circle mr-2 text-sm sm:text-base"></i>
                    Información importante
                  </h3>
                  <ul class="text-yellow-800 space-y-1 text-xs sm:text-sm">
                    <li>• Confirmar disponibilidad antes de realizar la reserva</li>
                    <li>• Los precios pueden variar según la temporada</li>
                    <li>• Se requiere documento de identidad vigente</li>
                    <li>• Políticas de cancelación según el hotel</li>
                    <li>• Para consultas específicas, contactar directamente</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón para regresar -->
        <div class="mt-4 sm:mt-8 text-center px-4">
          <button
            @click="regresar"
            class="inline-flex items-center text-white text-sm sm:text-base py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors duration-200 bg-blue-400 hover:bg-blue-500"
          >
            <i class="pi pi-arrow-left mr-2"></i>
            Regresar a Hoteles
          </button>
        </div>
      </div>
    </div>

    <!-- Toast para notificaciones -->
    <Toast />

    <!-- Modal de Reserva de Hotel -->
    <Dialog
      v-model:visible="showReservaDialog"
      modal
      header="Reservar Hotel"
      :closable="false"
      class="max-w-2xl w-full mx-4"
      :draggable="false"
    >
      <div v-if="hotel" class="space-y-6">
        <!-- Información del hotel seleccionado -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-4 rounded-lg">
          <h3 class="font-bold text-lg text-gray-800 mb-2">{{ hotel.nombre }}</h3>
          <p class="text-sm text-gray-600 mb-1">{{ hotel.direccion }}</p>
          <p class="text-sm text-gray-500">{{ hotel.provincia?.nombre }}, {{ hotel.pais?.nombre }}</p>
        </div>

        <!-- Formulario de reserva -->
        <form @submit.prevent="crearReservaHotel" class="space-y-4">
          <!-- Fechas de estadía -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Entrada</label>
              <DatePicker
                v-model="reservaForm.fecha_entrada"
                :minDate="getFechaMinima()"
                date-format="dd/mm/yy"
                placeholder="Seleccionar fecha de entrada"
                showIcon
                class="w-full"
                inputClass="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Salida</label>
              <DatePicker
                v-model="reservaForm.fecha_salida"
                :minDate="getFechaMinimaComputada"
                date-format="dd/mm/yy"
                placeholder="Seleccionar fecha de salida"
                showIcon
                class="w-full"
                inputClass="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Cantidad de personas y habitaciones -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad de Personas</label>
              <input
                v-model.number="reservaForm.cantidad_personas"
                type="number"
                min="1"
                max="10"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad de Habitaciones</label>
              <input
                v-model.number="reservaForm.cantidad_habitaciones"
                type="number"
                min="1"
                max="5"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Información del cliente -->
          <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-semibold text-gray-800">Información Personal</h4>
              <div v-if="tieneClienteExistente" class="flex items-center text-green-600 text-sm">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span>Datos precargados</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Identificación</label>
                <input
                  v-model="reservaForm.cliente_data.numero_identificacion"
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
                  v-model="reservaForm.cliente_data.tipo_documento"
                  required
                  :disabled="tieneClienteExistente"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
                >
                  <option value="" disabled>Seleccione un tipo</option>
                  <option value="DUI">DUI</option>
                  <option value="PASAPORTE">PASAPORTE</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
                <DatePicker
                  v-model="reservaForm.cliente_data.fecha_nacimiento"
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
                  v-model="reservaForm.cliente_data.genero"
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
                  v-model="reservaForm.cliente_data.telefono"
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
                  v-model="reservaForm.cliente_data.direccion"
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
        </form>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <button
            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="crearReservaHotel"
            :disabled="procesandoReserva"
          >
            <FontAwesomeIcon
              :icon="procesandoReserva ? faSpinner : faCheck"
              :class="[
                'h-5 text-white',
                { 'animate-spin': procesandoReserva }
              ]"
            />
            <span v-if="!procesandoReserva">Crear Reserva</span>
            <span v-else>Procesando...</span>
          </button>
          <button
            type="button"
            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="cerrarModalReserva"
            :disabled="procesandoReserva"
          >
            <FontAwesomeIcon :icon="faXmark" class="h-5" />
            Cancelar
          </button>
        </div>
      </template>
    </Dialog>

    <!-- Modal de autenticación requerida -->
    <ModalAuthRequerido
      v-model:visible="showAuthDialog"
      :tour-info="hotel ? { id: hotel.id, nombre: hotel.nombre, tipo: 'hotel' } : null"
    />
  </Catalogo>
</template>

<script setup>
import Catalogo from '../Catalogo.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import Toast from 'primevue/toast'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCheck, faSpinner, faXmark } from '@fortawesome/free-solid-svg-icons'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'

const page = usePage()
const user = computed(() => page.props.auth.user)

const toast = useToast()

// Variables para el modal de reserva de hotel
const showReservaDialog = ref(false)
const showAuthDialog = ref(false)
const procesandoReserva = ref(false)
const tieneClienteExistente = ref(false)

// Datos del formulario de reserva
const reservaForm = ref({
  fecha_entrada: null,
  fecha_salida: null,
  cantidad_personas: 1,
  cantidad_habitaciones: 1,
  cliente_data: {
    numero_identificacion: '',
    fecha_nacimiento: null,
    genero: '',
    direccion: '',
    telefono: '',
    tipo_documento: 'DUI'
  }
})

// Tipos de documento cargados desde la API
// Los tipos de documento ahora son ENUM: DUI, PASAPORTE

// Estado de validación del teléfono
const telefonoValidation = ref({
  isValid: false,
  country: null,
  formattedNumber: '',
  mensaje: ''
})

// Props
const props = defineProps({
  hotel: {
    type: Object,
    required: true
  }
})

// Estados reactivos (simplificados)
const loading = ref(false)
const error = ref(null)

// Variables para la galería de imágenes
const currentImageIndex = ref(0)
const carruselInterval = ref(null)

// Computed para obtener el hotel actual (usamos directamente los props)
const hotel = computed(() => {
  return props.hotel
})

// Función para obtener el hotel desde la API
// Función eliminada - usamos directamente los props del controlador

// Función para obtener la imagen actual
const obtenerImagenActual = () => {
  if (!hotel.value?.imagenes || hotel.value.imagenes.length === 0) {
    return 'https://via.placeholder.com/800x500/2563eb/ffffff?text=Sin+Imagen+Disponible'
  }

  const imagen = hotel.value.imagenes[currentImageIndex.value]
  const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre

  return `/storage/hoteles/${nombreImagen}`
}

// Funciones de navegación de imágenes
const imagenAnterior = () => {
  if (hotel.value.imagenes && hotel.value.imagenes.length > 1) {
    currentImageIndex.value = currentImageIndex.value === 0
      ? hotel.value.imagenes.length - 1
      : currentImageIndex.value - 1
    iniciarCarrusel() // Reiniciar el carrusel después de navegación manual
  }
}

const imagenSiguiente = () => {
  if (hotel.value.imagenes && hotel.value.imagenes.length > 1) {
    currentImageIndex.value = (currentImageIndex.value + 1) % hotel.value.imagenes.length
    iniciarCarrusel() // Reiniciar el carrusel después de navegación manual
  }
}

// Función para cambiar a una imagen específica
const cambiarImagen = (index) => {
  currentImageIndex.value = index
  iniciarCarrusel() // Reiniciar el carrusel después de navegación manual
}

// Funciones del carrusel automático
const iniciarCarrusel = () => {
  detenerCarrusel() // Detener cualquier carrusel existente antes de iniciar
  if (hotel.value.imagenes && hotel.value.imagenes.length > 1) {
    carruselInterval.value = setInterval(() => {
      currentImageIndex.value = (currentImageIndex.value + 1) % hotel.value.imagenes.length
    }, 3000) // Cambiar cada 3 segundos
  }
}

const detenerCarrusel = () => {
  if (carruselInterval.value) {
    clearInterval(carruselInterval.value)
    carruselInterval.value = null
  }
}

// Función para obtener tipos de documento desde la API
const obtenerTiposDocumento = async () => {
  try {
    loadingTiposDocumento.value = true
    const response = await fetch('/api/tipo-documentos', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`);
    }

    const data = await response.json()
    tiposDocumento.value = data.tipos || data || []

  } catch (err) {
    // Fallback en caso de error
    tiposDocumento.value = [
      { id: 1, nombre: 'DUI' },
      { id: 2, nombre: 'Pasaporte' },
      { id: 3, nombre: 'Licencia' }
    ]
  } finally {
    loadingTiposDocumento.value = false
  }
}

// Función para cargar datos del cliente existente
const cargarDatosCliente = async () => {
  if (!user.value) {
    return null
  }

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    const response = await fetch('/api/clientes/mi-perfil', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': token || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })

    if (response.ok) {
      const data = await response.json()
      return data.cliente || data || null
    } else if (response.status === 404) {
      return null
    } else {
      return null
    }
  } catch (error) {
    return null
  }
}

// Función para reservar el hotel
const reservarHotel = async () => {
  // Verificar si el usuario está logueado
  if (!user.value) {
    showAuthDialog.value = true
    return
  }

  // Verificar roles para restricción
  if (user.value.roles && Array.isArray(user.value.roles)) {
    const tieneRolRestringido = user.value.roles.some(role =>
      role.name === 'Administrador' || role.name === 'Empleado'
    )

    if (tieneRolRestringido) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Solo las cuentas de Cliente pueden realizar reservas. Los detalles del hotel están disponibles para consulta.',
        life: 5000
      })
      return
    }
  }

  // Resetear estado
  tieneClienteExistente.value = false

  // Cargar tipos de documento si no están cargados
  if (tiposDocumento.value.length === 0) {
    await obtenerTiposDocumento()
  }

  // Inicializar formulario con datos básicos
  reservaForm.value = {
    fecha_entrada: null,
    fecha_salida: null,
    cantidad_personas: 1,
    cantidad_habitaciones: 1,
    cliente_data: {
      numero_identificacion: '',
      fecha_nacimiento: null,
      genero: '',
      direccion: '',
      telefono: '',
      tipo_documento: 'DUI'
    }
  }

  // Cargar datos existentes del cliente si está logueado
  try {
    const clienteExistente = await cargarDatosCliente()

    if (clienteExistente) {
      tieneClienteExistente.value = true

      // Formatear fecha de nacimiento para Calendar
      let fechaNacimientoFormateada = null
      if (clienteExistente.fecha_nacimiento) {
        try {
          fechaNacimientoFormateada = new Date(clienteExistente.fecha_nacimiento)
        } catch (error) {
          fechaNacimientoFormateada = null
        }
      }

      // Actualizar formulario con datos existentes
      reservaForm.value.cliente_data = {
        numero_identificacion: clienteExistente.numero_identificacion || '',
        fecha_nacimiento: fechaNacimientoFormateada,
        genero: clienteExistente.genero || '',
        direccion: clienteExistente.direccion || '',
        telefono: clienteExistente.telefono || '',
        tipo_documento: clienteExistente.tipo_documento || 'DUI'
      }

      // Verificar si la fecha de nacimiento necesita corrección
      const hoy = new Date()
      const fechaNac = new Date(clienteExistente.fecha_nacimiento)

      if (fechaNac >= hoy) {
        toast.add({
          severity: 'warn',
          summary: 'Revisar Datos',
          detail: 'Sus datos han sido precargados, pero la fecha de nacimiento debe ser corregida (debe ser anterior a hoy)',
          life: 6000
        })
      } else {
        toast.add({
          severity: 'info',
          summary: 'Datos Precargados',
          detail: 'Sus datos personales han sido cargados automáticamente. Puede modificarlos si es necesario.',
          life: 4000
        })
      }
    }
  } catch (error) {
    // Si no se pueden cargar los datos, continuamos sin datos precargados
    tieneClienteExistente.value = false
  }

  showReservaDialog.value = true
}

// Función para contactar por WhatsApp
const contactarHotel = () => {
  // Verificar roles para restricción
  if (user.value && user.value.roles && Array.isArray(user.value.roles)) {
    const tieneRolRestringido = user.value.roles.some(role =>
      role.name === 'Administrador' || role.name === 'Empleado'
    )

    if (tieneRolRestringido) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Solo las cuentas de Cliente pueden contactar hoteles por WhatsApp desde esta sección.',
        life: 5000
      })
      return
    }
  }

  const mensaje = `Hola, estoy interesado/a en obtener más información sobre el hotel "${hotel.value.nombre}" ubicado en ${hotel.value.direccion}. ¿Podrían proporcionarme detalles sobre disponibilidad, precios y servicios? Gracias.`
  const whatsappUrl = `https://wa.me/50379858777?text=${encodeURIComponent(mensaje)}`
  window.open(whatsappUrl, '_blank')
}

// Watch para manejar teléfono precargado
watch(() => reservaForm.value.cliente_data.telefono, (nuevoTelefono, telefonoAnterior) => {
  // Si hay un teléfono precargado y es diferente del anterior
  if (nuevoTelefono && nuevoTelefono !== telefonoAnterior && tieneClienteExistente.value) {
    // Marcar como válido si viene de datos precargados
    telefonoValidation.value = {
      isValid: true,
      country: { name: 'Válido', code: '' },
      formattedNumber: nuevoTelefono,
      mensaje: 'Número válido (guardado previamente)'
    }
  }
}, { immediate: true })

// Función para validar la unicidad del teléfono
const validarTelefonoUnico = async (telefono) => {
  if (!telefono) return { esValido: true, mensaje: '' }

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

    const response = await fetch('/api/clientes/validar-telefono', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ telefono })
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()

    return {
      esValido: data.disponible,
      mensaje: data.disponible ? 'Teléfono disponible' : data.mensaje || 'Este teléfono ya está registrado'
    }
  } catch (error) {
    return { esValido: true, mensaje: 'Error al validar teléfono' }
  }
}

// Función de validación del teléfono
const onValidate = async (phoneObject) => {
  try {
    if (phoneObject && typeof phoneObject === 'object') {
      telefonoValidation.value.isValid = phoneObject.valid === true
      telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode }
      telefonoValidation.value.formattedNumber = phoneObject.formatted || ''

      // Actualizar el modelo inmediatamente
      if (phoneObject.valid === true && phoneObject.formatted) {
        reservaForm.value.cliente_data.telefono = phoneObject.formatted
      }

      // Si los datos están precargados, no validar duplicados (ya son del mismo cliente)
      if (tieneClienteExistente.value) {
        if (phoneObject.valid === true) {
          telefonoValidation.value.mensaje = 'Número válido (guardado previamente)'
        } else if (reservaForm.value.cliente_data.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else {
          telefonoValidation.value.mensaje = ''
        }
      } else {
        // Solo validar duplicados para nuevos clientes
        if (reservaForm.value.cliente_data.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else if (phoneObject.valid === true) {
          // Validar con el teléfono ya actualizado en el modelo
          const validacionUnicidad = await validarTelefonoUnico(reservaForm.value.cliente_data.telefono)
          if (!validacionUnicidad.esValido) {
            telefonoValidation.value.isValid = false
            telefonoValidation.value.mensaje = validacionUnicidad.mensaje
          } else {
            telefonoValidation.value.mensaje = 'Número válido para ' + phoneObject.country
          }
        } else {
          telefonoValidation.value.mensaje = ''
        }
      }
    }
  } catch (error) {
    telefonoValidation.value.mensaje = 'Error en validación'
  }
}

// Función para crear reserva de hotel
const crearReservaHotel = async () => {
  try {
    procesandoReserva.value = true

    // Validaciones básicas
    if (!reservaForm.value.fecha_entrada || !reservaForm.value.fecha_salida) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'Las fechas de entrada y salida son requeridas',
        life: 4000
      })
      return
    }

    if (new Date(reservaForm.value.fecha_entrada) >= new Date(reservaForm.value.fecha_salida)) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'La fecha de salida debe ser posterior a la fecha de entrada',
        life: 4000
      })
      return
    }

    if (!reservaForm.value.cliente_data.numero_identificacion.trim()) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'El número de identificación es requerido',
        life: 4000
      })
      return
    }

    if (!reservaForm.value.cliente_data.telefono) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'El número de teléfono es requerido',
        life: 4000
      })
      return
    }

    // Solo validar formato si el teléfono fue modificado (no viene de datos precargados)
    if (reservaForm.value.cliente_data.telefono && telefonoValidation.value.isValid === false && telefonoValidation.value.mensaje !== 'Número válido (guardado previamente)') {
      // Si el mensaje contiene información sobre duplicado, usar mensaje específico
      if (telefonoValidation.value.mensaje && (telefonoValidation.value.mensaje.includes('registrado') || telefonoValidation.value.mensaje.includes('diferente'))) {
        toast.add({
          severity: 'warn',
          summary: 'Teléfono ya registrado',
          detail: telefonoValidation.value.mensaje,
          life: 5000
        })
      } else {
        toast.add({
          severity: 'error',
          summary: 'Teléfono inválido',
          detail: 'Por favor, ingrese un número de teléfono válido',
          life: 4000
        })
      }
      return
    }

    // Validar cantidad de personas (máximo 10)
    if (reservaForm.value.cantidad_personas > 10) {
      toast.add({
        severity: 'error',
        summary: 'Cantidad de personas excede el límite',
        detail: 'La cantidad máxima de personas permitida es 10',
        life: 4000
      })
      return
    }

    // Validar fecha de nacimiento (debe ser anterior a hoy)
    if (reservaForm.value.cliente_data.fecha_nacimiento) {
      const hoy = new Date()
      hoy.setHours(0, 0, 0, 0) // Resetear horas para comparar solo fechas
      const fechaNacimiento = new Date(reservaForm.value.cliente_data.fecha_nacimiento)
      fechaNacimiento.setHours(0, 0, 0, 0)

      if (fechaNacimiento >= hoy) {
        toast.add({
          severity: 'error',
          summary: 'Fecha de nacimiento inválida',
          detail: `La fecha de nacimiento debe ser anterior a hoy (${hoy.toLocaleDateString()}). Por favor, corrija la fecha.`,
          life: 6000
        })
        return
      }
    }

    // Preparar datos para enviar
    const datosReserva = {
      hotel_id: hotel.value.id,
      fecha_entrada: reservaForm.value.fecha_entrada,
      fecha_salida: reservaForm.value.fecha_salida,
      cantidad_personas: reservaForm.value.cantidad_personas,
      cantidad_habitaciones: reservaForm.value.cantidad_habitaciones,
      cliente_data: reservaForm.value.cliente_data
    }

    // Llamada a la API para crear la reserva de hotel
    const response = await fetch('/api/reservas/hotel', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'same-origin',
      body: JSON.stringify(datosReserva)
    })

    if (!response.ok) {
      const errorData = await response.json()

      // Si hay errores de validación específicos, mostrarlos
      if (errorData.errors) {
        const errores = Object.values(errorData.errors).flat().join(', ')
        throw new Error(`Error de validación: ${errores}`)
      }

      throw new Error(errorData.message || 'Error al crear la reserva')
    }

    const data = await response.json()

    toast.add({
      severity: 'success',
      summary: 'Reserva Creada',
      detail: 'Su reserva ha sido creada exitosamente. Recibirá una confirmación por email.',
      life: 6000
    })

    showReservaDialog.value = false

  } catch (error) {
    console.error('Error al crear reserva de hotel:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'No se pudo crear la reserva. Intente nuevamente.',
      life: 4000
    })
  } finally {
    procesandoReserva.value = false
  }
}

// Función para cerrar el modal de reserva
const cerrarModalReserva = () => {
  showReservaDialog.value = false
}

// Función para obtener fecha mínima (hoy)
const getFechaMinima = () => {
  return new Date()
}

// Función para obtener fecha mínima de salida (día después de entrada)
const getFechaMinimaComputada = computed(() => {
  if (!reservaForm.value.fecha_entrada) return getFechaMinima()

  const fechaEntrada = new Date(reservaForm.value.fecha_entrada)
  fechaEntrada.setDate(fechaEntrada.getDate() + 1)
  return fechaEntrada
})

// Función para obtener fecha máxima de nacimiento (hace 18 años)
const getFechaMaximaNacimiento = () => {
  const hoy = new Date()
  hoy.setFullYear(hoy.getFullYear() - 18)
  return hoy
}

// Lifecycle hooks
onMounted(() => {
  iniciarCarrusel()
})

onUnmounted(() => {
  detenerCarrusel()
})

// Función para regresar
const regresar = () => {
  router.visit('/reservaciones')
}
</script>
