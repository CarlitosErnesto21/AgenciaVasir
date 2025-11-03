<template>
    <!-- 🚀 MODALES INTERACTIVOS - Responsive con PrimeVue Dialog -->

    <!-- Modal: Reservas Pendientes -->
    <Dialog
        :visible="showReservasPendientesModal"
        modal
        :draggable="false"
        :closable="true"
        :dismissableMask="true"
        class="mx-2 sm:mx-4"
        :style="{ width: '98vw', maxWidth: '36rem', maxHeight: 'calc(100vh - 20px)', padding: '0' }"
        :baseZIndex="10000"
        @update:visible="$emit('close-reservas-modal')"
    >
        <template #header>
            <div class="flex items-center">
                <i class="pi pi-clock text-amber-500 mr-2"></i>
                <span class="text-base sm:text-lg font-semibold text-gray-900">Reservas Pendientes</span>
            </div>
        </template>

        <div class="space-y-3 overflow-y-auto max-h-[60vh] sm:max-h-[70vh] px-2 sm:px-0">
            <div v-if="dashboardData.reservas && dashboardData.reservas.filter(r => r.estado && r.estado.toLowerCase() === 'pendiente').length > 0"
                class="space-y-3">
                <div v-for="reserva in dashboardData.reservas.filter(r => r.estado && r.estado.toLowerCase() === 'pendiente')"
                    :key="reserva.id"
                    class="bg-red-50 rounded-lg border border-red-200 hover:bg-red-100 transition-colors p-3 sm:p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="p-2 bg-red-100 rounded-full flex-shrink-0">
                                <i class="pi pi-calendar text-red-600 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 text-sm truncate">{{ reserva.entidad_nombre || 'Tour no especificado' }}</h4>
                                <p class="text-xs text-gray-600 truncate">
                                    {{ (reserva.cliente && reserva.cliente.user && reserva.cliente.user.name) ||
                                       (reserva.cliente && reserva.cliente.nombres) ||
                                       'Cliente no asignado' }}
                                </p>
                                <p class="text-xs text-gray-500">{{ new Date(reserva.fecha_reserva || reserva.fecha).toLocaleDateString('es-ES') }}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <p class="font-semibold text-gray-900 text-sm">${{ Number(reserva.total || 0).toLocaleString('es-ES') }}</p>
                            <p class="text-xs text-amber-600">{{ reserva.mayores_edad || 0 }} adultos, {{ reserva.menores_edad || 0 }} niños</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-6 sm:py-8">
                <i class="pi pi-check-circle text-green-400 text-3xl sm:text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm sm:text-base">¡Excelente! No hay reservas pendientes</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                <Link
                    href="/reservas"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                >
                    <FontAwesomeIcon :icon="faCheck" class="h-5 text-white" />
                    Ver Reservas
                </Link>
            </div>
        </template>
    </Dialog>

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
                <i class="pi pi-exclamation-triangle text-red-500 mr-2"></i>
                <span class="text-base sm:text-lg font-semibold text-gray-900">
                    <span class="hidden sm:inline">Productos con Stock Bajo</span>
                    <span class="sm:hidden">Stock Bajo</span>
                </span>
            </div>
        </template>

        <div class="space-y-3 overflow-y-auto max-h-[60vh] sm:max-h-[70vh] px-2 sm:px-0">
            <div v-if="dashboardData.stockBajo && dashboardData.stockBajo.length > 0"
                class="space-y-3">
                <div v-for="producto in dashboardData.stockBajo"
                    :key="producto.id"
                    class="bg-red-50 rounded-lg border border-red-200 hover:bg-red-100 transition-colors p-3 sm:p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="p-2 bg-red-100 rounded-full flex-shrink-0">
                                <i class="pi pi-box text-red-600 text-sm"></i>
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
                <i class="pi pi-check-circle text-green-400 text-3xl sm:text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm sm:text-base">¡Perfecto! Todos los productos tienen stock suficiente</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                <Link
                    href="/inventario"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                >
                    <FontAwesomeIcon :icon="faCheck" class="h-5 text-white" />
                    Gestionar Inventario
                </Link>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTimes, faCheck } from '@fortawesome/free-solid-svg-icons';

defineProps({
    showReservasPendientesModal: {
        type: Boolean,
        required: true
    },
    showProductosStockBajoModal: {
        type: Boolean,
        required: true
    },
    dashboardData: {
        type: Object,
        required: true
    }
});

defineEmits(['close-reservas-modal', 'close-stock-modal']);
</script>
