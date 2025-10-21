<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\ConfirmPassword.vue -->
<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Estado para mostrar/ocultar contraseña
const showPassword = ref(false);

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};

const handleForgotPasswordLink = (e) => {
    e.preventDefault(); // Prevenir navegación normal del Link

    // Hacer logout primero
    router.post(route('logout'), {}, {
        onSuccess: () => {
            // Después del logout, ir a forgot-password con GET
            router.visit(route('password.request'), {
                method: 'get'  // ← Explícitamente GET
            });
        },
        onError: (errors) => {
            console.error('Error al cerrar sesión:', errors);
            // Fallback: ir directamente con GET
            router.visit(route('password.request'), {
                method: 'get'
            });
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <Head title="Confirmar Contraseña" />

        <!-- ✅ Contenedor principal responsive -->
        <div class="w-full max-w-sm sm:max-w-md lg:max-w-lg">
            <!-- ✅ Logo centrado -->
            <div class="flex justify-center mb-6 sm:mb-8">
                <Link href="/">
                    <img
                        src="/images/logo.png"
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
                    <div class="mx-auto w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-3 sm:mb-4">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">
                        Área Segura
                    </h2>
                    <p class="text-xs sm:text-sm lg:text-base text-gray-600">
                        Confirma tu contraseña para continuar
                    </p>
                </div>

                <!-- ✅ Mensaje informativo -->
                <div class="mb-4 sm:mb-6">
                    <div class="p-3 sm:p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg">
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm lg:text-base text-yellow-800 leading-relaxed">
                                    Esta es un área segura de <strong class="text-red-600">VASIR</strong>.
                                    Por tu seguridad, confirma tu contraseña antes de continuar.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Formulario -->
                <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                    <div>
                        <InputLabel
                            for="password"
                            value="🔒 Contraseña Actual"
                            class="text-sm sm:text-base font-medium text-gray-700"
                        />

                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="mt-2 block w-full px-3 sm:px-4 py-2.5 sm:py-3 pr-10 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm sm:text-base"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                autofocus
                                placeholder="Ingresa tu contraseña actual"
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
                    </div>

                    <!-- ✅ Información adicional -->
                    <div class="p-3 sm:p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r-lg">
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xs sm:text-sm font-medium text-blue-800 mb-1">
                                    🛡️ ¿Por qué necesitas confirmar tu contraseña?
                                </h4>
                                <ul class="text-xs text-blue-700 space-y-1">
                                    <li>• Estás accediendo a información sensible</li>
                                    <li>• Protegemos tu cuenta de accesos no autorizados</li>
                                    <li>• Es una medida de seguridad adicional</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ Botones -->
                    <div class="space-y-3 sm:space-y-4">
                        <PrimaryButton
                            class="w-full bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500 focus:ring-offset-2 text-white font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                            :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <span v-if="form.processing" class="text-sm sm:text-base">Verificando...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Confirmar Contraseña
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

                        <!-- Cancelar -->
                        <button
                            type="button"
                            onclick="window.history.back()"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Cancelar
                        </button>
                    </div>
                </form>

                <!-- ✅ Ayuda adicional -->
                <details class="mt-4 sm:mt-6">
                    <summary class="cursor-pointer text-xs sm:text-sm font-medium text-gray-700 hover:text-gray-900 flex items-center">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ¿Olvidaste tu contraseña?
                    </summary>
                    <div class="mt-2 sm:mt-3 pl-4 sm:pl-6">
                        <div class="text-xs sm:text-sm text-gray-600">
                            Si no recordás tu contraseña, podés
                            <Link
                                :href="route('password.request')"
                                @click="handleForgotPasswordLink"
                                class="text-red-600 hover:text-red-700 underline font-medium"
                            >
                                cerrar sesión y recuperarla
                            </Link>
                        </div>
                    </div>
                </details>
            </div>

            <!-- ✅ Footer -->
            <div class="text-center mt-4 sm:mt-6">
                <p class="text-xs sm:text-sm text-gray-500">
                    🔒 Tu seguridad es nuestra prioridad en
                    <span class="text-red-600 font-medium">VASIR</span>
                </p>
            </div>
        </div>
    </div>
</template>
