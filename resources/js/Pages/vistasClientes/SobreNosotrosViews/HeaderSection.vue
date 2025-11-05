<script setup>
import { faPencil } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    siteSettings: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
  <!-- Header con Estadísticas, Misión y Visión -->
  <!-- ...existing code... -->
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
    </div>
</template>
