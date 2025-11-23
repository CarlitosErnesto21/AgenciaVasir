<script setup>
import { computed, ref } from 'vue';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
  faCheck, faXmark, faCalendarDays, faEye,
  faExclamationTriangle, faInfoCircle, faSpinner, faTimes
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
  // Modal para reprogramar reserva
  reprogramarVisible: {
    type: Boolean,
    default: false
  },
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
  reprogramandoReserva: {
    type: Boolean,
    default: false
  },
  finalizandoReserva: {
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
  'update:reprogramarVisible',
  'confirmar',
  'rechazar',
  'reprogramar',
  'finalizar',
  'verDetalles'
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

// Computed para v-model del modal Reprogramar
const isReprogramarVisible = computed({
  get: () => props.reprogramarVisible,
  set: (value) => emit('update:reprogramarVisible', value)
});

// Estado local para los formularios
const motivoRechazo = ref('');
const fechaNuevaReprogramacion = ref(null);
const motivoReprogramacion = ref('');
const observacionesReprogramacion = ref('');

// Funciones para obtener acciones disponibles según el estado
const getAccionesDisponibles = (reserva) => {
  if (!reserva || !reserva.estado) return [];

  switch (reserva.estado) {
    case 'PENDIENTE':
      return ['confirmar', 'rechazar', 'reprogramar', 'detalles'];
    case 'CONFIRMADA':
      return ['rechazar', 'reprogramar', 'finalizar', 'detalles'];
    case 'REPROGRAMADA':
      return ['rechazar', 'finalizar', 'detalles'];
    case 'RECHAZADA':
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

// Función para formatear fecha
const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A';
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

// Función para obtener fecha mínima (hoy)
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

const abrirModalReprogramar = () => {
  fechaNuevaReprogramacion.value = null;
  motivoReprogramacion.value = '';
  observacionesReprogramacion.value = '';
  isVisible.value = false;
  isReprogramarVisible.value = true;
};

const reprogramarReserva = () => {
  emit('reprogramar', {
    reserva: props.reserva,
    fechaNueva: fechaNuevaReprogramacion.value,
    motivo: motivoReprogramacion.value,
    observaciones: observacionesReprogramacion.value
  });
};

const finalizarReserva = () => {
  emit('finalizar', props.reserva);
  // No cerrar el modal aquí, se cerrará cuando termine el proceso
};

const verDetalles = () => {
  isVisible.value = false;
  isDetallesVisible.value = true;
};

// Limpiar formularios al cerrar modales
const limpiarFormularios = () => {
  motivoRechazo.value = '';
  fechaNuevaReprogramacion.value = null;
  motivoReprogramacion.value = '';
  observacionesReprogramacion.value = '';
};

// Watch para limpiar formularios cuando se cierran los modales
const cerrarModalRechazar = () => {
  isRechazarVisible.value = false;
  limpiarFormularios();
};

const cerrarModalReprogramar = () => {
  isReprogramarVisible.value = false;
  limpiarFormularios();
};
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

        <!-- Botón para reprogramar -->
        <button
          v-if="getAccionesDisponibles(reserva).includes('reprogramar')"
          class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start disabled:opacity-50 disabled:cursor-not-allowed"
          @click="abrirModalReprogramar"
          :disabled="reprogramandoReserva"
        >
          <FontAwesomeIcon
            v-if="reprogramandoReserva"
            :icon="faSpinner"
            class="h-5 w-5 animate-spin"
          />
          <FontAwesomeIcon
            v-else
            :icon="faCalendarDays"
            class="h-5 w-5"
          />
          <div class="text-left flex-1">
            <div class="font-medium">
              {{ reprogramandoReserva ? 'Reprogramando...' : 'Reprogramar Reserva' }}
            </div>
            <div class="text-xs opacity-90">
              {{ reprogramandoReserva ? 'Procesando la reprogramación' : 'Cambiar fecha de la reserva' }}
            </div>
          </div>
        </button>

        <!-- Botón para finalizar -->
        <button
          v-if="getAccionesDisponibles(reserva).includes('finalizar')"
          class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-md transition-all duration-200 ease-in-out flex items-center gap-3 justify-start disabled:opacity-50 disabled:cursor-not-allowed"
          @click="finalizarReserva"
          :disabled="finalizandoReserva"
        >
          <FontAwesomeIcon
            v-if="finalizandoReserva"
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
              {{ finalizandoReserva ? 'Finalizando...' : 'Finalizar Reserva' }}
            </div>
            <div class="text-xs opacity-90">
              {{ finalizandoReserva ? 'Procesando finalización' : 'Marcar como completada' }}
            </div>
          </div>
        </button>
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
          <div class="break-words">
            <span class="font-medium text-gray-700">Email:</span>
            <span class="ml-2">
              <template v-if="(reserva.cliente?.user?.email) || (reserva.cliente?.correo)">
                <div class="flex flex-col">
                  <a
                    :href="`https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(reserva.cliente?.user?.email || reserva.cliente?.correo)}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-blue-600 hover:underline flex items-center gap-1"
                  >
                    <FontAwesomeIcon :icon="faEnvelope" class="h-4 w-4 mr-1" />
                    {{ reserva.cliente?.user?.email || reserva.cliente?.correo }}
                  </a>
                  <span class="text-xs text-blue-700 mt-1">Toca para escribir por Gmail</span>
                </div>
              </template>
              <template v-else>
                Problema al obtener email o no existe
              </template>
            </span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Teléfono:</span>
            <span class="ml-2">
              <template v-if="reserva.cliente?.telefono">
                <div class="flex flex-col">
                  <a
                    :href="`https://wa.me/${reserva.cliente.telefono.replace(/[^\d]/g, '')}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-green-600 hover:underline flex items-center gap-1"
                  >
                    <FontAwesomeIcon :icon="faWhatsapp" class="h-4 w-4 mr-1" />
                    {{ reserva.cliente.telefono }}
                  </a>
                  <span class="text-xs text-green-700 mt-1">Toca para contactar por WhatsApp</span>
                </div>
              </template>
              <template v-else>
                Problema al obtener teléfono o no existe
              </template>
            </span>
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
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3 text-xs sm:text-sm">
          <div>
            <span class="font-medium text-gray-700">Adultos:</span>
            <span class="ml-2">{{ reserva.mayores_edad || 0 }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Niños:</span>
            <span class="ml-2">{{ reserva.menores_edad || 0 }}</span>
          </div>
          <div class="col-span-2 sm:col-span-1">
            <span class="font-medium text-gray-700">Total:</span>
            <span class="ml-2 font-bold text-green-600">${{ Number(reserva.total || 0).toFixed(2) }}</span>
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
          <span v-if="!procesando">Rechazar Reserva</span>
          <span v-else>Rechazando...</span>
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

  <!-- Modal para reprogramar reserva -->
  <Dialog
    v-model:visible="isReprogramarVisible"
    modal
    header="Reprogramar Reserva"
    :style="{ width: '95vw', maxWidth: '800px' }"
    :closable="false"
    :draggable="false"
  >
    <div class="space-y-6">
      <div v-if="reserva" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center gap-3 mb-3">
          <FontAwesomeIcon :icon="faCalendarDays" class="text-blue-600 text-lg" />
          <h4 class="font-medium text-blue-800">Información de la Reserva</h4>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div>
            <span class="font-medium text-gray-700">Cliente:</span>
            <span class="ml-2">{{ (reserva.cliente?.user?.name) || (reserva.cliente?.nombres) || 'N/A' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Servicio:</span>
            <span class="ml-2">{{ reserva.entidad_nombre }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Fecha actual:</span>
            <span class="ml-2">{{ formatearFecha(reserva.fecha_reserva) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Total:</span>
            <span class="ml-2 font-bold text-green-600">${{ Number(reserva.total || 0).toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Tour asociado -->
      <div v-if="tour && tour.id" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
        <div class="flex items-center gap-3 mb-3">
          <FontAwesomeIcon :icon="faInfoCircle" class="text-purple-600 text-lg" />
          <h4 class="font-medium text-purple-800">Tour Asociado</h4>
        </div>
        <div class="text-sm space-y-1">
          <p><strong>Nombre:</strong> {{ tour.nombre }}</p>
          <p><strong>Estado actual:</strong>
            <span :class="getColorEstadoTour(tour.estado)" class="px-2 py-1 rounded-full text-xs font-medium ml-1">
              {{ estadosTours.find(e => e.value === tour.estado)?.label || tour.estado }}
            </span>
          </p>
          <p class="text-purple-600 text-xs mt-2">
            ℹ️ Al reprogramar esta reserva, el tour también será marcado como "REPROGRAMADO"
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Nueva fecha <span class="text-red-500">*</span>
          </label>
          <DatePicker
            v-model="fechaNuevaReprogramacion"
            showTime
            dateFormat="dd/mm/yy"
            class="w-full"
            showIcon
            placeholder="Seleccione la nueva fecha y hora"
            :minDate="getMinDate()"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Motivo de la reprogramación <span class="text-red-500">*</span>
        </label>
        <Textarea
          v-model="motivoReprogramacion"
          placeholder="Especifica el motivo de la reprogramación..."
          rows="3"
          class="w-full"
          maxlength="255"
        />
        <small class="text-gray-500 text-xs mt-1">
          {{ motivoReprogramacion.length }}/255 caracteres
        </small>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Observaciones adicionales (opcional)
        </label>
        <Textarea
          v-model="observacionesReprogramacion"
          placeholder="Información adicional sobre la reprogramación..."
          rows="2"
          class="w-full"
          maxlength="500"
        />
        <small class="text-gray-500 text-xs mt-1">
          {{ observacionesReprogramacion.length }}/500 caracteres
        </small>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-center gap-4 w-full mt-6">
        <button
          @click="reprogramarReserva"
          :disabled="!fechaNuevaReprogramacion || !motivoReprogramacion.trim() || procesando"
          class="bg-yellow-500 hover:bg-yellow-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <FontAwesomeIcon
            v-if="procesando"
            :icon="faSpinner"
            class="animate-spin h-5 text-white"
          />
          <FontAwesomeIcon v-else :icon="faCalendarDays" class="h-5" />
          <span v-if="!procesando">Reprogramar</span>
          <span v-else>Reprogramando...</span>
        </button>
        <button
          type="button"
          @click="cerrarModalReprogramar"
          :disabled="procesando"
          class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
        >
          <FontAwesomeIcon :icon="faTimes" class="h-5" />Cancelar
        </button>
      </div>
    </template>
  </Dialog>
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
