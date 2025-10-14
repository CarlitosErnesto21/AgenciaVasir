<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faFilter, faImages, faPencil, faPlus, faSignOut, faTrashCan, faXmark } from "@fortawesome/free-solid-svg-icons";
import DatePicker from "primevue/datepicker";
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import Carousel from 'primevue/carousel';
import axios from "axios";
// --------- CONDICIONES Y RECORDATORIO LISTA
const condicionesLista = ref([]);
const nuevoItemCondiciones = ref("");
const agregarItemCondiciones = () => {
    if (nuevoItemCondiciones.value.trim()) {
        condicionesLista.value.push(nuevoItemCondiciones.value.trim());
        nuevoItemCondiciones.value = "";
    }
};
const eliminarItemCondiciones = (index) => {
    condicionesLista.value.splice(index, 1);
};
const recordatorioLista = ref([]);
const nuevoItemRecordatorio = ref("");
const agregarItemRecordatorio = () => {
    if (nuevoItemRecordatorio.value.trim()) {
        recordatorioLista.value.push(nuevoItemRecordatorio.value.trim());
        nuevoItemRecordatorio.value = "";
    }
};
const eliminarItemRecordatorio = (index) => {
    recordatorioLista.value.splice(index, 1);
};


const toast = useToast();

const paquetes = ref([]);
const paquete = ref({
    id: null,
    nombre: "",
    incluye: "",
    condiciones: "",
    recordatorio: "",
    precio: null,
    pais_id: null,
    provincia_id: null,
    fecha_salida: null,
    fecha_regreso: null,
    imagenes: [],
});
const incluyeLista = ref([]);
const nuevoItemIncluye = ref("");
const imagenPreviewList = ref([]);
const imagenFiles = ref([]);
const removedImages = ref([]);
const dialog = ref(false);
const deleteDialog = ref(false);
const unsavedChangesDialog = ref(false);
const submitted = ref(false);
const hasUnsavedChanges = ref(false);
const originalPaqueteData = ref(null);


// Filtros avanzados y helpers de fechas
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    pais_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    provincia_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    fecha_salida: { value: null, matchMode: FilterMatchMode.DATE_IS },
});
const selectedPais = ref(null);
const selectedProvincia = ref(null);
const selectedFechaInicio = ref(null);
const selectedFechaFin = ref(null);
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");
const url = "/api/paquetes";
const paises = ref([]);
const provincias = ref([]);
const showImageDialog = ref(false);
const selectedImages = ref([]);
const carouselIndex = ref(0);
const fileInput = ref(null);

// Ordenar por fecha de creación descendente y aplicar filtros avanzados
const filteredPaquetes = computed(() => {
    let filtered = paquetes.value.slice().sort((a, b) => {
        const dateA = new Date(a.created_at || a.fecha_salida || 0);
        const dateB = new Date(b.created_at || b.fecha_salida || 0);
        return dateB - dateA;
    });
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(paquete =>
            paquete.nombre?.toLowerCase().includes(searchTerm) ||
            paquete.incluye?.toLowerCase().includes(searchTerm) ||
            paquete.condiciones?.toLowerCase().includes(searchTerm) ||
            paquete.recordatorio?.toLowerCase().includes(searchTerm)
        );
    }
    if (filters.value.pais_id.value) {
        filtered = filtered.filter(paquete => paquete.pais_id == filters.value.pais_id.value);
    }
    if (filters.value.provincia_id.value) {
        filtered = filtered.filter(paquete => paquete.provincia_id == filters.value.provincia_id.value);
    }
    // Filtro por rango de fechas de salida
    if (selectedFechaInicio.value || selectedFechaFin.value) {
        filtered = filtered.filter(paquete => {
            if (!paquete.fecha_salida) return false;
            const fechaPaquete = new Date(paquete.fecha_salida);
            let cumpleFiltro = true;
            if (selectedFechaInicio.value) {
                const fechaInicio = new Date(selectedFechaInicio.value);
                cumpleFiltro = cumpleFiltro && fechaPaquete >= fechaInicio;
            }
            if (selectedFechaFin.value) {
                const fechaFin = new Date(selectedFechaFin.value);
                fechaFin.setHours(23, 59, 59, 999);
                cumpleFiltro = cumpleFiltro && fechaPaquete <= fechaFin;
            }
            return cumpleFiltro;
        });
    }
    return filtered;
});

// Helpers de fechas
const getMinDate = () => {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    return now;
};
const getMinDateRegreso = () => {
    if (!paquete.value.fecha_salida) return getMinDate();
    const fechaSalida = new Date(paquete.value.fecha_salida);
    fechaSalida.setHours(fechaSalida.getHours() + 1);
    return fechaSalida;
};
const getMaxDateSalida = () => {
    if (!paquete.value.fecha_regreso) return null;
    const fechaRegreso = new Date(paquete.value.fecha_regreso);
    fechaRegreso.setHours(fechaRegreso.getHours() - 1);
    return fechaRegreso;
};

// Validaciones de fechas
const validateFechaSalida = () => {
    if (!paquete.value.fecha_salida) return true;
    const now = new Date();
    const fechaSalida = new Date(paquete.value.fecha_salida);
    const minTime = new Date(now.getTime() + 60 * 60 * 1000);
    if (fechaSalida < minTime) return false;
    if (paquete.value.fecha_regreso) {
        const fechaRegreso = new Date(paquete.value.fecha_regreso);
        const diferenciaHoras = (fechaRegreso - fechaSalida) / (1000 * 60 * 60);
        if (diferenciaHoras < 1) return false;
    }
    return true;
};
const validateFechaRegreso = () => {
    if (!paquete.value.fecha_regreso) return true;
    const fechaRegreso = new Date(paquete.value.fecha_regreso);
    if (paquete.value.fecha_salida) {
        const fechaSalida = new Date(paquete.value.fecha_salida);
        const diferenciaHoras = (fechaRegreso - fechaSalida) / (1000 * 60 * 60);
        if (diferenciaHoras < 1) return false;
    }
    return true;
};

// Filtros: listeners para fechas
const onFechaInicioFilterChange = () => {
    if (selectedFechaInicio.value && selectedFechaFin.value) {
        const fechaInicio = new Date(selectedFechaInicio.value);
        const fechaFin = new Date(selectedFechaFin.value);
        if (fechaInicio > fechaFin) {
            selectedFechaFin.value = null;
            toast.add({ severity: "warn", summary: "Fecha ajustada", detail: "La fecha 'hasta' se limpió porque era anterior a la fecha 'desde'.", life: 3000 });
        }
    }
};
const onFechaFinFilterChange = () => {
    if (selectedFechaInicio.value && selectedFechaFin.value) {
        const fechaInicio = new Date(selectedFechaInicio.value);
        const fechaFin = new Date(selectedFechaFin.value);
        if (fechaFin < fechaInicio) {
            selectedFechaInicio.value = null;
            toast.add({ severity: "warn", summary: "Fecha ajustada", detail: "La fecha 'desde' se limpió porque era posterior a la fecha 'hasta'.", life: 3000 });
        }
    }
};

onMounted(async () => {
    // Limpiar filtros sin mostrar toast al cargar
    selectedPais.value = null;
    selectedProvincia.value = null;
    selectedFechaInicio.value = null;
    selectedFechaFin.value = null;
    filters.value.global.value = null;
    filters.value.pais_id.value = null;
    filters.value.provincia_id.value = null;
    filters.value.fecha_salida.value = null;
    await fetchPaquetes();
    await fetchPaises();
});

const fetchPaquetes = async () => {
    const { data } = await axios.get(url);
    // Forzar a array si es posible
    const paquetesArray = Array.isArray(data) ? data : (data.data || data.paquetes || []);
    paquetes.value = paquetesArray.map(p => ({
        ...p,
        pais: p.pais || (p.pais_id ? paises.value.find(pa => pa.id === p.pais_id) : null),
        provincia: p.provincia || (p.provincia_id ? provincias.value.find(pr => pr.id === p.provincia_id) : null),
        imagenes: (p.imagenes || []).map(img => typeof img === "string" ? img : img.nombre)
    }));
}
const fetchPaises = async () => {
    const { data } = await axios.get("/api/paises");
    paises.value = data;
}
const fetchProvincias = async (paisId) => {
    const { data } = await axios.get(`/api/provincias?pais_id=${paisId}`);
    provincias.value = data;
}
watch(() => paquete.value.pais_id, (val) => {
    if (val) {
        // Limpia antes de cargar nuevas provincias
        provincias.value = [];
        paquete.value.provincia_id = null;
        fetchProvincias(val);
    } else {
        provincias.value = [];
        paquete.value.provincia_id = null;
    }
});

// --------- FORM & DIALOGS
function resetForm() {
    paquete.value = {
        id: null,
        nombre: "",
        incluye: "",
        condiciones: "",
        recordatorio: "",
        precio: null,
        pais_id: null,
        provincia_id: null,
        fecha_salida: null,
        fecha_regreso: null,
        imagenes: [],
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    incluyeLista.value = [];
    nuevoItemIncluye.value = "";
    condicionesLista.value = [];
    nuevoItemCondiciones.value = "";
    recordatorioLista.value = [];
    nuevoItemRecordatorio.value = "";
    submitted.value = false;
}
function openNew() {
    resetForm();
    btnTitle.value = "Guardar";
    // Si hay país por defecto, cargar provincias
    if (paquete.value.pais_id) {
        fetchProvincias(paquete.value.pais_id);
    } else if (paises.value.length > 0) {
        // Si hay países, usar el primero como default
        paquete.value.pais_id = paises.value[0].id;
        fetchProvincias(paquete.value.pais_id);
    } else {
        provincias.value = [];
    }
    dialog.value = true;
    nextTick(() => {
        originalPaqueteData.value = {
            ...paquete.value,
            incluye_lista: [...incluyeLista.value],
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
}
function editPaquete(row) {
    resetForm();
    paquete.value = { ...row };
    incluyeLista.value = textoALista(row.incluye);
    condicionesLista.value = textoALista(row.condiciones);
    recordatorioLista.value = textoALista(row.recordatorio);
    imagenPreviewList.value = (row.imagenes || []).map(img => typeof img === "string" ? img : img.nombre);
    btnTitle.value = "Actualizar";
    // Cargar provincias del país del paquete editado y mantener la provincia seleccionada
    if (row.pais_id) {
        fetchProvincias(row.pais_id).then(() => {
            paquete.value.provincia_id = row.provincia_id;
        });
    } else {
        provincias.value = [];
    }
    dialog.value = true;
    nextTick(() => {
        originalPaqueteData.value = {
            ...paquete.value,
            incluye_lista: [...incluyeLista.value],
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
}

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
    originalTourData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// --------- INCLUYE LISTA
const agregarItemIncluye = () => {
    if (nuevoItemIncluye.value.trim()) {
        incluyeLista.value.push(nuevoItemIncluye.value.trim());
        nuevoItemIncluye.value = "";
    }
};
const eliminarItemIncluye = (index) => {
    incluyeLista.value.splice(index, 1);
};
const textoALista = (texto) => {
    return texto ? texto.split('|').filter(item => item.trim()).map(item => item.trim()) : [];
};
const listaATexto = (lista) => {
    return lista.join('|');
};

const onImageSelect = (event) => {
    const files = event.target ? event.target.files : event.files;
    const maxSize = 2 * 1024 * 1024;
    for (const file of files) {
        if (file instanceof File) {
            if (file.size > maxSize) {
                toast.add({ severity: "warn", summary: "Archivo no válido", detail: "Máximo 2MB por imagen.", life: 5000 });
                continue;
            }
            if (!file.type.startsWith('image/')) {
                toast.add({ severity: "warn", summary: "Formato no válido", detail: "Solo imágenes válidas.", life: 4000 });
                continue;
            }
            imagenFiles.value.push(file);
            const reader = new FileReader();
            reader.onload = (e) => imagenPreviewList.value.push(e.target.result);
            reader.readAsDataURL(file);
        }
    }
};
const removeImage = (index) => {
    const removed = imagenPreviewList.value[index];
    if (typeof removed === "string" && removed.startsWith("data:image")) {
        imagenPreviewList.value.splice(index, 1);
        imagenFiles.value.splice(index, 1);
    } else {
        removedImages.value.push(removed);
        imagenPreviewList.value.splice(index, 1);
    }
};
function viewImages(imagenesPaquete) {
    selectedImages.value = (imagenesPaquete || []).map(img => "/storage/" + (typeof img === "string" ? img : img.nombre));
    showImageDialog.value = true;
}

// --------- SAVE/DELETE
const savePaquete = async () => {
    submitted.value = true;
    // Validaciones robustas como en Tours
    if (!paquete.value.nombre || paquete.value.nombre.length < 6 || paquete.value.nombre.length > 100) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "El nombre debe tener entre 6 y 100 caracteres.", life: 4000 });
        return;
    }
    if (!paquete.value.fecha_salida || !paquete.value.fecha_regreso) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Debes ingresar las fechas de salida y regreso.", life: 4000 });
        return;
    }
    if (!validateFechaSalida() || !validateFechaRegreso()) {
        toast.add({ severity: "warn", summary: "Verifica las fechas", detail: "La fecha de salida debe ser futura y la de regreso posterior a la salida (mínimo 1 hora).", life: 4000 });
        return;
    }
    if (!paquete.value.precio || paquete.value.precio <= 0 || paquete.value.precio > 999999.99) {
        toast.add({ severity: "warn", summary: "Precio inválido", detail: "El precio debe ser mayor a 0 y menor o igual a 9999.99.", life: 4000 });
        return;
    }
    // Validar incluye
    const incluyeValidos = incluyeLista.value.filter(item => item.trim() !== "");
    if (incluyeValidos.length === 0) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Debes agregar al menos un elemento en Incluye.", life: 4000 });
        return;
    }
    // Validar condiciones
    const condicionesValidas = condicionesLista.value.filter(item => item.trim() !== "");
    if (condicionesValidas.length === 0) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Debes agregar al menos una condición válida.", life: 4000 });
        return;
    }
    // Validar recordatorio
    const recordatorioValidos = recordatorioLista.value.filter(item => item.trim() !== "");
    if (recordatorioValidos.length === 0) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Debes agregar al menos un recordatorio válido.", life: 4000 });
        return;
    }
    if (imagenPreviewList.value.length === 0) {
        toast.add({ severity: "warn", summary: "Imágenes requeridas", detail: "Debes subir al menos una imagen.", life: 4000 });
        return;
    }
    const maxSize = 2 * 1024 * 1024;
    const oversizedFiles = imagenFiles.value.filter(file => file.size > maxSize);
    if (oversizedFiles.length > 0) {
        toast.add({ severity: "warn", summary: "Imágenes muy grandes", detail: "Máximo 2MB por imagen.", life: 4000 });
        return;
    }
    let formData = new FormData();
    formData.append("nombre", paquete.value.nombre || "");
    // 'incluye' es opcional, no requiere validación ni contenido obligatorio
    formData.append("incluye", listaATexto(incluyeValidos));
    formData.append("condiciones", listaATexto(condicionesValidas));
    formData.append("recordatorio", listaATexto(recordatorioValidos));
    formData.append("precio", paquete.value.precio || "");
    // Enviar país y provincia al backend (país se obtiene por la provincia seleccionada)
    // Buscar el país de la provincia seleccionada
    let provinciaObj = provincias.value.find(p => p.id === paquete.value.provincia_id);
    let paisId = provinciaObj ? provinciaObj.pais_id : "";
    formData.append("pais_id", paisId);
    formData.append("provincia_id", paquete.value.provincia_id || "");
    formData.append("fecha_salida", paquete.value.fecha_salida);
    formData.append("fecha_regreso", paquete.value.fecha_regreso);
    imagenFiles.value.forEach(img => formData.append('imagenes[]', img));
    removedImages.value.forEach(img => {
        const fileName = img.includes("/") ? img.split("/").pop() : img;
        formData.append("removed_images[]", fileName);
    });
    try {
        if (paquete.value.id) {
            formData.append("_method", "PUT");
            await axios.post(`${url}/${paquete.value.id}`, formData);
            toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Paquete actualizado', life: 4000 });
        } else {
            await axios.post(url, formData);
            toast.add({ severity: 'success', summary: 'Creado', detail: 'Paquete creado', life: 4000 });
        }
        dialog.value = false;
        hasUnsavedChanges.value = false;
        await fetchPaquetes();
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "Revisa los campos e intenta nuevamente.", life: 5000 });
    }
}
const confirmDeletePaquete = (row) => {
    paquete.value = { ...row };
    deleteDialog.value = true;
}
const deletePaquete = async () => {
    try {
        await axios.delete(`${url}/${paquete.value.id}`);
        await fetchPaquetes();
        deleteDialog.value = false;
        toast.add({ severity: "success", summary: "Eliminado", detail: "Paquete eliminado", life: 4000 });
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudo eliminar el paquete.", life: 5000 });
    }
}

// --------- FILTERS
const clearFilters = () => {
    selectedPais.value = null;
    selectedProvincia.value = null;
    selectedFechaInicio.value = null;
    selectedFechaFin.value = null;
    filters.value.global.value = null;
    filters.value.pais_id.value = null;
    filters.value.provincia_id.value = null;
    filters.value.fecha_salida.value = null;
    toast.add({
        severity: "info",
        summary: "Filtros limpiados",
        detail: "Se han removido todos los filtros aplicados.",
        life: 3000
    });
};
watch(() => selectedPais.value, (val) => {
    filters.value.pais_id.value = val;
    if (val) fetchProvincias(val);
    else provincias.value = [];
});
watch(() => selectedProvincia.value, (val) => {
    filters.value.provincia_id.value = val;
});
watch(() => selectedFechaInicio.value, onFechaInicioFilterChange);
watch(() => selectedFechaFin.value, onFechaFinFilterChange);

// --------- CHANGE DETECTION
watch([paquete, incluyeLista, imagenPreviewList, removedImages], () => {
    if (originalPaqueteData.value && dialog.value) {
        nextTick(() => {
            const current = {
                ...paquete.value,
                incluye_lista: [...incluyeLista.value],
                imagenes_actuales: [...imagenPreviewList.value]
            };
            const hasChanges = JSON.stringify(current) !== JSON.stringify({
                ...originalPaqueteData.value,
                imagenes_actuales: originalPaqueteData.value.imagenes_originales
            }) || removedImages.value.length > 0;
            const isCreatingNew = !originalPaqueteData.value.id;
            const hasAnyData = paquete.value.nombre || paquete.value.destino || paquete.value.fecha_salida || paquete.value.fecha_regreso || paquete.value.precio || paquete.value.pais_id || paquete.value.provincia_id || incluyeLista.value.length > 0 || imagenPreviewList.value.length > 0;
            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true, flush: 'post' });
</script>

<template>
    <Head title="Paquetes" />
    <AuthenticatedLayout>
        <Toast class="z-[9999]" />
        <div class="py-4 sm:py-6 px-4 sm:px-7 mt-6 sm:mt-10 mx-auto bg-red-50 shadow-md rounded-lg h-screen-full">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-4 gap-4">
                <h3 class="text-lg sm:text-2xl text-blue-600 font-bold">Catálogo de Paquetes</h3>
                <button
                    class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                    @click="openNew">
                    <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 text-white" /><span>&nbsp;Agregar paquete</span>
                </button>
            </div>
            <DataTable
                :value="filteredPaquetes"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} paquetes"
                class="overflow-x-auto max-w-full"
                style="display: block; max-width: 84vw"
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
                                    {{ filteredPaquetes.length }} resultado{{ filteredPaquetes.length !== 1 ? 's' : '' }}
                                </div>
                            </div>
                            <button class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md" @click="clearFilters">
                                <FontAwesomeIcon :icon="faFilter" class="h-4 w-4 text-white" /><span>&nbsp;Limpiar filtros</span>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar paquetes..." class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div>
                                    <Select v-model="selectedPais" :options="paises" optionLabel="nombre" optionValue="id" placeholder="País" class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;" :clearable="true"/>
                                </div>
                                <div>
                                    <Select v-model="selectedProvincia" :options="provincias" optionLabel="nombre" optionValue="id" placeholder="Provincia" class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;" :clearable="true"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <Column field="nombre" header="Nombre" sortable class="w-44 min-w-44">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{ slotProps.data.nombre }}
                        </div>
                    </template>
                </Column>
                <Column field="incluye" header="Incluye" class="w-40 min-w-40">
                    <template #body="slotProps">
                        <ul class="text-sm leading-relaxed whitespace-normal ps-3 mb-0">
                            <li v-for="item in textoALista(slotProps.data.incluye)" :key="item">{{ item }}</li>
                        </ul>
                    </template>
                </Column>
                <Column field="pais.nombre" header="País" class="w-32 min-w-32">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.pais?.nombre }}
                        </div>
                    </template>
                </Column>
                <Column field="provincia.nombre" header="Provincia" class="w-32 min-w-32">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.provincia?.nombre }}
                        </div>
                    </template>
                </Column>
                <Column field="fecha_salida" header="Fecha salida" class="w-36 min-w-36">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.fecha_salida }}
                        </div>
                    </template>
                </Column>
                <Column field="precio" header="Precio" class="w-24 min-w-24">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{isNaN(Number(slotProps.data.precio)) ? "" : `$${Number(slotProps.data.precio).toFixed(2)}` }}
                        </div>
                    </template>
                </Column>
                <Column :exportable="false" class="w-56 min-w-56">
                    <template #header>
                        <div class="text-center w-full font-bold">
                            Acciones
                        </div>
                    </template>
                    <template #body="slotProps">
                        <div class="flex gap-1 justify-center items-center w-64">
                            <button
                                class="bg-orange-200/30 border py-2 px-1 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editPaquete(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-4 w-4 text-orange-600" />
                                &nbsp;Editar
                            </button>
                            <button
                                class="bg-blue-200/50 border py-2 px-1 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="viewImages(slotProps.data.imagenes)">
                                <FontAwesomeIcon :icon="faImages" class="h-4 w-4 text-blue-600" />
                                &nbsp;Imgs
                            </button>
                            <button
                                class="bg-red-200/30 border py-2 px-1 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeletePaquete(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-4 w-4 text-red-600" />
                                &nbsp;Eliminar
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Paquete'" :modal="true" :style="{ width: '500px' }" :closable="false">
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">Nombre: <span class="text-red-500 font-bold">*</span></label>
                            <InputText v-model.trim="paquete.nombre" id="nombre" name="nombre" :maxlength="100" class="flex-1" />
                        </div>
                        <small class="text-red-500 ml-28" v-if="paquete.nombre && paquete.nombre.length < 6">
                            El nombre debe tener al menos 6 caracteres.
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label class="w-24 flex items-center gap-1 mt-2">Incluye:</label>
                            <div class="flex-1">
                                <div class="flex gap-2 mb-3">
                                    <input v-model="nuevoItemIncluye" type="text" placeholder="Agregar nuevo elemento..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md" @keyup.enter="agregarItemIncluye" />
                                    <button type="button" @click="agregarItemIncluye" :disabled="!nuevoItemIncluye.trim()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                        <FontAwesomeIcon :icon="faPlus" class="h-5"/>
                                    </button>
                                </div>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <div v-for="(item, index) in incluyeLista" :key="index" class="flex items-center justify-between bg-gray-50 p-2 rounded-md border">
                                        <span class="flex-1">{{ item }}</span>
                                        <button type="button" @click="eliminarItemIncluye(index)" class="text-red-500 hover:text-red-700 p-1">
                                            <FontAwesomeIcon :icon="faXmark" class="h-5"/>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="incluyeLista.length === 0" class="text-gray-500 text-sm mt-2">Sin elementos agregados.</div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label class="w-24 flex items-center gap-1 mt-2">Condiciones:<span class="text-red-500 font-bold">*</span></label>
                            <div class="flex-1">
                                <div class="flex gap-2 mb-3">
                                    <input v-model="nuevoItemCondiciones" type="text" placeholder="Agregar condición..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md" @keyup.enter="agregarItemCondiciones" />
                                    <button type="button" @click="agregarItemCondiciones" :disabled="!nuevoItemCondiciones.trim()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                        <FontAwesomeIcon :icon="faPlus" class="h-5"/>
                                    </button>
                                </div>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <div v-for="(item, index) in condicionesLista" :key="index" class="flex items-center justify-between bg-gray-50 p-2 rounded-md border">
                                        <span class="flex-1">{{ item }}</span>
                                        <button type="button" @click="eliminarItemCondiciones(index)" class="text-red-500 hover:text-red-700 p-1">
                                            <FontAwesomeIcon :icon="faXmark" class="h-5"/>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="condicionesLista.length === 0" class="text-gray-500 text-sm mt-2">Sin condiciones agregadas.</div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label class="w-24 flex items-center gap-1 mt-2">Recordatorio:<span class="text-red-500 font-bold">*</span></label>
                            <div class="flex-1">
                                <div class="flex gap-2 mb-3">
                                    <input v-model="nuevoItemRecordatorio" type="text" placeholder="Agregar recordatorio..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md" @keyup.enter="agregarItemRecordatorio" />
                                    <button type="button" @click="agregarItemRecordatorio" :disabled="!nuevoItemRecordatorio.trim()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                        <FontAwesomeIcon :icon="faPlus" class="h-5"/>
                                    </button>
                                </div>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <div v-for="(item, index) in recordatorioLista" :key="index" class="flex items-center justify-between bg-gray-50 p-2 rounded-md border">
                                        <span class="flex-1">{{ item }}</span>
                                        <button type="button" @click="eliminarItemRecordatorio(index)" class="text-red-500 hover:text-red-700 p-1">
                                            <FontAwesomeIcon :icon="faXmark" class="h-5"/>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="recordatorioLista.length === 0" class="text-gray-500 text-sm mt-2">Sin recordatorios agregados.</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-full flex flex-col">
                            <div class="flex items-center gap-4">
                                <label for="provincia" class="w-24">Provincia:<span class="text-red-500 font-bold">*</span></label>
                                <Select
                                    v-model="paquete.provincia_id"
                                    :options="provincias"
                                    optionLabel="nombre"
                                    optionValue="id"
                                    id="provincia"
                                    :filter="true"
                                    filterPlaceholder="Buscar provincia..."
                                    :showClear="true"
                                    :class="{ 'p-invalid': submitted && !paquete.provincia_id }"
                                    class="flex-1"
                                    placeholder="Selecciona una provincia"
                                    @input="onInputDropdown && onInputDropdown('provincia', $event)"
                                    @filter="onFilterDropdown && onFilterDropdown('provincia', $event)"
                                />
                            </div>
                            <small class="text-red-500 ml-28" v-if="submitted && !paquete.provincia_id">La provincia es obligatoria.</small>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="flex items-center gap-1 mb-2">Fecha salida:<span class="text-red-500 font-bold">*</span></label>
                            <DatePicker v-model="paquete.fecha_salida" class="w-full" :minDate="getMinDate()" :maxDate="getMaxDateSalida()" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Selecciona fecha de salida" />
                        </div>
                        <div class="flex-1">
                            <label class="flex items-center gap-1 mb-2">Fecha regreso:<span class="text-red-500 font-bold">*</span></label>
                            <DatePicker v-model="paquete.fecha_regreso" class="w-full" :minDate="getMinDateRegreso()" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Selecciona fecha de regreso" />
                        </div>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label class="w-24 flex items-center gap-1">Precio:<span class="text-red-500 font-bold">*</span></label>
                            <InputNumber v-model="paquete.precio" mode="currency" currency="USD" :locale="'en-US'" :min="0.01" :max="999999.99" :maxFractionDigits="2" :minFractionDigits="2" class="flex-1" placeholder="$0.00"/>
                        </div>
                        <small class="text-red-500 ml-28" v-if=" submitted && (paquete.precio == null || paquete.precio <= 0 || paquete.precio > 999999.99)">El precio es obligatorio, debe ser mayor a 0 y menor o igual a 99999999.99.</small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label class="w-24 flex items-center gap-1">Imágenes:<span class="text-red-500 font-bold">*</span></label>
                            <div class="flex-1">
                                <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple @change="onImageSelect" class="hidden" ref="fileInput"/>
                                <button type="button" class="bg-white hover:bg-red-50 text-red-500 hover:text-red-600 border border-red-500 hover:border-red-600 px-6 py-2 rounded-md flex items-center gap-2"
                                    @click="$refs.fileInput.click()">
                                    <FontAwesomeIcon :icon="faPlus" class="h-4" /><span>Subir imágenes</span>
                                </button>
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && imagenPreviewList.length === 0">Las imágenes son obligatorias (al menos una).</small>
                    </div>
                    <div class="flex gap-4 flex-wrap mt-4 ml-28">
                        <div v-for="(img, index) in imagenPreviewList" :key="index" class="relative w-32 h-32">
                            <img :src=" img.startsWith('data:image') ? img : '/storage/' + img " alt="Vista previa" class="w-full h-full object-cover rounded border"/>
                            <button @click="removeImage(index)" class="absolute top-2 right-2 bg-gray-600/80 hover:bg-gray-700/80 text-white font-bold py-1 px-2 rounded-full shadow" style="transform: translate(50%, -50%)">
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button type="button" class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="hideDialog">
                            <FontAwesomeIcon :icon="faXmark" class="h-5 text-green-600" />Cancelar
                        </button>
                        <button class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="savePaquete">
                            <FontAwesomeIcon :icon="faCheck" class="h-5 text-white" />{{ btnTitle }}
                        </button>
                    </div>
                </template>
            </Dialog>
            <Dialog v-model:visible="deleteDialog" header="Eliminar paquete" :modal="true" :style="{ width: '350px' }" :closable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar el paquete: <b>{{ paquete.nombre }}</b>?</span>
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
                            @click="deletePaquete">
                            <FontAwesomeIcon :icon="faCheck" class="h-5" /><span>Sí</span>
                        </button>
                    </div>
                </template>
            </Dialog>
            <Dialog v-model:visible="unsavedChangesDialog" header="Cambios sin guardar" :modal="true" :style="{ width: '400px' }" :closable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¡Tienes información sin guardar!</span>
                        <span class="text-red-600 text-sm font-medium mt-1">¿Deseas salir sin guardar?</span>
                    </div>
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
            <Dialog v-model:visible="showImageDialog" header="Imágenes del paquete" :modal="true" :closable="false" :style="{ width: '700px' }">
                <div v-if="selectedImages.length" class="flex flex-col items-center justify-center">
                    <Carousel :value="selectedImages" :numVisible="1" :numScroll="1" :circular="true" v-model:page="carouselIndex" class="w-full" :showIndicators="selectedImages.length > 1" :showNavigators="selectedImages.length > 1" style="max-width: 610px">
                        <template #item="slotProps">
                            <div class="flex justify-center items-center w-full h-96">
                                <img :src="slotProps.data" alt="Imagen paquete" class="w-auto h-full max-h-96 object-contain rounded shadow"/>
                            </div>
                        </template>
                    </Carousel>
                </div>
                <div v-else class="text-center text-gray-500 py-8">No hay imágenes para este paquete.</div>
                <template #footer>
                    <div class="flex justify-center w-full">
                        <button type="button" class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="showImageDialog = false">
                            <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cerrar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
