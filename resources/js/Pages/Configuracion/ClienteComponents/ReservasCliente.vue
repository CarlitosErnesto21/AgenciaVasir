<template>
    <AuthenticatedLayout>
        <Head :title="`Reservas de ${cliente?.user?.name || 'Cliente'}`" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <!-- Header con información del cliente -->
            <div class="mb-6 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-blue-600 mb-2">Reservas del Cliente</h1>

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
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faArrowLeft" class="h-4 w-4" />
                        Volver a Clientes
                    </Link>
                </div>
            </div>

            <!-- Tarjetas de resumen -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <FontAwesomeIcon :icon="faCalendarDays" class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Reservas</p>
                            <p class="text-2xl font-bold text-gray-900">{{ reservas.length }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <FontAwesomeIcon :icon="faDollarSign" class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Monto Total</p>
                            <p class="text-2xl font-bold text-gray-900">${{ totalReservas.toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100">
                            <FontAwesomeIcon :icon="faClock" class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pendientes</p>
                            <p class="text-2xl font-bold text-gray-900">{{ reservasPendientes }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <FontAwesomeIcon :icon="faCheckCircle" class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Confirmadas</p>
                            <p class="text-2xl font-bold text-gray-900">{{ reservasConfirmadas }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <FontAwesomeIcon :icon="faUsers" class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Personas Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ totalPersonas }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de reservas -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <h3 class="text-2xl text-blue-600 font-bold mb-4">Historial de Reservas</h3>

                    <DataTable
                        :value="filteredReservas"
                        dataKey="id"
                        :paginator="true"
                        :rows="rowsPerPage"
                        :rowsPerPageOptions="rowsPerPageOptions"
                        v-model:rowsPerPage="rowsPerPage"
                        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reservas"
                        class="overflow-x-auto max-w-full"
                        responsiveLayout="scroll"
                        :pt="{
                            root: { class: 'text-sm' },
                            wrapper: { class: 'text-sm' },
                            table: { class: 'text-sm' },
                            thead: { class: 'text-sm' },
                            headerRow: { class: 'text-sm' },
                            headerCell: { class: 'text-sm font-medium py-3 px-2' },
                            tbody: { class: 'text-sm' },
                            bodyRow: { class: 'h-16 text-sm hover:bg-blue-50 transition-colors duration-200' },
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
                                            {{ filteredReservas.length }} resultado{{ filteredReservas.length !== 1 ? 's' : '' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <InputText v-model="filters['global'].value" placeholder="🔍 Buscar reservas..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
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

                        <Column field="estado" header="Estado" class="w-32">
                            <template #body="slotProps">
                                <span :class="getEstadoClass(slotProps.data.estado)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                                    {{ getEstadoLabel(slotProps.data.estado) }}
                                </span>
                            </template>
                        </Column>

                        <Column field="mayores_edad" header="Adultos" class="w-20">
                            <template #body="slotProps">
                                <span class="text-sm">{{ slotProps.data.mayores_edad }}</span>
                            </template>
                        </Column>

                        <Column field="menores_edad" header="Niños" class="w-20">
                            <template #body="slotProps">
                                <span class="text-sm">{{ slotProps.data.menores_edad || 0 }}</span>
                            </template>
                        </Column>

                        <Column field="total" header="Total" sortable class="w-24">
                            <template #body="slotProps">
                                <span class="text-sm font-semibold text-green-600">
                                    ${{ Number(slotProps.data.total).toFixed(2) }}
                                </span>
                            </template>
                        </Column>

                        <Column field="empleado.user.name" header="Agente" class="w-32 hidden lg:table-cell">
                            <template #body="slotProps">
                                <span class="text-sm">{{ slotProps.data.empleado?.user?.name || 'N/A' }}</span>
                            </template>
                        </Column>

                        <Column header="Acciones" class="w-24">
                            <template #body="slotProps">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-md text-xs transition-all duration-200"
                                        @click="verDetalles(slotProps.data)"
                                        title="Ver detalles"
                                    >
                                        <FontAwesomeIcon :icon="faEye" class="h-3 w-3" />
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
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
    faCalendarDays,
    faDollarSign,
    faClock,
    faCheckCircle,
    faUsers,
    faEye
} from "@fortawesome/free-solid-svg-icons";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';

// Props
const props = defineProps({
    cliente: {
        type: Object,
        required: true
    },
    reservas: {
        type: Array,
        default: () => []
    }
});

const toast = useToast();

// Estados reactivos
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Computed properties
const filteredReservas = computed(() => {
    let result = props.reservas;

    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        result = result.filter(reserva =>
            reserva.id.toString().includes(searchTerm) ||
            reserva.estado.toLowerCase().includes(searchTerm) ||
            (reserva.empleado?.user?.name && reserva.empleado.user.name.toLowerCase().includes(searchTerm))
        );
    }

    return result;
});

const totalReservas = computed(() => {
    return props.reservas.reduce((sum, reserva) => sum + parseFloat(reserva.total), 0);
});

const reservasPendientes = computed(() => {
    return props.reservas.filter(reserva => reserva.estado === 'PENDIENTE').length;
});

const reservasConfirmadas = computed(() => {
    return props.reservas.filter(reserva => reserva.estado === 'CONFIRMADA').length;
});

const totalPersonas = computed(() => {
    return props.reservas.reduce((sum, reserva) =>
        sum + reserva.mayores_edad + (reserva.menores_edad || 0), 0
    );
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
        case 'CONFIRMADA':
            return 'bg-green-100 text-green-800';
        case 'PENDIENTE':
            return 'bg-yellow-100 text-yellow-800';
        case 'RECHAZADA':
            return 'bg-red-100 text-red-800';
        case 'REPROGRAMADA':
            return 'bg-blue-100 text-blue-800';
        case 'FINALIZADA':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getEstadoLabel = (estado) => {
    switch (estado) {
        case 'CONFIRMADA':
            return 'Confirmada';
        case 'PENDIENTE':
            return 'Pendiente';
        case 'RECHAZADA':
            return 'Rechazada';
        case 'REPROGRAMADA':
            return 'Reprogramada';
        case 'FINALIZADA':
            return 'Finalizada';
        default:
            return estado;
    }
};

const verDetalles = (reserva) => {
    toast.add({
        severity: 'info',
        summary: 'Ver Detalles',
        detail: `Funcionalidad de detalles de reserva #${reserva.id} pendiente de implementar.`,
        life: 4000
    });
};

// Lifecycle
onMounted(() => {
    toast.add({
        severity: "success",
        summary: "Reservas cargadas",
        detail: `Se encontraron ${props.reservas.length} reservas para este cliente.`,
        life: 3000
    });
});
</script>
