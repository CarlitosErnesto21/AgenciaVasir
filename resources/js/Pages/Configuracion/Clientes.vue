<template>
    <AuthenticatedLayout>
        <Head title="Clientes" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center text-center sm:text-left gap-4 p-4">
                    <div class="w-full">
                        <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Clientes</h1>
                        <p class="text-gray-600">Visualización de la información de los clientes del sistema</p>
                    </div>
                </div>

                <!-- Loading independiente -->
                <div v-if="isLoadingTable" class="flex flex-col items-center justify-center py-12">
                    <FontAwesomeIcon :icon="faSpinner" class="animate-spin h-8 w-8 text-blue-600 mb-3" />
                    <p class="text-gray-600 font-medium">Cargando clientes...</p>
                </div>

                <DataTable
                v-else
                :value="filteredClientes"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} clientes"
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
                    bodyRow: { class: 'h-20 text-sm cursor-pointer hover:bg-blue-50 transition-colors duration-200' },
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
                                        {{ filteredClientes.length }} resultado{{ filteredClientes.length !== 1 ? 's' : '' }}
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
                                        <span>{{ isClearingFilters ? 'Limpia...' : 'Limpiar' }}</span>
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
                                <div class="relative">
                                    <FontAwesomeIcon :icon="faMagnifyingGlass" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                    <InputText v-model="filters['global'].value" placeholder="Buscar clientes..." class="w-full h-9 text-sm rounded-md pl-10" style="background-color: white; border-color: #93c5fd;"/>
                                </div>
                                <p class="text-blue-600 text-xs font-medium flex items-center gap-1">
                                    <FontAwesomeIcon :icon="faHandPointUp" class="h-3 w-3 text-yellow-500" />
                                    Haz clic en cualquier fila para ver los detalles del cliente.
                                </p>
                            </div>
                        </div>
                    </template>

                    <Column field="user.name" header="Nombre Completo" sortable class="w-40">
                        <template #body="slotProps">
                            <div
                                class="text-sm font-medium leading-relaxed overflow-hidden"
                                style="max-width: 150px; text-overflow: ellipsis; white-space: nowrap;"
                                :title="slotProps.data.user?.name || 'Sin nombre'"
                            >
                                {{ slotProps.data.user?.name || 'Sin nombre' }}
                            </div>
                        </template>
                    </Column>

                    <Column field="numero_identificacion" header="Identificación" class="w-32 hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-sm leading-relaxed">
                                {{ slotProps.data.numero_identificacion }}
                            </div>
                        </template>
                    </Column>

                    <Column field="telefono" header="Teléfono" class="w-32 hidden sm:table-cell">
                        <template #body="slotProps">
                            <div class="text-sm leading-relaxed">
                                {{ slotProps.data.telefono }}
                            </div>
                        </template>
                    </Column>

                    <Column field="email" header="Email" class="w-16 hidden sm:table-cell">
                        <template #body="slotProps">
                            <div
                                class="text-sm font-medium leading-relaxed overflow-hidden"
                                style="max-width: 150px; text-overflow: ellipsis; white-space: nowrap;"
                                :title="slotProps.data.email || (slotProps.data.user && slotProps.data.user.email) || 'No registrado'"
                            >
                                {{ slotProps.data.email || (slotProps.data.user && slotProps.data.user.email) || 'No registrado' }}
                            </div>
                        </template>
                    </Column>

                    <Column :exportable="false" class="w-52 min-w-36">
                        <template #header>
                            <div class="text-center w-full font-bold">
                                Acciones
                            </div>
                        </template>
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center items-center">
                                <button
                                    class="flex bg-green-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="openMoreActionsModal(slotProps.data)">
                                    <FontAwesomeIcon :icon="faListDots" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Más</span>
                                </button>
                                <button
                                    class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="confirmDeleteCliente(slotProps.data)">
                                    <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Eliminar</span>
                                </button>
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <FontAwesomeIcon :icon="faHandPointUp" class="h-16 w-16 text-gray-400 mb-4" />
                            <p class="text-xl font-semibold text-gray-600 mb-2">NO HAY CLIENTES REGISTRADOS</p>
                            <p class="text-gray-500 mb-6">Los clientes aparecerán aquí cuando se registren en el sistema</p>
                        </div>
                    </template>
                </DataTable>

                <!-- Componente de Modales de Clientes -->
                <ClienteModals
                    v-model:visible="moreActionsDialog"
                    v-model:delete-visible="deleteDialog"
                    v-model:unsaved-changes-visible="unsavedChangesDialog"
                    v-model:detalles-visible="showDetallesModal"
                    :cliente="selectedCliente || cliente"
                    :dialog-style="dialogStyle"
                    :is-deleting="isDeleting"
                    :is-loading="isLoading"
                    :submitted="submitted"
                    @view-details="handleViewDetails"
                    @view-reservations="handleViewReservations"
                    @view-purchases="handleViewPurchases"
                    @view-reports="handleViewReports"
                    @toggle-status="handleToggleStatus"
                    @delete-cliente="deleteCliente"
                    @cancel-delete="cancelDelete"
                    @close-without-saving="closeDialogWithoutSaving"
                    @continue-editing="continueEditing"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faUsers, faIdCard, faListDots, faPencil, faTrashCan, faSpinner, faHandPointUp, faMagnifyingGlass } from '@fortawesome/free-solid-svg-icons';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import ClienteModals from './ClienteComponents/Modales.vue';

const props = defineProps({
    clientes: {
        type: Array,
        default: () => []
    }
});

const toast = useToast();

// =====================================================
// ✅ VARIABLES DE ESTADO PRINCIPALES
// =====================================================
const cliente = reactive({
    id: null,
    nombre: '',
    apellido: '',
    genero: '',
    fecha_nacimiento: '',
    tipo_documento_id: null,
    numero_identificacion: '',
    telefono: '',
    email: '',
    direccion: ''
});

const selectedCliente = ref(null);

// =====================================================
// ✅ VARIABLES DE CONTROL DE MODALES - IGUAL QUE PRODUCTOS
// =====================================================
const isLoading = ref(false);
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const submitted = ref(false);
const isLoadingTable = ref(true);
const showDetallesModal = ref(false);
const moreActionsDialog = ref(false);
const deleteDialog = ref(false);
const unsavedChangesDialog = ref(false);

// =====================================================
// ✅ VARIABLES DE PAGINACIÓN Y FILTROS - IGUAL QUE PRODUCTOS
// =====================================================
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);

const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// =====================================================
// ✅ VARIABLES DE RESPONSIVIDAD - IGUAL QUE PRODUCTOS
// =====================================================
const dialogStyle = computed(() => ({
    width: '95vw',
    maxWidth: '500px',
    maxHeight: '95vh'
}));

// =====================================================
// ✅ FILTROS COMPUTADOS
// =====================================================
const filteredClientes = computed(() => {
    let result = props.clientes;

    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        result = result.filter(cliente =>
            (cliente.user && cliente.user.name && cliente.user.name.toLowerCase().includes(searchTerm)) ||
            (cliente.numero_identificacion && cliente.numero_identificacion.toLowerCase().includes(searchTerm)) ||
            (cliente.user && cliente.user.email && cliente.user.email.toLowerCase().includes(searchTerm)) ||
            (cliente.telefono && cliente.telefono.toLowerCase().includes(searchTerm))
        );
    }

    return result;
});

// =====================================================
// ✅ MÉTODOS PRINCIPALES - SIGUIENDO PATRÓN DE PRODUCTOS
// =====================================================
const openMoreActionsModal = (cliente) => {
    selectedCliente.value = cliente;
    moreActionsDialog.value = true;
};

const editCliente = (cliente) => {
    // TODO: Implementar editar cliente
    toast.add({ severity: 'info', summary: 'Función pendiente', detail: 'Editar cliente aún no implementado', life: 3000 });
};

const confirmDeleteCliente = (cliente) => {
    selectedCliente.value = cliente;
    deleteDialog.value = true;
};

const deleteCliente = async (deletionReason) => {
    isDeleting.value = true;

    try {
        // Usar cliente_id si existe, sino usar user_id
        const identificador = selectedCliente.value.id || selectedCliente.value.user_id;

        const response = await fetch(`/api/clientes/${identificador}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                deletion_reason: deletionReason
            })
        });

        const result = await response.json();

        if (response.ok) {
            const reservasCount = result.info?.reservas_count || 0;
            const ventasCount = result.info?.ventas_count || 0;
            const pagosReservasCount = result.info?.pagos_reservas_count || 0;
            const pagosVentasCount = result.info?.pagos_ventas_count || 0;
            const totalPagosCount = result.info?.total_pagos_count || 0;
            const stockReservationsCount = result.info?.stock_reservations_count || 0;

            let detailMessage = `Usuario y cliente eliminados completamente. Se eliminaron:\n`;
            detailMessage += `• Cuenta de usuario completa\n`;
            detailMessage += `• Datos del cliente\n`;
            detailMessage += `• ${reservasCount} reserva(s)\n`;
            detailMessage += `• ${ventasCount} venta(s)\n`;

            if (totalPagosCount > 0) {
                detailMessage += `• ${totalPagosCount} pago(s) total`;
                if (pagosReservasCount > 0 && pagosVentasCount > 0) {
                    detailMessage += ` (${pagosReservasCount} de reservas, ${pagosVentasCount} de ventas)`;
                } else if (pagosReservasCount > 0) {
                    detailMessage += ` (todos de reservas)`;
                } else if (pagosVentasCount > 0) {
                    detailMessage += ` (todos de ventas)`;
                }
            } else {
                detailMessage += `• Sin pagos asociados`;
            }

            if (stockReservationsCount > 0) {
                detailMessage += `\n• ${stockReservationsCount} reserva(s) de stock`;
            }

            toast.add({
                severity: 'success',
                summary: 'Usuario eliminado completamente',
                detail: detailMessage,
                life: 6000
            });

            // Recargar la página para actualizar la lista de clientes
            router.reload();

        } else {
            throw new Error(result.message || 'Error al eliminar el cliente');
        }

        deleteDialog.value = false;
        selectedCliente.value = null;
    } catch (error) {
        console.error('Error al eliminar cliente:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'Error al eliminar el cliente',
            life: 5000
        });
        // Asegurar que el modal se cierre incluso en caso de error
        deleteDialog.value = false;
    } finally {
        isDeleting.value = false;
    }
};

const cancelDelete = () => {
    deleteDialog.value = false;
    selectedCliente.value = null;
    isDeleting.value = false;
};

// =====================================================
// ✅ MÉTODOS DE FILTROS - IGUAL QUE PRODUCTOS
// =====================================================
const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        await new Promise(resolve => setTimeout(resolve, 300));
        filters.value.global.value = null;

        toast.add({
            severity: "success",
            summary: "Filtros limpiados",
            life: 2000
        });
    } finally {
        isClearingFilters.value = false;
    }
};

// =====================================================
// ✅ MÉTODOS DE MODALES DE ACCIONES - SIGUIENDO PATRÓN
// =====================================================
const handleViewDetails = (cliente) => {
    selectedCliente.value = cliente;
    showDetallesModal.value = true;
    moreActionsDialog.value = false;
};

    const handleViewReservations = () => {
        // Funcionalidad de visualizar reservas pendiente de implementar
    };

    const handleViewPurchases = () => {
        // Funcionalidad de visualizar compras pendiente de implementar
    };

    // Función para manejar el clic en la fila
    const onRowClick = (event) => {
        // Verificar si el clic fue en un botón para evitar abrir el modal
        const target = event.originalEvent.target;
        const isButton = target.closest('button');

        if (!isButton) {
            selectedCliente.value = event.data;
            showDetallesModal.value = true;
        }
    };

const handleViewReports = (cliente) => {
    // Navegar a la vista de informes usando Inertia SPA
    router.visit('/generar-informes');
    moreActionsDialog.value = false;
};

const handleToggleStatus = (cliente) => {
    toast.add({ severity: 'info', summary: 'Función pendiente', detail: 'Cambiar estado aún no implementado', life: 3000 });
    moreActionsDialog.value = false;
};

// =====================================================
// ✅ MÉTODOS DE CONTROL DE CAMBIOS NO GUARDADOS
// =====================================================
const closeDialogWithoutSaving = () => {
    // Resetear formulario si existiera
    Object.assign(cliente, {
        id: null,
        nombre: '',
        apellido: '',
        genero: '',
        fecha_nacimiento: '',
        tipo_documento_id: null,
        numero_identificacion: '',
        telefono: '',
        email: '',
        direccion: ''
    });

    submitted.value = false;
    unsavedChangesDialog.value = false;
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// =====================================================
// ✅ INICIALIZACIÓN
// =====================================================
// ✅ FUNCIÓN DE CARGA INICIAL
// =====================================================
const initializeComponent = () => {
    // Simular carga inicial
    setTimeout(() => {
        isLoadingTable.value = false;
    }, 1000);
};

// =====================================================
onMounted(() => {
    initializeComponent();
});
</script>
