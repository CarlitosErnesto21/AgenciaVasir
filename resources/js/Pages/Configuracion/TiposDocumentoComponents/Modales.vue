<template>
    <!-- Modal de Más Acciones -->
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        modal
        header="Más Acciones"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="space-y-4">
            <div class="text-center mb-4">
                <h4 class="text-lg font-semibold text-gray-800">
                   Tipo: <span class="text-blue-600">{{ tipoDocumento?.nombre || 'Tipo de Documento' }}</span>
                </h4>
                <p class="text-sm text-gray-600 mt-1">Selecciona una acción a realizar</p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <!-- Botón para ver detalles -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="$emit('view-details', tipoDocumento)"
                >
                    <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Ver Detalles</div>
                        <div class="text-xs opacity-90">Información completa del tipo</div>
                    </div>
                </button>

                <!-- Botón para editar tipo -->
                <button
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="$emit('edit-tipo', tipoDocumento)"
                >
                    <FontAwesomeIcon :icon="faPencil" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Editar Tipo</div>
                        <div class="text-xs opacity-90">Modificar información del tipo</div>
                    </div>
                </button>

                <!-- Botón para generar reporte -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="$emit('generate-report', tipoDocumento)"
                >
                    <FontAwesomeIcon :icon="faFileAlt" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Generar Reporte</div>
                        <div class="text-xs opacity-90">Reporte de uso del tipo</div>
                    </div>
                </button>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
                    💡 Selecciona una acción para continuar.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center w-full">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('update:visible', false)"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Confirmación de Eliminación -->
    <Dialog
        :visible="deleteVisible"
        @update:visible="$emit('update:deleteVisible', $event)"
        modal
        header="Eliminar tipo de documento"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¿Estás seguro de eliminar el tipo de documento: <b>{{ tipoDocumento?.nombre }}</b>?</span>
                <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="$emit('delete-tipo')"
                    :disabled="isDeleting"
                >
                    <FontAwesomeIcon
                        :icon="isDeleting ? faSpinner : faCheck"
                        :class="[
                            'h-5',
                            { 'animate-spin': isDeleting }
                        ]"
                    />
                    <span v-if="!isDeleting">Eliminar</span>
                    <span v-else>Eliminando...</span>
                </button>
                <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('cancel-delete')" :disabled="isDeleting">
                    <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cancelar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Cambios No Guardados -->
    <Dialog
        :visible="unsavedChangesVisible"
        @update:visible="$emit('update:unsavedChangesVisible', $event)"
        modal
        header="Cambios sin guardar"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¡Tienes información sin guardar!</span>
                <span class="text-red-600 text-sm font-medium mt-1">¿Deseas salir sin guardar?</span>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-3 w-full">
                <button type="button" class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('close-without-saving')">
                    <FontAwesomeIcon :icon="faSignOut" class="h-4" /><span>Salir sin guardar</span>
                </button>
                <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('continue-editing')">
                    <FontAwesomeIcon :icon="faPencil" class="h-4" /><span>Continuar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Detalles -->
    <Dialog
        :visible="detallesVisible"
        @update:visible="$emit('update:detallesVisible', $event)"
        modal
        header="Detalles del Tipo de Documento"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div v-if="tipoDocumento" class="space-y-4">
            <!-- Información básica del tipo -->
            <div class="grid grid-cols-1 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Nombre:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ tipoDocumento.nombre || 'Sin nombre' }}</p>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Descripción:</label>
                <p class="text-sm text-gray-900 mt-2 leading-relaxed">
                    Tipo de documento utilizado para identificación de personas.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('update:detallesVisible', false)"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import Dialog from 'primevue/dialog';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye, faPencil, faFileAlt, faExclamationTriangle, faSpinner, faXmark, faCheck, faSignOut } from '@fortawesome/free-solid-svg-icons';

defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    deleteVisible: {
        type: Boolean,
        default: false
    },
    unsavedChangesVisible: {
        type: Boolean,
        default: false
    },
    detallesVisible: {
        type: Boolean,
        default: false
    },
    tipoDocumento: {
        type: Object,
        default: null
    },
    dialogStyle: {
        type: Object,
        default: () => ({})
    },
    isDeleting: {
        type: Boolean,
        default: false
    },
    isLoading: {
        type: Boolean,
        default: false
    },
    submitted: {
        type: Boolean,
        default: false
    }
});

defineEmits([
    'update:visible',
    'update:deleteVisible',
    'update:unsavedChangesVisible',
    'update:detallesVisible',
    'view-details',
    'edit-tipo',
    'generate-report',
    'delete-tipo',
    'cancel-delete',
    'close-without-saving',
    'continue-editing'
]);
</script>
