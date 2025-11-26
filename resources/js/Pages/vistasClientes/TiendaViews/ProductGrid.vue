<script setup>
import ImageWithFallback from '@/Components/ImageWithFallback.vue'
import { faShoppingCart, faMinus, faPlus } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { ref, reactive } from 'vue'

// Props del componente
const props = defineProps({
  loading: {
    type: Boolean,
    required: true
  },
  filteredProducts: {
    type: Array,
    required: true
  }
})

// Emits del componente
const emit = defineEmits([
  'comprar-producto',
  'ir-a-detalle'
])

// Estado reactivo para las cantidades seleccionadas
const cantidades = reactive({})

// Métodos
const getCantidad = (productoId) => {
  return cantidades[productoId] || 1
}

const setCantidad = (productoId, cantidad) => {
  cantidades[productoId] = Math.max(1, Math.min(cantidad, getStockActual(productoId)))
}

const getStockActual = (productoId) => {
  const producto = props.filteredProducts.find(p => p.id === productoId)
  return producto ? producto.stock_actual : 0
}

const incrementarCantidad = (productoId) => {
  const cantidadActual = getCantidad(productoId)
  const stockDisponible = getStockActual(productoId)
  if (cantidadActual < stockDisponible) {
    setCantidad(productoId, cantidadActual + 1)
  }
}

const decrementarCantidad = (productoId) => {
  const cantidadActual = getCantidad(productoId)
  if (cantidadActual > 1) {
    setCantidad(productoId, cantidadActual - 1)
  }
}

const comprarProducto = (producto) => {
  const cantidad = getCantidad(producto.id)
  emit('comprar-producto', { ...producto, cantidadSeleccionada: cantidad })
  // Resetear la cantidad a 1 después de emitir el evento
  setCantidad(producto.id, 1)
}

const irADetalle = (producto) => {
  emit('ir-a-detalle', producto)
}

// Helper para construir URL de imagen
const getImageUrl = (producto) => {
  if (!producto.imagen) {
    return null
  }

  // Si ya es una URL completa, usarla tal como está
  if (producto.imagen.startsWith('http') || producto.imagen.startsWith('data:')) {
    return producto.imagen
  }

  // Construir URL relativa
  return `/storage/productos/${producto.imagen}`
}
</script>

<template>
  <!-- Loading state -->
  <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-2 sm:gap-4 md:gap-6">
    <div v-for="i in 8" :key="i" class="animate-pulse">
      <div class="bg-gradient-to-br from-white via-gray-50 to-gray-100 rounded-xl border-2 border-gray-200 p-4 sm:p-6 shadow-lg">
        <div class="h-32 bg-gradient-to-br from-gray-200 to-gray-300 rounded-lg mb-4"></div>
        <div class="h-4 bg-gradient-to-r from-gray-200 to-gray-300 rounded mb-3"></div>
        <div class="h-3 bg-gradient-to-r from-gray-200 to-gray-300 rounded mb-3 w-3/4"></div>
        <div class="h-6 bg-gradient-to-r from-green-200 to-green-300 rounded mb-4 w-1/2"></div>
        <div class="h-8 bg-gradient-to-r from-red-200 to-blue-200 rounded"></div>
      </div>
    </div>
  </div>

  <!-- Grid de productos -->
  <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-2 sm:gap-4 md:gap-6">
    <div
      v-for="producto in filteredProducts"
      :key="producto.id"
      class="border-2 border-gray-200 bg-gradient-to-br from-white via-blue-50/30 to-red-50/30 shadow-lg hover:shadow-2xl transition-all duration-500 flex flex-col transform hover:-translate-y-2 hover:border-red-300 group cursor-pointer rounded-xl overflow-hidden"
      style="height: 360px; min-height: 340px;"
      @click="irADetalle(producto)"
    >
      <!-- Header: Imagen -->
      <div class="w-full h-40 sm:h-44 bg-gradient-to-br from-gray-100 via-blue-100 to-red-100 overflow-hidden relative">
        <ImageWithFallback
          :src="getImageUrl(producto)"
          :alt="producto.nombre"
          :fallback-text="producto.nombre"
          container-class="w-full h-full"
          image-class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
        />
        <div class="absolute top-1 right-1 bg-gradient-to-r from-green-600 to-emerald-600 text-white px-1 py-0.5 rounded text-xs font-normal shadow-xl border border-white/20 leading-none md:top-2 md:right-2 md:px-2.5 md:py-1 md:rounded-full md:font-bold md:shadow-2xl z-10 transform hover:scale-105 transition-all duration-300" style="box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4), 0 0 0 2px white;">
          Stock: {{ producto.stock_actual }}
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
      </div>

      <!-- Contenido con flexbox para layout fijo -->
      <div class="flex flex-col p-3 sm:p-4 md:p-4" style="height: 200px;">
        <!-- Categoría y Título -->
        <div class="w-full overflow-hidden">
          <span class="inline-block w-full px-1.5 py-0.5 sm:px-2 sm:py-0.5 rounded-full font-medium bg-gradient-to-r from-red-600 to-red-700 text-white border border-white/20 transform hover:scale-105 transition-all duration-300 truncate text-center" style="box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4), 0 0 0 2px white; font-size: 10px; max-width: 100%;">
            {{ producto.categoria }}
          </span>
        </div>
        <h3 class="product-title text-sm font-bold text-gray-800 leading-tight group-hover:text-red-700 transition-colors duration-300 truncate">
          {{ producto.nombre }}
            <div class="text-gray-500 text-xs italic">
                Click para ver detalles
            </div>
        </h3>

        <!-- Footer con precio y botones (siempre en la parte inferior) -->
        <div>
          <!-- Precio -->
          <div class="text-lg font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent mb-2">
            ${{ producto.precio.toFixed(2) }}
          </div>

          <!-- Selector de cantidad estilo carrito -->
          <div class="flex items-center justify-center mb-2">
            <label class="text-xs text-gray-600 mr-2">Cantidad:</label>
            <div class="flex items-center gap-2">
              <button
                @click.stop="decrementarCantidad(producto.id)"
                class="w-7 h-7 flex items-center justify-center border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                :disabled="getCantidad(producto.id) <= 1"
              >
                <FontAwesomeIcon :icon="faMinus" class="w-3 h-3" />
              </button>

              <span class="w-8 text-center text-sm font-medium">
                {{ getCantidad(producto.id) }}
              </span>

              <button
                @click.stop="incrementarCantidad(producto.id)"
                class="w-7 h-7 flex items-center justify-center border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                :disabled="getCantidad(producto.id) >= producto.stock_actual"
              >
                <FontAwesomeIcon :icon="faPlus" class="w-3 h-3" />
              </button>
            </div>
          </div>

          <!-- Botón de comprar (ancho completo) -->
          <div>
            <button
              @click.stop="comprarProducto(producto)"
              :disabled="producto.stock_actual <= 0"
              class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 border-none px-2 py-2 sm:px-4 sm:py-2 text-white text-xs sm:text-sm font-bold rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 disabled:bg-gray-400 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed"
            >
              <FontAwesomeIcon :icon="faShoppingCart" class="mr-2"/>
              Comprar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Título del producto con line-clamp compatible */
.product-title {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  line-clamp: 1;
  overflow: hidden;
  min-height: 2.5rem;
  word-wrap: break-word;
  hyphens: auto;
}

/* Botones de cantidad estilizados */
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

button:disabled:hover {
  background-color: transparent !important;
}
</style>
