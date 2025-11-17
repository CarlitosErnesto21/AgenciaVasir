<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faList, faUser, faDoorOpen, faPhone, faEnvelope, faMapMarkerAlt, faSignInAlt, faUserPlus, faChevronDown, faHome, faUsers, faShoppingCart, faBoxOpen, faGlobe, faCalendarCheck, faMap, faLocationDot, faGlobeAmericas, faStore, faMapLocationDot, faVolcano, faHotel, faBus, faTimes, faArrowLeft, faArrowRight, faHouseChimneyUser, faSign, faSignIn } from '@fortawesome/free-solid-svg-icons'
import { faFacebook, faInstagram, faTiktok, faWhatsapp } from '@fortawesome/free-brands-svg-icons'
import { useCarritoStore } from '@/stores/carrito'
import Toast from 'primevue/toast'

const carrito = useCarritoStore()
const page = usePage()
const isSidebarOpen = ref(false)
const toursOpen = ref(false)
const toursOpenAside = ref(false)
const userMenuOpen = ref(false)

// Funciones para bloquear/desbloquear scroll del body
const lockBodyScroll = () => {
  // Obtener la posición actual del scroll
  const scrollY = window.scrollY

  document.body.style.position = 'fixed'
  document.body.style.top = `-${scrollY}px`
  document.body.style.left = '0'
  document.body.style.right = '0'
  document.body.style.overflow = 'hidden'
  // Guardar la posición para restaurarla después
  document.body.setAttribute('data-scroll-y', scrollY.toString())
}

const unlockBodyScroll = () => {
  const scrollY = document.body.getAttribute('data-scroll-y')

  document.body.style.position = ''
  document.body.style.top = ''
  document.body.style.left = ''
  document.body.style.right = ''
  document.body.style.overflow = ''

  if (scrollY) {
    window.scrollTo(0, parseInt(scrollY))
    document.body.removeAttribute('data-scroll-y')
  }
}

// Watcher para manejar el scroll cuando se abre/cierra el sidebar
watch(isSidebarOpen, (newValue) => {
  if (newValue) {
    lockBodyScroll()
  } else {
    unlockBodyScroll()
  }
})

const toggleTours = () => toursOpen.value = !toursOpen.value
const closeTours = e => { if (!e.target.closest('.tours-dropdown')) toursOpen.value = false }
const toggleUserMenu = () => userMenuOpen.value = !userMenuOpen.value
const closeUserMenu = e => { if (!e.target.closest('.user-menu-dropdown')) userMenuOpen.value = false }

onMounted(() => {
    document.addEventListener('click', closeTours)
    document.addEventListener('click', closeUserMenu)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', closeTours)
    document.removeEventListener('click', closeUserMenu)
    // Asegurar que el scroll se desbloquee cuando se desmonte el componente
    unlockBodyScroll()
})
const logout = () => {
    // Limpiar carrito al cerrar sesión
    carrito.limpiarCarritoAlCerrarSesion()

  // Eliminado: limpieza de claves de sessionStorage relacionadas con reservas/compras — funcionalidad retirada

    router.post(route('logout'), {}, {
        onSuccess: () => router.visit('/')
    })
}

// Computed properties para autenticación segura
const auth = computed(() => page.props.auth || {})
const config = computed(() => page.props.config || {})
const user = computed(() => auth.value.user || null)
const isAuthenticated = computed(() => !!user.value)

// Computed para verificar roles
const isCliente = computed(() => {
  if (!user.value?.roles) return false
  return user.value.roles.some(role => role.name === 'Cliente')
})

const isAdmin = computed(() => {
  if (!user.value?.roles) return false
  return user.value.roles.some(role => role.name === 'Administrador')
})

const isEmpleado = computed(() => {
  if (!user.value?.roles) return false
  return user.value.roles.some(role => role.name === 'Empleado')
})

const isAdminOrEmpleado = computed(() => isAdmin.value || isEmpleado.value)

// Watcher para detectar cambios en el estado de autenticación
watch(isAuthenticated, (newValue, oldValue) => {
  if (oldValue === true && newValue === false) {
    carrito.limpiarCarritoAlCerrarSesion()
  }
}, { immediate: false })
</script>

<template>
    <!-- Header principal -->
    <header class="bg-gradient-to-r from-white/98 via-blue-50/95 to-red-50/95 backdrop-blur-xl text-black shadow-2xl fixed top-0 left-0 w-full z-50 border-b-2 border-red-100/40 overflow-visible">
        <!-- Elementos decorativos de fondo -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-100/30 via-transparent to-red-100/30"></div>
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-500 via-blue-500 to-red-500 opacity-70"></div>
        <div class="relative px-5 py-5 md:px-6 md:py-8 lg:px-3 xl:py-7   flex justify-between items-center">
            <!-- Contenedor izquierdo: Menú + Logo -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <!-- Botón menú hamburguesa SOLO en móvil -->
                <button
                    @click="isSidebarOpen = !isSidebarOpen"
                    title="Abrir menú de navegación"
                    class="block lg:hidden"
                    aria-label="Abrir menú de navegación"
                >
                    <span class="sr-only">Abrir menú</span>
                    <FontAwesomeIcon :icon="faList" class="w-5 h-5 md:w-7 md:h-7  text-white hover:text-red-300 bg-red-600/80 hover:bg-red-600/90 p-2 rounded-full transform hover:scale-105 transition-transform duration-200" />
                </button>

                <!-- Logo con efecto -->
                <Link :href="route('inicio')" title="Ir a la página de inicio" class="flex items-center cursor-pointer select-none group">
                    <img src="/images/logo.png" alt="Logo VASIR" class="w-22 h-7 md:w-32 md:h-10 lg:w-28 lg:h-10 xl:w-44 xl:h-14 inline-block align-middle group-hover:scale-105 transition-transform duration-300 drop-shadow-sm" />
                </Link>
            </div>

            <!-- Menú de navegación con desplegable (solo desktop) -->
            <nav class="hidden lg:flex items-center bg-white/80 backdrop-blur-sm rounded-full lg:py-3 lg:px-5 xl:py-4 xl:px-6 shadow-xl border border-red-100/50">
              <!-- Indicador decorativo -->
              <div class="w-1 h-1 rounded-full bg-gradient-to-r from-red-500 to-blue-500 animate-pulse"></div>

                <Link
                    :href="route('inicio')"
                    :class=" [
                        'px-1 py-1 xl:px-3 xl:py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-300/50 tracking-wide hover:scale-105 transition-all duration-300 font-medium text-xs md:text-sm lg:text-sm xl:text-lg flex items-center group',
                        route().current('inicio')
                            ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                            : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                    ]"
                >
                    <FontAwesomeIcon :icon="faHome" class="w-5 h-4 mr-1 group-hover:scale-110 transition-transform duration-300" />
                    <span class="text-center">Inicio</span>
                </Link>

                <!-- Separador visual -->
                <div class="w-px h-6 bg-red-200/40"></div>

                <Link
                    :href="route('hoteles-clientes')"
                    :class=" [
                        'px-1 py-1 xl:px-3 xl:py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-300/50 tracking-wide hover:scale-105 transition-all duration-300 font-medium text-sm lg:text-sm xl:text-lg flex items-center justify-center group',
                        route().current('hoteles-clientes')
                            ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                            : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                    ]"
                >
                    <FontAwesomeIcon :icon="faHotel" class="w-5 h-4 mr-1 group-hover:scale-110 transition-transform duration-300" />
                    <span class="text-center">Hoteles</span>
                </Link>

                <!-- Separador visual -->
                <div class="w-px h-6 bg-red-200/40"></div>

                <!--Desplegable de Tours-->
                <div class="relative tours-dropdown" @click.stop="toggleTours" style="z-index: 9999;">
                    <button
                        class="px-1 py-1 xl:px-3 xl:py-1.5 rounded-xl hover:scale-105 transition-all duration-300 flex items-center focus:outline-none focus:ring-2 focus:ring-red-300/50 tracking-wide font-medium text-sm lg:text-sm xl:text-lg group"
                        :class=" [
                            (toursOpen || route().current('tours-nacionales') || route().current('tours-internacionales'))
                                ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                                : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                        ]"
                        type="button">
                        <FontAwesomeIcon :icon="faMapLocationDot" class="w-5 h-4 mr-1 group-hover:scale-110 transition-transform duration-300" />
                        Tours
                        <FontAwesomeIcon
                            :icon="faChevronDown"
                            :class="['w-5 h-4 ml-3 transition-all duration-300 group-hover:scale-110', toursOpen ? 'rotate-180' : '']"
                        />
                    </button>

                    <!-- Desplegable de tours con efecto glassmorphism -->
                    <div
                        class="absolute left-0 mt-2 w-64 bg-white/95 backdrop-blur-xl shadow-2xl border border-red-100/50 rounded-2xl z-[9999] transition-all overflow-hidden"
                        v-show="toursOpen"
                        style="z-index: 9999;"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-red-50/30 to-blue-50/30"></div>
                        <div class="relative p-2">
                            <Link
                                :href="route('tours-nacionales')"
                                :class=" [
                                    'flex items-center px-1 py-1 xl:px-3 xl:py-1.5 text-sm lg:text-sm xl:text-lg rounded-xl hover:scale-105 transition-all duration-300 font-medium group',
                                    route().current('tours-nacionales')
                                        ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                                        : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                                ]"
                            >
                                <FontAwesomeIcon :icon="faVolcano" class="w-5 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                                Tours Nacionales
                            </Link>
                            <Link
                                :href="route('tours-internacionales')"
                                :class=" [
                                    'flex items-center px-1 py-1 xl:px-3 xl:py-1.5 text-sm lg:text-sm xl:text-lg rounded-xl hover:scale-105 transition-all duration-300 font-medium group',
                                    route().current('tours-internacionales')
                                        ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                                        : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                                ]"
                            >
                                <FontAwesomeIcon :icon="faGlobeAmericas" class="w-5 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                                Tours Internacionales
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Separador visual -->
                <div class="w-px h-6 bg-red-200/40"></div>

                <Link
                    :href="route('tienda')"
                    :class=" [
                        'px-1 py-1 xl:px-3 xl:py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-300/50 tracking-wide hover:scale-105 transition-all duration-300 font-medium text-sm lg:text-sm xl:text-lg flex items-center justify-center group',
                        route().current('tienda')
                            ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                            : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                    ]"
                >
                    <FontAwesomeIcon :icon="faStore" class="w-5 h-4 mr-1 group-hover:scale-110 transition-transform duration-300" />
                    <span class="text-center">Tienda</span>
                </Link>

                <!-- Separador visual -->
                <div class="w-px h-6 bg-red-200/40"></div>

                <Link
                    :href="route('sobre-nosotros')"
                    :class=" [
                        'px-1 py-1 xl:px-3 xl:py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-300/50 tracking-wide hover:scale-105 transition-all duration-300 font-medium text-sm lg:text-sm xl:text-lg flex items-center justify-center group',
                        route().current('sobre-nosotros')
                            ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                            : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                    ]"
                >
                    <FontAwesomeIcon :icon="faUsers" class="w-5 h-4 mr-1 group-hover:scale-110 transition-transform duration-300" />
                    <span class="text-center">Sobre Nosotros</span>
                </Link>

                <!-- Separador visual -->
                <div class="w-px h-6 bg-red-200/40"></div>

                <Link
                    :href="route('contactos')"
                    :class=" [
                        'px-1 py-1 xl:px-3 xl:py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-300/50 tracking-wide hover:scale-105 transition-all duration-300 font-medium text-sm lg:text-sm xl:text-lg flex items-center justify-center group',
                        route().current('contactos')
                            ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold scale-105 shadow-lg'
                            : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
                    ]"
                >
                    <FontAwesomeIcon :icon="faEnvelope" class="w-5 h-4 mr-1 group-hover:scale-110 transition-transform duration-300" />
                    <span class="text-center">Contactos</span>
                </Link>

                <!-- Indicador decorativo final -->
                <div class="hidden lg:block w-1 h-1 rounded-full bg-gradient-to-r from-blue-500 to-red-500 animate-pulse"></div>
            </nav>

            <!-- Contenedor derecho: Auth -->
            <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 lg:space-x-2 xl:space-x-4">
                <template v-if="!isAuthenticated">
                    <Link
                        :href="route('login')"
                        class="flex items-center justify-center px-3 py-2 md:px-5 md:py-3 lg:px-2 lg:py-3 xl:px-5 xl:py-3 rounded-xl bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 text-sm md:text-base transform hover:scale-105"
                    >
                        <FontAwesomeIcon :icon="faSignIn" class="w-4 h-4 sm:w-5 sm:h-5 mr-2" />
                        <span class="hidden lg:block text-sm">Iniciar Sesión</span>
                        <span class="block lg:hidden text-sm">Entrar</span>
                    </Link>
                    <Link
                        :href="route('register')"
                        class="flex items-center justify-center px-3 py-2 md:px-5 md:py-3 lg:px-2 lg:py-3 xl:px-5 xl:py-3 rounded-xl text-red-700 hover:text-white font-semibold shadow-lg hover:shadow-xl border-2 border-red-600 bg-white/90 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 text-sm md:text-base transform hover:scale-105"
                    >
                        <FontAwesomeIcon :icon="faUserPlus" class="w-4 h-4 sm:w-5 sm:h-5 mr-2" />
                        <span class="hidden lg:block text-sm">Registrarse</span>
                        <span class="block lg:hidden text-sm">Nuevo</span>
                    </Link>
                </template>
                <template v-else>
                  <div class="relative user-menu-dropdown">
                    <button @click="toggleUserMenu"
                      class="flex items-center px-4 py-2 md:px-4 md:py-3 rounded-xl bg-white/80 backdrop-blur-sm hover:bg-gradient-to-r hover:from-red-600 hover:to-red-500 hover:text-white transition-all duration-200 shadow-lg border border-red-100/50 group focus:outline-none focus:ring-2 focus:ring-red-300/50"
                      :title="user?.email"
                    >
                      <span class=" font-semibold text-md sm:text-base text-gray-700 mr-2 group-hover:text-white transition-colors duration-200">
                        Perfil
                      </span>
                      <FontAwesomeIcon
                        :icon="faUser"
                        class="w-5 h-5 sm:w-5 sm:h-5 md:w-5 text-red-600 group-hover:text-white transition-colors duration-200 drop-shadow-sm"
                      />
                    </button>
                    <transition
                      enter-active-class="transition-all duration-200"
                      leave-active-class="transition-all duration-150"
                      enter-from-class="opacity-0 scale-95 -translate-y-1"
                      leave-to-class="opacity-0 scale-95 -translate-y-1"
                    >
                      <div
                        v-show="userMenuOpen"
                        class="absolute right-0 mt-3 w-80 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-red-100/50 z-[9999] overflow-hidden"
                        style="box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.25);"
                        :class="userMenuOpen ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
                      >
                        <!-- Elementos decorativos -->
                        <div class="absolute inset-0 bg-gradient-to-br from-red-100/30 via-transparent to-red-100/30"></div>
                        <!-- Header del perfil profesional -->
                        <div class="relative px-6 py-5 bg-gradient-to-b from-red-600 via-red-500 to-red-400 text-white">
                          <div class="flex items-center space-x-4">
                            <div class="relative">
                              <img
                                :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || user?.email)}&background=dc2626&color=fff&size=96&bold=true`"
                                class="w-14 h-14 rounded-full border-3 border-white shadow-xl"
                                alt="Avatar del usuario"
                              />
                              <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-gradient-to-br from-red-400 to-red-600 border-2 border-white rounded-full shadow"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                              <h3 class="text-base font-bold text-white truncate">
                                {{ user?.name || 'Usuario' }}
                              </h3>
                              <p class="text-sm text-red-100 truncate opacity-90">
                                {{ user?.email }}
                              </p>
                            </div>
                          </div>
                          <!-- Sin rol -->
                        </div>
                        <!-- Opciones del menú profesional -->
                        <div class="relative py-3 bg-white/95 backdrop-blur-sm">
                          <Link
                            v-if="isCliente"
                            :href="route('profile.edit')"
                            class="flex items-center px-6 py-3.5 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group hover:scale-[1.02] transform"
                            @click="userMenuOpen = false"
                          >
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-red-100 to-red-50 text-red-600 mr-4 group-hover:from-red-200 group-hover:to-red-100 group-hover:scale-110 transition-all duration-200 shadow-sm">
                              <FontAwesomeIcon :icon="faUser" class="w-4 h-4" />
                            </div>
                            <div class="flex flex-col">
                              <span class="font-semibold">Editar Perfil</span>
                              <span class="text-xs text-gray-500 mt-0.5">Actualizar información personal</span>
                            </div>
                          </Link>
                          <Link
                            v-if="isAdminOrEmpleado"
                            :href="route('dashboard')"
                            class="flex items-center px-6 py-3.5 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group hover:scale-[1.02] transform"
                            @click="userMenuOpen = false"
                          >
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-red-100 to-red-50 text-red-600 mr-4 group-hover:from-red-200 group-hover:to-red-100 group-hover:scale-110 transition-all duration-200 shadow-sm">
                              <FontAwesomeIcon :icon="faHouseChimneyUser" class="w-4 h-4" />
                            </div>
                            <div class="flex flex-col">
                              <span class="font-semibold">Dashboard administrativo</span>
                              <span class="text-xs text-gray-500 mt-0.5">Serás redirigido al dashboard administrativo.</span>
                            </div>
                          </Link>

                          <div class="relative my-3 mx-6">
                            <div class="absolute inset-0 flex items-center">
                              <div class="w-full border-t border-red-100"></div>
                            </div>
                          </div>
                          <button
                            v-if="isCliente"
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
                </template>
            </div>
        </div>
    </header>

    <!-- Drawer lateral para móvil -->
    <transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <!-- Overlay con glassmorphism mejorado -->
      <div
        v-if="isSidebarOpen"
        class="fixed inset-0 z-[9998] bg-gradient-to-br from-black/50 via-red-900/20 to-blue-900/30 backdrop-blur-sm lg:hidden"
        @click="isSidebarOpen = false"
        @touchmove.prevent
        @scroll.prevent
      ></div>
    </transition>
    <transition
      enter-active-class="transition-all duration-300"
      leave-active-class="transition-all duration-300"
      enter-from-class="-translate-x-full opacity-0"
      leave-to-class="-translate-x-full opacity-0"
    >
      <aside
        v-if="isSidebarOpen"
        class="fixed top-0 left-0 h-full w-80 bg-gradient-to-b from-white/95 via-blue-50/90 to-red-50/95 backdrop-blur-xl shadow-2xl z-[9999] flex flex-col lg:hidden border-r border-white/30 overflow-hidden"
        @touchmove.stop
      >
        <!-- Elementos decorativos de fondo -->
        <div class="absolute inset-0 bg-gradient-to-br from-red-100/20 via-transparent to-blue-100/20"></div>
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-400 via-blue-400 to-red-400"></div>

        <!-- Header del sidebar -->
        <div class="relative flex items-center justify-between px-4 sm:px-6 py-4 sm:py-6 border-b border-red-100/50 bg-white/80 backdrop-blur-sm">
            <Link :href="route('inicio')" class="flex items-center group" @click="isSidebarOpen = false">
              <img src="/images/logo.png" class="w-20 h-7 sm:w-24 sm:h-8 group-hover:scale-105 transition-transform duration-300" />
            </Link>
              <button
                @click="isSidebarOpen = false"
                class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gray-600/80 text-white hover:bg-gray-700/80 hover:scale-110 transition-all duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                aria-label="Cerrar menú"
              >
                <FontAwesomeIcon :icon="faTimes" class="w-4 h-4 sm:w-5 sm:h-5" />
                <span class="sr-only">Cerrar menú</span>
              </button>
        </div>

        <!-- Navegación principal con glassmorphism -->
        <nav class="relative flex flex-col space-y-2 px-3 py-4 sm:py-6 font-medium bg-white/60 backdrop-blur-lg rounded-xl shadow-xl border border-white/50 mx-2 mt-2 overflow-x-hidden overflow-y-auto min-h-0 flex-auto mb-2">
          <!-- Área scrollable -->
          <div class="flex-1 overflow-y-auto overflow-x-hidden overscroll-contain scroll-smooth">
            <!-- Elementos decorativos internos -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/30 via-transparent to-blue-50/30 pointer-events-none"></div>
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-red-300/40 to-transparent pointer-events-none"></div>

          <!-- Inicio -->
          <Link
            :href="route('inicio')"
            :class=" [
              'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
              route().current('inicio')
                ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold shadow-lg'
                : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
            ]"
            @click="isSidebarOpen = false"
          >
            <FontAwesomeIcon :icon="faHome" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300" />
            <span class="font-semibold">Inicio</span>
          </Link>
          <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

          <!-- Hoteles -->
          <Link
            :href="route('hoteles-clientes')"
            :class=" [
              'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
              route().current('hoteles-clientes')
                ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold shadow-lg'
                : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
            ]"
            @click="isSidebarOpen = false"
          >
            <FontAwesomeIcon :icon="faHotel" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300" />
            <span class="font-semibold">Hoteles</span>
          </Link>
          <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

          <!-- Desplegable de Tours -->
          <div class="relative">
            <button
              @click="toursOpenAside = !toursOpenAside"
              class="relative w-full flex items-center justify-between py-3 px-3 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-300/50 group"
              :class="{
                'bg-gradient-to-r from-red-600 to-red-400 text-white shadow-lg font-bold': toursOpenAside || route().current('tours-nacionales') || route().current('tours-internacionales'),
                'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg': !(toursOpenAside || route().current('tours-nacionales') || route().current('tours-internacionales'))
              }"
            >
              <div class="flex items-center">
                <FontAwesomeIcon :icon="faMapLocationDot" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300" />
                <span class="font-semibold">Tours</span>
              </div>
              <FontAwesomeIcon
                :icon="faChevronDown"
                :class="['w-4 h-4 transition-all duration-300 group-hover:scale-110', toursOpenAside ? 'rotate-180' : '']"
              />
            </button>

            <!-- Submenu de Tours -->
            <div v-show="toursOpenAside" class="ml-6 mr-2 mt-2 flex flex-col space-y-1 bg-white/40 backdrop-blur-sm rounded-lg p-2 border border-white/30">
              <Link
                :href="route('tours-nacionales')"
                :class=" [
                  'flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group',
                  route().current('tours-nacionales')
                    ? 'bg-gradient-to-r from-red-600 to-red-400 text-white shadow-lg font-bold'
                    : 'text-red-600 hover:bg-red-600 hover:text-white hover:shadow-md'
                ]"
                @click="isSidebarOpen = false"
              >
                <FontAwesomeIcon :icon="faVolcano" class="w-4 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                <span class="font-medium">Tours Nacionales</span>
              </Link>
              <Link
                :href="route('tours-internacionales')"
                :class=" [
                  'flex items-center py-2.5 px-3 rounded-lg transition-all duration-300 group',
                  route().current('tours-internacionales')
                    ? 'bg-gradient-to-r from-red-600 to-red-400 text-white shadow-lg font-bold'
                    : 'text-red-600 hover:bg-red-600 hover:text-white hover:shadow-md'
                ]"
                @click="isSidebarOpen = false"
              >
                <FontAwesomeIcon :icon="faGlobeAmericas" class="w-4 h-4 mr-3 group-hover:scale-110 transition-transform duration-300" />
                <span class="font-medium">Tours Internacionales</span>
              </Link>
            </div>
          </div>
          <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

          <!-- Tienda -->
          <Link
            :href="route('tienda')"
            :class=" [
              'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
              route().current('tienda')
                ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold shadow-lg'
                : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
            ]"
            @click="isSidebarOpen = false"
          >
            <FontAwesomeIcon :icon="faStore" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300" />
            <span class="font-semibold">Tienda</span>
          </Link>
          <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

          <!-- Sobre Nosotros -->
          <Link
            :href="route('sobre-nosotros')"
            :class=" [
              'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
              route().current('sobre-nosotros')
                ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold shadow-lg'
                : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
            ]"
            @click="isSidebarOpen = false"
          >
            <FontAwesomeIcon :icon="faUsers" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300" />
            <span class="font-semibold">Sobre Nosotros</span>
          </Link>
          <div class="relative w-full h-px bg-gradient-to-r from-transparent via-red-200/50 to-transparent my-1"></div>

          <!-- Contactos -->
          <Link
            :href="route('contactos')"
            :class=" [
              'relative flex items-center py-3 px-3 rounded-xl transition-all duration-300 group',
              route().current('contactos')
                ? 'bg-gradient-to-r from-red-600 to-red-400 text-white font-bold shadow-lg'
                : 'text-red-700 hover:bg-gradient-to-r hover:from-red-600 hover:to-red-400 hover:text-white hover:shadow-lg'
            ]"
            @click="isSidebarOpen = false"
          >
            <FontAwesomeIcon :icon="faEnvelope" class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform duration-300" />
            <span class="font-semibold">Contactos</span>
          </Link>
          </div>
          <!-- Indicador decorativo final -->
          <div class="flex justify-center py-4">
            <div class="w-16 h-1 rounded-full bg-gradient-to-r from-red-400 via-blue-400 to-red-400"></div>
          </div>
        </nav>
      </aside>
    </transition>

    <!-- AQUÍ VA EL CONTENIDO DINÁMICO -->
    <main class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-50 ">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-red-700 via-red-600 to-red-800 text-white relative overflow-hidden">
      <!-- Elementos decorativos de fondo -->
      <div class="absolute inset-0 bg-gradient-to-r from-red-900/20 via-transparent to-red-900/20"></div>
      <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-400 via-red-300 to-red-400"></div>

      <div class="relative max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="space-y-4">
          <div class="flex items-center space-x-1 mb-4">
            <span class="font-bold text-2xl bg-gradient-to-r from-white to-red-100 bg-clip-text text-transparent">VAS</span>
            <img src="/images/vasir.ico" alt="Pin Vasir" class="w-5 h-6 mx-1" style="vertical-align: middle;" />
            <span class="font-bold text-2xl bg-gradient-to-r from-white to-red-100 bg-clip-text text-transparent">R</span>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
            <p class="text-sm leading-relaxed text-red-50">
              Experiencias auténticas en El Salvador y más allá.<br>
              Turismo cultural, sostenible y creativo para viajeros de todas las edades<br><br>
              <span class="font-bold text-white">Ofrecemos:</span><br>
              <span class="text-red-100">
                - Tours nacionales e internacionales<br>
                - Venta de boletos aéreos<br>
                - Reservaciones en Hoteles<br>
                - Venta de artículos de viaje<br>
                - Servicio de transporte turístico<br>
                - Asesoría migratoria
              </span><br><br>
              <span class="font-bold text-yellow-300 text-leg">¡Viajes y Turismo!</span>
            </p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
              <FontAwesomeIcon :icon="faPhone" class="w-4 h-4 text-white" />
            </div>
            <h3 class="font-bold text-xl text-white">Contacto</h3>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 space-y-3">
            <div class="flex items-start space-x-3 p-2 rounded-lg hover:bg-white/10 transition-all duration-200">
              <FontAwesomeIcon :icon="faWhatsapp" class="w-5 h-5 text-green-300 mt-1 flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <a href="tel:+50379858777" class="text-red-100 hover:text-white underline decoration-dotted transition-colors">
                    +503 7985 8777
                  </a>
                  <span class="text-red-200">o al</span>
                  <a href="tel:+50323279199" class="text-red-100 hover:text-white underline decoration-dotted transition-colors">
                    +503 2327 9199
                  </a>
                </div>
              </div>
            </div>

            <div class="flex items-start space-x-3 p-2 rounded-lg hover:bg-white/10 transition-all duration-200">
              <FontAwesomeIcon :icon="faEnvelope" class="w-5 h-5 text-blue-300 mt-1 flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <span class="text-red-200 text-sm">Email:</span>
                <a :href="`https://mail.google.com/mail/?view=cm&fs=1&to=${config.mail_from_address || 'vasirtours19@gmail.com'}`"
                   class="block text-red-100 hover:text-white underline decoration-dotted transition-colors break-words"
                   target="_blank" rel="noopener">
                  {{ config.mail_from_address || 'vasirtours19@gmail.com' }}
                </a>
              </div>
            </div>

            <div class="flex items-start space-x-3 p-2 rounded-lg hover:bg-white/10 transition-all duration-200">
              <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-5 h-5 text-red-300 mt-1 flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <span class="text-red-200 text-sm">Dirección:</span>
                <a href="https://maps.app.goo.gl/Se61cWuQWd39rNkSA"
                   class="block text-red-100 hover:text-white underline decoration-dotted transition-colors"
                   target="_blank" rel="noopener">
                  2a Calle Oriente casa #12, Chalatenango, El Salvador
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 bg-gradient-to-br from-pink-400 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
              <FontAwesomeIcon :icon="faInstagram" class="w-4 h-4 text-white" />
            </div>
            <h3 class="font-bold text-xl text-white">Síguenos</h3>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 space-y-3">
            <div class="grid grid-cols-1 gap-3">
              <a href="https://www.facebook.com/share/1C7tZxDHzh/" target="_blank"
                 class="flex items-center space-x-3 p-2 rounded-lg hover:bg-white/10 transition-all duration-200 group">
                <div class="w-8 h-8 bg-gradient-to-br from-white/20 to-white/10 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                  <FontAwesomeIcon :icon="faFacebook" class="w-4 h-4 text-white group-hover:text-yellow-300 transition-colors" />
                </div>
                <span class="text-red-100 group-hover:text-white transition-colors font-medium">Facebook</span>
              </a>
              <a href="https://www.tiktok.com/@vasir_sv?_t=ZM-8wz8jwve57Y&_r=1" target="_blank"
                 class="flex items-center space-x-3 p-2 rounded-lg hover:bg-white/10 transition-all duration-200 group">
                <div class="w-8 h-8 bg-gradient-to-br from-white/20 to-white/10 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                  <FontAwesomeIcon :icon="faTiktok" class="w-4 h-4 text-white group-hover:text-yellow-300 transition-colors" />
                </div>
                <span class="text-red-100 group-hover:text-white transition-colors font-medium">TikTok</span>
              </a>
              <a href="https://www.instagram.com/vasir_sv?igsh=MWx3aGFzdnB5Y2l2OA==" target="_blank"
                 class="flex items-center space-x-3 p-2 rounded-lg hover:bg-white/10 transition-all duration-200 group">
                <div class="w-8 h-8 bg-gradient-to-br from-white/20 to-white/10 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                  <FontAwesomeIcon :icon="faInstagram" class="w-4 h-4 text-white group-hover:text-yellow-300 transition-colors" />
                </div>
                <span class="text-red-100 group-hover:text-white transition-colors font-medium">Instagram</span>
              </a>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
            <div class="flex items-center space-x-3 mb-3">
              <div class="w-6 h-6 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                <span class="text-white text-xs font-bold">★</span>
              </div>
              <h3 class="font-bold text-lg text-white">Categoría</h3>
            </div>
            <p class="text-red-100 font-medium">Agencia de turismo profesional</p>
          </div>
        </div>
      </div>

      <div class="relative bg-gradient-to-r from-red-900 via-red-800 to-red-900 text-center py-4 border-t border-red-600/30">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent"></div>
        <p class="relative text-red-100 text-sm font-medium">
          &copy; {{ new Date().getFullYear() }} Vasir. Todos los derechos reservados.
        </p>
      </div>
    </footer>

    <!-- Toast notifications con z-index máximo para aparecer sobre modales de pago -->
    <Toast />
</template>
<style>
.fade-enter-active {
  transition: opacity 1.2s;
}
.fade-leave-active {
  transition: opacity 0.8s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.fade-enter-to, .fade-leave-from {
  opacity: 1;
}
</style>

<style>
/*//////// Estilos responsivos para Toast notifications GLOBALES /////////*/
.p-toast {
  z-index: 999999 !important;
  position: fixed !important;
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

/* Estilos de colores removidos - usando los de app.css que son más suaves */
/*///////// Fin de los estilos para Toast GLOBALES ////////*/
</style>

