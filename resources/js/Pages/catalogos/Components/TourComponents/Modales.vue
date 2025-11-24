<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import Dialog from 'primevue/dialog';
import Carousel from 'primevue/carousel';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faEye, faExclamationTriangle, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark } from '@fortawesome/free-solid-svg-icons';

// Props
const props = defineProps({
    // Modal Más Acciones
    visible: {
        type: Boolean,
        default: false
    },
    // Modal Detalles del Tour
    detailsVisible: {
        type: Boolean,
        default: false
    },
    // Modal Carrusel de Imágenes
    carouselVisible: {
        type: Boolean,
        default: false
    },
    // Modal Eliminar Tour
    deleteVisible: {
        type: Boolean,
        default: false
    },
    // Modal Cambios sin guardar
    unsavedChangesVisible: {
        type: Boolean,
        default: false
    },
    tour: {
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
        default: '/storage/tours/'
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
    'viewReservations',
    // Modal Detalles del Tour
    'update:detailsVisible',
    'openImageModal',
    // Modal Carrusel de Imágenes
    'update:carouselVisible',
    'update:carouselIndex',
    // Modal Eliminar Tour
    'update:deleteVisible',
    'deleteTour',
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
const duplicateTour = () => {
    emit('update:visible', false);
    emit('duplicate', props.tour);
};

const changeStatus = () => {
    emit('update:visible', false);
    emit('changeStatus', props.tour);
};

const generateReport = () => {
    emit('update:visible', false);
    emit('generateReport', props.tour);
};

const archiveTour = () => {
    emit('update:visible', false);
    emit('archive', props.tour);
};

const viewDetails = () => {
    emit('update:visible', false);
    emit('viewDetails', props.tour);
};

const viewReservations = () => {
    emit('update:visible', false);
    emit('viewReservations', props.tour);
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
    emit('deleteTour', props.tour);
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

// Exportar el componente con un nombre específico
defineOptions({
    name: 'TourModals'
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
                    Tour: <span class="text-blue-600">{{ tour.nombre || 'Sin nombre' }}</span>
                </h4>
                <p class="text-sm text-gray-600 mt-1">Selecciona una acción a realizar</p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <!-- Botón para ver detalles -->
                <button
                    type="button"
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="viewDetails"
                >
                    <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
                    <div class="text-left">
                        <div class="font-medium">Ver Detalles</div>
                        <div class="text-xs opacity-90">Mostrar información completa del tour</div>
                    </div>
                </button>

                <!-- Botón para ver reservas -->
                <button
                    type="button"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="viewReservations"
                >
                    <FontAwesomeIcon :icon="faPlus" class="h-5 w-5" />
                    <div class="text-left">
                        <div class="font-medium">Ver Reservas</div>
                        <div class="text-xs opacity-90">Gestionar reservas de este tour</div>
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
                    @click="closeModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />Cerrar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Detalles del Tour -->
    <Dialog
        v-model:visible="isDetailsVisible"
        header="Detalles del Tour"
        :modal="true"
        :closable="false"
        :style="dialogStyle"
        :draggable="false"
    >
        <div v-if="tour" class="space-y-4">
            <!-- Información básica del tour -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Nombre del Tour:</label>
                    <p class="text-lg text-gray-900 mt-1">{{ tour.nombre }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Categoría:</label>
                    <p class="text-lg text-gray-900 mt-1">{{ tour.categoria }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Precio:</label>
                    <p class="text-lg font-semibold text-green-600 mt-1">${{ tour.precio }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Estado:</label>
                    <div class="mt-2">
                        <span :class="{
                            'bg-green-100 text-green-800': tour.estado === 'DISPONIBLE',
                            'bg-red-100 text-red-800': tour.estado === 'AGOTADO' || tour.estado === 'CANCELADO',
                            'bg-yellow-100 text-yellow-800': tour.estado === 'EN_CURSO' || tour.estado === 'REPROGRAMADO',
                            'bg-blue-100 text-blue-800': tour.estado === 'COMPLETADO',
                            'bg-gray-100 text-gray-800': tour.estado === 'SUSPENDIDO'
                        }" class="px-3 py-2 rounded-full font-medium text-sm">
                            {{ tour.estado }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Fechas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Fecha de Salida:</label>
                    <p class="text-lg text-gray-900 mt-1">
                        {{ tour.fecha_salida ? new Date(tour.fecha_salida).toLocaleString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }) : 'No definida' }}
                    </p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Fecha de Regreso:</label>
                    <p class="text-lg text-gray-900 mt-1">
                        {{ tour.fecha_regreso ? new Date(tour.fecha_regreso).toLocaleString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }) : 'No definida' }}
                    </p>
                </div>
            </div>

            <!-- Cupos y transporte -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Cupo Mínimo:</label>
                    <p class="text-lg text-gray-900 mt-1">{{ tour.cupo_min || 'No definido' }} personas</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Cupo Máximo:</label>
                    <p class="text-lg text-gray-900 mt-1">{{ tour.cupo_max || 'No definido' }} personas</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Cupos Disponibles:</label>
                    <p class="text-lg font-semibold text-blue-600 mt-1">{{ tour.cupos_disponibles || 0 }} personas</p>
                </div>
            </div>

            <!-- Punto de salida y transporte -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Punto de Salida:</label>
                    <p class="text-lg text-gray-900 mt-1">{{ tour.punto_salida }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Transporte:</label>
                    <p class="text-lg text-gray-900 mt-1">{{ tour.transporte?.nombre || tour['transporte.nombre'] || 'No asignado' }}</p>
                </div>
            </div>

            <!-- Lo que incluye -->
            <div v-if="tour.incluye && tour.incluye.trim() && tour.incluye.trim() !== 'Uw'" class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Lo que incluye:</label>
                <div class="mt-2">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="item in tour.incluye.split('|')" :key="item" class="text-gray-900">{{ item.trim() }}</li>
                    </ul>
                </div>
            </div>

            <!-- Lo que NO incluye -->
            <div v-if="tour.no_incluye && tour.no_incluye.trim() && !tour.no_incluye.includes('Ubwxubeuxbyexn')" class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Lo que NO incluye:</label>
                <div class="mt-2">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="item in tour.no_incluye.split('|')" :key="item" class="text-gray-900">{{ item.trim() }}</li>
                    </ul>
                </div>
            </div>

            <!-- Imágenes del tour -->
            <div v-if="tour.imagenes && tour.imagenes.length > 0" class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700 mb-3 block">Imágenes del Tour:</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div
                        v-for="(imagen, index) in tour.imagenes"
                        :key="index"
                        class="relative group cursor-pointer"
                        @click="openImageModal(index)"
                    >
                        <img
                            :src="imagePath + (typeof imagen === 'string' ? imagen : imagen.nombre)"
                            :alt="`Imagen ${index + 1} del tour`"
                            class="w-full h-24 sm:h-32 object-cover rounded-lg border-2 border-gray-200 hover:border-blue-400 transition-all duration-200"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center pointer-events-none">
                            <FontAwesomeIcon :icon="faEye" class="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 h-6 w-6" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-gray-50 p-3 rounded-lg text-center">
                <p class="text-gray-500">No hay imágenes disponibles para este tour.</p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeDetailsModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />Cerrar
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
            <!-- Debug info -->
            <div class="mb-4 text-sm text-gray-600 text-center">
                Imagen {{ currentPageIndex + 1 }} de {{ props.selectedImages.length }}
            </div>

            <!-- Carrusel personalizado con navegación manual -->
            <div class="relative w-full" style="max-width: 610px">
                <!-- Imagen actual -->
                <div class="flex justify-center items-center w-full h-96 bg-gray-50 rounded-lg">
                    <img
                        :src="props.selectedImages[currentPageIndex]"
                        :alt="`Imagen ${currentPageIndex + 1} del tour`"
                        class="w-auto h-full max-h-96 object-contain rounded shadow"
                    />
                </div>

                <!-- Navegadores -->
                <template v-if="props.selectedImages.length > 1">
                    <!-- Botón anterior -->
                    <button
                        type="button"
                        @click="previousImage"
                        class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all duration-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <!-- Botón siguiente -->
                    <button
                        type="button"
                        @click="nextImage"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all duration-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </template>

                <!-- Indicadores -->
                <div v-if="props.selectedImages.length > 1" class="flex justify-center mt-4 space-x-2">
                    <button
                        v-for="(image, index) in props.selectedImages"
                        :key="index"
                        @click="goToImage(index)"
                        :class="{
                            'bg-blue-500': index === currentPageIndex,
                            'bg-gray-300 hover:bg-gray-400': index !== currentPageIndex
                        }"
                        class="w-3 h-3 rounded-full transition-all duration-200"
                    ></button>
                </div>
            </div>
        </div>
        <div v-else class="text-center text-gray-500 py-8">No hay imágenes para este tour.</div>
        <template #footer>
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeCarouselModal"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cerrar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Eliminar Tour -->
    <Dialog v-model:visible="isDeleteVisible" header="Eliminar tour" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¿Estás seguro de eliminar el tour: <b>{{ tour.nombre }}</b>?</span>
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
