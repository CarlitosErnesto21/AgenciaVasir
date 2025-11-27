<script setup>
import { computed, ref, watch, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import {
  faCheck, faXmark, faCalendarDays, faEye,
  faExclamationTriangle, faInfoCircle, faSpinner, faTimes, faPhone
} from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';
import { faEnvelope } from '@fortawesome/free-solid-svg-icons';

// Props
const props = defineProps({
  // Modal Más Acciones
  visible: {
    type: Boolean,
    default: false
  },
  // Modal para ver detalles de reserva
  detallesVisible: {
    type: Boolean,
    default: false
  },
  // Modal para rechazar reserva
  rechazarVisible: {
    type: Boolean,
    default: false
  },
  // ELIMINADO: Modal para reprogramar reserva - ya no se usa
  // Datos de la reserva seleccionada
  reserva: {
    type: Object,
    default: () => ({})
  },
  // Tour asociado
  tour: {
    type: Object,
    default: () => ({})
  },
  // Estilo del diálogo
  dialogStyle: {
    type: Object,
    default: () => ({})
  },
  // Estados de loading
  procesando: {
    type: Boolean,
    default: false
  },
  confirmandoReserva: {
    type: Boolean,
    default: false
  },
  rechazandoReserva: {
    type: Boolean,
    default: false
  },
  // Estados disponibles para reservas
  estadosReservas: {
    type: Array,
    default: () => []
  },
  // Estados disponibles para tours
  estadosTours: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits([
  'update:visible',
  'update:detallesVisible',
  'update:rechazarVisible',
  // ELIMINADO: 'update:reprogramarVisible', 'reprogramar', 'finalizar' - ya no se usan
  'confirmar',
  'rechazar',
  'verDetalles',
  'actualizar-reserva'
]);

// Computed para v-model del modal Más Acciones
const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

// Computed para v-model del modal Detalles
const isDetallesVisible = computed({
  get: () => props.detallesVisible,
  set: (value) => emit('update:detallesVisible', value)
});

// Computed para v-model del modal Rechazar
const isRechazarVisible = computed({
  get: () => props.rechazarVisible,
  set: (value) => emit('update:rechazarVisible', value)
});

// ELIMINADO: isReprogramarVisible y variables de reprogramación - ya no se usan

// Estado local para los formularios
const motivoRechazo = ref('');

// Funciones para obtener acciones disponibles según el estado
// NOTA: Se eliminó 'reprogramar' - ahora solo se reprograma desde el TOUR
const getAccionesDisponibles = (reserva) => {
  if (!reserva || !reserva.estado) return [];

  switch (reserva.estado) {
    case 'PENDIENTE':
      return ['confirmar', 'rechazar', 'detalles'];
    case 'CONFIRMADA':
      return ['rechazar', 'detalles'];
    case 'REPROGRAMADA':
      return ['rechazar', 'detalles'];
    case 'CANCELADA':
      return ['detalles'];
    case 'FINALIZADA':
      return ['detalles'];
    default:
      return ['detalles'];
  }
};

// Función para obtener color del estado de reserva
const getColorEstadoReserva = (estado) => {
  const estadoObj = props.estadosReservas.find(e => e.value === estado);
  return estadoObj?.color || 'bg-gray-100 text-gray-800';
};

// Función para obtener color del estado de tour
const getColorEstadoTour = (estado) => {
  const estadoObj = props.estadosTours.find(e => e.value === estado);
  return estadoObj?.color || 'bg-gray-100 text-gray-800';
};

// Función para formatear fecha con información de hora cuando sea relevante
const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A';

  const fechaObj = new Date(fecha);

  // Si la hora es 00:00, mostrar solo la fecha
  if (fechaObj.getHours() === 0 && fechaObj.getMinutes() === 0) {
    return fechaObj.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  }

  // Si tiene hora específica, mostrar fecha y hora en formato AM/PM
  return fechaObj.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
};// Función para obtener fecha mínima (hoy)
const getMinDate = () => {
  return new Date();
};

// Funciones para manejar las acciones
const confirmarReserva = () => {
  emit('confirmar', props.reserva);
  // No cerrar el modal aquí, se cerrará cuando termine el proceso
};

const abrirModalRechazar = () => {
  motivoRechazo.value = '';
  isVisible.value = false;
  isRechazarVisible.value = true;
};

const rechazarReserva = () => {
  emit('rechazar', {
    reserva: props.reserva,
    motivo: motivoRechazo.value
  });
};

// ELIMINADO: abrirModalReprogramar, reprogramarReserva y finalizarReserva - ya no se usan

const verDetalles = async () => {
  isVisible.value = false;
  isDetallesVisible.value = true;
};// Limpiar formularios al cerrar modales
const limpiarFormularios = () => {
  motivoRechazo.value = '';
};

// Función para controlar el scroll del body
const bloquearScroll = () => {
  document.body.style.overflow = 'hidden';
  document.body.style.paddingRight = '15px'; // Compensar por scrollbar
};

const restaurarScroll = () => {
  document.body.style.overflow = '';
  document.body.style.paddingRight = '';
};

// Watch para limpiar formularios cuando se cierran los modales
const cerrarModalRechazar = () => {
  isRechazarVisible.value = false;
  limpiarFormularios();
};

// Función para abrir Gmail
const abrirGmail = (email) => {
  if (!email) return;

  // Detectar si es móvil
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  if (isMobile) {
    // En móviles, usar mailto: que el sistema operativo maneje
    window.location.href = `mailto:${email}`;
  } else {
    // En escritorio, abrir Gmail web directamente
    window.open(`https://mail.google.com/mail/?view=cm&fs=1&to=${email}`, '_blank');
  }
};

// Watchers para controlar el scroll cuando se abran/cierren los modales
watch(isVisible, (newValue) => {
  if (newValue) {
    bloquearScroll();
  } else {
    // Solo restaurar scroll si ningún otro modal está abierto
    if (!isDetallesVisible.value && !isRechazarVisible.value) {
      restaurarScroll();
    }
  }
});

watch(isDetallesVisible, (newValue) => {
  if (newValue) {
    bloquearScroll();
  } else {
    // Solo restaurar scroll si ningún otro modal está abierto
    if (!isVisible.value && !isRechazarVisible.value) {
      restaurarScroll();
    }
  }
});watch(isRechazarVisible, (newValue) => {
  if (newValue) {
    bloquearScroll();
  } else {
    // Solo restaurar scroll si ningún otro modal está abierto
    if (!isVisible.value && !isDetallesVisible.value) {
      restaurarScroll();
    }
  }
});

// Limpiar scroll al desmontar el componente
onUnmounted(() => {
  restaurarScroll();
});

// ELIMINADO: cerrarModalReprogramar - ya no se usa
</script>

<template>
  <!-- Modal de Más Acciones -->
  <Dialog
    v-model:visible="isVisible"
    header="Más Acciones"
    :modal="true"
    :style="dialogStyle"
    :closable="false"
    :draggable="false"
  >
    <div class="space-y-4">
      <div class="text-center mb-4">
        <h4 class="text-lg font-semibold text-gray-800">
          Reserva: <span class="text-blue-600">{{ reserva.entidad_nombre || 'Reserva' }}</span>
        </h4>
        <p class="text-sm text-gray-600 mt-1">
          Cliente: {{ (reserva.cliente?.user?.name) || (reserva.cliente?.nombres) || 'N/A' }}
        </p>
        <p class="text-xs text-gray-500 mt-1">Selecciona una acción a realizar</p>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <!-- Botón para ver detalles -->
        <button
          v-if="getAccionesDisponibles(reserva).includes('detalles')"
          class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start"
          @click="verDetalles"
        >
          <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
          <div class="text-left flex-1">
            <div class="font-medium">Ver Detalles</div>
            <div class="text-xs opacity-90">Información completa de la reserva</div>
          </div>
        </button>

        <!-- Botón para confirmar -->
        <button
          v-if="getAccionesDisponibles(reserva).includes('confirmar')"
          class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start disabled:opacity-50 disabled:cursor-not-allowed"
          @click="confirmarReserva"
          :disabled="confirmandoReserva"
        >
          <FontAwesomeIcon
            v-if="confirmandoReserva"
            :icon="faSpinner"
            class="h-5 w-5 animate-spin"
          />
          <FontAwesomeIcon
            v-else
            :icon="faCheck"
            class="h-5 w-5"
          />
          <div class="text-left flex-1">
            <div class="font-medium">
              {{ confirmandoReserva ? 'Confirmando...' : 'Confirmar Reserva' }}
            </div>
            <div class="text-xs opacity-90">
              {{ confirmandoReserva ? 'Procesando la confirmación' : 'Aprobar la reserva del cliente' }}
            </div>
          </div>
        </button>

        <!-- Botón para rechazar -->
        <button
          v-if="getAccionesDisponibles(reserva).includes('rechazar')"
          class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start disabled:opacity-50 disabled:cursor-not-allowed"
          @click="abrirModalRechazar"
          :disabled="rechazandoReserva"
        >
          <FontAwesomeIcon
            v-if="rechazandoReserva"
            :icon="faSpinner"
            class="h-5 w-5 animate-spin"
          />
          <FontAwesomeIcon
            v-else
            :icon="faXmark"
            class="h-5 w-5"
          />
          <div class="text-left flex-1">
            <div class="font-medium">
              {{ rechazandoReserva ? 'Rechazando...' : 'Rechazar Reserva' }}
            </div>
            <div class="text-xs opacity-90">
              {{ rechazandoReserva ? 'Procesando el rechazo' : 'Rechazar con motivo especificado' }}
            </div>
          </div>
        </button>

        <!-- ELIMINADO: Botón para reprogramar y finalizar reserva individual -->
        <!-- Ahora las reservas se manejan automáticamente desde el TOUR -->
      </div>
    </div>

    <template #footer>
      <div class="flex justify-center gap-4 w-full mt-6">
        <button
          type="button"
          @click="isVisible = false"
          class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
        >
          <FontAwesomeIcon :icon="faTimes" class="h-5" />
          Cerrar
        </button>
      </div>
    </template>
  </Dialog>

  <!-- Modal para ver detalles de reserva -->
  <Dialog
    v-model:visible="isDetallesVisible"
    modal
    header="Detalles de la Reserva"
    :style="{ width: '95vw', maxWidth: '800px' }"
    :closable="false"
    :draggable="false"
  >
    <div v-if="reserva" class="space-y-4 sm:space-y-6">
      <!-- Información del cliente -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4">
        <h4 class="font-semibold text-blue-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
          <FontAwesomeIcon :icon="faEye" class="text-blue-600 text-sm sm:text-base" />
          Información del Cliente
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-xs sm:text-sm">
          <div class="break-words">
            <span class="font-medium text-gray-700">Nombre:</span>
            <span class="ml-2">{{ (reserva.cliente?.user?.name) || (reserva.cliente?.nombres) || 'Problema al obtener nombre o no existe' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Documento:</span>
            <span class="ml-2">{{ reserva.cliente?.numero_identificacion || 'Problema al obtener documento o no existe' }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="font-medium text-gray-700">Email:</span>
            <div v-if="(reserva.cliente?.user?.email) || (reserva.cliente?.correo)" class="flex flex-wrap items-center gap-2">
              <span class="text-xs sm:text-sm break-all">{{ reserva.cliente?.user?.email || reserva.cliente?.correo }}</span>
              <a
                @click="abrirGmail(reserva.cliente?.user?.email || reserva.cliente?.correo)"
                href="#"
                class="inline-flex items-center gap-1 px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md transition-colors duration-200 cursor-pointer"
                title="Abrir Gmail para enviar correo"
              >
                <FontAwesomeIcon :icon="faEnvelope" class="h-3 w-3" />
                <span class="hidden sm:inline">Enviar Email</span>
              </a>
            </div>
            <span v-else class="text-gray-500 italic text-xs sm:text-sm">Problema al obtener email o no existe</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="font-medium text-gray-700">Teléfono:</span>
            <div v-if="reserva.cliente?.telefono" class="flex flex-wrap items-center gap-2">
              <span class="text-xs sm:text-sm">{{ reserva.cliente.telefono }}</span>
              <div class="flex gap-1">
                <a
                  :href="`https://wa.me/${reserva.cliente.telefono.replace(/[^0-9]/g, '')}`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1 px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs rounded-md transition-colors duration-200"
                  title="Enviar mensaje por WhatsApp"
                >
                  <FontAwesomeIcon :icon="faWhatsapp" class="h-3 w-3" />
                  <span class="hidden sm:inline">WhatsApp</span>
                </a>
                <a
                  :href="`tel:${reserva.cliente.telefono}`"
                  class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md transition-colors duration-200"
                  title="Llamar por teléfono"
                >
                  <FontAwesomeIcon :icon="faPhone" class="h-3 w-3" />
                  <span class="hidden sm:inline">Llamar</span>
                </a>
              </div>
            </div>
            <span v-else class="text-gray-500 italic text-xs sm:text-sm">Problema al obtener teléfono o no existe</span>
          </div>
        </div>
      </div>

      <!-- Información del servicio -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4">
        <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
          <FontAwesomeIcon :icon="faCalendarDays" class="text-gray-600 text-sm sm:text-base" />
          Información del Servicio
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-xs sm:text-sm">
          <div class="break-words">
            <span class="font-medium text-gray-700">Servicio:</span>
            <span class="ml-2">{{ reserva.entidad_nombre || 'Problema al obtener servicio o no existe' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Reservado:</span>
            <span class="ml-2">{{ formatearFecha(reserva.fecha_reserva) || 'Problema al obtener fecha o no existe' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Estado:</span>
            <span :class="getColorEstadoReserva(reserva.estado)" class="ml-2 px-2 py-1 rounded-full text-xs font-medium">
              {{ estadosReservas.find(e => e.value === reserva.estado)?.label || reserva.estado || 'Problema al obtener estado o no existe' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Información de personas y precio -->
      <div class="bg-green-50 border border-green-200 rounded-lg p-3 sm:p-4">
        <h4 class="font-semibold text-green-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
          <FontAwesomeIcon :icon="faInfoCircle" class="text-green-600 text-sm sm:text-base" />
          Detalles de la Reserva
        </h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 text-xs sm:text-sm">
          <div>
            <span class="font-medium text-gray-700">Adultos:</span>
            <span class="ml-2">{{ reserva.mayores_edad || 0 }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Niños:</span>
            <span class="ml-2">{{ reserva.menores_edad || 0 }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Total:</span>
            <span class="ml-2 font-bold text-green-600">${{ Number(reserva.total || 0).toFixed(2) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Ref. Wompi:</span>
            <span class="ml-2" v-if="reserva.pagos && reserva.pagos.length > 0 && reserva.pagos[0].referencia_wompi">
              {{ reserva.pagos[0].referencia_wompi }}
            </span>
            <span class="ml-2 text-gray-400 italic" v-else>Sin referencia</span>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-center gap-4 w-full mt-6">
        <button
          type="button"
          @click="isDetallesVisible = false"
          class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
        >
          <FontAwesomeIcon :icon="faTimes" class="h-5" />Cerrar
        </button>
      </div>
    </template>
  </Dialog>

  <!-- Modal para rechazar reserva -->
  <Dialog
    v-model:visible="isRechazarVisible"
    modal
    header="Rechazar Reserva"
    :style="dialogStyle"
    :closable="false"
    :draggable="false"
  >
    <div class="space-y-3 sm:space-y-4">
      <!-- Advertencia sobre eliminación permanente -->
      <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 sm:p-4 mb-4">
        <div class="flex items-start gap-2 sm:gap-3">
          <FontAwesomeIcon :icon="faExclamationTriangle" class="text-orange-600 text-base sm:text-lg mt-1 flex-shrink-0" />
          <div>
            <h4 class="font-semibold text-orange-800 text-sm sm:text-base mb-2">¡ADVERTENCIA - Acción Permanente!</h4>
            <div class="text-xs sm:text-sm text-orange-700 space-y-1">
              <p><strong>• Esta acción NO se puede deshacer</strong></p>
              <p>• La reserva será ELIMINADA completamente de la base de datos</p>
              <p>• Se liberarán automáticamente los cupos del tour</p>
              <p>• Se enviará una notificación por email al cliente</p>
              <p>• No será posible recuperar esta información posteriormente</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="reserva" class="bg-red-50 border border-red-200 rounded-lg p-3 sm:p-4">
        <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
          <FontAwesomeIcon :icon="faExclamationTriangle" class="text-red-600 text-base sm:text-lg" />
          <h4 class="font-medium text-red-800 text-sm sm:text-base">Confirmar Rechazo</h4>
        </div>
        <div class="text-xs sm:text-sm space-y-1">
          <p class="break-words"><strong>Cliente:</strong> {{ (reserva.cliente?.user?.name) || (reserva.cliente?.nombres) || 'N/A' }}</p>
          <p class="break-words"><strong>Servicio:</strong> {{ reserva.entidad_nombre }}</p>
          <p><strong>Fecha:</strong> {{ formatearFecha(reserva.fecha_reserva) }}</p>
          <p><strong>Total:</strong> ${{ Number(reserva.total || 0).toFixed(2) }}</p>
        </div>
      </div>

      <div>
        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
          Motivo del rechazo <span class="text-red-500">*</span>
        </label>
        <Textarea
          v-model="motivoRechazo"
          placeholder="Especifica el motivo por el cual se rechaza esta reserva..."
          rows="4"
          class="w-full text-xs sm:text-sm"
          maxlength="500"
          :pt="{
            root: 'w-full',
            textarea: 'text-xs sm:text-sm p-2 sm:p-3'
          }"
        />
        <small class="text-gray-500 text-xs mt-1">
          {{ motivoRechazo.length }}/500 caracteres
        </small>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-center gap-4 w-full mt-6">
        <button
          @click="rechazarReserva"
          :disabled="!motivoRechazo.trim() || procesando"
          class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <FontAwesomeIcon
            v-if="procesando"
            :icon="faSpinner"
            class="animate-spin h-5 text-white"
          />
          <FontAwesomeIcon v-else :icon="faCheck" class="h-5" />
          <span v-if="!procesando">Eliminar Reserva Definitivamente</span>
          <span v-else>Eliminando...</span>
        </button>
        <button
          type="button"
          @click="cerrarModalRechazar"
          :disabled="procesando"
          class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
        >
          <FontAwesomeIcon :icon="faTimes" class="h-5" />Cancelar
        </button>
      </div>
    </template>
  </Dialog>

  <!-- ELIMINADO: Modal para reprogramar reserva individual -->
  <!-- Las reservas ahora se reprograman automáticamente desde el TOUR -->
</template>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
