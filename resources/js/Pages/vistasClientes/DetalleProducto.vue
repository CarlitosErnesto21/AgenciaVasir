<template>
  <Catalogo>
    <div class="min-h-screen bg-gray-50 py-4 sm:py-8 mt-32">
      <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8 relative">
        <!-- Botón del carrito en la esquina superior derecha -->
        <div class="fixed top-20 sm:top-24 right-4 sm:right-6 z-[60]">
          <CarritoButton />
        </div>

        <!-- Breadcrumb -->
        <nav class="mb-4 sm:mb-8">
          <ol class="flex items-center space-x-1 sm:space-x-2 text-xs sm:text-sm">
            <li>
              <Link
                href="/"
                class="text-red-600 hover:text-red-800"
              >
                Inicio
              </Link>
            </li>
            <li class="text-gray-500">/</li>
            <li>
              <Link
                href="/tienda"
                class="text-red-600 hover:text-red-800"
              >
                Tienda
              </Link>
            </li>
            <li class="text-gray-500">/</li>
            <li class="text-gray-900 font-medium truncate">{{ producto.nombre }}</li>
          </ol>
        </nav>

        <!-- Estado de carga -->
        <div v-if="loading" class="bg-white rounded-lg shadow-lg overflow-hidden p-8">
          <div class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
            <span class="ml-3 text-gray-600">Cargando detalles del producto...</span>
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
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error al cargar el producto</h3>
            <p class="text-gray-600 mb-4">{{ error }}</p>
            <Link href="/tienda" class="bg-red-600 hover:bg-red-700 px-4 py-2 text-white rounded-lg transition-colors inline-block">
              Volver a la tienda
            </Link>
          </div>
        </div>

        <!-- Contenido principal -->
        <div v-else-if="producto" class="bg-white rounded-lg shadow-lg overflow-hidden">
          <!-- Galería de imágenes -->
          <div
            class="relative w-full h-64 sm:h-72 md:h-80 lg:h-96 bg-gray-100 rounded-t-lg overflow-hidden flex items-center justify-center"
            @mouseenter="detenerCarrusel"
            @mouseleave="iniciarCarrusel"
          >
            <div v-if="producto.imagenes && producto.imagenes.length > 0" class="relative w-full h-full flex items-center justify-center">
              <img
                :src="obtenerImagenActual()"
                :alt="producto.nombre"
                class="max-w-full max-h-full object-contain transition-opacity duration-500"
              />

              <!-- Controles de navegación de imágenes -->
              <div v-if="producto.imagenes.length > 1" class="absolute inset-0 flex items-center justify-between p-2 sm:p-4 pointer-events-none">
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
              <div v-if="producto.imagenes.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10">
                <div class="flex space-x-2">
                  <button
                    v-for="(imagen, index) in producto.imagenes"
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

          <!-- Información del producto -->
          <div class="p-4 sm:p-6 md:p-8">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
              <!-- Información principal -->
              <div class="order-2 xl:order-1">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-3 sm:mb-4">{{ producto.nombre }}</h1>

                <div class="mb-4 sm:mb-6">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-gradient-to-r from-red-600 to-red-700 text-white shadow-xl border border-white/20 transform hover:scale-105 transition-all duration-300" style="box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4), 0 0 0 2px white;">
                    {{ producto.categoria || 'Sin categoría' }}
                  </span>
                </div>

                <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-tag mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Categoría:</strong> {{ producto.categoria || 'Sin categoría' }}</span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-box mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Stock disponible:</strong>
                      <span :class="obtenerClaseStock(producto)">
                        {{ producto.stock_actual || 0 }} unidades
                      </span>
                    </span>
                  </div>
                  <div class="flex items-start text-gray-600 text-sm sm:text-base">
                    <i class="pi pi-info-circle mr-2 sm:mr-3 text-blue-600 mt-0.5 text-sm sm:text-base"></i>
                    <span><strong>Descripción:</strong></span>
                  </div>
                  <div class="ml-6 text-gray-700 text-sm sm:text-base leading-relaxed">
                    {{ producto.descripcion || 'Sin descripción disponible' }}
                  </div>
                </div>

                <!-- Precio -->
                <div class="mb-4 sm:mb-6 order-1 xl:order-none">
                  <div class="text-2xl sm:text-3xl font-bold text-green-600">
                    ${{ parseFloat(producto.precio || 0).toFixed(2) }}
                  </div>
                  <div class="text-gray-600 text-sm sm:text-base">Por unidad</div>
                </div>

                <!-- Selector de cantidad -->
                <div v-if="(producto.stock_actual || 0) > 0" class="mb-4 sm:mb-6">
                  <div class="flex items-center justify-center gap-4">
                    <label class="text-sm sm:text-base text-gray-700 font-medium">Cantidad:</label>
                    <div class="flex items-center gap-3">
                      <button
                        @click="decrementarCantidad"
                        class="w-10 h-10 flex items-center justify-center border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="cantidad <= 1"
                      >
                        <FontAwesomeIcon :icon="faMinus" class="w-4 h-4 text-gray-600" />
                      </button>

                      <span class="w-12 text-center text-lg font-semibold text-gray-800">
                        {{ cantidad }}
                      </span>

                      <button
                        @click="incrementarCantidad"
                        class="w-10 h-10 flex items-center justify-center border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="cantidad >= (producto.stock_actual || 0)"
                      >
                        <FontAwesomeIcon :icon="faPlus" class="w-4 h-4 text-gray-600" />
                      </button>
                    </div>
                  </div>

                  <!-- Indicador de stock disponible -->
                  <div class="text-center mt-2">
                    <span class="text-xs sm:text-sm text-gray-500">
                      Máximo disponible: {{ producto.stock_actual || 0 }} unidades
                    </span>
                  </div>
                </div>

                <!-- Botón de compra -->
                <button
                  @click="comprarProducto"
                  :disabled="(producto.stock_actual || 0) === 0"
                  :class="[
                    'w-full font-semibold py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors duration-200 text-sm sm:text-base flex items-center justify-center gap-2',
                    (producto.stock_actual || 0) === 0
                      ? 'bg-gray-400 text-white cursor-not-allowed'
                      : 'bg-red-600 hover:bg-red-700 text-white'
                  ]"
                >
                  <template v-if="(producto.stock_actual || 0) === 0">
                    Sin Stock Disponible
                  </template>
                  <template v-else>
                    <FontAwesomeIcon :icon="faShoppingCart" class="w-4 h-4" />
                    Agregar {{ cantidad }} al Carrito
                  </template>
                </button>
              </div>

              <!-- Detalles adicionales -->
              <div class="order-3 xl:order-2">
                <!-- Información adicional del producto -->
                <div class="mb-4 sm:mb-6">
                  <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 sm:p-6 border border-blue-200">
                    <h3 class="text-lg sm:text-xl font-semibold text-blue-900 mb-3 flex items-center">
                      <i class="pi pi-shopping-cart mr-2 text-sm sm:text-base"></i>
                      Información de compra
                    </h3>
                    <ul class="text-blue-800 space-y-2 text-sm sm:text-base">
                      <li class="flex items-center">
                        <i class="pi pi-check-circle mr-2 text-green-600"></i>
                        Producto en stock y disponible
                      </li>
                      <li class="flex items-center">
                        <i class="pi pi-shield mr-2 text-green-600"></i>
                        Garantía de calidad
                      </li>
                      <li class="flex items-center">
                        <i class="pi pi-credit-card mr-2 text-green-600"></i>
                        Wompi como pasarela de pago segura
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Información importante -->
                <div class="bg-yellow-50 rounded-lg p-3 sm:p-4 border border-yellow-200">
                  <h3 class="text-base sm:text-lg font-semibold text-yellow-900 mb-2">
                    <i class="pi pi-exclamation-triangle mr-2 text-sm sm:text-base"></i>
                    Información importante
                  </h3>
                  <ul class="text-yellow-800 space-y-1 text-xs sm:text-sm">
                    <li>• Verificar disponibilidad antes de realizar el pedido</li>
                    <li>• Los precios pueden variar según la disponibilidad</li>
                    <li>• Producto sujeto a stock disponible</li>
                    <li>• Para pedidos especiales, contactar directamente</li>
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
            class="inline-flex items-center text-white text-sm sm:text-base py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors duration-200 bg-red-400 hover:bg-red-500"
          >
            <i class="pi pi-arrow-left mr-2"></i>
            Regresar a la Tienda
          </button>
        </div>
      </div>
    </div>

    <!-- Toast para notificaciones -->
    <Toast class="z-[9999]" />

    <!-- Modal de autenticación requerida -->
    <ModalAuthRequerido
      v-model:visible="mostrarModalAuth"
      :productoInfo="productoSeleccionado"
    />

    <!-- Carrito de compras -->
    <CarritoCompras />
  </Catalogo>
</template>

<script setup>
import Catalogo from '../Catalogo.vue'
import CarritoCompras from './TiendaViews/CarritoCompras.vue'
import CarritoButton from './TiendaViews/CarritoButton.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import Toast from 'primevue/toast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faShoppingCart, faMinus, faPlus } from '@fortawesome/free-solid-svg-icons'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useCarritoStore } from '@/stores/carrito'

const page = usePage()
const user = computed(() => page.props.auth.user)
const toast = useToast()
const carrito = useCarritoStore()

// Props
const props = defineProps({
  producto: {
    type: Object,
    required: true
  }
})

// Estados reactivos
const loading = ref(false)
const error = ref(null)

// Estado del modal de autenticación
const mostrarModalAuth = ref(false)
const productoSeleccionado = ref(null)

// Variables para la galería de imágenes
const currentImageIndex = ref(0)
const carruselInterval = ref(null)

// Estado para la cantidad seleccionada
const cantidad = ref(1)

// Usar directamente los datos que vienen del controlador
const producto = computed(() => props.producto)

// Los datos del producto ya vienen del controlador, no necesitamos hacer llamadas adicionales

// Función para obtener la imagen actual
const obtenerImagenActual = () => {
  if (!producto.value?.imagenes || producto.value.imagenes.length === 0) {
    return 'https://via.placeholder.com/800x500/ef4444/ffffff?text=Sin+Imagen+Disponible'
  }

  const imagen = producto.value.imagenes[currentImageIndex.value]
  const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre

  return `/storage/productos/${nombreImagen}`
}

// Funciones de navegación de imágenes
const imagenAnterior = () => {
  if (producto.value.imagenes && producto.value.imagenes.length > 1) {
    currentImageIndex.value = currentImageIndex.value === 0
      ? producto.value.imagenes.length - 1
      : currentImageIndex.value - 1
    iniciarCarrusel() // Reiniciar el carrusel después de navegación manual
  }
}

const imagenSiguiente = () => {
  if (producto.value.imagenes && producto.value.imagenes.length > 1) {
    currentImageIndex.value = (currentImageIndex.value + 1) % producto.value.imagenes.length
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
  if (producto.value.imagenes && producto.value.imagenes.length > 1) {
    carruselInterval.value = setInterval(() => {
      currentImageIndex.value = (currentImageIndex.value + 1) % producto.value.imagenes.length
    }, 3000) // Cambiar cada 3 segundos
  }
}

const detenerCarrusel = () => {
  if (carruselInterval.value) {
    clearInterval(carruselInterval.value)
    carruselInterval.value = null
  }
}

// Funciones para manejar la cantidad
const incrementarCantidad = () => {
  const stockDisponible = producto.value.stock_actual || 0
  if (cantidad.value < stockDisponible) {
    cantidad.value++
  }
}

const decrementarCantidad = () => {
  if (cantidad.value > 1) {
    cantidad.value--
  }
}

// Función para comprar el producto
const comprarProducto = () => {
  if (producto.value.stock_actual <= 0) {
    toast.add({
      severity: 'warn',
      summary: 'Sin stock',
      detail: `El producto "${producto.value.nombre}" no está disponible`,
      life: 3000
    })
    return
  }

  // Verificar si el usuario está autenticado
  if (!user.value) {
    // Mostrar modal de autenticación
    productoSeleccionado.value = producto.value
    mostrarModalAuth.value = true
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
        detail: 'Solo las cuentas de Cliente pueden realizar compras. Puedes ver los detalles del producto.',
        life: 5000
      })
      return
    }
  }

  // Agregar al carrito usando Pinia con la cantidad seleccionada
  const productoConCantidad = { ...producto.value, cantidadSeleccionada: cantidad.value }
  const resultado = carrito.agregarProducto(productoConCantidad)

  if (!resultado.success) {
    // Solo mostrar toast para errores
    toast.add({
      severity: 'warn',
      summary: 'Stock insuficiente',
      detail: resultado.message,
      life: 3000
    })
  } else {
    // Resetear la cantidad a 1 después de agregar exitosamente
    cantidad.value = 1
  }
  // Para éxitos, la animación del carrito se activa automáticamente
}

// Función para obtener la clase CSS según disponibilidad de stock
const obtenerClaseStock = (producto) => {
  const stockActual = producto.stock_actual || 0

  if (stockActual === 0) {
    return 'text-red-600 font-bold' // Sin stock
  } else if (stockActual <= 5) {
    return 'text-orange-600 font-bold' // Poco stock
  } else if (stockActual <= 15) {
    return 'text-yellow-600 font-semibold' // Stock moderado
  } else {
    return 'text-green-600 font-medium' // Buen stock
  }
}

// Función para regresar
const regresar = () => {
  router.visit('/tienda')
}

// Lifecycle hooks
onMounted(() => {
  iniciarCarrusel()
  // Verificar estado de autenticación para el carrito
  carrito.verificarEstadoAutenticacion()
})

onUnmounted(() => {
  detenerCarrusel()
})
</script>
