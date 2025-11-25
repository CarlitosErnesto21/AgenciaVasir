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
    <div class="min-h-screen bg-gradient-to-br from-red-50 via-white to-red-50 flex items-center justify-center p-2">
        <Head title="Confirmar Contraseña" />

        <div class="w-full max-w-md">
            <!-- Tarjeta principal -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                <!-- Header con logo -->
                <div class="text-center mb-6">
                    <div class="mx-auto w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mb-3 p-2">
                        <img
                            src="/images/logo.png"
                            alt="VASIR Logo"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Verificación de Seguridad
                    </h1>
                    <p class="text-gray-600">
                        Confirma tu contraseña para acceder al área protegida
                    </p>
                </div>

                <!-- Mensaje informativo -->
                <div class="mb-4">
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-blue-800">
                                    Estás accediendo a un área protegida de <strong class="text-red-600">VASIR</strong>. Por seguridad, necesitamos verificar tu identidad.
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
                            value="Contraseña Actual"
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

                    <!-- Botones -->
                    <div class="space-y-3">
                        <PrimaryButton
                            class="w-full bg-red-600 hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-white font-semibold py-2.5 px-4 rounded-xl transition-all duration-200 flex items-center justify-center text-base shadow-lg hover:shadow-xl"
                            :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <span v-if="form.processing">Verificando...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Confirmar y Continuar
                            </span>
                        </PrimaryButton>

                        <button
                            type="button"
                            onclick="window.history.back()"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 px-4 rounded-xl transition-colors duration-200 flex items-center justify-center text-base"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</template>
