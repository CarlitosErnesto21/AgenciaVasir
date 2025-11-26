<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import Toast from 'primevue/toast';
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <!-- Header simple con botón de regreso -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <!-- Botón de regreso a la izquierda -->
                    <Link
                        :href="route('inicio')"
                        class="flex items-center space-x-2 px-3 py-2 rounded-lg text-gray-600 hover:text-red-600 hover:bg-red-50 transition-all duration-200 group"
                    >
                        <FontAwesomeIcon
                            :icon="faArrowLeft"
                            class="w-5 h-5 group-hover:transform group-hover:-translate-x-1 transition-transform duration-200"
                        />
                        <span class="font-medium">Volver al Inicio</span>
                    </Link>

                    <!-- Logo VASIR a la derecha -->
                    <Link :href="route('inicio')" class="flex items-center" title="Volver a inicio">
                        <img src="/images/logo.png" class="h-8" alt="VASIR" />
                    </Link>
                </div>
            </div>
        </header>

        <!-- Contenido principal con flex-grow para empujar el footer -->
        <main class="flex-grow py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer simple -->
        <footer class="bg-red-600 border-t border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="text-center text-white text-sm">
                    <p>&copy; {{ new Date().getFullYear() }} VASIR. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>

        <!-- Componente Toast para notificaciones -->
        <Toast />
    </div>
</template>

<style scoped>
.transition-transform {
    transition-property: transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Estilos para Toast notifications */
:deep(.p-toast) {
    z-index: 9999;
}

:deep(.p-toast .p-toast-message) {
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Animación de rotación para el ícono de carga */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
