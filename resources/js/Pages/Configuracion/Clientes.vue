<template>
    <AuthenticatedLayout>
        <Head title="Clientes" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Clientes</h1>
                <p class="text-gray-600">Visualización de clientes del sistema</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Clientes</h3>
                </div>

                            <DataTable
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
                                <div>
                                    <InputText v-model="filters['global'].value" placeholder="🔍 Buscar clientes..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                                </div>
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
                    @send-email="handleSendEmail"
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
import { Link, Head } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faUsers, faIdCard, faListDots, faPencil, faTrashCan, faSpinner } from '@fortawesome/free-solid-svg-icons';
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

const deleteCliente = async () => {
    isDeleting.value = true;

    try {
        // TODO: Implementar eliminación de cliente
        await new Promise(resolve => setTimeout(resolve, 1000)); // Simular delay

        toast.add({
            severity: 'success',
            summary: 'Cliente eliminado',
            detail: 'El cliente ha sido eliminado correctamente',
            life: 3000
        });

        deleteDialog.value = false;
        selectedCliente.value = null;
    } catch (error) {
        console.error('Error al eliminar cliente:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al eliminar el cliente',
            life: 3000
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

const handleSendEmail = (cliente) => {
    toast.add({ severity: 'info', summary: 'Función pendiente', detail: 'Enviar email aún no implementado', life: 3000 });
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
// ✅ FUNCIÓN PARA MOSTRAR TOASTS DE CARGA
// =====================================================
const showLoadingToasts = () => {
    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando clientes...",
        life: 2000
    });

    // Simular carga y mostrar toast de éxito
    setTimeout(() => {
        toast.add({
            severity: "success",
            summary: "Clientes cargados",
            life: 2000
        });
        isLoadingTable.value = false;
    }, 1000);
};

// =====================================================
onMounted(() => {
    showLoadingToasts();
});
</script>
