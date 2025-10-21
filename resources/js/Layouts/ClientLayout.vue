<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header simple con botón de regreso -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <!-- Botón de regreso y logo -->
                    <div class="flex items-center space-x-4">
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

                        <div class="hidden sm:block w-px h-6 bg-gray-300"></div>

                        <!-- Logo VASIR -->
                        <Link :href="route('inicio')" class="flex items-center" title="Volver a inicio">
                            <img src="/images/logo.png" class="h-8" alt="VASIR" />
                        </Link>
                    </div>

                    <!-- Indicador de perfil -->
                    <div class="flex items-center space-x-2 bg-red-50 rounded-lg px-3 py-2 border border-red-200">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-red-700 font-medium text-sm">Mi Perfil</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer simple -->
        <footer class="bg-red-600 border-t border-gray-200 mt-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="text-center text-white text-sm">
                    <p>&copy; {{ new Date().getFullYear() }} VASIR. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.transition-transform {
    transition-property: transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}
</style>
