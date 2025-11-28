<template>
  <Catalogo>
    <div class="min-h-screen bg-gray-50 py-4 sm:py-8 mt-24 md:mt-32 lg:mt-32 xl:mt-32">
      <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-4 sm:mb-8">
          <ol class="flex items-center space-x-1 sm:space-x-2 text-xs sm:text-sm">
            <li>
              <Link
                href="/"
                :class="tipo === 'nacional' ? 'text-red-600 hover:text-red-800' : 'text-blue-600 hover:text-blue-800'"
              >
                Inicio
              </Link>
            </li>
            <li class="text-gray-500">/</li>
            <li>
              <Link
                :href="tipo === 'nacional' ? '/tours-nacionales' : '/tours-internacionales'"
                :class="tipo === 'nacional' ? 'text-red-600 hover:text-red-800' : 'text-blue-600 hover:text-blue-800'"
              >
                {{ tipo === 'nacional' ? 'Tours Nacionales' : 'Tours Internacionales' }}
              </Link>
            </li>
            <li class="text-gray-500">/</li>
            <li class="text-gray-900 font-medium truncate">{{ tour.nombre }}</li>
          </ol>
        </nav>

        <!-- Estado de carga -->
        <div v-if="loading" class="bg-white rounded-lg shadow-lg overflow-hidden p-8">
          <div class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2" :class="tipo === 'nacional' ? 'border-red-600' : 'border-blue-600'"></div>
            <span class="ml-3 text-gray-600">Cargando detalles del tour...</span>
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
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error al cargar el tour</h3>
            <p class="text-gray-600 mb-4">{{ error }}</p>
            <button
              @click="obtenerTour"
              :class="tipo === 'nacional' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
              class="px-4 py-2 text-white rounded-lg transition-colors"
            >
              Reintentar
            </button>
          </div>
        </div>

        <!-- Contenido principal -->
        <div v-else-if="tour" class="bg-white rounded-lg shadow-lg overflow-hidden">
          <!-- Galería de imágenes -->
          <div
            class="relative w-full h-64 sm:h-72 md:h-80 lg:h-96 bg-gray-100 rounded-t-lg overflow-hidden flex items-center justify-center"
            @mouseenter="detenerCarrusel"
            @mouseleave="iniciarCarrusel"
          >
            <div v-if="tour.imagenes && tour.imagenes.length > 0" class="relative w-full h-full flex items-center justify-center">
              <img
                :src="obtenerImagenActual()"
                :alt="tour.nombre"
                class="max-w-full max-h-full object-contain transition-opacity duration-500"
              />

              <!-- Controles de navegación de imágenes -->
              <div v-if="tour.imagenes.length > 1" class="absolute inset-0 flex items-center justify-between p-2 sm:p-4 pointer-events-none">
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
              <div v-if="tour.imagenes.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10">
                <div class="flex space-x-2">
                  <button
                    v-for="(imagen, index) in tour.imagenes"
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

          <!-- Información del tour -->
          <div class="p-4 sm:p-6 md:p-8">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
              <!-- Información principal -->
              <div class="order-2 xl:order-1">
                <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-4 mb-3 sm:mb-4">
                  <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">{{ tour.nombre }}</h1>
                  <span v-if="esTourCompleto(tour)" class="inline-flex items-center px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-red-600 text-white animate-pulse shadow-md">
                    <i class="pi pi-times-circle mr-2"></i>
                    {{ tour.estado === 'EN_CURSO' ? 'EN CURSO' : 'COMPLETO' }}
                  </span>
                </div>

                <div class="mb-4 sm:mb-6">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-blue-100 text-blue-800">
                    {{ tour.categoria === 'NACIONAL' ? 'Nacional' : tour.categoria === 'INTERNACIONAL' ? 'Internacional' : 'Categoría no asignada' }}
                  </span>
                </div>

              <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-calendar mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Fecha de salida:</strong> {{ formatearFecha(tour.fecha_salida) }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-calendar mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Fecha de regreso:</strong> {{ formatearFecha(tour.fecha_regreso) }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-clock mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Duración:</strong> {{ calcularDuracion(tour.fecha_salida, tour.fecha_regreso) }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-map-marker mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Punto de salida:</strong> {{ tour.punto_salida }}</span>
                  </div>
                  <div v-if="tour.transporte" class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-car mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Transporte:</strong> {{ tour.transporte.nombre }}</span>
                  </div>
                  <div v-if="tour.transporte" class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-user-plus mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Cupo máximo:</strong> {{ tour.cupo_max }}</span>
                  </div>
                  <div v-if="tour.transporte" class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-user-minus mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Cupo mínimo:</strong> {{ tour.cupo_min }}</span>
                  </div>
                  <div :class="[
                    'flex items-start text-sm sm:text-base p-3 rounded-lg border-l-4 transition-all duration-300',
                    esTourCompleto(tour)
                      ? 'bg-red-50 border-red-500 text-red-700'
                      : 'bg-green-50 border-green-500 text-green-700'
                  ]">
                    <i :class="[
                      'mr-2 sm:mr-3 mt-0.5 text-sm sm:text-base',
                      esTourCompleto(tour) ? 'pi pi-times-circle text-red-600' : 'pi pi-users text-green-600'
                    ]"></i>
                    <div>
                      <span class="font-semibold">
                        {{ esTourCompleto(tour) ? 'Estado del Tour:' : 'Cupos disponibles:' }}&nbsp;
                      </span>
                      <div :class="[
                        'font-bold',
                        esTourCompleto(tour) ? 'text-red-600' : obtenerClaseCuposDetalle(tour)
                      ]">
                        {{ esTourCompleto(tour)
                          ? getMensajeEstadoTour(tour)
                          : `${tour.cupos_disponibles !== null && tour.cupos_disponibles !== undefined ? tour.cupos_disponibles : 0} cupos disponibles`
                        }}
                      </div>
                      {{ esTourCompleto(tour) ? getDescripcionEstado(tour) : '' }}
                    </div>
                  </div>
                </div>

                <!-- Precio -->
                <div class="mb-4 sm:mb-6 order-1 xl:order-none">
                  <div class="text-2xl sm:text-3xl font-bold text-red-600">
                    ${{ parseFloat(tour.precio).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                  </div>
                  <div class="text-gray-600 text-sm sm:text-base">Por persona</div>
                </div>

                <!-- Botón de reserva -->
                <button
                  @click="reservarTour"
                  :disabled="esTourCompleto(tour)"
                  :class="[
                    'w-3/4 mx-auto block font-semibold py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-all duration-300 text-sm sm:text-base shadow-lg hover:shadow-xl',
                    esTourCompleto(tour)
                      ? 'bg-gradient-to-r from-gray-300 via-gray-400 to-gray-600 text-white cursor-not-allowed opacity-60'
                      : tipo === 'nacional'
                        ? 'bg-gradient-to-r from-red-400 via-red-500 to-red-800 hover:from-red-500 hover:via-red-600 hover:to-red-900 text-white transform hover:scale-105 pulse-scale-tour'
                        : 'bg-gradient-to-r from-blue-400 via-blue-500 to-blue-800 hover:from-blue-500 hover:via-blue-600 hover:to-blue-900 text-white transform hover:scale-105 pulse-scale-tour'
                  ]"
                >
                  {{ esTourCompleto(tour) ? (tour.estado === 'EN_CURSO' ? 'Tour en Curso' : 'Tour Completo - Sin Cupos') : 'Reservar Tour' }}
                </button>
              </div>

              <!-- Detalles adicionales -->
              <div class="order-3 xl:order-2">
                <!-- Sección Lo que incluye / No incluye -->
                <div class="mb-4 sm:mb-6">
                  <!-- Layout móvil: lado a lado -->
                  <div class="sm:hidden">
                    <div class="grid grid-cols-2 gap-2">
                      <!-- Lo que incluye -->
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center">
                          <i class="pi pi-check-circle mr-1 text-blue-600 text-xs"></i>
                          <span class="truncate">Lo que incluye</span>
                        </h3>
                        <div class="bg-green-50 rounded-lg p-2">
                          <ul v-if="textoALista(tour.incluye).length > 0" class="space-y-1">
                            <li
                              v-for="(item, index) in textoALista(tour.incluye)"
                              :key="index"
                              class="text-gray-700 text-xs leading-tight flex items-start"
                            >
                              <span class="text-green-600 mr-1 mt-0.5 text-xs">
                                <FontAwesomeIcon :icon="faDotCircle" class="text-blue-600 mr-1"/>
                              </span>
                              <span>{{ item }}</span>
                            </li>
                          </ul>
                          <p v-else class="text-gray-500 text-xs italic">No hay información disponible</p>
                        </div>
                      </div>

                      <!-- Lo que NO incluye -->
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center">
                          <i class="pi pi-times-circle mr-1 text-red-600 text-xs"></i>
                          <span class="truncate">No incluye</span>
                        </h3>
                        <div class="bg-red-50 rounded-lg p-2">
                          <ul v-if="textoALista(tour.no_incluye).length > 0" class="space-y-1">
                            <li
                              v-for="(item, index) in textoALista(tour.no_incluye)"
                              :key="index"
                              class="text-gray-700 text-xs leading-tight flex items-start"
                            >
                              <span class="text-red-600 mr-1 mt-0.5 text-xs">
                                <FontAwesomeIcon :icon="faDotCircle" class="text-red-600 mr-1"/>
                              </span>
                              <span>{{ item }}</span>
                            </li>
                          </ul>
                          <p v-else class="text-gray-500 text-xs italic">No hay información disponible</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Layout desktop: vertical -->
                  <div class="hidden sm:block">
                    <!-- Lo que incluye -->
                    <div class="mb-4 sm:mb-6">
                      <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 sm:mb-3 flex items-center">
                        <i class="pi pi-check-circle mr-2 text-blue-600 text-sm sm:text-base"></i>
                        Lo que incluye
                      </h3>
                      <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                        <ul v-if="textoALista(tour.incluye).length > 0" class="space-y-2">
                          <li
                            v-for="(item, index) in textoALista(tour.incluye)"
                            :key="index"
                            class="text-gray-700 text-sm sm:text-base flex items-start"
                          >
                            <span class="text-green-600 mr-2 mt-1 text-sm">
                                <FontAwesomeIcon :icon="faDotCircle" class="text-blue-600 mr-1"/>
                            </span>
                            <span>{{ item }}</span>
                          </li>
                        </ul>
                        <p v-else class="text-gray-500 text-sm sm:text-base italic">No hay información disponible</p>
                      </div>
                    </div>

                    <!-- Lo que NO incluye -->
                    <div class="mb-4 sm:mb-6">
                      <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 sm:mb-3 flex items-center">
                        <i class="pi pi-times-circle mr-2 text-red-600 text-sm sm:text-base"></i>
                        Lo que NO incluye
                      </h3>
                      <div class="bg-red-50 rounded-lg p-3 sm:p-4">
                        <ul v-if="textoALista(tour.no_incluye).length > 0" class="space-y-2">
                          <li
                            v-for="(item, index) in textoALista(tour.no_incluye)"
                            :key="index"
                            class="text-gray-700 text-sm sm:text-base flex items-start"
                          >
                            <span class="text-red-600 mr-2 mt-1 text-sm">
                                <FontAwesomeIcon :icon="faDotCircle" class="text-red-600 mr-1"/>
                            </span>
                            <span>{{ item }}</span>
                          </li>
                        </ul>
                        <p v-else class="text-gray-500 text-sm sm:text-base italic">No hay información disponible</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Información importante en 3 columnas - Solo desktop - Ancho completo -->
          <div class="px-6 pb-6">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 sm:p-6 border border-blue-200 shadow-sm">
              <h3 class="text-lg sm:text-xl font-bold text-blue-900 mb-4 flex items-center">
                <i class="pi pi-info-circle mr-3 text-blue-600 text-lg"></i>
                Información Importante
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Acreditaciones -->
                <div class="p-3 bg-white/70 rounded-lg border-l-4 border-green-500 shadow-sm hover:shadow-lg transition-shadow duration-300">
                  <h4 class="font-semibold text-green-800 mb-2 text-sm flex items-center">
                    <span class="mr-1">
                        <FontAwesomeIcon :icon="faCertificate" class="text-green-500 mr-1"/>
                    </span>
                    Nuestras Acreditaciones
                  </h4>
                  <div class="space-y-1 text-green-700 text-xs">
                    <p>• Somos una marca registrada ®️</p>
                    <p>• Somos una Agencia respaldada por el MITUR y CORSATUR</p>
                    <p>• Poseemos Sello de Verificación de Protocolos de Bioseguridad</p>
                  </div>
                </div>

                <!-- Términos de Reserva -->
                <div class="p-3 bg-white/70 rounded-lg border-l-4 border-blue-500 shadow-sm hover:shadow-lg transition-shadow duration-300">
                  <h4 class="font-semibold text-blue-800 mb-2 text-sm flex items-center">
                    <span class="mr-1">
                        <FontAwesomeIcon :icon="faCreditCard" class="text-blue-500 mr-1"/>
                    </span>
                    Términos de Reserva
                  </h4>
                  <div class="space-y-1 text-blue-700 text-xs">
                    <p>• Tours sujetos a completar cupo mínimo</p>
                    <p>• Reservás tu cupo con el 50% y el resto el día del tour</p>
                  </div>
                </div>

                <!-- Políticas de Cancelación -->
                <div class="p-3 bg-white/70 rounded-lg border-l-4 border-orange-500 shadow-sm hover:shadow-lg transition-shadow duration-300">
                  <h4 class="font-semibold text-orange-800 mb-2 text-sm flex items-center">
                    <span class="mr-1">
                        <FontAwesomeIcon :icon="faClipboardList" class="text-orange-500 mr-1"/>
                    </span>
                    Políticas de Cancelación
                  </h4>
                  <div class="space-y-1 text-orange-700 text-xs">
                    <p class="font-medium text-green-600">• Si por cualquier motivo como organizadores cancelamos el tour, te devolvemos el total de tu dinero</p>
                    <p class="font-medium text-red-600">• Si no asistís en la fecha y hora indicada no hay devolución de tu reserva</p>
                    <p class="font-medium text-red-600">• Para cancelaciones con menos de 72 horas antes del tour, no hay devolución de tu reserva</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón para regresar -->
        <div class="mt-4 sm:mt-8 text-center px-4">
          <button
            @click="regresar"
            :class="[
              'inline-flex items-center text-white text-sm sm:text-base py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors duration-200',
              tipo === 'nacional'
                ? 'bg-red-400 hover:bg-red-500'
                : 'bg-blue-400 hover:bg-blue-500'
            ]"
          >
            <i class="pi pi-arrow-left mr-2"></i>
            Regresar a Tours
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de reserva de tour usando el componente reutilizable -->
    <Toast />
    <ModalReservaTour
      v-model:visible="showReservaDialog"
      :tour-seleccionado="tour"
      :user="user"
      @confirmar-reserva="manejarConfirmacionReserva"
      @actualizar-cupos="actualizarCupos"
      @refrescar-tour="refrescarTour"
    />

    <!-- Modal de autenticación requerida -->
    <ModalAuthRequerido
      v-model:visible="showAuthDialog"
      :tour-info="tour"
    />
  </Catalogo>
</template>

<script setup>
import Catalogo from '../Catalogo.vue'
import ModalReservaTour from './Modales/ModalReservaTour.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faC, faCertificate, faCheck, faClipboardList, faCreditCard, faDotCircle } from '@fortawesome/free-solid-svg-icons'

const page = usePage()
const user = computed(() => page.props.auth.user)

const toast = useToast()

// Variables para el modal de reserva de tour
const showReservaDialog = ref(false)
const showAuthDialog = ref(false)

// Props
const props = defineProps({
  tour: {
    type: Object,
    required: true
  },
  tipo: {
    type: String,
    required: true,
    validator: value => ['nacional', 'internacional'].includes(value)
  }
})

// Estados reactivos
const tourData = ref({})
const loading = ref(true)
const error = ref(null)

// URL de la API
const url = "/api/tours"

// Variables para la galería de imágenes
const currentImageIndex = ref(0)
const carruselInterval = ref(null)

// Computed para obtener el tour actual (API o props como fallback)
const tour = computed(() => {
  return Object.keys(tourData.value).length > 0 ? tourData.value : props.tour
})

// Función para obtener el tour desde la API
const obtenerTour = async () => {
  if (!props.tour?.id) {
    loading.value = false
    return
  }

  try {
    loading.value = true
    error.value = null

    const tourId = props.tour.id
    const apiUrl = `${url}/${tourId}`

    const response = await fetch(apiUrl, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()

    // Asegurar que el tour tenga cupos_disponibles
    if (data.cupos_disponibles === undefined || data.cupos_disponibles === null) {
      data.cupos_disponibles = data.cupo_max || 0
    }

    tourData.value = data

  } catch (err) {
    console.error('Error al obtener el tour:', err)
    error.value = err.message
    // Usar props como fallback si hay error en la API
    tourData.value = props.tour || {}
  } finally {
    loading.value = false
  }
}

// Función para formatear fecha con hora
const formatearFecha = (fecha) => {
  if (!fecha) return ''
  const date = new Date(fecha)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

// Función para calcular duración
const calcularDuracion = (fechaSalida, fechaRegreso) => {
  if (!fechaSalida || !fechaRegreso) return '1 día'

  const salida = new Date(fechaSalida)
  const regreso = new Date(fechaRegreso)

  // Calcular diferencia en milisegundos considerando las horas exactas
  const diffTime = regreso.getTime() - salida.getTime()

  // Si la diferencia es negativa o cero, es el mismo día o error
  if (diffTime <= 0) return '1 día'

  // Calcular días completos
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  return diffDays === 1 ? '1 día' : `${diffDays} días`
}

// Función para verificar si un tour está completo o no disponible para reservar
const esTourCompleto = (tour) => {
  const cuposDisponibles = parseInt(tour.cupos_disponibles) || 0
  return cuposDisponibles <= 0 || tour.estado === 'COMPLETO' || tour.estado === 'EN_CURSO'
}

// Función para obtener el mensaje del estado del tour
const getMensajeEstadoTour = (tour) => {
  if (tour.estado === 'EN_CURSO') {
    return 'EN CURSO - Tour iniciado'
  } else if (tour.estado === 'COMPLETO' || (tour.cupos_disponibles !== null && tour.cupos_disponibles <= 0)) {
    return 'COMPLETO - 0 cupos disponibles'
  }
  return 'DISPONIBLE'
}

// Función para obtener descripción del estado
const getDescripcionEstado = (tour) => {
  if (tour.estado === 'EN_CURSO') {
    return 'Este tour ya ha comenzado y no acepta nuevas reservas.'
  } else if (esTourCompleto(tour)) {
    return 'Este tour ha alcanzado su capacidad máxima.'
  }
  return ''
}

// Función para convertir texto a lista
const textoALista = (texto) => {
  if (!texto) return []
  return texto.split('|').filter(item => item.trim()).map(item => item.trim())
}

// Función para obtener la imagen actual
const obtenerImagenActual = () => {
  if (!tour.value?.imagenes || tour.value.imagenes.length === 0) {
    return 'https://via.placeholder.com/800x500/2563eb/ffffff?text=Sin+Imagen+Disponible'
  }

  const imagen = tour.value.imagenes[currentImageIndex.value]
  const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre

  return `/storage/tours/${nombreImagen}`
}

// Funciones de navegación de imágenes
const imagenAnterior = () => {
  if (tour.value.imagenes && tour.value.imagenes.length > 1) {
    currentImageIndex.value = currentImageIndex.value === 0
      ? tour.value.imagenes.length - 1
      : currentImageIndex.value - 1
    iniciarCarrusel() // Reiniciar el carrusel después de navegación manual
  }
}

const imagenSiguiente = () => {
  if (tour.value.imagenes && tour.value.imagenes.length > 1) {
    currentImageIndex.value = (currentImageIndex.value + 1) % tour.value.imagenes.length
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
  if (tour.value.imagenes && tour.value.imagenes.length > 1) {
    carruselInterval.value = setInterval(() => {
      currentImageIndex.value = (currentImageIndex.value + 1) % tour.value.imagenes.length
    }, 3000) // Cambiar cada 3 segundos
  }
}

const detenerCarrusel = () => {
  if (carruselInterval.value) {
    clearInterval(carruselInterval.value)
    carruselInterval.value = null
  }
}

// Función para reservar el tour
const reservarTour = () => {
  // Verificar si el usuario está logueado
  if (!user.value) {
    showAuthDialog.value = true
  } else {
    // Verificar roles para restricción
    if (user.value.roles && Array.isArray(user.value.roles)) {
      const tieneRolRestringido = user.value.roles.some(role =>
        role.name === 'Administrador' || role.name === 'Empleado'
      )

      if (tieneRolRestringido) {
        toast.add({
          severity: 'warn',
          summary: 'Acceso Restringido',
          detail: 'Solo las cuentas de Cliente pueden realizar reservas. Los detalles del tour están disponibles para consulta.',
          life: 5000
        })
        return
      }
    }

    showReservaDialog.value = true
  }
}

// Eliminado: verificarReservaPendiente y su lógica de sessionStorage
// La reanudación de reservas mediante sessionStorage ha sido retirada (Option B).

// Función para manejar la confirmación de reserva desde el componente hijo
const manejarConfirmacionReserva = (reserva) => {
  // Cerrar modal (el toast ya se muestra en el modal)
  showReservaModal.value = false
}

// Función para actualizar cupos dinámicamente
const actualizarCupos = (datosActualizacion) => {
  const { tourId, cuposDisponibles } = datosActualizacion

  // Actualizar los cupos disponibles en tourData si es el mismo tour
  if (tourData.value.id === tourId) {
    tourData.value.cupos_disponibles = cuposDisponibles
  }
}

// Función para refrescar el tour actual desde la API
const refrescarTour = async () => {
  if (!props.tour?.id) return

  try {
    const tourId = props.tour.id
    const response = await fetch(`/api/tours/${tourId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      }
    })

    if (response.ok) {
      const tourActualizado = await response.json()
      tourData.value = tourActualizado
    }
  } catch (error) {
    console.error('Error al refrescar tour:', error)
  }
}

// Watch para verificar reserva pendiente cuando el usuario cambie
// Eliminado: watcher que invocaba verificarReservaPendiente (ya no es necesario)

// Lifecycle hooks
onMounted(async () => {
  await obtenerTour()
  iniciarCarrusel()
})

onUnmounted(() => {
  detenerCarrusel()
})

// Función para obtener la clase CSS según disponibilidad de cupos en detalle
const obtenerClaseCuposDetalle = (tour) => {
  const cuposDisponibles = tour.cupos_disponibles !== null && tour.cupos_disponibles !== undefined ? tour.cupos_disponibles : 0
  const cupoMax = tour.cupo_max || 1
  const porcentajeDisponible = (cuposDisponibles / cupoMax) * 100

  if (cuposDisponibles === 0) {
    return 'text-red-600 font-bold' // Sin cupos
  } else if (porcentajeDisponible <= 20) {
    return 'text-orange-600 font-bold' // Pocos cupos
  } else if (porcentajeDisponible <= 50) {
    return 'text-yellow-600 font-semibold' // Moderados cupos
  } else {
    return 'text-green-600 font-medium' // Muchos cupos
  }
}

// Función para regresar
const regresar = () => {
  const ruta = props.tipo === 'nacional' ? '/tours-nacionales' : '/tours-internacionales'
  router.visit(ruta)
}
</script>

<style scoped>
@keyframes pulse-scale {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
}

@keyframes shine {
  0% {
    left: -100%;
    opacity: 0;
  }
  50% {
    opacity: 1;
  }
  100% {
    left: 100%;
    opacity: 0;
  }
}

.pulse-scale-tour {
  animation: pulse-scale 2.8s ease-in-out infinite;
  position: relative;
  overflow: hidden;
}

.pulse-scale-tour::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.7), transparent);
  z-index: 10;
  pointer-events: none;
  transform: skewX(-20deg);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.pulse-scale-tour:hover::after {
  animation: shine 1.5s ease-in-out;
}
</style>
