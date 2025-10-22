<script setup>
import { computed, ref } from 'vue';
import Dialog from 'primevue/dialog';
import Carousel from 'primevue/carousel';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye, faExclamationTriangle, faPlus, faTrashCan, faXmark } from '@fortawesome/free-solid-svg-icons';

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
    // Modal Detalles del Tour
    'update:detailsVisible',
    'openImageModal',
    // Modal Carrusel de Imágenes
    'update:carouselVisible',
    'update:carouselIndex'
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

// Computed para el v-model del índice del carrusel
const currentCarouselIndex = computed({
    get: () => props.carouselIndex,
    set: (value) => emit('update:carouselIndex', value)
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
                
                <!-- Botón para duplicar tour -->
                <button 
                    type="button" 
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="duplicateTour"
                >
                    <FontAwesomeIcon :icon="faPlus" class="h-5 w-5" />
                    <div class="text-left">
                        <div class="font-medium">Duplicar Tour</div>
                        <div class="text-xs opacity-90">Crear una copia de este tour</div>
                    </div>
                </button>
                
                <!-- Botón para cambiar estado -->
                <button 
                    type="button" 
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="changeStatus"
                >
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-5 w-5" />
                    <div class="text-left">
                        <div class="font-medium">Cambiar Estado</div>
                        <div class="text-xs opacity-90">Modificar el estado del tour</div>
                    </div>
                </button>
                
                <!-- Botón para generar reporte -->
                <button 
                    type="button" 
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="generateReport"
                >
                    <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
                    <div class="text-left">
                        <div class="font-medium">Generar Reporte</div>
                        <div class="text-xs opacity-90">Crear reporte detallado del tour</div>
                    </div>
                </button>
                
                <!-- Botón para archivar -->
                <button 
                    type="button" 
                    class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="archiveTour"
                >
                    <FontAwesomeIcon :icon="faTrashCan" class="h-5 w-5" />
                    <div class="text-left">
                        <div class="font-medium">Archivar Tour</div>
                        <div class="text-xs opacity-90">Mover a archivos históricos</div>
                    </div>
                </button>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
                    💡 Selecciona una acción para continuar
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
                    <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />Cerrar
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
                        {{ tour.fecha_salida ? new Date(tour.fecha_salida).toLocaleString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'No definida' }}
                    </p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700">Fecha de Regreso:</label>
                    <p class="text-lg text-gray-900 mt-1">
                        {{ tour.fecha_regreso ? new Date(tour.fecha_regreso).toLocaleString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'No definida' }}
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
        <div v-if="selectedImages.length" class="flex flex-col items-center justify-center">
            <Carousel 
                :value="selectedImages" 
                :numVisible="1" 
                :numScroll="1" 
                :circular="true" 
                v-model:page="currentCarouselIndex" 
                class="w-full" 
                :showIndicators="selectedImages.length > 1" 
                :showNavigators="selectedImages.length > 1" 
                style="max-width: 610px"
            >
                <template #item="slotProps">
                    <div class="flex justify-center items-center w-full h-96">
                        <img :src="slotProps.data" alt="Imagen tour" class="w-auto h-full max-h-96 object-contain rounded shadow"/>
                    </div>
                </template>
            </Carousel>
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
</template>