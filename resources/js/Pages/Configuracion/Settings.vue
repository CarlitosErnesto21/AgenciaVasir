<template>
    <AuthenticatedLayout>
        <!-- Toast para notificaciones -->
        <Toast />

        <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-6 lg:py-8">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-600 to-red-700 px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4">
                    <h1 class="text-sm sm:text-lg lg:text-2xl font-bold text-white flex items-center">
                        <FontAwesomeIcon :icon="faGear" class="mr-1 sm:mr-2 lg:mr-3 text-sm sm:text-base lg:text-xl" />
                        <span class="hidden sm:inline">Configuración del Sistema</span>
                        <span class="sm:hidden">Configuración del sistema</span>
                    </h1>
                    <p class="text-red-100 mt-0.5 sm:mt-1 lg:mt-2 text-xs sm:text-sm lg:text-base hidden sm:block">Configure los parámetros generales del sistema</p>
                </div>

                <!-- Layout principal con menú lateral -->
                <div class="flex flex-col lg:flex-row min-h-screen">
                    <!-- Menú lateral -->
                    <div class="w-full lg:w-80 bg-gray-50 border-b lg:border-b-0 lg:border-r border-gray-200 p-2 sm:p-4 lg:p-6">
                        <nav class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-1 gap-1 sm:gap-2 lg:space-y-2 lg:gap-0">
                            <button
                                v-for="item in menuItems"
                                :key="item.id"
                                @click="handleSectionChange(item.id)"
                                :class="[
                                    'w-full flex flex-col lg:flex-row items-center lg:items-center px-1.5 sm:px-3 py-1.5 sm:py-2 lg:px-4 lg:py-3 text-center lg:text-left rounded-lg transition-all duration-200',
                                    activeSection === item.id
                                        ? 'bg-red-600 text-white shadow-md'
                                        : 'text-gray-700 hover:bg-red-50 hover:text-red-700'
                                ]"
                            >
                                <span class="text-sm sm:text-lg lg:text-xl mb-0.5 sm:mb-1 lg:mb-0 lg:mr-3">{{ item.icon }}</span>
                                <div class="flex-1">
                                    <div class="font-medium text-xs sm:text-sm lg:text-base">{{ item.title }}</div>
                                    <div class="text-xs hidden lg:block" :class="activeSection === item.id ? 'text-red-100' : 'text-gray-500'">
                                        {{ item.description }}
                                    </div>
                                </div>
                            </button>
                        </nav>
                    </div>

                    <!-- Contenido principal -->
                    <div class="flex-1 p-2 sm:p-4 lg:p-6">
                        <!-- General Settings -->
                        <GeneralSettings
                            v-if="activeSection === 'general'"
                            :settings="settings"
                            :is-saving="isSaving"
                            @save="saveSettings"
                            @reset="resetSettings"
                            @unsaved-changes="reportUnsavedChanges"
                        />

                        <!-- Security Settings -->
                        <SecuritySettings
                            v-if="activeSection === 'security'"
                            :settings="settings"
                            :is-saving="isSaving"
                            @save="saveSettings"
                            @reset="resetSettings"
                            @unsaved-changes="reportUnsavedChanges"
                        />

                        <!-- Notification Settings -->
                        <NotificationSettings
                            v-if="activeSection === 'notifications'"
                            :settings="settings"
                            :is-saving="isSaving"
                            @save="saveSettings"
                            @reset="resetSettings"
                            @unsaved-changes="reportUnsavedChanges"
                        />

                        <!-- Corporate Settings -->
                        <CorporateSettings
                            v-if="activeSection === 'corporate'"
                            :settings="settings"
                            :company-values="companyValues"
                            :is-saving="isSaving"
                            @reset="resetSettings"
                            @unsaved-changes="reportUnsavedChanges"
                            @settings-updated="reloadSettings"
                        />

                        <!-- Database Settings -->
                        <DatabaseSettings
                            v-if="activeSection === 'database'"
                            :database-info="databaseInfo"
                            :is-saving="isSaving"
                            @save="saveSettings"
                            @reset="resetSettings"
                            @unsaved-changes="reportUnsavedChanges"
                        />
                    </div>
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
import { faGear, faSpinner, faFileArchive, faDatabase } from '@fortawesome/free-solid-svg-icons';

// Importar componentes de configuración
import GeneralSettings from './SettingsComponent/GeneralSettings.vue';
import SecuritySettings from './SettingsComponent/SecuritySettings.vue';
import NotificationSettings from './SettingsComponent/NotificationSettings.vue';
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
const activeSection = ref('general');

// Variable reactiva para companyValues que se actualiza desde el servidor
const companyValues = ref(props.companyValues);

// Variables para manejo global de cambios sin guardar
const globalHasUnsavedChanges = ref(false);
const showUnsavedModal = ref(false);
const pendingSection = ref(null);

// Función para verificar cambios antes de cambiar de sección
const handleSectionChange = (newSection) => {
    if (globalHasUnsavedChanges.value) {
        pendingSection.value = newSection;
        showUnsavedModal.value = true;
    } else {
        activeSection.value = newSection;
    }
};

// Confirmar cambio de sección sin guardar
const confirmSectionChange = () => {
    globalHasUnsavedChanges.value = false;
    showUnsavedModal.value = false;
    if (pendingSection.value) {
        activeSection.value = pendingSection.value;
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

// Elementos del menú lateral
const menuItems = ref([
    {
        id: 'general',
        icon: '⚙️',
        title: 'Configuración General',
        description: 'Información básica del sistema'
    },
    {
        id: 'security',
        icon: '🔐',
        title: 'Seguridad',
        description: 'Configuraciones de seguridad'
    },
    {
        id: 'notifications',
        icon: '📧',
        title: 'Notificaciones',
        description: 'Gestión de comunicaciones'
    },
    {
        id: 'corporate',
        icon: '🏢',
        title: 'Información Corporativa',
        description: 'Contenido empresarial'
    },
    {
        id: 'database',
        icon: '💾',
        title: 'Base de Datos',
        description: 'Respaldos y mantenimiento'
    }
]);

// Configuraciones del sistema
const settings = ref({
    // Configuraciones para contenido del sitio
    mission: props.siteSettings.mission || '',
    vision: props.siteSettings.vision || '',
    description: props.siteSettings.description || '',

    // Configuraciones del sistema (solo para display, no funcionales aún)
    systemName: 'Sistema VASIR',
    version: '1.0.0',
    contactEmail: 'contacto@vasir.com',
    contactPhone: '+505 8888-8888',
    requireEmailVerification: true,
    enableTwoFactor: false,
    sessionTimeout: 120,
    emailNotifications: true,
    smsNotifications: false,
    smtpServer: 'smtp.gmail.com'
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
