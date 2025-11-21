<script setup>
import { faBox, faDollar, faFilter, faMoneyBill, faTags } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed } from 'vue'

// Props del componente
const props = defineProps({
  categories: {
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
  },
  showMobileFilters: {
    type: Boolean,
    required: true
  },
  loadingCategories: {
    type: Boolean,
    required: true
  },
  preciosStats: {
    type: Object,
    required: true
  }
})

// Emits del componente
const emit = defineEmits([
  'update:selectedCategories',
  'update:minPrice',
  'update:maxPrice',
  'update:showMobileFilters',
  'toggle-category',
  'clear-filters',
  'apply-price-filter'
])

// Computed para categorías con conteo
const categoriasConConteo = computed(() => {
  return props.categories.map(categoria => ({
    ...categoria,
    cantidad: categoria.cantidad || 0
  })).filter(categoria => categoria.cantidad > 0)
})

// Métodos
const toggleCategory = (categoryName) => {
  emit('toggle-category', categoryName)
}

const applyPriceFilter = () => {
  emit('apply-price-filter')
}

const updateMinPrice = (value) => {
  emit('update:minPrice', value)
}

const updateMaxPrice = (value) => {
  emit('update:maxPrice', value)
}

const toggleMobileFilters = () => {
  emit('update:showMobileFilters', !props.showMobileFilters)
}
</script>

<template>
  <!-- Panel de Filtros Desktop -->
  <div class="w-72 xl:w-80 p-4 lg:p-5 h-fit transition-all duration-300 hidden lg:block">
    <div class="bg-red-500 text-white text-center py-2 rounded-xl mb-4 sm:mb-5">
      <h3 class="text-base sm:text-lg font-bold flex items-center justify-center">
        <span class="text-lg sm:text-xl mr-2">
            <FontAwesomeIcon :icon="faFilter" class="text-white"/>
        </span>
        Filtros de búsqueda
      </h3>
    </div>

    <!-- Filtro de Precio -->
    <div class="mb-4 sm:mb-5">
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-3 sm:p-4 shadow-lg">
        <h4 class="font-bold text-gray-800 mb-3 text-sm flex items-center">
          <span class="mr-2">
            <FontAwesomeIcon :icon="faDollar" class="text-gray-800"/>
          </span>
          Filtrar por precio
        </h4>
        <div class="flex gap-2 items-end mb-3">
          <div class="flex-1">
            <InputNumber
              :modelValue="minPrice"
              @update:modelValue="updateMinPrice"
              :inputStyle="{ width: '100%' }"
              placeholder="Min"
              :min="0"
              :step="0.01"
              mode="currency"
              currency="USD"
              locale="en-US"
              class="w-full"
            />
          </div>
          <div class="flex-1">
            <InputNumber
              :modelValue="maxPrice"
              @update:modelValue="updateMaxPrice"
              :inputStyle="{ width: '100%' }"
              placeholder="Max"
              :min="0"
              :step="0.01"
              mode="currency"
              currency="USD"
              locale="en-US"
              class="w-full"
            />
          </div>
          <button
            @click="applyPriceFilter"
            class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1"
          >
            Aplicar
          </button>
        </div>
      </div>
    </div>

    <!-- Filtro de Categorías -->
    <div>
      <div class="bg-gradient-to-br from-red-50 to-blue-50 border-2 border-red-200 rounded-xl p-3 sm:p-4 shadow-lg">
        <h4 class="font-bold text-gray-800 mb-3 text-sm flex items-center">
          <span class="mr-2">
            <FontAwesomeIcon :icon="faTags" class="text-gray-800"/>
          </span>
          Categorías
          <span v-if="loadingCategories" class="text-xs text-gray-500 ml-2">(cargando...)</span>
        </h4>

        <div v-if="loadingCategories" class="space-y-3">
          <div v-for="i in 3" :key="i" class="animate-pulse">
            <div class="h-10 bg-gray-200 rounded-lg"></div>
          </div>
        </div>

        <div v-else class="space-y-2">
          <label
            v-for="category in categoriasConConteo"
            :key="category.id"
            class="flex items-center cursor-pointer hover:bg-white/60 p-2 rounded-lg transition-all duration-300 group shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
          >
            <input
              type="checkbox"
              :checked="selectedCategories.includes(category.nombre)"
              @change="toggleCategory(category.nombre)"
              class="mr-3 accent-green-600 scale-110"
            />
            <span class="text-xs text-gray-700 flex-1 font-medium group-hover:text-green-700 transition-colors">{{ category.nombre }}</span>
            <span class="text-xs text-white bg-gradient-to-r from-red-500 to-blue-500 px-3 py-1 rounded-full font-bold shadow-sm">
              {{ category.cantidad }}
            </span>
          </label>

          <div v-if="categoriasConConteo.length === 0" class="text-center py-6 text-gray-500 text-sm bg-white rounded-lg shadow-sm">
            <div class="text-2xl mb-2">
                <FontAwesomeIcon :icon="faBox" class="text-gray-400"/>
            </div>
            No hay categorías disponibles
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
