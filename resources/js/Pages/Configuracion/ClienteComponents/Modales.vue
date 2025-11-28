<script setup>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faEye, faTrashCan, faXmark, faSpinner, faUsers, faPencil, faUserEdit, faEnvelope, faFileText, faUser, faDatabase, faChartLine, faDownload, faCalendarCheck, faShoppingCart, faPhone, faCreditCard } from "@fortawesome/free-solid-svg-icons";
import { faWhatsapp } from "@fortawesome/free-brands-svg-icons";
import { ref, computed, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Dialog from 'primevue/dialog';

// Props recibidas desde el componente padre
const props = defineProps({
    visible: Boolean,
    deleteVisible: Boolean,
    unsavedChangesVisible: Boolean,
    detallesVisible: Boolean,
    cliente: Object,
    dialogStyle: Object,
    isDeleting: Boolean,
    isLoading: Boolean,
    submitted: Boolean,
});

// Emits para comunicación con el componente padre
const emit = defineEmits([
    'update:visible',
    'update:delete-visible',
    'update:unsaved-changes-visible',
    'update:detalles-visible',
    'view-details',
    'view-reservations',
    'view-purchases',
    'view-reports',
    'toggle-status',
    'delete-cliente',
    'cancel-delete',
    'close-without-saving',
    'continue-editing'
]);

// Variable para confirmación de eliminación
const confirmationText = ref('');
// Variable para el motivo de eliminación
const deletionReason = ref('');
// Estadísticas de eliminación
const estadisticasEliminacion = ref(null);
const cargandoEstadisticas = ref(false);

// Funciones para manejar eventos
const handleViewDetails = () => {
    isVisible.value = false; // Cerrar modal de más acciones
    emit('view-details', props.cliente);
};

const handleViewReports = () => {
    isVisible.value = false; // Cerrar modal de más acciones
    // Navegar a la vista de informes usando Inertia SPA
    router.visit('/generar-informes');
};

// Cargar estadísticas de eliminación
const cargarEstadisticasEliminacion = async () => {
    if (!props.cliente) return;

    cargandoEstadisticas.value = true;
    try {
        const identificador = props.cliente.id || props.cliente.user_id;
        const response = await fetch(`/api/clientes/${identificador}/estadisticas-eliminacion`, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            estadisticasEliminacion.value = result.estadisticas;
        } else {
            // En caso de error, usar valores por defecto
            estadisticasEliminacion.value = {
                reservas_count: 0,
                ventas_count: 0,
                pagos_reservas_count: 0,
                pagos_ventas_count: 0,
                total_pagos_count: 0
            };
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        // Usar valores por defecto
        estadisticasEliminacion.value = {
            reservas_count: 0,
            ventas_count: 0,
            pagos_reservas_count: 0,
            pagos_ventas_count: 0,
            total_pagos_count: 0
        };
    } finally {
        cargandoEstadisticas.value = false;
    }
};

const handleToggleStatus = () => {
    emit('toggle-status', props.cliente);
};

const confirmDelete = () => {
    emit('delete-cliente', deletionReason.value);
    confirmationText.value = ''; // Limpiar el campo
    deletionReason.value = ''; // Limpiar el motivo
};

const cancelDelete = () => {
    confirmationText.value = ''; // Limpiar el campo
    deletionReason.value = ''; // Limpiar el motivo
    emit('cancel-delete');
};

// Función para cerrar el modal de más acciones
const closeModal = () => {
    isVisible.value = false;
};

const handleCloseWithoutSaving = () => {
    emit('close-without-saving');
};

const handleContinueEditing = () => {
    emit('continue-editing');
};

// Función para abrir Gmail
const abrirGmail = (email) => {
  if (!email) return;

  // Detectar si es móvil
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  if (isMobile) {
    // En móviles, usar mailto: que el sistema operativo maneje
    window.location.href = `mailto:${email}`;
  } else {
    // En escritorio, abrir Gmail web directamente
    window.open(`https://mail.google.com/mail/?view=cm&fs=1&to=${email}`, '_blank');
  }
};// Computed properties para v-model
const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const isDeleteVisible = computed({
    get: () => props.deleteVisible,
    set: (value) => emit('update:delete-visible', value)
});

const isUnsavedChangesVisible = computed({
    get: () => props.unsavedChangesVisible,
    set: (value) => emit('update:unsaved-changes-visible', value)
});

const isDetallesVisible = computed({
    get: () => props.detallesVisible,
    set: (value) => emit('update:detalles-visible', value)
});

// Funciones de ayuda
const updateVisible = (value) => {
    emit('update:visible', value);
};

const updateDeleteVisible = (value) => {
    emit('update:delete-visible', value);
};

const updateUnsavedChangesVisible = (value) => {
    emit('update:unsaved-changes-visible', value);
};

const updateDetallesVisible = (value) => {
    emit('update:detalles-visible', value);
};

// Watcher para cargar estadísticas cuando se abre el modal de eliminación
watch(() => props.deleteVisible, (newValue) => {
    if (newValue && props.cliente) {
        cargarEstadisticasEliminacion();
        // Reset form values
        confirmationText.value = '';
        deletionReason.value = '';
    }
});
</script>

<template>
    <!-- Modal de Más Acciones -->
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
                    Cliente: <span class="text-blue-600">{{ cliente.user?.name || 'Cliente' }}</span>
                </h4>
                <p class="text-sm text-gray-600 mt-1">Selecciona una acción a realizar</p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <!-- Ver Detalles -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="handleViewDetails"
                >
                    <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Ver Detalles</div>
                        <div class="text-xs opacity-90">Información completa del cliente</div>
                    </div>
                </button>



                <!-- Ver Informes del Cliente -->
                <button
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="handleViewReports"
                >
                    <FontAwesomeIcon :icon="faChartLine" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Ver Informes del Cliente</div>
                        <div class="text-xs opacity-90">Descargar reportes de reservas y ventas</div>
                    </div>
                </button>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 mb-3">
                    <h4 class="font-semibold text-blue-800 text-sm mb-2 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faChartLine" class="h-4 w-4" />
                        ¿Cómo descargar informes del cliente?
                    </h4>
                    <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                        <li>Haz clic en "Ver Informes del Cliente" para abrir la vista de informes</li>
                        <li>Selecciona "Reservas de Cliente" o "Ventas de Cliente" en el tipo de informe</li>
                        <li>Busca y selecciona el cliente deseado</li>
                        <li>Haz clic en "Generar Informe PDF" para descargar</li>
                    </ol>
                </div>
                <p class="text-xs text-gray-500 text-center">
                    💡 Selecciona una acción para continuar.
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
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de Eliminar Cliente -->
    <Dialog v-model:visible="isDeleteVisible" header="Eliminar usuario y cliente" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
        <div class="space-y-3 sm:space-y-4 p-1 sm:p-0">
            <!-- Encabezado de advertencia -->
            <div class="flex flex-col sm:flex-row items-start gap-2 sm:gap-3">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="h-6 w-6 sm:h-8 sm:w-8 text-red-500 flex-shrink-0" />
                <div class="flex flex-col flex-1">
                    <span class="text-sm sm:text-lg font-semibold">¿Estás seguro de eliminar completamente al usuario:</span>
                    <span class="font-bold text-blue-600 text-sm sm:text-lg break-words">{{ cliente.user?.name }}?</span>
                    <span class="text-red-600 text-xs sm:text-sm font-medium mt-1 sm:mt-2">Se eliminará su cuenta y todos sus datos. Esta acción es irreversible.</span>
                </div>
            </div>

            <!-- Advertencia de impacto -->
            <div class="border-l-4 border-red-500 bg-red-50 p-3 sm:p-4 rounded-r-lg">
                <div class="space-y-2 sm:space-y-3">
                    <p class="text-red-700 font-medium text-xs sm:text-sm">
                        Al eliminar este cliente se eliminarán <strong>PERMANENTEMENTE</strong>:
                    </p>

                    <!-- Estadísticas específicas -->
                    <div v-if="cargandoEstadisticas" class="flex items-center justify-center py-4">
                        <FontAwesomeIcon :icon="faSpinner" class="h-5 w-5 animate-spin text-red-600 mr-2" />
                        <span class="text-red-700 text-sm">Calculando impacto...</span>
                    </div>

                    <ul v-else class="space-y-1 sm:space-y-2 text-red-700">
                        <li class="flex items-start gap-2 text-xs sm:text-sm">
                            <FontAwesomeIcon :icon="faUser" class="h-3 w-3 sm:h-4 sm:w-4 mt-0.5 flex-shrink-0" />
                            <span>Su cuenta de usuario completa</span>
                        </li>
                        <li class="flex items-start gap-2 text-xs sm:text-sm">
                            <FontAwesomeIcon :icon="faCalendarCheck" class="h-3 w-3 sm:h-4 sm:w-4 mt-0.5 flex-shrink-0" />
                            <span>
                                {{ estadisticasEliminacion?.reservas_count || 0 }} reserva(s)
                                <span v-if="estadisticasEliminacion?.reservas_count > 0" class="font-semibold">(activas e históricas)</span>
                            </span>
                        </li>
                        <li class="flex items-start gap-2 text-xs sm:text-sm">
                            <FontAwesomeIcon :icon="faShoppingCart" class="h-3 w-3 sm:h-4 sm:w-4 mt-0.5 flex-shrink-0" />
                            <span>
                                {{ estadisticasEliminacion?.ventas_count || 0 }} venta(s) y transacciones
                            </span>
                        </li>
                        <li class="flex items-start gap-2 text-xs sm:text-sm">
                            <FontAwesomeIcon :icon="faCreditCard" class="h-3 w-3 sm:h-4 sm:w-4 mt-0.5 flex-shrink-0" />
                            <span>
                                <strong>{{ estadisticasEliminacion?.total_pagos_count || 0 }} pago(s) total</strong>
                                <span v-if="estadisticasEliminacion && (estadisticasEliminacion.pagos_reservas_count > 0 || estadisticasEliminacion.pagos_ventas_count > 0)" class="block text-xs mt-1 ml-5">
                                    {{ estadisticasEliminacion.pagos_reservas_count }} de reservas, {{ estadisticasEliminacion.pagos_ventas_count }} de ventas
                                </span>
                            </span>
                        </li>
                        <li class="flex items-start gap-2 text-xs sm:text-sm">
                            <FontAwesomeIcon :icon="faDatabase" class="h-3 w-3 sm:h-4 sm:w-4 mt-0.5 flex-shrink-0" />
                            <span>Todo su historial y datos personales</span>
                        </li>
                    </ul>

                    <div class="mt-2 sm:mt-3 p-2 sm:p-3 bg-yellow-100 border-l-4 border-yellow-500 rounded-r">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 sm:gap-2">
                            <FontAwesomeIcon :icon="faDownload" class="h-4 w-4 sm:h-5 sm:w-5 text-yellow-600 flex-shrink-0" />
                            <p class="text-yellow-800 font-semibold text-xs sm:text-sm">
                                RECOMENDACIÓN: Descarga los datos del cliente antes de eliminarlo
                            </p>
                        </div>
                        <p class="text-yellow-700 text-xs sm:text-sm mt-1">
                            Ve a <strong>Informes → Reservas/Ventas de Cliente</strong> para descargar sus datos. Una vez eliminado, no podrás recuperar ninguna información.
                        </p>
                        <button
                            type="button"
                            class="mt-2 bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded text-xs font-medium flex items-center gap-1.5 transition-colors duration-200"
                            @click="handleViewReports"
                        >
                            <FontAwesomeIcon :icon="faChartLine" class="h-3 w-3" />
                            Ir a Informes
                        </button>
                    </div>

                    <!-- Advertencia especial para pagos -->
                    <div v-if="estadisticasEliminacion && estadisticasEliminacion.total_pagos_count > 0" class="mt-2 sm:mt-3 p-2 sm:p-3 bg-red-200 border-l-4 border-red-600 rounded-r">
                        <div class="flex items-start gap-2">
                            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 w-4 text-red-700 mt-0.5 flex-shrink-0" />
                            <div>
                                <p class="text-red-900 font-bold text-xs sm:text-sm">
                                    ¡CUIDADO! Este cliente tiene {{ estadisticasEliminacion.total_pagos_count }} pago(s) registrado(s)
                                </p>
                                <p class="text-red-800 text-xs mt-1">
                                    Al eliminarlo se perderá el historial completo de transacciones y pagos. Esta información no se puede recuperar.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 sm:mt-3 p-2 sm:p-3 bg-red-100 border border-red-200 rounded">
                        <p class="text-red-800 font-bold text-center text-xs sm:text-sm">
                            TODOS estos datos serán eliminados permanentemente
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sección del motivo de eliminación -->
            <div class="bg-orange-50 p-3 sm:p-4 rounded-lg border border-orange-200">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 sm:gap-2 mb-2 sm:mb-3">
                    <FontAwesomeIcon :icon="faFileText" class="h-4 w-4 sm:h-5 sm:w-5 text-orange-600 flex-shrink-0" />
                    <h4 class="font-semibold text-orange-800 text-sm sm:text-base">Motivo de eliminación (Requerido)</h4>
                </div>
                <p class="text-orange-700 text-xs sm:text-sm mb-2 sm:mb-3">
                    Especifica el motivo por el cual se está eliminando esta cuenta. Este motivo se incluirá en el correo de notificación que se enviará al cliente.
                </p>
                <textarea
                    v-model="deletionReason"
                    rows="3"
                    class="w-full p-2 sm:p-3 border border-orange-300 rounded-lg text-xs sm:text-sm resize-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                    placeholder="Ejemplo: Violación de términos de servicio, solicitud del cliente, cuenta inactiva, etc."
                    maxlength="500"
                ></textarea>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-1 sm:gap-2 mt-2">
                    <span class="text-orange-600 text-xs">{{ deletionReason.length }}/500 caracteres</span>
                    <span v-if="!deletionReason.trim()" class="text-red-500 text-xs font-medium">
                        * Campo requerido
                    </span>
                </div>
            </div>

            <!-- Campo de confirmación -->
            <div class="bg-gray-100 p-3 sm:p-4 rounded-lg border">
                <label for="confirmationInput" class="block text-gray-700 font-medium mb-2 text-center text-xs sm:text-sm">
                    Escribe "CONFIRMAR" para proceder con la eliminación
                </label>
                <input
                    id="confirmationInput"
                    v-model="confirmationText"
                    type="text"
                    class="w-full p-2 sm:p-3 border border-gray-300 rounded-lg text-center font-bold uppercase tracking-widest text-xs sm:text-sm"
                    placeholder="CONFIRMAR"
                    maxlength="9"
                    @input="confirmationText = $event.target.value.toUpperCase()"
                />
            </div>
        </div>
        <template #footer>
            <div class="flex flex-col sm:flex-row justify-center gap-2 sm:gap-4 w-full p-2 sm:p-0">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-3 sm:px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center justify-center gap-1 sm:gap-2 disabled:opacity-50 disabled:cursor-not-allowed text-xs sm:text-sm"
                    @click="confirmDelete"
                    :disabled="confirmationText !== 'CONFIRMAR' || !deletionReason.trim() || isDeleting"
                >
                    <FontAwesomeIcon
                        :icon="isDeleting ? faSpinner : faTrashCan"
                        :class="[
                            'h-4 sm:h-5',
                            { 'animate-spin': isDeleting }
                        ]"
                    />
                    <span v-if="!isDeleting" class="hidden sm:inline">{{ confirmationText === 'CONFIRMAR' ? 'ELIMINAR CLIENTE' : 'Escribe CONFIRMAR' }}</span>
                    <span v-if="!isDeleting" class="sm:hidden">{{ confirmationText === 'CONFIRMAR' ? 'ELIMINAR' : 'Escribe CONFIRMAR' }}</span>
                    <span v-else>Eliminando...</span>
                </button>
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-600 text-white border-none px-3 sm:px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center justify-center gap-1 sm:gap-2 text-xs sm:text-sm"
                    @click="cancelDelete"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-4 sm:h-5" />
                    <span>Cancelar</span>
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de cambios no guardados -->
    <Dialog
        v-model:visible="isUnsavedChangesVisible"
        header="Cambios sin guardar"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex items-center gap-4 mb-4">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-12 w-12 text-yellow-500" />
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Descartar cambios?</h3>
                <p class="text-gray-600">
                    Tienes cambios sin guardar. ¿Estás seguro de que deseas cerrar sin guardar?
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    class="bg-red-500 hover:bg-red-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="handleCloseWithoutSaving"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-4 w-4" />
                    Descartar cambios
                </button>
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="handleContinueEditing"
                >
                    <FontAwesomeIcon :icon="faPencil" class="h-4 w-4" />
                    Continuar editando
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de detalles del cliente -->
    <Dialog
        v-model:visible="isDetallesVisible"
        header="Detalles del Cliente"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div v-if="cliente" class="space-y-3 sm:space-y-4 p-1 sm:p-0">
            <!-- Alerta para usuarios sin datos completos de cliente -->
            <div v-if="!cliente.id" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 sm:p-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 w-4 sm:h-5 sm:w-5 text-yellow-600 flex-shrink-0" />
                    <div class="text-sm sm:text-base">
                        <h4 class="font-medium text-yellow-800 text-sm sm:text-base">Usuario sin datos de cliente completos</h4>
                        <p class="text-yellow-700 text-xs sm:text-sm mt-1">Este usuario tiene rol Cliente pero no ha completado su información personal.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:gap-4">
                <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 text-sm sm:text-base">Información Personal</h4>
                    <div class="space-y-1 sm:space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                            <span class="font-medium text-xs sm:text-sm">Nombre Completo:</span>
                            <span class="text-xs sm:text-sm break-words">{{ cliente.user?.name || 'No registrado' }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                            <span class="font-medium text-xs sm:text-sm">Género:</span>
                            <span v-if="cliente.genero && cliente.genero !== 'No registrado'"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                :class="cliente.genero === 'MASCULINO' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                {{ cliente.genero }}
                            </span>
                            <span v-else class="text-gray-500 italic text-xs sm:text-sm">{{ cliente.genero || 'No registrado' }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                            <span class="font-medium text-xs sm:text-sm">Fecha de Nacimiento:</span>
                            <span :class="cliente.fecha_nacimiento ? 'text-xs sm:text-sm' : 'text-gray-500 italic text-xs sm:text-sm'">
                                {{ cliente.fecha_nacimiento || 'No registrada' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 text-sm sm:text-base">Información de Contacto</h4>
                    <div class="space-y-2 sm:space-y-3">
                        <!-- Teléfono con WhatsApp y llamada -->
                        <div class="flex flex-col gap-1">
                            <span class="font-medium text-xs sm:text-sm">Teléfono:</span>
                            <div v-if="cliente.telefono && cliente.telefono !== 'No registrado'" class="flex flex-wrap items-center gap-2">
                                <span class="text-xs sm:text-sm">{{ cliente.telefono }}</span>
                                <div class="flex gap-1">
                                    <a
                                        :href="`https://wa.me/${cliente.telefono.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs rounded-md transition-colors duration-200"
                                        title="Enviar mensaje por WhatsApp"
                                    >
                                        <FontAwesomeIcon :icon="faWhatsapp" class="h-3 w-3" />
                                        <span class="hidden sm:inline">WhatsApp</span>
                                    </a>
                                    <a
                                        :href="`tel:${cliente.telefono}`"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md transition-colors duration-200"
                                        title="Llamar por teléfono"
                                    >
                                        <FontAwesomeIcon :icon="faPhone" class="h-3 w-3" />
                                        <span class="hidden sm:inline">Llamar</span>
                                    </a>
                                </div>
                            </div>
                            <span v-else class="text-gray-500 italic text-xs sm:text-sm">No registrado</span>
                        </div>

                        <!-- Correo Electrónico con botón enviar -->
                        <div class="flex flex-col gap-1">
                            <span class="font-medium text-xs sm:text-sm">Correo Electrónico:</span>
                            <div v-if="cliente.user?.email" class="flex flex-wrap items-center gap-2">
                                <span class="text-xs sm:text-sm break-all">{{ cliente.user.email }}</span>
                                <a
                                    @click="abrirGmail(cliente.user.email)"
                                    href="#"
                                    class="inline-flex items-center gap-1 px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md transition-colors duration-200 cursor-pointer"
                                    title="Abrir Gmail para enviar correo"
                                >
                                    <FontAwesomeIcon :icon="faEnvelope" class="h-3 w-3" />
                                    <span class="hidden sm:inline">Enviar Email</span>
                                </a>
                            </div>
                            <span v-else class="text-gray-500 italic text-xs sm:text-sm">No registrado</span>
                        </div>

                        <!-- Dirección -->
                        <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-2">
                            <span class="font-medium text-xs sm:text-sm flex-shrink-0">Dirección:</span>
                            <span :class="cliente.direccion && cliente.direccion !== 'No registrada' ? 'text-xs sm:text-sm' : 'text-gray-500 italic text-xs sm:text-sm'" class="break-words">
                                {{ cliente.direccion || 'No registrada' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 text-sm sm:text-base">Documentación</h4>
                    <div class="space-y-1 sm:space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                            <span class="font-medium text-xs sm:text-sm">Tipo de Documento:</span>
                            <span v-if="cliente.tipo_documento && cliente.tipo_documento !== 'No registrado'"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ cliente.tipo_documento }}
                            </span>
                            <span v-else class="text-gray-500 italic text-xs sm:text-sm">{{ cliente.tipo_documento || 'No registrado' }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                            <span class="font-medium text-xs sm:text-sm">Número de Identificación:</span>
                            <span :class="cliente.numero_identificacion && cliente.numero_identificacion !== 'No registrado' ? 'text-xs sm:text-sm' : 'text-gray-500 italic text-xs sm:text-sm'">
                                {{ cliente.numero_identificacion || 'No registrado' }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <template #footer>
            <div class="flex justify-center w-full mt-3 sm:mt-6 p-2 sm:p-0">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-4 sm:px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-1 sm:gap-2 text-xs sm:text-sm"
                    @click="updateDetallesVisible(false)"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-4 sm:h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>
</template>
