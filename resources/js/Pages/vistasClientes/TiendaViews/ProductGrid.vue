<script setup>
import ImageWithFallback from '@/Components/ImageWithFallback.vue'

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
  'ver-detalles'
])

// Métodos
const comprarProducto = (producto) => {
  emit('comprar-producto', producto)
}

const verDetalles = (producto) => {
  emit('ver-detalles', producto)
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
  <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-2 sm:gap-4 md:gap-6">
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
  <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-2 sm:gap-4 md:gap-6">
    <Card
      v-for="producto in filteredProducts"
      :key="producto.id"
      class="border-2 border-gray-200 bg-gradient-to-br from-white via-blue-50/30 to-red-50/30 shadow-lg hover:shadow-2xl transition-all duration-500 flex flex-col h-full transform hover:-translate-y-2 hover:border-red-300 group"
    >
      <template #header>
        <div class="w-full flex justify-center items-center h-32 bg-gradient-to-br from-gray-100 via-blue-100 to-red-100 rounded-t-lg overflow-hidden group relative">
          <ImageWithFallback
            :src="getImageUrl(producto)"
            :alt="producto.nombre"
            :fallback-text="producto.nombre"
            container-class="w-full h-full"
            image-class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
          />
          <div class="absolute top-1 right-1 bg-gradient-to-r from-red-600 to-red-700 text-white px-1 py-0.5 rounded text-xs font-normal shadow-sm border border-white/20 leading-none md:top-2 md:right-2 md:px-2.5 md:py-1 md:rounded-full md:font-bold md:shadow-lg z-10">
            {{ producto.categoria }}
          </div>
          <div class="absolute top-1 left-1 bg-gradient-to-r from-green-600 to-emerald-600 text-white px-1 py-0.5 rounded text-xs font-normal shadow-sm border border-white/20 leading-none md:top-2 md:left-2 md:px-2.5 md:py-1 md:rounded-full md:font-bold md:shadow-lg z-10">
            Stock: {{ producto.stock_actual }}
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>
      </template>

      <template #title>
        <span class="text-sm sm:text-base md:text-lg font-bold text-gray-800 leading-tight line-clamp-2 group-hover:text-red-700 transition-colors duration-300">
          {{ producto.nombre }}
        </span>
      </template>

      <template #content>
        <div class="flex-grow">
          <div class="text-gray-600 text-xs sm:text-sm mb-2 sm:mb-4 line-clamp-2 leading-relaxed">
            {{ producto.descripcion }}
          </div>
          <div class="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent mb-2 sm:mb-4">
            ${{ producto.precio.toFixed(2) }}
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex gap-1 sm:gap-2 mt-auto">
          <button
            @click="comprarProducto(producto)"
            :disabled="producto.stock_actual <= 0"
            class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 border-none px-2 py-1.5 sm:px-4 sm:py-2 text-white text-xs sm:text-sm font-bold rounded-lg transition-all duration-300 flex-1 shadow-md hover:shadow-lg transform hover:-translate-y-1 disabled:bg-gray-400 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed"
          >
            🛒 Comprar
          </button>
          <button
            @click="verDetalles(producto)"
            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 border-none text-white transition-all duration-300 w-8 h-8 sm:w-10 sm:h-10 p-0 shadow-md hover:shadow-lg transform hover:-translate-y-1 rounded-lg flex items-center justify-center"
          >
            <i class="pi pi-eye text-xs sm:text-sm"></i>
          </button>
        </div>
      </template>
    </Card>
  </div>
</template>
