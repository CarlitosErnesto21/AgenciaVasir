<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import Dialog from 'primevue/dialog';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faSpinner, faXmark, faPlus, faMinus, faWarning, faInfoCircle } from '@fortawesome/free-solid-svg-icons';
import { useToast } from "primevue/usetoast";
import axios from "axios";

const toast = useToast();

// Props
const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    producto: {
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
    'stockUpdated'
]);

// Estados reactivos
const isLoading = ref(false);
const tipoMovimiento = ref('entrada'); // 'entrada', 'salida', 'ajuste'
const cantidad = ref(null);
const motivo = ref('');
const submitted = ref(false);
const nuevoStockActual = ref(null);
const nuevoStockMinimo = ref(null);

// Computed para el v-model del modal
const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

// Computed para calcular el nuevo stock según el tipo de movimiento
const stockResultante = computed(() => {
    if (!props.producto || cantidad.value === null || cantidad.value === '') {
        return parseInt(props.producto?.stock_actual || 0);
    }

    // Asegurar que tanto el stock actual como la cantidad sean números enteros
    const stockActual = parseInt(props.producto.stock_actual || 0);
    const cantidadNum = parseInt(cantidad.value || 0);

    switch (tipoMovimiento.value) {
        case 'entrada':
            return stockActual + cantidadNum;
        case 'salida':
            return Math.max(0, stockActual - cantidadNum);
        case 'ajuste':
            return cantidadNum; // Para ajuste, la cantidad ES el nuevo stock
        default:
            return stockActual;
    }
});

// Computed para determinar si el stock resultante está en alerta
const stockEnAlerta = computed(() => {
    const stockMinimo = props.producto?.stock_minimo || 1;
    return stockResultante.value <= stockMinimo;
});

// Opciones para el tipo de movimiento
const tiposMovimiento = ref([
    { label: 'Entrada de stock', value: 'entrada', descripcion: 'Aumentar el inventario' },
    { label: 'Salida de stock', value: 'salida', descripcion: 'Reducir el inventario' },
    { label: 'Ajuste de inventario', value: 'ajuste', descripcion: 'Establecer cantidad exacta' }
]);

// Watcher para resetear el formulario cuando se abre el modal
watch(() => props.visible, (newValue) => {
    if (newValue) {
        resetForm();
    }
});

// Watcher para limpiar cantidad cuando cambia el tipo de movimiento
watch(tipoMovimiento, () => {
    cantidad.value = null;
    motivo.value = '';
    submitted.value = false;
});

// Función para resetear el formulario
const resetForm = () => {
    tipoMovimiento.value = 'entrada';
    cantidad.value = null;
    motivo.value = '';
    submitted.value = false;
    nuevoStockActual.value = props.producto?.stock_actual || null;
    nuevoStockMinimo.value = props.producto?.stock_minimo || null;
};

// Función para validar entrada numérica en tiempo real
const onStockKeyDown = (event) => {
    const currentValue = event.target.value;
    const key = event.key;

    // Permitir teclas de control
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].includes(event.keyCode) ||
        (event.ctrlKey && [65, 67, 86, 88].includes(event.keyCode))) {
        return;
    }

    // Solo permitir números
    if (!/[0-9]/.test(key)) {
        event.preventDefault();
        return;
    }

    const newValue = currentValue + key;
    const num = parseInt(newValue);

    // Limitar a máximo 4 dígitos y máximo 9999
    if (newValue.length > 4 || num > 9999) {
        event.preventDefault();
        return;
    }
};

// Función para manejar paste en campos numéricos
const onStockPaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');
    const numericValue = paste.replace(/[^0-9]/g, '');

    if (numericValue) {
        let num = parseInt(numericValue);
        if (num > 9999) {
            num = 9999;
        }
        event.target.value = num.toString();
        // Triggear el evento input para actualizar v-model
        event.target.dispatchEvent(new Event('input', { bubbles: true }));
    }
};

// Función para incrementar cantidad
const incrementarCantidad = () => {
    const valorActual = cantidad.value || 0;
    if (valorActual < 9999) {
        cantidad.value = valorActual + 1;
    }
};

// Función para decrementar cantidad
const decrementarCantidad = () => {
    const valorActual = cantidad.value || 0;
    if (valorActual > 0) {
        cantidad.value = valorActual - 1;
    }
};

// Función para actualizar el stock
const actualizarStock = async () => {
    submitted.value = true;

    // Validaciones
    if (!cantidad.value || cantidad.value <= 0 || cantidad.value > 9999) {
        toast.add({
            severity: "warn",
            summary: "Campo requerido",
            detail: "Debes ingresar una cantidad válida entre 1 y 9999.",
            life: 4000
        });
        return;
    }

    if (!motivo.value.trim() || motivo.value.trim().length < 3 || motivo.value.trim().length > 100) {
        toast.add({
            severity: "warn",
            summary: "Campo requerido",
            detail: "El motivo debe tener entre 3 y 100 caracteres.",
            life: 4000
        });
        return;
    }

    // Validar que el stock resultante sea válido
    if (stockResultante.value < 0 || stockResultante.value > 9999) {
        toast.add({
            severity: "warn",
            summary: "Stock inválido",
            detail: "El stock resultante debe estar entre 0 y 9999.",
            life: 4000
        });
        return;
    }

    // Validación específica para salida de stock
    if (tipoMovimiento.value === 'salida' && cantidad.value > props.producto.stock_actual) {
        toast.add({
            severity: "warn",
            summary: "Stock insuficiente",
            detail: `No puedes retirar ${cantidad.value} unidades. Stock disponible: ${props.producto.stock_actual}`,
            life: 4000
        });
        return;
    }

    isLoading.value = true;

    try {
        // Preparar datos como objeto JSON
        const data = {
            tipo_movimiento: tipoMovimiento.value,
            cantidad: parseInt(cantidad.value),
            motivo: motivo.value.trim(),
            stock_resultante: parseInt(stockResultante.value)
        };

        // Si hay campos de ajuste de stock mínimo, incluirlos
        if (nuevoStockMinimo.value !== null && nuevoStockMinimo.value !== props.producto.stock_minimo) {
            data.nuevo_stock_minimo = parseInt(nuevoStockMinimo.value);
        }



        const response = await axios.patch(`/api/productos/${props.producto.id}/actualizar-stock`, data, {
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
        });

        // Emitir evento para actualizar la lista de productos y cerrar modal
        emit('stockUpdated', response.data);

        // Cerrar modal
        emit('update:visible', false);

    } catch (err) {
        let errorMessage = "Error al actualizar el stock";

        if (err.response?.status === 422) {
            // Error de validación
            const errors = err.response.data.errors || {};
            const firstError = Object.values(errors)[0]?.[0];
            errorMessage = firstError || err.response.data.message || "Error de validación";
        } else {
            errorMessage = err.response?.data?.message || errorMessage;
        }

        toast.add({
            severity: "error",
            summary: "Error",
            detail: errorMessage,
            life: 6000
        });
    } finally {
        isLoading.value = false;
    }
};

// Función para cerrar el modal
const cerrarModal = () => {
    emit('update:visible', false);
};

// Función para obtener color del tipo de movimiento
const getColorTipoMovimiento = (tipo) => {
    switch (tipo) {
        case 'entrada':
            return 'text-green-600 bg-green-50 border-green-200';
        case 'salida':
            return 'text-red-600 bg-red-50 border-red-200';
        case 'ajuste':
            return 'text-blue-600 bg-blue-50 border-blue-200';
        default:
            return 'text-gray-600 bg-gray-50 border-gray-200';
    }
};
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        header="Actualizar Stock"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="space-y-6">
            <!-- Información del producto -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-800 mb-2">{{ producto.nombre }}</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Stock actual:</span>
                        <span class="font-semibold ml-2">{{ producto.stock_actual || 0 }} unidades</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Stock mínimo:</span>
                        <span class="font-semibold ml-2">{{ producto.stock_minimo || 1 }} unidades</span>
                    </div>
                </div>
            </div>

            <!-- Tipo de movimiento -->
            <div class="space-y-3">
                <label class="block text-sm font-semibold text-gray-700">
                    Tipo de movimiento: <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 gap-2">
                    <label
                        v-for="tipo in tiposMovimiento"
                        :key="tipo.value"
                        class="flex items-center p-3 border rounded-lg cursor-pointer transition-all hover:bg-gray-50"
                        :class="tipoMovimiento === tipo.value ? getColorTipoMovimiento(tipo.value) : 'border-gray-200'"
                    >
                        <input
                            type="radio"
                            v-model="tipoMovimiento"
                            :value="tipo.value"
                            class="mr-3"
                        />
                        <div class="flex-1">
                            <div class="font-medium">{{ tipo.label }}</div>
                            <div class="text-xs opacity-75">{{ tipo.descripcion }}</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Cantidad -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                    {{ tipoMovimiento === 'ajuste' ? 'Nuevo stock:' : 'Cantidad:' }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="decrementarCantidad"
                        :disabled="!cantidad || cantidad <= 0"
                        class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white p-2 rounded-lg transition-all"
                    >
                        <FontAwesomeIcon :icon="faMinus" class="h-4 w-4" />
                    </button>
                    <InputText
                        v-model="cantidad"
                        type="number"
                        min="0"
                        max="9999"
                        class="flex-1 text-center border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                        :class="{'p-invalid': submitted && (!cantidad || cantidad <= 0)}"
                        placeholder="0"
                        @keydown="onStockKeyDown"
                        @paste="onStockPaste"
                    />
                    <button
                        type="button"
                        @click="incrementarCantidad"
                        :disabled="cantidad >= 9999"
                        class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white p-2 rounded-lg transition-all"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4" />
                    </button>
                </div>
                <small class="text-red-500" v-if="submitted && (!cantidad || cantidad <= 0)">
                    La cantidad es obligatoria y debe ser mayor a 0.
                </small>
            </div>

            <!-- Previsualización del resultado -->
            <div v-if="cantidad && cantidad > 0" class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <FontAwesomeIcon :icon="faInfoCircle" class="h-4 w-4 text-blue-600" />
                    <span class="font-semibold text-blue-800">Resultado del movimiento:</span>
                </div>
                <div class="text-sm space-y-1">
                    <div class="flex justify-between">
                        <span>Stock actual:</span>
                        <span class="font-medium">{{ producto.stock_actual || 0 }} unidades</span>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ tipoMovimiento === 'entrada' ? 'Cantidad a agregar:' : tipoMovimiento === 'salida' ? 'Cantidad a retirar:' : 'Nuevo stock:' }}</span>
                        <span class="font-medium" :class="tipoMovimiento === 'entrada' ? 'text-green-600' : tipoMovimiento === 'salida' ? 'text-red-600' : 'text-blue-600'">
                            {{ tipoMovimiento === 'ajuste' ? '' : (tipoMovimiento === 'entrada' ? '+' : '-') }}{{ cantidad }} unidades
                        </span>
                    </div>
                    <hr class="border-blue-200">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Stock resultante:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-lg" :class="stockEnAlerta ? 'text-red-600' : 'text-green-600'">
                                {{ stockResultante }} unidades
                            </span>
                            <FontAwesomeIcon
                                v-if="stockEnAlerta"
                                :icon="faWarning"
                                class="h-4 w-4 text-red-500"
                                title="Stock por debajo del mínimo"
                            />
                        </div>
                    </div>
                    <div v-if="stockEnAlerta" class="text-xs text-red-600 mt-2 flex items-center gap-1">
                        <FontAwesomeIcon :icon="faWarning" class="h-3 w-3" />
                        El stock resultante está por debajo del mínimo recomendado ({{ producto.stock_minimo || 1 }} unidades)
                    </div>
                </div>
            </div>

            <!-- Motivo -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                    Motivo del movimiento: <span class="text-red-500">*</span>
                </label>
                <InputText
                    v-model="motivo"
                    type="text"
                    maxlength="100"
                    class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                    :class="{'p-invalid': submitted && !motivo.trim()}"
                    placeholder="Ejemplo: Compra de mercancía, Producto dañado, Inventario físico, etc."
                />
                <small class="text-gray-500 text-xs">
                    Describe brevemente la razón de este movimiento de stock (máximo 100 caracteres)
                </small>
                <small class="text-red-500" v-if="submitted && !motivo.trim()">
                    El motivo es obligatorio.
                </small>
            </div>

            <!-- Ajuste de stock mínimo (opcional) -->
            <div class="space-y-2 border-t pt-4">
                <label class="block text-sm font-medium text-gray-600">
                    Ajustar stock mínimo (opcional):
                </label>
                <InputText
                    v-model="nuevoStockMinimo"
                    type="number"
                    min="1"
                    max="100"
                    class="w-full border-2 border-gray-300 hover:border-gray-400 focus:border-gray-400 focus:ring-0 focus:shadow-none rounded-md"
                    placeholder="Nuevo stock mínimo"
                    @keydown="onStockKeyDown"
                    @paste="onStockPaste"
                />
                <small class="text-gray-500 text-xs">
                    Actual: {{ producto.stock_minimo || 1 }} unidades
                </small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-center gap-4 w-full mt-6">
                <button
                    class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="actualizarStock"
                    :disabled="isLoading"
                >
                    <FontAwesomeIcon
                        :icon="isLoading ? faSpinner : faCheck"
                        :class="[
                            'h-5 text-white',
                            { 'animate-spin': isLoading }
                        ]"
                    />
                    <span v-if="!isLoading">Actualizar Stock</span>
                    <span v-else>Actualizando...</span>
                </button>
                <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                    @click="cerrarModal"
                    :disabled="isLoading"
                >
                    <FontAwesomeIcon :icon="faXmark" class="h-5" />
                    Cancelar
                </button>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Estilos personalizados para el componente */
.p-invalid {
    border-color: #ef4444 !important;
}

/* Animación para el spinner */
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
