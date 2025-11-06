<script setup>
import Catalogo from './Catalogo.vue'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBagShopping, faBullseye, faPhone, faStar, faVolcano, faFaceSmile, faTrophy, faMapLocationDot, faGlobeAmericas, faHotel, faPassport, faDollarSign, faChevronLeft, faChevronRight, faPlane } from '@fortawesome/free-solid-svg-icons'
import { faRocketchat, faWhatsapp } from '@fortawesome/free-brands-svg-icons'

const products = ref([])
const slides = ref([])
const paquetesVisa = ref([])
const loading = ref(true)
const totalTours = ref(0)
const currentSlide = ref(0)

// Variables para el modal de detalles del paquete
const mostrarModal = ref(false)
const paqueteSeleccionado = ref(null)

// Calcular años de experiencia desde 2019
const calcularAnosExperiencia = () => {
  const fechaInicio = new Date('2019-01-01')
  const fechaActual = new Date()
  const diferencia = fechaActual.getFullYear() - fechaInicio.getFullYear()
  return diferencia
}

// Datos estáticos como respaldo si la API falla
const fallbackData = [
  {
    titulo: 'Tours Culturales El Salvador',
    descripcion: 'Descubre la rica historia y tradiciones de El Salvador visitando sitios arqueológicos, pueblos coloniales y museos fascinantes.',
    imagen: ''
  },
  {
    titulo: 'Aventuras en la Naturaleza',
    descripcion: 'Explora volcanes activos, lagos cristalinos y bosques tropicales en emocionantes expediciones llenas de adrenalina.',
    imagen: ''
  },
  {
    titulo: 'Experiencias Gastronómicas',
    descripcion: 'Deléitate con los sabores auténticos salvadoreños en tours culinarios que despertarán todos tus sentidos.',
    imagen: ''
  },
  {
    titulo: 'Playas Paradisíacas',
    descripcion: 'Relájate en las mejores playas del Pacífico con arenas negras volcánicas y perfectas olas para surfear.',
    imagen: ''
  }
]

// Estadísticas de la empresa (dinámicas)
const estadisticas = ref([
  { numero: '500+', descripcion: 'Clientes Felices', icono: faFaceSmile, color: 'text-yellow-400' },
  { numero: '0', descripcion: 'Tours Disponibles', icono: faMapLocationDot, color: 'text-yellow-400' },
  { numero: `${calcularAnosExperiencia()}+`, descripcion: 'Años de Experiencia', icono: faStar, color: 'text-yellow-400' },
  { numero: '100%', descripcion: 'Satisfacción Garantizada', icono: faTrophy, color: 'text-yellow-400' }
])

// Servicios principales
const servicios = ref([
  {
    titulo: 'Tours Nacionales',
    tituloColor: 'text-red-600',
    descripcion: 'Explora los destinos más emblemáticos de El Salvador con guías expertos y experiencias inolvidables.',
    icono: faVolcano,
    color: 'text-red-600',
    label: 'Reservar Ahora',
    enlace: '/tours-nacionales',
    botonColor: '!border-2 !border-red-600 !text-red-600 hover:!bg-red-600 hover:!text-white'
  },
  {
    titulo: 'Tours Internacionales',
    tituloColor: 'text-blue-600',
    descripcion: 'Descubre destinos increíbles más allá de nuestras fronteras con tours exclusivos.',
    icono: faGlobeAmericas,
    color: 'text-blue-600',
    label: 'Reservar Ahora',
    enlace: '/tours-internacionales',
    botonColor: '!border-2 !border-blue-600 !text-blue-600 hover:!bg-blue-600 hover:!text-white'
  },
  {
    titulo: 'Reservaciones de Hoteles',
    tituloColor: 'text-red-600',
    descripcion: 'Reserva en hoteles seleccionados con las mejores tarifas y comodidades.',
    icono: faHotel,
    color: 'text-red-600',
    label: 'Reservar Ahora',
    enlace: '/reservaciones',
    botonColor: '!border-2 !border-red-600 !text-red-600 hover:!bg-red-600 hover:!text-white'
  },
  {
    titulo: 'Compra productos en línea',
    tituloColor: 'text-yellow-600',
    descripcion: 'Adquiere productos turísticos y souvenirs directamente desde nuestra tienda en línea.',
    icono: faBagShopping,
    color: 'text-yellow-600',
    label: 'Visitar Tienda',
    enlace: '/tienda',
    botonColor: '!border-2 !border-yellow-600 !text-yellow-600 hover:!bg-yellow-600 hover:!text-white'
  }
])

const cargarTours = async () => {
  try {
    // Crear una instancia limpia de axios para evitar headers de autenticación
    const publicAxios = axios.create({
      withCredentials: false,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })

    // Obtener todos los tours
    const res = await publicAxios.get('/api/tours')
    const data = res.data.data || res.data || []

    // Actualizar el total de tours en las estadísticas
    totalTours.value = data.length
    estadisticas.value[1].numero = data.length > 0 ? `${data.length}+` : '0'

    // Filtrar tours disponibles para el slider
    const disponibles = data.filter(tour => tour.cupos_disponibles > 0)
    disponibles.forEach(tour => {
      slides.value.push({
        nombre: tour.nombre,
        imagenes: tour.imagenes,
      })
    })
  } catch (error) {
    console.error('Error cargando tours:', error)
    // En caso de error, mantener valor por defecto
    estadisticas.value[1].numero = '50+'
  }
}

const cargarPaquetesVisa = async () => {
  try {
    // Crear una instancia limpia de axios para evitar headers de autenticación
    const publicAxios = axios.create({
      withCredentials: false,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })

    // Obtener todos los paquetes de visa
    const res = await publicAxios.get('/api/paquetes-visas')
    const data = res.data.data || res.data || []
    paquetesVisa.value = data
  } catch (error) {
    console.error('Error cargando paquetes de visa:', error)
    paquetesVisa.value = []
  }
}

// Funciones del carrusel
const nextSlide = () => {
  if (paquetesVisa.value.length > 0) {
    currentSlide.value = (currentSlide.value + 1) % Math.ceil(paquetesVisa.value.length / getItemsPerSlide())
  }
}

const prevSlide = () => {
  if (paquetesVisa.value.length > 0) {
    currentSlide.value = currentSlide.value === 0 ? Math.ceil(paquetesVisa.value.length / getItemsPerSlide()) - 1 : currentSlide.value - 1
  }
}

const getItemsPerSlide = () => {
  // Responsive: 1 en móvil, 2 en tablet, 3 en desktop
  if (typeof window !== 'undefined') {
    if (window.innerWidth < 768) return 1
    if (window.innerWidth < 1024) return 2
    return 3
  }
  return 3
}

const getVisiblePaquetes = () => {
  const itemsPerSlide = getItemsPerSlide()
  const start = currentSlide.value * itemsPerSlide
  return paquetesVisa.value.slice(start, start + itemsPerSlide)
}

// Auto-avance del carrusel (opcional)
let autoSlideInterval = null

const startAutoSlide = () => {
  if (paquetesVisa.value.length > getItemsPerSlide()) {
    autoSlideInterval = setInterval(() => {
      nextSlide()
    }, 5000) // Cambia cada 5 segundos
  }
}

const stopAutoSlide = () => {
  if (autoSlideInterval) {
    clearInterval(autoSlideInterval)
    autoSlideInterval = null
  }
}

// Función para manejar errores de imagen
const handleImageError = (event) => {
  console.error('Error cargando imagen:', event.target.src)
  event.target.style.display = 'none'
  // Mostrar el placeholder
  const container = event.target.closest('.relative')
  if (container) {
    container.innerHTML = `
      <div class="h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
        <div class="text-center">
          <svg class="w-16 h-16 text-blue-400 mb-2 mx-auto" fill="currentColor" viewBox="0 0 20 20">
            <path d="M4 3a2 2 0 00-2 2v1.816a.5.5 0 00.106.316l2 3a.5.5 0 00.894 0l2-3a.5.5 0 00.106-.316V5a2 2 0 00-2-2H4zM4 4h1a1 1 0 011 1v1.465L5.5 7.793 5 6.465V5a1 1 0 011-1z"/>
          </svg>
          <p class="text-blue-600 text-sm font-medium">Imagen no disponible</p>
        </div>
      </div>
    `
  }
}

// Funciones para generar mensajes personalizados de WhatsApp
const generarMensajeWhatsApp = (tipo = 'general', nombrePaquete = '') => {
  const mensajes = {
    general: 'Hola VASIR, me gustaría recibir información sobre sus servicios turísticos. ¿Podrían ayudarme?',
    paquete: `Hola VASIR, me interesa el paquete de visa "${nombrePaquete}". ¿Podrían darme más información?`
  }
  return encodeURIComponent(mensajes[tipo] || mensajes.general)
}

// Función para abrir WhatsApp con mensaje personalizado
const abrirWhatsApp = (tipo = 'general', nombrePaquete = '') => {
  const numeroWhatsApp = '50379858777'
  const mensaje = generarMensajeWhatsApp(tipo, nombrePaquete)
  const url = `https://wa.me/${numeroWhatsApp}?text=${mensaje}`
  window.open(url, '_blank')
}

// Funciones para el modal de detalles
const abrirModal = (paquete) => {
  paqueteSeleccionado.value = paquete
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
  paqueteSeleccionado.value = null
}

// Función para convertir string con separador | en array
const convertirALista = (texto) => {
  if (!texto) return []
  return texto.split('|').map(item => item.trim()).filter(item => item.length > 0)
}

onMounted(cargarTours)
onMounted(cargarPaquetesVisa)
onMounted(async () => {
    try {
        // Crear una instancia limpia de axios para evitar headers de autenticación
        const publicAxios = axios.create({
            withCredentials: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })

        const { data } = await publicAxios.get('/api/productos')
        products.value = data.length > 0 ? data : fallbackData
    } catch {
        products.value = fallbackData
    } finally {
        loading.value = false
    }
})

// Iniciar auto-avance cuando el componente se monta
onMounted(() => {
    setTimeout(() => {
        startAutoSlide()
    }, 3000) // Esperar 3 segundos antes de iniciar el auto-avance
})

// Limpiar el intervalo cuando el componente se desmonta
onUnmounted(() => {
    stopAutoSlide()
})
</script>

<template>
  <Catalogo>
    <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-50 min-h-screen pt-20 md:pt-28 lg:pt-28 xl:pt-32">
      <template v-if="loading">
        <div class="p-4 sm:p-6 md:p-8 max-w-7xl mx-auto">
          <div class="animate-pulse space-y-8">
            <!-- Hero loading -->
            <div class="h-96 bg-gradient-to-r from-red-100 via-red-50 to-red-100 rounded-2xl shadow-lg"></div>
            <!-- Stats loading -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
              <div v-for="i in 4" :key="i" class="h-28 bg-white/80 backdrop-blur-sm rounded-xl shadow-md border border-white/20"></div>
            </div>
            <!-- Content loading -->
            <div class="h-72 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20"></div>
          </div>
        </div>
      </template>

      <template v-else>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-red-600 via-red-500 to-blue-600 text-white py-12 sm:py-16 md:py-20 lg:py-24 overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-r from-black/30 via-transparent to-black/30"></div>
          <div class="absolute top-0 left-0 w-full h-full">
            <div class="absolute top-5 sm:top-10 left-5 sm:left-10 w-20 h-20 sm:w-32 sm:h-32 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute top-20 sm:top-40 right-10 sm:right-20 w-32 h-32 sm:w-48 sm:h-48 bg-yellow-400/20 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 sm:bottom-20 left-1/4 sm:left-1/3 w-40 h-40 sm:w-64 sm:h-64 bg-blue-400/15 rounded-full blur-2xl"></div>
          </div>
          <div class="relative max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
            <div class="text-center mb-8 sm:mb-12 md:mb-16">
              <div class="inline-block mb-4 sm:mb-6">
                <div class="bg-gradient-to-r from-yellow-400 to-yellow-400 text-yellow-700 px-3 py-1.5 sm:px-6 sm:py-2 rounded-full font-bold text-xs sm:text-sm shadow-lg">
                  <FontAwesomeIcon :icon="faStar" class="text-yellow-600 mr-2" />Experiencias Únicas Garantizadas
                </div>
              </div>
              <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold mb-4 sm:mb-6 leading-tight">
                ¡Bienvenido a <span class="bg-gradient-to-r from-yellow-300 to-yellow-400 bg-clip-text text-transparent">VASIR</span>!
              </h1>
              <p class="text-base sm:text-lg md:text-xl lg:text-2xl mb-6 sm:mb-8 md:mb-10 opacity-90 max-w-sm sm:max-w-2xl md:max-w-3xl lg:max-w-4xl mx-auto leading-relaxed px-4 sm:px-0">
                Tu puerta de entrada a las experiencias más increíbles de El Salvador.
                Descubre paisajes únicos, cultura vibrante y aventuras inolvidables.
              </p>
              <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center px-4 sm:px-0">
                <Link :href="route('tours-nacionales')">
                  <Button class="w-full sm:w-auto !bg-gradient-to-r !from-yellow-500 !to-yellow-400 !border-none !px-6 sm:!px-8 !py-3 sm:!py-4 !text-yellow-700 font-bold rounded-xl hover:!from-yellow-400 hover:!to-yellow-300 transform hover:scale-105 transition-all duration-300 shadow-xl hover:shadow-2xl text-sm sm:text-base">
                    <FontAwesomeIcon :icon="faVolcano" class="text-yellow-700" />Ver Tours Nacionales
                  </Button>
                </Link>
                <Link :href="route('tours-internacionales')">
                  <Button outlined class="w-full sm:w-auto !border-2 !border-white/80 !text-yellow-400 !px-6 sm:!px-8 !py-3 sm:!py-4 font-bold rounded-xl hover:!bg-white/20 hover:!border-white backdrop-blur-sm transform hover:scale-105 transition-all duration-300 shadow-lg text-sm sm:text-base">
                    <FontAwesomeIcon :icon="faMapLocationDot" class="text-yellow-400" />Ver Tours Internacionales
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </section>

        <!-- CTA Final -->
        <section class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-br from-white via-white to-white relative overflow-hidden">
          <div class="relative max-w-3xl sm:max-w-4xl lg:max-w-5xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 text-center">
            <div class="mb-6 sm:mb-8">
              <span class="bg-red-600 backdrop-blur-sm text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-full font-semibold text-xs sm:text-sm shadow-lg">
                ¡Comienza Tu Aventura!
              </span>
            </div>
            <h2 class="text-red-600 text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold mb-4 sm:mb-6 leading-tight">
              <FontAwesomeIcon :icon="faRocketchat" class="text-red-600 mr-2" />¿Listo para tu próxima aventura?
            </h2>
            <p class="text-black text-sm sm:text-base md:text-lg lg:text-xl xl:text-2xl mb-8 sm:mb-10 md:mb-12 opacity-90 max-w-xs sm:max-w-2xl md:max-w-3xl lg:max-w-4xl mx-auto leading-relaxed px-4 sm:px-0">
              Permítenos ser parte de tus mejores recuerdos. Contáctanos hoy mismo y comienza a planificar
              la experiencia de viaje que siempre has soñado. Tu próxima gran aventura te está esperando.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center px-4 sm:px-0">
              <Link :href="route('contactos')">
                <Button class="w-full sm:w-auto !bg-gradient-to-r !from-yellow-500 !to-yellow-400 !border-none !px-6 sm:!px-8 md:!px-10 !py-3 sm:!py-4 !text-black font-bold rounded-xl hover:!from-yellow-400 hover:!to-yellow-300 transform hover:scale-105 transition-all duration-300 shadow-xl hover:shadow-2xl text-sm sm:text-base">
                  <FontAwesomeIcon :icon="faPhone" class="text-red-600" />Contactar Ahora
                </Button>
              </Link>
            </div>
          </div>
        </section>

        <!-- Paquetes de Visa -->
        <section v-if="paquetesVisa.length > 0" class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-br from-red-600 via-red-500 to-blue-600 text-white relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-r from-black/20 via-transparent to-black/20"></div>
          <div class="absolute top-0 left-0 w-full h-full">
            <div class="absolute top-5 sm:top-10 right-5 sm:right-10 w-24 h-24 sm:w-40 sm:h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 sm:bottom-20 left-10 sm:left-20 w-32 h-32 sm:w-56 sm:h-56 bg-yellow-400/15 rounded-full blur-3xl"></div>
          </div>
          <div class="relative mx-auto px-3 sm:px-4 md:px-6 lg:px-8 max-w-7xl">
            <div class="text-center mb-8 sm:mb-12 md:mb-16">
              <div class="inline-block mb-3 sm:mb-4">
                <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-full font-semibold text-xs sm:text-sm shadow-lg">
                  Paquetes Especiales
                </span>
              </div>

              <!-- Imagen world.png -->
              <div class="mb-4 sm:mb-6 flex justify-center">
                <img src="/images/world.png" alt="Mundo" class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 object-contain" />
              </div>

              <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-4 sm:mb-6">
                <FontAwesomeIcon :icon="faPassport" class="text-yellow-400 mr-2" />Paquetes de Visa
              </h2>

              <div class="max-w-sm sm:max-w-xl md:max-w-2xl lg:max-w-3xl mx-auto px-4 sm:px-0">
                <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-yellow-300 mb-3 sm:mb-4">
                  ¡Tu visa americana con la asesoría de VASIR!
                </h3>

                <p class="text-sm sm:text-base md:text-lg opacity-90 leading-relaxed mb-3 sm:mb-4">
                  ¿Pensando en viajar a Estados Unidos?
                </p>

                <p class="text-sm sm:text-base md:text-lg opacity-90 leading-relaxed mb-4 sm:mb-6">
                  En VASIR te acompañamos paso a paso para que tu proceso de visa sea fácil, rápido y sin complicaciones.
                  <FontAwesomeIcon :icon="faPlane" class="text-yellow-400 ml-2" />
                </p>

                <p class="text-base sm:text-lg md:text-xl font-semibold text-yellow-300 mb-6 sm:mb-8">
                  Contamos con las siguientes opciones para ti:
                </p>
              </div>
            </div>

            <!-- Carrusel de Paquetes -->
            <div class="relative" @mouseenter="stopAutoSlide" @mouseleave="startAutoSlide">
              <!-- Controles del carrusel -->
              <div class="flex justify-between items-center mb-6 sm:mb-8">
                <button
                  @click="prevSlide"
                  class="p-2 sm:p-3 bg-white/20 backdrop-blur-sm rounded-full shadow-lg border border-white/30 hover:bg-white/30 hover:border-white/50 transition-all duration-300 transform hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="paquetesVisa.length <= getItemsPerSlide()"
                >
                  <FontAwesomeIcon :icon="faChevronLeft" class="text-white text-sm sm:text-base" />
                </button>

                <div class="flex space-x-2">
                  <span
                    v-for="(dot, index) in Math.ceil(paquetesVisa.length / getItemsPerSlide())"
                    :key="index"
                    @click="currentSlide = index; stopAutoSlide(); startAutoSlide()"
                    :class="[
                      'w-2 h-2 sm:w-3 sm:h-3 rounded-full cursor-pointer transition-all duration-300',
                      currentSlide === index ? 'bg-yellow-400 scale-110' : 'bg-white/40 hover:bg-white/60'
                    ]"
                  ></span>
                </div>

                <button
                  @click="nextSlide"
                  class="p-2 sm:p-3 bg-white/20 backdrop-blur-sm rounded-full shadow-lg border border-white/30 hover:bg-white/30 hover:border-white/50 transition-all duration-300 transform hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="paquetesVisa.length <= getItemsPerSlide()"
                >
                  <FontAwesomeIcon :icon="faChevronRight" class="text-white text-sm sm:text-base" />
                </button>
              </div>

              <!-- Tarjetas del carrusel -->
              <div class="overflow-hidden">
                <div
                  class="flex transition-transform duration-500 ease-in-out"
                  :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
                >
                  <div
                    v-for="(paquete, index) in paquetesVisa"
                    :key="paquete.id"
                    :class="[
                      'flex-shrink-0 px-2 sm:px-3',
                      'w-full md:w-1/2 lg:w-1/3'
                    ]"
                  >
                    <div class="bg-white backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl border border-white/70 overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 sm:hover:-translate-y-2 transition-all duration-300 group flex flex-col">
                      <!-- Área clickeable para abrir modal -->
                      <div @click="abrirModal(paquete)" class="cursor-pointer flex flex-col">
                        <!-- Imagen del paquete -->
                        <div v-if="paquete.imagenes && paquete.imagenes.length > 0" class="relative h-48 sm:h-56 md:h-48 overflow-hidden flex-shrink-0">
                          <img
                            :src="`/storage/paquetesvisas/${paquete.imagenes[0].nombre}`"
                            :alt="paquete.nombre"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            @error="handleImageError"
                          />
                          <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>
                        </div>

                        <!-- Placeholder cuando no hay imagen -->
                        <div v-else class="relative h-48 sm:h-56 md:h-48 overflow-hidden bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center flex-shrink-0">
                          <div class="text-center">
                            <FontAwesomeIcon :icon="faPassport" class="text-4xl text-blue-400 mb-2" />
                            <p class="text-blue-600 text-sm font-medium">Sin imagen</p>
                          </div>
                        </div>

                        <!-- Contenido de la tarjeta -->
                        <div class="p-4 sm:p-6 flex flex-col">
                          <!-- Título y descripción -->
                          <div>
                            <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-blue-700 mb-2 sm:mb-3 leading-tight group-hover:text-blue-800 transition-colors duration-300">
                              {{ paquete.nombre }}
                            </h3>

                            <!-- Descripción con altura mínima -->
                            <div class="mb-4 min-h-[2rem] sm:min-h-[2.5rem]">
                              <p v-if="paquete.descripcion" class="text-gray-600 text-sm sm:text-base leading-tight overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2;">
                                {{ paquete.descripcion }}
                              </p>
                            </div>
                          </div>

                          <!-- Precio -->
                          <div class="flex items-center justify-between pt-4 border-t border-gray-200 mb-2">
                            <div class="flex items-center">
                              <FontAwesomeIcon :icon="faDollarSign" class="text-green-600 text-lg mr-1" />
                              <span class="text-2xl sm:text-3xl font-bold text-green-600">
                                {{ parseFloat(paquete.precio || 0).toFixed(2) }}
                              </span>
                            </div>

                            <div class="text-right">
                              <span class="text-sm text-gray-600 block font-medium">Precio</span>
                              <span class="text-sm text-gray-500">USD</span>
                            </div>
                          </div>

                          <!-- Mensaje para ver detalles -->
                          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-2 mt-2">
                            <p class="text-blue-700 text-xs sm:text-sm font-medium text-center">
                              💡 Dar clic para ver todos los detalles
                            </p>
                          </div>
                        </div>
                      </div>

                      <!-- Botón de contacto (fuera del área clickeable) -->
                      <div class="px-3 sm:px-4 pb-3 sm:pb-4">
                        <button
                          @click="abrirWhatsApp('paquete', paquete.nombre)"
                          class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-3 sm:px-4 py-2 sm:py-3 rounded-lg sm:rounded-xl font-semibold hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center text-sm sm:text-base"
                        >
                          <FontAwesomeIcon :icon="faWhatsapp" class="mr-2" />
                          Consultar por WhatsApp
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Indicador de paquetes vacío (solo si no hay paquetes) -->
              <div v-if="paquetesVisa.length === 0" class="text-center py-12 sm:py-16">
                <FontAwesomeIcon :icon="faPassport" class="text-6xl sm:text-7xl text-white/30 mb-4 sm:mb-6" />
                <p class="text-white/70 text-lg sm:text-xl">No hay paquetes de visa disponibles en este momento.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Servicios -->
        <section class="py-10 sm:py-16 md:py-20 lg:py-22 bg-gradient-to-br from-gray-50 via-blue-50/40 to-red-50/40 relative">
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
          <div class="relative mx-auto px-2 md:px-3 lg:px-4 xl:px-5">
            <div class="text-center mb-8 sm:mb-12 md:mb-16">
              <div class="inline-block mb-3 sm:mb-4">
                <span class="bg-gradient-to-r from-red-600 to-red-500 text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-full font-semibold text-xs sm:text-sm shadow-lg">
                  Servicios Premium
                </span>
              </div>
              <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-red-700 to-red-600 bg-clip-text text-transparent mb-4 sm:mb-6">
                <FontAwesomeIcon :icon="faBullseye" class="text-red-600 mr-2" />Nuestros Servicios
              </h2>
              <p class="text-sm sm:text-base md:text-lg text-gray-600 max-w-sm sm:max-w-xl md:max-w-2xl lg:max-w-3xl mx-auto leading-relaxed px-4 sm:px-0">
                Ofrecemos una gama completa de servicios turísticos profesionales para hacer realidad
                tus sueños de viaje con la más alta calidad y atención personalizada.
              </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-3 md:gap-3 lg:gap-4 xl:gap-5">
              <div
                v-for="servicio in servicios"
                :key="servicio.titulo"
                class="bg-white/80 backdrop-blur-sm rounded-xl sm:rounded-2xl p-4 sm:p-6 md:p-4 shadow-lg sm:shadow-xl border border-white/40 text-center hover:shadow-2xl transform hover:-translate-y-1 sm:hover:-translate-y-2 transition-all duration-300 group hover:bg-white/90 flex flex-col justify-between"
              >
                <div class="text-3xl sm:text-4xl md:text-5xl mb-3 sm:mb-4 md:mb-6 group-hover:scale-110 transition-transform duration-300 group-hover:animate-bounce">
                  <FontAwesomeIcon :icon="servicio.icono" :class="servicio.color" class="md:pt-4"/>
                </div>
                <h3 :class="['text-base sm:text-lg md:text-xl font-bold mb-2 sm:mb-3 md:mb-4 leading-tight', servicio.tituloColor]">{{ servicio.titulo}}</h3>
                <p class="text-gray-800 mb-4 sm:mb-5 md:mb-6 text-xs sm:text-sm md:text-lg leading-relaxed opacity-90">{{ servicio.descripcion }}</p>
                <Link :href="servicio.enlace" class="md:mb-4">
                  <Button
                    v-if="servicio.label"
                    :label="servicio.label"
                    :class="servicio.botonColor"
                    outlined
                    class="w-full sm:w-auto /*!border-2 !border-red-600 !text-red-600*/ !px-4 sm:!px-6 !py-1.5 sm:!py-2 !text-xs sm:!text-sm font-semibold rounded-lg sm:rounded-xl /*hover:!bg-red-600 hover:!text-white*/ transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-105"
                    size="small"
                  />
                </Link>
              </div>
            </div>
          </div>
        </section>

        <!-- Estadísticas -->
        <section class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-br from-red-600 via-red-500 to-blue-600 text-white relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-r from-black/20 via-transparent to-black/20"></div>
          <div class="absolute top-0 left-0 w-full h-full">
            <div class="absolute top-5 sm:top-10 right-5 sm:right-10 w-24 h-24 sm:w-40 sm:h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 sm:bottom-20 left-10 sm:left-20 w-32 h-32 sm:w-56 sm:h-56 bg-yellow-400/15 rounded-full blur-3xl"></div>
          </div>
          <div class="relative mx-auto px-2 md:px-3 lg:px-4 xl:px-8">
            <div class="text-center mb-6">
              <div class="inline-block mb-3 sm:mb-4">
                <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-full font-semibold text-xs sm:text-sm shadow-lg">
                  Nuestra Experiencia
                </span>
              </div>
              <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-4">
                <FontAwesomeIcon :icon="faStar" class="text-yellow-400 mr-2" />Nuestra Trayectoria
              </h2>
              <p class="text-sm sm:text-base md:text-lg lg:text-xl opacity-90 max-w-sm sm:max-w-xl md:max-w-2xl lg:max-w-3xl mx-auto leading-relaxed px-4 sm:px-0">
                Números que respaldan nuestra excelencia y compromiso con experiencias inolvidables
              </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-4 md:gap-3 lg:gap-4 xl:gap-8">
              <div
                v-for="stat in estadisticas"
                :key="stat.descripcion"
                class="text-center p-4 sm:p-6 md:p-8 bg-white/15 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-white/20 hover:bg-white/25 transition-all duration-300 transform hover:-translate-y-1 sm:hover:-translate-y-2 hover:scale-105 group shadow-lg sm:shadow-xl"
              >
                <div class="text-3xl sm:text-4xl md:text-5xl mb-2 sm:mb-3 md:mb-4 group-hover:scale-110 transition-transform duration-300"><FontAwesomeIcon :icon="stat.icono" :class="stat.color" /></div>
                <div class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-2 sm:mb-3 bg-gradient-to-r from-yellow-300 to-yellow-400 bg-clip-text text-transparent">{{ stat.numero }}</div>
                <div class="text-xs sm:text-sm md:text-base opacity-90 font-medium px-1">{{ stat.descripcion }}</div>
              </div>
            </div>
          </div>
        </section>
      </template>
    </div>

    <!-- Modal de detalles del paquete -->
    <Dialog
      v-model:visible="mostrarModal"
      :closable="false"
      :modal="true"
      :draggable="false"
      class="mx-4"
      :style="{ width: '90vw', maxWidth: '600px' }"
    >
      <template #header>
        <div class="flex items-center gap-3">
          <FontAwesomeIcon :icon="faPassport" class="text-blue-600 text-xl" />
          <span class="text-xl font-bold text-blue-700">Detalles del Paquete</span>
        </div>
      </template>

      <!-- Contenido scrolleable del modal -->
      <div v-if="paqueteSeleccionado" class="space-y-6 max-h-96 overflow-y-auto">
        <!-- Imagen -->
        <div class="text-center">
          <div v-if="paqueteSeleccionado.imagenes && paqueteSeleccionado.imagenes.length > 0" class="relative">
            <img
              :src="`/storage/paquetesvisas/${paqueteSeleccionado.imagenes[0].nombre}`"
              :alt="paqueteSeleccionado.nombre"
              class="w-full h-64 object-cover rounded-lg shadow-lg"
              @error="handleImageError"
            />
          </div>
          <div v-else class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center">
            <div class="text-center">
              <FontAwesomeIcon :icon="faPassport" class="text-6xl text-blue-400 mb-3" />
              <p class="text-blue-600 text-lg font-medium">Sin imagen</p>
            </div>
          </div>
        </div>

        <!-- Nombre -->
        <div>
          <h3 class="text-2xl font-bold text-blue-700 text-center mb-4">
            {{ paqueteSeleccionado.nombre }}
          </h3>
        </div>

        <!-- Descripción (solo si existe) -->
        <div v-if="paqueteSeleccionado.descripcion" class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-800 mb-2">Descripción</h4>
          <p class="text-gray-700 leading-relaxed">{{ paqueteSeleccionado.descripcion }}</p>
        </div>

        <!-- Precio -->
        <div class="bg-green-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-800 mb-2">Precio</h4>
          <div class="flex items-center">
            <FontAwesomeIcon :icon="faDollarSign" class="text-green-600 text-2xl mr-2" />
            <span class="text-3xl font-bold text-green-600">
              {{ parseFloat(paqueteSeleccionado.precio || 0).toFixed(2) }} USD
            </span>
          </div>
        </div>

        <!-- Incluye (solo si existe) -->
        <div v-if="paqueteSeleccionado.incluye" class="bg-blue-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-800 mb-3">✅ Incluye</h4>
          <ul class="space-y-2">
            <li
              v-for="(item, index) in convertirALista(paqueteSeleccionado.incluye)"
              :key="index"
              class="flex items-start text-gray-700"
            >
              <span class="text-green-600 mr-2 mt-1 text-sm">•</span>
              <span class="leading-relaxed">{{ item }}</span>
            </li>
          </ul>
        </div>

        <!-- No incluye (solo si existe) -->
        <div v-if="paqueteSeleccionado.no_incluye" class="bg-red-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-800 mb-3">❌ No incluye</h4>
          <ul class="space-y-2">
            <li
              v-for="(item, index) in convertirALista(paqueteSeleccionado.no_incluye)"
              :key="index"
              class="flex items-start text-gray-700"
            >
              <span class="text-red-600 mr-2 mt-1 text-sm">•</span>
              <span class="leading-relaxed">{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Footer fijo con botones -->
      <template #footer>
        <div class="flex flex-col sm:flex-row gap-3 w-full">
          <button
            @click="abrirWhatsApp('paquete', paqueteSeleccionado?.nombre)"
            class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center"
          >
            <FontAwesomeIcon :icon="faWhatsapp" class="mr-2" />
            Consultar por WhatsApp
          </button>
          <button
            @click="cerrarModal"
            class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition-all duration-300 shadow-lg hover:shadow-xl"
          >
            Cerrar
          </button>
        </div>
      </template>
    </Dialog>
  </Catalogo>
</template>
