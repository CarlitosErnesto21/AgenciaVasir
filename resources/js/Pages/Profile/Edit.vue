<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
        default: null,
    },
});

// Obtener información del usuario y sus roles
const user = props.user || null;

const isAdmin = computed(() => {
    if (!user?.roles || !Array.isArray(user.roles)) return false;
    return user.roles.some(role => role.name === 'Administrador' || role.name === 'Empleado');
});

// Decidir qué layout usar basado en el rol
const layoutComponent = computed(() => {
    return isAdmin.value ? AuthenticatedLayout : ClientLayout;
});

// Navegación por pestañas (solo para clientes)
const activeTab = ref('profile');

const setActiveTab = (tab) => {
    activeTab.value = tab;
    // Solo hacer scroll para clientes que usan navegación
    if (!isAdmin.value) {
        const element = document.getElementById(tab);
        if (element) {
            const offset = 100;
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    }
};

// Detectar qué sección está visible al hacer scroll (solo para clientes)
const handleScroll = () => {
    // Solo ejecutar para clientes
    if (isAdmin.value) return;

    const sections = ['profile', 'security', 'account'];
    const offset = 190;

    for (let i = sections.length - 1; i >= 0; i--) {
        const element = document.getElementById(sections[i]);
        if (element) {
            const rect = element.getBoundingClientRect();
            if (rect.top <= offset) {
                activeTab.value = sections[i];
                break;
            }
        }
    }
};

onMounted(() => {
    // Solo agregar scroll listener para clientes
    if (!isAdmin.value) {
        window.addEventListener('scroll', handleScroll);
    }
});

onUnmounted(() => {
    // Solo remover scroll listener si fue agregado
    if (!isAdmin.value) {
        window.removeEventListener('scroll', handleScroll);
    }
});
</script>

<template>
    <Head title="Mi Perfil" />

    <!-- Layout condicional basado en el rol del usuario -->
    <component :is="layoutComponent">
        <div class="min-h-screen bg-gray-50" :class="isAdmin ? 'pt-6 md:pt-10' : ''">

            <!-- Header para admin/empleado sin fondo rojo -->
            <div v-if="isAdmin" class="bg-white shadow-lg border-b border-gray-200 mb-8 md:mb-10">
                <div class="max-w-4xl mx-auto px-4 sm:px-8 py-6 sm:py-8 flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                    <div class="bg-red-600 rounded-xl p-4 shadow flex items-center justify-center border-4 border-white mb-3 sm:mb-0">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Mi Perfil</h1>
                        <p class="text-gray-700 text-base sm:text-lg mt-1 font-medium">Gestiona tu información personal y configuración de cuenta</p>
                    </div>
                </div>
            </div>

            <!-- Header mejorado para clientes -->
            <div v-else class="mb-8 md:mb-10">
                <div class="flex flex-col items-center justify-center">
                    <div class="bg-gradient-to-br from-red-600 via-red-500 to-red-400 rounded-full p-4 sm:p-5 w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center mb-3 sm:mb-4 shadow-2xl border-4 border-white">
                        <svg class="w-12 h-12 sm:w-14 sm:h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-1 tracking-tight">Mi Perfil</h1>
                    <p class="text-gray-700 text-base sm:text-lg font-medium">Gestiona tu información personal y configuración de cuenta</p>
                </div>
            </div>

            <div class="py-6 sm:py-8">
                <div :class="isAdmin ? 'max-w-4xl' : 'max-w-4xl'" class="mx-auto px-2 sm:px-4 md:px-6 lg:px-8">
                    <div v-if="isAdmin" class="space-y-6 md:space-y-8">
                        <!-- Información Personal -->
                        <section id="profile" class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-4 sm:px-6 py-4 bg-red-50 border-b border-gray-200 flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                <div class="bg-red-600 rounded-md p-2 flex items-center justify-center mb-2 sm:mb-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Información Personal</h2>
                                    <p class="text-gray-600 text-xs sm:text-sm">Actualiza tu información de perfil y correo electrónico</p>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <UpdateProfileInformationForm
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                />
                            </div>
                        </section>

                        <!-- Seguridad -->
                        <section id="security" class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-4 sm:px-6 py-4 bg-red-50 border-b border-gray-200 flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                <div class="bg-red-600 rounded-md p-2 flex items-center justify-center mb-2 sm:mb-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Seguridad</h2>
                                    <p class="text-gray-600 text-xs sm:text-sm">Mantén tu cuenta segura con una contraseña fuerte</p>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <UpdatePasswordForm />
                            </div>
                        </section>

                        <!-- Configuración de cuenta -->
                        <section id="account" class="bg-white shadow rounded-lg border border-red-200 overflow-hidden">
                            <div class="px-4 sm:px-6 py-4 bg-red-50 border-b border-red-200 flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                <div class="bg-red-600 rounded-md p-2 flex items-center justify-center mb-2 sm:mb-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h2 class="text-lg sm:text-xl font-semibold text-red-900">Eliminar Cuenta</h2>
                                    <p class="text-red-700 text-xs sm:text-sm">Acción irreversible - procede con precaución</p>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <DeleteUserForm
                                    :disabled="user?.roles && user.roles.some(role => ['Administrador', 'Empleado'].includes(role.name))"
                                    :rol="user?.roles && user.roles.length > 0 ? user.roles[0].name : ''"
                                />
                            </div>
                        </section>
                    </div>

                    <!-- Layout simple para clientes -->
                    <div v-else class="space-y-6 md:space-y-8">
                        <!-- Información Personal -->
                        <section id="profile" class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden scroll-mt-24">
                            <div class="px-4 sm:px-6 py-4 bg-red-50 border-b border-gray-200">
                                <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                    <div class="bg-red-600 rounded-md p-2 flex items-center justify-center mb-2 sm:mb-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Personal</h2>
                                        <p class="text-gray-600 text-xs sm:text-sm">Actualiza tu información de perfil y correo electrónico</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <UpdateProfileInformationForm
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                />
                            </div>
                        </section>

                        <!-- Seguridad -->
                        <section id="security" class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden scroll-mt-24">
                            <div class="px-4 sm:px-6 py-4 bg-red-50 border-b border-gray-200">
                                <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                    <div class="bg-red-600 rounded-md p-2 flex items-center justify-center mb-2 sm:mb-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">Seguridad</h2>
                                        <p class="text-gray-600 text-xs sm:text-sm">Mantén tu cuenta segura con una contraseña fuerte</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <UpdatePasswordForm />
                            </div>
                        </section>

                        <!-- Configuración de cuenta -->
                        <section id="account" class="bg-white shadow-sm rounded-lg border border-red-200 overflow-hidden scroll-mt-24">
                            <div class="px-4 sm:px-6 py-4 bg-red-50 border-b border-red-200">
                                <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                    <div class="bg-red-600 rounded-md p-2 flex items-center justify-center mb-2 sm:mb-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <h2 class="text-base sm:text-lg font-semibold text-red-900">Eliminar Cuenta</h2>
                                        <p class="text-red-700 text-xs sm:text-sm">Acción irreversible - procede con precaución</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6">
                                <DeleteUserForm :disabled="user?.roles && user.roles.some(role => ['Administrador', 'Empleado'].includes(role.name))" />
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
.sticky {
    position: sticky;
}

.transition-colors {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
