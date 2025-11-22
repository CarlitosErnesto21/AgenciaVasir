<script setup>
import Catalogo from '../Catalogo.vue'
import ModalReservaTour from './Modales/ModalReservaTour.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { router, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCalendarAlt, faChevronLeft, faChevronRight, faImage, faMapMarkerAlt, faPause, faPlay, faXmark, faSearch, faTimes, faVolcano, faExclamationTriangle, faMapSigns, faLeaf, faHandshake } from '@fortawesome/free-solid-svg-icons'

// Recibir los props del controlador (opcional, como fallback)
const props = defineProps({
  tours: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const user = computed(() => page.props.auth.user)

const toast = useToast()

// Estados reactivos principales (movidos al inicio para evitar problemas de referencia)
const tours = ref([])
const loading = ref(true)
const error = ref(null)

// Variable para búsqueda
const searchQuery = ref('')

// Variables para el modal de reserva de tour
const showReservaDialog = ref(false)
const showAuthDialog = ref(false)
const tourSeleccionado = ref(null)

const reservarTour = (tour) => {
  tourSeleccionado.value = tour

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
          detail: 'Solo las cuentas de Cliente pueden realizar reservas. Puedes ver los detalles del tour usando el botón "Info".',
          life: 5000
        })
        return
      }
    }

    showReservaDialog.value = true
  }
}

// Función para manejar la confirmación de reserva desde el componente hijo
const manejarConfirmacionReserva = (reserva) => {
  toast.add({
    severity: 'success',
    summary: 'Reserva Confirmada',
    detail: 'Tu reserva ha sido registrada. Te contactaremos pronto.',
    life: 5000
  })

  // Cerrar modal
  showReservaDialog.value = false
}

// Función para actualizar cupos dinámicamente
const actualizarCupos = (datosActualizacion) => {
  const { tourId, cuposDisponibles } = datosActualizacion

  // Buscar y actualizar el tour en la lista
  const tourIndex = tours.value.findIndex(tour => tour.id === tourId)
  if (tourIndex !== -1) {
    tours.value[tourIndex].cupos_disponibles = cuposDisponibles

    // Forzar reactividad
    tours.value = [...tours.value]
  }
}

// Función para refrescar un tour específico desde la API
const refrescarTour = async (tourId) => {
  try {
    const response = await fetch(`/api/tours/${tourId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      }
    })

    if (response.ok) {
      const tourActualizado = await response.json()

      // Actualizar el tour en la lista
      const tourIndex = tours.value.findIndex(tour => tour.id === tourId)
      if (tourIndex !== -1) {
        tours.value[tourIndex] = tourActualizado
        tours.value = [...tours.value]
      }
    }
  } catch (error) {
    console.error('Error al refrescar tour:', error)
  }
}

// Watch para verificar reserva pendiente cuando el usuario cambie
// Eliminado: watcher que verificaba reservas pendientes via sessionStorage

// URL de la API para tours nacionales
const url = "/api/tours?categoria=nacional"

// Computed properties para estadísticas dinámicas
const estadisticas = computed(() => {
  if (!tours.value || tours.value.length === 0) {
    return {
      totalDestinos: 0,
      totalPaises: 0,
      precioMinimo: 0,
      paisesUnicos: []
    }
  }

  // Filtrar solo tours que tienen cupos disponibles
  const toursDisponibles = tours.value.filter(tour => {
    const cuposDisponibles = tour.cupos_disponibles !== null && tour.cupos_disponibles !== undefined ? tour.cupos_disponibles : 0
    return cuposDisponibles > 0
  })

  const precios = toursDisponibles.map(tour => parseFloat(tour.precio)).filter(precio => !isNaN(precio))
  // Como no tenemos campo pais aún, usaremos punto_salida como ubicación
  const ubicacionesUnicas = [...new Set(toursDisponibles.map(tour => tour.punto_salida).filter(ubicacion => ubicacion))]

  return {
    totalDestinos: toursDisponibles.length,
    totalPaises: ubicacionesUnicas.length,
    precioMinimo: precios.length > 0 ? Math.min(...precios) : 0,
    paisesUnicos: ubicacionesUnicas
  }
})

// Computed properties para separar tours por disponibilidad con filtro de búsqueda
const toursDisponibles = computed(() => {
  let filtrados = tours.value.filter(tour => {
    const cuposDisponibles = parseInt(tour.cupos_disponibles) || 0
    return cuposDisponibles > 0
  })

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    filtrados = filtrados.filter(tour => {
      const nombre = tour.nombre?.toLowerCase() || ''
      const pais = tour.pais?.nombre?.toLowerCase() || ''
      const ubicacion = tour.ubicacion?.toLowerCase() || ''
      const descripcion = tour.descripcion?.toLowerCase() || ''

      return nombre.includes(query) ||
             pais.includes(query) ||
             ubicacion.includes(query) ||
             descripcion.includes(query)
    })
  }

  return filtrados
})

const toursSinCupos = computed(() => {
  return tours.value.filter(tour => {
    const cuposDisponibles = parseInt(tour.cupos_disponibles) || 0
    return cuposDisponibles <= 0
  })
})

// Función para obtener tours desde la API
const obtenerTours = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await fetch(url, {
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

    // Asegurar que cada tour tenga cupos_disponibles
    const toursConCupos = (data.data || data || []).map(tour => {
      // Si no tiene cupos_disponibles, usar cupo_max como fallback temporal
      if (tour.cupos_disponibles === undefined || tour.cupos_disponibles === null) {
        tour.cupos_disponibles = tour.cupo_max || 0
      }
      return tour
    })

    tours.value = toursConCupos

  } catch (err) {
    console.error('Error al obtener tours:', err)
    error.value = err.message
    // Usar props como fallback si hay error en la API
    tours.value = props.tours || []
  } finally {
    loading.value = false
  }
}

// Función para formatear la fecha con hora
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

// Función para formatear la duración
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

  return diffDays === 1 ? '1 día' : `${diffDays} días`;
}

// Variables para el carrusel de imágenes
const showImageDialog = ref(false)
const selectedTourImages = ref([])
const currentImageIndex = ref(0)
const galleryIntervalId = ref(null)
const isGalleryAutoPlaying = ref(true)

// Variables para carruseles automáticos en las cards
const cardImageIndices = ref({})
const intervalIds = ref({})

// Función para obtener la imagen actual del carrusel automático
const obtenerImagenActual = (tour) => {
  if (!tour.imagenes || tour.imagenes.length === 0) {
    return 'https://via.placeholder.com/400x300/ef4444/ffffff?text=Sin+Imagen'
  }

  // Asegurarse de que el tour tenga un ID para el índice
  if (!tour.id) return `/storage/tours/${typeof tour.imagenes[0] === 'string' ? tour.imagenes[0] : tour.imagenes[0].nombre}`;

  // Si solo tiene una imagen, mostrar esa
  if (tour.imagenes.length === 1) {
    const nombreImagen = typeof tour.imagenes[0] === 'string' ? tour.imagenes[0] : tour.imagenes[0].nombre
    return `/storage/tours/${nombreImagen}`
  }

  // Si tiene múltiples imágenes, usar el índice del carrusel
  const currentIndex = cardImageIndices.value[tour.id] || 0
  const imagen = tour.imagenes[currentIndex]
  const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre

  return `/storage/tours/${nombreImagen}`;
}

// Función para inicializar carrusel automático
const inicializarCarrusel = (tour) => {
  if (!tour.imagenes || tour.imagenes.length <= 1 || !tour.id) return

  // Inicializar índice si no existe
  if (!(tour.id in cardImageIndices.value)) {
    cardImageIndices.value[tour.id] = 0
  }

  // Crear intervalo para cambiar imágenes automáticamente
  if (!(tour.id in intervalIds.value)) {
    intervalIds.value[tour.id] = setInterval(() => {
      // Validación adicional de seguridad
      if (tour.imagenes && tour.imagenes.length > 0) {
        cardImageIndices.value[tour.id] = (cardImageIndices.value[tour.id] + 1) % tour.imagenes.length
      }
    }, 3000) // Cambiar cada 3 segundos
  }
}

// Función para detener todos los carruseles
const detenerTodosLosCarruseles = () => {
  Object.keys(intervalIds.value).forEach(tourId => {
    clearInterval(intervalIds.value[tourId])
  })
  intervalIds.value = {}
  detenerCarruselGaleria() // También detener el carrusel de la galería
}

// Lifecycle hooks
onMounted(async () => {
  // Obtener tours desde la API
  await obtenerTours()

  // Inicializar carruseles para todos los tours con múltiples imágenes
  tours.value.forEach(tour => {
    if (tour.imagenes && tour.imagenes.length > 1) {
      inicializarCarrusel(tour)
    }
  })
})

onUnmounted(() => {
  detenerTodosLosCarruseles()
})

// Función para obtener todas las imágenes
const obtenerTodasLasImagenes = (imagenes) => {
  if (!imagenes || imagenes.length === 0) {
    return ['https://via.placeholder.com/400x300/ef4444/ffffff?text=Sin+Imagen']
  }

  return imagenes.map(imagen => {
    const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre
    return `/storage/tours/${nombreImagen}`;
  })
}

// Función para mostrar galería de imágenes
const mostrarGaleria = (tour) => {
  selectedTourImages.value = obtenerTodasLasImagenes(tour.imagenes)
  currentImageIndex.value = 0
  showImageDialog.value = true
  isGalleryAutoPlaying.value = false

  // No iniciar carrusel automático en la galería
}

// Función para alternar play/pausa del carrusel
const toggleGalleryAutoPlay = () => {
  if (isGalleryAutoPlaying.value) {
    detenerCarruselGaleria()
    isGalleryAutoPlaying.value = false
  } else {
    iniciarCarruselGaleria()
    isGalleryAutoPlaying.value = true
  }
}

// Función para iniciar carrusel automático en la galería
const iniciarCarruselGaleria = () => {
  detenerCarruselGaleria() // Limpiar cualquier intervalo anterior
  galleryIntervalId.value = setInterval(() => {
    siguienteImagen()
  }, 4000) // Cambiar cada 4 segundos
}

// Función para detener carrusel automático en la galería
const detenerCarruselGaleria = () => {
  if (galleryIntervalId.value) {
    clearInterval(galleryIntervalId.value)
    galleryIntervalId.value = null
  }
}

// Funciones para navegar en el carrusel (navegación manual únicamente)
const siguienteImagen = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % selectedTourImages.value.length
}

const imagenAnterior = () => {
  currentImageIndex.value = currentImageIndex.value === 0
    ? selectedTourImages.value.length - 1
    : currentImageIndex.value - 1
}

const irAImagen = (index) => {
  currentImageIndex.value = index
}

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  searchQuery.value = ''
}

// Función para obtener la clase CSS según disponibilidad de cupos
const obtenerClaseCupos = (tour) => {
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
    return 'text-green-600' // Muchos cupos
  }
}

// Funciones para los botones
const verMasInfo = (tour) => {
  // Navegar a la vista detallada del tour nacional
  router.visit(`/tours-nacionales/${tour.id}`)
}
</script>

<template>
  <Catalogo>
    <Toast />
    <!-- Header Profesional con Stats Integradas - Ancho completo de la pantalla -->
    <div class="w-full bg-gradient-to-br from-white to-gray-50 shadow-xl overflow-hidden border-b border-gray-200 mb-3 sm:mb-4 mt-20 sm:mt-20 md:mt-28 lg:mt-32 xl:mt-32">
      <!-- Header con gradiente -->
      <div class="bg-gradient-to-r from-red-500 via-blue-600 to-blue-600 text-white text-center py-4 sm:py-6">
        <div class="flex items-center justify-center gap-3 mb-1">
          <!-- <img src="/images/sv.png" alt="Bandera El Salvador" class="w-8 h-8 sm:w-12 sm:h-12 shadow-lg rounded-full border-2 border-white/30" /> -->
          <FontAwesomeIcon :icon="faVolcano" class="w-8 h-8 sm:w-12 sm:h-12 text-yellow-300 drop-shadow-lg" />
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
            Tours Nacionales
          </h1>
        </div>
        <p class="text-base sm:text-lg text-red-100 px-4">Descubre las maravillas de El Salvador.</p>
      </div>


    </div>

    <!-- Contenido principal con padding -->
    <div class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen px-4 sm:px-6 lg:px-8 pb-8">
      <div class="w-full max-w-7xl mx-auto">

        <!-- Estado de carga -->
        <div v-if="loading && tours.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-red-200 border-t-red-600 mb-4"></div>
            <p class="text-lg font-semibold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent">Cargando tours nacionales...</p>
            <p class="text-sm text-gray-500 mt-2">Preparando las mejores experiencias para ti</p>
          </div>
        </div>

        <!-- Estado de error -->
        <div v-else-if="error && tours.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 text-red-700 px-8 py-6 rounded-xl shadow-lg max-w-md mx-auto">
            <div class="text-4xl mb-4">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="w-12 h-12 text-yellow-500 mx-auto" />
            </div>
            <h3 class="text-xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent mb-3">No se pudieron cargar los tours</h3>
            <p class="text-sm text-red-600 leading-relaxed">Por favor, intenta recargar la página o contacta con nosotros.</p>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="!loading && tours.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-red-50 to-indigo-50 border-2 border-red-200 rounded-xl shadow-lg p-8 max-w-lg mx-auto">
            <div class="text-6xl mb-4">
                <FontAwesomeIcon :icon="faVolcano" class="w-16 h-16 text-indigo-500 mx-auto" />
            </div>
            <h3 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-indigo-600 bg-clip-text text-transparent mb-3">No hay tours nacionales disponibles</h3>
            <p class="text-gray-600 mb-4 leading-relaxed">Próximamente tendremos nuevos destinos nacionales.</p>
            <p class="text-sm text-gray-500">Mientras tanto, puedes explorar nuestros tours internacionales.</p>
          </div>
        </div>

        <!-- Barra de búsqueda optimizada -->
        <div v-if="tours.length > 0" class="bg-gradient-to-br from-white to-red-50 rounded-2xl p-4 shadow-lg border border-red-200 mb-6">
          <div class="max-w-xl mx-auto">
            <div class="text-center mb-3">
              <div class="flex items-center justify-center gap-2 mb-2">
                <h3 class="text-base font-bold bg-gradient-to-r from-red-600 to-purple-600 bg-clip-text text-transparent">
                  Explorar Tours Nacionales
                </h3>
              </div>
            </div>

            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <FontAwesomeIcon :icon="faSearch" class="w-4 h-4 text-red-400 group-focus-within:text-red-600 transition-colors" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar destinos, lugares, ubicaciones..."
                class="w-full pl-10 pr-10 py-3 text-gray-700 bg-white border-2 border-red-300 rounded-xl focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 transition-all duration-300 text-sm shadow-sm placeholder-gray-400"
              />
              <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button
                  @click="limpiarBusqueda"
                  class="text-gray-400 hover:text-red-500 transition-colors duration-200 p-1.5 rounded-full hover:bg-red-100"
                  title="Limpiar búsqueda"
                >
                  <FontAwesomeIcon :icon="faTimes" class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div class="mt-2 text-center">
              <p class="text-xs text-gray-600 bg-white/60 rounded-full px-3 py-1 inline-block">
                {{ searchQuery
                  ? `${toursDisponibles.length} resultado${toursDisponibles.length !== 1 ? 's' : ''} encontrado${toursDisponibles.length !== 1 ? 's' : ''}`
                  : `${toursDisponibles.length} destino${toursDisponibles.length !== 1 ? 's' : ''} disponible${toursDisponibles.length !== 1 ? 's' : ''}`
                }}
              </p>
            </div>
          </div>
        </div>

        <!-- Tours Disponibles -->
        <div v-if="toursDisponibles.length > 0" class="mb-8">

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="tour in toursDisponibles"
              :key="tour.id"
              class="bg-gradient-to-br from-white to-gray-50 hover:from-gray-50 hover:to-white border-2 border-gray-200 hover:border-red-300 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col min-h-[320px] sm:min-h-[340px] transform hover:-translate-y-2 hover:scale-[1.02] overflow-hidden rounded-xl"
              >
              <template #header>
                <div class="relative w-full h-28 sm:h-30 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-200"
                    @click="mostrarGaleria(tour)">
                  <img
                    :src="obtenerImagenActual(tour)"
                    :alt="tour.nombre"
                    class="object-contain h-full w-full bg-gradient-to-br from-gray-50 to-white group-hover:scale-110 transition-transform duration-500"
                    @error="$event.target.src = 'https://via.placeholder.com/300x200/ef4444/ffffff?text=' + encodeURIComponent((tour.nombre || 'Tour').substring(0, 15))"
                  />
                  <div class="absolute top-1.5 right-1.5 bg-gradient-to-r from-green-500 to-green-600 text-white px-2 py-0.5 rounded-full text-xs font-bold shadow-lg border border-green-400">
                    ${{ tour.precio }}
                  </div>
                  <div class="absolute bottom-1.5 left-1.5 bg-gradient-to-r from-black/80 to-gray-800/80 backdrop-blur-sm text-white px-2 py-0.5 rounded-full text-xs font-medium border border-white/20">
                    {{ calcularDuracion(tour.fecha_salida, tour.fecha_regreso) }}
                  </div>
                  <!-- Indicador de múltiples imágenes -->
                  <div v-if="tour.imagenes && tour.imagenes.length > 1"
                      class="absolute top-1.5 left-1.5 bg-gradient-to-r from-black/80 to-gray-800/80 backdrop-blur-sm text-white px-1.5 py-0.5 rounded-full text-xs flex items-center border border-white/20">
                    <FontAwesomeIcon :icon="faImage" class="w-3 h-3 mr-1" />
                    {{ tour.imagenes.length }}
                  </div>
                  <!-- Indicador de carrusel activo -->
                  <div v-if="tour.imagenes && tour.imagenes.length > 1"
                      class="absolute bottom-1.5 right-1.5 flex space-x-1">
                    <div
                      v-for="(_, index) in tour.imagenes"
                      :key="index"
                      class="w-1.5 h-1.5 rounded-full transition-all duration-300 border border-white/50"
                      :class="(cardImageIndices[tour.id] || 0) === index ? 'bg-white shadow-md' : 'bg-white/60'"
                    ></div>
                  </div>
                  <!-- Overlay de hover -->
                  <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
              </template>

              <template #title>
                <div class="h-6 sm:h-8 flex items-start px-2.5 pt-1 cursor-pointer hover:bg-gradient-to-r hover:from-gray-50 hover:to-red-50 transition-all duration-300 rounded-lg mx-2"
                    @click="verMasInfo(tour)">
                  <span class="text-xs font-bold bg-gradient-to-r from-gray-800 to-gray-700 bg-clip-text text-transparent leading-tight line-clamp-2 hover:from-red-600 hover:to-blue-600 transition-all duration-300">{{ tour.nombre }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-2.5 pb-2.5 min-h-0">
                  <div class="flex-1 space-y-1 cursor-pointer hover:bg-gradient-to-br hover:from-gray-50 hover:to-blue-50 transition-all duration-300 rounded-lg p-1 -m-1"
                       @click="verMasInfo(tour)">
                    <div class="flex items-center text-xs text-gray-500 mb-0.5 bg-gray-50 rounded-lg p-1 border border-gray-100 shadow-sm">
                      <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3 mr-1 flex-shrink-0 text-red-500" />
                      <span class="truncate font-medium text-xs">{{ tour.punto_salida }}</span>
                    </div>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-1.5 border border-blue-100">
                      <div class="flex items-center gap-1 mb-0.5">
                        <FontAwesomeIcon :icon="faCalendarAlt" class="w-3 h-3 text-red-500 flex-shrink-0" />
                        <p class="text-xs font-bold bg-gradient-to-r from-red-600 to-red-600 bg-clip-text text-transparent">Fecha y hora de salida</p>
                      </div>
                      <p class="text-xs text-gray-700 font-semibold">{{ formatearFecha(tour.fecha_salida) }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-1.5 border border-green-100">
                      <div class="flex items-center gap-1 mb-0.5">
                        <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Cupos disponibles</p>
                      </div>
                      <p class="text-xs font-bold" :class="obtenerClaseCupos(tour)">
                        {{ tour.cupos_disponibles !== null && tour.cupos_disponibles !== undefined ? tour.cupos_disponibles : 0 }} cupos
                      </p>
                    </div>
                  </div>

                  <!-- Texto informativo -->
                  <div class="mt-1 mb-1 text-center">
                    <p class="text-xs text-gray-500 italic">Toca en cualquier parte para ver más detalles</p>
                  </div>

                  <!-- Botones profesionales - SIEMPRE VISIBLES -->
                  <div class="flex gap-1 mt-1.5 pt-1.5 border-t border-gray-100 flex-shrink-0">
                    <button
                      @click="reservarTour(tour)"
                      class="px-2 py-1 text-xs font-bold rounded-lg transition-all duration-300 flex-1 shadow-md hover:shadow-lg bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white transform hover:scale-105"
                      >
                        Reservar
                    </button>
                    <button
                        @click="verMasInfo(tour)"
                        class="border-2 border-red-500 text-red-600 hover:red-blue-700 px-2 py-1 text-xs font-bold rounded-lg hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg bg-white"
                      >
                        Info
                    </button>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Tours Sin Cupos -->
        <div v-if="toursSinCupos.length > 0" class="mb-8">
          <div class="bg-gradient-to-r from-gray-500 to-gray-600 text-white text-center py-4 px-6 rounded-t-xl mb-6">
            <h2 class="text-xl md:text-2xl font-bold">😔 Tours Sin Cupos Disponibles</h2>
            <p class="text-gray-200 text-sm mt-1">{{ toursSinCupos.length }} destino{{ toursSinCupos.length !== 1 ? 's' : '' }} temporalmente agotado{{ toursSinCupos.length !== 1 ? 's' : '' }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="tour in toursSinCupos"
              :key="tour.id"
              class="border-2 border-gray-300 bg-gray-50 opacity-75 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col min-h-[400px] sm:min-h-[450px] overflow-hidden rounded-xl"
              >
              <template #header>
                <div class="relative w-full h-36 sm:h-40 bg-gradient-to-br from-gray-200 via-gray-150 to-gray-300 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-300"
                    @click="mostrarGaleria(tour)">
                  <img
                    :src="obtenerImagenActual(tour)"
                    :alt="tour.nombre"
                    class="object-contain h-full w-full bg-gradient-to-br from-gray-100 to-gray-200 group-hover:scale-110 transition-transform duration-500 filter grayscale"
                    @error="$event.target.src = 'https://via.placeholder.com/300x200/6b7280/ffffff?text=' + encodeURIComponent((tour.nombre || 'Tour').substring(0, 15))"
                  />
                  <div class="absolute top-2 right-2 bg-gray-600 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg border border-gray-500">
                    ${{ tour.precio }}
                  </div>
                  <div class="absolute bottom-2 left-2 bg-red-600/90 text-white px-3 py-1 rounded-full text-xs font-bold border border-red-400">
                    SIN CUPOS
                  </div>
                  <!-- Indicador de múltiples imágenes -->
                  <div v-if="tour.imagenes && tour.imagenes.length > 1"
                      class="absolute top-2 left-2 bg-gradient-to-r from-black/80 to-gray-800/80 backdrop-blur-sm text-white px-2 py-1 rounded-full text-xs flex items-center border border-white/20">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                    {{ tour.imagenes.length }}
                  </div>
                </div>
              </template>

              <template #title>
                <div class="h-10 sm:h-12 flex items-start px-4 pt-3 cursor-pointer hover:bg-gray-100 transition-all duration-300 rounded-lg mx-2"
                    @click="verMasInfo(tour)">
                  <span class="text-xs sm:text-sm font-bold text-gray-600 leading-tight line-clamp-2">{{ tour.nombre }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-4 pb-4 min-h-0">
                  <div class="flex-1 space-y-2 cursor-pointer hover:bg-gray-100 transition-all duration-300 rounded-lg p-2 -m-2 border border-transparent hover:border-gray-200"
                      @click="verMasInfo(tour)">
                    <div class="flex items-center text-xs text-gray-500 mb-2 bg-gray-100 rounded-lg p-2">
                      <svg class="w-3 h-3 mr-2 flex-shrink-0 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                      </svg>
                      <span class="truncate font-medium">{{ tour.punto_salida }}</span>
                    </div>
                    <div class="space-y-2">
                      <div class="bg-gray-100 rounded-lg p-2 border border-gray-200">
                        <p class="text-xs font-bold text-gray-600 mb-1">📅 Fecha de Salida:</p>
                        <p class="text-xs text-gray-500 font-medium">{{ formatearFecha(tour.fecha_salida) }}</p>
                      </div>
                      <div class="bg-red-100 rounded-lg p-2 border border-red-200">
                        <p class="text-xs font-bold text-red-700 mb-1">👥 Disponibles:</p>
                        <p class="text-xs font-bold text-red-600">
                          0 cupos
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Texto informativo -->
                  <div class="mt-2 mb-1 text-center">
                    <p class="text-xs text-gray-500 italic">Clic para ver más detalles</p>
                  </div>

                  <!-- Botones profesionales - SIEMPRE VISIBLES -->
                  <div class="flex gap-2 mt-2 pt-3 border-t border-gray-200 flex-shrink-0">
                    <button
                      disabled
                      class="px-3 py-2 text-xs font-bold rounded-lg transition-all duration-300 flex-1 shadow-md bg-gray-400 text-white cursor-not-allowed"
                      >
                        Sin Cupos
                    </button>
                    <button
                        @click="verMasInfo(tour)"
                        class="border-2 border-gray-400 text-gray-600 hover:text-gray-700 px-3 py-2 text-xs font-bold rounded-lg hover:bg-gray-100 transition-all duration-300 shadow-md bg-white"
                      >
                        Info
                    </button>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Info adicional profesional -->
        <div class="w-full">
          <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
          <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-red-500 via-blue-600 to-blue-600 text-white text-center py-6">
              <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
                ¿Por qué elegir nuestros tours nacionales?
              </h2>
              <p class="text-base sm:text-lg text-red-100 px-4">Descubre El Salvador como nunca antes</p>
            </div>

            <!-- Contenido -->
            <div class="p-2 md:p-8">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-8">
                <div class="text-center bg-gradient-to-br from-white to-red-50 hover:from-red-50 hover:to-red-100 rounded-xl p-1 md:p-6  shadow-md hover:shadow-xl border-2 border-transparent hover:border-red-200 transform hover:-translate-y-2 hover:scale-105 transition-all duration-300">
                  <div class="text-lg md:text-5xl mb-4">
                    <FontAwesomeIcon :icon="faMapSigns" class="text-yellow-500"/>
                  </div>
                  <h3 class="text-sm md:text-xl font-bold bg-gradient-to-r from-red-600 to-red-700 bg-clip-text text-transparent mb-3">VIAJES HECHOS A TU MEDIDA</h3>
                  <p class="text-gray-700 leading-relaxed text-xs md:text-sm">En VASIR te guiamos en cada paso del viaje: desde la planificación hasta el regreso, con atención cercana y personalizada.</p>
                </div>
                <div class="text-center bg-gradient-to-br from-white to-green-50 hover:from-green-50 hover:to-green-100 rounded-xl p-1 md:p-6  shadow-md hover:shadow-xl border-2 border-transparent hover:border-green-200 transform hover:-translate-y-2 hover:scale-105 transition-all duration-300">
                  <div class="text-lg md:text-5xl mb-4">
                    <FontAwesomeIcon :icon="faLeaf" class="text-green-500"/>
                  </div>
                  <h3 class="text-sm md:text-xl font-bold bg-gradient-to-r from-green-600 to-green-700 bg-clip-text text-transparent mb-3">EXPERIENCIAS AUTÉNTICAS Y RESPONSABLES</h3>
                  <p class="text-gray-700 leading-relaxed text-xs md:text-sm">Diseñamos viajes enfocados en la sostenibilidad, la cultura y el turismo naranja, para que vivás experiencias reales y con impacto positivo.</p>
                </div>
                <div class="text-center bg-gradient-to-br from-white to-blue-50 hover:from-blue-50 hover:to-blue-100 rounded-xl p-1 md:p-6 shadow-md hover:shadow-xl border-2 border-transparent hover:border-blue-200 transform hover:-translate-y-2 hover:scale-105 transition-all duration-300">
                  <div class="text-lg md:text-5xl mb-4">
                    <FontAwesomeIcon :icon="faHandshake" class="text-blue-500"/>
                  </div>
                  <h3 class="text-sm md:text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent mb-3">CALIDAD Y CONFIANZA</h3>
                  <p class="text-gray-700 leading-relaxed text-xs md:text-sm">Trabajamos con aliados verificados y logística clara, asegurando tranquilidad, comodidad y una experiencia sin complicaciones.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Diálogo profesional para mostrar galería de imágenes -->
      <Dialog
        v-model:visible="showImageDialog"
        modal
        :closable="false"
        class="max-w-3xl w-full md:w-full mx-4 z-[99999] mt-16 sm:mt-24 md:mt-24 lg:mt-24 xl:mt-24 2xl:mt-24"
        :draggable="false"
        :pt="{
          root: { class: 'z-[99999]' },
          mask: { class: 'z-[99999]' },
          content: { class: 'p-0 overflow-hidden rounded-lg z-[99999]' },
          header: { class: 'p-0 border-none z-[99999]' }
        }"
      >
        <template #header>
          <div class="w-full text-blue-500 p-4 rounded-lg flex items-center justify-between">
            <h3 class="text-md md:text-xl font-bold">Imágenes del tour</h3>
            <div class="flex items-center gap-3">
              <!-- Botón de cerrar personalizado y visible -->
              <button
                @click="showImageDialog = false"
                class="flex items-center justify-center w-8 h-8 bg-gradient-to-r from-gray-500/80 to-gray-600/80 backdrop-blur-sm hover:from-gray-600/90 hover:to-gray-700/90 text-white rounded-full transition-all border border-gray-300/30 shadow-lg transform hover:scale-110"
                title="Cerrar galería"
              >
                <FontAwesomeIcon :icon="faXmark" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </template>

        <div class="bg-gradient-to-br from-gray-50 to-white p-0">
          <!-- Imagen principal -->
          <div class="relative h-72 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl overflow-hidden mb-6 flex items-center justify-center shadow-lg border-2 border-gray-200">
            <img
              :src="selectedTourImages[currentImageIndex]"
              alt="Tour imagen"
              class="max-w-full max-h-full object-contain rounded-lg"
            />

            <!-- Botones de navegación profesionales -->
            <div v-if="selectedTourImages.length > 1" class="absolute inset-0 flex items-center justify-between p-0 md:p-12">
              <button
                @click="imagenAnterior"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-3 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all z-10 border border-white/20 shadow-lg transform hover:scale-110"
              >
                <FontAwesomeIcon :icon="faChevronLeft" class="w-6 h-4"/>
              </button>
              <button
                @click="siguienteImagen"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-3 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all z-10 border border-white/20 shadow-lg transform hover:scale-110"
              >
                <FontAwesomeIcon :icon="faChevronRight" class="w-6 h-4"/>
              </button>
            </div>

            <!-- Contador de imágenes profesional -->
            <div v-if="selectedTourImages.length > 1"
                class="absolute bottom-4 right-4 bg-gradient-to-r from-black/80 to-gray-800/80 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium z-10 border border-white/20 shadow-lg">
              {{ currentImageIndex + 1 }} / {{ selectedTourImages.length }}
            </div>

            <!-- Indicador de autoplay profesional -->
            <div v-if="selectedTourImages.length > 1 && isGalleryAutoPlaying"
                class="absolute top-4 right-4 bg-gradient-to-r from-red-500/90 to-red-600/90 backdrop-blur-sm text-white px-3 py-2 rounded-full text-xs font-medium z-10 flex items-center gap-2 border border-green-300/30 shadow-lg">
              <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
              <span>Auto</span>
            </div>
          </div>

          <!-- Miniaturas profesionales -->
          <div v-if="selectedTourImages.length > 1" class="flex gap-3 overflow-x-auto pb-2 px-2">
            <button
              v-for="(imagen, index) in selectedTourImages"
              :key="index"
              @click="irAImagen(index)"
              class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-3 transition-all bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center shadow-md hover:shadow-lg transform hover:scale-110"
              :class="currentImageIndex === index ? 'border-red-500 ring-2 ring-red-300' : 'border-gray-300 hover:border-gray-400'"
            >
              <img
                :src="imagen"
                :alt="`Miniatura ${index + 1}`"
                class="max-w-full max-h-full object-contain rounded"
              />
            </button>
          </div>
        </div>
      </Dialog>

      <!-- Modal de reserva de tour usando el componente reutilizable -->
      <ModalReservaTour
        v-model:visible="showReservaDialog"
        :tour-seleccionado="tourSeleccionado"
        :user="user"
        @confirmar-reserva="manejarConfirmacionReserva"
        @actualizar-cupos="actualizarCupos"
        @refrescar-tour="refrescarTour"
      />

    <!-- Modal de autenticación requerida -->
      <ModalAuthRequerido
        v-model:visible="showAuthDialog"
        :tour-info="tourSeleccionado"
      />
  </Catalogo>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>s
