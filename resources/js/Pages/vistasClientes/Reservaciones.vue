<script setup>
import Catalogo from '../Catalogo.vue'
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { router, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faMapMarkerAlt, faChevronLeft, faChevronRight, faImage, faXmark, faPause, faPlay, faPhone, faEnvelope, faInfoCircle, faSearch, faTimes } from '@fortawesome/free-solid-svg-icons'

// Recibir los props del controlador (opcional, como fallback)
const props = defineProps({
  hoteles: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const user = computed(() => page.props.auth.user)
const config = computed(() => page.props.config || {})

const toast = useToast()

// Estados reactivos principales
const hoteles = ref([])
const loading = ref(true)
const error = ref(null)

// Variable para búsqueda
const searchQuery = ref('')

// URL de la API para hoteles
const url = "/api/hoteles"



// Computed properties para hoteles filtrados por búsqueda
const hotelesDisponibles = computed(() => {
  let filtrados = hoteles.value.filter(hotel => hotel.estado === 'activo')

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    filtrados = filtrados.filter(hotel => {
      const nombre = hotel.nombre?.toLowerCase() || ''
      const provincia = hotel.provincia?.nombre?.toLowerCase() || ''
      const pais = hotel.pais?.nombre?.toLowerCase() || ''
      const direccion = hotel.direccion?.toLowerCase() || ''

      return nombre.includes(query) ||
             provincia.includes(query) ||
             pais.includes(query) ||
             direccion.includes(query)
    })
  }

  return filtrados
})

const hotelesNoDisponibles = computed(() => {
  return hoteles.value.filter(hotel => hotel.estado === 'inactivo')
})

// Función para obtener hoteles desde la API
const obtenerHoteles = async () => {
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
    hoteles.value = data || []

  } catch (err) {
    console.error('Error al obtener hoteles:', err)
    error.value = err.message
    // Usar props como fallback si hay error en la API
    hoteles.value = props.hoteles || []
  } finally {
    loading.value = false
  }
}

// Variables para el carrusel de imágenes
const showImageDialog = ref(false)
const selectedHotelImages = ref([])
const currentImageIndex = ref(0)
const galleryIntervalId = ref(null)
const isGalleryAutoPlaying = ref(true)

// Variables para carruseles automáticos en las cards
const cardImageIndices = ref({})
const intervalIds = ref({})

// Función para obtener la imagen actual del carrusel automático
const obtenerImagenActual = (hotel) => {
  if (!hotel.imagenes || hotel.imagenes.length === 0) {
    return 'https://via.placeholder.com/400x300/ef4444/ffffff?text=Sin+Imagen'
  }

  // Asegurarse de que el hotel tenga un ID para el índice
  if (!hotel.id) return `/storage/hoteles/${typeof hotel.imagenes[0] === 'string' ? hotel.imagenes[0] : hotel.imagenes[0].nombre}`;

  // Si solo tiene una imagen, mostrar esa
  if (hotel.imagenes.length === 1) {
    const nombreImagen = typeof hotel.imagenes[0] === 'string' ? hotel.imagenes[0] : hotel.imagenes[0].nombre
    return `/storage/hoteles/${nombreImagen}`
  }

  // Si tiene múltiples imágenes, usar el índice del carrusel
  const currentIndex = cardImageIndices.value[hotel.id] || 0
  const imagen = hotel.imagenes[currentIndex]
  const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre

  return `/storage/hoteles/${nombreImagen}`;
}

// Función para inicializar carrusel automático
const inicializarCarrusel = (hotel) => {
  if (!hotel.imagenes || hotel.imagenes.length <= 1 || !hotel.id) return

  // Inicializar índice si no existe
  if (!(hotel.id in cardImageIndices.value)) {
    cardImageIndices.value[hotel.id] = 0
  }

  // Crear intervalo para cambiar imágenes automáticamente
  if (!(hotel.id in intervalIds.value)) {
    intervalIds.value[hotel.id] = setInterval(() => {
      // Validación adicional de seguridad
      if (hotel.imagenes && hotel.imagenes.length > 0) {
        cardImageIndices.value[hotel.id] = (cardImageIndices.value[hotel.id] + 1) % hotel.imagenes.length
      }
    }, 3000) // Cambiar cada 3 segundos
  }
}

// Función para detener todos los carruseles
const detenerTodosLosCarruseles = () => {
  Object.keys(intervalIds.value).forEach(hotelId => {
    clearInterval(intervalIds.value[hotelId])
  })
  intervalIds.value = {}
  detenerCarruselGaleria() // También detener el carrusel de la galería
}

// Lifecycle hooks
onMounted(async () => {
  // Obtener hoteles desde la API
  await obtenerHoteles()

  // Inicializar carruseles para todos los hoteles con múltiples imágenes
  hoteles.value.forEach(hotel => {
    if (hotel.imagenes && hotel.imagenes.length > 1) {
      inicializarCarrusel(hotel)
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
    return `/storage/hoteles/${nombreImagen}`;
  })
}

// Función para mostrar galería de imágenes
const mostrarGaleria = (hotel) => {
  selectedHotelImages.value = obtenerTodasLasImagenes(hotel.imagenes)
  currentImageIndex.value = 0
  showImageDialog.value = true
  isGalleryAutoPlaying.value = true

  // Iniciar carrusel automático en la galería si hay múltiples imágenes
  if (selectedHotelImages.value.length > 1) {
    iniciarCarruselGaleria()
  }
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

// Funciones para navegar en el carrusel
const siguienteImagen = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % selectedHotelImages.value.length
  // Reiniciar el timer automático solo si está activado
  if (showImageDialog.value && selectedHotelImages.value.length > 1 && isGalleryAutoPlaying.value) {
    iniciarCarruselGaleria()
  }
}

const imagenAnterior = () => {
  currentImageIndex.value = currentImageIndex.value === 0
    ? selectedHotelImages.value.length - 1
    : currentImageIndex.value - 1
  // Reiniciar el timer automático solo si está activado
  if (showImageDialog.value && selectedHotelImages.value.length > 1 && isGalleryAutoPlaying.value) {
    iniciarCarruselGaleria()
  }
}

const irAImagen = (index) => {
  currentImageIndex.value = index
  // Reiniciar el timer automático solo si está activado
  if (showImageDialog.value && selectedHotelImages.value.length > 1 && isGalleryAutoPlaying.value) {
    iniciarCarruselGaleria()
  }
}

// Funciones para los botones
const contactarHotel = (hotel) => {
  const mensaje = `Hola, estoy interesado/a en obtener más información sobre el hotel "${hotel.nombre}" ubicado en ${hotel.direccion}. ¿Podrían proporcionarme detalles sobre disponibilidad, precios y servicios? Gracias.`
  const whatsappUrl = `https://wa.me/50379858777?text=${encodeURIComponent(mensaje)}`
  window.open(whatsappUrl, '_blank')
}

const verMasInfo = (hotel) => {
  toast.add({
    severity: 'info',
    summary: `Información de ${hotel.nombre}`,
    detail: `Ubicación: ${hotel.direccion} | Provincia: ${hotel.provincia?.nombre || 'No especificada'} | Categoría: ${hotel.categoria_hotel?.nombre || 'No especificada'} | Estado: ${hotel.estado} | ${hotel.descripcion}`,
    life: 8000
  })
}

// Función para contactar por email
const contactarPorEmail = (hotel) => {
  const asunto = `consulta sobre hotel ${hotel.nombre}`
  const cuerpo = `Estimados,

Me dirijo a ustedes para solicitar información sobre el hotel "${hotel.nombre}" ubicado en ${hotel.direccion}.

Me gustaría conocer:
- Disponibilidad de habitaciones
- Tarifas disponibles
- Servicios incluidos
- Políticas de reserva y cancelación

Quedo atento a su respuesta.

Saludos cordiales.`

  const emailUrl = `mailto:${config.value.mail_from_address || 'vasirtours19@gmail.com'}?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(cuerpo)}`
  window.open(emailUrl, '_blank')
}

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  searchQuery.value = ''
}
</script>

<template>
  <Catalogo>
    <Toast />
    <!-- Header Profesional con Stats Integradas - Ancho completo de la pantalla -->
    <div class="w-full bg-gradient-to-br from-white to-gray-50 shadow-xl overflow-hidden border-b border-gray-200 mb-3 sm:mb-4 mt-20 sm:mt-20 md:mt-28 lg:mt-32 xl:mt-32">
      <!-- Header con gradiente -->
      <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-red-600 text-white text-center py-4 sm:py-6">
        <div class="flex items-center justify-center gap-3 mb-1">
          <FontAwesomeIcon :icon="faImage" class="w-8 h-8 sm:w-12 sm:h-12 text-yellow-300 shadow-lg" />
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
            Hoteles Disponibles
          </h1>
        </div>
        <p class="text-base sm:text-lg text-blue-100 px-4">Descubre los mejores hoteles para tu estadía perfecta.</p>
      </div>


    </div>

    <!-- Contenido principal con padding -->
    <div class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen px-4 sm:px-6 lg:px-8 pb-8">
      <div class="w-full max-w-7xl mx-auto">

        <!-- Estado de carga -->
        <div v-if="loading && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
            <p class="text-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Cargando hoteles...</p>
            <p class="text-sm text-gray-500 mt-2">Preparando los mejores alojamientos para ti</p>
          </div>
        </div>

        <!-- Estado de error -->
        <div v-else-if="error && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 text-red-700 px-8 py-6 rounded-xl shadow-lg max-w-md mx-auto">
            <div class="text-4xl mb-4">⚠️</div>
            <h3 class="text-xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent mb-3">No se pudieron cargar los hoteles</h3>
            <p class="text-sm text-red-600 leading-relaxed">Por favor, intenta recargar la página o contacta con nosotros.</p>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="!loading && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl shadow-lg p-8 max-w-lg mx-auto">
            <div class="text-6xl mb-4">🏨</div>
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">No hay hoteles disponibles</h3>
            <p class="text-gray-600 mb-4 leading-relaxed">Próximamente tendremos nuevos hoteles disponibles.</p>
            <p class="text-sm text-gray-500">Mientras tanto, puedes explorar nuestros tours.</p>
          </div>
        </div>

        <!-- Barra de búsqueda -->
        <div v-if="hoteles.length > 0" class="bg-white rounded-xl p-6 shadow-md border border-gray-200 mb-8">
          <div class="max-w-2xl mx-auto">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
              <FontAwesomeIcon :icon="faSearch" class="w-5 h-5 text-blue-600 mr-2" />
              Buscar Hoteles
            </h3>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <FontAwesomeIcon :icon="faSearch" class="w-5 h-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar por nombre del hotel, provincia, país o ubicación..."
                class="w-full pl-12 pr-12 py-4 text-gray-700 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300 text-lg"
              />
              <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <button
                  @click="limpiarBusqueda"
                  class="text-gray-400 hover:text-red-500 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Limpiar búsqueda"
                >
                  <FontAwesomeIcon :icon="faTimes" class="w-5 h-5" />
                </button>
              </div>
            </div>
            <div class="mt-3 text-center">
              <p class="text-sm text-gray-500">
                {{ searchQuery ? `Mostrando ${hotelesDisponibles.length} resultado${hotelesDisponibles.length !== 1 ? 's' : ''} para "${searchQuery}"` : `${hotelesDisponibles.length} hotel${hotelesDisponibles.length !== 1 ? 'es' : ''} disponible${hotelesDisponibles.length !== 1 ? 's' : ''}` }}
              </p>
            </div>
          </div>
        </div>

        <!-- Hoteles Disponibles -->
        <div v-if="hotelesDisponibles.length > 0" class="mb-8">
          <div class="bg-gradient-to-r from-blue-500 via-blue-500 to-blue-500 text-white text-center py-4 px-6 rounded-t-xl mb-6">
            <h2 class="text-xl md:text-2xl font-bold">Hoteles Disponibles</h2>
            <p class="text-blue-100 text-sm mt-1">{{ hotelesDisponibles.length }} hotel{{ hotelesDisponibles.length !== 1 ? 'es' : '' }} disponible{{ hotelesDisponibles.length !== 1 ? 's' : '' }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="hotel in hotelesDisponibles"
              :key="hotel.id"
              class="bg-gradient-to-br from-white to-gray-50 hover:from-gray-50 hover:to-white border-2 border-gray-200 hover:border-blue-300 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col min-h-[400px] sm:min-h-[450px] transform hover:-translate-y-2 hover:scale-[1.02] overflow-hidden rounded-xl"
              >
              <template #header>
                <div class="relative w-full h-36 sm:h-40 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-200"
                     @click="mostrarGaleria(hotel)">
                  <img
                    :src="obtenerImagenActual(hotel)"
                    :alt="hotel.nombre"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    loading="lazy"
                  />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                  <!-- Indicador de múltiples imágenes -->
                  <div v-if="hotel.imagenes && hotel.imagenes.length > 1" class="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded-full text-xs flex items-center gap-1">
                    <FontAwesomeIcon :icon="faImage" class="w-3 h-3" />
                    {{ hotel.imagenes.length }}
                  </div>

                  <!-- Categoría badge -->
                  <div class="absolute top-2 left-2 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                    {{ hotel.categoria_hotel?.nombre || 'Sin categoría' }}
                  </div>

                  <!-- Provincia badge -->
                  <div class="absolute top-2 right-2 bg-purple-600 text-white px-2 py-1 rounded-full text-xs font-semibold shadow-lg flex items-center gap-1">
                    <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3" />
                    {{ hotel.provincia?.nombre || 'Sin provincia' }}
                  </div>
                </div>
              </template>

              <template #title>
                <div class="h-10 sm:h-12 flex items-start px-4 pt-3 cursor-pointer hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-300 rounded-lg mx-2"
                     @click="verMasInfo(hotel)">
                  <span class="font-bold text-gray-800 leading-tight line-clamp-2 text-sm sm:text-base">{{ hotel.nombre }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-4 pb-4 min-h-0">
                  <div class="flex-grow space-y-3">
                    <!-- Descripción -->
                    <p class="text-gray-600 text-sm line-clamp-2">
                      {{ hotel.descripcion }}
                    </p>

                    <!-- Información del hotel -->
                    <div class="space-y-2">
                      <div>
                        <p class="text-xs font-semibold text-gray-700 mb-1">Dirección:</p>
                        <p class="text-xs text-gray-600 flex items-start gap-1">
                          <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3 text-purple-500 mt-0.5 flex-shrink-0" />
                          {{ hotel.direccion }}
                        </p>
                      </div>

                      <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                        <div>
                          <p class="font-semibold text-gray-700 mb-1">Provincia:</p>
                          <p>{{ hotel.provincia?.nombre || 'No especificada' }}</p>
                        </div>
                        <div>
                          <p class="font-semibold text-gray-700 mb-1">Categoría:</p>
                          <p>{{ hotel.categoria_hotel?.nombre || 'Sin categoría' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Botones de acción -->
                  <div class="flex gap-2 mt-4">
                    <Button
                      label="WhatsApp"
                      @click="contactarHotel(hotel)"
                      class="!border-none !px-3 !py-2 !text-sm font-semibold rounded transition-all flex-1 shadow-sm !bg-green-600 !text-white hover:!bg-green-700"
                      size="small"
                    />
                    <Button
                      label="Email"
                      @click="contactarPorEmail(hotel)"
                      outlined
                      class="!border-blue-600 !text-blue-600 !px-3 !py-2 !text-sm font-semibold rounded hover:!bg-blue-50 transition-all"
                      size="small"
                    />
                    <Button
                      label="Info"
                      @click="verMasInfo(hotel)"
                      outlined
                      class="!border-purple-600 !text-purple-600 !px-2 !py-2 !text-sm font-semibold rounded hover:!bg-purple-50 transition-all"
                      size="small"
                    >
                      <FontAwesomeIcon :icon="faInfoCircle" class="w-4 h-4" />
                    </Button>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Hoteles No Disponibles -->
        <div v-if="hotelesNoDisponibles.length > 0" class="mb-8">
          <div class="bg-gradient-to-r from-gray-500 to-gray-600 text-white text-center py-4 px-6 rounded-t-xl mb-6">
            <h2 class="text-xl md:text-2xl font-bold">😔 Hoteles Temporalmente No Disponibles</h2>
            <p class="text-gray-200 text-sm mt-1">{{ hotelesNoDisponibles.length }} hotel{{ hotelesNoDisponibles.length !== 1 ? 'es' : '' }} temporalmente no disponible{{ hotelesNoDisponibles.length !== 1 ? 's' : '' }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="hotel in hotelesNoDisponibles"
              :key="hotel.id"
              class="border-2 border-gray-300 bg-gray-50 opacity-75 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col min-h-[400px] sm:min-h-[450px] overflow-hidden rounded-xl"
              >
              <template #header>
                <div class="relative w-full h-36 sm:h-40 bg-gradient-to-br from-gray-200 via-gray-150 to-gray-300 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-300"
                     @click="mostrarGaleria(hotel)">
                  <img
                    :src="obtenerImagenActual(hotel)"
                    :alt="hotel.nombre"
                    class="w-full h-full object-cover filter grayscale"
                    loading="lazy"
                  />

                  <!-- Badge de no disponible -->
                  <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span class="bg-red-600 text-white px-4 py-2 rounded-full text-sm font-bold">No Disponible</span>
                  </div>
                </div>
              </template>

              <template #title>
                <div class="h-10 sm:h-12 flex items-start px-4 pt-3">
                  <span class="font-bold text-gray-600 leading-tight line-clamp-2 text-sm sm:text-base">{{ hotel.nombre }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-4 pb-4 min-h-0">
                  <div class="flex-grow space-y-3">
                    <p class="text-gray-500 text-sm line-clamp-2">
                      {{ hotel.descripcion }}
                    </p>

                    <div class="space-y-2">
                      <div>
                        <p class="text-xs font-semibold text-gray-600 mb-1">Dirección:</p>
                        <p class="text-xs text-gray-500">{{ hotel.direccion }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="flex gap-2 mt-4">
                    <Button
                      label="Contactar para Info"
                      @click="contactarHotel(hotel)"
                      class="!border-none !px-3 !py-2 !text-sm font-semibold rounded transition-all flex-1 shadow-sm !bg-gray-600 !text-white hover:!bg-gray-700"
                      size="small"
                    />
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
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center py-6">
              <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
                ¿Por qué reservar con nosotros?
              </h2>
              <p class="text-base sm:text-lg text-blue-100 px-4">Tu estadía perfecta está a un mensaje de distancia</p>
            </div>

            <!-- Contenido -->
            <div class="p-2 md:p-8">
              <div class="grid grid-cols-3 md:grid-cols-3 gap-2 md:gap-8">
                <div class="text-center p-2 md:p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                  <div class="text-3xl md:text-4xl mb-2">🔍</div>
                  <h3 class="font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent mb-2 text-lg">Hoteles Verificados</h3>
                  <p class="text-gray-600 text-sm leading-relaxed">Todos nuestros hoteles están verificados y seleccionados cuidadosamente</p>
                </div>
                <div class="text-center p-2 md:p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200">
                  <div class="text-3xl md:text-4xl mb-2">💬</div>
                  <h3 class="font-bold bg-gradient-to-r from-purple-600 to-purple-700 bg-clip-text text-transparent mb-2 text-lg">Atención Personalizada</h3>
                  <p class="text-gray-600 text-sm leading-relaxed">Te asesoramos personalmente para encontrar el alojamiento perfecto</p>
                </div>
                <div class="text-center p-2 md:p-6 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200">
                  <div class="text-3xl md:text-4xl mb-2">⚡</div>
                  <h3 class="font-bold bg-gradient-to-r from-red-600 to-red-700 bg-clip-text text-transparent mb-2 text-lg">Respuesta Rápida</h3>
                  <p class="text-gray-600 text-sm leading-relaxed">Respuesta inmediata por WhatsApp y email para tus consultas</p>
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
          <div class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 rounded-lg flex items-center justify-between">
            <h3 class="text-md md:text-xl font-bold bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">Imágenes del hotel</h3>
            <div class="flex items-center gap-3">
              <button
                v-if="selectedHotelImages.length > 1"
                @click="toggleGalleryAutoPlay"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-2 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all border border-white/20 shadow-lg transform hover:scale-110"
                :title="isGalleryAutoPlaying ? 'Pausar carrusel automático' : 'Reanudar carrusel automático'"
              >
                <FontAwesomeIcon :icon="isGalleryAutoPlaying ? faPause : faPlay" class="w-4 h-4" />
              </button>
              <!-- Botón de cerrar personalizado y visible -->
              <button
                @click="showImageDialog = false; detenerCarruselGaleria()"
                class="bg-gradient-to-r from-red-600/80 to-red-700/80 backdrop-blur-sm text-white p-2 rounded-full hover:from-red-700/90 hover:to-red-800/90 transition-all border border-white/20 shadow-lg transform hover:scale-110"
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
              :src="selectedHotelImages[currentImageIndex]"
              alt="Hotel imagen"
              class="max-w-full max-h-full object-contain rounded-lg"
            />

            <!-- Botones de navegación profesionales -->
            <div v-if="selectedHotelImages.length > 1" class="absolute inset-0 flex items-center justify-between p-0 md:p-12">
              <button
                @click="imagenAnterior"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-3 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all z-10 border border-white/20 shadow-lg transform hover:scale-110"
              >
                <FontAwesomeIcon :icon="faChevronLeft" class="w-5 h-5" />
              </button>
              <button
                @click="siguienteImagen"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-3 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all z-10 border border-white/20 shadow-lg transform hover:scale-110"
              >
                <FontAwesomeIcon :icon="faChevronRight" class="w-5 h-5" />
              </button>
            </div>

            <!-- Contador de imágenes profesional -->
            <div v-if="selectedHotelImages.length > 1"
                class="absolute bottom-4 right-4 bg-gradient-to-r from-black/80 to-gray-800/80 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium z-10 border border-white/20 shadow-lg">
              {{ currentImageIndex + 1 }} / {{ selectedHotelImages.length }}
            </div>

            <!-- Indicador de autoplay profesional -->
            <div v-if="selectedHotelImages.length > 1 && isGalleryAutoPlaying"
                class="absolute top-4 right-4 bg-gradient-to-r from-blue-500/90 to-blue-600/90 backdrop-blur-sm text-white px-3 py-2 rounded-full text-xs font-medium z-10 flex items-center gap-2 border border-blue-300/30 shadow-lg">
              <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
              <span>Auto</span>
            </div>
          </div>

          <!-- Miniaturas profesionales -->
          <div v-if="selectedHotelImages.length > 1" class="flex gap-3 overflow-x-auto pb-2 px-2">
            <button
              v-for="(imagen, index) in selectedHotelImages"
              :key="index"
              @click="irAImagen(index)"
              class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-3 transition-all bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center shadow-md hover:shadow-lg transform hover:scale-110"
              :class="currentImageIndex === index ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-300 hover:border-gray-400'"
            >
              <img
                :src="imagen"
                :alt="`Hotel imagen ${index + 1}`"
                class="max-w-full max-h-full object-contain rounded"
              />
            </button>
          </div>
        </div>
      </Dialog>
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
</style>
