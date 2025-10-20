<script setup>
// Validación de campos obligatorios
const isFormIncomplete = computed(() => {
    return !form.name || !form.email || !form.password || !form.password_confirmation;
});
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    isRegister: Boolean
});

// Estados para mostrar/ocultar contraseñas
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);


const form = useForm({
    name: '',
    email: '',
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

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onSuccess: () => {
            // Verificar si hay una reserva pendiente ACTIVA de esta sesión
            const reservaPendiente = sessionStorage.getItem('tour_reserva_pendiente');
            const sessionActiva = sessionStorage.getItem('reserva_session_activa');

            if (reservaPendiente && sessionActiva === 'true') {
                const tourInfo = JSON.parse(reservaPendiente);
                // NO limpiar aquí - dejar que la vista de destino lo maneje
                // Redirigir a la vista original
                router.visit(tourInfo.returnUrl);
                return;
            }

            // Verificar si hay un producto pendiente ACTIVO de esta sesión
            const productoPendiente = sessionStorage.getItem('producto_compra_pendiente');
            const sessionActivaProducto = sessionStorage.getItem('compra_session_activa');

            if (productoPendiente && sessionActivaProducto === 'true') {
                const productoInfo = JSON.parse(productoPendiente);
                // NO limpiar aquí - dejar que la vista de destino lo maneje
                // Redirigir a la vista original
                router.visit(productoInfo.returnUrl);
                return;
            }

            // Si no hay reserva o compra pendiente activa, limpiar cualquier dato residual
            if (!sessionActiva || sessionActiva !== 'true') {
                sessionStorage.removeItem('tour_reserva_pendiente');
                sessionStorage.removeItem('reserva_session_activa');
            }

            if (!sessionActivaProducto || sessionActivaProducto !== 'true') {
                sessionStorage.removeItem('producto_compra_pendiente');
                sessionStorage.removeItem('compra_session_activa');
            }
        }
    });
};
</script>

<template>
    <Head title="Register" />

    <!-- Logo responsive -->
    <div class="flex justify-center mb-4 sm:mb-6">
        <Link href="/">
            <img src="images/logo.png" alt="Logo"
                 class="h-8 sm:h-10 lg:h-12 w-auto cursor-pointer transition-transform hover:scale-105"
                 title="Ir al catálogo"/>
        </Link>
    </div>

    <!-- Título para móvil -->
    <div class="text-center mb-3 md:hidden">
        <h1 class="text-xl font-bold text-gray-800">Crear Cuenta</h1>
    </div>

    <form @submit.prevent="submit" class="w-full max-w-sm mx-auto space-y-3" novalidate>
            <!-- Campo Nombre -->
            <div class="space-y-1">
                <InputLabel for="register-name" value="Nombre Completo:"
                           class="text-sm font-semibold text-gray-700"/>
                <TextInput
                    id="register-name"
                    type="text"
                    class="mt-1 block w-full text-sm sm:text-base rounded-lg bg-white text-black border border-gray-300 focus:border-red-500 focus:ring-red-500 transition-colors"
                    v-model="form.name"
                    required
                    :autofocus="isRegister"
                    autocomplete="name"
                    placeholder="Tu nombre completo"
                />
                <InputError class="mt-1 text-xs" :message="form.errors.name" />
            </div>

            <!-- Campo Email -->
            <div class="space-y-1">
                <InputLabel for="register-email" value="Correo Electrónico:"
                           class="text-sm font-semibold text-gray-700"/>
                <TextInput
                    id="register-email"
                    type="text"
                    v-model="form.email"
                    class="mt-1 block w-full text-sm sm:text-base rounded-lg bg-white text-black border transition-colors"
                    required
                    autocomplete="username"
                    placeholder="correo@ejemplo.com"
                />
                <InputError class="mt-1 text-xs" :message="form.errors.email" />
            </div>

            <!-- Campo Contraseña -->
            <div class="space-y-1">
                <InputLabel for="register-password" value="Contraseña:"
                           class="text-sm font-semibold text-gray-700"/>
                <div class="relative">
                    <TextInput
                        id="register-password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full text-sm sm:text-base pr-10 rounded-lg bg-white text-black border border-gray-300 focus:border-red-500 focus:ring-red-500 transition-colors"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Crea una contraseña segura"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                        :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                        <svg v-if="!showPassword" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                        </svg>
                    </button>
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.password" />
                <div v-if="passwordErrors.length" class="mt-1 text-xs text-red-500 space-y-1">
                    <div v-for="(err, i) in passwordErrors" :key="i">{{ err }}</div>
                </div>
            </div>

            <!-- Campo Confirmar Contraseña -->
            <div class="space-y-1">
                <InputLabel
                    for="register-password_confirmation"
                    value="Confirmar Contraseña:"
                    class="text-sm font-semibold text-gray-700"
                />
                <div class="relative">
                    <TextInput
                        id="register-password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        class="mt-1 block w-full text-sm sm:text-base pr-10 rounded-lg bg-white text-black border border-gray-300 focus:border-red-500 focus:ring-red-500 transition-colors"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repite tu contraseña"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                        :title="showPasswordConfirmation ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                        <svg v-if="!showPasswordConfirmation" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                        </svg>
                    </button>
                </div>
                <InputError
                    class="mt-1 text-xs"
                    :message="form.errors.password_confirmation"
                />
                <div v-if="passwordConfirmationError" class="mt-1 text-xs text-red-500">
                    {{ passwordConfirmationError }}
                </div>
            </div>

            <!-- Botón de envío -->
            <div class="pt-2 flex justify-center">
                <PrimaryButton
                    class="px-8 sm:px-12 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 focus:ring-red-500 text-white font-semibold py-2.5 sm:py-3 text-sm sm:text-base rounded-lg transition-all duration-300 transform hover:scale-[1.02] focus:scale-[1.02] shadow-lg hover:shadow-xl"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing || passwordErrors.length || passwordConfirmationError || isFormIncomplete }"
                    :disabled="form.processing || passwordErrors.length || passwordConfirmationError || isFormIncomplete"
                >
                    <span v-if="form.processing" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creando cuenta...
                    </span>
                    <span v-else>Crear Cuenta</span>
                </PrimaryButton>
            </div>
        </form>
</template>
