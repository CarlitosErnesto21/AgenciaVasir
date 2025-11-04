<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4 overflow-hidden" style="touch-action:none; overscroll-behavior:contain; padding-top:0;">
    <!-- Contenedor principal responsive -->
    <div class="relative w-full max-w-xs sm:max-w-sm md:max-w-2xl lg:max-w-4xl xl:max-w-5xl
                h-auto md:h-[500px]
                bg-transparent rounded-xl shadow-lg overflow-hidden flex flex-col md:flex-row">

      <!-- Panel lateral animado - solo visible en desktop -->
      <div
        class="hidden md:flex absolute top-0 left-0 h-full w-1/2 flex-col items-center justify-center transition-all duration-700 z-20"
        :class="isRegister ? 'translate-x-full bg-gradient-to-br from-red-300 to-red-500' : 'bg-gradient-to-br from-red-300 to-red-500'"
      >
        <div class="text-white text-center px-4 lg:px-8">
          <h2 class="text-xl lg:text-2xl xl:text-3xl font-bold mb-2">
            {{ isRegister ? '¡Bienvenido!' : '¡Hola de nuevo!' }}
          </h2>
          <p class="mb-6 lg:mb-8 text-sm lg:text-base">
            {{ isRegister ? '¿Ya tienes una cuenta?' : '¿No tienes una cuenta?' }}
          </p>
          <button
            @click="props.toggleForm"
            class="border-2 border-white px-4 lg:px-6 xl:px-8 py-2 rounded-full text-white text-sm lg:text-base font-semibold hover:bg-white hover:text-red-500 transition-all duration-300 transform hover:scale-105"
          >
            {{ props.isRegister ? 'INICIA SESIÓN' : 'REGÍSTRATE' }}
          </button>
        </div>
      </div>

      <!-- Botones de cambio para móvil -->
      <div class="md:hidden flex justify-center space-x-4 p-4 bg-gradient-to-r from-red-300 to-red-500">
        <button
          @click="props.toggleForm"
          :class="!isRegister ? 'bg-white text-red-500' : 'border-2 border-white text-white'"
          class="px-4 py-2 rounded-full text-sm font-semibold transition-all duration-300 transform hover:scale-105"
        >
          INICIAR SESIÓN
        </button>
        <button
          @click="props.toggleForm"
          :class="isRegister ? 'bg-white text-red-500' : 'border-2 border-white text-white'"
          class="px-4 py-2 rounded-full text-sm font-semibold transition-all duration-300 transform hover:scale-105"
        >
          REGISTRARSE
        </button>
      </div>

      <!-- Panel de formularios -->
      <div
        class="flex-1 md:absolute md:top-0 md:left-0 md:w-full md:h-full flex transition-all duration-700"
        :class="isRegister ? 'md:-translate-x-1/2' : ''"
      >
        <!-- Lado izquierdo (vacío solo para simetría visual en desktop) -->
        <div class="hidden md:block w-1/2 h-full"></div>

        <!-- Formularios -->
        <div class="w-full md:w-1/2 h-full flex flex-col items-center justify-center bg-white p-6 relative">
          <!-- Login -->
          <div
            class="w-full flex flex-col items-center justify-center transition-all duration-700"
            :class="isRegister ? 'md:opacity-0 md:pointer-events-none md:absolute md:inset-0 hidden md:flex' : 'md:opacity-100 md:pointer-events-auto md:absolute md:inset-0 block md:flex'"
          >
            <slot name="login" />
          </div>

          <!-- Register -->
          <div
            class="w-full flex flex-col items-center justify-center transition-all duration-700"
            :class="isRegister ? 'md:opacity-100 md:pointer-events-auto md:absolute md:inset-0 block md:flex' : 'md:opacity-0 md:pointer-events-none md:absolute md:inset-0 hidden md:flex'"
          >
            <slot name="register" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  isRegister: Boolean,
  toggleForm: Function,
});
</script>
