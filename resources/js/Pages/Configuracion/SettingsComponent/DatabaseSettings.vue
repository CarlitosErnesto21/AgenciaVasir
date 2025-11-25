<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-red-600 via-red-700 to-red-800 px-3 sm:px-4 lg:px-6 xl:px-8 py-4 sm:py-6 lg:py-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-white/5 to-blue-500/10"></div>
            <div class="relative">
                <h1 class="text-lg sm:text-xl lg:text-2xl xl:text-3xl font-bold text-white flex items-center mb-1 sm:mb-2">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-1.5 sm:p-2 rounded-lg sm:rounded-xl mr-2 sm:mr-3 shadow-lg">
                        <FontAwesomeIcon :icon="faDatabase" class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-white" />
                    </div>
                    <span class="bg-gradient-to-r from-white to-red-100 bg-clip-text text-transparent">
                        <span class="hidden sm:inline">Gestión de Base de Datos</span>
                        <span class="sm:hidden">Base de Datos</span>
                    </span>
                </h1>
                <p class="text-red-100 text-xs sm:text-sm lg:text-base ml-8 sm:ml-12 lg:ml-14 hidden sm:block font-medium">Administración completa de respaldos y mantenimiento del sistema</p>
            </div>
        </div>

        <!-- Content -->
        <div class="p-3 sm:p-4 lg:p-5 xl:p-6">
            <div class="space-y-4">
                <!-- Respaldo de Base de Datos -->
                <div class="bg-gradient-to-br from-white to-red-50/50 rounded-xl sm:rounded-2xl p-4 sm:p-5 lg:p-6 border border-red-100/50 shadow-lg hover:shadow-xl transition-all duration-300">
                    <div class="flex flex-col sm:flex-row sm:items-center mb-3 sm:mb-4">
                        <div class="bg-gradient-to-br from-red-500 to-red-600 p-2 sm:p-3 rounded-lg sm:rounded-xl shadow-lg mr-0 sm:mr-4 mb-2 sm:mb-0 self-start">
                            <FontAwesomeIcon :icon="faSave" class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Estado de la Base de Datos</h3>
                            <p class="text-xs sm:text-sm text-red-600 font-medium">Monitoreo y estadísticas</p>
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-600 mb-4 sm:mb-6 leading-relaxed pl-1">
                        Información y métricas del estado actual de la base de datos del sistema.
                        <span class="hidden sm:inline">Monitoreo en tiempo real del rendimiento y almacenamiento.</span>
                    </p>
                    <div class="flex justify-center">
                        <Link :href="route('backups')"
                            class="group inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg sm:rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-xs sm:text-sm font-semibold w-full sm:w-auto">
                            <FontAwesomeIcon :icon="faFileArchive" class="mr-2 sm:mr-3 w-3 h-3 sm:w-4 sm:h-4 group-hover:scale-110 transition-transform" />
                            <span class="hidden sm:inline">Gestionar Backups</span>
                            <span class="sm:hidden">Backups</span>
                        </Link>
                    </div>
                </div>

                <!-- Información de la Base de Datos -->
                <div class="bg-gradient-to-br from-gray-50 to-red-50/30 rounded-xl sm:rounded-2xl p-4 sm:p-5 lg:p-6 border border-red-100/50 shadow-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 sm:mb-6">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-2 sm:p-3 rounded-lg sm:rounded-xl shadow-lg mr-0 sm:mr-4 mb-2 sm:mb-0 self-start">
                            <FontAwesomeIcon :icon="faChartBar" class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Información del Sistema</h3>
                            <p class="text-xs sm:text-sm text-blue-600 font-medium">Métricas y estado actual</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                        <div class="bg-gradient-to-br from-white to-red-50/30 rounded-lg sm:rounded-xl p-3 sm:p-4 lg:p-5 border border-red-100/50 shadow-md hover:shadow-lg transition-all duration-300 group">
                            <div class="flex items-center mb-2 sm:mb-3">
                                <div class="bg-gradient-to-br from-red-500 to-red-600 p-1.5 sm:p-2 rounded-md sm:rounded-lg mr-2 sm:mr-3 group-hover:scale-110 transition-transform">
                                    <FontAwesomeIcon :icon="faFileArchive" class="w-3 h-3 sm:w-4 sm:h-4 text-white" />
                                </div>
                                <span class="font-semibold text-gray-800 text-xs sm:text-sm">Último Respaldo</span>
                            </div>
                            <span class="text-xs sm:text-sm text-gray-700 font-bold block break-words">{{ databaseInfo.last_backup_formatted }}</span>
                        </div>
                        <div class="bg-gradient-to-br from-white to-blue-50/30 rounded-lg sm:rounded-xl p-3 sm:p-4 lg:p-5 border border-blue-100/50 shadow-md hover:shadow-lg transition-all duration-300 group">
                            <div class="flex items-center mb-2 sm:mb-3">
                                <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-1.5 sm:p-2 rounded-md sm:rounded-lg mr-2 sm:mr-3 group-hover:scale-110 transition-transform">
                                    <FontAwesomeIcon :icon="faInfoCircle" class="w-3 h-3 sm:w-4 sm:h-4 text-white" />
                                </div>
                                <span class="font-semibold text-gray-800 text-xs sm:text-sm">Estado del Sistema</span>
                            </div>
                            <span :class="getStatusClass(databaseInfo.status)" class="font-bold text-xs sm:text-sm block">
                                {{ databaseInfo.status_text }}
                            </span>
                        </div>
                        <div class="bg-gradient-to-br from-white to-gray-50/30 rounded-lg sm:rounded-xl p-3 sm:p-4 lg:p-5 border border-gray-100/50 shadow-md hover:shadow-lg transition-all duration-300 group">
                            <div class="flex items-center mb-2 sm:mb-3">
                                <div class="bg-gradient-to-br from-red-600 to-red-700 p-1.5 sm:p-2 rounded-md sm:rounded-lg mr-2 sm:mr-3 group-hover:scale-110 transition-transform">
                                    <FontAwesomeIcon :icon="faDatabase" class="w-3 h-3 sm:w-4 sm:h-4 text-white" />
                                </div>
                                <span class="font-semibold text-gray-800 text-xs sm:text-sm">Tamaño de Datos</span>
                            </div>
                            <span class="text-xs sm:text-sm text-gray-700 font-bold block">{{ databaseInfo.database_size }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faDatabase, faFileArchive, faSave, faChartBar, faInfoCircle } from '@fortawesome/free-solid-svg-icons'

defineProps({
    databaseInfo: {
        type: Object,
        required: true
    }
})



// Función para obtener las clases CSS según el estado de la base de datos
const getStatusClass = (status) => {
    switch (status) {
        case 'operational':
            return 'text-green-600';
        case 'high_load':
            return 'text-yellow-600';
        case 'warning':
            return 'text-orange-600';
        case 'error':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};
</script>
