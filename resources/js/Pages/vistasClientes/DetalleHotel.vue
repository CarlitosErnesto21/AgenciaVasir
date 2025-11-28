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
            <div class="max-w-4xl mx-auto">
              <!-- Información principal -->
              <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-3 sm:mb-4 text-center">{{ hotel.nombre }}</h1>
                <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                  <div class="flex items-center gap-3 text-gray-600 text-sm sm:text-base">
                    <FontAwesomeIcon :icon="faLocation" class="text-blue-500"/>
                    <span><strong>Dirección:</strong> {{ hotel.direccion }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-gray-600 text-sm sm:text-base">
                    <FontAwesomeIcon :icon="faMapLocationDot" class="text-blue-500"/>
                    <span><strong>Ubicación:</strong> {{ hotel.provincia?.nombre || 'No especificado' }}, {{ hotel.pais?.nombre || 'No especificado' }}</span>
                  </div>
                </div>

                <!-- Descripción -->
                <div v-if="hotel.descripcion" class="mb-6 sm:mb-8">
                  <h3 class="text-lg font-semibold text-gray-900 mb-3">Descripción</h3>
                  <div class="text-gray-700 text-sm sm:text-base leading-relaxed bg-gray-50 p-4 rounded-lg">
                    {{ hotel.descripcion }}
                  </div>
                </div>

                <!-- Botón de contacto por WhatsApp -->
                <div class="text-center">
                  <button
                    @click="contactarHotel"
                    class="group inline-flex items-center justify-center gap-3 font-bold py-4 px-10 rounded-xl transition-all duration-300 text-lg bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-xl hover:shadow-2xl transform hover:scale-105 hover:-translate-y-1 pulse-scale-whatsapp"
                  >
                    <FontAwesomeIcon :icon="faWhatsapp" class="w-6 h-6 group-hover:animate-pulse"/>
                    <span>Contactar por WhatsApp</span>
                    <FontAwesomeIcon :icon="faArrowRight" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"/>
                  </button>
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
  </Catalogo>
</template>

<script setup>
import Catalogo from '../Catalogo.vue'
import Toast from 'primevue/toast'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faLocation, faMapLocation, faMapLocationDot, faArrowRight } from '@fortawesome/free-solid-svg-icons'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'

const page = usePage()
const user = computed(() => page.props.auth.user)
const config = computed(() => page.props.config || {})

const toast = useToast()

// Variable para el modal de autenticación
const showAuthDialog = ref(false)

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
  const mensaje = `Hola, estoy interesado/a en obtener más información sobre el hotel "${hotel.value.nombre}" ubicado en ${hotel.value.direccion}. ¿Podrían proporcionarme detalles sobre disponibilidad, precios y servicios? Gracias.`
  const whatsappUrl = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensaje)}`
  window.open(whatsappUrl, '_blank')
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

<style scoped>
@keyframes pulse-scale {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
}

.pulse-scale-whatsapp {
  animation: pulse-scale 2.3s ease-in-out infinite;
}
</style>
