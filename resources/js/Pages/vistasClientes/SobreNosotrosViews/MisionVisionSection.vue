<script setup>
import { faBook, faBullseye, faCog, faStar } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    siteSettings: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
  <!-- Descripción, Misión y Visión -->
  <div class="mb-8 sm:mb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg overflow-hidden p-6 sm:p-8">

        <!-- Descripción de la empresa -->
        <div class="mb-4 sm:mb-6 md:mb-8">
          <div v-if="siteSettings.description" class="bg-gradient-to-br from-blue-50 to-red-50 border border-blue-200 rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-6 lg:p-8 shadow-md sm:shadow-lg">
            <div class="text-center">
                <h2 class="text-sm sm:text-base lg:text-3xl font-bold text-blue-600 mb-2 sm:mb-3 md:mb-4">
                    <FontAwesomeIcon :icon="faBook" class="text-blue-500 text-sm sm:text-2xl mr-1 sm:mr-2" />
                    Historia
                </h2>
              <p class="text-xs sm:text-sm md:text-base lg:text-lg text-gray-700 leading-tight sm:leading-relaxed max-w-4xl mx-auto">
                {{ siteSettings.description }}
              </p>
            </div>
          </div>
          <div v-else class="bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-6 lg:p-8 shadow-md sm:shadow-lg">
            <div class="text-center">
              <div class="text-2xl sm:text-3xl md:text-4xl mb-2 sm:mb-3 md:mb-4">
                <FontAwesomeIcon :icon="faBook" class="text-yellow-500 mr-2" />
              </div>
              <h2 class="text-sm sm:text-base md:text-lg lg:text-xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent mb-1 sm:mb-2">Descripción en desarrollo</h2>
              <p class="text-xs sm:text-sm md:text-base text-gray-700 leading-tight sm:leading-relaxed">
                La información de VASIR se encuentra en desarrollo... ¡Sigue disfrutando de nuestros servicios!
                <br>
                <strong>Administradores:</strong> Pueden configurar la descripción desde la sección "Configuración" del panel de administración.
              </p>
            </div>
          </div>
        </div>

        <!-- Misión y Visión - Layout responsivo -->
        <div v-if="siteSettings.mission || siteSettings.vision" class="mb-2">

          <!-- Móvil: Grid de 2 columnas compacto -->
          <div class="block md:hidden">
            <div class="grid grid-cols-2 gap-2 sm:gap-3">
              <div v-if="siteSettings.mission" class="border border-gray-300 bg-white shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 rounded-lg sm:rounded-xl p-2 sm:p-3">
                <div class="flex flex-row justify-center items-center text-center mb-1 sm:mb-2">
                  <FontAwesomeIcon :icon="faBullseye" class="text-red-500 text-lg sm:text-xl mb-1 mr-1" />
                  <h2 class="text-xs sm:text-sm font-bold text-red-600 leading-tight">Misión</h2>
                </div>
                <p class="text-xs text-gray-700 leading-tight text-center">
                  {{ siteSettings.mission }}
                </p>
              </div>

              <div v-if="siteSettings.vision" class="border border-gray-300 bg-white shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 rounded-lg sm:rounded-xl p-2 sm:p-3">
                <div class="flex flex-row justify-center items-center text-center mb-1 sm:mb-2">
                  <FontAwesomeIcon :icon="faStar" class="text-yellow-500 text-lg sm:text-xl mb-1 mr-1" />
                  <h2 class="text-xs sm:text-sm font-bold text-yellow-600 leading-tight">Visión</h2>
                </div>
                <p class="text-xs text-gray-700 leading-tight text-center">
                  {{ siteSettings.vision }}
                </p>
              </div>
            </div>
          </div>

          <!-- Desktop: Layout mejorado lado a lado -->
          <div class="hidden md:block">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-8 xl:gap-12">
              <div v-if="siteSettings.mission" class="border border-gray-300 bg-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 rounded-xl p-6 lg:p-8 text-center">
                <div class="flex flex-row justify-center items-center mb-4 lg:mb-6">
                  <div class="bg-red-50 rounded-full p-3">
                    <FontAwesomeIcon :icon="faBullseye" class="text-red-500 text-2xl lg:text-3xl" />
                  </div>
                  <h2 class="text-xl lg:text-2xl xl:text-3xl font-bold text-red-600">Nuestra Misión</h2>
                </div>
                <p class="text-sm lg:text-base xl:text-lg text-gray-700 leading-relaxed">
                  {{ siteSettings.mission }}
                </p>
              </div>

              <div v-if="siteSettings.vision" class="border border-gray-300 bg-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 rounded-xl p-6 lg:p-8 text-center">
                <div class="flex flex-row justify-center items-center mb-4 lg:mb-6">
                  <div class="bg-yellow-50 rounded-full p-3">
                    <FontAwesomeIcon :icon="faStar" class="text-yellow-500 text-2xl lg:text-3xl" />
                  </div>
                  <h2 class="text-xl lg:text-2xl xl:text-3xl font-bold text-yellow-600">Nuestra Visión</h2>
                </div>
                <p class="text-sm lg:text-base xl:text-lg text-gray-700 leading-relaxed">
                  {{ siteSettings.vision }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Mensaje cuando no hay contenido configurado -->
        <div v-if="!siteSettings.mission && !siteSettings.vision" class="text-center py-8 sm:py-12 mb-8 sm:mb-12 px-4">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 sm:p-6 md:p-8 max-w-2xl mx-auto">
            <div class="text-4xl sm:text-5xl md:text-6xl mb-3 sm:mb-4">
                <FontAwesomeIcon :icon="faCog" class="text-gray-400" />
            </div>
            <h3 class="text-lg sm:text-xl font-semibold text-yellow-800 mb-2">Contenido en configuración</h3>
            <p class="text-sm sm:text-base text-yellow-700">
              La misión y visión de la empresa están siendo configuradas por nuestro equipo.
              <br>
              <strong>Administradores:</strong> Pueden configurar este contenido desde la sección "Configuración" del panel de administración.
            </p>
          </div>
        </div>

      </div> <!-- End bg-gradient-to-br from-gray-50 to-white -->
    </div> <!-- End max-w-7xl mx-auto -->
  </div> <!-- End mb-8 sm:mb-12 -->
</template>
