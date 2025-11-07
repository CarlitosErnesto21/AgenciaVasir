<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import Dialog from 'primevue/dialog';
import Calendar from 'primevue/calendar';
import Select from 'primevue/select';
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
    { label: 'Agotado', value: 'AGOTADO', descripcion: 'No hay cupos disponibles', color: 'text-red-600', disponible: true },
    { label: 'En Curso', value: 'EN_CURSO', descripcion: 'El tour está en desarrollo', color: 'text-blue-600', disponible: true },
    { label: 'Completado', value: 'COMPLETADO', descripcion: 'El tour ha finalizado exitosamente', color: 'text-gray-600', disponible: true },
    { label: 'Cancelado', value: 'CANCELADO', descripcion: 'El tour ha sido cancelado', color: 'text-red-600', disponible: true },
    { label: 'Suspendido', value: 'SUSPENDIDO', descripcion: 'El tour está temporalmente suspendido', color: 'text-orange-600', disponible: true },
    { label: 'Reprogramado', value: 'REPROGRAMADO', descripcion: 'El tour ha sido reprogramado a una nueva fecha', color: 'text-purple-600', disponible: true }
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
    }
});

// Watch para mostrar/ocultar formulario de reprogramación
watch(selectedEstado, (nuevoEstado) => {
    mostrarFormularioReprogramacion.value = nuevoEstado === 'REPROGRAMADO';
    if (nuevoEstado !== 'REPROGRAMADO') {
        // Limpiar campos de reprogramación
        nuevaFechaSalida.value = null;
        nuevaFechaRegreso.value = null;
        motivoReprogramacion.value = '';
        observaciones.value = '';
    }
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

    if (selectedEstado.value === 'REPROGRAMADO') {
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

const cambiarEstado = async () => {
    submitted.value = true;

    if (!validarFormulario()) {
        return;
    }

    isLoading.value = true;

    try {
        const payload = {
            estado: selectedEstado.value
        };

        // Si es reprogramación, agregar datos adicionales
        if (selectedEstado.value === 'REPROGRAMADO') {
            payload.fecha_salida = nuevaFechaSalida.value;
            payload.fecha_regreso = nuevaFechaRegreso.value;
            payload.motivo_reprogramacion = motivoReprogramacion.value;
            payload.observaciones = observaciones.value;
        }

        const response = await axios.put(`/api/tours/${props.tour.id}/cambiar-estado`, payload);

        toast.add({
            severity: "success",
            summary: "¡Estado actualizado!",
            detail: selectedEstado.value === 'REPROGRAMADO'
                ? "El tour ha sido reprogramado correctamente."
                : `El estado del tour ha sido cambiado a ${estadosOptions.value.find(e => e.value === selectedEstado.value)?.label}.`,
            life: 4000
        });

        // Emitir evento para actualizar la lista
        emit('estadoActualizado', response.data.tour);
        closeModal();

    } catch (error) {
        console.error('Error al cambiar estado:', error);

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
                        <span class="font-medium text-gray-700">Fecha de salida:</span>
                        <span class="ml-2">{{ new Date(tour.fecha_salida).toLocaleDateString('es-ES') }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Fecha de regreso:</span>
                        <span class="ml-2">{{ new Date(tour.fecha_regreso).toLocaleDateString('es-ES') }}</span>
                    </div>
                </div>
            </div>

            <!-- Selector de estado -->
            <div class="space-y-3">
                <label class="block text-sm font-semibold text-gray-700">
                    Nuevo Estado <span class="text-red-500">*</span>
                </label>
                <Select
                    v-model="selectedEstado"
                    :options="estadosOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Selecciona un estado"
                    class="w-full"
                    :class="{ 'p-invalid': submitted && !selectedEstado }"
                >
                    <template #option="slotProps">
                        <div class="flex flex-col py-2">
                            <span :class="slotProps.option.color" class="font-medium">
                                {{ slotProps.option.label }}
                            </span>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ slotProps.option.descripcion }}
                            </span>
                        </div>
                    </template>
                </Select>
                <small v-if="submitted && !selectedEstado" class="text-red-500">
                    El estado es obligatorio.
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
                        <Calendar
                            v-model="nuevaFechaSalida"
                            showTime
                            showIcon
                            dateFormat="dd/mm/yy"
                            :minDate="getMinDate()"
                            placeholder="Selecciona fecha y hora"
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
                        <Calendar
                            v-model="nuevaFechaRegreso"
                            showTime
                            showIcon
                            dateFormat="dd/mm/yy"
                            :minDate="getMinDateRegreso()"
                            placeholder="Selecciona fecha y hora"
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
                    <Select
                        v-model="motivoReprogramacion"
                        :options="motivosComunes"
                        placeholder="Selecciona o escribe un motivo"
                        editable
                        class="w-full"
                        :class="{ 'p-invalid': submitted && !motivoReprogramacion.trim() }"
                    />
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
                            <span v-if="selectedEstado === 'REPROGRAMADO'">
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
                    class="bg-blue-500 hover:bg-blue-700 text-white px-9 sm:px-12 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="closeModal"
                    :disabled="isLoading"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />
                    Cancelar
                </button>
                <button
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="cambiarEstado"
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
                    <span v-else>{{ selectedEstado === 'REPROGRAMADO' ? 'Reprogramando...' : 'Actualizando...' }}</span>
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

/* Estilos para el dropdown del Select de PrimeVue */
:deep(.p-select-overlay) {
    border: 2px solid #9ca3af !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

:deep(.p-select-option) {
    border-bottom: 1px solid #e5e7eb !important;
    padding: 8px 12px !important;
    transition: background-color 0.2s ease !important;
}

:deep(.p-select-option:last-child) {
    border-bottom: none !important;
}

:deep(.p-select-option:hover) {
    background-color: #f3f4f6 !important;
}

:deep(.p-select-option[aria-selected="true"]) {
    background-color: #dbeafe !important;
    color: #1e40af !important;
}
</style>
