<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faRoute, faDoorOpen, faChevronDown, faUser, faHotel, faGear, faBoxesStacked,
            faClipboardList, faBox, faHouseChimneyUser, faBars, faFileInvoice, faUserPen,
            faTimes, faUsers, faCog, faStore,
            faIdCard} from "@fortawesome/free-solid-svg-icons";
import axios from "axios";
import { route } from "ziggy-js";

const page = usePage();
const user = page.props.auth?.user;
const isSidebarOpen = ref(false);
const isOpen = ref(false);
const showProfileMenu = ref(false);
const isConfigDropdownOpen = ref(false);

// Funciones para bloquear/desbloquear scroll del body
const lockBodyScroll = () => {
    document.body.style.overflow = 'hidden';
}

const unlockBodyScroll = () => {
    document.body.style.overflow = '';
}

const toggleDropdown = () => {
    // Al abrir Catálogos, cerrar Configuración
    if (!isOpen.value) {
        isConfigDropdownOpen.value = false;
    }
    isOpen.value = !isOpen.value;
};

const toggleConfigDropdown = () => {
    // Al abrir Configuración, cerrar Catálogos
    if (!isConfigDropdownOpen.value) {
        isOpen.value = false;
    }
    isConfigDropdownOpen.value = !isConfigDropdownOpen.value;
};

const logout = () => {
    router.post(route('logout'));
};

const handleResize = () => {
    // En móviles, cerrar el sidebar si cambia el tamaño
    if (window.innerWidth < 768) {
        isSidebarOpen.value = false;
        unlockBodyScroll();
    } else {
        // En escritorio, solo cerrar el overlay móvil
        isSidebarOpen.value = false;
        unlockBodyScroll();
    }
}

const handleGlobalClick = (e) => {
    const aside = document.querySelector("aside");

    // Cerrar sidebar en móvil cuando se hace click fuera
    if (
        isSidebarOpen.value &&
        aside &&
        !aside.contains(e.target)
    ) {
        isSidebarOpen.value = false;
        unlockBodyScroll();
    }

    // Cerrar dropdown de catálogos
    if (isOpen.value) {
        const catalogBtn = document.querySelector('button[title="Catálogos"]');
        if (
            (!aside || !aside.contains(e.target)) &&
            (!catalogBtn || !catalogBtn.contains(e.target))
        ) {
            isOpen.value = false;
        }
    }

    // Cerrar dropdown de configuración (igual que Catálogos)
    if (isConfigDropdownOpen.value) {
        const configBtn = document.querySelector('button[title="Configuración"]');
        if (
            (!aside || !aside.contains(e.target)) &&
            (!configBtn || !configBtn.contains(e.target))
        ) {
            isConfigDropdownOpen.value = false;
        }
    }

    // Cerrar menú de perfil
    if (showProfileMenu.value) {
        const menu = document.getElementById("profile-menu");
        if (menu && !menu.contains(e.target)) {
            showProfileMenu.value = false;
        }
    }
}

const openProfileMenu = (e) => {
    e.stopPropagation();
    showProfileMenu.value = true;
};

// Eliminada la declaración duplicada de toggleConfigDropdown

// Watcher para manejar el scroll cuando se abre/cierra el sidebar móvil
watch(isSidebarOpen, (newValue) => {
    // Solo bloquear el scroll si el sidebar móvil está abierto
    if (window.innerWidth < 768 && newValue) {
        lockBodyScroll();
    } else {
        unlockBodyScroll();
    }
})


// Función simplificada para navegación
const navigateAndCloseSidebar = (routeName) => {
    isConfigDropdownOpen.value = false;
    // Solo cerrar el sidebar en móviles
    isSidebarOpen.value = false;
    router.visit(route(routeName));
}

// Función para cerrar dropdown de catálogos con animación suave
const navigateAndCloseDropdown = (routeName) => {
    // Cerrar el dropdown primero para mostrar la animación
    isOpen.value = false;
    // Esperar a que termine la animación antes de navegar
    setTimeout(() => {
        router.visit(route(routeName));
    }, 200); // 200ms coincide con leave-active-class duration
}

// Función para cerrar dropdown de configuración con animación suave
const navigateAndCloseConfigDropdown = (routeName) => {
    // Cerrar el dropdown primero para mostrar la animación
    isConfigDropdownOpen.value = false;
    // Esperar a que termine la animación antes de navegar
    setTimeout(() => {
        router.visit(route(routeName));
    }, 200); // 200ms coincide con leave-active-class duration
}

onMounted(() => {
    window.addEventListener("resize", handleResize);
    // Sidebar expandido por defecto en escritorio
    isSidebarOpen.value = false;
    document.addEventListener("click", handleGlobalClick, true);
});
onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);
    document.removeEventListener("click", handleGlobalClick, true);
    // Asegurar que el scroll se desbloquee cuando se desmonte el componente
    unlockBodyScroll();
});

</script>

<template>
    <div class="min-h-screen flex flex-col bg-white">
        <Toast />
        <!-- Header principal profesional -->
        <header class="bg-gradient-to-r from-white/98 via-red-50/95 to-white/98 backdrop-blur-xl text-black shadow-lg fixed top-0 left-0 w-full z-50 border-b border-red-100/50 overflow-visible">
            <!-- Elementos decorativos de fondo -->
            <div class="absolute inset-0 bg-gradient-to-r from-red-100/20 via-transparent to-red-100/20"></div>
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-500 via-red-600 to-red-500 opacity-80"></div>

            <div class="relative px-4 sm:px-6 py-3 sm:py-4 flex justify-between items-center">
                <!-- Contenedor izquierdo: Menú + Logo -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Botón menú hamburguesa SOLO en móvil -->
                    <button
                        @click="isSidebarOpen = !isSidebarOpen"
                        title="Abrir menú de navegación"
                        class="block md:hidden rounded-xl p-2.5 sm:p-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 group backdrop-blur-sm transform hover:scale-105"
                        aria-label="Abrir menú de navegación"
                    >
                        <span class="sr-only">Abrir menú</span>
                        <FontAwesomeIcon
                            :icon="faBars"
                            class="w-5 h-5 sm:w-6 sm:h-6 group-hover:scale-110 transition-transform duration-200"
                        />
                    </button>

                    <!-- Logo con efecto profesional -->
                    <Link
                        :href="route('dashboard')"
                        title="Ir al Dashboard"
                        class="flex items-center cursor-pointer select-none group"
                    >
                        <img
                            src="/images/logo.png"
                            alt="Logo VASIR"
                            class="w-24 h-8 sm:w-28 sm:h-10 md:w-32 md:h-11 inline-block align-middle group-hover:scale-105 transition-transform duration-300 drop-shadow-sm"
                        />
                    </Link>
                </div>

                <!-- Contenedor derecho: Usuario -->
                <div class="flex items-center space-x-4">
                    <div class="relative user-menu-dropdown">
                        <button @click="openProfileMenu"
                            class="flex items-center px-3 py-2 rounded-xl bg-white/80 backdrop-blur-sm hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white transition-all duration-200 shadow-lg border border-red-100/50 group focus:outline-none focus:ring-2 focus:ring-red-300/50"
                            :title="user?.email"
                        >
                            <span class="font-semibold text-sm sm:text-base text-gray-700 mr-2 group-hover:text-white transition-colors duration-200">
                                Mi perfil
                            </span>
                            <FontAwesomeIcon
                                :icon="faUser"
                                class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 group-hover:text-white transition-colors duration-200 drop-shadow-sm"
                            />
                            <FontAwesomeIcon :icon="faChevronDown" class="w-4 h-4 sm:w-5 sm:h-5 text-gray-600 transition-transform duration-200 ml-2" :class="{ 'rotate-180': showProfileMenu }" />
                        </button>

                        <!-- Menú de perfil profesional -->
                        <transition
                            enter-active-class="transition-all duration-200"
                            leave-active-class="transition-all duration-150"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            leave-to-class="opacity-0 scale-95 -translate-y-1"
                        >
                            <div
                                v-if="showProfileMenu"
                                id="profile-menu"
                                class="absolute right-0 mt-3 w-80 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-red-100/50 z-[9999] overflow-hidden"
                                style="box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.25);"
                            >
                                <!-- Elementos decorativos -->
                                <div class="absolute inset-0 bg-gradient-to-br from-red-100/30 via-transparent to-red-100/30"></div>

                                <!-- Header del perfil profesional -->
                                <div class="relative px-6 py-5 bg-gradient-to-b from-red-600 via-red-500 to-red-400 text-white">
                                    <div class="flex items-center space-x-4">
                                        <div class="relative">
                                            <img
                                                :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(
                                                    user.name
                                                )}&background=dc2626&color=fff&size=96&bold=true`"
                                                class="w-14 h-14 rounded-full border-3 border-white shadow-xl"
                                                alt="Avatar del usuario"
                                            />
                                            <!-- Decoración de avatar -->
                                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-gradient-to-br from-red-400 to-red-600 border-2 border-white rounded-full shadow"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-base font-bold text-white truncate">
                                                {{ user.name }}
                                            </h3>
                                            <p class="text-sm text-red-100 truncate opacity-90">
                                                {{ user.email }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm border border-white/20 shadow">
                                            <svg class="w-2 h-2 mr-2 opacity-90" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                                            {{ user?.roles && user.roles.length > 0
                                                ? user.roles[0].name.charAt(0).toUpperCase() + user.roles[0].name.slice(1)
                                                : "Invitado"
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Opciones del menú profesional -->
                                <div class="relative py-3 bg-white/95 backdrop-blur-sm">
                                    <Link
                                        :href="route('profile.edit')"
                                        class="flex items-center px-6 py-3.5 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group hover:scale-[1.02] transform"
                                    >
                                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-red-100 to-red-50 text-red-600 mr-4 group-hover:from-red-200 group-hover:to-red-100 group-hover:scale-110 transition-all duration-200 shadow-sm">
                                            <FontAwesomeIcon :icon="faUserPen" class="w-4 h-4" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">Editar Perfil</span>
                                            <span class="text-xs text-gray-500 mt-0.5">Actualizar información personal</span>
                                        </div>
                                    </Link>
                                    <Link
                                        :href="route('inicio')"
                                        class="flex items-center px-6 py-3.5 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group hover:scale-[1.02] transform"
                                    >
                                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-red-100 to-red-50 text-red-600 mr-4 group-hover:from-red-200 group-hover:to-red-100 group-hover:scale-110 transition-all duration-200 shadow-sm">
                                            <FontAwesomeIcon :icon="faStore" class="w-4 h-4" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">Páginas Públicas</span>
                                            <span class="text-xs text-gray-500 mt-0.5">Serás redirigido a las vistas públicas.</span>
                                        </div>
                                    </Link>

                                    <!-- Separador elegante -->
                                    <div class="relative my-3 mx-6">
                                        <div class="absolute inset-0 flex items-center">
                                            <div class="w-full border-t border-red-100"></div>
                                        </div>
                                    </div>

                                    <button
                                        class="flex items-center w-full px-6 py-3.5 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group hover:scale-[1.02] transform"
                                        @click="logout"
                                    >
                                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-gray-100 to-gray-50 text-gray-600 mr-4 group-hover:from-red-200 group-hover:to-red-100 group-hover:text-red-600 group-hover:scale-110 transition-all duration-200 shadow-sm">
                                            <FontAwesomeIcon :icon="faDoorOpen" class="w-4 h-4" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">Cerrar Sesión</span>
                                            <span class="text-xs text-gray-500 mt-0.5">Salir de forma segura</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </header>

        <!-- Overlay móvil profesional -->
        <transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 z-[9998] bg-gradient-to-br from-black/60 via-red-900/30 to-black/60 backdrop-blur-sm md:hidden"
                @click="isSidebarOpen = false"
                @touchmove.prevent
                @scroll.prevent
            ></div>
        </transition>

        <div class="flex flex-1 pt-16">
            <!-- Sidebar móvil profesional -->
            <transition
                enter-active-class="transition-all duration-300"
                leave-active-class="transition-all duration-300"
                enter-from-class="-translate-x-full opacity-0"
                leave-to-class="-translate-x-full opacity-0"
            >
                <aside
                    v-if="isSidebarOpen"
                    class="fixed top-0 left-0 h-full w-80 bg-white shadow-2xl z-[9999] flex flex-col md:hidden border-r border-red-200"
                    @touchmove.stop
                >
                    <!-- Elementos decorativos -->
                    <div class="absolute inset-0 bg-gradient-to-br from-red-100/20 via-transparent to-red-100/20"></div>
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-400 via-red-500 to-red-400"></div>

                    <!-- Header del sidebar móvil con altura del header principal -->
                    <div class="relative flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-red-100/50 bg-white/80 backdrop-blur-sm" style="height: 64px;">
                        <Link :href="route('dashboard')" class="flex items-center group" @click="isSidebarOpen = false">
                            <img
                                src="/images/logo.png"
                                class="w-20 h-7 sm:w-24 sm:h-8 group-hover:scale-105 transition-transform duration-300"
                                alt="Logo VASIR"
                            />
                        </Link>
                        <button
                            @click="isSidebarOpen = false"
                            class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-red-600 text-white hover:bg-red-700 hover:scale-110 transition-all duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
                            aria-label="Cerrar menú"
                        >
                            <FontAwesomeIcon :icon="faTimes" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Navegación móvil profesional -->
                    <nav class="flex-1 overflow-y-auto overscroll-contain">
                        <div class="px-3 py-4 space-y-2">
                            <!-- Elementos decorativos internos -->
                            <div class="absolute inset-0 bg-gradient-to-br from-white/30 via-transparent to-red-50/30 pointer-events-none"></div>

                            <!-- Inicio -->
                            <Link
                                :href="route('dashboard')"
                                :class="[
                                    'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
                                    route().current('dashboard')
                                        ? 'bg-gradient-to-r from-red-600 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                @click.prevent="navigateAndCloseSidebar('dashboard')"
                            >
                                <FontAwesomeIcon
                                    :icon="faHouseChimneyUser"
                                    class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                />
                                <span class="font-semibold">Dashboard</span>
                            </Link>

                            <!-- Separador visual -->
                            <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

                            <!-- Desplegable de Catálogos -->
                            <div class="relative">
                                <button
                                    @click="toggleDropdown"
                                    class="relative w-full flex items-center justify-between py-3 px-3 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-300/50 group"
                                    :class="{
                                        'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg font-bold': isOpen || ['productos', 'tours', 'hoteles', 'paquetesVisas'].some(r => route().current(r)),
                                        'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg': !(isOpen || ['productos', 'tours', 'hoteles', 'paquetesVisas'].some(r => route().current(r)))
                                    }"
                                >
                                    <div class="flex items-center">
                                        <FontAwesomeIcon
                                            :icon="faBoxesStacked"
                                            class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                        />
                                        <span class="font-semibold">Catálogos</span>
                                    </div>
                                    <FontAwesomeIcon
                                        :icon="faChevronDown"
                                        :class="['w-4 h-4 transition-all duration-300 group-hover:scale-110', isOpen ? 'rotate-180' : '']"
                                    />
                                </button>

                                <!-- Submenu de Catálogos -->
                                <div v-show="isOpen" class="ml-6 mr-2 mt-2 flex flex-col space-y-1 bg-gray-50 rounded-lg p-2 border border-gray-200">
                                    <Link
                                        :href="route('productos')"
                                        :class="[
                                            'flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group',
                                            route().current('productos')
                                                ? 'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg font-bold'
                                                : 'text-gray-700 hover:bg-red-600 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseSidebar('productos')"
                                    >
                                        <FontAwesomeIcon :icon="faBox" class="w-4 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium">Productos</span>
                                    </Link>
                                    <Link
                                        :href="route('tours')"
                                        :class="[
                                            'flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group',
                                            route().current('tours')
                                                ? 'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg font-bold'
                                                : 'text-gray-700 hover:bg-red-600 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseSidebar('tours')"
                                    >
                                        <FontAwesomeIcon :icon="faRoute" class="w-4 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium">Tours</span>
                                    </Link>
                                    <Link
                                        :href="route('hoteles')"
                                        :class="[
                                            'flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group',
                                            route().current('hoteles')
                                                ? 'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg font-bold'
                                                : 'text-gray-700 hover:bg-red-600 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseSidebar('hoteles')"
                                    >
                                        <FontAwesomeIcon :icon="faHotel" class="w-4 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium">Hoteles</span>
                                    </Link>
                                    <Link
                                        :href="route('paquetesVisas')"
                                        :class="[
                                            'flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group',
                                            route().current('paquetesVisas')
                                                ? 'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg font-bold'
                                                : 'text-gray-700 hover:bg-red-600 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseSidebar('paquetesVisas')"
                                    >
                                        <FontAwesomeIcon :icon="faIdCard" class="w-4 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium">Paquetes Visa</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Separador visual -->
                            <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

                            <!-- Reservaciones -->
                            <Link
                                :href="route('reservas')"
                                :class="[
                                    'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
                                    route().current('reservas')
                                        ? 'bg-gradient-to-r from-red-600 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                @click.prevent="navigateAndCloseSidebar('reservas')"
                            >
                                <FontAwesomeIcon
                                    :icon="faClipboardList"
                                    class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                />
                                <span class="font-semibold">Reservas tours</span>
                            </Link>

                            <!-- Ventas -->
                            <Link
                                :href="route('ventas')"
                                :class="[
                                    'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
                                    route().current('ventas')
                                        ? 'bg-gradient-to-r from-red-600 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                @click.prevent="navigateAndCloseSidebar('ventas')"
                            >
                                <FontAwesomeIcon
                                    :icon="faFileInvoice"
                                    class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                />
                                <span class="font-semibold">Ventas</span>
                            </Link>

                            <!-- Inventario -->
                            <Link
                                :href="route('inventario')"
                                :class="[
                                    'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
                                    route().current('inventario')
                                        ? 'bg-gradient-to-r from-red-600 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                @click.prevent="navigateAndCloseSidebar('inventario')"
                            >
                                <FontAwesomeIcon
                                    :icon="faClipboardList"
                                    class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                />
                                <span class="font-semibold">Inventario</span>
                            </Link>

                            <!-- Separador visual -->
                            <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

                            <!-- Clientes -->
                            <Link
                                :href="route('clientes')"
                                :class="[
                                    'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
                                    route().current('clientes')
                                        ? 'bg-gradient-to-r from-red-600 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                @click.prevent="navigateAndCloseSidebar('clientes')"
                            >
                                <FontAwesomeIcon
                                    :icon="faUsers"
                                    class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                />
                                <span class="font-semibold">Clientes</span>
                            </Link>



                            <!-- Separador visual -->
                            <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

                            <!-- Informes -->
                            <Link
                                :href="route('informes')"
                                :class="[
                                    'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
                                    route().current('informes')
                                        ? 'bg-gradient-to-r from-red-600 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                @click.prevent="navigateAndCloseSidebar('informes')"
                            >
                                <FontAwesomeIcon
                                    :icon="faFileInvoice"
                                    class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                />
                                <span class="font-semibold">Informes</span>
                            </Link>

                            <!-- Separador visual -->
                            <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

                            <!-- Configuración -->
                            <div class="relative">
                                <button
                                    v-if="user && user.roles && user.roles.some(role => role.name === 'Administrador')"
                                    @click="toggleConfigDropdown"
                                    class="relative w-full flex items-center justify-between py-3 px-3 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-300/50 group"
                                    :class="{
                                        'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg font-bold': isConfigDropdownOpen,
                                        'text-gray-800 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white hover:shadow-lg': !isConfigDropdownOpen
                                    }"
                                >
                                    <div class="flex items-center">
                                        <FontAwesomeIcon
                                            :icon="faGear"
                                            class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300"
                                        />
                                        <span class="font-semibold">Configuración</span>
                                    </div>
                                    <FontAwesomeIcon
                                        :icon="faChevronDown"
                                        :class="['w-4 h-4 transition-all duration-300 group-hover:scale-110', isConfigDropdownOpen ? 'rotate-180' : '']"
                                    />
                                </button>

                                <!-- Submenu de Configuración -->
                                <div v-show="isConfigDropdownOpen" class="ml-6 mr-2 mt-2 flex flex-col space-y-1 bg-gray-50 rounded-lg p-2 border border-gray-200">
                                    <Link
                                        v-if="user && user.roles && user.roles.some(role => role.name === 'Administrador')"
                                        :href="route('settings')"
                                        @click="isSidebarOpen = false"
                                        class="flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group text-gray-700 hover:bg-red-600 hover:text-white hover:shadow-md"
                                    >
                                        <FontAwesomeIcon :icon="faCog" class="w-4 h-4 mr-3 text-gray-600 group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium">Sistema</span>
                                    </Link>
                                    <Link
                                        v-if="user && user.roles && user.roles.some(role => role.name === 'Administrador')"
                                        :href="route('gestionUsuarios')"
                                        @click="isSidebarOpen = false"
                                        class="flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group text-gray-700 hover:bg-red-600 hover:text-white hover:shadow-md"
                                    >
                                        <FontAwesomeIcon :icon="faUsers" class="w-4 h-4 mr-3 text-gray-600 group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium">Usuarios</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </nav>
                </aside>
            </transition>

            <!-- Sidebar de escritorio profesional -->
            <aside
                class="w-64 fixed top-16 left-0 h-[calc(100vh-64px)] bg-gradient-to-b from-red-600 via-red-500 to-red-400 backdrop-blur-xl text-white transition-all duration-300 ease-in-out shadow-2xl z-40 pt-6 hidden md:flex flex-col border-r border-red-100/50 overflow-x-visible overflow-y-auto"
            >
                <!-- Elementos decorativos -->
                <div class="absolute inset-0 bg-gradient-to-br from-red-100/20 via-transparent to-red-100/20 pointer-events-none"></div>
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-400 via-red-500 to-red-400"></div>



                <!-- Título del menú -->
                <div class="px-4 mb-6 text-center">
                    <h2 class="text-xl font-bold text-white drop-shadow-md">
                        MENÚ PRINCIPAL
                    </h2>
                    <div class="mt-2 w-16 h-1 bg-white/70 rounded-full mx-auto drop-shadow"></div>
                </div>

                <!-- Navegación principal -->
                <nav class="flex-1 px-3">
                    <div class="space-y-2">
                        <!-- Dashboard -->
                        <Link
                            :href="route('dashboard')"
                            :class="[
                                'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 justify-start',
                                route().current('dashboard')
                                    ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                    : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                            ]"
                            title="Dashboard"
                        >
                            <FontAwesomeIcon
                                :icon="faHouseChimneyUser"
                                class="mr-3 w-5 h-5 group-hover:scale-110 transition-transform duration-300"
                            />
                            <span class="font-semibold">Dashboard</span>
                        </Link>

                        <!-- Catálogos -->
                        <div class="relative">
                            <button
                                @click="toggleDropdown"
                                :class="[
                                    'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-300/50 w-full justify-between',
                                    [
                                        'productos', 'tours', 'hoteles', 'paquetesVisas'
                                    ].some(r => route().current(r))
                                        ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                        : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                                ]"
                                title="Catálogos"
                            >
                                <div class="flex items-center">
                                    <FontAwesomeIcon
                                        :icon="faBoxesStacked"
                                        class="mr-3 w-5 h-5 group-hover:scale-110 transition-transform duration-300"
                                    />
                                    <span class="font-semibold">Catálogos</span>
                                </div>
                                <FontAwesomeIcon
                                    :icon="faChevronDown"
                                    :class="['w-4 h-4 transition-all duration-300 group-hover:scale-110', isOpen ? 'rotate-180' : '']"
                                />
                            </button>



                            <!-- Dropdown expandido con animación -->
                            <transition
                                enter-active-class="transition-all duration-300"
                                leave-active-class="transition-all duration-200"
                                enter-from-class="opacity-0 -translate-y-2"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div v-if="isOpen" class="mt-2 ml-4 space-y-1 border-l-2 border-red-200/50 pl-4">
                                    <Link
                                        :href="route('productos')"
                                        :class="[
                                            'flex items-center px-3 py-2 rounded-lg transition-all duration-300 group',
                                            route().current('productos')
                                                ? 'bg-gradient-to-r from-red-700 to-red-500 text-white shadow-md font-bold'
                                                : 'text-white hover:bg-red-700 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseDropdown('productos')"
                                    >
                                        <FontAwesomeIcon :icon="faBox" class="w-4 h-4 mr-2 text-white group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium text-sm">Productos</span>
                                    </Link>
                                    <Link
                                        :href="route('tours')"
                                        :class="[
                                            'flex items-center px-3 py-2 rounded-lg transition-all duration-300 group',
                                            route().current('tours')
                                                ? 'bg-gradient-to-r from-red-700 to-red-500 text-white shadow-md font-bold'
                                                : 'text-white hover:bg-red-700 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseDropdown('tours')"
                                    >
                                        <FontAwesomeIcon :icon="faRoute" class="w-4 h-4 mr-2 text-white group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium text-sm">Tours</span>
                                    </Link>
                                    <Link
                                        :href="route('hoteles')"
                                        :class="[
                                            'flex items-center px-3 py-2 rounded-lg transition-all duration-300 group',
                                            route().current('hoteles')
                                                ? 'bg-gradient-to-r from-red-700 to-red-500 text-white shadow-md font-bold'
                                                : 'text-white hover:bg-red-700 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseDropdown('hoteles')"
                                    >
                                        <FontAwesomeIcon :icon="faHotel" class="w-4 h-4 mr-2 text-white group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium text-sm">Hoteles</span>
                                    </Link>
                                    <Link
                                        :href="route('paquetesVisas')"
                                        :class="[
                                            'flex items-center px-3 py-2 rounded-lg transition-all duration-300 group',
                                            route().current('paquetesVisas')
                                                ? 'bg-gradient-to-r from-red-700 to-red-500 text-white shadow-md font-bold'
                                                : 'text-white hover:bg-red-700 hover:text-white hover:shadow-md'
                                        ]"
                                        @click.prevent="navigateAndCloseDropdown('paquetesVisas')"
                                    >
                                        <FontAwesomeIcon :icon="faIdCard" class="w-4 h-4 mr-2 text-white group-hover:scale-110 transition-transform duration-300" />
                                        <span class="font-medium text-sm">Paquetes Visa</span>
                                    </Link>
                                </div>
                            </transition>
                        </div>

                        <!-- Reservaciones -->
                        <Link
                            :href="route('reservas')"
                            :class="[
                                'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 justify-start',
                                route().current('reservas')
                                    ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                    : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                            ]"
                            title="Reservaciones"
                        >
                            <FontAwesomeIcon
                                :icon="faClipboardList"
                                class="mr-3 w-5 h-5 text-white group-hover:scale-110 transition-transform duration-300"
                            />
                            <span class="font-semibold">Reservas tours</span>
                        </Link>

                        <!-- Ventas -->
                        <Link
                            :href="route('ventas')"
                            :class="[
                                'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 justify-start',
                                route().current('ventas')
                                    ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                    : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                            ]"
                            title="Ventas"
                        >
                            <FontAwesomeIcon
                                :icon="faFileInvoice"
                                class="mr-3 w-5 h-5 text-white group-hover:scale-110 transition-transform duration-300"
                            />
                            <span class="font-semibold">Ventas</span>
                        </Link>

                        <!-- Inventario -->
                        <Link
                            :href="route('inventario')"
                            :class="[
                                'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 justify-start',
                                route().current('inventario')
                                    ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                    : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                            ]"
                            title="Inventario"
                        >
                            <FontAwesomeIcon
                                :icon="faClipboardList"
                                class="mr-3 w-5 h-5 text-white group-hover:scale-110 transition-transform duration-300"
                            />
                            <span class="font-semibold">Inventario</span>
                        </Link>

                        <!-- Clientes -->
                        <Link
                            :href="route('clientes')"
                            :class="[
                                'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 justify-start',
                                route().current('clientes')
                                    ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                    : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                            ]"
                            title="Clientes"
                        >
                            <FontAwesomeIcon
                                :icon="faUsers"
                                class="mr-3 w-5 h-5 text-white group-hover:scale-110 transition-transform duration-300"
                            />
                            <span class="font-semibold">Clientes</span>
                        </Link>



                        <!-- Informes -->
                        <Link
                            :href="route('informes')"
                            :class="[
                                'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 justify-start',
                                route().current('informes')
                                    ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                    : 'text-white hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                            ]"
                            title="Informes"
                        >
                            <FontAwesomeIcon
                                :icon="faFileInvoice"
                                class="mr-3 w-5 h-5 text-white group-hover:scale-110 transition-transform duration-300"
                            />
                            <span class="font-semibold">Informes</span>
                        </Link>
                    </div>
                </nav>

                <!-- Configuración en la parte inferior (dropdown siempre visible en escritorio) -->
                <div class="mt-auto p-3 relative">
                    <button
                        v-if="user && user.roles && user.roles.some(role => role.name === 'Administrador')"
                        :class="[
                            'flex items-center px-4 py-3 rounded-xl transition-all duration-300 group hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-300/50 w-full text-white justify-between',
                            [
                                'roles', 'clientes', 'settings'
                            ].some(r => route().current(r))
                                ? 'bg-gradient-to-r from-red-700 to-red-500 text-white font-bold shadow-lg'
                                : 'hover:bg-gradient-to-r hover:from-red-700 hover:to-red-500 hover:text-white hover:shadow-lg'
                        ]"
                        title="Configuración"
                        @click="toggleConfigDropdown"
                    >
                        <FontAwesomeIcon
                            :icon="faGear"
                            class="mr-3 w-5 h-5 group-hover:scale-110 transition-transform duration-300"
                        />
                        <span class="font-semibold">Configuración</span>
                        <FontAwesomeIcon
                            :icon="faChevronDown"
                            class="ml-2 w-4 h-4 transition-all duration-300"
                            :class="isConfigDropdownOpen ? 'rotate-180' : ''"
                        />
                    </button>
                    <transition
                        enter-active-class="transition-all duration-300"
                        leave-active-class="transition-all duration-200"
                        enter-from-class="opacity-0 -translate-y-2"
                        leave-to-class="opacity-0 -translate-y-2"
                    >
                        <div v-if="isConfigDropdownOpen" class="mt-2 ml-4 space-y-1 border-l-2 border-red-200/50 pl-4">
                            <Link
                                v-if="user && user.roles && user.roles.some(role => role.name === 'Administrador')"
                                :href="route('settings')"
                                :class="[
                                    'flex items-center px-3 py-2 rounded-lg transition-all duration-300 group w-full text-left',
                                    route().current('settings')
                                        ? 'bg-gradient-to-r from-red-700 to-red-500 text-white shadow-md font-bold'
                                        : 'text-white hover:bg-red-700 hover:text-white hover:shadow-md'
                                ]"
                                @click.prevent="navigateAndCloseConfigDropdown('settings')"
                            >
                                <FontAwesomeIcon
                                    :icon="faCog" class="w-4 h-4 mr-2 text-white group-hover:scale-110 transition-transform duration-300" />
                                <span class="font-medium text-sm">Sistema</span>
                            </Link>
                            <Link
                                v-if="user && user.roles && user.roles.some(role => role.name === 'Administrador')"
                                :href="route('gestionUsuarios')"
                                :class="[
                                    'flex items-center px-3 py-2 rounded-lg transition-all duration-300 group w-full text-left',
                                    route().current('gestionUsuarios')
                                        ? 'bg-gradient-to-r from-red-700 to-red-500 text-white shadow-md font-bold'
                                        : 'text-white hover:bg-red-700 hover:text-white hover:shadow-md'
                                ]"
                                @click.prevent="navigateAndCloseConfigDropdown('gestionUsuarios')"
                            >
                                <FontAwesomeIcon
                                    :icon="faUsers" class="w-4 h-4 mr-2 text-white group-hover:scale-110 transition-transform duration-300" />
                                <span class="font-medium text-sm">Usuarios</span>
                            </Link>
                        </div>
                    </transition>
                </div>
            </aside>

            <!-- Contenido principal -->
            <div
                class="flex-1 flex flex-col transition-all duration-300 md:ml-64"
            >
                <!-- Área de contenido principal -->
                <main class="flex-1 overflow-auto bg-white">
                    <slot />
                </main>

                <!-- Footer -->
                <footer class="bg-red-600 border-t border-gray-200">
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <div class="text-center text-white text-sm">
                            <p>&copy; {{ new Date().getFullYear() }} VASIR. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Scrollbar personalizado */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(220, 38, 38, 0.3);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(220, 38, 38, 0.5);
}

/* Asegurar que los dropdowns colapsados aparezcan por encima */
.dropdown-collapsed {
    z-index: 999999 !important;
    position: absolute !important;
}
</style>

<style>
/*//////// Estilos responsivos para Toast notifications GLOBALES /////////*/
.p-toast {
  z-index: 9999 !important;
}

.p-toast .p-toast-message {
  margin: 0.5rem !important;
  min-width: 250px !important;
  max-width: 90vw !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}

/* Mobile styles */
@media (max-width: 639px) {
  .p-toast {
    width: 100% !important;
    left: 0 !important;
    right: 0 !important;
    top: 1rem !important;
  }

  .p-toast .p-toast-message {
    margin: 0.25rem !important;
    min-width: unset !important;
    max-width: calc(100vw - 1rem) !important;
    border-radius: 0.5rem !important;
  }

  .p-toast .p-toast-message .p-toast-message-content {
    padding: 0.75rem !important;
  }

  .p-toast .p-toast-message .p-toast-summary {
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.25rem !important;
  }

  .p-toast .p-toast-message .p-toast-detail {
    font-size: 0.8125rem !important;
    line-height: 1.4 !important;
  }

  .p-toast .p-toast-icon-close {
    width: 1.25rem !important;
    height: 1.25rem !important;
    top: 0.5rem !important;
    right: 0.5rem !important;
  }
}

/* Tablet styles */
@media (min-width: 640px) and (max-width: 1024px) {
  .p-toast .p-toast-message {
    max-width: 400px !important;
    margin: 0.75rem !important;
  }
}

/* Desktop styles */
@media (min-width: 1025px) {
  .p-toast .p-toast-message {
    max-width: 450px !important;
    margin: 1rem !important;
  }
}

/* Mejoras generales para mejor UX */
.p-toast .p-toast-message {
  border-radius: 0.5rem !important;
  border: none !important;
}
</style>
