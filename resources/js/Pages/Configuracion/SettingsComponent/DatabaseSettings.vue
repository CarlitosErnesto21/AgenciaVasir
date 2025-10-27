<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4">
            <h1 class="text-sm sm:text-lg lg:text-2xl font-bold text-white flex items-center">
                <FontAwesomeIcon :icon="faDatabase" class="mr-1 sm:mr-2 lg:mr-3 text-sm sm:text-lg lg:text-2xl" />
                <span class="hidden sm:inline">Gestión de Base de Datos</span>
                <span class="sm:hidden">Base de Datos</span>
            </h1>
            <p class="text-red-100 mt-0.5 sm:mt-1 lg:mt-2 text-xs sm:text-sm lg:text-base hidden sm:block">Respaldos y mantenimiento de la base de datos</p>
        </div>

        <!-- Content -->
        <div class="p-3 sm:p-4 lg:p-5">
            <div class="space-y-4">
                <!-- Respaldo de Base de Datos -->
                <div class="bg-red-50 rounded-lg p-3 sm:p-4 border border-red-200">
                    <h3 class="text-sm sm:text-base font-semibold text-gray-800 mb-3 flex items-center">
                        <span class="text-base sm:text-lg mr-2">💾</span>
                        Respaldo de Base de Datos
                    </h3>
                    <p class="text-xs sm:text-sm text-gray-600 mb-4">
                        Cree un respaldo completo de la base de datos para proteger su información.
                    </p>
                    <Link :href="route('backups')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-sm text-sm font-medium">
                        <FontAwesomeIcon :icon="faFileArchive" class="mr-2" />
                        Crear Respaldo
                    </Link>
                </div>

                <!-- Información de la Base de Datos -->
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                    <h3 class="text-sm sm:text-base font-semibold text-gray-800 mb-3 flex items-center">
                        <span class="text-base sm:text-lg mr-2">📊</span>
                        Información de la Base de Datos
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-3 border border-gray-100">
                            <span class="font-medium text-gray-800 text-sm block mb-1">Último respaldo</span>
                            <span class="text-sm text-gray-600">{{ databaseInfo.last_backup_formatted }}</span>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-100">
                            <span class="font-medium text-gray-800 text-sm block mb-1">Estado</span>
                            <span :class="getStatusClass(databaseInfo.status)" class="font-medium text-sm">
                                {{ databaseInfo.status_text }}
                            </span>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-100">
                            <span class="font-medium text-gray-800 text-sm block mb-1">Tamaño aprox</span>
                            <span class="text-sm text-gray-600">{{ databaseInfo.database_size }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 mt-4 pt-3 border-t border-gray-200">
                <button
                    @click="$emit('reset')"
                    type="button"
                    class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                >
                    Restablecer
                </button>
                <button
                    @click="$emit('save')"
                    :disabled="isSaving"
                    type="button"
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 transition-colors duration-200 flex items-center justify-center"
                >
                    <FontAwesomeIcon
                        v-if="isSaving"
                        :icon="faSpinner"
                        class="animate-spin mr-2"
                    />
                    {{ isSaving ? 'Guardando...' : 'Guardar Configuración' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faDatabase, faFileArchive, faSpinner } from '@fortawesome/free-solid-svg-icons'

defineProps({
    databaseInfo: {
        type: Object,
        required: true
    },
    isSaving: {
        type: Boolean,
        default: false
    }
})

defineEmits(['save', 'reset'])

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
