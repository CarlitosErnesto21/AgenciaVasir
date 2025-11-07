<script setup lang="ts">
import { faGlobe, faHandshake, faMedal, faStar } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { computed } from 'vue';

// Calcular años de experiencia desde 2019
const calcularAnosExperiencia = () => {
  const fechaInicio = new Date('2019-03-01')
  const fechaActual = new Date()
  const diferencia = fechaActual.getFullYear() - fechaInicio.getFullYear()
  return diferencia
}
</script>

<template>
  <!-- Por qué elegirnos -->
  <div class="mb-2 sm:mb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-gradient-to-br from-white via-red-50 to-blue-50 rounded-xl shadow-xl border border-gray-200">
        <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white text-center py-4 sm:py-6 rounded-t-xl mb-4 ">
          <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold">
            <FontAwesomeIcon :icon="faStar" class="text-yellow-500 mr-2" />
            ¿Por qué elegir VASIR?</h2>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:gap-4 md:gap-6 lg:gap-8 p-3">
          <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-4 md:p-6 lg:p-8 text-center shadow-md sm:shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200 hover:border-red-200">
            <div class="text-lg sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl mb-1 sm:mb-2 md:mb-4 lg:mb-6">
                <FontAwesomeIcon :icon="faMedal" class="text-red-500" />
            </div>
            <h3 class="text-xs sm:text-sm md:text-lg lg:text-xl xl:text-2xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent mb-1 sm:mb-2 md:mb-3 lg:mb-4 leading-tight">Experiencia Comprobada</h3>
            <p class="text-xs sm:text-xs md:text-sm lg:text-base xl:text-lg text-gray-700 leading-tight sm:leading-relaxed">Más de {{ calcularAnosExperiencia() }} años creando experiencias turísticas únicas y memorables.</p>
          </div>
          <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-4 md:p-6 lg:p-8 text-center shadow-md sm:shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200 hover:border-blue-200">
            <div class="text-lg sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl mb-1 sm:mb-2 md:mb-4 lg:mb-6">
                <FontAwesomeIcon :icon="faHandshake" class="text-yellow-500" />
            </div>
            <h3 class="text-xs sm:text-sm md:text-lg lg:text-xl xl:text-2xl font-bold bg-gradient-to-r from-blue-600 to-red-600 bg-clip-text text-transparent mb-1 sm:mb-2 md:mb-3 lg:mb-4 leading-tight">Atención Personalizada</h3>
            <p class="text-xs sm:text-xs md:text-sm lg:text-base xl:text-lg text-gray-700 leading-tight sm:leading-relaxed">Cada cliente es único, por eso adaptamos nuestros servicios a tus necesidades.</p>
          </div>
          <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-4 md:p-6 lg:p-8 text-center shadow-md sm:shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200 hover:border-red-200">
            <div class="text-lg sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl mb-1 sm:mb-2 md:mb-4 lg:mb-6">
                <FontAwesomeIcon :icon="faGlobe" class="text-blue-500" />
            </div>
            <h3 class="text-xs sm:text-sm md:text-lg lg:text-xl xl:text-2xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent mb-1 sm:mb-2 md:mb-3 lg:mb-4 leading-tight">Turismo Responsable</h3>
            <p class="text-xs sm:text-xs md:text-sm lg:text-base xl:text-lg text-gray-700 leading-tight sm:leading-relaxed">Comprometidos con el desarrollo sostenible y el cuidado del medio ambiente.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
