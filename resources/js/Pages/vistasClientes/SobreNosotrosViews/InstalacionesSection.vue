<script setup>
import { faShop } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref } from 'vue';

const showImagenAmpliada = ref(false)

function ampliarImagen(event) {
  event.preventDefault()
  event.stopPropagation()
  showImagenAmpliada.value = true
  // Prevenir scroll del body cuando el modal está abierto
  document.body.style.overflow = 'hidden'
}

function cerrarImagen(event) {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  showImagenAmpliada.value = false
  // Restaurar scroll del body
  document.body.style.overflow = 'auto'
}
</script>

<template>
  <!-- Foto del negocio -->
  <div class="mb-8 sm:mb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl shadow-xl border border-gray-200">
        <div class="bg-gradient-to-r from-blue-600 to-red-600 text-white text-center py-4 sm:py-6 rounded-t-xl mb-6 sm:mb-8">
            <h3 class="text-xl sm:text-2xl md:text-3xl font-bold">
                <FontAwesomeIcon :icon="faShop" class="text-white mr-2" />
                Nuestras Instalaciones
            </h3>
        </div>
        <!-- Layout responsivo -->
        <div class="p-2 sm:p-3 md:p-4 lg:p-6">

            <!-- Móvil: Imagen arriba, grid de 2 columnas abajo -->
            <div class="block lg:hidden">
                <!-- Imagen -->
                <div class="mb-3 sm:mb-4 md:mb-6">
                    <div class="relative group cursor-pointer">
                <img
                    src="images/instalacion_negocio.jpg"
                    alt="Oficinas VASIR"
                    class="w-full rounded-lg sm:rounded-xl shadow-md sm:shadow-lg border-2 sm:border-4 border-gradient-to-r from-red-200 to-blue-200 cursor-pointer hover:scale-105 transition-transform duration-300 group-hover:shadow-2xl"
                    @click="ampliarImagen"
                    @keydown.enter="ampliarImagen"
                    tabindex="0"
                    role="button"
                    aria-label="Click para ampliar la imagen de nuestras instalaciones"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-blue-600/20 via-red-500/10 to-transparent rounded-lg sm:rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                <div class="absolute bottom-2 sm:bottom-4 left-2 sm:left-4 bg-white/90 backdrop-blur-sm px-2 sm:px-3 py-1 rounded-md sm:rounded-lg text-xs sm:text-sm font-medium text-gray-800 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none flex items-center gap-1 sm:gap-2">
                    <span class="text-blue-600">🔍</span>
                    <span>Click para ampliar</span>
                </div>
                <!-- Icono de zoom en el centro -->
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                    <div class="bg-white/20 backdrop-blur-sm rounded-full p-2 sm:p-4 border-2 border-white/30">
                    <svg class="w-4 h-4 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                    </svg>
                    </div>
                    </div>
                    </div>
                </div>

                <!-- Grid de información - 2 columnas en móvil -->
                <div class="grid grid-cols-2 gap-2 sm:gap-3 md:gap-4">
                    <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-3 md:p-4 shadow-md sm:shadow-lg border border-gray-200 hover:border-red-200 transition-all duration-300">
                    <div class="flex items-center mb-1 sm:mb-2 md:mb-3">
                        <div class="w-2 h-2 sm:w-3 sm:h-3 bg-gradient-to-r from-red-600 to-blue-600 rounded-full mr-1 sm:mr-2 md:mr-3"></div>
                        <h4 class="text-xs sm:text-sm md:text-lg font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent leading-tight">Ubicación Estratégica</h4>
                    </div>
                    <p class="text-xs sm:text-xs md:text-sm text-gray-700 leading-tight">
                        Contamos con modernas instalaciones ubicadas en el corazón de Chalatenango,
                        diseñadas para brindarte el mejor servicio y atención personalizada.
                    </p>
                    </div>
                    <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-3 md:p-4 shadow-md sm:shadow-lg border border-gray-200 hover:border-blue-200 transition-all duration-300">
                    <div class="flex items-center mb-1 sm:mb-2 md:mb-3">
                        <div class="w-2 h-2 sm:w-3 sm:h-3 bg-gradient-to-r from-blue-600 to-red-600 rounded-full mr-1 sm:mr-2 md:mr-3"></div>
                        <h4 class="text-xs sm:text-sm md:text-lg font-bold bg-gradient-to-r from-blue-600 to-red-600 bg-clip-text text-transparent leading-tight">Equipo Profesional</h4>
                    </div>
                    <p class="text-xs sm:text-xs md:text-sm text-gray-700 leading-tight">
                        Nuestro equipo profesional y apasionado por el turismo está siempre dispuesto
                        a ayudarte y a hacer de tu viaje una experiencia memorable que atesorarás para siempre.
                    </p>
                    </div>
                </div>
            </div>

            <!-- Desktop: Imagen a la izquierda, información a la derecha -->
            <div class="hidden lg:flex lg:items-center lg:gap-6 xl:gap-8">
                <!-- Imagen lado izquierdo -->
                <div class="w-1/2">
                    <div class="relative group cursor-pointer">
                    <img
                        src="images/instalacion_negocio.jpg"
                        alt="Oficinas VASIR"
                        class="w-full rounded-xl shadow-lg border-4 border-gradient-to-r from-red-200 to-blue-200 cursor-pointer hover:scale-105 transition-transform duration-300 group-hover:shadow-2xl"
                        @click="ampliarImagen"
                        @keydown.enter="ampliarImagen"
                        tabindex="0"
                        role="button"
                        aria-label="Click para ampliar la imagen de nuestras instalaciones"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-blue-600/20 via-red-500/10 to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                    <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-lg text-sm font-medium text-gray-800 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none flex items-center gap-2">
                        <span class="text-blue-600">🔍</span>
                        <span>Click para ampliar</span>
                    </div>
                    <!-- Icono de zoom en el centro -->
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4 border-2 border-white/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                        </svg>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Información lado derecho -->
                <div class="w-1/2 space-y-4 xl:space-y-6">
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200 hover:border-red-200 transition-all duration-300">
                    <div class="flex items-center mb-3 xl:mb-4">
                        <div class="w-3 h-3 bg-gradient-to-r from-red-600 to-blue-600 rounded-full mr-3"></div>
                        <h4 class="text-lg xl:text-xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent">Ubicación Estratégica</h4>
                    </div>
                    <p class="text-sm xl:text-base text-gray-700 leading-relaxed">
                        Contamos con modernas instalaciones ubicadas en el corazón de Chalatenango,
                        diseñadas para brindarte el mejor servicio y atención personalizada.
                    </p>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200 hover:border-blue-200 transition-all duration-300">
                    <div class="flex items-center mb-3 xl:mb-4">
                        <div class="w-3 h-3 bg-gradient-to-r from-blue-600 to-red-600 rounded-full mr-3"></div>
                        <h4 class="text-lg xl:text-xl font-bold bg-gradient-to-r from-blue-600 to-red-600 bg-clip-text text-transparent">Equipo Profesional</h4>
                    </div>
                    <p class="text-sm xl:text-base text-gray-700 leading-relaxed">
                        Nuestro equipo profesional y apasionado por el turismo está siempre dispuesto
                        a ayudarte y a hacer de tu viaje una experiencia memorable que atesorarás para siempre.
                    </p>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-400 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showImagenAmpliada"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 backdrop-blur-md p-4"
        @click.self="cerrarImagen"
        @keydown.esc="cerrarImagen"
        tabindex="0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        <!-- Contenedor principal del modal -->
        <div class="relative max-w-7xl max-h-[90vh] w-full h-full flex items-center justify-center">
          <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-90 rotate-1"
            enter-to-class="opacity-100 scale-100 rotate-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 scale-100 rotate-0"
            leave-to-class="opacity-0 scale-95 rotate-1"
          >
            <div
              v-if="showImagenAmpliada"
              class="relative bg-white rounded-2xl shadow-2xl p-2 max-w-full max-h-full overflow-hidden"
            >
              <!-- Imagen principal -->
              <img
                src="images/instalacion_negocio.jpg"
                alt="Oficinas VASIR - Vista ampliada"
                class="max-w-full max-h-[85vh] rounded-xl object-contain mx-auto block"
                @click.stop
              />

              <!-- Header del modal -->
              <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/60 to-transparent p-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                  </div>
                  <button
                    class="text-white/80 hover:text-white text-2xl font-light hover:bg-white/20 rounded-full w-8 h-8 flex items-center justify-center transition-all duration-200"
                    @click="cerrarImagen"
                    @keydown.enter="cerrarImagen"
                    tabindex="0"
                    aria-label="Cerrar imagen ampliada"
                  >
                    ×
                  </button>
                </div>
              </div>

              <!-- Footer del modal -->
              <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 rounded-b-2xl">
                <div class="text-center">
                  <h3 class="text-white text-xl font-bold mb-2 flex items-center justify-center gap-2" id="modal-title">
                    <span class="text-2xl">
                        <FontAwesomeIcon :icon="faShop" class="text-white" />
                    </span>
                    Instalaciones VASIR
                  </h3>
                  <p class="text-white/90 text-sm mb-3">
                    📍 2a Calle Oriente casa #12, Chalatenango, El Salvador
                  </p>
                  <div class="flex items-center justify-center space-x-4 text-white/80 text-xs">
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                      </svg>
                      Agencia de Viajes
                    </span>
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                      </svg>
                      Desde 2019
                    </span>
                  </div>
                </div>
              </div>

              <!-- Botón de cerrar lateral -->
              <button
                class="absolute top-6 right-6 bg-blue-600/90 hover:bg-blue-700 text-white rounded-full w-12 h-12 flex items-center justify-center transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-110 backdrop-blur-sm border-2 border-white/20"
                @click="cerrarImagen"
                @keydown.enter="cerrarImagen"
                tabindex="0"
                aria-label="Cerrar imagen ampliada"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>

              <!-- Indicador de zoom -->
              <div class="absolute bottom-6 right-6 bg-blue-600/90 text-white px-3 py-1 rounded-full text-xs font-medium flex items-center gap-2 backdrop-blur-sm border border-white/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Vista ampliada</span>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
