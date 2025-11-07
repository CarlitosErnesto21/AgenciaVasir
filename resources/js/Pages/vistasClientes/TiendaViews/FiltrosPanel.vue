<script setup>
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

const clearFilters = () => {
  emit('clear-filters')
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
  <!-- Panel de Filtros - Colapsable en móvil -->
  <div class="lg:hidden">
    <button
      @click="toggleMobileFilters"
      class="w-full bg-gradient-to-r from-red-600 to-blue-600 text-white py-2 -mt-3 px-4 font-bold text-sm shadow-lg flex items-center justify-between"
    >
      <span class="flex items-center">
        <span class="text-lg mr-2">🔧</span>
        Filtros
      </span>
      <i :class="showMobileFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
    </button>

    <div v-show="showMobileFilters" class="mt-4 bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl shadow-xl p-4 border border-gray-200">

      <!-- Filtro de Precio Móvil -->
      <div class="mb-4">
        <h4 class="font-bold text-gray-800 mb-3 text-sm flex items-center">
          <span class="mr-2">💰</span>
          Filtrar por precio
        </h4>
        <div class="flex gap-2 items-end mb-3">
          <div class="flex-1">
            <label class="text-xs text-gray-600 font-medium mb-1 block">Mínimo</label>
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
            <label class="text-xs text-gray-600 font-medium mb-1 block">Máximo</label>
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
            class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-all duration-300 shadow-md"
          >
            Aplicar
          </button>
        </div>
      </div>

      <!-- Filtro de Categorías Móvil -->
      <div>
        <h4 class="font-bold text-gray-800 mb-3 text-sm flex items-center">
          <span class="mr-2">🏷️</span>
          Categorías
        </h4>
        <div class="grid grid-cols-2 gap-2">
          <label
            v-for="category in categoriasConConteo"
            :key="category.id"
            class="flex items-center cursor-pointer hover:bg-white/60 p-2 rounded-lg transition-all duration-300 text-xs"
          >
            <input
              type="checkbox"
              :checked="selectedCategories.includes(category.nombre)"
              @change="toggleCategory(category.nombre)"
              class="mr-2 accent-green-600"
            />
            <span class="text-gray-700 flex-1 font-medium">{{ category.nombre }}</span>
            <span class="text-xs text-white bg-gradient-to-r from-green-500 to-emerald-500 px-2 py-1 rounded-full font-bold">
              {{ category.cantidad }}
            </span>
          </label>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel de Filtros Desktop -->
  <div class="w-80 xl:w-96 bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl shadow-xl p-6 lg:p-8 h-fit border border-gray-200 hover:shadow-2xl transition-all duration-300 hidden lg:block">
    <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white text-center py-2 sm:py-1 rounded-xl mb-6 sm:mb-8">
      <h3 class="text-lg sm:text-xl md:text-lg font-bold flex items-center justify-center">
        <span class="text-xl sm:text-2xl mr-2">🔧</span>
        Filtros de búsqueda
      </h3>
    </div>



    <!-- Estadísticas de precios -->
    <div class="mb-6">
      <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-xl p-4 sm:p-5 shadow-lg">
        <h5 class="text-sm sm:text-base font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent mb-3 flex items-center">
          <span class="mr-2">📊</span>
          Rango de precios
        </h5>
        <div class="text-xs sm:text-sm text-gray-700 space-y-2">
          <div class="flex justify-between items-center p-2 bg-white rounded-lg shadow-sm">
            <span class="font-medium">Mínimo:</span>
            <span class="font-bold text-green-600">${{ preciosStats.min.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between items-center p-2 bg-white rounded-lg shadow-sm">
            <span class="font-medium">Máximo:</span>
            <span class="font-bold text-red-600">${{ preciosStats.max.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between items-center p-2 bg-white rounded-lg shadow-sm">
            <span class="font-medium">Promedio:</span>
            <span class="font-bold text-blue-600">${{ preciosStats.promedio.toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtro de Precio -->
    <div class="mb-6 sm:mb-8">
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 sm:p-5 shadow-lg">
        <h4 class="font-bold text-gray-800 mb-4 text-sm sm:text-base flex items-center">
          <span class="mr-2">💰</span>
          Filtrar por precio
        </h4>
        <div class="flex gap-2 items-end mb-3">
          <div class="flex-1">
            <label class="text-xs sm:text-sm text-gray-600 font-medium mb-1 block">Mínimo</label>
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
            <label class="text-xs sm:text-sm text-gray-600 font-medium mb-1 block">Máximo</label>
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
      <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-4 sm:p-5 shadow-lg">
        <h4 class="font-bold text-gray-800 mb-4 text-sm sm:text-base flex items-center">
          <span class="mr-2">🏷️</span>
          Categorías
          <span v-if="loadingCategories" class="text-xs text-gray-500 ml-2">(cargando...)</span>
        </h4>

        <div v-if="loadingCategories" class="space-y-3">
          <div v-for="i in 3" :key="i" class="animate-pulse">
            <div class="h-10 bg-gray-200 rounded-lg"></div>
          </div>
        </div>

        <div v-else class="space-y-3">
          <label
            v-for="category in categoriasConConteo"
            :key="category.id"
            class="flex items-center cursor-pointer hover:bg-white/60 p-3 rounded-lg transition-all duration-300 group shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
          >
            <input
              type="checkbox"
              :checked="selectedCategories.includes(category.nombre)"
              @change="toggleCategory(category.nombre)"
              class="mr-3 accent-green-600 scale-110"
            />
            <span class="text-sm text-gray-700 flex-1 font-medium group-hover:text-green-700 transition-colors">{{ category.nombre }}</span>
            <span class="text-xs text-white bg-gradient-to-r from-green-500 to-emerald-500 px-3 py-1 rounded-full font-bold shadow-sm">
              {{ category.cantidad }}
            </span>
          </label>

          <div v-if="categoriasConConteo.length === 0" class="text-center py-6 text-gray-500 text-sm bg-white rounded-lg shadow-sm">
            <div class="text-2xl mb-2">📦</div>
            No hay categorías disponibles
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
