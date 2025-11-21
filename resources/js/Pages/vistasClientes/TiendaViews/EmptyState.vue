<script setup>
import { faBroom, faRefresh, faSearch } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Props del componente
const props = defineProps({
  loading: {
    type: Boolean,
    required: true
  },
  filteredProducts: {
    type: Array,
    required: true
  },
  products: {
    type: Array,
    required: true
  }
})

// Emits del componente
const emit = defineEmits([
  'clear-filters',
  'recargar-datos'
])

// Métodos
const clearFilters = () => {
  emit('clear-filters')
}

const recargarDatos = () => {
  emit('recargar-datos')
}
</script>

<template>
  <!-- Mensaje cuando no hay productos -->
  <div v-if="!loading && filteredProducts.length === 0" class="text-center">
    <div class="p-8 sm:p-12">
      <div class="w-20 h-20 bg-red-500 rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg">
        <span class="text-white text-4xl">
            <FontAwesomeIcon :icon="faSearch"/>
        </span>
      </div>
      <h3 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-gray-700 to-gray-900 bg-clip-text text-transparent mb-4">
        No se encontraron productos
      </h3>
      <p class="text-gray-600 mb-6 text-sm sm:text-base leading-relaxed">
        <span v-if="products.length === 0">
          No hay productos disponibles en este momento. Por favor, inténtalo más tarde.
        </span>
        <span v-else>
          Intenta ajustar los filtros de búsqueda o usar términos diferentes para encontrar lo que buscas.
        </span>
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
        <button
          @click="clearFilters"
          class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
        >
            <FontAwesomeIcon :icon="faBroom" class="mr-2"/>
            Limpiar filtros
        </button>
        <button
          @click="recargarDatos"
          class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
        >

            <FontAwesomeIcon :icon="faRefresh" class="mr-2"/>
            Recargar productos
        </button>
      </div>
    </div>
  </div>
</template>
