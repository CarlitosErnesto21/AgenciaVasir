<template>
    <!-- 🚀 MODALES INTERACTIVOS - Responsive con PrimeVue Dialog -->

    <!-- Modal: Productos Stock Bajo -->
    <Dialog
        :visible="showProductosStockBajoModal"
        modal
        :draggable="false"
        :closable="true"
        :dismissableMask="true"
        class="mx-2 sm:mx-4"
        :style="{ width: '98vw', maxWidth: '36rem', maxHeight: 'calc(100vh - 20px)', padding: '0' }"
        :baseZIndex="10000"
        @update:visible="$emit('close-stock-modal')"
    >
        <template #header>
            <div class="flex items-center">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="text-red-500 mr-2" />
                <span class="text-base sm:text-lg font-semibold text-gray-900">
                    <span class="hidden sm:inline">Productos con Stock Bajo</span>
                    <span class="sm:hidden">Stock Bajo</span>
                </span>
            </div>
        </template>

        <!-- Texto informativo -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
            <div class="flex items-start space-x-2">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="text-yellow-500 text-sm mt-0.5 flex-shrink-0" />
                <div class="text-xs sm:text-sm text-yellow-700">
                    <p class="font-medium mb-1">Productos que requieren reabastecimiento</p>
                    <p class="text-yellow-600">Se muestran productos con stock actual igual o menor al stock mínimo configurado. Es recomendable reponerlos pronto.</p>
                </div>
            </div>
        </div>

        <div class="space-y-3 overflow-y-auto max-h-[60vh] sm:max-h-[70vh] px-2 sm:px-0">
            <div v-if="dashboardData.stockBajo && dashboardData.stockBajo.length > 0"
                class="space-y-3">
                <div v-for="producto in dashboardData.stockBajo"
                    :key="producto.id"
                    class="bg-red-50 rounded-lg border border-red-200 hover:bg-red-100 transition-colors p-3 sm:p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="p-2 bg-red-100 rounded-full flex-shrink-0">
                                <FontAwesomeIcon :icon="faBox" class="text-red-600 text-sm" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 text-sm truncate">{{ producto.nombre }}</h4>
                                <p class="text-xs text-gray-600 truncate">Categoría: {{ producto.categoria?.nombre || 'Sin categoría' }}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-2">
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                    Stock: {{ producto.stock_actual }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    Min: {{ producto.stock_minimo }}
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 mt-1">
                                ${{ Number(producto.precio || 0).toLocaleString('es-ES') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-6 sm:py-8">
                <FontAwesomeIcon :icon="faCheckCircle" class="text-green-400 text-3xl sm:text-4xl mb-3" />
                <p class="text-gray-500 text-sm sm:text-base">¡Perfecto! Todos los productos tienen stock suficiente</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                <Link
                    href="/productos"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                >
                    <FontAwesomeIcon :icon="faCheck" class="h-5 text-white" />
                    Gestionar Productos
                </Link>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTimes, faCheck, faExclamationTriangle, faBox, faCheckCircle } from '@fortawesome/free-solid-svg-icons';

defineProps({
    showProductosStockBajoModal: {
        type: Boolean,
        required: true
    },
    dashboardData: {
        type: Object,
        required: true
    }
});

defineEmits(['close-stock-modal']);
</script>
