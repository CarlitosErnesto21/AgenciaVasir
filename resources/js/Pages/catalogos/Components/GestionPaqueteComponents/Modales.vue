<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import Carousel from 'primevue/carousel';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faEye, faExclamationTriangle, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark, faClipboardList, faListDots, faCopy, faFileExport, faArchive } from '@fortawesome/free-solid-svg-icons';

// Props
const props = defineProps({
    // Modal Más Acciones
    visible: {
        type: Boolean,
        default: false
    },
    // Modal Detalles del Paquete
    detailsVisible: {
        type: Boolean,
        default: false
    },
    // Modal Carrusel de Imágenes
    carouselVisible: {
        type: Boolean,
        default: false
    },
    // Modal Eliminar Paquete
    deleteVisible: {
        type: Boolean,
        default: false
    },
    // Modal Cambios sin guardar
    unsavedChangesVisible: {
        type: Boolean,
        default: false
    },
    paquete: {
        type: Object,
        default: () => ({})
    },
    dialogStyle: {
        type: Object,
        default: () => ({})
    },
    // Para el carrusel de imágenes
    selectedImages: {
        type: Array,
        default: () => []
    },
    carouselIndex: {
        type: Number,
        default: 0
    },
    imagePath: {
        type: String,
        default: '/storage/paquetes/'
    },
    // Para el modal de eliminar
    isDeleting: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits([
    // Modal Más Acciones
    'update:visible',
    'duplicate',
    'changeStatus',
    'generateReport',
    'archive',
    'viewDetails',
    // Modal Detalles del Paquete
    'update:detailsVisible',
    'openImageModal',
    // Modal Carrusel de Imágenes
    'update:carouselVisible',
    'update:carouselIndex',
    // Modal Eliminar Paquete
    'update:deleteVisible',
    'deletePaquete',
    'cancelDelete',
    // Modal Cambios sin guardar
    'update:unsavedChangesVisible',
    'closeWithoutSaving',
    'continueEditing'
]);

// Computed para el v-model del modal Más Acciones
const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

// Computed para el v-model del modal de Detalles
const isDetailsVisible = computed({
    get: () => props.detailsVisible,
    set: (value) => emit('update:detailsVisible', value)
});

// Computed para el v-model del modal de Carrusel
const isCarouselVisible = computed({
    get: () => props.carouselVisible,
    set: (value) => emit('update:carouselVisible', value)
});

// Computed para el v-model del modal de eliminar
const isDeleteVisible = computed({
    get: () => props.deleteVisible,
    set: (value) => emit('update:deleteVisible', value)
});

// Computed para el v-model del modal de cambios sin guardar
const isUnsavedChangesVisible = computed({
    get: () => props.unsavedChangesVisible,
    set: (value) => emit('update:unsavedChangesVisible', value)
});

// Variable reactiva para el índice actual del carrusel
const currentPageIndex = ref(0);

// Variable para responsividad
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Watcher para sincronizar el índice cuando cambie el prop
watch(() => props.carouselIndex, (newIndex) => {
    currentPageIndex.value = newIndex;
}, { immediate: true });

// Watcher para emitir cambios del carrusel al padre
watch(currentPageIndex, (newIndex) => {
    emit('update:carouselIndex', newIndex);
});

// Funciones para las acciones
const duplicatePaquete = () => {
    emit('update:visible', false);
    emit('duplicate', props.paquete);
};

const changeStatus = () => {
    emit('update:visible', false);
    emit('changeStatus', props.paquete);
};

const generateReport = () => {
    emit('update:visible', false);
    emit('generateReport', props.paquete);
};

const archivePaquete = () => {
    emit('update:visible', false);
    emit('archive', props.paquete);
};

const viewDetails = () => {
    emit('update:visible', false);
    emit('viewDetails', props.paquete);
};

const closeModal = () => {
    emit('update:visible', false);
};

// Función para abrir el modal de carrusel de imágenes
const openImageModal = (index) => {
    emit('openImageModal', index);
};

// Función para formatear precio
const formatPrice = (price) => {
    return new Intl.NumberFormat('es-CR', {
        style: 'currency',
        currency: 'CRC'
    }).format(price);
};

// Función para formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return 'No definida';
    return new Date(dateString).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

// 🔄 Funciones de ciclo de vida para responsividad
onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateWindowWidth);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateWindowWidth);
    }
});

// 📱 Función de responsividad
const updateWindowWidth = () => {
    if (typeof window !== 'undefined') {
        windowWidth.value = window.innerWidth;
    }
};
</script>

<template>
    <!-- 🎭 Modal de Más Acciones -->
    <Dialog
        v-model:visible="isVisible"
        header="Más Acciones"
        :modal="true"
        :style="dialogStyle"
        :closable="true"
        :draggable="false"
    >
        <div class="space-y-4">
            <div class="text-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ paquete?.nombre }}</h3>
                <p class="text-sm text-gray-600">Selecciona una acción para este paquete</p>
            </div>
            
            <div class="grid grid-cols-1 gap-3">
                <!-- Ver Detalles -->
                <button
                    @click="viewDetails"
                    class="flex items-center gap-3 w-full p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors duration-200"
                >
                    <FontAwesomeIcon :icon="faEye" class="text-blue-600 text-lg" />
                    <div class="text-left">
                        <div class="font-medium text-blue-800">Ver Detalles</div>
                        <div class="text-sm text-blue-600">Información completa del paquete</div>
                    </div>
                </button>

                <!-- Duplicar -->
                <button
                    @click="duplicatePaquete"
                    class="flex items-center gap-3 w-full p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors duration-200"
                >
                    <FontAwesomeIcon :icon="faCopy" class="text-green-600 text-lg" />
                    <div class="text-left">
                        <div class="font-medium text-green-800">Duplicar Paquete</div>
                        <div class="text-sm text-green-600">Crear una copia para editar</div>
                    </div>
                </button>

                <!-- Cambiar Estado -->
                <button
                    @click="changeStatus"
                    class="flex items-center gap-3 w-full p-3 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg transition-colors duration-200"
                >
                    <FontAwesomeIcon :icon="faClipboardList" class="text-yellow-600 text-lg" />
                    <div class="text-left">
                        <div class="font-medium text-yellow-800">Cambiar Estado</div>
                        <div class="text-sm text-yellow-600">Modificar disponibilidad</div>
                    </div>
                </button>

                <!-- Generar Reporte -->
                <button
                    @click="generateReport"
                    class="flex items-center gap-3 w-full p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition-colors duration-200"
                >
                    <FontAwesomeIcon :icon="faFileExport" class="text-purple-600 text-lg" />
                    <div class="text-left">
                        <div class="font-medium text-purple-800">Generar Reporte</div>
                        <div class="text-sm text-purple-600">Exportar información del paquete</div>
                    </div>
                </button>

                <!-- Archivar -->
                <button
                    @click="archivePaquete"
                    class="flex items-center gap-3 w-full p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors duration-200"
                >
                    <FontAwesomeIcon :icon="faArchive" class="text-gray-600 text-lg" />
                    <div class="text-left">
                        <div class="font-medium text-gray-800">Archivar Paquete</div>
                        <div class="text-sm text-gray-600">Mover a archivo</div>
                    </div>
                </button>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center">
                <button
                    type="button"
                    class="bg-gray-500 hover:bg-gray-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-4" />
                    <span>Cerrar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- 📋 Modal de Detalles del Paquete -->
    <Dialog
        v-model:visible="isDetailsVisible"
        header="Detalles del Paquete"
        :modal="true"
        :style="{ width: windowWidth < 768 ? '95vw' : '600px' }"
        :closable="true"
        :draggable="false"
    >
        <div class="space-y-6" v-if="paquete">
            <!-- Información básica -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-lg font-bold text-blue-800 mb-3">{{ paquete.nombre }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Precio:</span>
                        <span class="ml-2 text-green-600 font-bold">{{ formatPrice(paquete.precio) }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Duración:</span>
                        <span class="ml-2">{{ paquete.duracion }} días</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Fecha de salida:</span>
                        <span class="ml-2">{{ formatDate(paquete.fecha_salida) }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Fecha de regreso:</span>
                        <span class="ml-2">{{ formatDate(paquete.fecha_regreso) }}</span>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            <div v-if="paquete.descripcion">
                <h4 class="font-semibold text-gray-800 mb-2">Descripción:</h4>
                <p class="text-gray-600 text-sm leading-relaxed bg-gray-50 p-3 rounded">
                    {{ paquete.descripcion }}
                </p>
            </div>

            <!-- Imágenes -->
            <div v-if="paquete.imagenes && paquete.imagenes.length > 0">
                <h4 class="font-semibold text-gray-800 mb-3">Imágenes del Paquete:</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div
                        v-for="(imagen, index) in paquete.imagenes"
                        :key="index"
                        class="relative group cursor-pointer"
                        @click="openImageModal(index)"
                    >
                        <img
                            :src="imagePath + imagen"
                            :alt="'Imagen ' + (index + 1)"
                            class="w-full h-24 object-cover rounded-lg border group-hover:opacity-75 transition-opacity"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all duration-200 flex items-center justify-center">
                            <FontAwesomeIcon :icon="faEye" class="text-white opacity-0 group-hover:opacity-100 text-xl transition-opacity" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>

    <!-- 🖼️ Modal Carrusel de Imágenes -->
    <Dialog
        v-model:visible="isCarouselVisible"
        header="Galería de Imágenes"
        :modal="true"
        :style="{ width: windowWidth < 768 ? '95vw' : '80vw', maxWidth: '900px' }"
        :closable="true"
        :draggable="false"
    >
        <div v-if="selectedImages && selectedImages.length > 0">
            <Carousel
                :value="selectedImages"
                :numVisible="1"
                :numScroll="1"
                :circular="true"
                :showNavigators="true"
                :showIndicators="true"
                v-model:page="currentPageIndex"
            >
                <template #item="slotProps">
                    <div class="text-center">
                        <img
                            :src="imagePath + slotProps.data"
                            :alt="'Imagen ' + (slotProps.index + 1)"
                            class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg"
                        />
                    </div>
                </template>
            </Carousel>
        </div>
        <div v-else class="text-center py-8">
            <p class="text-gray-500">No hay imágenes disponibles</p>
        </div>
    </Dialog>

    <!-- 🗑️ Modal de Eliminar Paquete -->
    <Dialog
        v-model:visible="isDeleteVisible"
        header="Eliminar Paquete"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¿Estás seguro de eliminar el paquete: <b>{{ paquete?.nombre }}</b>?</span>
                <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
            </div>
        </div>
        
        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="$emit('deletePaquete')"
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
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('cancelDelete')"
                    :disabled="isDeleting"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    <span>Cancelar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- ⚠️ Modal de Cambios sin Guardar -->
    <Dialog
        v-model:visible="isUnsavedChangesVisible"
        header="Cambios sin guardar"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-yellow-500" />
            <div>
                <p class="text-gray-800 font-medium">Tienes cambios sin guardar</p>
                <p class="text-sm text-gray-600 mt-1">¿Qué deseas hacer con los cambios?</p>
            </div>
        </div>
        
        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('closeWithoutSaving')"
                >
                    <FontAwesomeIcon :icon="faSignOut" class="h-4" />
                    <span>Salir sin guardar</span>
                </button>
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="$emit('continueEditing')"
                >
                    <FontAwesomeIcon :icon="faPencil" class="h-4" />
                    <span>Continuar</span>
                </button>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Animación para el spinner de loading */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Estilos para el carrusel */
:deep(.p-carousel-content) {
    background: transparent;
}

:deep(.p-carousel-item) {
    text-align: center;
}

:deep(.p-carousel-indicator) {
    background-color: rgba(255, 255, 255, 0.5);
}

:deep(.p-carousel-indicator.p-highlight) {
    background-color: #3b82f6;
}
</style>
