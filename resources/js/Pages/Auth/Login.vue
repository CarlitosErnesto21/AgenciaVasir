<script setup>
import { ref, computed, watch } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Register from './Register.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
});

// Estado para mostrar/ocultar contraseña
const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
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

const page = usePage();
const isRegister = ref(window.location.pathname.startsWith('/register'));

// Actualiza el título cuando cambias de formulario
watch(isRegister, (value) => {
    document.title = value ? 'Register - VASIR' : 'Log in - VASIR';
});

const toggleForm = () => {
    if (isRegister.value) {
        window.history.pushState({}, '', '/login');
        isRegister.value = false;
    } else {
        window.history.pushState({}, '', '/register');
        isRegister.value = true;
    }
};
</script>

<template>
    <GuestLayout :is-register="isRegister" :toggle-form="toggleForm">
        <template #login>
            <Head title="Log in" />

            <!-- Mensaje de estado -->
            <div v-if="status" class="mb-4 text-xs sm:text-sm font-medium text-green-600 text-center">
                {{ status }}
            </div>

            <!-- Logo responsive -->
            <div class="flex justify-center mb-4 sm:mb-6">
                <Link href="/">
                    <img src="images/logo.png" alt="Logo"
                         class="h-8 sm:h-10 lg:h-12 w-auto cursor-pointer transition-transform hover:scale-105"
                         title="Ir al Catálogo"/>
                </Link>
            </div>

            <!-- Título para móvil -->
            <div class="text-center mb-4 md:hidden">
                <h1 class="text-xl font-bold text-gray-800">Iniciar Sesión</h1>
            </div>

            <form @submit.prevent="submit" class="w-full max-w-sm mx-auto space-y-4">
                <!-- Campo Email -->
                <div class="space-y-1">
                    <InputLabel for="login-email" value="Correo Electrónico:"
                               class="text-sm font-semibold text-gray-700" />
                    <TextInput
                        id="login-email"
                        type="email"
                        class="mt-1 block w-full text-sm sm:text-base rounded-lg bg-white text-black border border-gray-300 focus:border-red-500 focus:ring-red-500 transition-colors"
                        v-model="form.email"
                        required
                        :autofocus="!isRegister"
                        autocomplete="username"
                        placeholder="correo@ejemplo.com"
                    />
                    <InputError class="mt-1 text-xs" :message="form.errors.email" />
                </div>

                <!-- Campo Contraseña -->
                <div class="space-y-1">
                    <InputLabel for="login-password" value="Contraseña:"
                               class="text-sm font-semibold text-gray-700" />
                    <div class="relative">
                        <TextInput
                            id="login-password"
                            :type="showPassword ? 'text' : 'password'"
                            class="mt-1 block w-full text-sm sm:text-base pr-10 rounded-lg bg-white text-black border border-gray-300 focus:border-red-500 focus:ring-red-500 transition-colors"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="Tu contraseña"
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
                </div>

                <!-- Botón de envío -->
                <div class="pt-4 flex justify-center">
                    <PrimaryButton
                        class="px-8 sm:px-12 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 focus:ring-red-500 text-white font-semibold py-2.5 sm:py-3 text-sm sm:text-base rounded-lg transition-all duration-300 transform hover:scale-[1.02] focus:scale-[1.02] shadow-lg hover:shadow-xl"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing">
                        <span v-if="form.processing" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Ingresando...
                        </span>
                        <span v-else>Iniciar Sesión</span>
                    </PrimaryButton>
                </div>

                <!-- Enlace de recuperación -->
                <div class="text-center pt-2">
                    <Link
                        :href="route('password.request')"
                        class="text-xs sm:text-sm text-red-600 hover:text-red-800 underline font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>
            </form>
        </template>
        <template #register>
            <Register :is-register="isRegister" />
        </template>
    </GuestLayout>
</template>
