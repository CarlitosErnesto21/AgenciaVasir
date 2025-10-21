<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Auth\ResetPassword.vue -->
<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
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

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <Head title="Restablecer Contraseña" />

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
                    <div class="mx-auto w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-green-100 rounded-full flex items-center justify-center mb-3 sm:mb-4">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">
                        Restablecer Contraseña
                    </h2>
                    <p class="text-xs sm:text-sm lg:text-base text-gray-600">
                        Crea tu nueva contraseña segura
                    </p>
                </div>

                <!-- ✅ Mensaje informativo -->
                <div class="mb-4 sm:mb-6">
                    <div class="p-3 sm:p-4 bg-green-50 border-l-4 border-green-400 rounded-r-lg">
                        <div class="flex items-start space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm lg:text-base text-green-800 leading-relaxed">
                                    Tu enlace de recuperación es válido. Ingresa tu nueva contraseña para
                                    <strong>{{ email }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Formulario -->
                <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                    <!-- Email (readonly) -->
                    <div>
                        <InputLabel for="email" value="📧 Correo Electrónico" class="text-sm sm:text-base font-medium text-gray-700" />

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
                        <InputLabel for="password" value="🔒 Nueva Contraseña" class="text-sm sm:text-base font-medium text-gray-700" />

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
                    </div>

                    <!-- Confirmar contraseña -->
                    <div>
                        <InputLabel for="password_confirmation" value="🔒 Confirmar Contraseña" class="text-sm sm:text-base font-medium text-gray-700" />

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
                    </div>

                    <!-- ✅ Consejos de seguridad -->
                    <div class="p-3 sm:p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg">
                        <h4 class="text-xs sm:text-sm font-medium text-yellow-800 mb-2">
                            💡 Consejos para una contraseña segura:
                        </h4>
                        <ul class="text-xs text-yellow-700 space-y-1">
                            <li>• Mínimo 8 caracteres</li>
                            <li>• Combina letras, números y símbolos</li>
                            <li>• Evita información personal</li>
                            <li>• No reutilices contraseñas de otras cuentas</li>
                        </ul>
                    </div>

                    <!-- ✅ Botón principal -->
                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 focus:ring-offset-2 text-white font-medium py-2.5 sm:py-3 lg:py-4 px-4 sm:px-6 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base"
                            :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
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
