<template>
    <AuthenticatedLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <Link :href="route('settings')"
                                class="flex items-center text-white hover:text-red-200 transition-colors duration-200 p-2 rounded-lg hover:bg-red-800 mr-4"
                                title="Regresar a configuración">
                                <FontAwesomeIcon :icon="faArrowLeft" class="h-5 w-5" />
                            </Link>
                            <div>
                                <h1 class="text-2xl font-bold text-white flex items-center">
                                    <FontAwesomeIcon :icon="faFileArchive" class="mr-3" />
                                    Respaldo de Base de Datos
                                </h1>
                                <p class="text-red-100 mt-2">Genere y gestione copias de seguridad de la base de datos</p>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Content -->
                <div class="p-6">
                    <!-- GESTIÓN MANUAL DE RESPALDOS -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <span class="text-2xl mr-2">💾</span>
                            Gestión Manual de Respaldos
                        </h2>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                            <div class="space-y-6">
                                <div class="bg-white border rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium mr-3">
                                                🔧 Manual
                                            </div>
                                            <span class="text-gray-700 font-medium">
                                                Respaldos bajo demanda
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex space-x-4">
                                    <button
                                        @click="generateBackup"
                                        :disabled="isGenerating"
                                        class="bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg flex items-center transition-colors duration-200"
                                    >
                                        <FontAwesomeIcon
                                            :icon="isGenerating ? faSpinner : faDownload"
                                            :class="{ 'animate-spin': isGenerating }"
                                            class="mr-2"
                                        />
                                        {{ isGenerating ? 'Generando...' : 'Generar Respaldo' }}
                                    </button>

                                    <button
                                        @click="cleanupBackups"
                                        :disabled="isGenerating"
                                        class="bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg flex items-center transition-colors duration-200"
                                    >
                                        <FontAwesomeIcon :icon="faBroom" class="mr-2" />
                                        Limpiar Antiguos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RESPALDOS DISPONIBLES -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Respaldos Disponibles</h2>

                        <div v-if="isLoading" class="text-center py-8 text-gray-500">
                            <FontAwesomeIcon :icon="faSpinner" class="text-4xl mb-2 animate-spin" />
                            <p>Cargando respaldos...</p>
                        </div>

                        <div v-else-if="backups.length === 0" class="text-center py-8 text-gray-500">
                            <FontAwesomeIcon :icon="faFolderOpen" class="text-4xl mb-2" />
                            <p>No hay respaldos disponibles</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="backup in backups"
                                :key="backup.id"
                                class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                            >
                                <div class="flex items-center">
                                    <FontAwesomeIcon :icon="faFileArchive" class="text-red-600 mr-3" />
                                    <div>
                                        <h3 class="font-medium text-gray-800">{{ backup.name }}</h3>
                                        <p class="text-sm text-gray-500">{{ backup.date }} - {{ backup.size }}</p>
                                    </div>
                                </div>

                                <div class="flex space-x-2">
                                    <button
                                        @click="downloadBackup(backup.id)"
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded-full hover:bg-blue-50 transition-colors duration-200"
                                        title="Descargar"
                                    >
                                        <FontAwesomeIcon :icon="faDownload" />
                                    </button>
                                    <button
                                        @click="deleteBackup(backup.id)"
                                        class="text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50 transition-colors duration-200"
                                        title="Eliminar"
                                    >
                                        <FontAwesomeIcon :icon="faTrash" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast para notificaciones -->
        <Toast />
        <!-- Dialog de confirmación -->
        <ConfirmDialog
            :style="{ width: '450px' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faFileArchive,
    faDownload,
    faTrash,
    faFolderOpen,
    faSpinner,
    faArrowLeft,
    faBroom
} from '@fortawesome/free-solid-svg-icons';
import axios from 'axios';
import { route } from 'ziggy-js';

const toast = useToast();
const confirm = useConfirm();
const isGenerating = ref(false);
const isLoading = ref(false);
const backups = ref([]);

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
    const backupName = backup ? backup.name : `Backup ${backupId}`;

    confirm.require({
        message: `¿Está seguro de que desea eliminar el backup "${backupName}"? Esta acción no se puede deshacer.`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        rejectClass: 'p-button-outlined',
        acceptLabel: 'Sí, Eliminar',
        rejectLabel: 'Cancelar',
        accept: async () => {
            try {
                const response = await axios.delete(`/api/backups/${backupId}`, {
                    headers: getApiHeaders()
                });

                if (response.data.success) {
                    toast.add({
                        severity: 'success',
                        summary: 'Backup Eliminado',
                        detail: `El backup "${backupName}" ha sido eliminado correctamente.`,
                        life: 3000
                    });

                    // Recargar la lista de backups
                    await loadBackups();
                } else {
                    throw new Error(response.data.message || 'Error al eliminar el backup');
                }
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Error al Eliminar',
                    detail: error.response?.data?.message || error.message || 'Error desconocido',
                    life: 5000
                });
            }
        }
    });
};

const cleanupBackups = () => {
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

    // Calcular qué archivos se eliminarán (todos excepto los últimos KEEP_LATEST_BACKUPS)
    const sortedBackups = [...backups.value].sort((a, b) => {
        // Intentar ordenar por fecha, si falla usar el nombre del archivo
        try {
            // Convertir formato "dd/mm/yyyy HH:MM" a Date
            const dateA = a.date.split(' ');
            const [dayA, monthA, yearA] = dateA[0].split('/');
            const timeA = dateA[1] || '00:00';
            const fullDateA = new Date(`${yearA}-${monthA}-${dayA}T${timeA}`);

            const dateB = b.date.split(' ');
            const [dayB, monthB, yearB] = dateB[0].split('/');
            const timeB = dateB[1] || '00:00';
            const fullDateB = new Date(`${yearB}-${monthB}-${dayB}T${timeB}`);

            return fullDateB - fullDateA; // Más reciente primero
        } catch (error) {
            // Si falla el parseo de fechas, ordenar por nombre del archivo
            console.warn('Error parsing backup dates, falling back to filename sort:', error);
            return b.name.localeCompare(a.name);
        }
    });

    const backupsToDelete = sortedBackups.slice(KEEP_LATEST_BACKUPS); // Los que sobran después de mantener los más recientes

    if (backupsToDelete.length === 0) {
        toast.add({
            severity: 'info',
            summary: 'No Hay Nada Que Limpiar',
            detail: `Ya tienes solo los últimos ${KEEP_LATEST_BACKUPS} respaldos.`,
            life: 3000
        });
        return;
    }

    const fileNames = backupsToDelete.map(b => b.name).join(', ');

    confirm.require({
        message: `¿Está seguro de limpiar los respaldos antiguos? Se eliminarán ${backupsToDelete.length} respaldo(s): ${fileNames}. Se mantendrán solo los últimos ${KEEP_LATEST_BACKUPS}. Esta acción no se puede deshacer.`,
        header: 'Confirmar Limpieza',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, Limpiar',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-warning',
        accept: async () => {
            try {
                const response = await axios.post('/api/backups/cleanup', {}, {
                    headers: getApiHeaders()
                });

                if (response.data.success) {
                    toast.add({
                        severity: 'success',
                        summary: 'Limpieza Completada',
                        detail: `Se han eliminado ${backupsToDelete.length} respaldo(s) antiguos.`,
                        life: 4000
                    });

                    // Recargar la lista de backups
                    await loadBackups();
                } else {
                    throw new Error(response.data.message || 'Error al limpiar los backups');
                }
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Error al Limpiar',
                    detail: error.response?.data?.message || error.message || 'Error desconocido',
                    life: 5000
                });
            }
        }
    });
};

onMounted(() => {
    loadBackups();
});
</script>
