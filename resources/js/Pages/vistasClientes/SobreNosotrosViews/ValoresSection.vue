<script setup>
import { faDiamond, faDiamondTurnRight } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    companyValues: {
        type: Array,
        default: () => []
    }
});

const iconosValores = ['🏆', '🛡️', '🌱', '💡', '⭐', '🎯', '🤝', '💎', '🔥', '✨', '🚀', '💪', '🎖️', '🌟', '⚡', '🔆'];
</script>

<template>
  <!-- Nuestros Valores -->
  <div class="mb-8 sm:mb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-blue-600 to-red-600 text-white text-center py-4 sm:py-6 rounded-t-xl">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold">
                💎
                Nuestros Valores
            </h2>
        </div>

    <!-- Valores desde la base de datos -->
    <div v-if="companyValues && companyValues.length > 0" class="bg-gradient-to-br from-gray-50 to-white p-6 sm:p-8 rounded-b-xl shadow-lg">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4 md:gap-5 lg:gap-6">
        <div
          v-for="(valor, index) in companyValues"
          :key="valor.id"
          class="bg-white hover:bg-gradient-to-br hover:from-red-50 hover:to-blue-50 rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-5 lg:p-4 shadow-md hover:shadow-xl border border-gray-200 hover:border-red-200 text-center transition-all duration-300 transform hover:-translate-y-1 hover:scale-105"
        >
          <div class="text-2xl sm:text-3xl md:text-4xl lg:text-3xl mb-2 sm:mb-3 md:mb-4 lg:mb-2">{{ iconosValores[index % iconosValores.length] }}</div>
          <h3 class="text-sm sm:text-base md:text-lg lg:text-base font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent mb-1 sm:mb-2 md:mb-3 lg:mb-2 leading-tight">{{ valor.titulo }}</h3>
          <p class="text-xs sm:text-sm md:text-sm lg:text-xs text-gray-700 leading-relaxed">{{ valor.descripcion }}</p>
        </div>
      </div>
    </div>

    <!-- Mensaje cuando no hay valores configurados -->
    <div v-else class="bg-gradient-to-br from-gray-50 to-white p-6 sm:p-8 rounded-b-xl shadow-lg">
      <div class="text-center py-8 sm:py-12">
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-4 sm:p-6 md:p-8 max-w-2xl mx-auto">
          <div class="text-4xl sm:text-5xl md:text-6xl mb-3 sm:mb-4">💎</div>
          <h3 class="text-lg sm:text-xl font-semibold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent mb-2">Valores en configuración</h3>
          <p class="text-sm sm:text-base text-gray-700">
            Los valores corporativos están siendo configurados por nuestro equipo.
            <br>
            <strong>Administradores:</strong> Pueden configurar los valores desde la sección "Configuración" del panel de administración.
          </p>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>
