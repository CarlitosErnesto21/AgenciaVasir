<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

// Estados para mostrar/ocultar contraseñas
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: '',
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
    return !form.current_password || !form.password || !form.password_confirmation;
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: (response) => {
            // No necesitamos resetear el form porque seremos redirigidos
            // La redirección se maneja automáticamente por Inertia desde el backend
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="bg-white/90 rounded-2xl shadow-lg border border-gray-100 p-4 sm:p-6 md:p-8">
        <form @submit.prevent="updatePassword" class="space-y-8">
            <!-- Contraseña Actual -->
            <div class="space-y-2">
                <InputLabel for="current_password" value="Contraseña Actual" class="text-gray-700 font-semibold text-base" />
                <div class="relative">
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 text-gray-900 text-base shadow-sm
                               focus:border-red-500 focus:ring-2 focus:ring-red-100
                               hover:border-gray-300 transition-colors
                               bg-white placeholder-gray-400"
                        autocomplete="current-password"
                        placeholder="Tu contraseña actual"
                    />
                    <button
                        type="button"
                        @click="showCurrentPassword = !showCurrentPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg v-if="!showCurrentPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                        </svg>
                    </button>
                </div>
                <InputError :message="form.errors.current_password" class="text-red-600 text-sm" />
            </div>

            <!-- Grid para nuevas contraseñas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Nueva Contraseña -->
                <div class="space-y-2">
                    <InputLabel for="password" value="Nueva Contraseña" class="text-gray-700 font-semibold text-base" />
                    <div class="relative">
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :type="showNewPassword ? 'text' : 'password'"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 text-gray-900 text-base shadow-sm
                                   focus:border-red-500 focus:ring-2 focus:ring-red-100
                                   hover:border-gray-300 transition-colors
                                   bg-white placeholder-gray-400"
                            autocomplete="new-password"
                            placeholder="Nueva contraseña"
                        />
                        <button
                            type="button"
                            @click="showNewPassword = !showNewPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg v-if="!showNewPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" class="text-red-600 text-sm" />
                    <div v-if="passwordErrors.length" class="mt-1 text-xs text-red-500 space-y-1">
                        <div v-for="(err, i) in passwordErrors" :key="i">{{ err }}</div>
                    </div>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="space-y-2">
                    <InputLabel for="password_confirmation" value="Confirmar Contraseña" class="text-gray-700 font-semibold text-base" />
                    <div class="relative">
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 text-gray-900 text-base shadow-sm
                                   focus:border-red-500 focus:ring-2 focus:ring-red-100
                                   hover:border-gray-300 transition-colors
                                   bg-white placeholder-gray-400"
                            autocomplete="new-password"
                            placeholder="Confirmar contraseña"
                        />
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg v-if="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" class="text-red-600 text-sm" />
                    <div v-if="passwordConfirmationError" class="mt-1 text-xs text-red-500">
                        {{ passwordConfirmationError }}
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-8 border-t border-gray-100 mt-2">
                <PrimaryButton
                    :disabled="form.processing || passwordErrors.length || passwordConfirmationError || isFormIncomplete"
                    class="inline-flex items-center px-7 py-3 bg-red-600 text-white text-base font-semibold
                           rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500
                           disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition-colors w-full sm:w-auto"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    {{ form.processing ? 'Actualizando...' : 'Actualizar Contraseña' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-y-1"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div
                        v-if="form.recentlySuccessful"
                        class="flex items-center gap-2 px-5 py-2.5 bg-blue-50 text-blue-800
                               rounded-lg border border-blue-200 text-base shadow-sm w-full sm:w-auto"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Contraseña actualizada. Redirigiendo al login...
                    </div>
                </Transition>
            </div>
        </form>
    </section>
</template>
