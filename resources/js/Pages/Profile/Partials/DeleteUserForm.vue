<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, computed, onMounted, onUnmounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faExclamationTriangle } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
    rol: {
        type: String,
        default: '',
    },
});

// Computed para determinar si es un cliente
const isClient = computed(() => {
    return props.rol === 'Cliente';
});

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

// Estado para mostrar/ocultar contraseña
const showPassword = ref(false);

// Sistema de toast
const toast = useToast();

// Clave para localStorage del cooldown
const COOLDOWN_STORAGE_KEY = 'account_deletion_cooldown';

// Variables para el cooldown del botón
const cooldownActive = ref(false);
const cooldownSeconds = ref(0);
let cooldownInterval = null;

// Función para iniciar el cooldown de 5 minutos
const startCooldown = () => {
    const endTime = Date.now() + (5 * 60 * 1000); // 5 minutos desde ahora
    localStorage.setItem(COOLDOWN_STORAGE_KEY, endTime.toString());

    cooldownActive.value = true;
    updateCooldownFromStorage();
};

// Función para actualizar el cooldown desde localStorage
const updateCooldownFromStorage = () => {
    const endTime = localStorage.getItem(COOLDOWN_STORAGE_KEY);

    if (!endTime) {
        cooldownActive.value = false;
        cooldownSeconds.value = 0;
        return;
    }

    const remaining = Math.max(0, Math.ceil((parseInt(endTime) - Date.now()) / 1000));

    if (remaining <= 0) {
        // El cooldown ha expirado
        localStorage.removeItem(COOLDOWN_STORAGE_KEY);
        cooldownActive.value = false;
        cooldownSeconds.value = 0;
        if (cooldownInterval) {
            clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
        return;
    }

    // Aún hay tiempo restante
    cooldownActive.value = true;
    cooldownSeconds.value = remaining;

    // Iniciar o continuar el interval
    if (!cooldownInterval) {
        cooldownInterval = setInterval(() => {
            updateCooldownFromStorage();
        }, 1000);
    }
};

// Función para verificar y restaurar el cooldown al montar el componente
const checkExistingCooldown = () => {
    updateCooldownFromStorage();
};

// Función para formatear segundos a minutos:segundos
const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds.toString().padStart(2, '0')}s`;
};

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    if (props.disabled || (isClient.value && cooldownActive.value)) return;
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value && passwordInput.value.focus());
};

const deleteUser = () => {
    if (props.disabled) return;

    // Si es cliente, enviar solicitud de eliminación
    if (isClient.value) {
        form.post(route('profile.request-deletion'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                // Mostrar toast de éxito
                toast.add({
                    severity: 'success',
                    summary: 'Solicitud Enviada',
                    detail: 'Tu solicitud de eliminación ha sido enviada al equipo de la Agencia VASIR. Recibirás una respuesta pronto.',
                    life: 6000
                });
                // Iniciar cooldown de 5 minutos
                startCooldown();
            },
            onError: () => {
                passwordInput.value && passwordInput.value.focus();
                // Mostrar toast de error
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Error al enviar la solicitud. Por favor, intenta de nuevo.',
                    life: 4000
                });
            },
            onFinish: () => form.reset(),
        });
    } else {
        // Para otros roles, eliminar directamente
        form.delete(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.value && passwordInput.value.focus(),
            onFinish: () => form.reset(),
        });
    }
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};

// Verificar cooldown existente al montar el componente
onMounted(() => {
    checkExistingCooldown();
});

// Limpiar el interval cuando el componente se desmonte
onUnmounted(() => {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
        cooldownInterval = null;
    }
});
</script>

<template>
    <section class="bg-white/90 rounded-2xl shadow-lg border border-gray-100 p-4 sm:p-6 md:p-8">
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 sm:p-5 mb-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                <div class="flex-shrink-0 flex justify-center sm:block mb-2 sm:mb-0">
                    <div class="bg-red-100 rounded-full p-2 mx-auto">
                        <FontAwesomeIcon :icon="faExclamationTriangle" class="w-5 h-5 text-red-600" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-900 mb-2">Zona de Peligro</h3>
                    <p class="text-red-800 text-sm leading-relaxed mb-3">
                        <template v-if="isClient">
                            Esta es irreversible. Al enviar la solicitud de eliminación, se notificará a nuestro equipo para que procese la eliminación de tu cuenta.
                        </template>
                        <template v-else>
                            Una vez eliminada tu cuenta, todos los recursos y datos serán eliminados permanentemente.
                        </template>
                    </p>
                    <div class="bg-white bg-opacity-60 rounded-md p-3 border-l-2 border-red-500">
                        <p class="text-red-900 font-medium text-xs">
                            <template v-if="disabled">
                                <template v-if="rol === 'Administrador'">
                                    Para eliminar tu cuenta, por favor ponte en contacto con soporte. Siendo Administrador no puedes eliminar tu cuenta directamente desde esta interfaz por fines de seguridad.
                                </template>
                                <template v-else-if="rol === 'Empleado'">
                                    Para eliminar tu cuenta, por favor ponte en contacto con el Administrador de tu empresa.
                                </template>
                            </template>
                            <template v-else>
                                <template v-if="isClient">
                                    Nuestro equipo revisará tu solicitud y te contactará si es necesario.
                                    <span v-if="cooldownActive" class="block mt-2 text-xs text-red-600 font-medium">
                                        Debes esperar {{ formatTime(cooldownSeconds) }} antes de enviar otra solicitud.
                                    </span>
                                </template>
                                <template v-else>
                                    Esta acción no se puede deshacer. Todos tus datos se perderán permanentemente.
                                </template>
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-center mt-2">
            <DangerButton
                @click="confirmUserDeletion"
                :disabled="props.disabled || (isClient && cooldownActive)"
                :class="[
                    'inline-flex items-center px-6 sm:px-7 py-3 text-white text-base font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors shadow-md w-full sm:w-auto',
                    cooldownActive && isClient ? 'bg-gray-500 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700',
                    'disabled:opacity-50 disabled:cursor-not-allowed'
                ]"
            >
                <template v-if="cooldownActive && isClient">
                    <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
                    </svg>
                    Esperar {{ formatTime(cooldownSeconds) }}
                </template>
                <template v-else>
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!isClient" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ isClient ? 'Enviar Solicitud de Eliminación' : 'Eliminar Mi Cuenta' }}
                </template>
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-auto">
                <!-- Header del modal -->
                <div class="bg-red-600 rounded-t-2xl px-4 sm:px-8 py-6">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto sm:mx-0">
                            <FontAwesomeIcon :icon="faExclamationTriangle" class="w-6 h-6 text-white" />
                        </div>
                        <div class="text-center sm:text-left">
                            <h2 class="text-2xl font-bold text-white">{{ isClient ? 'Solicitar Eliminación' : 'Confirmar Eliminación' }}</h2>
                            <p class="text-red-100 text-base">{{ isClient ? 'Enviar solicitud al administrador' : 'Esta acción es irreversible' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contenido del modal -->
                <div class="p-4 sm:p-8">
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 sm:p-5 mb-8 rounded-xl shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                            <div class="flex-shrink-0 flex justify-center sm:block mb-2 sm:mb-0">
                                <FontAwesomeIcon :icon="faExclamationTriangle" class="w-5 h-5 text-red-500 mt-0.5" />
                            </div>
                            <div>
                                <p class="text-red-800 text-base leading-relaxed">
                                    <template v-if="isClient">
                                        Se enviará una solicitud al administrador para eliminar tu cuenta.
                                        Una vez procesada, todos tus recursos y datos serán eliminados permanentemente.
                                        Ingresa tu contraseña para confirmar el envío de la solicitud.
                                    </template>
                                    <template v-else>
                                        Una vez eliminada tu cuenta, todos los recursos y datos serán eliminados permanentemente.
                                        Ingresa tu contraseña para confirmar esta acción.
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <InputLabel for="password" value="Confirma con tu contraseña:" class="text-gray-700 font-semibold text-base" />

                        <div class="relative">
                            <TextInput
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full border border-red-300 rounded-xl px-4 py-3 pr-12 text-gray-900 text-base shadow-sm
                                       focus:border-red-500 focus:ring-2 focus:ring-red-100
                                       hover:border-red-400 transition-colors
                                       bg-red-50 focus:bg-white placeholder-gray-500"
                                placeholder="Tu contraseña actual"
                                @keyup.enter="deleteUser"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-red-400 hover:text-red-600 transition-colors"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                </svg>
                            </button>
                        </div>

                        <InputError :message="form.errors.password" class="text-red-600 text-sm" />
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-8 border-t border-gray-100 mt-8">
                        <SecondaryButton
                            @click="closeModal"
                            class="px-5 py-2.5 bg-gray-100 text-gray-700 text-base font-semibold rounded-lg
                                   hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300
                                   transition-colors border border-gray-300 w-full sm:w-auto"
                        >
                            Cancelar
                        </SecondaryButton>

                        <DangerButton
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                            @click="deleteUser"
                            class="px-5 py-2.5 bg-red-600 text-white text-base font-semibold rounded-lg
                                   hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500
                                   disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition-colors w-full sm:w-auto"
                        >
                            {{ form.processing ? (isClient ? 'Enviando solicitud...' : 'Eliminando...') : (isClient ? 'Sí, Enviar Solicitud' : 'Sí, Eliminar') }}
                        </DangerButton>
                    </div>
                </div>
            </div>
        </Modal>
    </section>
</template>
