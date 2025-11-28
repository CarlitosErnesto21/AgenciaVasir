<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faUser, faLock, faExclamationTriangle, faChevronLeft, faFileAlt, faShoppingCart, faEnvelope } from '@fortawesome/free-solid-svg-icons';
import { useToast } from 'primevue/usetoast';

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
    cliente: {
        type: Object,
        default: null,
    },
    empleado: {
        type: Object,
        default: null,
    },
    debugEmpleado: {
        type: Object,
        default: null,
    },
});

// Obtener información del usuario y sus roles
const user = props.user || null;
const toast = useToast();

const isAdmin = computed(() => {
    if (!user?.roles || !Array.isArray(user.roles)) return false;
    return user.roles.some(role => role.name === 'Administrador' || role.name === 'Empleado');
});

// Decidir qué layout usar basado en el rol
const layoutComponent = computed(() => {
    return isAdmin.value ? AuthenticatedLayout : ClientLayout;
});

// Estado para mostrar el formulario seleccionado
const selectedForm = ref(null); // null, 'profile', 'password', 'delete'

// Estado de carga para deshabilitar tarjetas durante procesamiento
const isLoadingReport = ref(false);

// Estados de cooldown para evitar spam de correos (5 minutos)
const cooldownReservaciones = ref(0);
const cooldownCompras = ref(0);

// Función para verificar y manejar cooldown
const checkCooldown = (type) => {
    const key = `report_cooldown_${type}`;
    const lastRequest = localStorage.getItem(key);

    if (lastRequest) {
        const elapsed = Date.now() - parseInt(lastRequest);
        const remaining = 300000 - elapsed; // 5 minutos en milisegundos

        if (remaining > 0) {
            return Math.ceil(remaining / 1000); // Devolver segundos restantes
        } else {
            localStorage.removeItem(key);
            return 0;
        }
    }
    return 0;
};

// Función para establecer cooldown
const setCooldown = (type) => {
    const key = `report_cooldown_${type}`;
    localStorage.setItem(key, Date.now().toString());
};

// Función para formatear tiempo mm:ss
const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
};

// Verificar cooldowns al cargar la página
onMounted(() => {
    // Inicializar con valores seguros
    cooldownReservaciones.value = 0;
    cooldownCompras.value = 0;

    // Verificar cooldowns existentes
    const reservacionesCooldown = checkCooldown('reservaciones');
    const comprasCooldown = checkCooldown('compras');

    if (reservacionesCooldown > 0) {
        cooldownReservaciones.value = reservacionesCooldown;
        startCooldownTimer('reservaciones');
    }
    if (comprasCooldown > 0) {
        cooldownCompras.value = comprasCooldown;
        startCooldownTimer('compras');
    }
});// Función para iniciar temporizador de cooldown
const startCooldownTimer = (type) => {
    const timer = setInterval(() => {
        const remaining = checkCooldown(type);

        if (type === 'reservaciones') {
            cooldownReservaciones.value = remaining;
        } else {
            cooldownCompras.value = remaining;
        }

        if (remaining <= 0) {
            clearInterval(timer);
        }
    }, 1000);
};

const showForm = (form, event = null) => {
    if (event) {
        event.preventDefault(); // Prevenir comportamiento por defecto
    }
    selectedForm.value = form;
};

const goBack = () => {
    selectedForm.value = null;
};

// Handlers para clicks
const handleReservacionesClick = (event) => {
    event.preventDefault(); // Prevenir comportamiento por defecto

    if (isLoadingReport.value || cooldownReservaciones.value > 0) {
        return;
    }

    solicitarInformeReservaciones();
};

const handleComprasClick = (event) => {
    event.preventDefault(); // Prevenir comportamiento por defecto

    if (isLoadingReport.value || cooldownCompras.value > 0) {
        return;
    }

    solicitarInformeCompras();
};

// Funciones para solicitar informes por email - Solo para clientes
const solicitarInformeReservaciones = async () => {
    if (isLoadingReport.value) {
        return;
    }

    // Verificar cooldown
    if (cooldownReservaciones.value > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Espera un momento',
            detail: `Debes esperar ${formatTime(cooldownReservaciones.value)} antes de solicitar otro informe de reservaciones.`,
            life: 5000
        });
        return;
    }

    isLoadingReport.value = true;

    toast.add({
        severity: 'info',
        summary: 'Preparando envío',
        detail: 'Generando y preparando el envío de tu informe de reservaciones por correo...',
        life: 4000
    });

    try {
        const response = await fetch('/mi-perfil/descargar-reservaciones', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });        if (response.ok) {
            const data = await response.json();

            if (data.success) {
                // Establecer cooldown de 5 minutos
                setCooldown('reservaciones');
                cooldownReservaciones.value = 300; // 5 minutos
                startCooldownTimer('reservaciones');

                toast.add({
                    severity: 'success',
                    summary: 'Solicitud enviada',
                    detail: data.message || 'El informe de reservaciones será enviado a tu correo electrónico.',
                    life: 6000
                });
            } else {
                // Manejar diferentes tipos de respuesta
                let severity, summary;

                if (data.type === 'info') {
                    severity = 'info';
                    summary = 'Sin reservaciones';
                } else if (data.type === 'warning') {
                    severity = 'warn';
                    summary = 'Configuración requerida';
                } else {
                    severity = 'warn';
                    summary = 'Aviso';
                }

                toast.add({
                    severity: severity,
                    summary: summary,
                    detail: data.message || 'No se pudo procesar la solicitud del informe.',
                    life: 8000
                });
            }
        } else {
            const errorData = await response.json().catch(() => ({ message: 'Error desconocido' }));            toast.add({
                severity: 'error',
                summary: `Error del servidor (${response.status})`,
                detail: errorData.message || `Error ${response.status}: ${response.statusText}`,
                life: 8000
            });
        }
    } catch (error) {
        console.error('🚨 Error de conexión completo:', {
            name: error.name,
            message: error.message,
            stack: error.stack,
            cause: error.cause
        });

        toast.add({
            severity: 'error',
            summary: 'Error de conexión',
            detail: `Error: ${error.message}. Por favor, intenta nuevamente.`,
            life: 6000
        });
    } finally {
        isLoadingReport.value = false;
    }
};

const solicitarInformeCompras = async () => {
    if (isLoadingReport.value) {
        return;
    }

    // Verificar cooldown
    if (cooldownCompras.value > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Espera un momento',
            detail: `Debes esperar ${formatTime(cooldownCompras.value)} antes de solicitar otro informe de compras.`,
            life: 5000
        });
        return;
    }

    isLoadingReport.value = true;    // Toast de preparando envío
    toast.add({
        severity: 'info',
        summary: 'Preparando envío',
        detail: 'Generando y preparando el envío de tu informe de compras por correo...',
        life: 4000
    });

    try {
        const response = await fetch('/mi-perfil/descargar-compras', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();

            if (data.success) {
                // Establecer cooldown de 5 minutos
                setCooldown('compras');
                cooldownCompras.value = 300; // 5 minutos
                startCooldownTimer('compras');

                toast.add({
                    severity: 'success',
                    summary: 'Solicitud enviada',
                    detail: data.message || 'El informe de compras será enviado a tu correo electrónico.',
                    life: 6000
                });
            } else {
                // Manejar diferentes tipos de respuesta
                let severity, summary;

                if (data.type === 'info') {
                    severity = 'info';
                    summary = 'Sin compras';
                } else if (data.type === 'warning') {
                    severity = 'warn';
                    summary = 'Configuración requerida';
                } else {
                    severity = 'warn';
                    summary = 'Aviso';
                }

                toast.add({
                    severity: severity,
                    summary: summary,
                    detail: data.message || 'No se pudo procesar la solicitud del informe.',
                    life: 8000
                });
            }
        } else {
            const errorData = await response.json().catch(() => ({ message: 'Error desconocido' }));

            toast.add({
                severity: 'error',
                summary: 'Error del servidor',
                detail: errorData.message || 'Error interno del servidor.',
                life: 6000
            });
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error de conexión',
            detail: `Error: ${error.message}. Por favor, intenta nuevamente.`,
            life: 6000
        });
    } finally {
        isLoadingReport.value = false;
    }
};
</script>

<template>
    <Head title="Mi Perfil" />

    <!-- Layout condicional basado en el rol del usuario -->
    <component :is="layoutComponent">
        <div class="bg-gray-50" :class="isAdmin ? 'pt-4 md:pt-6' : ''">

            <!-- Header para admin/empleado sin fondo rojo -->
            <div v-if="isAdmin" class="bg-white shadow-lg border-b border-gray-200 mb-5 md:mb-10">
                <div class="max-w-4xl mx-auto px-4 sm:px-8 py-6 sm:py-8 flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                    <div class="bg-red-600 rounded-xl p-4 shadow flex items-center justify-center border-4 border-white mb-3 sm:mb-0">
                        <FontAwesomeIcon :icon="faUser" class="w-10 h-10 text-white" />
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Mi Perfil</h1>
                        <p class="text-gray-700 text-base sm:text-lg mt-1 font-medium">Gestiona tu información personal y configuración de cuenta</p>
                    </div>
                </div>
            </div>

            <!-- Header mejorado para clientes -->
            <div v-else class="mb-5 md:mb-10">
                <div class="flex flex-col items-center justify-center">
                    <div class="bg-gradient-to-br from-red-600 via-red-500 to-red-400 rounded-full p-4 sm:p-5 w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center mb-3 sm:mb-4 shadow-2xl border-4 border-white">
                        <FontAwesomeIcon :icon="faUser" class="w-12 h-12 sm:w-14 sm:h-14 text-white" />
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-1 tracking-tight">Mi Perfil</h1>
                    <p class="text-gray-700 text-base sm:text-lg font-medium">Gestiona tu información personal y configuración de cuenta</p>
                </div>
            </div>

            <div class="py-2 sm:py-4">
                <div :class="isAdmin ? 'max-w-4xl' : 'max-w-4xl'" class="mx-auto px-2 sm:px-4 md:px-6 lg:px-8">
                    <template v-if="!selectedForm">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Tarjeta Información Personal -->
                            <div @click="(event) => !isLoadingReport && showForm('profile', event)" :class="{
                                'cursor-pointer bg-white hover:shadow-2xl': !isLoadingReport,
                                'cursor-not-allowed bg-gray-50 opacity-50': isLoadingReport
                            }" class="rounded-2xl shadow-lg border border-gray-100 p-6 flex flex-col items-center transition-all group">
                                <div class="bg-red-100 rounded-full p-4 mb-4">
                                    <FontAwesomeIcon :icon="faUser" class="w-8 h-8 text-red-600" />
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Información Personal</h3>
                                <p class="text-gray-600 text-sm text-center">Actualiza tu información de perfil y correo electrónico</p>
                            </div>
                            <!-- Tarjeta Seguridad -->
                            <div @click="(event) => !isLoadingReport && showForm('password', event)" :class="{
                                'cursor-pointer bg-white hover:shadow-2xl': !isLoadingReport,
                                'cursor-not-allowed bg-gray-50 opacity-50': isLoadingReport
                            }" class="rounded-2xl shadow-lg border border-gray-100 p-6 flex flex-col items-center transition-all group">
                                <div class="bg-red-100 rounded-full p-4 mb-4">
                                    <FontAwesomeIcon :icon="faLock" class="w-8 h-8 text-red-600" />
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Seguridad</h3>
                                <p class="text-gray-600 text-sm text-center">Cambia tu contraseña y mantén tu cuenta segura</p>
                            </div>
                            <!-- Tarjeta Solicitar Informe de Reservaciones - Solo para Clientes -->
                            <div v-if="!isAdmin" @click="handleReservacionesClick" :class="{
                                'cursor-pointer bg-white hover:shadow-2xl': !isLoadingReport && cooldownReservaciones === 0,
                                'cursor-not-allowed bg-gray-50 opacity-50': isLoadingReport || cooldownReservaciones > 0
                            }" class="rounded-2xl shadow-lg border border-blue-200 p-6 flex flex-col items-center transition-all group">
                                <div class="bg-blue-100 rounded-full p-4 mb-4">
                                    <FontAwesomeIcon :icon="faEnvelope" class="w-8 h-8 text-blue-600" />
                                </div>
                                <h3 class="text-lg text-center font-bold text-blue-900 mb-1">
                                    {{ cooldownReservaciones > 0 ? 'Disponible en' : 'Reservaciones' }}
                                </h3>
                                <p class="text-blue-700 text-sm text-center">
                                    {{ cooldownReservaciones > 0
                                        ? `${formatTime(cooldownReservaciones)} - Evita spam de correos`
                                        : 'Solicitar informe PDF de los detalles de tus reservaciones'
                                    }}
                                </p>
                            </div>

                            <!-- Tarjeta Solicitar Informe de Compras - Solo para Clientes -->
                            <div v-if="!isAdmin" @click="handleComprasClick" :class="{
                                'cursor-pointer bg-white hover:shadow-2xl': !isLoadingReport && cooldownCompras === 0,
                                'cursor-not-allowed bg-gray-50 opacity-50': isLoadingReport || cooldownCompras > 0
                            }" class="rounded-2xl shadow-lg border border-green-200 p-6 flex flex-col items-center transition-all group">
                                <div class="bg-green-100 rounded-full p-4 mb-4">
                                    <FontAwesomeIcon :icon="faEnvelope" class="w-8 h-8 text-green-600" />
                                </div>
                                <h3 class="text-lg text-center font-bold text-green-900 mb-1">
                                    {{ cooldownCompras > 0 ? 'Disponible en' : 'Compras' }}
                                </h3>
                                <p class="text-green-700 text-sm text-center">
                                    {{ cooldownCompras > 0
                                        ? `${formatTime(cooldownCompras)} - Evita spam de correos`
                                        : 'Solicitar informe PDF de los detalles de tus compras'
                                    }}
                                </p>
                            </div>

                            <!-- Tarjeta Eliminar Cuenta -->
                            <div @click="(event) => !isLoadingReport && showForm('delete', event)" :class="{
                                'cursor-pointer bg-white hover:shadow-2xl': !isLoadingReport,
                                'cursor-not-allowed bg-gray-50 opacity-50': isLoadingReport
                            }" class="rounded-2xl shadow-lg border border-red-200 p-6 flex flex-col items-center transition-all group">
                                <div class="bg-red-200 rounded-full p-4 mb-4">
                                    <FontAwesomeIcon :icon="faExclamationTriangle" class="w-8 h-8 text-red-700" />
                                </div>
                                <h3 class="text-lg font-bold text-red-900 mb-1">
                                    {{ !isAdmin ? 'Solicitar Eliminación' : 'Eliminar Cuenta' }}
                                </h3>
                                <p class="text-red-700 text-sm text-center">
                                    <template v-if="!isAdmin">
                                        Enviar una solicitud al equipo de la Agencia VASIR
                                    </template>
                                    <template v-else-if="user?.roles && user.roles.some(role => role.name === 'Empleado')">
                                        Debes contactar al Administrador para eliminar tu cuenta
                                    </template>
                                    <template v-else>
                                        Debes contactar a soporte para eliminar tu cuenta
                                    </template>
                                </p>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <button @click="goBack" class="mb-6 flex items-center text-red-600 hover:text-red-800 font-semibold text-base"><FontAwesomeIcon :icon="faChevronLeft" class="w-5 h-5 mr-2" />Volver</button>
                        <div v-if="selectedForm === 'profile'">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                :cliente="props.cliente"
                                :empleado="props.empleado"
                            />
                        </div>
                        <div v-else-if="selectedForm === 'password'">
                            <UpdatePasswordForm />
                        </div>
                        <div v-else-if="selectedForm === 'delete'">
                            <DeleteUserForm :disabled="user?.roles && user.roles.some(role => ['Administrador', 'Empleado'].includes(role.name))" :rol="user?.roles && user.roles.length > 0 ? user.roles[0].name : ''" />
                        </div>
                    </template>
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
