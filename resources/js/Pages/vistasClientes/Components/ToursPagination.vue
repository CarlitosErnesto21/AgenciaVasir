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
  allFilteredTours: {
    type: Array,
    required: true
  },
  itemsPerPage: {
    type: Number,
    required: true,
    default: 4
  },
  colorScheme: {
    type: String,
    default: 'red', // 'red' para nacionales, 'blue' para internacionales
    validator: (value) => ['red', 'blue'].includes(value)
  }
})

// Emits del componente
const emit = defineEmits([
  'go-to-page',
  'go-to-previous-page',
  'go-to-next-page'
])

// Páginas visibles para la paginación responsiva
const visiblePages = computed(() => {
  const total = props.totalPagesComputed
  const current = props.currentPage
  const delta = 1 // Número de páginas a mostrar a cada lado de la actual (reducido para tours)

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

// Clases CSS dinámicas basadas en el esquema de color
const colorClasses = computed(() => {
  const isRed = props.colorScheme === 'red'
  
  return {
    button: isRed ? 
      'bg-gradient-to-r from-red-500 to-red-500 hover:from-red-600 hover:to-red-600 disabled:from-red-300 disabled:to-red-300' :
      'bg-gradient-to-r from-blue-500 to-blue-500 hover:from-blue-600 hover:to-blue-600 disabled:from-blue-300 disabled:to-blue-300',
    pageActive: isRed ? 
      'bg-gradient-to-r from-red-600 to-red-700' :
      'bg-gradient-to-r from-blue-600 to-blue-700',
    pageInactive: isRed ? 
      'bg-gradient-to-r from-red-200 to-red-200 hover:from-red-300 hover:to-red-300' :
      'bg-gradient-to-r from-blue-200 to-blue-200 hover:from-blue-300 hover:to-blue-300',
    autoIndicator: isRed ?
      'bg-gradient-to-r from-red-500/90 to-red-600/90' :
      'bg-gradient-to-r from-blue-500/90 to-blue-600/90'
  }
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
</script>

<template>
  <!-- Paginación Responsiva para Tours -->
  <div v-if="allFilteredTours.length > itemsPerPage" class="mt-6 sm:mt-8">
    <div class="p-4 sm:p-6">

      <!-- Información de paginación -->
      <div class="text-center mb-4">
        <span class="text-sm sm:text-base text-gray-600 font-medium">
          Página {{ currentPage }} de {{ totalPagesComputed }}
          <span class="hidden sm:inline">- {{ allFilteredTours.length }} tours en total</span>
        </span>
      </div>

      <!-- Controles de paginación -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-4">
        <!-- Botón Anterior -->
        <button
          @click="goToPreviousPage"
          :disabled="currentPage === 1"
          :class="[colorClasses.button, 'disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 disabled:transform-none flex items-center justify-center gap-2 w-full sm:w-auto']"
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
            :class="[
              currentPage === 1 ? colorClasses.pageActive : colorClasses.pageActive,
              'text-white w-8 h-8 sm:w-10 sm:h-10 rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-sm sm:text-base'
            ]"
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
            :class="[
              currentPage === page ? colorClasses.pageActive : colorClasses.pageInactive,
              'text-white w-8 h-8 sm:w-10 sm:h-10 rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-sm sm:text-base'
            ]"
          >
            {{ page }}
          </button>

          <!-- Puntos suspensivos derecha -->
          <span v-if="visiblePages[visiblePages.length - 1] < totalPagesComputed - 1" class="text-gray-500 px-1 text-sm sm:text-base">...</span>

          <!-- Última página -->
          <button
            v-if="visiblePages[visiblePages.length - 1] < totalPagesComputed"
            @click="goToPage(totalPagesComputed)"
            :class="[
              currentPage === totalPagesComputed ? colorClasses.pageActive : colorClasses.pageActive,
              'text-white w-8 h-8 sm:w-10 sm:h-10 rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-sm sm:text-base'
            ]"
          >
            {{ totalPagesComputed }}
          </button>
        </div>

        <!-- Botón Siguiente -->
        <button
          @click="goToNextPage"
          :disabled="currentPage === totalPagesComputed"
          :class="[colorClasses.button, 'disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 disabled:transform-none flex items-center justify-center gap-2 w-full sm:w-auto']"
        >
          <span class="hidden sm:inline">Siguiente</span>
          <i class="pi pi-chevron-right text-sm"></i>
        </button>
      </div>

      <!-- Información fija: siempre 4 tours por página -->
      <div class="text-center mt-4">
        <span class="text-xs text-gray-500 bg-white/60 rounded-full px-3 py-1 inline-block">
          Mostrando {{ itemsPerPage }} tours por página
        </span>
      </div>
    </div>
  </div>

  <!-- Información cuando no hay suficientes tours para paginación -->
  <div v-else-if="allFilteredTours.length > 0 && allFilteredTours.length <= itemsPerPage" class="mt-6 sm:mt-8">
    <div class="text-center">
      <span class="text-xs text-gray-500 bg-white/60 rounded-full px-3 py-1 inline-block">
        {{ allFilteredTours.length }} tours en total
      </span>
    </div>
  </div>
</template>