<script setup>
import { faChild, faExclamationTriangle, faMessage, faPerson, faUsers } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed } from 'vue'

// Props del componente
const props = defineProps({
  cuposAdultos: {
    type: Number,
    default: 1
  },
  cuposMenores: {
    type: Number,
    default: 0
  },
  tourSeleccionado: {
    type: Object,
    default: null
  }
})

// Emits para comunicación con el componente padre
const emit = defineEmits(['update:cuposAdultos', 'update:cuposMenores', 'mostrar-toast'])

// Computed para el total de cupos
const cupos_total = computed(() => {
  const adultos = Number(props.cuposAdultos) || 0
  const menores = Number(props.cuposMenores) || 0
  return adultos + menores
})

// Computed para cupos disponibles del tour
const cuposDisponibles = computed(() => {
  return props.tourSeleccionado?.cupos_disponibles !== null && props.tourSeleccionado?.cupos_disponibles !== undefined
    ? props.tourSeleccionado.cupos_disponibles
    : 0
})

// Funciones para incrementar/decrementar cupos
const incrementAdultos = () => {
  const nuevoTotal = cupos_total.value + 1
  if (props.cuposAdultos < 20 && nuevoTotal <= cuposDisponibles.value) {
    emit('update:cuposAdultos', props.cuposAdultos + 1)
  } else if (nuevoTotal > cuposDisponibles.value) {
    emit('mostrar-toast', {
      severity: 'warn',
      summary: 'Cupos insuficientes',
      detail: `Solo hay ${cuposDisponibles.value} cupos disponibles para este tour.`,
      life: 4000
    })
  }
}

const decrementAdultos = () => {
  if (props.cuposAdultos > 1) {
    emit('update:cuposAdultos', props.cuposAdultos - 1)
  }
}

const incrementMenores = () => {
  const nuevoTotal = cupos_total.value + 1
  if (props.cuposMenores < 20 && nuevoTotal <= cuposDisponibles.value) {
    emit('update:cuposMenores', props.cuposMenores + 1)
  } else if (nuevoTotal > cuposDisponibles.value) {
    emit('mostrar-toast', {
      severity: 'warn',
      summary: 'Cupos insuficientes',
      detail: `Solo hay ${cuposDisponibles.value} cupos disponibles para este tour.`,
      life: 4000
    })
  }
}

const decrementMenores = () => {
  if (props.cuposMenores > 0) {
    emit('update:cuposMenores', props.cuposMenores - 1)
  }
}
</script>

<template>
  <div>
    <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 flex flex-col sm:flex-row sm:items-center justify-between text-sm sm:text-base">
      <span class="flex items-center mb-1 sm:mb-0">
        <span class="text-base sm:text-lg mr-1 sm:mr-2">
            <FontAwesomeIcon :icon="faUsers" class="h-5 text-blue-500" />
        </span>
        <span class="text-blue-500">Cupos a reservar</span>
      </span>
      <div class="flex gap-2 self-start sm:self-auto">
        <span class="bg-blue-100 text-blue-700 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-bold">
          Disponibles: {{ cuposDisponibles }}
        </span>
        <span class="bg-red-100 text-red-700 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-bold">
          Total: {{ cupos_total }}
        </span>
      </div>
    </h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 bg-gray-50 p-3 sm:p-4 rounded-lg">
      <div>
        <label class="flex items-center text-xs sm:text-sm font-semibold mb-1 sm:mb-2">
          <span class="mr-1 sm:mr-2">
            <FontAwesomeIcon :icon="faPerson" class="h-5 text-blue-500" />
          </span>
          <span class="text-blue-500">Mayores de edad</span>
        </label>
        <!-- Botones personalizados para todas las pantallas -->
        <div class="flex justify-center gap-2 sm:gap-3">
          <button
            type="button"
            @click="decrementAdultos"
            :disabled="cuposAdultos <= 1"
            class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-colors font-bold text-sm sm:text-base"
          >
            -
          </button>
          <span class="flex items-center px-3 sm:px-4 py-2 sm:py-2.5 bg-white border border-gray-300 rounded-lg text-sm sm:text-base font-medium min-w-[3rem] sm:min-w-[4rem] justify-center">
            {{ cuposAdultos }}
          </span>
          <button
            type="button"
            @click="incrementAdultos"
            :disabled="cuposAdultos >= 20"
            class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-colors font-bold text-sm sm:text-base"
          >
            +
          </button>
        </div>
      </div>
      <div>
        <label class="flex items-center text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">
          <span class="mr-1 sm:mr-2">
            <FontAwesomeIcon :icon="faChild" class="h-5 text-blue-500" />
          </span>
          <span class="text-blue-500">Menores de edad</span>
        </label>
        <!-- Botones personalizados para todas las pantallas -->
        <div class="flex justify-center gap-2 sm:gap-3">
          <button
            type="button"
            @click="decrementMenores"
            :disabled="cuposMenores <= 0"
            class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-colors font-bold text-sm sm:text-base"
          >
            -
          </button>
          <span class="flex items-center px-3 sm:px-4 py-2 sm:py-2.5 bg-white border border-gray-300 rounded-lg text-sm sm:text-base font-medium min-w-[3rem] sm:min-w-[4rem] justify-center">
            {{ cuposMenores }}
          </span>
          <button
            type="button"
            @click="incrementMenores"
            :disabled="cuposMenores >= 20"
            class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-colors font-bold text-sm sm:text-base"
          >
            +
          </button>
        </div>
        <p class="text-xs text-amber-600 mt-1 sm:mt-2 flex items-center justify-center sm:justify-start">
          <span class="mr-1">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 text-yellow-500" />
          </span>
          <span class="hidden sm:inline">Presentar permiso firmado de padre/madre</span>
          <span class="sm:hidden">Requiere permiso de los padres</span>
        </p>
      </div>
    </div>

    <!-- Resumen de precios -->
    <div v-if="tourSeleccionado" class="bg-gradient-to-br from-white via-blue-50 to-purple-50 border-2 border-blue-200 p-4 sm:p-6 rounded-2xl mt-6 shadow-lg backdrop-blur-sm">
      <h5 class="font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4 sm:mb-5 flex items-center text-base sm:text-lg">
        <span class="mr-3 text-2xl">💰</span>
        <span>Resumen de Reserva</span>
      </h5>

      <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-4 sm:p-5 shadow-inner border border-gray-100">
        <div class="space-y-4">

          <!-- Información del tour -->
          <div class="bg-white border border-blue-200 rounded-lg p-4 shadow-sm">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <p class="font-bold text-gray-800 text-sm sm:text-base line-clamp-2">{{ tourSeleccionado.nombre }}</p>
                <div class="mt-2 flex items-center gap-2">
                  <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-bold">
                    ${{ (tourSeleccionado.precio || tourSeleccionado.precio_adulto || 0).toLocaleString() }}
                  </span>
                  <span class="text-gray-600 text-xs">por persona</span>
                </div>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-200 my-3"></div>

          <!-- Total a pagar -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg p-4 shadow-sm">
            <div class="flex justify-between items-center mb-3">
              <div class="flex items-center gap-2">
                <span class="text-gray-800 font-bold text-base sm:text-lg">Total a Pagar:</span>
                <div class="flex items-center gap-1">
                  <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                  <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                  <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                </div>
              </div>
              <span class="text-blue-700 font-black text-2xl sm:text-3xl">${{ ((tourSeleccionado.precio || tourSeleccionado.precio_adulto || 0) * cupos_total).toLocaleString() }}</span>
            </div>
            <div class="bg-white border border-blue-100 rounded-lg p-2">
              <p class="text-gray-600 text-xs sm:text-sm font-medium text-center">
                {{ cupos_total }} {{ cupos_total === 1 ? 'cupo' : 'cupos' }} × ${{ (tourSeleccionado.precio || tourSeleccionado.precio_adulto || 0).toLocaleString() }}
              </p>
            </div>
          </div>


          <div class="bg-gradient-to-br from-gray-50 to-blue-50 border-2 border-gray-200 rounded-xl p-4 sm:p-5 shadow-lg mt-4">
            <div class="flex items-center justify-center gap-3 mb-4">
              <div class="bg-blue-100 p-2 rounded-full">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="h-5 text-blue-600" />
              </div>
              <h6 class="font-bold text-lg text-gray-700">Información Importante</h6>
            </div>

            <div class="space-y-3 mb-4">
              <div class="bg-white border border-blue-100 rounded-lg p-3 shadow-sm">
                <div class="flex items-start gap-2">
                  <FontAwesomeIcon :icon="faMessage" class="h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                  <p class="text-gray-700 text-sm leading-relaxed">
                    <span class="font-semibold text-gray-800">Proceso de reserva:</span> Al completar esta reserva, recibirá un correo electrónico con los detalles de su reserva en estado PENDIENTE.
                  </p>
                </div>
              </div>

              <div class="bg-white border border-green-100 rounded-lg p-3 shadow-sm">
                <div class="flex items-start gap-2">
                  <FontAwesomeIcon :icon="faMessage" class="h-4 text-green-600 mt-0.5 flex-shrink-0" />
                  <p class="text-gray-700 text-sm leading-relaxed">
                    <span class="font-semibold text-gray-800">Confirmación:</span> Una vez procesado el pago, recibirá una segunda notificación por correo con la confirmación definitiva de su reserva.
                  </p>
                </div>
              </div>

              <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 shadow-sm">
                <div class="flex items-start gap-2">
                  <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 text-amber-600 mt-0.5 flex-shrink-0" />
                  <p class="text-gray-700 text-sm leading-relaxed">
                    <span class="font-semibold text-gray-800">Restricción:</span> Solo se permite una reserva activa por tour por cliente. Si ya tiene una reserva para este tour, no podrá realizar otra hasta completar o cancelar la existente.
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <p class="text-gray-700 font-medium text-xs sm:text-sm text-center">
                <FontAwesomeIcon :icon="faMessage" class="h-4 text-blue-500 inline-block mr-2" />
                Nuestro equipo se pondrá en contacto para coordinar los detalles finales
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
