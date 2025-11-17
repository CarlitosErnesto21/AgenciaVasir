<script setup>

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faKey, faEnvelope, faEye, faTrashCan, faXmark, faSpinner, faUsers, faPencil } from "@fortawesome/free-solid-svg-icons";
import { ref, computed } from "vue";

// Props recibidas desde el componente padre
const props = defineProps({
    visible: Boolean,
    deleteVisible: Boolean,
    unsavedChangesVisible: Boolean,
    passwordVisible: Boolean,
    empleado: Object,
    usuario: Object, // Nuevo: usuario asociado
    detailsVisible: Boolean, // Nuevo: visibilidad del modal de detalles
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
    'update:password-visible',
    'update:details-visible', // Nuevo: para el modal de detalles
    'change-password',
    'send-credentials',
    'view-details',
    'delete-empleado',
    'cancel-delete',
    'close-without-saving',
    'continue-editing',
    'update-password'
]);
// Computed para el v-model del modal de detalles
const isDetailsVisible = computed({
    get: () => props.detailsVisible,
    set: (value) => emit('update:details-visible', value)
});

// Variables reactivas locales
const passwordData = ref({
    password: '',
    password_confirmation: ''
});

// Validaciones de contraseña
const passwordErrors = computed(() => {
    const errors = [];
    const value = passwordData.value.password || '';
    if (value.length > 0 && value.length < 8) {
        errors.push('La contraseña debe tener al menos 8 caracteres.');
    }
    if (value.length > 0 && !/[A-Z]/.test(value)) {
        errors.push('La contraseña debe incluir al menos una letra mayúscula.');
    }
    if (value.length > 0 && !/[0-9]/.test(value)) {
        errors.push('La contraseña debe incluir al menos un número.');
    }
    if (value.length > 0 && /[\s.]/.test(value)) {
        errors.push('La contraseña no puede contener espacios ni puntos.');
    }
    return errors;
});

// Validación de confirmación de contraseña
const passwordConfirmationError = computed(() => {
    if (
        passwordData.value.password_confirmation.length > 0 &&
        passwordData.value.password !== passwordData.value.password_confirmation
    ) {
        return 'Las contraseñas no coinciden.';
    }
    return '';
});

// Función para resetear el formulario de contraseña
const resetPasswordForm = () => {
    passwordData.value = {
        password: '',
        password_confirmation: ''
    };
};

// Funciones para manejar eventos
const handleChangePassword = () => {
    emit('change-password', props.empleado);
};

const handleSendCredentials = () => {
    emit('send-credentials', props.empleado);
};

const handleViewDetails = () => {
    emit('view-details', props.empleado);
};

const handleDeleteEmpleado = () => {
    emit('delete-empleado');
};

const handleCancelDelete = () => {
    emit('cancel-delete');
};

const handleCloseWithoutSaving = () => {
    emit('close-without-saving');
};

const handleContinueEditing = () => {
    emit('continue-editing');
};

const handleUpdatePassword = () => {
    // Validar antes de emitir
    if (!passwordData.value.password || passwordData.value.password !== passwordData.value.password_confirmation) {
        return;
    }
    emit('update-password', passwordData.value);
};

// Watchers para sincronizar con el componente padre
const updateVisible = (value) => {
    emit('update:visible', value);
};

const updateDeleteVisible = (value) => {
    emit('update:delete-visible', value);
};

const updateUnsavedChangesVisible = (value) => {
    emit('update:unsaved-changes-visible', value);
};

const updatePasswordVisible = (value) => {
    emit('update:password-visible', value);
    if (!value) {
        resetPasswordForm();
    }
};
</script>

<template>
        <!-- Modal de Detalles de Usuario y Empleado -->
        <Dialog
            v-model:visible="isDetailsVisible"
            header="Detalles del Empleado"
            :modal="true"
            :style="dialogStyle"
            :closable="false"
            :draggable="false"
        >
            <div v-if="props.usuario || props.empleado" class="space-y-6">
                <!-- Detalles de Usuario -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faUsers" class="h-5 w-5 text-blue-600" />
                        Usuario
                    </h3>
                    <div class="flex flex-col gap-2">
                        <div>
                            <span class="font-medium text-gray-700">Nombre:</span>
                            <span class="ml-2 text-gray-900">{{ props.usuario?.name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Email:</span>
                            <span class="ml-2 text-gray-900">{{ props.usuario?.email ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Rol:</span>
                            <span class="ml-2 text-gray-900 break-words">
                                <template v-if="Array.isArray(props.usuario?.roles) && props.usuario.roles.length">
                                    <span v-for="(rol, idx) in props.usuario.roles" :key="rol.id || idx" class="inline-block bg-blue-100 text-blue-800 rounded px-2 py-0.5 text-xs font-semibold mr-1 mb-1">
                                        {{ rol.name || rol }}
                                    </span>
                                </template>
                                <template v-else>
                                    -
                                </template>
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Cargo:</span>
                            <span class="ml-2 text-gray-900">{{ props.empleado?.cargo ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Teléfono:</span>
                            <span class="ml-2 text-gray-900">{{ props.empleado?.telefono ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center text-gray-500 py-8">No hay datos para mostrar.</div>
            <template #footer>
                <div class="flex justify-center w-full mt-6">
                    <button
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="isDetailsVisible = false"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        Cerrar
                    </button>
                </div>
            </template>
        </Dialog>
    <!-- Modal de Más Acciones -->
    <Dialog
        :visible="visible"
        @update:visible="updateVisible"
        header="Más Acciones"
        :modal="true"
        :style="dialogStyle"
        :closable="true"
        :draggable="false"
    >
        <div class="space-y-3">
            <button
                @click="handleChangePassword"
                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-blue-50 rounded-lg transition-colors duration-200 border border-gray-200 hover:border-blue-300"
            >
                <FontAwesomeIcon :icon="faKey" class="h-5 w-5 text-blue-600" />
                <div>
                    <div class="font-medium text-gray-900">Cambiar Contraseña</div>
                    <div class="text-sm text-gray-500">Actualizar la contraseña de acceso</div>
                </div>
            </button>

            <button
                @click="handleViewDetails"
                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-purple-50 rounded-lg transition-colors duration-200 border border-gray-200 hover:border-purple-300"
            >
                <FontAwesomeIcon :icon="faEye" class="h-5 w-5 text-purple-600" />
                <div>
                    <div class="font-medium text-gray-900">Ver Detalles</div>
                    <div class="text-sm text-gray-500">Información completa del empleado</div>
                </div>
            </button>
        </div>
    </Dialog>

    <!-- Modal de confirmación de eliminación -->
    <Dialog
        :visible="deleteVisible"
        @update:visible="updateDeleteVisible"
        header="Confirmar Eliminación"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex items-center gap-4 mb-4">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-12 w-12 text-red-500" />
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Eliminar empleado?</h3>
                <p class="text-gray-600">
                    ¿Estás seguro de que deseas eliminar al empleado
                    <strong>"{{ empleado?.nombre }}"</strong>?
                </p>
                <p class="text-sm text-red-600 mt-2">
                    Esta acción no se puede deshacer y eliminará tanto el empleado como su usuario asociado.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-4 w-full">
                <button
                    class="bg-red-500 hover:bg-red-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleDeleteEmpleado"
                    :disabled="isDeleting"
                >
                    <FontAwesomeIcon
                        :icon="isDeleting ? faSpinner : faTrashCan"
                        :class="[
                            'h-4 w-4',
                            { 'animate-spin': isDeleting }
                        ]"
                    />
                    <span>{{ isDeleting ? 'Eliminando...' : 'Eliminar' }}</span>
                </button>
                <button
                    type="button"
                    class="bg-gray-500 hover:bg-gray-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="handleCancelDelete"
                    :disabled="isDeleting"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-4 w-4" />
                    Cancelar
                </button>
            </div>
        </template>
    </Dialog>

    <!-- Modal de cambios no guardados -->
    <Dialog
        :visible="unsavedChangesVisible"
        @update:visible="updateUnsavedChangesVisible"
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

    <!-- Modal de cambio de contraseña -->
    <Dialog
        :visible="passwordVisible"
        @update:visible="updatePasswordVisible"
        header="Cambiar Contraseña"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="space-y-4">
            <div class="text-sm text-gray-600 mb-4">
                Actualizar la contraseña para: <strong>{{ empleado?.nombre }}</strong>
            </div>

            <!-- Nueva Contraseña -->
            <div class="w-full flex flex-col">
                <div class="flex items-center gap-4">
                    <label for="new_password" class="w-32 flex items-center gap-1">
                        Nueva contraseña: <span class="text-red-500 font-bold">*</span>
                    </label>
                    <Password
                        v-model="passwordData.password"
                        id="new_password"
                        name="new_password"
                        :class="{'p-invalid': props.submitted && passwordErrors.length > 0}"
                        class="flex-1"
                        placeholder="Nueva contraseña"
                        toggleMask
                        :feedback="false"
                        inputClass="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                    />
                </div>
                <div v-if="passwordErrors.length > 0" class="ml-36 mt-1">
                    <small v-for="error in passwordErrors" :key="error" class="block text-red-500 text-xs">
                        {{ error }}
                    </small>
                </div>
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div class="w-full flex flex-col">
                <div class="flex items-center gap-4">
                    <label for="confirm_password" class="w-32 flex items-center gap-1">
                        Confirmar: <span class="text-red-500 font-bold">*</span>
                    </label>
                    <Password
                        v-model="passwordData.password_confirmation"
                        id="confirm_password"
                        name="confirm_password"
                        :class="{'p-invalid': props.submitted && passwordConfirmationError}"
                        class="flex-1"
                        placeholder="Repetir nueva contraseña"
                        toggleMask
                        :feedback="false"
                        inputClass="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                    />
                </div>
                <small v-if="passwordConfirmationError" class="text-red-500 ml-36 mt-1">
                    {{ passwordConfirmationError }}
                </small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-4 w-full mt-6">
                <button
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleUpdatePassword"
                    :disabled="isLoading"
                >
                    <FontAwesomeIcon
                        :icon="isLoading ? faSpinner : faCheck"
                        :class="[
                            'h-5 text-white',
                            { 'animate-spin': isLoading }
                        ]"
                    />
                    <span v-if="!isLoading">Actualizar</span>
                    <span v-else>Actualizando...</span>
                </button>
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="updatePasswordVisible(false)"
                    :disabled="isLoading"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cancelar
                </button>
            </div>
        </template>
    </Dialog>
</template>
