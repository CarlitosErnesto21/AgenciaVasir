<template>
    <!-- 📊 HEADER CON MÉTRICAS PRINCIPALES - Responsive Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Ventas Hoy -->
        <div class="bg-gradient-to-br from-[#e2a5a5] via-[#d7a1a1] to-[#c98585] rounded-xl shadow-md border border-[#d7a1a1] p-4 sm:p-6 transition-all duration-200 hover:scale-105">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-[#7b1f26] text-xs sm:text-sm font-medium opacity-90 truncate">{{ metrics[0].label }}</p>
                    <p class="text-[#7b1f26] text-lg sm:text-2xl font-bold mt-1">{{ metrics[0].value }}</p>
                </div>
                <div class="p-1.5 sm:p-2 bg-white bg-opacity-30 rounded-lg flex-shrink-0 ml-2">
                    <i :class="metrics[0].icon + ' text-[#7b1f26] text-lg sm:text-xl'"></i>
                </div>
            </div>
        </div>

        <!-- Ingresos Hoy -->
        <div class="bg-gradient-to-br from-[#ffe082] via-[#ffd54f] to-[#ffca28] rounded-xl shadow-md border border-[#ffd54f] p-4 sm:p-6 transition-all duration-200 hover:scale-105">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-[#a68700] text-xs sm:text-sm font-medium opacity-90 truncate">{{ metrics[1].label }}</p>
                    <p class="text-[#a68700] text-lg sm:text-2xl font-bold mt-1">
                        <span class="hidden sm:inline">{{ metrics[1].value }}</span>
                        <span class="sm:hidden">{{ formatValueForMobile(metrics[1].value, dashboardData.inventario) }}</span>
                    </p>
                </div>
                <div class="p-1.5 sm:p-2 bg-white bg-opacity-30 rounded-lg flex-shrink-0 ml-2">
                    <i :class="metrics[1].icon + ' text-[#a68700] text-lg sm:text-xl'"></i>
                </div>
            </div>
        </div>

        <!-- Reservas del Mes -->
        <Link href="/reservas"
            class="bg-gradient-to-br from-[#b39ddb] via-[#9575cd] to-[#7e57c2] rounded-xl shadow-md border border-[#9575cd] p-4 sm:p-6 cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 block">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-[#512da8] text-xs sm:text-sm font-medium opacity-90 truncate">{{ metrics[2].label }}</p>
                    <p class="text-[#512da8] text-lg sm:text-2xl font-bold mt-1">{{ metrics[2].value }}</p>
                    <p class="text-[#512da8] text-xs opacity-80 mt-1 hidden sm:block">Click para ver todas</p>
                    <p class="text-[#512da8] text-xs opacity-80 mt-1 sm:hidden">Tap aquí</p>
                </div>
                <div class="p-1.5 sm:p-2 bg-white bg-opacity-30 rounded-lg flex-shrink-0 ml-2">
                    <i :class="metrics[2].icon + ' text-[#512da8] text-lg sm:text-xl'"></i>
                </div>
            </div>
        </Link>

        <!-- Tours Activos -->
        <Link href="/tours"
            class="bg-gradient-to-br from-[#90caf9] via-[#64b5f6] to-[#42a5f5] rounded-xl shadow-md border border-[#64b5f6] p-4 sm:p-6 cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 block">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-[#1565c0] text-xs sm:text-sm font-medium opacity-90 truncate">{{ metrics[3].label }}</p>
                    <p class="text-[#1565c0] text-lg sm:text-2xl font-bold mt-1">{{ metrics[3].value }}</p>
                    <p class="text-[#1565c0] text-xs opacity-80 mt-1 hidden sm:block">Click para gestionar</p>
                    <p class="text-[#1565c0] text-xs opacity-80 mt-1 sm:hidden">Tap aquí</p>
                </div>
                <div class="p-1.5 sm:p-2 bg-white bg-opacity-30 rounded-lg flex-shrink-0 ml-2">
                    <i :class="metrics[3].icon + ' text-[#1565c0] text-lg sm:text-xl'"></i>
                </div>
            </div>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    metrics: {
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
</script>
