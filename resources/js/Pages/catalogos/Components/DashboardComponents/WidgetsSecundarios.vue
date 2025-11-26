<template>
  <div class="lg:space-y-4 grid grid-cols-2 lg:grid-cols-1 gap-3 lg:gap-4">
    <!-- Reservas Pendientes -->
    <Link href="/tours" class="block">
      <div class="p-0 rounded-2xl border-2 border-solid bg-gradient-to-br from-[#f7e7b2] via-[#f2daa0] to-[#e6c87a] shadow-md border-[#f2daa0]
        transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 cursor-pointer max-w-full">
        <div class="flex items-stretch min-w-0">
          <!-- Indicador lateral SOLO en md+ -->
          <div class="hidden md:block w-2 rounded-l-2xl bg-yellow-400"></div>
          <div class="flex-1 p-4 sm:p-6 flex flex-col justify-center min-w-0">
            <div class="flex items-center justify-between min-w-0">
              <div class="min-w-0 flex-1 font-sans">
                <p class="text-xs sm:text-sm font-medium text-gray-700 truncate">{{ widgets[0].title }}</p>
                <hr class="my-2 border-t-2 border-gray-200 opacity-30" />
                <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ widgets[0].value }}</p>
                <p class="text-xs text-gray-500 mt-1">Click para ver reservas</p>
              </div>
              <div class="flex items-center justify-center shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-white/40 rounded-full overflow-hidden ml-2">
                <FontAwesomeIcon :icon="getIconByName(widgets[0].icon)" :class="'text-base sm:text-xl ' + widgets[0].iconColor" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </Link>

    <!-- Productos Stock Bajo -->
    <div class="p-0 rounded-2xl border-2 border-solid bg-gradient-to-br from-[#f5b8b8] via-[#e7a6a6] to-[#d88a8a] shadow-md border-[#e7a6a6]
      transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 cursor-pointer max-w-full"
         @click="$emit('toggle-stock-modal')">
      <div class="flex items-stretch min-w-0">
        <div class="hidden md:block w-2 rounded-l-2xl bg-red-400"></div>
        <div class="flex-1 p-4 sm:p-6 flex flex-col justify-center min-w-0">
          <div class="flex items-center justify-between min-w-0">
            <div class="min-w-0 flex-1 font-sans">
              <p class="text-xs sm:text-sm font-medium text-gray-700 truncate">{{ widgets[1].title }}</p>
              <hr class="my-2 border-t-2 border-gray-200 opacity-30" />
              <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ widgets[1].value }}</p>
              <p class="text-xs text-gray-500 mt-1">Click para ver productos</p>
            </div>
            <div class="flex items-center justify-center shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-white/40 rounded-full overflow-hidden ml-2">
              <FontAwesomeIcon :icon="getIconByName(widgets[1].icon)" :class="'text-base sm:text-xl ' + widgets[1].iconColor" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Valor Inventario -->
    <Link href="/inventario" class="block">
      <div class="p-0 rounded-2xl border-2 border-solid col-span-2 lg:col-span-1 bg-gradient-to-br from-[#b2f7e7] via-[#a0f2da] to-[#7ae6c8] shadow-md border-[#a0f2da]
        transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 cursor-pointer max-w-full">
        <div class="flex items-stretch min-w-0">
          <div class="hidden md:block w-2 rounded-l-2xl bg-green-400"></div>
          <div class="flex-1 p-4 sm:p-6 flex flex-col justify-center min-w-0">
            <div class="flex items-center justify-between min-w-0">
              <div class="min-w-0 flex-1 font-sans">
                <p class="text-xs sm:text-sm font-medium text-gray-700 truncate">{{ widgets[2].title }}</p>
                <hr class="my-2 border-t-2 border-gray-200 opacity-30" />
                <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">
                  <span class="hidden sm:inline">{{ widgets[2].value }}</span>
                  <span class="sm:hidden">{{ formatValueForMobile(widgets[2].value, dashboardData.inventario) }}</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">Click para ver inventario</p>
              </div>
              <div class="flex items-center justify-center shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-white/40 rounded-full overflow-hidden ml-2">
                <FontAwesomeIcon :icon="getIconByName(widgets[2].icon)" :class="'text-base sm:text-xl ' + widgets[2].iconColor" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClock, faExclamationTriangle, faWallet, faUsers } from '@fortawesome/free-solid-svg-icons';

defineProps({
    widgets: {
        type: Array,
        required: true
    },
    dashboardData: {
        type: Object,
        required: true
    },
    formatValueForMobile: {
        type: Function,
        required: true
    }
});

defineEmits(['toggle-stock-modal']);

// Función helper para obtener el ícono FontAwesome por nombre
const getIconByName = (iconName) => {
    const iconMap = {
        'faClock': faClock,
        'faExclamationTriangle': faExclamationTriangle,
        'faWallet': faWallet,
        'faUsers': faUsers
    };
    return iconMap[iconName] || faClock;
};
</script>
