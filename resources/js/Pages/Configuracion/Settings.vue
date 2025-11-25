<template>
    <AuthenticatedLayout>
        <!-- Toast para notificaciones -->
        <Toast />

        <div class="bg-gray-50 pt-4 md:pt-6">
            <!-- Header -->
            <div class="bg-white shadow-lg border-b border-gray-200 mb-5 md:mb-10">
                <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 flex flex-col sm:flex-row items-center gap-3 sm:gap-4 lg:gap-6">
                    <div class="bg-red-600 rounded-xl p-4 shadow flex items-center justify-center border-4 border-white mb-3 sm:mb-0">
                        <FontAwesomeIcon :icon="faGear" class="w-10 h-10 text-white" />
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Configuración del Sistema</h1>
                        <p class="text-gray-700 text-base sm:text-lg mt-1 font-medium">Configure los parámetros generales del sistema</p>
                    </div>
                </div>
            </div>

            <div class="py-2 sm:py-4">
                <div class="max-w-4xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
                    <template v-if="!selectedForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            <!-- Tarjeta Información Corporativa -->
                            <div @click="showForm('corporate')" class="cursor-pointer bg-white rounded-xl sm:rounded-2xl shadow-lg border border-gray-100 p-4 sm:p-6 flex flex-col items-center hover:shadow-2xl transition-all group">
                                <div class="bg-red-100 rounded-full p-3 sm:p-4 mb-3 sm:mb-4">
                                    <FontAwesomeIcon :icon="faBuilding" class="w-6 h-6 sm:w-8 sm:h-8 text-red-600" />
                                </div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Información Corporativa</h3>
                                <p class="text-gray-600 text-xs sm:text-sm text-center leading-relaxed">Configure la misión, visión, descripción y valores corporativos de la empresa</p>
                            </div>

                            <!-- Tarjeta Base de Datos -->
                            <div @click="showForm('database')" class="cursor-pointer bg-white rounded-xl sm:rounded-2xl shadow-lg border border-gray-100 p-4 sm:p-6 flex flex-col items-center hover:shadow-2xl transition-all group">
                                <div class="bg-red-100 rounded-full p-3 sm:p-4 mb-3 sm:mb-4">
                                    <FontAwesomeIcon :icon="faDatabase" class="w-6 h-6 sm:w-8 sm:h-8 text-red-600" />
                                </div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Base de Datos</h3>
                                <p class="text-gray-600 text-xs sm:text-sm text-center leading-relaxed">Gestione respaldos y mantenimiento de la base de datos del sistema</p>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <button @click="goBack" class="mb-4 sm:mb-6 flex items-center text-red-600 hover:text-red-800 font-semibold text-sm sm:text-base px-2 py-1 rounded-md hover:bg-red-50 transition-all">
                            <FontAwesomeIcon :icon="faChevronLeft" class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2" />
                            <span class="hidden sm:inline">Volver</span>
                            <span class="sm:hidden">Atrás</span>
                        </button>

                        <div v-if="selectedForm === 'corporate'">
                            <CorporateSettings
                                :settings="settings"
                                :company-values="companyValues"
                                :is-saving="isSaving"
                                @reset="resetSettings"
                                @unsaved-changes="reportUnsavedChanges"
                                @settings-updated="reloadSettings"
                            />
                        </div>

                        <div v-else-if="selectedForm === 'database'">
                            <DatabaseSettings
                                :database-info="databaseInfo"
                                :is-saving="isSaving"
                                @save="saveSettings"
                                @reset="resetSettings"
                                @unsaved-changes="reportUnsavedChanges"
                            />
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal global de cambios sin guardar -->
        <UnsavedChangesModal
            v-model:visible="showUnsavedModal"
            @continue-editing="cancelSectionChange"
            @exit-without-saving="confirmSectionChange"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue/usetoast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faGear, faSpinner, faFileArchive, faDatabase, faChevronLeft, faBuilding } from '@fortawesome/free-solid-svg-icons';
import Toast from 'primevue/toast';

// Importar componentes de configuración
import CorporateSettings from './SettingsComponent/CorporateSettings.vue';
import DatabaseSettings from './SettingsComponent/DatabaseSettings.vue';

// Importar composable y modal para cambios sin guardar
import { useUnsavedChanges } from './SettingsComponent/components/useUnsavedChanges.js';
import UnsavedChangesModal from './SettingsComponent/components/UnsavedChangesModal.vue';

const page = usePage();
const toast = useToast();

const props = defineProps({
    siteSettings: {
        type: Object,
        default: () => ({})
    },
    companyValues: {
        type: Array,
        default: () => []
    },
    databaseInfo: {
        type: Object,
        default: () => ({
            last_backup_formatted: 'No disponible',
            database_size: 'No disponible',
            status: 'unknown',
            status_text: 'Desconocido'
        })
    }
});

const isSaving = ref(false);
const selectedForm = ref(null); // null, 'corporate', 'database'

// Variable reactiva para companyValues que se actualiza desde el servidor
const companyValues = ref(props.companyValues);

// Variables para manejo global de cambios sin guardar
const globalHasUnsavedChanges = ref(false);
const showUnsavedModal = ref(false);
const pendingSection = ref(null);

// Función para mostrar formulario
const showForm = (form) => {
    if (globalHasUnsavedChanges.value) {
        pendingSection.value = form;
        showUnsavedModal.value = true;
    } else {
        selectedForm.value = form;
    }
};

// Función para volver
const goBack = () => {
    if (globalHasUnsavedChanges.value) {
        pendingSection.value = null;
        showUnsavedModal.value = true;
    } else {
        selectedForm.value = null;
    }
};

// Confirmar cambio de sección sin guardar
const confirmSectionChange = () => {
    globalHasUnsavedChanges.value = false;
    showUnsavedModal.value = false;
    if (pendingSection.value !== undefined) {
        selectedForm.value = pendingSection.value;
        pendingSection.value = null;
    }
};

// Cancelar cambio de sección
const cancelSectionChange = () => {
    showUnsavedModal.value = false;
    pendingSection.value = null;
};

// Función para que los componentes hijos reporten cambios
const reportUnsavedChanges = (hasChanges) => {
    globalHasUnsavedChanges.value = hasChanges;
};



// Configuraciones del sistema
const settings = ref({
    // Configuraciones para contenido del sitio
    mission: props.siteSettings.mission || '',
    vision: props.siteSettings.vision || '',
    description: props.siteSettings.description || ''
});

// Configuraciones originales para restablecer
const originalSettings = ref({...settings.value});

const saveSettings = async () => {
    isSaving.value = true;
    try {
        // Guardar configuraciones de contenido del sitio
        await router.post(route('settings.update'), {
            mission: settings.value.mission,
            vision: settings.value.vision,
            description: settings.value.description
        }, {
            onSuccess: (page) => {
                // Actualizar las configuraciones originales
                originalSettings.value.mission = settings.value.mission;
                originalSettings.value.vision = settings.value.vision;
                originalSettings.value.description = settings.value.description;

                // Mostrar toast de éxito
                toast.add({
                    severity: 'success',
                    summary: '¡Éxito!',
                    detail: 'Configuración de la empresa actualizada correctamente',
                    life: 3000
                });
            },
            onError: (errors) => {
                console.error('Error al guardar la configuración:', errors);

                // Mostrar toast de error
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Error al guardar la configuración',
                    life: 5000
                });
            }
        });

    } catch (error) {
        console.error('Error al guardar la configuración:', error);

        // Mostrar toast de error
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al guardar la configuración',
            life: 5000
        });
    } finally {
        isSaving.value = false;
    }
};

const resetSettings = () => {
    if (confirm('¿Está seguro de restablecer todos los cambios?')) {
        settings.value = {...originalSettings.value};
    }
};

// Función para recargar settings después de un guardado exitoso
const reloadSettings = () => {
    router.get(route('settings'), {}, {
        preserveState: false,
        preserveScroll: true,
        only: ['siteSettings', 'companyValues'],
        onSuccess: (page) => {

            // Actualizar los settings locales con los nuevos datos del servidor
            settings.value.mission = page.props.siteSettings.mission || '';
            settings.value.vision = page.props.siteSettings.vision || '';
            settings.value.description = page.props.siteSettings.description || '';

            // Actualizar también los originalSettings
            originalSettings.value.mission = settings.value.mission;
            originalSettings.value.vision = settings.value.vision;
            originalSettings.value.description = settings.value.description;

            // Actualizar companyValues con los datos frescos del servidor
            companyValues.value = page.props.companyValues;
        },
        onError: (errors) => {
            console.error('❌ Error al recargar settings:', errors);
        }
    });
};
</script>
