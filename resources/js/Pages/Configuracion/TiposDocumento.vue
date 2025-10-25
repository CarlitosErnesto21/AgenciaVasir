<template>
    <AuthenticatedLayout>
        <Head title="Tipos de Documento" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Tipos de Documento</h1>
                <p class="text-gray-600">Administración completa de tipos de documentos del sistema</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Tipos de Documento</h3>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                            @click="openNew">
                            <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" /><span>&nbsp;Agregar tipo</span>
                        </button>
                    </div>
                </div>

                <DataTable
                    :value="filteredTipos"
                    dataKey="id"
                    :paginator="true"
                    :rows="rowsPerPage"
                    :rowsPerPageOptions="rowsPerPageOptions"
                    v-model:rowsPerPage="rowsPerPage"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tipos de documento"
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
                                        {{ filteredTipos.length }} resultado{{ filteredTipos.length !== 1 ? 's' : '' }}
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
                                    <InputText v-model="filters['global'].value" placeholder="🔍 Buscar tipos de documento..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                                </div>
                            </div>
                        </div>
                    </template>

                    <Column field="nombre" header="Nombre" sortable class="w-64">
                        <template #body="slotProps">
                            <div
                                class="text-sm font-medium leading-relaxed overflow-hidden"
                                style="max-width: 150px; text-overflow: ellipsis; white-space: nowrap;"
                                :title="slotProps.data.nombre"
                            >
                                {{ slotProps.data.nombre }}
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
                                   class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="editTipo(slotProps.data)">
                                    <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Editar</span>
                                </button>
                                <button
                                    class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="confirmDeleteTipo(slotProps.data)">
                                    <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Eliminar</span>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>


            </div>

            <!--Modal de formulario -->
            <Dialog
                :visible="showCreateModal || showEditModal"
                @update:visible="(value) => !value && hideDialog()"
                :header="(showCreateModal ? 'Guardar' : 'Actualizar') + ' Tipo de Documento'"
                :modal="true"
                :style="dialogStyle"
                :closable="false"
                :draggable="false"
            >
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model.trim="tipoDocumento.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="20"
                                :class="{'p-invalid': submitted && (!tipoDocumento.nombre || tipoDocumento.nombre.length < 3 || tipoDocumento.nombre.length > 20)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md uppercase"
                                placeholder="DUI, PASAPORTE, ETC."
                                @input="onNombreInput"
                                @paste="onNombrePaste"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="tipoDocumento.nombre && tipoDocumento.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres. Actual: {{ tipoDocumento.nombre.length }}/3
                        </small>
                        <small class="text-orange-500 ml-28" v-if="tipoDocumento.nombre && tipoDocumento.nombre.length >= 18 && tipoDocumento.nombre.length <= 20">
                            Caracteres restantes: {{ 20 - tipoDocumento.nombre.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !tipoDocumento.nombre">
                            El nombre es obligatorio.
                        </small>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-center gap-4 w-full mt-6">
                        <button
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="showCreateModal ? createTipo() : updateTipo()"
                            :disabled="isLoading"
                        >
                            <FontAwesomeIcon
                                :icon="isLoading ? faSpinner : faCheck"
                                :class="[
                                    'h-5 text-white',
                                    { 'animate-spin': isLoading }
                                ]"
                            />
                            <span v-if="!isLoading">{{ showCreateModal ? 'Guardar' : 'Actualizar' }}</span>
                            <span v-else>{{ showCreateModal ? 'Guardando...' : 'Actualizando...' }}</span>
                        </button>
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="hideDialog" :disabled="isLoading">
                            <FontAwesomeIcon :icon="faXmark" class="h-5" />Cancelar
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- Modal de Eliminación -->
            <Dialog
                v-model:visible="deleteDialog"
                header="Eliminar tipo de documento"
                :modal="true"
                :style="dialogStyle"
                :closable="false"
                :draggable="false"
            >
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar el tipo de documento: <b>{{ selectedTipo?.nombre }}</b>?</span>
                        <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="deleteTipo"
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
                            @click="deleteDialog = false" :disabled="isDeleting">
                            <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cancelar</span>
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- Modal de Cambios sin guardar -->
            <Dialog
                v-model:visible="unsavedChangesDialog"
                header="Cambios sin guardar"
                :modal="true"
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
                            @click="closeDialogWithoutSaving">
                            <FontAwesomeIcon :icon="faSignOut" class="h-4" /><span>Salir sin guardar</span>
                        </button>
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="continueEditing">
                            <FontAwesomeIcon :icon="faPencil" class="h-4" /><span>Continuar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { useToast } from 'primevue/usetoast';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faIdCard, faPlus, faPencil, faTrashCan, faSpinner, faCheck, faXmark, faExclamationTriangle, faSignOut } from '@fortawesome/free-solid-svg-icons';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import axios from 'axios';

const props = defineProps({
    tipoDocumentos: {
        type: Array,
        default: () => []
    }
});

const toast = useToast();

// =====================================================
// ✅ VARIABLES DE ESTADO PRINCIPALES
// =====================================================
const tipoDocumento = reactive({
    id: null,
    nombre: ''
});

const selectedTipo = ref(null);
const tipos = ref(props.tipoDocumentos || []);

// =====================================================
// ✅ VARIABLES DE CONTROL DE MODALES - IGUAL QUE PRODUCTOS
// =====================================================
const isLoading = ref(false);
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const isLoadingTable = ref(true);
const submitted = ref(false);
const deleteDialog = ref(false);
const unsavedChangesDialog = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingTipo = ref(null);
const hasUnsavedChanges = ref(false);
const originalTipoData = ref(null);

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
const filteredTipos = computed(() => {
    let result = tipos.value;

    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        result = result.filter(tipo =>
            tipo.nombre.toLowerCase().includes(searchTerm)
        );
    }

    return result;
});

// 👀 Watcher para detectar cambios en el modal
watch([tipoDocumento], () => {
    if (originalTipoData.value && (showCreateModal.value || showEditModal.value)) {
        nextTick(() => {
            const current = { ...tipoDocumento };
            const hasChanges = JSON.stringify(current) !== JSON.stringify(originalTipoData.value);
            const isCreatingNew = !originalTipoData.value.id;
            const hasAnyData = tipoDocumento.nombre;
            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true, flush: 'post' });

// =====================================================
// ✅ MÉTODOS PRINCIPALES - SIGUIENDO PATRÓN DE PRODUCTOS
// =====================================================
const openNew = () => {
    Object.assign(tipoDocumento, {
        id: null,
        nombre: ''
    });
    submitted.value = false;
    showCreateModal.value = true;
    nextTick(() => {
        originalTipoData.value = { ...tipoDocumento };
        hasUnsavedChanges.value = false;
    });
};

const onRowClick = (event) => {
    selectedTipo.value = event.data;
};

const editTipo = (tipo) => {
    editingTipo.value = tipo;
    Object.assign(tipoDocumento, {
        id: tipo.id,
        nombre: tipo.nombre
    });
    submitted.value = false;
    showEditModal.value = true;
    nextTick(() => {
        originalTipoData.value = { ...tipoDocumento };
        hasUnsavedChanges.value = false;
    });
};

const confirmDeleteTipo = (tipo) => {
    selectedTipo.value = tipo;
    deleteDialog.value = true;
};

const deleteTipo = async () => {
    isDeleting.value = true;

    try {
        const response = await axios.delete(`/api/tipo-documentos/${selectedTipo.value.id}`);

        if (response.data.success) {
            const index = tipos.value.findIndex(t => t.id === selectedTipo.value.id);
            if (index !== -1) {
                tipos.value.splice(index, 1);
            }

            toast.add({
                severity: 'success',
                summary: 'Tipo eliminado',
                detail: 'El tipo de documento ha sido eliminado correctamente',
                life: 3000
            });

            deleteDialog.value = false;
            selectedTipo.value = null;
        }
    } catch (error) {
        console.error('Error al eliminar tipo:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al eliminar el tipo de documento',
            life: 3000
        });
    } finally {
        isDeleting.value = false;
    }
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


// =====================================================
// ✅ MÉTODOS CRUD - CREAR Y ACTUALIZAR
// =====================================================
const createTipo = async () => {
    submitted.value = true;

    // Validar nombre específicamente
    if (!tipoDocumento.nombre || tipoDocumento.nombre.length < 3 || tipoDocumento.nombre.length > 20) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que el nombre esté completo y cumpla los requisitos.",
            life: 4000
        });
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.post('/api/tipo-documentos', {
            nombre: tipoDocumento.nombre.trim()
        });

        if (response.data.success) {
            tipos.value.push(response.data.tipo_documento);
            hasUnsavedChanges.value = false;
            originalTipoData.value = null;
            closeModals();
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Tipo de documento creado exitosamente',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al crear tipo:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al crear el tipo de documento',
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const updateTipo = async () => {
    submitted.value = true;

    // Validar nombre específicamente
    if (!tipoDocumento.nombre || tipoDocumento.nombre.length < 3 || tipoDocumento.nombre.length > 20 || !editingTipo.value) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que el nombre esté completo y cumpla los requisitos.",
            life: 4000
        });
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.put(`/api/tipo-documentos/${editingTipo.value.id}`, {
            nombre: tipoDocumento.nombre.trim()
        });

        if (response.data.success) {
            const index = tipos.value.findIndex(t => t.id === editingTipo.value.id);
            if (index !== -1) {
                tipos.value[index] = response.data.tipo_documento;
            }
            hasUnsavedChanges.value = false;
            originalTipoData.value = null;
            closeModals();
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Tipo de documento actualizado exitosamente',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al actualizar tipo:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al actualizar el tipo de documento',
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const closeModals = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingTipo.value = null;
    isLoading.value = false;
    submitted.value = false;
    hasUnsavedChanges.value = false;
    originalTipoData.value = null;
    Object.assign(tipoDocumento, {
        id: null,
        nombre: ''
    });
};

// 🚪 Función para cerrar modal con verificación de cambios
const hideDialog = () => {
    if (hasUnsavedChanges.value) {
        unsavedChangesDialog.value = true;
    } else {
        closeDialogWithoutSaving();
    }
};

// =====================================================
// ✅ MÉTODOS DE CONTROL DE CAMBIOS NO GUARDADOS
// =====================================================
const closeDialogWithoutSaving = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    unsavedChangesDialog.value = false;
    hasUnsavedChanges.value = false;
    originalTipoData.value = null;
    editingTipo.value = null;
    Object.assign(tipoDocumento, {
        id: null,
        nombre: ''
    });
    submitted.value = false;
};const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// =====================================================
// ✅ VALIDACIONES EN TIEMPO REAL - IGUAL QUE PRODUCTOS
// =====================================================
const validateNombre = () => {
    if (tipoDocumento.nombre && tipoDocumento.nombre.length > 20) {
        tipoDocumento.nombre = tipoDocumento.nombre.substring(0, 20);
    }
};

// Función para manejar input en tiempo real con toast informativo
const onNombreInput = (event) => {
    const value = event.target.value;
    const invalidChars = value.match(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g);

    if (invalidChars && invalidChars.length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Caracteres no permitidos',
            detail: 'Solo se permiten letras, espacios y acentos. Se han removido los caracteres no válidos.',
            life: 3000
        });
    }

    // Limpiar caracteres no válidos y convertir a mayúsculas
    const cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '').toUpperCase();
    event.target.value = cleanValue;
    tipoDocumento.nombre = cleanValue;

    validateNombre();
};

// Función para manejar paste
const onNombrePaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');

    // Limpiar el texto pegado y convertir a mayúsculas
    const cleanPaste = paste
        .replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '') // Solo letras, espacios y acentos
        .replace(/\s+/g, ' ') // Múltiples espacios a uno solo
        .trim() // Eliminar espacios al inicio y final
        .toUpperCase(); // Convertir a mayúsculas

    if (cleanPaste !== paste.toUpperCase()) {
        toast.add({
            severity: 'info',
            summary: 'Texto limpiado',
            detail: 'Se han removido números y caracteres especiales del texto pegado y convertido a mayúsculas.',
            life: 3000
        });
    }

    // Limitar a 20 caracteres
    const limitedPaste = cleanPaste.length > 20 ? cleanPaste.substring(0, 20) : cleanPaste;

    // Actualizar el modelo
    tipoDocumento.nombre = limitedPaste;
    event.target.value = limitedPaste;
};// =====================================================
// ✅ MÉTODOS DE UTILIDAD
// =====================================================
const formatDate = (dateString) => {
    if (!dateString) return 'No disponible';
    return new Date(dateString).toLocaleDateString('es-ES');
};

const loadTipos = async () => {
    try {
        const response = await axios.get('/api/tipo-documentos');
        if (response.data.success) {
            tipos.value = response.data.tipos;
        }
    } catch (error) {
        console.error('Error loading tipos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los tipos de documento',
            life: 3000
        });
    }
};

const loadTiposWithToasts = async () => {
    isLoadingTable.value = true;

    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando tipos de documento...",
        life: 2000
    });

    try {
        const response = await axios.get('/api/tipo-documentos');
        if (response.data.success) {
            tipos.value = response.data.tipos;
        }

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Tipos de documento cargados",
            life: 2000
        });

    } catch (error) {
        console.error('Error loading tipos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los tipos de documento',
            life: 4000
        });
    } finally {
        isLoadingTable.value = false;
    }
};

// =====================================================
// ✅ INICIALIZACIÓN
// =====================================================
onMounted(() => {
    console.log('Tipos de documento cargados:', tipos.value);
    if (tipos.value.length === 0) {
        loadTiposWithToasts();
    } else {
        isLoadingTable.value = false;
    }
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
