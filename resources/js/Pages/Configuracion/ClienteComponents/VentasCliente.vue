<template>
    <AuthenticatedLayout>
        <Head :title="`Ventas de ${cliente?.user?.name || 'Cliente'}`" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <!-- Header con información del cliente -->
            <div class="mb-6 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-blue-600 mb-2">Ventas del Cliente</h1>

                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <FontAwesomeIcon :icon="faUser" class="text-blue-500" />
                                <span class="text-lg font-semibold text-gray-800">{{ cliente?.user?.name || 'Cliente no encontrado' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <FontAwesomeIcon :icon="faIdCard" class="text-gray-500" />
                                <span class="text-sm text-gray-600">{{ cliente?.numero_identificacion || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <Link
                        :href="route('clientes')"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 text-sm"
                    >
                        <FontAwesomeIcon :icon="faArrowLeft" class="h-4 w-4" />
                        <span class="hidden sm:inline">Volver a Clientes</span>
                        <span class="sm:hidden">Volver</span>
                    </Link>
                </div>
            </div>

            <!-- Tarjetas de resumen -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-3">
                    <div class="flex flex-col items-center text-center">
                        <div class="p-1.5 rounded-full bg-blue-100 mb-2">
                            <FontAwesomeIcon :icon="faShoppingCart" class="h-4 w-4 text-blue-600" />
                        </div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Total</p>
                        <p class="text-lg font-bold text-gray-900">{{ ventas.length }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-3">
                    <div class="flex flex-col items-center text-center">
                        <div class="p-1.5 rounded-full bg-green-100 mb-2">
                            <FontAwesomeIcon :icon="faDollarSign" class="h-4 w-4 text-green-600" />
                        </div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Monto</p>
                        <p class="text-lg font-bold text-gray-900">${{ totalVentas.toFixed(0) }}</p>
                    </div>
                </div>



                <div class="bg-white rounded-lg shadow-md p-3">
                    <div class="flex flex-col items-center text-center">
                        <div class="p-1.5 rounded-full bg-green-100 mb-2">
                            <FontAwesomeIcon :icon="faCheckCircle" class="h-4 w-4 text-green-600" />
                        </div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Completadas</p>
                        <p class="text-lg font-bold text-gray-900">{{ ventasCompletadas }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de ventas -->
            <div class="bg-white rounded-lg shadow-md">
                    <h3 class="text-2xl text-blue-600 font-bold mb-4">Historial de Ventas</h3>

                    <DataTable
                        :value="filteredVentas"
                        dataKey="id"
                        :paginator="true"
                        :rows="rowsPerPage"
                        :rowsPerPageOptions="rowsPerPageOptions"
                        v-model:rowsPerPage="rowsPerPage"
                        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} ventas"
                        class="overflow-x-auto max-w-full"
                        responsiveLayout="scroll"
                        @row-click="onRowClick"
                        :pt="{
                            root: { class: 'text-sm' },
                            wrapper: { class: 'text-sm' },
                            table: { class: 'text-sm' },
                            thead: { class: 'text-sm' },
                            headerRow: { class: 'text-sm' },
                            headerCell: { class: 'text-sm font-medium py-3 px-2' },
                            tbody: { class: 'text-sm' },
                            bodyRow: { class: 'h-16 text-sm hover:bg-blue-50 transition-colors duration-200 cursor-pointer' },
                            bodyCell: { class: 'py-3 px-2 text-sm' },
                            paginator: { class: 'text-xs sm:text-sm' },
                            paginatorWrapper: { class: 'flex flex-wrap justify-center sm:justify-between items-center gap-2 p-2' }
                        }"
                    >
                        <template #header>
                            <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4">
                                <div class="flex flex-col sm:flex-row items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-base font-medium text-gray-800 flex items-center gap-2">
                                            <i class="pi pi-filter text-blue-600 text-sm"></i><span>Filtros</span>
                                        </h3>
                                        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                                            {{ filteredVentas.length }} resultado{{ filteredVentas.length !== 1 ? 's' : '' }}
                                        </div>
                                        <button
                                            class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md flex sm:hidden disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                                            @click="clearFilters"
                                            :disabled="isClearingFilters">
                                            <FontAwesomeIcon
                                                v-if="isClearingFilters"
                                                :icon="faSpinner"
                                                class="animate-spin h-3 w-3"
                                            />
                                            <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
                                        </button>
                                    </div>
                                    <button
                                        class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md hidden sm:flex disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                                        @click="clearFilters"
                                        :disabled="isClearingFilters">
                                        <FontAwesomeIcon
                                            v-if="isClearingFilters"
                                            :icon="faSpinner"
                                            class="animate-spin h-3 w-3"
                                        />
                                        <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <InputText v-model="filters['global'].value" placeholder="🔍 Buscar ventas..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                                        <Select
                                            v-model="estadoFilter"
                                            :options="estadoOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Filtrar por estado"
                                            class="w-full text-sm border border-blue-300"
                                            :pt="{
                                                root: { class: 'h-9' },
                                                input: { class: 'text-sm' },
                                                dropdown: { class: 'text-sm' }
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <Column field="id" header="ID" sortable class="w-16">
                            <template #body="slotProps">
                                <span class="font-medium text-blue-600">#{{ slotProps.data.id }}</span>
                            </template>
                        </Column>

                        <Column field="fecha" header="Fecha" sortable class="w-28">
                            <template #body="slotProps">
                                <span class="text-sm">{{ formatDate(slotProps.data.fecha) }}</span>
                            </template>
                        </Column>

                        <Column field="total" header="Total" sortable class="w-24">
                            <template #body="slotProps">
                                <span class="text-sm font-semibold text-green-600">
                                    ${{ Number(slotProps.data.total).toFixed(2) }}
                                </span>
                            </template>
                        </Column>



                    </DataTable>
                </div>
            </div>

        <!-- Dialog de detalles de la venta -->
        <Dialog
            v-model:visible="showDetalleDialog"
            header="Detalles de la Venta"
            :modal="true"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :closable="true"
            :draggable="false"
        >
            <div v-if="selectedVenta" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Información General</h4>
                        <div class="space-y-2">
                            <p><strong>ID de Venta:</strong> #{{ selectedVenta.id }}</p>
                            <p><strong>Fecha:</strong> {{ formatDate(selectedVenta.fecha) }}</p>
                            <p><strong>Estado:</strong>
                                <span :class="getEstadoClass(selectedVenta.estado)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2">
                                    {{ getEstadoLabel(selectedVenta.estado) }}
                                </span>
                            </p>
                            <p><strong>Total:</strong>
                                <span class="text-lg font-semibold text-green-600">${{ Number(selectedVenta.total).toFixed(2) }}</span>
                            </p>
                        </div>
                    </div>




                </div>
            </div>

            <template #footer>
                <div class="flex justify-center w-full">
                    <button
                        type="button"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="closeDetalleDialog"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        Cerrar
                    </button>
                </div>
            </template>
        </Dialog>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faUser,
    faIdCard,
    faArrowLeft,
    faShoppingCart,
    faDollarSign,
    faClock,
    faCheckCircle,
    faXmark,
    faSpinner
} from "@fortawesome/free-solid-svg-icons";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';

// Props
const props = defineProps({
    cliente: {
        type: Object,
        required: true
    },
    ventas: {
        type: Array,
        default: () => []
    }
});

const toast = useToast();

// Estados reactivos
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const estadoFilter = ref('');
const selectedVenta = ref(null);
const showDetalleDialog = ref(false);
const isClearingFilters = ref(false);

const estadoOptions = ref([
    { label: 'Todos', value: null },
    { label: 'Completadas', value: 'completada' },
    { label: 'Canceladas', value: 'cancelada' }
]);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Computed properties
const filteredVentas = computed(() => {
    let result = props.ventas;

    // Filtro por estado
    if (estadoFilter.value) {
        result = result.filter(venta => venta.estado === estadoFilter.value);
    }

    // Filtro global de búsqueda
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        result = result.filter(venta =>
            venta.id.toString().includes(searchTerm)
        );
    }

    return result;
});

const totalVentas = computed(() => {
    return props.ventas.reduce((sum, venta) => sum + parseFloat(venta.total), 0);
});


const ventasCompletadas = computed(() => {
    return props.ventas.filter(venta => venta.estado === 'completada').length;
});

// Funciones auxiliares
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const getEstadoClass = (estado) => {
    switch (estado) {
        case 'completada':
            return 'bg-green-100 text-green-800';
        case 'cancelada':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getEstadoLabel = (estado) => {
    switch (estado) {
        case 'completada':
            return 'Completada';
        case 'cancelada':
            return 'Cancelada';
        default:
            return estado;
    }
};

const onRowClick = (event) => {
    selectedVenta.value = event.data;
    showDetalleDialog.value = true;
};

const closeDetalleDialog = () => {
    showDetalleDialog.value = false;
    selectedVenta.value = null;
};

// Función para limpiar filtros
const clearFilters = async () => {
    isClearingFilters.value = true;

    // Simular tiempo de procesamiento
    await new Promise(resolve => setTimeout(resolve, 300));

    estadoFilter.value = '';

    isClearingFilters.value = false;

    toast.add({
        severity: 'success',
        summary: 'Filtros limpiados',
        detail: 'Los filtros han sido limpiados exitosamente',
        life: 3000
    });
};

// Lifecycle
onMounted(() => {
    toast.add({
        severity: "success",
        summary: "Ventas cargadas",
        detail: `Se encontraron ${props.ventas.length} ventas para este cliente.`,
        life: 3000
    });
});
</script>


<style>
/* Estilos para el dropdown del Select de PrimeVue */
.p-select-overlay {
    border: 2px solid #9ca3af !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

.p-select-option {
    border-bottom: 1px solid #e5e7eb !important;
    padding: 8px 12px !important;
    transition: background-color 0.2s ease !important;
}

.p-select-option:last-child {
    border-bottom: none !important;
}

.p-select-option:hover {
    background-color: #f3f4f6 !important;
}

.p-select-option[aria-selected="true"] {
    background-color: #dbeafe !important;
    color: #1e40af !important;
}
/* Fin de los estilos para el select */


/* Estilos para el paginador */
.p-paginator-current {
  display: none !important;
}

@media (min-width: 640px) {
  .p-paginator-current {
    display: inline !important;
  }
  .p-paginator {
    justify-content: center !important;
  }
}
/* Fin de los estilos para el paginador */

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
/* Fin de la animación para el spinner de loading */
</style>
