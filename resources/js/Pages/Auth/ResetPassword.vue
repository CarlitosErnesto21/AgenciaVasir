<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\ResetPassword.vue -->
<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEnvelope, faLock, faLightbulb } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
    success: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: '',
    },
});

// Estados para mostrar/ocultar contraseñas
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

// Validaciones reactivas de contraseña
const passwordErrors = computed(() => {
    const errors = [];
    const value = form.password || '';
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

// Validación reactiva para confirmar contraseña
const passwordConfirmationError = computed(() => {
    if (
        form.password_confirmation.length > 0 &&
        form.password !== form.password_confirmation
    ) {
        return 'Las contraseñas no coinciden.';
    }
    return '';
});

// Validación de campos obligatorios
const isFormIncomplete = computed(() => {
    return !form.password || !form.password_confirmation;
});

const submit = () => {
    form.post(route('password.store'), {
        onSuccess: () => {
            // El servidor ya redirige automáticamente al login con un mensaje de éxito
            // Inertia maneja la redirección automáticamente
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <Head title="Restablecer Contraseña" />

        <!-- ✅ Contenedor principal responsive -->
        <div class="w-full max-w-sm sm:max-w-md lg:max-w-lg">
            <!-- ✅ Tarjeta principal -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl p-6 sm:p-8 lg:p-10 border border-gray-100">
                <!-- ✅ Título con logo -->
                <div class="text-center mb-4 sm:mb-6">
                    <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 bg-white rounded-full flex items-center justify-center mb-3 sm:mb-4 shadow-lg border-2 border-green-100">
                        <img
                            src="/images/logo.png"
                            alt="VASIR Logo"
                            class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 object-contain"
                        />
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">
                        Restablecer Contraseña
                    </h2>
                    <p class="text-xs sm:text-sm lg:text-base text-gray-600">
                        Crea tu nueva contraseña segura
                    </p>
                </div>

                <!-- ✅ Mensaje de éxito -->
                <div v-if="success" class="space-y-4 sm:space-y-6">
                    <div class="p-4 sm:p-6 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm sm:text-base font-medium text-green-800 mb-2">
                                    ¡Contraseña Restablecida Exitosamente!
                                </h3>
                                <p class="text-xs sm:text-sm text-green-700 leading-relaxed">
                                    {{ message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <Link
                            :href="route('login')"
                            class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 focus:ring-offset-2 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Ir a Iniciar Sesión
                        </Link>
                    </div>
                </div>

                <!-- ✅ Formulario -->
                <form v-if="!success" @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                    <!-- Email (readonly) -->
                    <div>
                        <InputLabel for="email" class="text-sm sm:text-base font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <FontAwesomeIcon :icon="faEnvelope" class="text-gray-600" />
                                Correo Electrónico
                            </span>
                        </InputLabel>

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-2 block w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg bg-gray-50 text-sm sm:text-base text-black"
                            v-model="form.email"
                            readonly
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Nueva contraseña -->
                    <div>
                        <InputLabel for="password" class="text-sm sm:text-base font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <FontAwesomeIcon :icon="faLock" class="text-gray-600" />
                                Nueva Contraseña
                            </span>
                        </InputLabel>

                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="mt-2 block w-full px-3 sm:px-4 py-2.5 sm:py-3 pr-10 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm sm:text-base text-black"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="Mínimo 8 caracteres"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                </svg>
                            </button>
                        </div>

                        <InputError class="mt-2" :message="form.errors.password" />
                        <div v-if="passwordErrors.length" class="mt-2 text-xs text-red-500 space-y-1">
                            <div v-for="(err, i) in passwordErrors" :key="i">{{ err }}</div>
                        </div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div>
                        <InputLabel for="password_confirmation" class="text-sm sm:text-base font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <FontAwesomeIcon :icon="faLock" class="text-gray-600" />
                                Confirmar Contraseña
                            </span>
                        </InputLabel>

                        <div class="relative">
                            <TextInput
                                id="password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                class="mt-2 block w-full px-3 sm:px-4 py-2.5 sm:py-3 pr-10 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm sm:text-base text-black"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Confirma tu nueva contraseña"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg v-if="!showPasswordConfirmation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                </svg>
                            </button>
                        </div>

                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        <div v-if="passwordConfirmationError" class="mt-2 text-xs text-red-500">
                            {{ passwordConfirmationError }}
                        </div>
                    </div>

                    <!-- ✅ Botón principal -->
                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 focus:ring-offset-2 text-white font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                            :class="{ 'opacity-75 cursor-not-allowed': form.processing || passwordErrors.length || passwordConfirmationError || isFormIncomplete }"
                            :disabled="form.processing || passwordErrors.length || passwordConfirmationError || isFormIncomplete"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <span v-if="form.processing" class="text-sm sm:text-base">Actualizando...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Restablecer Contraseña
                            </span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- ✅ Footer -->
            <div class="text-center mt-4 sm:mt-6">
                <p class="text-xs sm:text-sm text-gray-500">
                    ¿Recordaste tu contraseña?
                    <Link :href="route('login')" class="text-red-600 hover:text-red-700 underline font-medium">
                        Iniciar sesión
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
