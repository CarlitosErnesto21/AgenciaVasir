<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faSpinner, faXmark, faExclamationTriangle, faCalendarDays, faClockRotateLeft } from '@fortawesome/free-solid-svg-icons';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// Props
const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    tour: {
        type: Object,
        default: () => ({})
    },
    dialogStyle: {
        type: Object,
        default: () => ({})
    }
});

// Emits
const emit = defineEmits([
    'update:visible',
    'estadoActualizado'
]);

const toast = useToast();

// Estados disponibles
const estadosOptions = ref([
    { label: 'Disponible', value: 'DISPONIBLE', descripcion: 'El tour está disponible para reservas', color: 'text-green-600', disponible: true },
    { label: 'Completo', value: 'COMPLETO', descripcion: 'No hay cupos disponibles', color: 'text-red-600', disponible: true },
    { label: 'En Curso', value: 'EN_CURSO', descripcion: 'El tour está en desarrollo', color: 'text-blue-600', disponible: true },
    { label: 'Finalizado', value: 'FINALIZADO', descripcion: 'El tour ha finalizado exitosamente y se notificará a los clientes', color: 'text-gray-600', disponible: true },
    { label: 'Cancelada', value: 'CANCELADA', descripcion: 'El tour será cancelado y se notificará a todos los clientes con reservas', color: 'text-red-600', disponible: true },
    { label: 'Reprogramada', value: 'REPROGRAMADA', descripcion: 'El tour ha sido reprogramado a una nueva fecha', color: 'text-purple-600', disponible: true }
]);

// Estados reactivos
const selectedEstado = ref(null);
const mostrarFormularioReprogramacion = ref(false);
const nuevaFechaSalida = ref(null);
const nuevaFechaRegreso = ref(null);
const motivoReprogramacion = ref('');
const observaciones = ref('');
const isLoading = ref(false);
const submitted = ref(false);

// Computed para el v-model del modal
const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

// Watch para resetear formulario cuando cambie el tour o se abra el modal
watch([() => props.visible, () => props.tour], () => {
    if (props.visible && props.tour) {
        resetForm();
        // Desactivar scroll del body cuando se abre el modal
        document.body.style.overflow = 'hidden';
    } else {
        // Reactivar scroll del body cuando se cierra el modal
        document.body.style.overflow = 'auto';
    }
});

// Watch para mostrar/ocultar formulario de reprogramación
watch(selectedEstado, (nuevoEstado) => {
    mostrarFormularioReprogramacion.value = nuevoEstado === 'REPROGRAMADA';

    if (nuevoEstado !== 'REPROGRAMADA') {
        // Limpiar campos de reprogramación
        nuevaFechaSalida.value = null;
        nuevaFechaRegreso.value = null;
        motivoReprogramacion.value = '';
        observaciones.value = '';
    }
});

// Limpiar scroll al desmontar el componente
onUnmounted(() => {
    document.body.style.overflow = 'auto';
});

// Motivos predefinidos para reprogramación
const motivosComunes = ref([
    'Condiciones climáticas adversas',
    'Problemas de transporte',
    'Falta de cupo mínimo',
    'Solicitud de clientes',
    'Mantenimiento de instalaciones',
    'Emergencia sanitaria',
    'Otro motivo'
]);

const resetForm = () => {
    selectedEstado.value = props.tour?.estado || null;
    mostrarFormularioReprogramacion.value = false;
    nuevaFechaSalida.value = null;
    nuevaFechaRegreso.value = null;
    motivoReprogramacion.value = '';
    observaciones.value = '';
    submitted.value = false;
};

const closeModal = () => {
    resetForm();
    // Reactivar scroll cuando se cierra el modal
    document.body.style.overflow = 'auto';
    emit('update:visible', false);
};

// Validaciones
const validarFormulario = () => {
    if (!selectedEstado.value) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Debes seleccionar un estado.",
            life: 4000
        });
        return false;
    }

    if (selectedEstado.value === 'REPROGRAMADA') {
        if (!nuevaFechaSalida.value) {
            toast.add({
                severity: "warn",
                summary: "Campos requeridos",
                detail: "Debes seleccionar una nueva fecha de salida.",
                life: 4000
            });
            return false;
        }

        if (!nuevaFechaRegreso.value) {
            toast.add({
                severity: "warn",
                summary: "Campos requeridos",
                detail: "Debes seleccionar una nueva fecha de regreso.",
                life: 4000
            });
            return false;
        }

        if (!motivoReprogramacion.value.trim()) {
            toast.add({
                severity: "warn",
                summary: "Campos requeridos",
                detail: "Debes especificar el motivo de la reprogramación.",
                life: 4000
            });
            return false;
        }

        // Validar que las fechas sean futuras
        const now = new Date();
        if (nuevaFechaSalida.value < now) {
            toast.add({
                severity: "warn",
                summary: "Fecha inválida",
                detail: "La nueva fecha de salida debe ser futura.",
                life: 4000
            });
            return false;
        }

        // Validar que fecha de regreso sea posterior a fecha de salida
        if (nuevaFechaRegreso.value <= nuevaFechaSalida.value) {
            toast.add({
                severity: "warn",
                summary: "Fecha inválida",
                detail: "La fecha de regreso debe ser posterior a la fecha de salida.",
                life: 4000
            });
            return false;
        }
    }

    return true;
};

const cambiarEstado = async (event) => {
    // Prevenir comportamiento por defecto
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    submitted.value = true;

    if (!validarFormulario()) {
        return;
    }

    isLoading.value = true;

    try {
        const payload = {
            estado: selectedEstado.value
        };

        let response;

        // Si es finalización, usar endpoint específico
        if (selectedEstado.value === 'FINALIZADO') {
            const finalizarPayload = {
                observaciones: observaciones.value || ''
            };
            response = await axios.put(`/api/tours/${props.tour.id}/finalizar`, finalizarPayload);
        }
        // Si es cancelación, agregar datos adicionales y cancelar reservas
        else if (selectedEstado.value === 'CANCELADA') {
            payload.motivo_cancelacion = observaciones.value || 'Tour cancelado por el administrador';
            payload.cancelar_reservas = true; // Cancelar todas las reservas asociadas

            response = await axios.put(`/api/tours/${props.tour.id}/cambiar-estado`, payload);
        }
        // Si es reprogramación, agregar datos adicionales
        else if (selectedEstado.value === 'REPROGRAMADA') {
            // Formatear las fechas en formato local de El Salvador (sin conversión UTC)
            // Usamos toLocaleString para mantener la hora local seleccionada
            const formatearFechaLocal = (fecha) => {
                const year = fecha.getFullYear();
                const month = String(fecha.getMonth() + 1).padStart(2, '0');
                const day = String(fecha.getDate()).padStart(2, '0');
                const hours = String(fecha.getHours()).padStart(2, '0');
                const minutes = String(fecha.getMinutes()).padStart(2, '0');
                const seconds = String(fecha.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            };

            payload.fecha_salida = formatearFechaLocal(nuevaFechaSalida.value);
            payload.fecha_regreso = formatearFechaLocal(nuevaFechaRegreso.value);
            payload.motivo_reprogramacion = motivoReprogramacion.value;
            payload.observaciones = observaciones.value;

            response = await axios.put(`/api/tours/${props.tour.id}/cambiar-estado`, payload);
        }
        // Para otros estados, usar el endpoint normal
        else {
            response = await axios.put(`/api/tours/${props.tour.id}/cambiar-estado`, payload);
        }

        // Emitir evento para actualizar la lista PRIMERO (sin esperar)
        const tourActualizado = response.data.tour || {
            ...props.tour,
            estado: selectedEstado.value,
            fecha_salida: selectedEstado.value === 'REPROGRAMADA' ? payload.fecha_salida : props.tour.fecha_salida,
            fecha_regreso: selectedEstado.value === 'REPROGRAMADA' ? payload.fecha_regreso : props.tour.fecha_regreso
        };

        emit('estadoActualizado', tourActualizado);

        // Mostrar toast después de emitir el evento
        toast.add({
            severity: "success",
            summary: "¡Estado actualizado!",
            detail: selectedEstado.value === 'REPROGRAMADA'
                ? "El tour ha sido reprogramado correctamente."
                : selectedEstado.value === 'FINALIZADO'
                    ? "El tour ha sido finalizado correctamente."
                    : selectedEstado.value === 'CANCELADA'
                        ? "El tour ha sido cancelado y se notificó a todos los clientes."
                        : `El estado del tour ha sido cambiado a ${estadosOptions.value.find(e => e.value === selectedEstado.value)?.label}.`,
            life: 4000
        });

        // Cerrar modal
        closeModal();

    } catch (error) {

        let mensaje = "No se pudo cambiar el estado del tour.";
        if (error.response?.data?.message) {
            mensaje = error.response.data.message;
        } else if (error.response?.data?.errors) {
            const errores = Object.values(error.response.data.errors).flat();
            mensaje = errores.join(', ');
        }

        toast.add({
            severity: "error",
            summary: "Error",
            detail: mensaje,
            life: 4000
        });
    } finally {
        isLoading.value = false;
    }
};

// Función para obtener fecha mínima (ahora + 1 hora)
const getMinDate = () => {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    return now;
};

// Función para obtener fecha mínima de regreso (fecha salida + 1 hora)
const getMinDateRegreso = () => {
    if (!nuevaFechaSalida.value) return getMinDate();
    const fechaSalida = new Date(nuevaFechaSalida.value);
    fechaSalida.setHours(fechaSalida.getHours() + 1);
    return fechaSalida;
};

// Función para formatear fecha y hora en formato amigable
const formatearFechaHora = (fecha) => {
    if (!fecha) return 'N/A';

    const fechaObj = new Date(fecha);

    // Verificar si es una fecha válida
    if (isNaN(fechaObj.getTime())) return 'Fecha inválida';

    // Si la hora es 00:00, mostrar solo la fecha
    if (fechaObj.getHours() === 0 && fechaObj.getMinutes() === 0) {
        return fechaObj.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }) + ' (Sin hora especificada)';
    }

    // Mostrar fecha y hora completa en formato AM/PM
    return fechaObj.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        header="Cambiar Estado del Tour"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div v-if="tour" class="space-y-6">
            <!-- Información del tour -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-blue-800 mb-2">{{ tour.nombre }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Estado actual:</span>
                        <span :class="estadosOptions.find(e => e.value === tour.estado)?.color || 'text-gray-600'" class="ml-2 font-semibold">
                            {{ estadosOptions.find(e => e.value === tour.estado)?.label || tour.estado }}
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Categoría:</span>
                        <span class="ml-2">{{ tour.categoria }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Salida:</span>
                        <span class="ml-2">{{ formatearFechaHora(tour.fecha_salida) }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Regreso:</span>
                        <span class="ml-2">{{ formatearFechaHora(tour.fecha_regreso) }}</span>
                    </div>
                </div>
            </div>

            <!-- Selector de estado -->
            <div class="space-y-3">
                <label class="block text-sm font-semibold text-gray-700">
                    Nuevo Estado <span class="text-red-500">*</span>
                </label>
                <div class="text-xs text-gray-500 mb-3">Selecciona el nuevo estado para el tour</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        v-for="estado in estadosOptions"
                        :key="estado.value"
                        type="button"
                        :class="[
                            'w-full p-3 rounded-lg border-2 transition-all duration-200 ease-in-out text-left',
                            selectedEstado === estado.value
                                ? 'border-blue-500 bg-blue-50 shadow-md'
                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                        ]"
                        @click="selectedEstado = estado.value"
                    >
                        <div class="flex items-start gap-3">
                            <div :class="[
                                'w-4 h-4 rounded-full border-2 flex-shrink-0 mt-0.5',
                                selectedEstado === estado.value
                                    ? 'border-blue-500 bg-blue-500'
                                    : 'border-gray-300'
                            ]">
                                <div v-if="selectedEstado === estado.value" class="w-full h-full rounded-full bg-white transform scale-50"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div :class="[estado.color, 'font-medium text-sm']">
                                    {{ estado.label }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1 leading-tight">
                                    {{ estado.descripcion }}
                                </div>
                            </div>
                        </div>
                    </button>
                </div>

                <small v-if="submitted && !selectedEstado" class="text-red-500">
                    Debes seleccionar un estado.
                </small>
            </div>

            <!-- Formulario de reprogramación -->
            <div v-if="mostrarFormularioReprogramacion" class="bg-purple-50 border border-purple-200 rounded-lg p-4 space-y-4">
                <div class="flex items-center gap-2 mb-4">
                    <FontAwesomeIcon :icon="faClockRotateLeft" class="text-purple-600" />
                    <h5 class="text-lg font-semibold text-purple-800">Datos de Reprogramación</h5>
                </div>

                <!-- Nuevas fechas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nueva Fecha de Salida <span class="text-red-500">*</span>
                        </label>
                        <DatePicker
                            v-model="nuevaFechaSalida"
                            showTime
                            showIcon
                            dateFormat="dd/mm/yy"
                            timeFormat="12"
                            hourFormat="12"
                            :minDate="getMinDate()"
                            placeholder="Selecciona fecha y hora (AM/PM)"
                            class="w-full"
                            :class="{ 'p-invalid': submitted && !nuevaFechaSalida }"
                        />
                        <small v-if="submitted && !nuevaFechaSalida" class="text-red-500">
                            La nueva fecha de salida es obligatoria.
                        </small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nueva Fecha de Regreso <span class="text-red-500">*</span>
                        </label>
                        <DatePicker
                            v-model="nuevaFechaRegreso"
                            showTime
                            showIcon
                            dateFormat="dd/mm/yy"
                            timeFormat="12"
                            hourFormat="12"
                            :minDate="getMinDateRegreso()"
                            placeholder="Selecciona fecha y hora (AM/PM)"
                            class="w-full"
                            :class="{ 'p-invalid': submitted && !nuevaFechaRegreso }"
                        />
                        <small v-if="submitted && !nuevaFechaRegreso" class="text-red-500">
                            La nueva fecha de regreso es obligatoria.
                        </small>
                    </div>
                </div>

                <!-- Motivo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo de la Reprogramación <span class="text-red-500">*</span>
                    </label>
                    <div class="text-xs text-gray-500 mb-3">Selecciona un motivo predefinido o escribe uno personalizado</div>

                    <!-- Motivos predefinidos -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                        <button
                            v-for="motivo in motivosComunes"
                            :key="motivo"
                            type="button"
                            :class="[
                                'text-left p-2 rounded-md border transition-all duration-200 ease-in-out text-sm',
                                motivoReprogramacion === motivo
                                    ? 'border-purple-500 bg-purple-50 text-purple-800'
                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700'
                            ]"
                            @click="motivoReprogramacion = motivo"
                        >
                            {{ motivo }}
                        </button>
                    </div>

                    <!-- Campo personalizado -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Motivo personalizado:
                        </label>
                        <textarea
                            v-model="motivoReprogramacion"
                            rows="2"
                            placeholder="Escribe un motivo personalizado..."
                            class="w-full p-2 border border-gray-300 rounded-md text-sm focus:ring-purple-500 focus:border-purple-500"
                            :class="{ 'border-red-500': submitted && !motivoReprogramacion.trim() }"
                            maxlength="255"
                        ></textarea>
                    </div>

                    <small v-if="submitted && !motivoReprogramacion.trim()" class="text-red-500">
                        El motivo es obligatorio.
                    </small>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Observaciones Adicionales
                    </label>
                    <textarea
                        v-model="observaciones"
                        rows="3"
                        placeholder="Agrega cualquier observación adicional..."
                        class="w-full p-2 border border-gray-300 rounded-md text-sm focus:ring-purple-500 focus:border-purple-500"
                        maxlength="500"
                    ></textarea>
                    <small class="text-gray-500">
                        {{ observaciones.length }}/500 caracteres
                    </small>
                </div>
            </div>

            <!-- Warning para cambios de estado -->
            <div v-if="selectedEstado && selectedEstado !== tour.estado" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="text-yellow-600 mt-1" />
                    <div>
                        <h5 class="font-medium text-yellow-800">Confirmación de cambio</h5>
                        <p class="text-sm text-yellow-700 mt-1">
                            Estás a punto de cambiar el estado del tour de
                            <strong>{{ estadosOptions.find(e => e.value === tour.estado)?.label }}</strong>
                            a
                            <strong>{{ estadosOptions.find(e => e.value === selectedEstado)?.label }}</strong>.
                            <span v-if="selectedEstado === 'REPROGRAMADA'">
                                Esto actualizará las fechas del tour y notificará a los clientes con reservas.
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-4 w-full mt-6">
                <button
                    type="button"
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click.prevent="cambiarEstado"
                    :disabled="isLoading || !selectedEstado"
                >
                    <FontAwesomeIcon
                        :icon="isLoading ? faSpinner : faCheck"
                        :class="[
                            'h-5 text-white',
                            { 'animate-spin': isLoading }
                        ]"
                    />
                    <span v-if="!isLoading">Cambiar Estado</span>
                    <span v-else>{{ selectedEstado === 'REPROGRAMADA' ? 'Reprogramando...' : 'Actualizando...' }}</span>
                </button>
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-9 sm:px-12 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeModal"
                    :disabled="isLoading"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />
                    Cancelar
                </button>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Animación para el spinner de loading */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
