<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\ForgotPassword.vue -->
<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faLock, faInfoCircle, faCheck, faEnvelope,
    faPaperPlane, faArrowLeft, faSpinner
} from '@fortawesome/free-solid-svg-icons';

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
            <!-- ✅ Tarjeta principal -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl p-6 sm:p-8 lg:p-10 border border-gray-100">
                <!-- ✅ Título con logo -->
                <div class="text-center mb-4 sm:mb-6">
                    <Link href="/" class="inline-block mb-3 sm:mb-4">
                        <div class="mx-auto w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-white rounded-full flex items-center justify-center shadow-md border border-gray-200 hover:scale-105 transition-transform duration-200 cursor-pointer">
                            <img
                                src="/images/logo.png"
                                alt="VASIR Logo"
                                class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 object-contain"
                            />
                        </div>
                    </Link>
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
                                <FontAwesomeIcon :icon="faInfoCircle" class="h-4 w-4 sm:h-5 sm:w-5 text-blue-400 mt-0.5" />
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
                                <FontAwesomeIcon :icon="faCheck" class="h-4 w-4 sm:h-5 sm:w-5 text-green-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-medium text-green-800">
                                    Enlace de recuperación enviado
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
                        <InputLabel for="email" class="text-sm sm:text-base font-medium text-gray-700 flex items-center gap-2">
                            <FontAwesomeIcon :icon="faEnvelope" class="h-4 w-4 text-gray-600" />
                            Correo Electrónico
                        </InputLabel>

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
                            <FontAwesomeIcon v-if="form.processing" :icon="faSpinner" class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" />
                            <span v-if="form.processing" class="text-sm sm:text-base">Enviando...</span>
                            <span v-else class="flex items-center">
                                <FontAwesomeIcon :icon="faPaperPlane" class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" />
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
                            <FontAwesomeIcon :icon="faArrowLeft" class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" />
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
