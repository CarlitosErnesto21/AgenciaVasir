<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\VerifyEmail.vue -->
<script setup>
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheckCircle, faExclamationTriangle, faLightbulb } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    status: {
        type: String,
    },
    email: {
        type: String,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    resendCount: {
        type: Number,
        default: 0,
    },
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
});

// Obtener datos de configuración
const page = usePage();
const config = computed(() => page.props.config || {});
const adminEmail = computed(() => config.value.admin_email || 'vasirtours19@gmail.com');

const form = useForm({
    email: props.email,
});

const submit = () => {
    // Usar ruta apropiada según el estado de autenticación
    const routeName = props.isAuthenticated ? 'verification.send' : 'verification.send.public';
    form.post(route(routeName));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const hasLimitError = computed(() => !!props.errors.limit || props.resendCount >= 3);
const remainingAttempts = computed(() => Math.max(0, 3 - props.resendCount));

// Función para abrir Gmail de soporte
const abrirGmailSoporte = () => {
  const email = adminEmail.value;

  // Verificar si el email del administrador está disponible
  if (!email || email.includes('no disponible')) {
    return;
  }

  const subject = 'Problema con verificación de email';
  const body = `Hola,

Tengo problemas con la verificación de mi email en VASIR.

Mi correo registrado: ${props.email}

Por favor, ayúdenme a resolver este problema.

Gracias.`;

  // Detectar si es móvil
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  if (isMobile) {
    // En móviles, usar mailto: que el sistema operativo maneje
    const mailtoUrl = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    window.location.href = mailtoUrl;
  } else {
    // En escritorio, abrir Gmail web directamente
    const encodedSubject = encodeURIComponent(subject);
    const encodedBody = encodeURIComponent(body);
    window.open(`https://mail.google.com/mail/?view=cm&fs=1&to=${email}&su=${encodedSubject}&body=${encodedBody}`, '_blank');
  }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <Head title="Verificación de Email" />

        <!-- ✅ Contenedor principal responsive -->
        <div class="w-full max-w-sm sm:max-w-md lg:max-w-lg xl:max-w-xl">
            <!-- ✅ Tarjeta principal responsive -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl p-6 sm:p-8 lg:p-10 border border-gray-100">
                <!-- ✅ Título con logo responsive -->
                <div class="text-center mb-4 sm:mb-6">
                    <div class="flex justify-center mb-3 sm:mb-4">
                        <Link href="/">
                            <img
                                src="/images/logo.png"
                                alt="VASIR Logo"
                                class="h-12 sm:h-14 lg:h-16 w-auto cursor-pointer hover:scale-105 transition-transform duration-200"
                                title="Ir al inicio"
                            />
                        </Link>
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
                                <FontAwesomeIcon :icon="faCheckCircle" class="h-4 w-4 sm:h-5 sm:w-5 text-green-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-medium text-green-800">
                                    Nuevo enlace enviado correctamente
                                </p>
                                <p class="text-xs text-green-700 mt-1">
                                    Revisa tu bandeja de entrada y spam
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ❌ Mensaje de error de límite -->
                    <div
                        v-if="hasLimitError"
                        class="p-3 sm:p-4 bg-red-50 border-l-4 border-red-400 rounded-r-lg"
                    >
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 w-4 sm:h-5 sm:w-5 text-red-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-medium text-red-800">
                                    Límite alcanzado
                                </p>
                                <p class="text-xs text-red-700 mt-1">
                                    {{ errors.limit }}
                                </p>
                                <p class="text-xs text-red-700 mt-2 font-medium flex items-center">
                                    <FontAwesomeIcon :icon="faLightbulb" class="mr-1.5 text-yellow-500" />
                                    Te recomendamos volver a registrarte o contactar a soporte
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
                        :class="{
                            'opacity-75 cursor-not-allowed': form.processing || hasLimitError,
                            'bg-gray-400 hover:bg-gray-400': hasLimitError
                        }"
                        :disabled="form.processing || hasLimitError"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        <span v-if="form.processing" class="text-sm sm:text-base">Enviando...</span>
                        <span v-else-if="hasLimitError" class="flex items-center text-sm sm:text-base">
                            <FontAwesomeIcon :icon="faExclamationTriangle" class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" />
                            <span class="hidden sm:inline">Límite Alcanzado (3/3)</span>
                            <span class="sm:hidden">Límite Alcanzado</span>
                        </span>
                        <span v-else class="flex items-center text-sm sm:text-base">
                            <FontAwesomeIcon :icon="faLightbulb" class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" />
                            <span class="hidden sm:inline">Reenviar Email de Verificación ({{ remainingAttempts }}/3)</span>
                            <span class="sm:hidden">Reenviar ({{ remainingAttempts }}/3)</span>
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
                            <span>Máximo <strong>3 reenvíos</strong> por sesión de registro</span>
                        </div>
                    </div>
                </details>
            </div>

            <!-- ✅ Footer con soporte responsive -->
            <div class="text-center mt-4 sm:mt-6">
                <p class="text-xs sm:text-sm text-gray-500 px-2">
                    ¿Tienes problemas?
                    <a
                        href="#"
                        @click="abrirGmailSoporte"
                        class="text-red-600 hover:text-red-700 underline font-medium cursor-pointer"
                    >
                        Contacta a soporte
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
