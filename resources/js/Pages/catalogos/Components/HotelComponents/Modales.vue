<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faEye, faExclamationTriangle,  faPencil,  faSignOut, faSpinner, faTrashCan, faXmark, faClipboardList, faMapLocationDot } from '@fortawesome/free-solid-svg-icons';

// Props
const props = defineProps({
    // Modal Detalles del Hotel
    detailsVisible: {
        type: Boolean,
        default: false
    },
    // Modal Carrusel de Imágenes
    carouselVisible: {
        type: Boolean,
        default: false
    },
    // Modal Eliminar Hotel
    deleteVisible: {
        type: Boolean,
        default: false
    },
    // Modal Cambios sin guardar
    unsavedChangesVisible: {
        type: Boolean,
        default: false
    },
    hotel: {
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
        default: '/storage/hoteles/'
    },
    // Para el modal de eliminar
    isDeleting: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits([
    // Modal Detalles del Hotel
    'update:detailsVisible',
    'openImageModal',
    // Modal Carrusel de Imágenes
    'update:carouselVisible',
    'update:carouselIndex',
    // Modal Eliminar Hotel
    'update:deleteVisible',
    'deleteHotel',
    'cancelDelete',
    // Modal Cambios sin guardar
    'update:unsavedChangesVisible',
    'closeWithoutSaving',
    'continueEditing'
]);

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
    emit('deleteHotel', props.hotel);
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
    name: 'HotelModals'
});
</script>

<template>
    <!-- Modal de Detalles del Hotel -->
    <Dialog
        v-model:visible="isDetailsVisible"
        header="Detalles del Hotel"
        :modal="true"
        :closable="false"
        :style="dialogStyle"
        :draggable="false"
    >
        <div v-if="hotel" class="space-y-4">
            <!-- Información básica del hotel -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Nombre:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ hotel.nombre || 'Sin nombre' }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Provincia:</label>
                    <p class="text-sm text-gray-900 mt-1">{{ hotel.provincia?.nombre || 'Sin provincia' }}</p>
                </div>
            </div>

            <!-- País -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">País:</label>
                <p class="text-sm text-gray-900 mt-1">{{ hotel.provincia?.pais?.nombre || 'Sin país' }}</p>
            </div>

            <!-- Dirección del hotel -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Dirección:</label>
                <p class="text-sm text-gray-900 mt-2 leading-relaxed">
                    {{ hotel.direccion || 'Sin dirección disponible' }}
                </p>
            </div>

            <!-- Descripción del hotel -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700">Descripción:</label>
                <p class="text-sm text-gray-900 mt-2 leading-relaxed">
                    {{ hotel.descripcion || 'Sin descripción disponible' }}
                </p>
            </div>

            <!-- Imágenes del hotel -->
            <div v-if="hotel.imagenes && hotel.imagenes.length > 0" class="bg-gray-50 p-3 rounded-lg">
                <label class="text-sm font-semibold text-gray-700 mb-3 block">Imágenes del Hotel:</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div
                        v-for="(imagen, index) in hotel.imagenes"
                        :key="index"
                        class="relative cursor-pointer group"
                        @click="openImageModal(index)"
                    >
                        <img
                            :src="imagePath + (typeof imagen === 'string' ? imagen : imagen.nombre)"
                            :alt="`Imagen ${index + 1} del hotel`"
                            class="w-full h-20 object-cover rounded-lg border group-hover:opacity-75 transition-opacity"
                        />
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black bg-opacity-50 rounded-lg">
                            <FontAwesomeIcon :icon="faEye" class="h-6 w-6 text-white" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-gray-50 p-3 rounded-lg text-center">
                <p class="text-gray-500">No hay imágenes disponibles para este hotel.</p>
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
                        alt="Imagen del hotel"
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
        <div v-else class="text-center text-gray-500 py-8">No hay imágenes para este hotel.</div>
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

    <!-- Modal de Eliminar Hotel -->
    <Dialog v-model:visible="isDeleteVisible" header="Eliminar hotel" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¿Estás seguro de eliminar el hotel: <b>{{ hotel.nombre }}</b>?</span>
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
