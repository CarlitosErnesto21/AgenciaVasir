<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import {
  faCheck, faXmark, faCalendarDays, faEye,
  faExclamationTriangle, faInfoCircle, faSpinner, faTimes, faPhone,
  faCopy, faExternalLinkAlt, faLink, faCreditCard, faTrash
} from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';
import { faEnvelope } from '@fortawesome/free-solid-svg-icons';

// Inicializar toast
const toast = useToast();

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

// 🔗 Estados para el generador de enlaces de pago Wompi
const generandoEnlace = ref(false);
const enlaceWompiGenerado = ref(null);
const showEnlaceWompi = ref(false);

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

  // Limpiar estados de Wompi
  enlaceWompiGenerado.value = null;
  showEnlaceWompi.value = false;
  generandoEnlace.value = false;
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

// 🔗 Funciones para generar enlaces de pago Wompi
const generarEnlaceWompi = async () => {
  if (!props.reserva) return;

  generandoEnlace.value = true;
  enlaceWompiGenerado.value = null;
  showEnlaceWompi.value = false;

  try {
    const reserva = props.reserva;

    // Preparar datos para Wompi según la validación del backend
    const tourData = {
      id: props.tour?.id || reserva.tour_id || 1, // Requerido por validación
      nombre: props.tour?.nombre || reserva.entidad_nombre || 'Tour reservado',
      cupos_adultos: reserva.mayores_edad || 0, // Nombre correcto según validación
      cupos_menores: reserva.menores_edad || 0, // Nombre correcto según validación
      total: parseFloat(reserva.total)
    };

    const customerEmail = reserva.cliente?.user?.email || reserva.cliente?.correo;

    // Validar que tengamos un email
    if (!customerEmail) {
      throw new Error('No se encontró email del cliente para generar el enlace de pago');
    }

    const requestData = {
      reserva_id: reserva.id,
      amount: parseFloat(reserva.total),
      customer_email: customerEmail,
      tour_data: tourData
    };

    console.log('🔗 Enviando datos a Wompi:', requestData);

    const response = await axios.post('/api/wompi/payment-link-tour', requestData);

    if (response.data.success) {
      enlaceWompiGenerado.value = {
        url: response.data.payment_link,
        reference: response.data.reference,
        amount: requestData.amount
      };
      showEnlaceWompi.value = true;
      console.log('✅ Enlace generado exitosamente:', enlaceWompiGenerado.value);
    } else {
      throw new Error(response.data.message || 'Error al generar enlace');
    }
  } catch (error) {
    console.error('❌ Error generando enlace Wompi:', error);

    let errorMessage = 'Error al generar enlace de pago';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    // Mostrar error con toast
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 5000
    });
  } finally {
    generandoEnlace.value = false;
  }
};

const copiarEnlace = async () => {
  if (!enlaceWompiGenerado.value?.url) return;

  try {
    await navigator.clipboard.writeText(enlaceWompiGenerado.value.url);
    // Mostrar éxito con toast
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Enlace copiado al portapapeles',
      life: 3000
    });
  } catch (error) {
    console.error('Error copiando enlace:', error);
    // Fallback para navegadores que no soportan clipboard
    const textArea = document.createElement('textarea');
    textArea.value = enlaceWompiGenerado.value.url;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Enlace copiado al portapapeles',
      life: 3000
    });
  }
};

const abrirEnlace = () => {
  if (!enlaceWompiGenerado.value?.url) return;
  window.open(enlaceWompiGenerado.value.url, '_blank');
};

const limpiarEnlace = () => {
  enlaceWompiGenerado.value = null;
  showEnlaceWompi.value = false;
};

// Función auxiliar para verificar si la reserva puede generar enlace
const puedeGenerarEnlace = (reserva) => {
  if (!reserva) return false;

  // Solo reservas pendientes pueden generar enlaces
  const estadosPermitidos = ['PENDIENTE', 'pendiente'];

  // Verificar si ya tiene un pago aprobado usando el pago activo
  const tienePagoAprobado = reserva.pago_activo && reserva.pago_activo.estado === 'approved';

  return estadosPermitidos.includes(reserva.estado) && !tienePagoAprobado;
};

// Computed para verificar si el pago está pendiente
const tienePagoPendiente = computed(() => {
  if (!props.reserva) return true;

  // DEBUG: Log súper detallado
  console.log('==================================================');
  console.log('🔍 DEBUG SÚPER DETALLADO - PAGO ACTIVO');
  console.log('==================================================');
  console.log('1. DATOS GENERALES DE RESERVA:');
  console.log('   - ID de reserva:', props.reserva.id);
  console.log('   - Estado de reserva:', props.reserva.estado);
  console.log('   - Objeto reserva completo:', JSON.stringify(props.reserva, null, 2));

  console.log('2. VERIFICACIÓN PAGO_ACTIVO:');
  console.log('   - pago_activo existe (boolean):', !!props.reserva.pago_activo);
  console.log('   - pago_activo es null:', props.reserva.pago_activo === null);
  console.log('   - pago_activo es undefined:', props.reserva.pago_activo === undefined);
  console.log('   - typeof pago_activo:', typeof props.reserva.pago_activo);

  if (props.reserva.pago_activo) {
    console.log('3. DATOS DE PAGO_ACTIVO:');
    console.log('   - ID del pago:', props.reserva.pago_activo.id);
    console.log('   - Estado del pago:', props.reserva.pago_activo.estado);
    console.log('   - Tipo de estado (typeof):', typeof props.reserva.pago_activo.estado);
    console.log('   - Estado === "approved":', props.reserva.pago_activo.estado === 'approved');
    console.log('   - Estado == "approved":', props.reserva.pago_activo.estado == 'approved');
    console.log('   - Referencia Wompi:', props.reserva.pago_activo.referencia_wompi);
    console.log('   - Monto:', props.reserva.pago_activo.monto);
    console.log('   - Moneda:', props.reserva.pago_activo.moneda);
    console.log('   - Created at:', props.reserva.pago_activo.created_at);
    console.log('   - Updated at:', props.reserva.pago_activo.updated_at);
    console.log('   - Objeto pago_activo completo:', JSON.stringify(props.reserva.pago_activo, null, 2));
  } else {
    console.log('3. PAGO_ACTIVO ES NULL/UNDEFINED - VERIFICANDO PAGOS ARRAY:');
    console.log('   - props.reserva.pagos existe:', !!props.reserva.pagos);
    console.log('   - props.reserva.pagos length:', props.reserva.pagos?.length);
    if (props.reserva.pagos && props.reserva.pagos.length > 0) {
      console.log('   - Todos los pagos:', JSON.stringify(props.reserva.pagos, null, 2));
      props.reserva.pagos.forEach((pago, index) => {
        console.log(`   - Pago ${index}: ID=${pago.id}, Estado=${pago.estado}, Ref=${pago.referencia_wompi}`);
      });
    }
  }

  console.log('4. LÓGICA DE DECISIÓN:');
  const resultado = !props.reserva.pago_activo || props.reserva.pago_activo.estado !== 'approved';
  console.log('   - Resultado tienePagoPendiente:', resultado);
  console.log('   - Razón:',
    !props.reserva.pago_activo ? 'No hay pago_activo' :
    props.reserva.pago_activo.estado !== 'approved' ? `Estado "${props.reserva.pago_activo.estado}" no es "approved"` :
    'Pago está aprobado'
  );
  console.log('==================================================');

  // Si no hay pago activo, consideramos pendiente
  if (!props.reserva.pago_activo) return true;

  // Verificar si el pago activo no está aprobado
  return props.reserva.pago_activo.estado !== 'approved';
});

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
    // Limpiar estados de Wompi al cerrar
    limpiarEnlace();
    // Solo restaurar scroll si ningún otro modal está abierto
    if (!isVisible.value && !isRechazarVisible.value) {
      restaurarScroll();
    }
  }
});

// Watcher para detectar cambios en la reserva
watch(() => props.reserva, (newReserva, oldReserva) => {
  console.log('📊 WATCHER - CAMBIO EN PROPS.RESERVA:');
  console.log('   - Reserva anterior:', oldReserva);
  console.log('   - Reserva nueva:', newReserva);
  console.log('   - ID anterior:', oldReserva?.id);
  console.log('   - ID nuevo:', newReserva?.id);
  console.log('   - pago_activo anterior:', oldReserva?.pago_activo);
  console.log('   - pago_activo nuevo:', newReserva?.pago_activo);
  if (newReserva?.pago_activo) {
    console.log('   - Estado nuevo del pago:', newReserva.pago_activo.estado);
    console.log('   - Referencia nueva del pago:', newReserva.pago_activo.referencia_wompi);
  }
}, { deep: true });

watch(isRechazarVisible, (newValue) => {
  if (newValue) {
    bloquearScroll();
  } else {
    // Solo restaurar scroll si ningún otro modal está abierto
    if (!isVisible.value && !isDetallesVisible.value) {
      restaurarScroll();
    }
  }
});

// Log inicial al montar el componente
onMounted(() => {
  console.log('🚀 COMPONENTE MONTADO - DATOS INICIALES:');
  console.log('   - Props reserva completa:', JSON.stringify(props.reserva, null, 2));
  console.log('   - pago_activo inicial:', props.reserva?.pago_activo);
  console.log('   - Estado inicial del pago:', props.reserva?.pago_activo?.estado);
});

// Limpiar scroll al desmontar el componente
onUnmounted(() => {
  restaurarScroll();
});

// ELIMINADO: cerrarModalReprogramar - ya no se usa
</script>

<template>
  <!-- Toast para notificaciones -->
  <Toast />

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

        <!-- Mensaje de advertencia para pago pendiente -->
        <div
          v-if="getAccionesDisponibles(reserva).includes('confirmar') && tienePagoPendiente"
          class="bg-orange-50 border border-orange-200 rounded-lg p-3"
        >
          <div class="flex items-center gap-2 mb-2">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 w-4 text-orange-600" />
            <span class="font-semibold text-orange-800 text-sm">Pago Pendiente</span>
            <span class="px-2 py-0.5 bg-orange-200 text-orange-800 text-xs font-medium rounded-full ml-auto">
              No Confirmar
            </span>
          </div>
          <p class="text-orange-700 text-xs leading-relaxed">
            El cliente no ha pagado. Revisa el estado en <span class="font-semibold">"Ver Detalles"</span> antes de confirmar.
          </p>
        </div>

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
    :style="{ width: '95vw', maxWidth: '800px' }"
    :closable="false"
    :draggable="false"
  >
    <!-- Header personalizado con estado y fecha prominentes -->
    <template #header>
      <div class="w-full">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-1">
              Detalles de la Reserva
            </h3>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <!-- Estado de la reserva -->
            <div class="flex items-center gap-2">
              <span
                :class="getColorEstadoReserva(reserva.estado)"
                class="px-3 py-1.5 rounded-full text-sm font-bold shadow-md border-2 border-white"
              >
                {{ estadosReservas.find(e => e.value === reserva.estado)?.label || reserva.estado }}
              </span>
            </div>

            <!-- Fecha de la reserva -->
            <div class="flex items-center gap-2">
              <FontAwesomeIcon :icon="faCalendarDays" class="text-blue-600 text-lg" />
              <div class="text-right">
                <p class="text-sm font-bold text-blue-700 leading-tight">
                  {{ formatearFecha(reserva.fecha_reserva) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
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
        </div>
      </div>

      <!-- Información de Pagos -->
      <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 sm:p-4">
        <h4 class="font-semibold text-slate-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
          <FontAwesomeIcon :icon="faCreditCard" class="text-slate-600 text-sm sm:text-base" />
          Información de Pagos
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex items-center gap-2">
            <span class="font-medium text-gray-700">Estado de Pago:</span>
            <span
              class="px-2 py-1 rounded-full text-xs font-medium"
              :class="{
                'bg-green-100 text-green-800': reserva.pago_activo && reserva.pago_activo.estado === 'approved',
                'bg-yellow-100 text-yellow-800': !reserva.pago_activo || reserva.pago_activo.estado !== 'approved'
              }"
              @click="console.log('🎯 CLICK EN ESTADO - pago_activo:', reserva.pago_activo, 'estado:', reserva.pago_activo?.estado)"
            >
              {{
                (() => {
                  console.log('🎨 RENDERIZANDO ESTADO DE PAGO:');
                  console.log('   - reserva.pago_activo:', reserva.pago_activo);
                  console.log('   - reserva.pago_activo?.estado:', reserva.pago_activo?.estado);
                  console.log('   - Condición completa:', reserva.pago_activo && reserva.pago_activo.estado === 'approved');
                  const resultado = (reserva.pago_activo && reserva.pago_activo.estado === 'approved') ? 'Pagado' : 'Pendiente de Pago';
                  console.log('   - Texto que se mostrará:', resultado);
                  return resultado;
                })()
              }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <span class="font-medium text-gray-700">Ref. Wompi:</span>
            <span
              v-if="reserva.pago_activo && reserva.pago_activo.referencia_wompi"
              class="text-xs bg-gray-100 px-2 py-1 rounded font-mono"
              @click="console.log('🎯 CLICK EN REFERENCIA - pago_activo:', reserva.pago_activo, 'referencia:', reserva.pago_activo?.referencia_wompi)"
            >
              {{
                (() => {
                  console.log('📋 RENDERIZANDO REFERENCIA WOMPI:');
                  console.log('   - reserva.pago_activo:', reserva.pago_activo);
                  console.log('   - reserva.pago_activo?.referencia_wompi:', reserva.pago_activo?.referencia_wompi);
                  console.log('   - Condición v-if:', reserva.pago_activo && reserva.pago_activo.referencia_wompi);
                  console.log('   - Referencia que se mostrará:', reserva.pago_activo.referencia_wompi);
                  return reserva.pago_activo.referencia_wompi;
                })()
              }}
            </span>
            <span
              v-else
              class="text-gray-400 italic text-sm"
              @click="console.log('🚫 CLICK EN SIN REFERENCIA - pago_activo:', reserva.pago_activo, 'condición v-else ejecutada')"
            >
              {{
                (() => {
                  console.log('❌ RENDERIZANDO "SIN REFERENCIA":');
                  console.log('   - reserva.pago_activo:', reserva.pago_activo);
                  console.log('   - reserva.pago_activo?.referencia_wompi:', reserva.pago_activo?.referencia_wompi);
                  console.log('   - ¿Por qué v-else? No cumple:', reserva.pago_activo && reserva.pago_activo.referencia_wompi);
                  console.log('   - Razones posibles:');
                  console.log('     - No hay pago_activo:', !reserva.pago_activo);
                  console.log('     - No hay referencia_wompi:', !reserva.pago_activo?.referencia_wompi);
                  return 'Sin referencia';
                })()
              }}
            </span>
          </div>
        </div>
      </div>

      <!-- 🔗 Generador de Enlaces de Pago Wompi -->
      <div v-if="puedeGenerarEnlace(reserva)" class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border-2 border-yellow-300 p-4">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <FontAwesomeIcon :icon="faLink" class="text-orange-600 text-lg" />
            <h4 class="text-lg font-semibold text-gray-800">Generar Enlace de Pago Wompi</h4>
          </div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
              <FontAwesomeIcon :icon="faCreditCard" class="mr-1" />
              Total: ${{ Number(reserva.total || 0).toFixed(2) }}
            </span>
          </div>
        </div>

        <div class="space-y-3">
          <p class="text-sm text-gray-600">
            <FontAwesomeIcon :icon="faInfoCircle" class="text-blue-500 mr-1" />
            <strong>¿El cliente aún no ha pagado?</strong><br>
            Genera un enlace de pago personalizado para que el cliente pueda pagar esta reserva directamente.
          </p>

          <!-- Botón para generar enlace -->
          <div v-if="!showEnlaceWompi" class="flex justify-center">
            <button
              @click="generarEnlaceWompi"
              :disabled="generandoEnlace"
              class="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl"
            >
              <FontAwesomeIcon
                :icon="generandoEnlace ? faSpinner : faLink"
                :class="{ 'animate-spin': generandoEnlace }"
              />
              {{ generandoEnlace ? 'Generando enlace...' : 'Generar Enlace de Pago' }}
            </button>
          </div>

          <!-- Enlace generado -->
          <div v-if="showEnlaceWompi && enlaceWompiGenerado" class="space-y-3">
            <div class="bg-white rounded-lg border border-yellow-300 p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Enlace de Pago Generado:</span>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <FontAwesomeIcon :icon="faCheck" class="mr-1" />
                  ¡Listo para usar!
                </span>
              </div>

              <div class="bg-gray-50 rounded-md p-3 mb-3">
                <p class="text-sm text-gray-800 break-all font-mono">
                  {{ enlaceWompiGenerado.url }}
                </p>
              </div>

              <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                <span>Referencia: {{ enlaceWompiGenerado.reference }}</span>
                <span>Monto: ${{ enlaceWompiGenerado.amount.toFixed(2) }}</span>
              </div>
            </div>

            <!-- Botones de acción para el enlace -->
            <div class="flex flex-wrap gap-2 justify-center">
              <button
                @click="copiarEnlace"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium"
              >
                <FontAwesomeIcon :icon="faCopy" />
                Copiar Enlace
              </button>

              <button
                @click="abrirEnlace"
                class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium"
              >
                <FontAwesomeIcon :icon="faExternalLinkAlt" />
                Ver Enlace
              </button>

              <button
                @click="limpiarEnlace"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium"
              >
                <FontAwesomeIcon :icon="faTrash" />
                Limpiar
              </button>
            </div>

            <!-- Instrucciones de uso -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <h5 class="text-sm font-medium text-blue-800 mb-2">
                <FontAwesomeIcon :icon="faInfoCircle" class="mr-1" />
                Instrucciones de uso:
              </h5>
              <ul class="text-xs text-blue-700 space-y-1">
                <li>• Copia el enlace y envíalo al cliente por WhatsApp, email o SMS</li>
                <li>• El cliente podrá pagar su reserva de manera segura</li>
                <li>• Recibirás notificación automática cuando se complete el pago</li>
                <li>• El enlace es seguro y está protegido por Wompi</li>
              </ul>
            </div>
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
      <!-- Información de la reserva a rechazar -->
      <div v-if="reserva" class="bg-red-50 border border-red-200 rounded-lg p-3 sm:p-4 mb-4">
        <div class="flex items-start gap-2 sm:gap-3">
          <FontAwesomeIcon :icon="faExclamationTriangle" class="text-red-600 text-base sm:text-lg mt-1 flex-shrink-0" />
          <div class="flex-1">
            <h4 class="font-semibold text-red-800 text-sm sm:text-base mb-2">Rechazar Reserva</h4>
            <div class="text-xs sm:text-sm text-red-700 mb-3 space-y-1">
              <p><strong>• Acción permanente:</strong> elimina la reserva y notifica al cliente</p>
              <p>• Los cupos se liberan automáticamente</p>
            </div>
            <div class="bg-white bg-opacity-60 rounded p-2 text-xs sm:text-sm space-y-1">
              <p class="break-words"><strong>Cliente:</strong> {{ (reserva.cliente?.user?.name) || (reserva.cliente?.nombres) || 'N/A' }}</p>
              <p class="break-words"><strong>Servicio:</strong> {{ reserva.entidad_nombre }}</p>
              <p><strong>Fecha:</strong> {{ formatearFecha(reserva.fecha_reserva) }}</p>
              <p><strong>Total:</strong> ${{ Number(reserva.total || 0).toFixed(2) }}</p>
            </div>
          </div>
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
