<script setup>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faEye, faTrashCan, faXmark, faSpinner, faUsers, faPencil, faUserEdit, faEnvelope, faFileText, faCalendarDays, faBagShopping } from "@fortawesome/free-solid-svg-icons";
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

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
    'send-email',
    'toggle-status',
    'delete-cliente',
    'cancel-delete',
    'close-without-saving',
    'continue-editing'
]);

// Funciones para manejar eventos
const handleViewDetails = () => {
    isVisible.value = false; // Cerrar modal de más acciones
    emit('view-details', props.cliente);
};

const handleSendEmail = () => {
    isVisible.value = false; // Cerrar modal de más acciones
    emit('send-email', props.cliente);
};

const handleToggleStatus = () => {
    emit('toggle-status', props.cliente);
};

const handleDeleteCliente = () => {
    emit('delete-cliente');
};

const handleCancelDelete = () => {
    emit('cancel-delete');
};

// Funciones específicas para el modal de eliminar
const confirmDelete = () => {
    emit('delete-cliente', props.cliente);
};

const cancelDelete = () => {
    emit('cancel-delete');
    // También cerrar el modal directamente
    isDeleteVisible.value = false;
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

// Computed properties para v-model
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

                <!-- Visualizar Reservas -->
                <Link
                    :href="route('clientes.reservas', { cliente: cliente.id })"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="closeModal"
                >
                    <FontAwesomeIcon :icon="faCalendarDays" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Visualizar Reservas</div>
                        <div class="text-xs opacity-90">Historial de reservas del cliente</div>
                    </div>
                </Link>

                <!-- Visualizar Compras -->
                <Link
                    :href="route('clientes.ventas', { cliente: cliente.id })"
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="closeModal"
                >
                    <FontAwesomeIcon :icon="faBagShopping" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Visualizar Compras</div>
                        <div class="text-xs opacity-90">Historial de compras del cliente</div>
                    </div>
                </Link>

                <!-- Enviar Email -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
                    @click="handleSendEmail"
                >
                    <FontAwesomeIcon :icon="faEnvelope" class="h-5 w-5" />
                    <div class="text-left flex-1">
                        <div class="font-medium">Enviar Email</div>
                        <div class="text-xs opacity-90">Mensaje personalizado al cliente</div>
                    </div>
                </button>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
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

    <!-- Modal de confirmación de eliminación -->
        <!-- Modal de Eliminar Cliente -->
    <Dialog v-model:visible="isDeleteVisible" header="Eliminar cliente" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
        <div class="flex items-center gap-3">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
            <div class="flex flex-col">
                <span>¿Estás seguro de eliminar el cliente: <b>{{ cliente.user?.name }}</b>?</span>
                <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="confirmDelete"
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
                    @click="cancelDelete" :disabled="isDeleting">
                    <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cancelar</span>
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
        <div v-if="cliente" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-3">Información Personal</h4>
                    <div class="space-y-2">
                        <p><strong>Nombre Completo:</strong> {{ cliente.user?.name || 'No registrado' }}</p>
                        <p><strong>Género:</strong>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2"
                                :class="cliente.genero === 'MASCULINO' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                {{ cliente.genero }}
                            </span>
                        </p>
                        <p><strong>Fecha de Nacimiento:</strong> {{ cliente.fecha_nacimiento }}</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-3">Información de Contacto</h4>
                    <div class="space-y-2">
                        <p><strong>Teléfono:</strong> {{ cliente.telefono }}</p>
                        <p><strong>Correo Electrónico:</strong> {{ cliente.user?.email || 'No registrado' }}</p>
                        <p><strong>Dirección:</strong> {{ cliente.direccion }}</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-3">Documentación</h4>
                    <div class="space-y-2">
                        <p><strong>Tipo de Documento:</strong>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                {{ cliente.tipo_documento?.nombre || 'No registrado' }}
                            </span>
                        </p>
                        <p><strong>Número de Identificación:</strong> {{ cliente.numero_identificacion }}</p>
                    </div>
                </div>


            </div>
        </div>

        <template #footer>
            <div class="flex justify-center w-full mt-6">
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="updateDetallesVisible(false)"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cerrar
                </button>
            </div>
        </template>
    </Dialog>
</template>
