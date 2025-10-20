<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\VerifyEmail.vue -->
<script setup>
import { computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
    email: {
        type: String,
    },
});

const form = useForm({
    email: props.email,
});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <Head title="Verificación de Email" />

        <!-- ✅ Contenedor principal responsive -->
        <div class="w-full max-w-sm sm:max-w-md lg:max-w-lg xl:max-w-xl">
            <!-- ✅ Logo centrado responsive -->
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

            <!-- ✅ Tarjeta principal responsive -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl p-6 sm:p-8 lg:p-10 border border-gray-100">
                <!-- ✅ Título con icono responsive -->
                <div class="text-center mb-4 sm:mb-6">
                    <div class="mx-auto w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-red-100 rounded-full flex items-center justify-center mb-3 sm:mb-4">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">
                        Verificación de Email
                    </h2>
                    <p class="text-xs sm:text-sm lg:text-base text-gray-600">
                        Confirma tu dirección de correo electrónico
                    </p>
                </div>

                <!-- ✅ Mensaje principal responsive -->
                <div class="mb-4 sm:mb-6">
                    <div class="p-3 sm:p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r-lg mb-3 sm:mb-4">
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm lg:text-base text-blue-800 leading-relaxed">
                                    ¡Gracias por registrarte en <strong class="text-red-600">VASIR</strong>!
                                    Hemos enviado un enlace de verificación a tu correo electrónico.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ Mensaje de éxito responsive -->
                    <div
                        v-if="verificationLinkSent"
                        class="p-3 sm:p-4 bg-green-50 border-l-4 border-green-400 rounded-r-lg"
                    >
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-medium text-green-800">
                                    ✅ Nuevo enlace enviado correctamente
                                </p>
                                <p class="text-xs text-green-700 mt-1">
                                    Revisa tu bandeja de entrada y spam
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Formulario responsive -->
                <form @submit.prevent="submit" class="space-y-3 sm:space-y-4">
                    <!-- Botón principal responsive -->
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
                            <span class="hidden sm:inline">Reenviar Email de Verificación</span>
                            <span class="sm:hidden">Reenviar Email</span>
                        </span>
                    </PrimaryButton>

                    <!-- Separador responsive -->
                    <div class="relative my-4 sm:my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-xs sm:text-sm">
                            <span class="px-2 bg-white text-gray-500">o</span>
                        </div>
                    </div>

                    <!-- Botón logout responsive -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </Link>
                </form>

                <!-- ✅ Ayuda expandible responsive -->
                <details class="mt-4 sm:mt-6">
                    <summary class="cursor-pointer text-xs sm:text-sm font-medium text-gray-700 hover:text-gray-900 flex items-center">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ¿No encuentras el email?
                    </summary>
                    <div class="mt-2 sm:mt-3 pl-4 sm:pl-6 space-y-1.5 sm:space-y-2">
                        <div class="flex items-start text-xs sm:text-xs lg:text-sm text-gray-600">
                            <span class="text-red-500 mr-1.5 sm:mr-2 flex-shrink-0">•</span>
                            <span>Revisa tu carpeta de <strong>spam</strong> o correo no deseado</span>
                        </div>
                        <div class="flex items-start text-xs sm:text-xs lg:text-sm text-gray-600">
                            <span class="text-red-500 mr-1.5 sm:mr-2 flex-shrink-0">•</span>
                            <span>Verifica que escribiste correctamente tu email</span>
                        </div>
                        <div class="flex items-start text-xs sm:text-xs lg:text-sm text-gray-600">
                            <span class="text-red-500 mr-1.5 sm:mr-2 flex-shrink-0">•</span>
                            <span>El enlace expira en <strong>15 minutos</strong> por seguridad</span>
                        </div>
                        <div class="flex items-start text-xs sm:text-xs lg:text-sm text-gray-600">
                            <span class="text-red-500 mr-1.5 sm:mr-2 flex-shrink-0">•</span>
                            <span>Puedes reenviar el email las veces que necesites</span>
                        </div>
                    </div>
                </details>
            </div>

            <!-- ✅ Footer con soporte responsive -->
            <div class="text-center mt-4 sm:mt-6">
                <p class="text-xs sm:text-sm text-gray-500 px-2">
                    ¿Tienes problemas?
                    <a href="mailto:soporte@vasir.com" class="text-red-600 hover:text-red-700 underline font-medium">
                        Contacta a soporte
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
