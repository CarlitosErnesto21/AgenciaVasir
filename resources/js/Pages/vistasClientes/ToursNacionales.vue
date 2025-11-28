<script setup>
import Catalogo from '../Catalogo.vue'
import ModalReservaTour from './Modales/ModalReservaTour.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import ToursPagination from './Components/ToursPagination.vue'
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { router, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCalendarAlt, faChevronLeft, faChevronRight, faImage, faMapMarkerAlt, faXmark, faSearch, faTimes, faVolcano,
    faExclamationTriangle, faMapSigns, faLeaf, faHandshake } from '@fortawesome/free-solid-svg-icons'

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

// 📄 Variables de paginación
const currentPage = ref(1)
const itemsPerPage = ref(4) // Siempre 4 tours por página
const totalPages = ref(0)

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
  // Cerrar modal (el toast ya se muestra en el modal)
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

// Computed properties para todos los tours con filtro de búsqueda (sin paginación)
const allFilteredTours = computed(() => {
  let filtrados = tours.value.filter(tour => {
    // Mostrar todos los tours excepto los cancelados/finalizados
    // EN_CURSO se muestra deshabilitado como COMPLETO
    return tour.estado !== 'CANCELADA' && tour.estado !== 'FINALIZADO'
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

// 📄 Cálculo del total de páginas
const totalPagesComputed = computed(() => {
  return Math.ceil(allFilteredTours.value.length / itemsPerPage.value)
})

// 📄 Tours paginados para mostrar
const toursVisibles = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return allFilteredTours.value.slice(start, end)
})

// Helper para verificar si un tour está completo o no disponible para reservar
const esTourCompleto = (tour) => {
  const cuposDisponibles = parseInt(tour.cupos_disponibles) || 0
  return cuposDisponibles <= 0 || tour.estado === 'COMPLETO' || tour.estado === 'EN_CURSO'
}

// Función para obtener el texto de la etiqueta según el estado
const getEtiquetaEstado = (tour) => {
  if (tour.estado === 'EN_CURSO') {
    return 'EN CURSO'
  } else if (tour.estado === 'COMPLETO' || (tour.cupos_disponibles !== null && tour.cupos_disponibles <= 0)) {
    return 'COMPLETO'
  }
  return 'DISPONIBLE'
}

// Mantener para compatibilidad (pero ya no se usa para separar secciones)
const toursDisponibles = computed(() => {
  return allFilteredTours.value.filter(tour => !esTourCompleto(tour))
})

const toursSinCupos = computed(() => {
  return allFilteredTours.value.filter(tour => esTourCompleto(tour))
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

// 📄 Funciones de paginación
const goToPage = (page) => {
  if (page >= 1 && page <= totalPagesComputed.value) {
    currentPage.value = page
  }
}

const goToPreviousPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const goToNextPage = () => {
  if (currentPage.value < totalPagesComputed.value) {
    goToPage(currentPage.value + 1)
  }
}

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  searchQuery.value = ''
  currentPage.value = 1 // Resetear a la primera página
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

// Watcher para resetear la paginación cuando cambie la búsqueda
watch(searchQuery, () => {
  currentPage.value = 1
})

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
    <div class="w-full bg-gradient-to-br from-white to-gray-50 shadow-xl overflow-hidden border-b border-gray-200 mb-3 sm:mb-4 pt-20 md:pt-24 lg:pt-28 xl:pt-28">
      <!-- Header con gradiente -->
      <div class="bg-gradient-to-r from-red-500 via-blue-600 to-blue-600 text-white text-center py-4 sm:py-6">
        <div class="flex items-center justify-center gap-3 mb-1">
          <!-- <img src="/images/sv.png" alt="Bandera El Salvador" class="w-8 h-8 sm:w-12 sm:h-12 shadow-lg rounded-full border-2 border-white/30" /> -->
          <FontAwesomeIcon :icon="faVolcano" class="w-8 h-8 sm:w-12 sm:h-12 text-yellow-300 drop-shadow-lg erupcion-volcan" />
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
        <div v-else-if="!loading && allFilteredTours.length === 0" class="text-center py-12">
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
        <div v-if="allFilteredTours.length > 0" class="bg-gradient-to-br from-white to-red-50 rounded-2xl p-4 shadow-lg border border-red-200 mb-6">
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
                  ? `${allFilteredTours.length} resultado${allFilteredTours.length !== 1 ? 's' : ''} encontrado${allFilteredTours.length !== 1 ? 's' : ''}`
                  : `${allFilteredTours.length} destino${allFilteredTours.length !== 1 ? 's' : ''} disponible${allFilteredTours.length !== 1 ? 's' : ''}`
                }}
              </p>
            </div>
          </div>
        </div>

        <!-- Todos los Tours -->
        <div v-if="allFilteredTours.length > 0" class="mb-8">

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="tour in toursVisibles"
              :key="tour.id"
              :class="[
                'flex flex-col min-h-[320px] sm:min-h-[340px] overflow-hidden rounded-xl transition-all duration-300 border-2 shadow-lg',
                esTourCompleto(tour)
                  ? 'bg-gray-100 border-gray-300 opacity-60 cursor-not-allowed'
                  : 'bg-gradient-to-br from-white to-gray-50 hover:from-gray-50 hover:to-white border-gray-200 hover:border-red-300 hover:shadow-xl transform hover:-translate-y-2 hover:scale-[1.02]'
              ]"
              >
              <template #header>
                <div class="relative w-full h-28 sm:h-30 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-200"
                    @click="mostrarGaleria(tour)">
                  <img
                    :src="obtenerImagenActual(tour)"
                    :alt="tour.nombre"
                    :class="[
                      'object-contain h-full w-full bg-gradient-to-br from-gray-50 to-white transition-transform duration-500',
                      esTourCompleto(tour)
                        ? 'filter grayscale opacity-75'
                        : 'group-hover:scale-110'
                    ]"
                    @error="$event.target.src = 'https://via.placeholder.com/300x200/ef4444/ffffff?text=' + encodeURIComponent((tour.nombre || 'Tour').substring(0, 15))"
                  />
                  <div class="absolute top-1.5 right-1.5 bg-gradient-to-r from-green-500 to-green-600 text-white px-2 py-0.5 rounded-full text-xs font-bold shadow-lg border border-green-400">
                    ${{ tour.precio }}
                  </div>
                  <!-- Etiqueta de estado -->
                  <div v-if="esTourCompleto(tour)" class="absolute top-8 right-1.5 bg-gradient-to-r from-red-500 to-red-600 text-white px-2 py-0.5 rounded-full text-xs font-bold shadow-lg border border-red-400 animate-pulse">
                    {{ getEtiquetaEstado(tour) }}
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
                    <div :class="[
                      'rounded-lg p-1.5 border',
                      esTourCompleto(tour)
                        ? 'bg-gradient-to-r from-red-50 to-red-100 border-red-200'
                        : 'bg-gradient-to-r from-green-50 to-emerald-50 border-green-100'
                    ]">
                      <div class="flex items-center gap-1 mb-0.5">
                        <svg v-if="esTourCompleto(tour)" class="w-3 h-3 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <svg v-else class="w-3 h-3 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs font-bold" :class="[
                          esTourCompleto(tour)
                            ? 'bg-gradient-to-r from-red-600 to-red-700 bg-clip-text text-transparent'
                            : 'bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent'
                        ]">
                          {{ esTourCompleto(tour) ? (tour.estado === 'EN_CURSO' ? 'Tour en curso' : 'Tour completo') : 'Cupos disponibles' }}
                        </p>
                      </div>
                      <p class="text-xs font-bold" :class="[
                        esTourCompleto(tour)
                          ? 'text-red-600'
                          : obtenerClaseCupos(tour)
                      ]">
                        {{ esTourCompleto(tour) ? '0 cupos' : `${tour.cupos_disponibles !== null && tour.cupos_disponibles !== undefined ? tour.cupos_disponibles : 0} cupos` }}
                      </p>
                    </div>
                  </div>

                  <!-- Texto informativo -->
                  <div class="mt-1 mb-1 text-center">
                    <p class="text-xs text-gray-500 italic">Toca en cualquier parte para ver más detalles</p>
                  </div>

                  <!-- Botones profesionales -->
                  <div class="flex gap-1 mt-1.5 pt-1.5 border-t border-gray-100 flex-shrink-0">
                    <button
                      v-if="esTourCompleto(tour)"
                      disabled
                      class="px-2 py-1 text-xs font-bold rounded-lg flex-1 bg-gray-400 text-gray-200 cursor-not-allowed opacity-60"
                      >
                        Agotado
                    </button>
                    <button
                      v-else
                      @click="reservarTour(tour)"
                      class="px-2 py-1 text-xs font-bold rounded-lg transition-all duration-300 flex-1 shadow-md hover:shadow-lg bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white transform hover:scale-105"
                      >
                        Reservar
                    </button>
                    <button
                        :disabled="esTourCompleto(tour)"
                        @click="esTourCompleto(tour) ? null : verMasInfo(tour)"
                        :class="[
                          'border-2 px-2 py-1 text-xs font-bold rounded-lg transition-all duration-300 transform shadow-md',
                          esTourCompleto(tour)
                            ? 'border-gray-400 text-gray-400 bg-gray-100 cursor-not-allowed opacity-60'
                            : 'border-red-500 text-red-600 hover:red-blue-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 hover:scale-105 hover:shadow-lg bg-white'
                        ]"
                      >
                        Info
                    </button>
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Controles de paginación -->
          <ToursPagination
            :currentPage="currentPage"
            :totalPagesComputed="totalPagesComputed"
            :allFilteredTours="allFilteredTours"
            :itemsPerPage="itemsPerPage"
            colorScheme="red"
            @go-to-page="goToPage"
            @go-to-previous-page="goToPreviousPage"
            @go-to-next-page="goToNextPage"
          />
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

/* Animación de erupción volcánica */
.erupcion-volcan {
  animation: erupcion-volcan 4s infinite;
}

@keyframes erupcion-volcan {
  0% {
    transform: scale(1) rotate(0deg);
    color: #fcd34d;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
  }
  /* Temblor previo a la erupción */
  10%, 12%, 14%, 16%, 18% {
    transform: scale(1.02) rotate(-1deg);
    color: #f59e0b;
  }
  11%, 13%, 15%, 17% {
    transform: scale(1.02) rotate(1deg);
    color: #f59e0b;
  }
  20% {
    transform: scale(1) rotate(0deg);
    color: #fcd34d;
  }
  /* Erupción intensa */
  25% {
    transform: scale(1.15) rotate(-2deg);
    color: #ef4444;
    filter: drop-shadow(0 6px 20px rgba(239, 68, 68, 0.6)) drop-shadow(0 0 15px rgba(251, 146, 60, 0.8));
  }
  30% {
    transform: scale(1.2) rotate(2deg);
    color: #dc2626;
    filter: drop-shadow(0 8px 25px rgba(220, 38, 38, 0.7)) drop-shadow(0 0 20px rgba(251, 146, 60, 0.9));
  }
  35% {
    transform: scale(1.25) rotate(-1deg);
    color: #b91c1c;
    filter: drop-shadow(0 10px 30px rgba(185, 28, 28, 0.8)) drop-shadow(0 0 25px rgba(251, 146, 60, 1));
  }
  /* Pico de erupción */
  40% {
    transform: scale(1.3) rotate(0deg);
    color: #991b1b;
    filter: drop-shadow(0 12px 35px rgba(153, 27, 27, 0.9)) drop-shadow(0 0 30px rgba(251, 146, 60, 1)) drop-shadow(0 0 15px rgba(255, 255, 255, 0.5));
  }
  /* Enfriamiento gradual */
  50% {
    transform: scale(1.2) rotate(1deg);
    color: #dc2626;
    filter: drop-shadow(0 8px 25px rgba(220, 38, 38, 0.7)) drop-shadow(0 0 20px rgba(251, 146, 60, 0.8));
  }
  60% {
    transform: scale(1.1) rotate(-0.5deg);
    color: #ef4444;
    filter: drop-shadow(0 6px 20px rgba(239, 68, 68, 0.5)) drop-shadow(0 0 15px rgba(251, 146, 60, 0.6));
  }
  70% {
    transform: scale(1.05) rotate(0deg);
    color: #f59e0b;
    filter: drop-shadow(0 4px 15px rgba(245, 158, 11, 0.4));
  }
  80% {
    transform: scale(1.02) rotate(0deg);
    color: #fbbf24;
    filter: drop-shadow(0 4px 10px rgba(251, 191, 36, 0.3));
  }
  /* Vuelta al estado normal */
  90% {
    transform: scale(1.01) rotate(0deg);
    color: #fcd34d;
    filter: drop-shadow(0 4px 8px rgba(252, 211, 77, 0.2));
  }
  100% {
    transform: scale(1) rotate(0deg);
    color: #fcd34d;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
  }
}
</style>s
