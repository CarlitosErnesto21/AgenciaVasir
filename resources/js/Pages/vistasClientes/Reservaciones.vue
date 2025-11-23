<script setup>
import Catalogo from '../Catalogo.vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { router, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faMapMarkerAlt, faChevronLeft, faChevronRight, faImage, faXmark, faPause, faPlay,
    faPlane, faSearch, faTimes, faExclamationTriangle, faHotel } from '@fortawesome/free-solid-svg-icons'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Toast from 'primevue/toast'

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
  let filtrados = hoteles.value

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

// Ya no necesitamos filtrar por estado, todos los hoteles están disponibles
// const hotelesNoDisponibles = computed(() => {
//   return hoteles.value.filter(hotel => hotel.estado === 'NO_DISPONIBLE')
// })

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

function generarMensajeWhatsApp(tipo = 'general') {
  const mensajes = {
    general: 'Hola VASIR, me gustaría recibir información sobre sus servicios turísticos. ¿Podrían ayudarme?',
    aerolíneas: 'Hola VASIR, necesito información sobre vuelos y tarifas de aerolíneas. ¿Podrían ayudarme?',

  }
  return encodeURIComponent(mensajes[tipo] || mensajes.general)
}

// Función para abrir WhatsApp con mensaje personalizado
function abrirWhatsApp(tipo = 'general') {
  // Verificar si el usuario está autenticado y su rol
  const user = page.props.auth?.user

  // Verificar si el usuario tiene roles de Administrador o Empleado
  if (user && user.roles && user.roles.length > 0) {
    const userRoles = user.roles.map(role => typeof role === 'string' ? role : role.name || role.rol)

    if (userRoles.includes('Administrador') || userRoles.includes('Empleado')) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Esta acción está disponible solo para clientes. Los administradores y empleados no pueden realizar consultas de WhatsApp como clientes.',
        life: 5000
      })
      return
    }
  }

  // Obtener el teléfono del administrador desde la configuración compartida
  const adminPhone = config.value?.admin_phone

  // Verificar si es un número válido (no el texto "no disponible")
  if (!adminPhone || adminPhone.includes('no disponible')) {
    toast.add({
      severity: 'info',
      summary: 'WhatsApp no disponible',
      detail: 'El contacto de WhatsApp no está disponible en este momento. Puede contactarnos por nuestras redes sociales.',
      life: 5000
    })
    return
  }

  // Limpiar el número para WhatsApp (solo dígitos)
  const numeroWhatsApp = adminPhone.replace(/\D/g, '')
  const mensaje = generarMensajeWhatsApp(tipo)
  const url = `https://wa.me/${numeroWhatsApp}?text=${mensaje}`
  window.open(url, '_blank')
}

// Función para contactar hotel por WhatsApp
const contactarHotel = (hotel) => {
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

  // Obtener el teléfono del administrador desde la configuración compartida
  const adminPhone = config.value?.admin_phone

  // Verificar si es un número válido (no el texto "no disponible")
  if (!adminPhone || adminPhone.includes('no disponible')) {
    toast.add({
      severity: 'info',
      summary: 'WhatsApp no disponible',
      detail: 'El contacto de WhatsApp no está disponible en este momento. Puede contactarnos por nuestras redes sociales.',
      life: 5000
    })
    return
  }

  // Limpiar el número para WhatsApp (solo dígitos)
  const numeroWhatsApp = adminPhone.replace(/\D/g, '')
  const mensaje = `Hola, estoy interesado/a en obtener más información sobre el hotel "${hotel.nombre}" ubicado en ${hotel.direccion}. ¿Podrían proporcionarme detalles sobre disponibilidad, precios y servicios? Gracias.`
  const whatsappUrl = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensaje)}`
  window.open(whatsappUrl, '_blank')
}

const navegarADetalle = (hotel) => {
  router.visit(`/hoteles/${hotel.id}`)
}

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  searchQuery.value = ''
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
</script>

<template>
  <Catalogo>
    <Toast />
    <!-- Header Profesional con Stats Integradas - Ancho completo de la pantalla -->
    <div class="w-full bg-gradient-to-br from-white to-gray-50 shadow-xl overflow-hidden border-b border-gray-200 mb-3 sm:mb-4 pt-20 md:pt-24 lg:pt-28 xl:pt-28">
      <!-- Header con gradiente -->
      <div class="bg-gradient-to-r from-blue-600 via-blue-600 to-red-500 text-white text-center py-4 sm:py-6">
        <div class="flex items-center justify-center gap-3 mb-1">
          <FontAwesomeIcon :icon="faHotel" class="w-8 h-8 sm:w-12 sm:h-12 text-yellow-300 shadow-lg" />
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
            Hoteles y Boletos Aéreos
          </h1>
        </div>
        <p class="text-base sm:text-lg text-blue-100 px-4">Descubre los mejores hoteles para tu estadía perfecta y los boletos aéreos más convenientes</p>
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
            <div class="text-4xl mb-4">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="text-yellow-500"/>
            </div>
            <h3 class="text-xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent mb-3">No se pudieron cargar los hoteles</h3>
            <p class="text-sm text-red-600 leading-relaxed">Por favor, intenta recargar la página o contacta con nosotros.</p>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="!loading && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl shadow-lg p-8 max-w-lg mx-auto">
            <div class="text-6xl mb-4">
                <FontAwesomeIcon :icon="faHotel" class="text-blue-400"/>
            </div>
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">No hay hoteles disponibles</h3>
            <p class="text-gray-600 mb-4 leading-relaxed">Próximamente tendremos nuevos hoteles disponibles.</p>
            <p class="text-sm text-gray-500">Mientras tanto, puedes explorar nuestros tours.</p>
          </div>
        </div>

        <!-- Barra de búsqueda optimizada -->
        <div v-if="hoteles.length > 0" class="bg-gradient-to-br from-white to-blue-50 rounded-2xl p-4 shadow-lg border border-blue-200 mb-6">
          <div class="max-w-xl mx-auto">
            <div class="text-center mb-3">
              <div class="flex items-center justify-center gap-2 mb-2">
                <h3 class="text-base font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                  Explorar Hoteles Disponibles
                </h3>
              </div>
            </div>

            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <FontAwesomeIcon :icon="faSearch" class="w-4 h-4 text-blue-400 group-focus-within:text-blue-600 transition-colors" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar hoteles, provincias, países, ubicaciones..."
                class="w-full pl-10 pr-10 py-3 text-gray-700 bg-white border-2 border-blue-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all duration-300 text-sm shadow-sm placeholder-gray-400"
              />
              <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button
                  @click="limpiarBusqueda"
                  class="text-gray-400 hover:text-blue-500 transition-colors duration-200 p-1.5 rounded-full hover:bg-blue-100"
                  title="Limpiar búsqueda"
                >
                  <FontAwesomeIcon :icon="faTimes" class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div class="mt-2 text-center">
              <p class="text-xs text-gray-600 bg-white/60 rounded-full px-3 py-1 inline-block">
                {{ searchQuery
                  ? `${hotelesDisponibles.length} resultado${hotelesDisponibles.length !== 1 ? 's' : ''} encontrado${hotelesDisponibles.length !== 1 ? 's' : ''}`
                  : `${hotelesDisponibles.length} hotel${hotelesDisponibles.length !== 1 ? 'es' : ''} disponible${hotelesDisponibles.length !== 1 ? 's' : ''}`
                }}
              </p>
            </div>
          </div>
        </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 lg:gap-10">
            <Card
              v-for="hotel in hotelesDisponibles"
              :key="hotel.id"
              class="group bg-white border-0 shadow-xl hover:shadow-2xl transition-all duration-500 flex flex-col min-h-[380px] transform hover:-translate-y-3 hover:scale-[1.01] overflow-hidden rounded-2xl cursor-pointer relative"
              @click="navegarADetalle(hotel)"
              >
              <template #header>
                <div class="relative w-full h-40 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 overflow-hidden">
                  <img
                    :src="obtenerImagenActual(hotel)"
                    :alt="hotel.nombre"
                    class="w-full h-full object-cover group-hover:scale-110 group-hover:brightness-110 transition-all duration-700"
                    loading="lazy"
                  />
                  <!-- Overlay mejorado -->
                  <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent group-hover:from-black/50 transition-all duration-500"></div>

                  <!-- Badge de provincia elegante -->
                  <div class="absolute top-4 right-4 z-10">
                    <div class="bg-purple-600 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-xl border border-white/30 flex items-center gap-1.5">
                      <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3" />
                      <span>{{ hotel.provincia?.nombre || 'Sin provincia' }}</span>
                    </div>
                  </div>

                  <!-- Indicador de múltiples imágenes mejorado -->
                  <div v-if="hotel.imagenes && hotel.imagenes.length > 1" class="absolute bottom-4 right-4 bg-black/70 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-2 border border-white/20">
                    <FontAwesomeIcon :icon="faImage" class="w-3 h-3" />
                    <span>{{ hotel.imagenes.length }} fotos</span>
                  </div>

                  <!-- Título superpuesto -->
                  <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                    <h3 class="font-bold text-lg leading-tight line-clamp-2 drop-shadow-2xl group-hover:text-yellow-300 transition-colors duration-300">{{ hotel.nombre }}</h3>
                  </div>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col p-4">
                  <!-- Información de ubicación con diseño moderno -->
                  <div class="space-y-3 mb-3">
                    <!-- Dirección con icono circular -->
                    <div class="flex items-start gap-2.5">
                      <div class="flex-shrink-0 w-7 h-7 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-md">
                        <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3 text-white" />
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-800 mb-0.5">Ubicación</p>
                        <p class="text-xs text-gray-600 leading-relaxed line-clamp-1">{{ hotel.direccion }}</p>
                      </div>
                    </div>

                    <!-- País con diseño destacado -->
                    <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 p-2.5 rounded-lg border border-blue-200/50 shadow-sm">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                          <div class="w-2.5 h-2.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full"></div>
                          <span class="text-xs font-semibold text-gray-700">País</span>
                        </div>
                        <span class="text-xs font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 px-2.5 py-0.5 bg-white rounded-full shadow-sm border border-blue-100 leading-relaxed line-clamp-1">
                          {{ hotel.provincia?.pais?.nombre || 'No especificado' }}
                        </span>
                      </div>
                    </div>
                  </div>                  <!-- Separador elegante -->

                  <!-- Texto informativo -->
                  <div class="mt-1 mb-1 text-center">
                    <p class="text-xs text-gray-500 italic">Toca en cualquier parte para ver más detalles</p>
                  </div>

                  <!-- Botón de WhatsApp premium -->
                  <div class="mt-auto">
                    <Button
                      @click.stop="contactarHotel(hotel)"
                      class="!border-none !px-4 !py-2.5 !text-sm font-bold rounded-lg transition-all duration-300 w-full shadow-lg !bg-gradient-to-r !from-green-500 !via-emerald-500 !to-green-600 hover:!from-green-600 hover:!via-emerald-600 hover:!to-green-700 !text-white hover:shadow-xl transform hover:scale-[1.01] active:scale-[0.99] relative overflow-hidden group/button"
                      size="small"
                    >
                      <!-- Efecto de brillo animado -->
                      <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/button:translate-x-full transition-transform duration-1000"></div>

                      <div class="flex items-center justify-center gap-2 relative z-10">
                        <FontAwesomeIcon :icon="faWhatsapp" class="w-4 h-4" />
                        <span class="font-bold">Reservar ahora</span>
                      </div>
                    </Button>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Sección eliminada: Hoteles No Disponibles ya no es necesaria -->
        <!-- Ahora todos los hoteles se muestran como disponibles -->

        <!-- Sección de Aerolíneas  -->
        <div class="w-full mb-8 mt-6">
          <div class="bg-gradient-to-br from-white via-purple-50 to-indigo-50 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 shadow-lg sm:shadow-xl border-2 border-gray-200 hover:shadow-2xl transition-all duration-300">
            <!-- Header responsivo -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-center py-3 sm:py-4 md:py-6 rounded-lg sm:rounded-xl mb-4 sm:mb-6 md:mb-8">
              <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold flex items-center justify-center gap-2">
                <FontAwesomeIcon :icon="faPlane" />
                <span class="text-center">Aerolíneas Asociadas</span>
              </h3>
            </div>

            <!-- Mensaje informativo -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-5 mb-4 sm:mb-6">
              <div class="text-center">
                <h4 class="text-sm sm:text-base md:text-lg font-bold text-blue-800 mb-2">
                  ¿Necesitas información sobre vuelos?
                </h4>
                <p class="text-xs sm:text-sm text-blue-600 mb-3 sm:mb-4">
                  Trabajamos con las mejores aerolíneas para ofrecerte las mejores tarifas y conexiones
                </p>
              </div>
            </div>

            <!-- Grid de logos de aerolíneas -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 md:gap-6 mb-4 sm:mb-6">
              <!-- Avianca -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_avianca.png" alt="Avianca" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">AVIANCA</div>
                </div>
              </div>

              <!-- Copa Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-300 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_copaAirlines.png" alt="Copa Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">COPA</div>
                </div>
              </div>

               <!-- Aero Mexico -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_aeroMexico.png" alt="Aero Mexico" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">AERO MEXICO</div>
                </div>
              </div>

              <!-- JetBlue Airways -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-800 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_jetBlue.png" alt="JetBlue Airways" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">JETBLUE</div>
                </div>
              </div>

              <!-- Iberia -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-yellow-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_iberia.png" alt="Iberia" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">IBERIA</div>
                </div>
              </div>

              <!-- Volaris -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-green-400 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_volaris.png" alt="Volaris" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">VOLARIS</div>
                </div>
              </div>

              <!-- Frontier Airlines-->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-green-800 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_frontier.png" alt="Frontier Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">FRONTIER</div>
                </div>
              </div>

              <!-- Air Canada-->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_airCanada.png" alt="Air Canada" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">AIR CANADA</div>
                </div>
              </div>

              <!-- Arajet Airlines-->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-purple-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_arajet.png" alt="Arajeet Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">ARAJEET</div>
                </div>
              </div>

              <!-- American Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_americanAirlines.png" alt="American" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-red-700 text-white px-2 py-1 rounded text-xs font-bold">AMERICAN</div>
                </div>
              </div>

              <!-- United Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-900 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_unitedAirlines.png" alt="United Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-800 text-white px-2 py-1 rounded text-xs font-bold">UNITED</div>
                </div>
              </div>

              <!-- Delta Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_delta.png" alt="Delta Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">DELTA</div>
                </div>
              </div>

              <!-- Spirit Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-gray-900 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_spirit.png" alt="Spirit Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-yellow-500 text-black px-2 py-1 rounded text-xs font-bold">SPIRIT</div>
                </div>
              </div>
            </div>
            <!-- Botón de contacto WhatsApp para aerolíneas -->
            <div class="text-center">
              <p class="text-xs sm:text-sm text-gray-600">
                Consulta tarifas especiales y disponibilidad de vuelos con nuestras aerolíneas asociadas,
                a través de nuestro canal de WhatsApp.
              </p>
              <button
                @click="abrirWhatsApp('aerolíneas')"
                type="button"
                class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 sm:px-6 md:px-8 py-2 mt-4 sm:mt-11 rounded-lg sm:rounded-xl font-semibold text-sm sm:text-base transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-2 group cursor-pointer"
              >
                <span>¿Necesitas información?</span>
                <span class="px-2 py-2 rounded-full">
                  <FontAwesomeIcon :icon="faWhatsapp" class="w-5 h-5 text-white" />
                </span>
              </button>
            </div>
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
