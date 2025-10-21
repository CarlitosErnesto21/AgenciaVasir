<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import Select from "primevue/select";
import { faArrowLeft, faBusSimple, faCheck, faExclamationTriangle, faFilter, faPencil, faSignOut, faTrashCan, faXmark } from "@fortawesome/free-solid-svg-icons";
import axios from "axios";

const toast = useToast();
const transportes = ref([]);
const transporte = ref({
    id: null,
    numero_placa: "",
    nombre: "",
    capacidad: null,
    marca: "",
    estado: "DISPONIBLE",
});
const selectedTransportes = ref(null);
const dialog = ref(false);
const deleteDialog = ref(false);
const unsavedChangesDialog = ref(false);
const submitted = ref(false);
const hasUnsavedChanges = ref(false);
const originalTransporteData = ref(null);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
    capacidad: { value: null, matchMode: FilterMatchMode.EQUALS },
});
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");

// Opciones para el select de estado
const estadoOptions = ref([
    { label: 'DISPONIBLE', value: 'DISPONIBLE' },
    { label: 'NO DISPONIBLE', value: 'NO_DISPONIBLE' }
]);

const filteredTransportes = computed(() => {
    let filtered = transportes.value;

    // Filtro por búsqueda global
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(t =>
            t.nombre.toLowerCase().includes(searchTerm) ||
            (t.capacidad && t.capacidad.toString().includes(searchTerm))
        );
    }

    // Filtro por nombre
    if (filters.value.nombre.value) {
        filtered = filtered.filter(t =>
            t.nombre.toLowerCase().includes(filters.value.nombre.value.toLowerCase())
        );
    }

    // Filtro por capacidad
    if (filters.value.capacidad.value) {
        filtered = filtered.filter(t =>
            t.capacidad == filters.value.capacidad.value
        );
    }

    return filtered;
});

// Watcher para cambios en el modal (para cambios no guardados)
watch([transporte], () => {
    if (originalTransporteData.value && dialog.value) {
        nextTick(() => {
            const current = { ...transporte.value };
            const hasChanges = JSON.stringify(current) !== JSON.stringify(originalTransporteData.value);
            const isCreatingNew = !originalTransporteData.value.id;
            const hasAnyData = transporte.value.nombre || transporte.value.capacidad;
            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true });

function resetForm() {
    transporte.value = {
        id: null,
        numero_placa: "",
        nombre: "",
        capacidad: null,
        marca: "",
        estado: "DISPONIBLE",
    };
    submitted.value = false;
}

onMounted(() => {
    fetchTransportes();
});

const fetchTransportes = async () => {
    try {
        const response = await axios.get("/api/transportes");
        transportes.value = response.data.sort((a, b) => b.id - a.id);
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los transportes.", life: 4000 });
    }
};

const openNew = () => {
    resetForm();
    btnTitle.value = "Guardar";
    dialog.value = true;
    nextTick(() => {
        originalTransporteData.value = { ...transporte.value };
        hasUnsavedChanges.value = false;
    });
};

const editTransporte = (t) => {
    resetForm();
    transporte.value = { ...t };
    btnTitle.value = "Actualizar";
    dialog.value = true;
    nextTick(() => {
        originalTransporteData.value = { ...transporte.value };
        hasUnsavedChanges.value = false;
    });
};

const placaRegex = /^(P|M|C|A|R|D|V|G|AB|MB|CD|CC|CR|MI|FA|PE|OF|OI)[ -]?[0-9A-F]{6}$/i;
const saveOrUpdate = async () => {
    submitted.value = true;
    if (!transporte.value.numero_placa || transporte.value.numero_placa.length < 5 || transporte.value.numero_placa.length > 10) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "La placa debe tener entre 5 y 10 caracteres.", life: 4000 });
        return;
    }
    if (!placaRegex.test(transporte.value.numero_placa)) {
        toast.add({ severity: "warn", summary: "Formato inválido", detail: "La placa debe iniciar con un prefijo válido y tener 6 caracteres alfanuméricos. Ejemplo: P 123456, AB-12A3F", life: 5000 });
        return;
    }
    if (!transporte.value.nombre || transporte.value.nombre.length < 3 || transporte.value.nombre.length > 50) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "El nombre debe tener entre 3 y 50 caracteres.", life: 4000 });
        return;
    }
    if (!transporte.value.capacidad || transporte.value.capacidad < 1) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "La capacidad debe ser al menos 1.", life: 4000 });
        return;
    }
    if (!transporte.value.marca || transporte.value.marca.length < 2 || transporte.value.marca.length > 30) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "La marca debe tener entre 2 y 30 caracteres.", life: 4000 });
        return;
    }
    try {
        let response;
        if (!transporte.value.id) {
            response = await axios.post("/api/transportes", {
                numero_placa: transporte.value.numero_placa,
                nombre: transporte.value.nombre,
                capacidad: transporte.value.capacidad,
                marca: transporte.value.marca,
                estado: transporte.value.estado,
            });
            toast.add({ severity: "success", summary: "¡Éxito!", detail: "Transporte creado correctamente.", life: 4000 });
        } else {
            response = await axios.put(`/api/transportes/${transporte.value.id}`, {
                numero_placa: transporte.value.numero_placa,
                nombre: transporte.value.nombre,
                capacidad: transporte.value.capacidad,
                marca: transporte.value.marca,
                estado: transporte.value.estado,
            });
            toast.add({ severity: "success", summary: "¡Éxito!", detail: "Transporte actualizado correctamente.", life: 4000 });
        }
        await fetchTransportes();
        dialog.value = false;
        hasUnsavedChanges.value = false;
        originalTransporteData.value = null;
        resetForm();
    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors && err.response.data.errors.numero_placa) {
            toast.add({ severity: "error", summary: "Error", detail: err.response.data.errors.numero_placa[0], life: 4000 });
        } else {
            toast.add({ severity: "error", summary: "Error", detail: "No se pudo guardar el transporte.", life: 4000 });
        }
    }
};

const confirmDeleteTransporte = (t) => {
    transporte.value = { ...t };
    deleteDialog.value = true;
};

const deleteTransporte = async () => {
    try {
        await axios.delete(`/api/transportes/${transporte.value.id}`);
        await fetchTransportes();
        deleteDialog.value = false;
        toast.add({ severity: "success", summary: "¡Eliminado!", detail: "Transporte eliminado correctamente.", life: 4000 });
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudo eliminar el transporte.", life: 4000 });
    }
};

const hideDialog = () => {
    if (hasUnsavedChanges.value) {
        unsavedChangesDialog.value = true;
    } else {
        closeDialogWithoutSaving();
    }
};

const closeDialogWithoutSaving = () => {
    dialog.value = false;
    unsavedChangesDialog.value = false;
    hasUnsavedChanges.value = false;
    originalTransporteData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// Variable para controlar el mensaje de límite máximo
const showMaxLimitMessage = ref(false);

// Función para prevenir teclas no válidas antes de escribir
const onKeyDown = (event) => {
    const currentValue = event.target.value;
    const key = event.key;
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].includes(event.keyCode) ||
        // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (event.ctrlKey && [65, 67, 86, 88].includes(event.keyCode))) {
        return;
    }
    if (!/[0-9]/.test(key)) {
        event.preventDefault();
        return;
    }
    const newValue = currentValue + key;
    const num = parseInt(newValue);

    if (newValue.length > 3 || num > 500) {
        event.preventDefault();
        if (num > 500 || newValue.length > 3) {
            showMaxLimitMessage.value = true;
        }
        return;
    }
    showMaxLimitMessage.value = false;
};

// Función para limpiar valor en caso de paste
const onPaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');
    const numericValue = paste.replace(/[^0-9]/g, '');

    if (numericValue) {
        let num = parseInt(numericValue);
        if (num > 500) {
            num = 500;
            showMaxLimitMessage.value = true;
        }
        transporte.value.capacidad = num.toString();
    }
};
</script>
<template>
    <Head title="Catálogo de Transportes" />
    <AuthenticatedLayout>
        <Toast class="z-[9999]" />
        <div class="py-4 sm:py-6 px-4 sm:px-7 mt-6 sm:mt-10 mx-auto bg-red-50 shadow-lg rounded-lg h-screen-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2 sm:gap-0">
                <div class="flex items-center gap-3">
                    <Link :href="route('tours')" class="flex items-center text-blue-600 hover:text-blue-700 transition-colors duration-200 px-4 rounded-lg" title="Regresar a Tours">
                        <FontAwesomeIcon :icon="faArrowLeft" class="h-8" />
                    </Link>
                    <h3 class="text-lg sm:text-2xl text-blue-600 font-bold">Catálogo de Transportes</h3>
                </div>
                <button
                    class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300" @click="openNew">
                    <FontAwesomeIcon :icon="faBusSimple" class="h-4 w-4 text-white" /><span>&nbsp;Agregar transporte</span>
                </button>
            </div>
            <DataTable
                :value="filteredTransportes"
                v-model:selection="selectedTransportes"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} transportes"
                class="overflow-x-auto max-w-full"
                style="display: block; max-width: 84vw;"
                responsiveLayout="scroll"
                :pt="{
                    root: { class: 'text-sm' },
                    wrapper: { class: 'text-sm' },
                    table: { class: 'text-sm' },
                    thead: { class: 'text-sm' },
                    headerRow: { class: 'text-sm' },
                    headerCell: { class: 'text-sm font-medium py-3 px-2' },
                    tbody: { class: 'text-sm' },
                    bodyRow: { class: 'h-20 text-sm' },
                    bodyCell: { class: 'py-3 px-2 text-sm' },
                    paginator: { class: 'text-xs sm:text-sm' },
                    paginatorWrapper: { class: 'flex flex-wrap justify-center sm:justify-between items-center gap-2 p-2' }
                }"
            >
                <template #header>
                    <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base font-medium text-gray-800 flex items-center gap-2">
                                    <i class="pi pi-filter text-blue-600 text-sm"></i><span>Filtros</span>
                                </h3>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                                    {{ filteredTransportes.length }} resultado{{ filteredTransportes.length !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar transportes..." class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;" />
                            </div>
                        </div>
                    </div>
                </template>
                <Column field="numero_placa" header="Placa" sortable class="w-32 min-w-24">
                    <template #body="slotProps">
                        <div class="text-sm font-mono leading-relaxed">
                            {{ slotProps.data.numero_placa }}
                        </div>
                    </template>
                </Column>
                <Column field="nombre" header="Nombre" sortable class="w-40 min-w-34">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{ slotProps.data.nombre }}
                        </div>
                    </template>
                </Column>
                <Column field="capacidad" header="Capacidad" class="w-32 min-w-20">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed text-center">
                            {{ slotProps.data.capacidad }}
                        </div>
                    </template>
                </Column>
                <Column field="marca" header="Marca" sortable class="w-32 min-w-24">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.marca }}
                        </div>
                    </template>
                </Column>
                <Column field="estado" header="Estado" sortable class="w-32 min-w-24">
                    <template #body="slotProps">
                        <span :class="slotProps.data.estado === 'DISPONIBLE' ? 'bg-green-100 text-green-800 px-2 py-1 rounded-full' : 'bg-red-100 text-red-800 px-2 py-1 rounded-full'">
                            {{ slotProps.data.estado === 'NO_DISPONIBLE' ? 'NO DISPONIBLE' : slotProps.data.estado }}
                        </span>
                    </template>
                </Column>
                <Column :exportable="false" class="w-40 min-w-52">
                    <template #header>
                        <div class="text-center w-full font-bold">
                            Acciones
                        </div>
                    </template>
                    <template #body="slotProps">
                        <div class="flex gap-2 h-full justify-center items-center">
                            <button
                                class="bg-orange-200/30 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editTransporte(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-4 w-4 text-orange-600" />
                                &nbsp;Editar
                            </button>
                            <button
                                class="bg-red-200/30 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteTransporte(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-4 w-4 text-red-600" />
                                &nbsp;Eliminar
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Transporte'" :modal="true" :style="{ width: '400px' }"
                :closable="false" :draggable="false">
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="numero_placa" class="w-24 flex items-center gap-1">
                                Placa: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText v-model.trim="transporte.numero_placa" id="numero_placa" name="numero_placa" :maxlength="10" :class="{'p-invalid': submitted && (!transporte.numero_placa || transporte.numero_placa.length < 5 || transporte.numero_placa.length > 10),}" class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md" placeholder="Ej. ABC123, 12A345"/>
                        </div>
                        <small class="text-red-500 ml-28" v-if="transporte.numero_placa && transporte.numero_placa.length < 5">
                            La placa debe tener al menos 5 caracteres. Actual: {{ transporte.numero_placa.length }}/5
                        </small>
                        <small class="text-orange-500 ml-28" v-if="transporte.numero_placa && transporte.numero_placa.length > 10">
                            Caracteres restantes: {{ 10 - transporte.numero_placa.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !transporte.numero_placa">
                            La placa es obligatoria.
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText v-model.trim="transporte.nombre" id="nombre" name="nombre" :maxlength="50" :class="{'p-invalid': submitted && (!transporte.nombre || transporte.nombre.length < 3 || transporte.nombre.length > 50),}" class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md" placeholder="Ej. Autobús, Carro, etc."/>
                        </div>
                        <small class="text-red-500 ml-28" v-if="transporte.nombre && transporte.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres. Actual: {{ transporte.nombre.length }}/3
                        </small>
                        <small class="text-orange-500 ml-28" v-if="transporte.nombre && transporte.nombre.length > 45">
                            Caracteres restantes: {{ 50 - transporte.nombre.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !transporte.nombre">
                            El nombre es obligatorio.
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="capacidad" class="w-24 flex items-center gap-1">
                                Capacidad: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model="transporte.capacidad"
                                id="capacidad"
                                name="capacidad"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                max="500"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :class="{'p-invalid': submitted && (!transporte.capacidad || transporte.capacidad < 1),}"
                                placeholder="Cantidad (1-500)"
                                @keydown="onKeyDown"
                                @paste="onPaste"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && (!transporte.capacidad || transporte.capacidad < 1)">
                            La capacidad es obligatoria y debe ser mayor o igual a 1.
                        </small>
                        <small class="text-orange-500 ml-28" v-if="showMaxLimitMessage">
                            Capacidad ajustada al límite máximo permitido (500).
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="marca" class="w-24 flex items-center gap-1">
                                Marca: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText v-model.trim="transporte.marca" id="marca" name="marca" :maxlength="30" :class="{'p-invalid': submitted && (!transporte.marca || transporte.marca.length < 2 || transporte.marca.length > 30),}" class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md" placeholder="Ej. Toyota, Mercedes"/>
                        </div>
                        <small class="text-red-500 ml-28" v-if="transporte.marca && transporte.marca.length < 2">
                            La marca debe tener al menos 2 caracteres. Actual: {{ transporte.marca.length }}/2
                        </small>
                        <small class="text-orange-500 ml-28" v-if="transporte.marca && transporte.marca.length > 30">
                            Caracteres restantes: {{ 30 - transporte.marca.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !transporte.marca">
                            La marca es obligatoria.
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="estado" class="w-24 flex items-center gap-1">
                                Estado: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Select
                                v-model="transporte.estado"
                                :options="estadoOptions"
                                optionLabel="label"
                                optionValue="value"
                                id="estado"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                            />
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button type="button" class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="hideDialog">
                            <FontAwesomeIcon :icon="faXmark" class="h-5 text-green-600" />Cancelar
                        </button>
                        <button class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="saveOrUpdate">
                            <FontAwesomeIcon :icon="faCheck" class="h-5 text-white" />{{ btnTitle }}
                        </button>
                    </div>
                </template>
            </Dialog>
            <Dialog v-model:visible="deleteDialog" header="Eliminar transporte" :modal="true" :style="{ width: '350px' }" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar el transporte: <b>{{ transporte.nombre }}</b>?</span>
                        <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button type="button" class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="deleteDialog = false">
                            <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>No</span>
                        </button>
                        <button type="button" class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="deleteTransporte">
                            <FontAwesomeIcon :icon="faCheck" class="h-5" /><span>Sí</span>
                        </button>
                    </div>
                </template>
            </Dialog>
            <Dialog v-model:visible="unsavedChangesDialog" header="Cambios sin guardar" :modal="true" :style="{ width: '400px' }" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-orange-500" />
                    <span>Tienes información sin guardar. ¿Deseas salir sin guardar?</span>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-3 w-full">
                        <button type="button" class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="continueEditing">
                            <FontAwesomeIcon :icon="faPencil" class="h-4" /><span>Continuar</span>
                        </button>
                        <button type="button" class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="closeDialogWithoutSaving">
                            <FontAwesomeIcon :icon="faSignOut" class="h-4" /><span>Salir sin guardar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
