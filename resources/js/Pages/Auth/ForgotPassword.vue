<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\ForgotPassword.vue -->
<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <Head title="Recuperar Contraseña" />

        <!-- ✅ Contenedor principal responsive -->
        <div class="w-full max-w-sm sm:max-w-md lg:max-w-lg">
            <!-- ✅ Logo centrado -->
            <div class="flex justify-center mb-6 sm:mb-8">
                <Link href="/">
                    <img
                        src="images/logo.png"
                        alt="VASIR Logo"
                        class="h-8 sm:h-10 lg:h-12 w-auto cursor-pointer hover:scale-105 transition-transform duration-200"
                        title="Ir al inicio"
                    />
                </Link>
            </div>

            <!-- ✅ Tarjeta principal -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl p-6 sm:p-8 lg:p-10 border border-gray-100">
                <!-- ✅ Título con icono -->
                <div class="text-center mb-4 sm:mb-6">
                    <div class="mx-auto w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-red-100 rounded-full flex items-center justify-center mb-3 sm:mb-4">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">
                        ¿Olvidaste tu contraseña?
                    </h2>
                    <p class="text-xs sm:text-sm lg:text-base text-gray-600">
                        No te preocupes, te ayudamos a recuperarla
                    </p>
                </div>

                <!-- ✅ Mensaje informativo -->
                <div class="mb-4 sm:mb-6">
                    <div class="p-3 sm:p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r-lg">
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm lg:text-base text-blue-800 leading-relaxed">
                                    Ingresa tu dirección de correo electrónico y te enviaremos un enlace
                                    para restablecer tu contraseña.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ Mensaje de éxito -->
                    <div
                        v-if="status"
                        class="mt-3 sm:mt-4 p-3 sm:p-4 bg-green-50 border-l-4 border-green-400 rounded-r-lg"
                    >
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-medium text-green-800">
                                    ✅ Enlace de recuperación enviado
                                </p>
                                <p class="text-xs text-green-700 mt-1">
                                    Revisa tu email y sigue las instrucciones
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Formulario -->
                <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                    <div>
                        <InputLabel for="email" value="📧 Correo Electrónico" class="text-sm sm:text-base font-medium text-gray-700" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-2 block w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm sm:text-base"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="tu@email.com"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- ✅ Botones -->
                    <div class="space-y-3 sm:space-y-4">
                        <PrimaryButton
                            class="w-full bg-red-600 hover:bg-red-700 focus:ring-red-500 focus:ring-offset-2 text-white font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                            :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <span v-if="form.processing" class="text-sm sm:text-base">Enviando...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Enviar Enlace de Recuperación
                            </span>
                        </PrimaryButton>

                        <!-- Separador -->
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-xs sm:text-sm">
                                <span class="px-2 bg-white text-gray-500">o</span>
                            </div>
                        </div>

                        <!-- Volver al login -->
                        <Link
                            :href="route('login')"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver al Inicio de Sesión
                        </Link>
                    </div>
                </form>
            </div>

            <!-- ✅ Footer -->
            <div class="text-center mt-4 sm:mt-6">
                <p class="text-xs sm:text-sm text-gray-500">
                    ¿No tienes cuenta?
                    <Link :href="route('register')" class="text-red-600 hover:text-red-700 underline font-medium">
                        Regístrate aquí
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
