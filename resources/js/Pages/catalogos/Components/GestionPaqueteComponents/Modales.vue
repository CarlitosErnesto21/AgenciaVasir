<script setup>
import { computed, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Carousel from 'primevue/carousel';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faEye, faExclamationTriangle, faPencil, faSignOut, faSpinner, faXmark, faCopy, faFileAlt, faArchive, faPowerOff } from '@fortawesome/free-solid-svg-icons';

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

// Watcher para sincronizar el índice cuando cambie el prop
watch(() => props.carouselIndex, (newIndex) => {
    currentPageIndex.value = newIndex;
}, { immediate: true });

// Watcher para emitir cambios del carrusel al padre
watch(currentPageIndex, (newIndex) => {
    emit('update:carouselIndex', newIndex);
});

// Funciones para las acciones
const duplicate = () => {
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

const archive = () => {
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

// Función para cerrar el modal de detalles
const closeDetailsModal = () => {
    emit('update:detailsVisible', false);
};

// Función para cerrar el modal de carrusel
const closeCarouselModal = () => {
    emit('update:carouselVisible', false);
};

// Funciones de navegación del carrusel personalizado
const previousImage = () => {
    if (props.selectedImages.length > 1) {
        const newIndex = currentPageIndex.value === 0 ? props.selectedImages.length - 1 : currentPageIndex.value - 1;
        currentPageIndex.value = newIndex;
    }
};

const nextImage = () => {
    if (props.selectedImages.length > 1) {
        const newIndex = currentPageIndex.value === props.selectedImages.length - 1 ? 0 : currentPageIndex.value + 1;
        currentPageIndex.value = newIndex;
    }
};

const goToImage = (index) => {
    if (index >= 0 && index < props.selectedImages.length) {
        currentPageIndex.value = index;
    }
};

// Funciones para el modal de eliminar
const confirmDelete = () => {
    emit('deletePaquete', props.paquete);
};

const cancelDelete = () => {
    emit('cancelDelete');
    emit('update:deleteVisible', false);
};

// Funciones para el modal de cambios sin guardar
const closeWithoutSaving = () => {
    emit('closeWithoutSaving');
};

const continueEditing = () => {
    emit('continueEditing');
};

// Función para formatear texto a lista
const textoALista = (texto) => {
    return texto ? texto.split('|').filter(item => item.trim()).map(item => item.trim()) : [];
};

// Exportar el componente con un nombre específico
defineOptions({
    name: 'PaqueteModals'
});
</script>

<template>
    <!-- Modal de Más Acciones -->
    <Dialog
        v-model:visible="isVisible"
        header="Más Acciones"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="space-y-4">
            <div class="text-center mb-4">
                <h4 class="text-lg font-semibold text-gray-800">
                   Paquete: <span class="text-blue-600">{{ paquete.nombre || 'Paquete' }}</span>
                </h4>
                <p class="text-sm text-gray-600 mt-1">Selecciona una acción a realizar</p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <!-- Botón para ver detalles -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="viewDetails"
                >
                    <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Ver Detalles</div>
                        <div class="text-xs opacity-90">Información completa del paquete</div>
                    </div>
                </button>

                <!-- Botón para duplicar paquete -->
                <button
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="duplicate"
                >
                    <FontAwesomeIcon :icon="faCopy" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Duplicar Paquete</div>
                        <div class="text-xs opacity-90">Crear copia para editar</div>
                    </div>
                </button>

                <!-- Botón para cambiar estado -->
                <button
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="changeStatus"
                >
                    <FontAwesomeIcon :icon="faPowerOff" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Cambiar Estado</div>
                        <div class="text-xs opacity-90">Activar/Desactivar paquete</div>
                    </div>
                </button>

                <!-- Botón para generar reporte -->
                <button
                    class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="generateReport"
                >
                    <FontAwesomeIcon :icon="faFileAlt" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Generar Reporte</div>
                        <div class="text-xs opacity-90">Descargar información del paquete</div>
                    </div>
                </button>

                <!-- Botón para archivar -->
                <button
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="archive"
                >
                    <FontAwesomeIcon :icon="faArchive" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Archivar Paquete</div>
                        <div class="text-xs opacity-90">Mover a archivo histórico</div>
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
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Detalles del Paquete -->
    <Dialog
        v-model:visible="isDetailsVisible"
        header="Detalles del Paquete"
        :modal="true"
        :closable="false"
        :style="dialogStyle"
        :draggable="false"
    >
        <div v-if="paquete" class="space-y-4">
            <!-- Información básica del paquete -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Nombre:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ paquete.nombre || 'Sin nombre' }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">País:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ paquete.pais?.nombre || 'Sin país' }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Provincia:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ paquete.provincia?.nombre || 'Sin provincia' }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Precio:</label>
                    <p class="text-sm text-gray-900 mt-1 font-semibold">
                        {{ paquete.precio ? `$${Number(paquete.precio).toFixed(2)}` : 'No definido' }}
                    </p>
                </div>
            </div>

            <!-- Información de fechas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Fecha de Salida:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ paquete.fecha_salida || 'Sin fecha' }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Fecha de Regreso:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ paquete.fecha_regreso || 'Sin fecha' }}</p>
                </div>
            </div>

            <!-- Incluye -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Incluye:</label>
                <div class="mt-2">
                    <ul class="text-sm text-gray-900 list-disc list-inside space-y-1">
                        <li v-for="item in textoALista(paquete.incluye)" :key="item">{{ item }}</li>
                    </ul>
                    <p v-if="!paquete.incluye || textoALista(paquete.incluye).length === 0" class="text-sm text-gray-500">Sin información disponible</p>
                </div>
            </div>

            <!-- Condiciones -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Condiciones:</label>
                <div class="mt-2">
                    <ul class="text-sm text-gray-900 list-disc list-inside space-y-1">
                        <li v-for="item in textoALista(paquete.condiciones)" :key="item">{{ item }}</li>
                    </ul>
                    <p v-if="!paquete.condiciones || textoALista(paquete.condiciones).length === 0" class="text-sm text-gray-500">Sin condiciones disponibles</p>
                </div>
            </div>

            <!-- Recordatorio -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Recordatorio:</label>
                <div class="mt-2">
                    <ul class="text-sm text-gray-900 list-disc list-inside space-y-1">
                        <li v-for="item in textoALista(paquete.recordatorio)" :key="item">{{ item }}</li>
                    </ul>
                    <p v-if="!paquete.recordatorio || textoALista(paquete.recordatorio).length === 0" class="text-sm text-gray-500">Sin recordatorios disponibles</p>
                </div>
            </div>

            <!-- Imágenes del paquete -->
            <div v-if="paquete.imagenes && paquete.imagenes.length > 0" class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700 mb-3 block">Imágenes del Paquete:</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div
                        v-for="(imagen, index) in paquete.imagenes"
                        :key="index"
                        class="relative cursor-pointer group"
                        @click="openImageModal(index)"
                    >
                        <img
                            :src="imagePath + (typeof imagen === 'string' ? imagen : imagen.nombre)"
                            :alt="`Imagen ${index + 1} del paquete`"
                            class="w-full h-20 object-cover rounded-lg border group-hover:opacity-75 transition-opacity"
                        />
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black bg-opacity-50 rounded-lg">
                            <FontAwesomeIcon :icon="faEye" class="h-6 w-6 text-white" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-gray-50 p-3 rounded-lg text-center">
                <p class="text-gray-500">No hay imágenes disponibles para este paquete.</p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeDetailsModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal del carrusel de imágenes -->
    <Dialog
        v-model:visible="isCarouselVisible"
        header="Galería de Imágenes"
        :modal="true"
        :closable="false"
        :style="dialogStyle"
        :draggable="false"
    >
        <div v-if="props.selectedImages.length" class="flex flex-col items-center justify-center">
            <!-- Info de imagen actual -->
            <div class="mb-4 text-sm text-gray-600 text-center">
                Imagen {{ currentPageIndex + 1 }} de {{ props.selectedImages.length }}
            </div>

            <!-- Carrusel personalizado con navegación manual -->
            <div class="relative w-full" style="max-width: 610px">
                <!-- Imagen actual -->
                <div class="flex justify-center items-center w-full h-96 bg-gray-50 rounded-lg">
                    <img
                        :src="props.selectedImages[currentPageIndex]"
                        alt="Imagen del paquete"
                        class="w-auto h-full max-h-96 object-contain rounded shadow"
                    />
                </div>

                <!-- Navegadores -->
                <template v-if="props.selectedImages.length > 1">
                    <!-- Botón anterior -->
                    <button
                        @click="previousImage"
                        class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all"
                    >
                        <i class="pi pi-chevron-left"></i>
                    </button>

                    <!-- Botón siguiente -->
                    <button
                        @click="nextImage"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all"
                    >
                        <i class="pi pi-chevron-right"></i>
                    </button>
                </template>

                <!-- Indicadores -->
                <div v-if="props.selectedImages.length > 1" class="flex justify-center mt-4 space-x-2">
                    <button
                        v-for="(image, index) in props.selectedImages"
                        :key="index"
                        @click="goToImage(index)"
                        :class="[
                            'w-3 h-3 rounded-full transition-all',
                            index === currentPageIndex ? 'bg-blue-500' : 'bg-gray-300 hover:bg-gray-400'
                        ]"
                    ></button>
                </div>
            </div>
        </div>
        <div v-else class="text-center text-gray-500 py-8">No hay imágenes para este paquete.</div>
        <template #footer>
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeCarouselModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Eliminar Paquete -->
    <Dialog v-model:visible="isDeleteVisible" header="Eliminar paquete" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¿Estás seguro de eliminar el paquete: <b>{{ paquete.nombre }}</b>?</span>
                <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="confirmDelete"
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
                    @click="cancelDelete" :disabled="isDeleting">
                    <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cancelar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Cambios sin guardar -->
    <Dialog v-model:visible="isUnsavedChangesVisible" header="Cambios sin guardar" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
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
                    @click="closeWithoutSaving">
                    <FontAwesomeIcon :icon="faSignOut" class="h-4" /><span>Salir sin guardar</span>
                </button>
                <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="continueEditing">
                    <FontAwesomeIcon :icon="faPencil" class="h-4" /><span>Continuar</span>
                </button>
            </div>
        </template>
    </Dialog>
</template>


