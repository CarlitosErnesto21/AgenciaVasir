<script setup>
import { computed } from 'vue'

// Props del componente
const props = defineProps({
  currentPage: {
    type: Number,
    required: true
  },
  totalPagesComputed: {
    type: Number,
    required: true
  },
  allFilteredProducts: {
    type: Array,
    required: true
  },
  itemsPerPage: {
    type: Number,
    required: true
  }
})

// Emits del componente
const emit = defineEmits([
  'update:currentPage',
  'update:itemsPerPage',
  'go-to-page',
  'go-to-previous-page',
  'go-to-next-page'
])

// Páginas visibles para la paginación responsiva
const visiblePages = computed(() => {
  const total = props.totalPagesComputed
  const current = props.currentPage
  const delta = 2 // Número de páginas a mostrar a cada lado de la actual

  let start = Math.max(1, current - delta)
  let end = Math.min(total, current + delta)

  // Ajustar para mantener un número consistente de páginas visibles
  if (end - start < 2 * delta) {
    if (start === 1) {
      end = Math.min(total, start + 2 * delta)
    } else if (end === total) {
      start = Math.max(1, end - 2 * delta)
    }
  }

  const pages = []
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

// Métodos
const goToPage = (page) => {
  emit('go-to-page', page)
}

const goToPreviousPage = () => {
  emit('go-to-previous-page')
}

const goToNextPage = () => {
  emit('go-to-next-page')
}

const updateItemsPerPage = (value) => {
  emit('update:itemsPerPage', value)
}
</script>

<template>
  <!-- Paginación Responsiva -->
  <div v-if="allFilteredProducts.length > 0" class="mt-6 sm:mt-8">
    <div class="p-4 sm:p-6">

      <!-- Información de paginación (solo si hay más de una página) -->
      <div v-if="totalPagesComputed > 1" class="text-center mb-4">
        <span class="text-sm sm:text-base text-gray-600 font-medium">
          Página {{ currentPage }} de {{ totalPagesComputed }}
          <span class="hidden sm:inline">- {{ allFilteredProducts.length }} productos en total</span>
        </span>
      </div>

      <!-- Información cuando solo hay una página -->
      <div v-else class="text-center mb-4">
        <span class="text-sm sm:text-base text-gray-600 font-medium">
          {{ allFilteredProducts.length }} productos en total
        </span>
      </div>

      <!-- Controles de paginación (solo si hay más de una página) -->
      <div v-if="totalPagesComputed > 1" class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-4">
        <!-- Botón Anterior -->
        <button
          @click="goToPreviousPage"
          :disabled="currentPage === 1"
          class="w-full sm:w-auto bg-gradient-to-r from-red-500 to-red-500 hover:from-red-600 hover:to-red-600 disabled:from-red-300 disabled:to-red-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 disabled:transform-none flex items-center justify-center gap-2"
        >
          <i class="pi pi-chevron-left text-sm"></i>
          <span class="hidden sm:inline">Anterior</span>
        </button>

        <!-- Números de página -->
        <div class="flex flex-wrap items-center justify-center gap-1 sm:gap-2">
          <!-- Primera página -->
          <button
            v-if="visiblePages[0] > 1"
            @click="goToPage(1)"
            :class="currentPage === 1
              ? 'bg-gradient-to-r from-red-600 to-red-700 text-white'
              : 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white'"
            class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-sm sm:text-base"
          >
            1
          </button>

          <!-- Puntos suspensivos izquierda -->
          <span v-if="visiblePages[0] > 2" class="text-gray-500 px-1 text-sm sm:text-base">...</span>

          <!-- Páginas visibles -->
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="currentPage === page
              ? 'bg-gradient-to-r from-red-600 to-red-700 text-white'
              : 'bg-gradient-to-r from-red-200 to-red-200 hover:from-red-200 hover:to-red-200 text-white'"
            class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-sm sm:text-base"
          >
            {{ page }}
          </button>

          <!-- Puntos suspensivos derecha -->
          <span v-if="visiblePages[visiblePages.length - 1] < totalPagesComputed - 1" class="text-gray-500 px-1 text-sm sm:text-base">...</span>

          <!-- Última página -->
          <button
            v-if="visiblePages[visiblePages.length - 1] < totalPagesComputed"
            @click="goToPage(totalPagesComputed)"
            :class="currentPage === totalPagesComputed
              ? 'bg-gradient-to-r from-red-600 to-red-700 text-white'
              : 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white'"
            class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-sm sm:text-base"
          >
            {{ totalPagesComputed }}
          </button>
        </div>

        <!-- Botón Siguiente -->
        <button
          @click="goToNextPage"
          :disabled="currentPage === totalPagesComputed"
          class="w-full sm:w-auto bg-gradient-to-r from-red-500 to-red-500 hover:from-red-600 hover:to-red-600 disabled:from-red-300 disabled:to-red-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 disabled:transform-none flex items-center justify-center gap-2"
        >
          <span class="hidden sm:inline">Siguiente</span>
          <i class="pi pi-chevron-right text-sm"></i>
        </button>
      </div>

      <!-- Selector de productos por página (siempre visible en desktop) -->
      <div class="hidden lg:flex items-center justify-center mt-4 gap-3">
        <span class="text-sm text-gray-600 font-medium">Productos por página:</span>
        <select
          :value="itemsPerPage"
          @change="updateItemsPerPage(parseInt($event.target.value))"
          class="bg-white border border-gray-300 rounded-lg pl-3 pr-3 py-2 text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 cursor-pointer min-w-[60px]"
        >
          <option value="4">4</option>
          <option value="8">8</option>
          <option value="12">12</option>
          <option value="16">16</option>
          <option value="20">20</option>
        </select>
      </div>
    </div>
  </div>
</template>
