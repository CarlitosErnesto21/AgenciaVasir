<template>
    <AuthenticatedLayout>
        <div class="container mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6">
            <!-- Header con navegación -->
            <div class="mb-4 sm:mb-6 mt-1 sm:mt-2">
                <div class="flex items-center">
                    <Link :href="route('settings')"
                        class="flex items-center text-blue-600 hover:text-blue-700 transition-colors duration-200 px-2 py-1 rounded-md hover:bg-blue-50"
                        :disabled="isLoading || isGenerating">
                        <FontAwesomeIcon
                            :icon="(isLoading || isGenerating) ? faSpinner : faArrowLeft"
                            :class="{ 'animate-spin': (isLoading || isGenerating) }"
                            class="h-4 w-4 mr-2"
                        />
                        <span class="text-sm font-medium">
                            {{ (isLoading || isGenerating) ? 'Cargando...' : 'Volver a Configuración' }}
                        </span>
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <!-- Header interno -->
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-3 sm:mb-4 gap-3 sm:gap-4 p-4 sm:p-5 lg:p-6">
                    <div class="w-full">
                        <h3 class="text-xl sm:text-2xl lg:text-3xl text-blue-600 font-bold text-center sm:text-start">Gestión de Respaldos</h3>
                        <p class="text-gray-600 text-center sm:text-start mt-1 text-sm sm:text-base">Administre las copias de seguridad de la base de datos del sistema</p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            @click="generateBackup"
                            :disabled="isGenerating"
                            class="flex bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 border border-blue-500 px-3 sm:px-4 py-2 text-xs sm:text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-all duration-300 items-center justify-center w-full sm:w-auto"
                        >
                            <FontAwesomeIcon
                                :icon="isGenerating ? faSpinner : faDownload"
                                :class="{ 'animate-spin': isGenerating }"
                                class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2 text-white"
                            />
                            <span v-if="isGenerating">Generando...</span>
                            <span v-else class="hidden sm:inline">Generar Respaldo</span>
                            <span v-else class="sm:hidden">Generar</span>
                        </button>
                        <button
                            @click="cleanupBackups"
                            :disabled="isGenerating"
                            class="flex bg-orange-500 hover:bg-orange-600 disabled:bg-gray-400 border border-orange-500 px-3 sm:px-4 py-2 text-xs sm:text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-all duration-300 items-center justify-center w-full sm:w-auto"
                        >
                            <FontAwesomeIcon :icon="faBroom" class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2 text-white" />
                            <span class="hidden sm:inline">Limpiar Antiguos</span>
                            <span class="sm:hidden">Limpiar</span>
                        </button>
                    </div>
                </div>

                <!-- Tabla de respaldos -->
                <DataTable
                    :value="backups"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} respaldos"
                    class="overflow-x-auto max-w-full"
                    responsiveLayout="scroll"
                    :loading="isLoading"
                    :pt="{
                        root: { class: 'text-sm' },
                        wrapper: { class: 'text-sm' },
                        table: { class: 'text-sm' },
                        thead: { class: 'text-sm' },
                        headerRow: { class: 'text-sm' },
                        headerCell: { class: 'text-sm font-medium py-3 px-2' },
                        tbody: { class: 'text-sm' },
                        bodyRow: { class: 'h-20 text-sm hover:bg-blue-50 transition-colors duration-200' },
                        bodyCell: { class: 'py-3 px-2 text-sm' },
                        paginator: { class: 'text-xs sm:text-sm' },
                        paginatorWrapper: { class: 'flex flex-wrap justify-center sm:justify-between items-center gap-2 p-2' }
                    }"
                >
                    <template #empty>
                        <div class="text-center py-8 text-gray-500">
                            <FontAwesomeIcon :icon="faFolderOpen" class="text-4xl mb-2" />
                            <p>No hay respaldos disponibles</p>
                        </div>
                    </template>

                    <Column field="name" header="Nombre del Archivo" sortable class="w-40">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <FontAwesomeIcon :icon="faFileArchive" class="text-blue-600 mr-2 h-4 w-4" />
                                <div
                                    class="text-sm font-medium leading-relaxed overflow-hidden"
                                    style="max-width: 200px; text-overflow: ellipsis; white-space: nowrap;"
                                    :title="slotProps.data.name"
                                >
                                    {{ slotProps.data.name }}
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="date" header="Fecha de Creación" sortable class="w-32 hidden sm:table-cell">
                        <template #body="slotProps">
                            <div class="text-sm leading-relaxed">
                                {{ slotProps.data.date }}
                            </div>
                        </template>
                    </Column>

                    <Column field="size" header="Tamaño" class="w-24 hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-sm leading-relaxed">
                                {{ slotProps.data.size }}
                            </div>
                        </template>
                    </Column>

                    <Column :exportable="false" class="w-32 min-w-28">
                        <template #header>
                            <div class="text-center w-full font-bold">
                                Acciones
                            </div>
                        </template>
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center items-center">
                                <button
                                    @click="downloadBackup(slotProps.data.id)"
                                    class="flex bg-green-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    :title="'Descargar ' + slotProps.data.name"
                                >
                                    <FontAwesomeIcon :icon="faDownload" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Descargar</span>
                                </button>
                                <button
                                    @click="deleteBackup(slotProps.data.id)"
                                    class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    :title="'Eliminar ' + slotProps.data.name"
                                >
                                    <FontAwesomeIcon :icon="faTrash" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Eliminar</span>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Toast para notificaciones -->
        <Toast class="z-[9999]" />

        <!-- Dialog de confirmación personalizado -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Eliminar Respaldo"
            :modal="true"
            :style="dialogStyle"
            :closable="false"
            :draggable="false"
        >
            <div class="flex items-center gap-3">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                <div class="flex flex-col">
                    <span>¿Estás seguro de eliminar el respaldo: <b>{{ selectedBackup?.name }}</b>?</span>
                    <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-center gap-4 w-full">
                    <button
                        type="button"
                        class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="confirmDeleteBackup"
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
                        @click="cancelDeleteBackup"
                        :disabled="isDeleting"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        <span>Cancelar</span>
                    </button>
                </div>
            </template>
        </Dialog>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import {
    faFileArchive,
    faDownload,
    faTrash,
    faFolderOpen,
    faSpinner,
    faArrowLeft,
    faBroom,
    faExclamationTriangle,
    faCheck,
    faXmark
} from '@fortawesome/free-solid-svg-icons';
import axios from 'axios';
import { route } from 'ziggy-js';

const toast = useToast();
const isGenerating = ref(false);
const isLoading = ref(false);
const backups = ref([]);

// Estados para el modal de confirmación personalizado
const deleteDialog = ref(false);
const selectedBackup = ref(null);
const isDeleting = ref(false);

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
    if (windowWidth.value < 640) {
        return { width: '95vw', maxWidth: '380px' };
    } else if (windowWidth.value < 768) {
        return { width: '400px' };
    } else {
        return { width: '450px' };
    }
});

// Configuración del sistema: número de respaldos a mantener
const KEEP_LATEST_BACKUPS = 3; // Configurable: número de respaldos recientes a mantener

// Helper para headers HTTP comunes
const getApiHeaders = () => ({
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    'Accept': 'application/json',
    'Content-Type': 'application/json'
});

const loadBackups = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/backups', {
            headers: getApiHeaders()
        });

        if (response.data.success) {
            backups.value = response.data.backups;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: response.data.message || 'Error al cargar los backups',
                life: 5000
            });
        }
    } catch (error) {
        if (error.response) {
            toast.add({
                severity: 'error',
                summary: 'Error de Conexión',
                detail: `Error ${error.response.status}: ${error.response.data.message || 'Error desconocido'}`,
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error de Red',
                detail: 'No se pudo conectar con el servidor',
                life: 5000
            });
        }
    } finally {
        isLoading.value = false;
    }
};

const generateBackup = async () => {
    if (isGenerating.value) {
        toast.add({
            severity: 'warn',
            summary: 'Backup en Proceso',
            detail: 'Ya se está generando un backup. Por favor espere.',
            life: 3000
        });
        return;
    }

    isGenerating.value = true;

    try {
        toast.add({
            severity: 'info',
            summary: 'Generando Backup',
            detail: 'El proceso puede tomar unos minutos...',
            life: 5000
        });

        const response = await axios.post('/api/backups/generate', {}, {
            headers: getApiHeaders()
        });

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Backup Generado',
                detail: 'El backup se ha generado correctamente.',
                life: 4000
            });

            // Recargar la lista de backups
            await loadBackups();
        } else {
            throw new Error(response.data.message || 'Error al generar el backup');
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error al Generar Backup',
            detail: error.response?.data?.message || error.message || 'Error desconocido',
            life: 5000
        });
    } finally {
        isGenerating.value = false;
    }
};

const downloadBackup = async (backupId) => {
    try {
        const response = await axios.get(`/api/backups/${backupId}/download`, {
            responseType: 'blob'
        });

        // Crear un enlace de descarga
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        // Obtener el nombre del archivo del header o usar uno por defecto
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'backup.zip';
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="(.+)"/);
            if (filenameMatch) {
                filename = filenameMatch[1];
            }
        }

        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        toast.add({
            severity: 'success',
            summary: 'Descarga Iniciada',
            detail: 'El archivo se está descargando',
            life: 3000
        });

    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error de Descarga',
            detail: 'No se pudo descargar el respaldo',
            life: 5000
        });
    }
};

const deleteBackup = (backupId) => {
    // Buscar información del backup en la lista
    const backup = backups.value.find(b => b.id === backupId);
    selectedBackup.value = backup || { id: backupId, name: `Backup ${backupId}` };
    deleteDialog.value = true;
};

const confirmDeleteBackup = async () => {
    if (!selectedBackup.value) return;

    isDeleting.value = true;

    try {
        const response = await axios.delete(`/api/backups/${selectedBackup.value.id}`, {
            headers: getApiHeaders()
        });

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Respaldo Eliminado',
                detail: `El respaldo "${selectedBackup.value.name}" ha sido eliminado correctamente.`,
                life: 3000
            });

            // Recargar la lista de backups
            await loadBackups();
            deleteDialog.value = false;
            selectedBackup.value = null;
        } else {
            throw new Error(response.data.message || 'Error al eliminar el respaldo');
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error al Eliminar',
            detail: error.response?.data?.message || error.message || 'Error desconocido',
            life: 5000
        });
    } finally {
        isDeleting.value = false;
    }
};

const cancelDeleteBackup = () => {
    deleteDialog.value = false;
    selectedBackup.value = null;
};

const cleanupBackups = async () => {
    // Verificar si hay respaldos disponibles para limpiar
    if (backups.value.length === 0) {
        toast.add({
            severity: 'info',
            summary: 'No Hay Nada Que Limpiar',
            detail: 'No hay respaldos disponibles para limpiar.',
            life: 3000
        });
        return;
    }

    // Si hay KEEP_LATEST_BACKUPS o menos respaldos, no hay necesidad de limpiar
    if (backups.value.length <= KEEP_LATEST_BACKUPS) {
        toast.add({
            severity: 'info',
            summary: 'No Hay Nada Que Limpiar',
            detail: `Solo hay ${backups.value.length} respaldo(s). Se mantienen automáticamente los últimos ${KEEP_LATEST_BACKUPS}.`,
            life: 3000
        });
        return;
    }

    // Mostrar confirmación y proceder directamente con la limpieza
    toast.add({
        severity: 'warn',
        summary: 'Iniciando Limpieza',
        detail: `Se eliminarán los respaldos antiguos, manteniendo solo los últimos ${KEEP_LATEST_BACKUPS}.`,
        life: 4000
    });

    try {
        const response = await axios.post('/api/backups/cleanup', {}, {
            headers: getApiHeaders()
        });

        if (response.data.success) {
            const deletedCount = response.data.deleted_count || 'varios';
            toast.add({
                severity: 'success',
                summary: 'Limpieza Completada',
                detail: `Se han eliminado ${deletedCount} respaldo(s) antiguos.`,
                life: 4000
            });

            // Recargar la lista de backups
            await loadBackups();
        } else {
            throw new Error(response.data.message || 'Error al limpiar los respaldos');
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error al Limpiar',
            detail: error.response?.data?.message || error.message || 'Error desconocido',
            life: 5000
        });
    }
};

// Listener para actualizar el tamaño de ventana
const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth;
};

onMounted(() => {
    loadBackups();
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateWindowWidth);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateWindowWidth);
    }
});
</script>
