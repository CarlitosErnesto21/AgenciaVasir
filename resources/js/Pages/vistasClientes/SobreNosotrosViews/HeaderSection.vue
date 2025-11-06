<script setup>
import { faFaceSmile, faPencil, faStar } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref } from 'vue';

const props = defineProps({
    siteSettings: {
        type: Object,
        default: () => ({})
    }
});

// Calcular años de experiencia desde 2019
const calcularAnosExperiencia = () => {
  const fechaInicio = new Date('2019-01-01')
  const fechaActual = new Date()
  const diferencia = fechaActual.getFullYear() - fechaInicio.getFullYear()
  return diferencia
}

// Datos de estadísticas
const estadisticas = ref([
  { numero: `${calcularAnosExperiencia()}+`, descripcion: 'Años de experiencia', icono: faStar, color: 'text-yellow-400' },
  { numero: '500+', descripcion: 'Clientes satisfechos', icono: faFaceSmile, color: 'text-green-400' },
  { numero: '50+', descripcion: 'Destinos visitados', icono: faFaceSmile, color: 'text-blue-400' },
  { numero: '100%', descripcion: 'Compromiso y calidad', icono: faFaceSmile, color: 'text-red-400' }
]);
</script>

<template>
  <!-- Header con Estadísticas, Misión y Visión -->
  <div class="mb-8 sm:mb-12">
    <div class="min-h-screen pt-20 sm:pt-20 md:pt-28 lg:pt-32 xl:pt-32">
      <!-- Header - Full Width -->
      <div class="w-full mb-6">
        <div class="bg-gradient-to-r from-red-600 via-red-500 to-blue-600 text-white text-center py-6 sm:py-8 md:py-10 shadow-xl">
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4">🏢 Sobre Nosotros</h1>
          <p class="text-base sm:text-lg text-red-100 mb-4 px-4">Descubre la historia y pasión detrás de VASIR</p>
          <div v-if="siteSettings.description" class="text-base sm:text-lg md:text-xl text-white max-w-4xl mx-auto leading-relaxed px-4">
            {{ siteSettings.description }}
          </div>
          <div v-else class="max-w-3xl mx-auto px-4">
            <div class="bg-blue-500/20 backdrop-blur-sm border border-blue-300/30 rounded-lg p-3 sm:p-4 text-sm">
              <p class="text-blue-100">
                <strong><FontAwesomeIcon :icon="faPencil" /> La información de VASIR se encuentra en desarrollo... ¡Sigue disfrutando de nuestros servicios!</strong>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Container -->
      <div class="px-1 sm:px-1 lg:px-2 max-w-7xl mx-auto">
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg overflow-hidden p-6 sm:p-8">
        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-3 md:gap-4 lg:gap-6 mb-8">
          <div
            v-for="(stat, index) in estadisticas"
            :key="stat.descripcion"
            class="bg-gradient-to-br from-white to-gray-50 hover:from-red-50 hover:to-blue-50 rounded-lg sm:rounded-xl p-2 sm:p-3 md:p-4 lg:p-6 shadow-lg border-2 border-transparent hover:border-red-200 text-center transform hover:-translate-y-2 hover:scale-105 transition-all duration-300"
          >
            <div class="text-3xl sm:text-4xl md:text-5xl mb-2 sm:mb-3">
              <FontAwesomeIcon :icon="stat.icono" :class="stat.color" />
            </div>
            <h3 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent mb-1 sm:mb-2">{{ stat.numero }}</h3>
            <p class="text-xs sm:text-sm md:text-base text-gray-700 font-medium leading-tight">{{ stat.descripcion }}</p>
          </div>
        </div>

        <!-- Misión y Visión -->
        <div v-if="siteSettings.mission || siteSettings.vision" class="grid grid-cols-2 gap-3 sm:gap-4 md:gap-6 lg:gap-8">
          <div v-if="siteSettings.mission" class="border border-gray-300 bg-white shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 rounded-xl p-6">
            <div class="flex items-center mb-2 sm:mb-3 md:mb-4">
              <span class="text-xl sm:text-2xl md:text-3xl mr-1 sm:mr-2 md:mr-3">🎯</span>
              <h2 class="text-sm sm:text-lg md:text-xl lg:text-2xl font-bold text-red-700">Nuestra Misión</h2>
            </div>
            <p class="text-xs sm:text-sm md:text-base lg:text-lg text-gray-700 leading-relaxed">
              {{ siteSettings.mission }}
            </p>
          </div>

          <div v-if="siteSettings.vision" class="border border-gray-300 bg-white shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 rounded-xl p-6">
            <div class="flex items-center mb-2 sm:mb-3 md:mb-4">
              <span class="text-xl sm:text-2xl md:text-3xl mr-1 sm:mr-2 md:mr-3">🌟</span>
              <h2 class="text-sm sm:text-lg md:text-xl lg:text-2xl font-bold text-red-700">Nuestra Visión</h2>
            </div>
            <p class="text-xs sm:text-sm md:text-base lg:text-lg text-gray-700 leading-relaxed">
              {{ siteSettings.vision }}
            </p>
          </div>
        </div>

        <!-- Mensaje cuando no hay contenido configurado -->
        <div v-if="!siteSettings.mission && !siteSettings.vision" class="text-center py-8">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 sm:p-6 md:p-8 max-w-2xl mx-auto">
            <div class="text-4xl sm:text-5xl md:text-6xl mb-3 sm:mb-4">⚙️</div>
            <h3 class="text-lg sm:text-xl font-semibold text-yellow-800 mb-2">Contenido en configuración</h3>
            <p class="text-sm sm:text-base text-yellow-700">
              La misión y visión de la empresa están siendo configuradas por nuestro equipo.
              <br>
              <strong>Administradores:</strong> Pueden configurar este contenido desde la sección "Configuración" del panel de administración.
            </p>
          </div>
        </div>
        </div> <!-- End Content Container Wrapper -->
      </div> <!-- End Content Container -->
    </div> <!-- End Main Container -->
  </div> <!-- End Header Section -->
</template>
