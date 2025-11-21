<script setup>
import { faCheck } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Props del componente
const props = defineProps({
  searchTerm: {
    type: String,
    required: true
  },
  allFilteredProducts: {
    type: Array,
    required: true
  },
  selectedCategories: {
    type: Array,
    required: true
  },
  minPrice: {
    type: [String, Number],
    required: true
  },
  maxPrice: {
    type: [String, Number],
    required: true
  }
})

// Emits del componente
const emit = defineEmits([
  'update:searchTerm',
  'clear-filters'
])

// Métodos
const updateSearchTerm = (value) => {
  emit('update:searchTerm', value)
}

const clearFilters = () => {
  emit('clear-filters')
}
</script>

<template>
  <!-- Información de resultados con buscador -->
  <div class="mb-4">
    <div class="rounded-xl p-3 sm:p-4">
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <!-- Buscador -->
        <div class="w-full lg:flex-1 lg:max-w-md">
          <IconField iconPosition="left">
            <InputIcon class="pi pi-search text-gray-400" />
            <InputText
              :modelValue="searchTerm"
              @update:modelValue="updateSearchTerm"
              placeholder="Buscar productos..."
              class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-1 transition-all duration-300 shadow-sm hover:shadow-md bg-white/90 font-medium"
            />
          </IconField>
        </div>

        <!-- Información de productos y botón limpiar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3">
          <!-- Información de productos -->
          <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg px-4 py-2 shadow-md">
            <span class="text-sm sm:text-base font-bold">
              {{ allFilteredProducts.length }} producto{{ allFilteredProducts.length !== 1 ? 's' : '' }} encontrado{{ allFilteredProducts.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <!-- Botón limpiar filtros -->
          <button
            v-if="selectedCategories.length > 0 || minPrice || maxPrice || searchTerm"
            @click="clearFilters"
            class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2"
          >
            <span class="hidden sm:inline">Limpiar Filtros</span>
            <span class="sm:hidden">Limpiar</span>
          </button>
        </div>
      </div>

      <div v-if="selectedCategories.length > 0 || minPrice || maxPrice || searchTerm"
           class="flex flex-wrap gap-2 mt-4">
        <span class="bg-gradient-to-r from-green-100 to-blue-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium shadow-sm border border-green-200">
            <FontAwesomeIcon :icon="faCheck" class="mr-1" />
            Filtros activos: {{
            [
              searchTerm ? `Búsqueda: "${searchTerm}"` : '',
              selectedCategories.length > 0 ? `Categorías (${selectedCategories.length})` : '',
              minPrice ? `Min: $${minPrice}` : '',
              maxPrice ? `Max: $${maxPrice}` : ''
            ].filter(Boolean).join(', ')
          }}
        </span>
      </div>
    </div>
  </div>
</template>
