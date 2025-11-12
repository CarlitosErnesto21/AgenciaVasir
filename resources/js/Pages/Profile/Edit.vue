<!-- filepath: c:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir\resources\js\Pages\Profile\Edit.vue -->
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
});

// Obtener información del usuario y sus roles
const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const isAdmin = computed(() => {
    if (!user.value?.roles || !Array.isArray(user.value.roles)) return false;
    return user.value.roles.some(role => role.name === 'admin' || role.name === 'empleado');
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
        <div class="min-h-screen bg-gray-50" :class="isAdmin ? 'mt-12' : ''">
            <!-- Header compacto y profesional (solo para admin) -->
            <div v-if="isAdmin" class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="bg-red-600 rounded-lg p-3 shadow-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Mi Perfil</h1>
                                <p class="text-gray-600 text-sm">Gestiona tu información personal y configuración de cuenta</p>
                            </div>
                        </div>

                        <div class="hidden sm:flex items-center space-x-2 bg-green-50 rounded-lg px-3 py-2 border border-green-200">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-green-700 font-medium text-sm">Activa</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header simple para clientes -->
            <div v-else class="mb-8">
                <div class="text-center">
                    <div class="bg-red-600 rounded-full p-4 w-20 h-20 mx-auto mb-4 shadow-lg">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Mi Perfil</h1>
                    <p class="text-gray-600">Gestiona tu información personal y configuración de cuenta</p>
                </div>
            </div>

            <div class="py-8">
                <div :class="isAdmin ? 'max-w-6xl' : 'max-w-4xl'" class="mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <!-- Navegación horizontal para clientes solamente -->
                    <div v-if="!isAdmin" class="sticky z-20 mb-8 top-16" :style="{ backgroundColor: '#f9fafb' }">
                        <div class="py-2">
                            <!-- Indicador de progreso -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                    <span>Progreso del perfil</span>
                                    <span>{{ Math.round(((activeTab === 'profile' ? 1 : activeTab === 'security' ? 2 : 3) / 3) * 100) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div 
                                        class="bg-red-600 h-1.5 rounded-full transition-all duration-300"
                                        :style="{ width: `${((activeTab === 'profile' ? 1 : activeTab === 'security' ? 2 : 3) / 3) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            
                            <nav class="flex space-x-1 bg-white rounded-lg shadow-md border border-gray-200 p-1">
                                <button 
                                    @click="setActiveTab('profile')"
                                    :class="[
                                        'flex-1 rounded-md py-3 px-2 sm:px-4 text-center font-medium text-xs sm:text-sm transition-all duration-200',
                                        activeTab === 'profile' 
                                            ? 'bg-red-600 text-white shadow-sm' 
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-red-600'
                                    ]"
                                >
                                    <span class="flex items-center justify-center gap-1 sm:gap-2">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Información Personal</span>
                                        <span class="sm:hidden">Información</span>
                                    </span>
                                </button>
                                <button 
                                    @click="setActiveTab('security')"
                                    :class="[
                                        'flex-1 rounded-md py-3 px-2 sm:px-4 text-center font-medium text-xs sm:text-sm transition-all duration-200',
                                        activeTab === 'security' 
                                            ? 'bg-red-600 text-white shadow-sm' 
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-red-600'
                                    ]"
                                >
                                    <span class="flex items-center justify-center gap-1 sm:gap-2">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Actualizar Contraseña</span>
                                        <span class="sm:hidden">Contraseña</span>
                                    </span>
                                </button>
                                <button 
                                    @click="setActiveTab('account')"
                                    :class="[
                                        'flex-1 rounded-md py-3 px-2 sm:px-4 text-center font-medium text-xs sm:text-sm transition-all duration-200',
                                        activeTab === 'account' 
                                            ? 'bg-red-600 text-white shadow-sm' 
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-red-600'
                                    ]"
                                >
                                    <span class="flex items-center justify-center gap-1 sm:gap-2">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Eliminar Cuenta</span>
                                        <span class="sm:hidden">Eliminar</span>
                                    </span>
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Layout simplificado para admin: sin navegación ni barra de progreso -->
                    <div v-if="isAdmin" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Contenido principal -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Información Personal -->
                            <section id="profile" :class="[
                                'bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden',
                                isAdmin ? 'scroll-mt-36' : 'scroll-mt-24'
                            ]">
                                <div class="px-6 py-4 bg-red-50 border-b border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="bg-red-600 rounded-md p-2">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">Información Personal</h2>
                                            <p class="text-gray-600 text-sm">Actualiza tu información de perfil y correo electrónico</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <UpdateProfileInformationForm
                                        :must-verify-email="mustVerifyEmail"
                                        :status="status"
                                    />
                                </div>
                            </section>

                            <!-- Seguridad -->
                            <section id="security" :class="[
                                'bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden',
                                isAdmin ? 'scroll-mt-36' : 'scroll-mt-24'
                            ]">
                                <div class="px-6 py-4 bg-red-50 border-b border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="bg-red-600 rounded-md p-2">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">Seguridad</h2>
                                            <p class="text-gray-600 text-sm">Mantén tu cuenta segura con una contraseña fuerte</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <UpdatePasswordForm />
                                </div>
                            </section>

                            <!-- Configuración de cuenta -->
                            <section id="account" :class="[
                                'bg-white shadow-sm rounded-lg border border-red-200 overflow-hidden',
                                isAdmin ? 'scroll-mt-36' : 'scroll-mt-24'
                            ]">
                                <div class="px-6 py-4 bg-red-50 border-b border-red-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="bg-red-600 rounded-md p-2">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-red-900">Eliminar Cuenta</h2>
                                            <p class="text-red-700 text-sm">Acción irreversible - procede con precaución</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <DeleteUserForm />
                                </div>
                            </section>
                        </div>

                        <!-- Sidebar informativo para admin -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Estado de la cuenta -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                                <div class="bg-red-600 px-4 py-3">
                                    <h3 class="text-white font-semibold text-sm">Estado de la Cuenta</h3>
                                </div>
                                <div class="p-4 space-y-3">
                                    <div class="flex items-center justify-between py-2 px-3 bg-green-50 rounded-md border border-green-200">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span class="text-sm font-medium text-green-800">Email</span>
                                        </div>
                                        <span class="text-xs font-medium bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                            Verificado
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between py-2 px-3 bg-green-50 rounded-md border border-green-200">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span class="text-sm font-medium text-green-800">Seguridad</span>
                                        </div>
                                        <span class="text-xs font-medium bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                            Segura
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between py-2 px-3 bg-blue-50 rounded-md border border-blue-200">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                            <span class="text-sm font-medium text-blue-800">Último acceso</span>
                                        </div>
                                        <span class="text-xs font-medium text-blue-600">Hoy</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Consejos -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <h4 class="text-gray-800 font-semibold text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Consejos de Seguridad
                                    </h4>
                                </div>
                                <div class="p-4">
                                    <ul class="text-xs text-gray-600 space-y-2">
                                        <li class="flex items-start">
                                            <span class="w-1 h-1 bg-red-600 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                            Usa contraseñas únicas y complejas
                                        </li>
                                        <li class="flex items-start">
                                            <span class="w-1 h-1 bg-red-600 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                            Mantén tu email siempre actualizado
                                        </li>
                                        <li class="flex items-start">
                                            <span class="w-1 h-1 bg-red-600 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                            Revisa la actividad de tu cuenta regularmente
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Soporte -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                                <div class="p-4">
                                    <h4 class="text-gray-800 font-semibold text-sm mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ¿Necesitas ayuda?
                                    </h4>
                                    <p class="text-xs text-gray-600 mb-3">
                                        Contacta con soporte para cualquier consulta sobre tu cuenta.
                                    </p>
                                    <button class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-2 px-3 rounded-md transition-colors duration-200">
                                        Contactar Soporte
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Layout simple para clientes -->
                    <div v-else class="space-y-8">
                        <!-- Información Personal -->
                        <section id="profile" class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden scroll-mt-24">
                            <div class="px-6 py-4 bg-red-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-red-600 rounded-md p-2">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900">Información Personal</h2>
                                        <p class="text-gray-600 text-sm">Actualiza tu información de perfil y correo electrónico</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <UpdateProfileInformationForm
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                />
                            </div>
                        </section>

                        <!-- Seguridad -->
                        <section id="security" class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden scroll-mt-24">
                            <div class="px-6 py-4 bg-red-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-red-600 rounded-md p-2">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900">Seguridad</h2>
                                        <p class="text-gray-600 text-sm">Mantén tu cuenta segura con una contraseña fuerte</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <UpdatePasswordForm />
                            </div>
                        </section>

                        <!-- Configuración de cuenta -->
                        <section id="account" class="bg-white shadow-sm rounded-lg border border-red-200 overflow-hidden scroll-mt-24">
                            <div class="px-6 py-4 bg-red-50 border-b border-red-200">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-red-600 rounded-md p-2">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-red-900">Eliminar Cuenta</h2>
                                        <p class="text-red-700 text-sm">Acción irreversible - procede con precaución</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <DeleteUserForm />
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
