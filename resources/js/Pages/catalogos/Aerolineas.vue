<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import DatePicker from "primevue/datepicker";
import FileUpload from 'primevue/fileupload';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye, faListDots, faPencil, faTrashCan, faPlus, faFilter, faSpinner, faCheck, faXmark, faExclamationTriangle } from '@fortawesome/free-solid-svg-icons';
import AerolineaModals from './Components/AerolineaComponents/Modales.vue';

const toast = useToast();

// 📊 Variables principales
const aerolineas = ref([]);
const selectedAerolineas = ref();
const url = "/api/aerolineas";
const IMAGE_PATH = "/storage/aerolineas/";

// 🔍 Sistema de filtros
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// 📱 Sistema de responsividad
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

const dialogStyle = computed(() => {
    if (windowWidth.value < 640) {
        return { width: '95vw', maxWidth: '380px' };
    } else if (windowWidth.value < 768) {
        return { width: '400px' };
    } else {
        return { width: '450px' };
    }
});

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);

// 📝 Formulario
const aerolinea = ref({
    id: null,
    nombre: "",
    fecha: null,
    imagenes: [],
});
const imagenPreviewList = ref([]);
const imagenFiles = ref([]);
const removedImages = ref([]);
const submitted = ref(false);
const btnTitle = ref("Guardar");

// 🎭 Estados de modales
const dialog = ref(false);
const deleteDialog = ref(false);
const showImageDialog = ref(false);
const showMoreActionsModal = ref(false);
const showDetailsModal = ref(false);
const showUnsavedChangesModal = ref(false);

// 🖼️ Sistema de imágenes
const selectedImages = ref([]);
const carouselIndex = ref(0);

// ⚡ Estados de carga
const isLoading = ref(false);
const isDeleting = ref(false);

// 📱 Aerolínea seleccionada para acciones
const selectedAerolinea = ref(null);

// Variables para controlar cambios sin guardar
const hasUnsavedChanges = ref(false);
const originalAerolineaData = ref(null);

// 🔄 Funciones de ciclo de vida
onMounted(() => {
    fetchAerolineasWithToasts();
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateWindowWidth);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateWindowWidth);
    }
});

// 📱 Funciones de responsividad
const updateWindowWidth = () => {
    if (typeof window !== 'undefined') {
        windowWidth.value = window.innerWidth;
    }
};

// 📊 Funciones de datos
const fetchAerolineas = async () => {
    try {
        const response = await axios.get(url);
        // Mapear imágenes a solo nombres
        aerolineas.value = response.data.map((aerolinea) => ({
            ...aerolinea,
            imagenes: (aerolinea.imagenes || []).map((img) =>
                typeof img === "string" ? img : img.nombre
            ),
        }));
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las aerolíneas",
            life: 4000,
        });
    }
};

const fetchAerolineasWithToasts = async () => {
    // Mostrar toast de carga
    toast.add({
        severity: "info",
        summary: "Cargando aerolíneas...",
        life: 2000
    });

    try {
        const response = await axios.get(url);
        // Mapear imágenes a solo nombres
        aerolineas.value = response.data.map((aerolinea) => ({
            ...aerolinea,
            imagenes: (aerolinea.imagenes || []).map((img) =>
                typeof img === "string" ? img : img.nombre
            ),
        }));

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Aerolíneas cargadas",
            life: 2000
        });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las aerolíneas",
            life: 4000,
        });
    }
};

// 📝 Funciones del formulario
const openNew = () => {
    aerolinea.value = {
        id: null,
        nombre: "",
        fecha: null,
        imagenes: [],
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
    btnTitle.value = "Guardar";
    hasUnsavedChanges.value = false;

    // Establecer datos originales para nuevo producto
    originalAerolineaData.value = {
        id: null,
        nombre: "",
        fecha: null,
        imagenes: [],
        imagenes_originales: []
    };

    dialog.value = true;
};

const editAerolinea = (a) => {
    aerolinea.value = { ...a };
    imagenFiles.value = [];
    btnTitle.value = "Actualizar";
    imagenPreviewList.value = Array.isArray(a.imagenes)
        ? a.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
        : [];
    removedImages.value = [];
    hasUnsavedChanges.value = false;

    // Establecer datos originales para edición
    originalAerolineaData.value = {
        ...a,
        imagenes_originales: Array.isArray(a.imagenes)
            ? a.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
            : []
    };

    dialog.value = true;
};

const hideDialog = () => {
    if (hasUnsavedChanges.value) {
        showUnsavedChangesModal.value = true;
    } else {
        closeDialogWithoutSaving();
    }
};

const closeDialogWithoutSaving = () => {
    dialog.value = false;
    showUnsavedChangesModal.value = false;
    hasUnsavedChanges.value = false;
    originalAerolineaData.value = null;
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
};

const saveOrUpdate = async () => {
    submitted.value = true;

    if (!aerolinea.value.nombre || !aerolinea.value.fecha) {
        return;
    }

    if (imagenPreviewList.value.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Debes subir al menos una imagen.",
            life: 4000,
        });
        return;
    }

    try {
        isLoading.value = true;
        const formData = new FormData();
        formData.append("nombre", String(aerolinea.value.nombre));

        let fechaFormateada = aerolinea.value.fecha;
        if (
            fechaFormateada &&
            typeof fechaFormateada === "object" &&
            typeof fechaFormateada.toISOString === "function"
        ) {
            fechaFormateada = fechaFormateada.toISOString().split("T")[0];
        } else if (typeof fechaFormateada === "string") {
            fechaFormateada = fechaFormateada;
        } else {
            fechaFormateada = "";
        }
        formData.append("fecha", fechaFormateada);

        imagenFiles.value.forEach((img) => {
            formData.append("imagenes[]", img);
        });

        removedImages.value.forEach((img) => {
            formData.append("removed_images[]", img.split("/").pop());
        });

        let response;
        if (!aerolinea.value.id) {
            response = await axios.post(url, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            toast.add({
                severity: "success",
                summary: "Aerolínea agregada",
                life: 3000,
            });
        } else {
            formData.append("_method", "PUT");
            response = await axios.post(
                `${url}/${aerolinea.value.id}`,
                formData,
                { headers: { "Content-Type": "multipart/form-data" } }
            );
            toast.add({
                severity: "info",
                summary: "Aerolínea actualizada",
                life: 3000,
            });
        }

        await fetchAerolineas();
        dialog.value = false;
        aerolinea.value = {
            id: null,
            nombre: "",
            fecha: null,
            imagenes: [],
        };
        imagenPreviewList.value = [];
        imagenFiles.value = [];
        removedImages.value = [];
        submitted.value = false;
        hasUnsavedChanges.value = false;
        originalAerolineaData.value = null;
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo guardar la aerolínea.",
            life: 4000,
        });
    } finally {
        isLoading.value = false;
    }
};

// 🗑️ Funciones de eliminación
const confirmDeleteAerolinea = (a) => {
    selectedAerolinea.value = { ...a };
    deleteDialog.value = true;
};

const deleteAerolinea = async () => {
    try {
        isDeleting.value = true;
        await axios.delete(`${url}/${selectedAerolinea.value.id}`);
        await fetchAerolineas();
        deleteDialog.value = false;
        selectedAerolinea.value = null;
        toast.add({
            severity: "success",
            summary: "Aerolínea eliminada",
            life: 3000,
        });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo eliminar la aerolínea.",
            life: 3000,
        });
    } finally {
        isDeleting.value = false;
    }
};

// 🖼️ Funciones de imágenes
const onImageSelect = (event) => {
    for (const file of event.files) {
        if (file instanceof File) {
            imagenFiles.value.push(file);
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenPreviewList.value.push(e.target.result);
                // Solo marcar cambios si el modal está abierto
                if (dialog.value) {
                    checkForChanges();
                }
            };
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

    // Solo marcar cambios si el modal está abierto
    if (dialog.value) {
        checkForChanges();
    }
};



// 🎭 Funciones de modales
const openMoreActionsModal = (aerolineaItem) => {
    selectedAerolinea.value = aerolineaItem;
    showMoreActionsModal.value = true;
};

const openDetailsModal = (aerolineaItem) => {
    selectedAerolinea.value = aerolineaItem;
    showDetailsModal.value = true;
};

const openImageModal = (index) => {
    carouselIndex.value = index;
    showImageDialog.value = true;
};

// 💾 Funciones de cambios sin guardar
const handleUnsavedChanges = (action) => {
    if (hasUnsavedChanges.value) {
        showUnsavedChangesModal.value = true;
        // Guardar la acción para ejecutar después
        window.pendingAction = action;
    } else {
        action();
    }
};

const closeWithoutSaving = () => {
    closeDialogWithoutSaving();

    // Ejecutar acción pendiente si existe
    if (window.pendingAction) {
        window.pendingAction();
        window.pendingAction = null;
    }
};const continueEditing = () => {
    showUnsavedChangesModal.value = false;
    window.pendingAction = null;
};

// 🎬 Funciones adicionales para las acciones de modales
const duplicateAerolinea = (aerolineaItem) => {
    // Crear una nueva aerolínea basada en la seleccionada
    aerolinea.value = {
        id: null,
        nombre: `${aerolineaItem.nombre} (Copia)`,
        fecha: aerolineaItem.fecha,
        imagenes: [],
    };
    imagenPreviewList.value = Array.isArray(aerolineaItem.imagenes)
        ? aerolineaItem.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
        : [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
    btnTitle.value = "Guardar";
    hasUnsavedChanges.value = false;

    // Establecer datos originales para duplicación (es como crear nuevo pero con datos previos)
    originalAerolineaData.value = {
        id: null,
        nombre: `${aerolineaItem.nombre} (Copia)`,
        fecha: aerolineaItem.fecha,
        imagenes: [],
        imagenes_originales: Array.isArray(aerolineaItem.imagenes)
            ? aerolineaItem.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
            : []
    };

    dialog.value = true;

    toast.add({
        severity: "info",
        summary: "Aerolínea duplicada",
        detail: "Se ha creado una copia de la aerolínea para editar",
        life: 3000,
    });
};

const changeAerolineaStatus = (aerolineaItem) => {
    toast.add({
        severity: "info",
        summary: "Cambio de estado",
        detail: `Función para cambiar estado de: ${aerolineaItem.nombre}`,
        life: 3000,
    });
};

const generateAerolineaReport = (aerolineaItem) => {
    toast.add({
        severity: "info",
        summary: "Generar reporte",
        detail: `Generando reporte para: ${aerolineaItem.nombre}`,
        life: 3000,
    });
};

const archiveAerolinea = (aerolineaItem) => {
    toast.add({
        severity: "info",
        summary: "Archivar aerolínea",
        detail: `Archivando aerolínea: ${aerolineaItem.nombre}`,
        life: 3000,
    });
};

// � Función para verificar cambios
const checkForChanges = () => {
    if (!originalAerolineaData.value || !dialog.value) return;

    nextTick(() => {
        const current = {
            ...aerolinea.value,
            imagenes_actuales: [...imagenPreviewList.value]
        };

        const hasChanges = JSON.stringify(current) !== JSON.stringify({
            ...originalAerolineaData.value,
            imagenes_actuales: originalAerolineaData.value.imagenes_originales
        }) || removedImages.value.length > 0;

        const isCreatingNew = !originalAerolineaData.value.id;
        const hasAnyData = aerolinea.value.nombre ||
                          aerolinea.value.fecha ||
                          imagenPreviewList.value.length > 0;

        hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
    });
};

// 👀 Watcher inteligente para detectar cambios
watch([aerolinea, imagenPreviewList, removedImages], () => {
    if (originalAerolineaData.value && dialog.value) {
        checkForChanges();
    }
}, { deep: true, flush: 'post' });
</script>

<template>
    <Head title="Aerolíneas" />
    <AuthenticatedLayout>
        <Toast class="z-[9999]" />

        <!-- 📱 Contenedor principal con diseño responsivo -->
        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Aerolíneas</h1>
                <p class="text-gray-600">Administra las aerolíneas del sistema</p>
            </div>

            <!-- 📊 Tabla de aerolíneas -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Aerolíneas</h3>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2"
                            @click="openNew"
                        >
                            <FontAwesomeIcon :icon="faPlus" class="h-4 w-4" />
                            <span>Agregar Aerolínea</span>
                        </button>
                    </div>
                </div>

                <!-- 📋 DataTable con diseño profesional -->
                <DataTable
                    :value="aerolineas"
                    v-model:selection="selectedAerolineas"
                    dataKey="id"
                    :filters="filters"
                    :paginator="true"
                    :rows="rowsPerPage"
                    :rowsPerPageOptions="rowsPerPageOptions"
                    v-model:rowsPerPage="rowsPerPage"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} aerolíneas"
                    class="overflow-x-auto"
                    responsiveLayout="scroll"
                    :pt="{
                        root: { class: 'text-sm' },
                        wrapper: { class: 'text-sm' },
                        table: { class: 'text-sm' },
                        thead: { class: 'text-sm' },
                        headerRow: { class: 'text-sm' },
                        headerCell: { class: 'text-sm font-medium py-3 px-2' },
                        tbody: { class: 'text-sm' },
                        bodyRow: { class: 'h-16 text-sm' },
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
                                        <FontAwesomeIcon :icon="faFilter" class="text-blue-600 text-sm" />
                                        <span>Filtros y Búsqueda</span>
                                    </h3>
                                    <div class="bg-blue-100 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                                        {{ aerolineas.length }} resultado{{ aerolineas.length !== 1 ? 's' : '' }}
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <InputText
                                        v-model="filters['global'].value"
                                        placeholder="🔍 Buscar aerolíneas..."
                                        class="w-full h-9 text-sm rounded-md"
                                        style="background-color: white; border-color: #93c5fd;"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Columna Nombre -->
                    <Column field="nombre" header="Nombre" sortable class="w-96 min-w-16">
                        <template #body="slotProps">
                            <div class="text-sm font-medium leading-relaxed">
                                {{ slotProps.data.nombre }}
                            </div>
                        </template>
                    </Column>

                    <!-- Columna Fecha -->
                    <Column field="fecha" header="Fecha de Fundación" sortable class="w-96 min-w-16">
                        <template #body="slotProps">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ slotProps.data.fecha
                                    ? new Date(slotProps.data.fecha).toLocaleDateString("es-ES", {
                                        day: "2-digit",
                                        month: "2-digit",
                                        year: "numeric",
                                    }) : 'No definida' }}
                            </span>
                        </template>
                    </Column>

                    <!-- Columna Acciones -->
                    <Column :exportable="false" class="w-52 min-w-16">
                        <template #header>
                            <div class="text-center w-full font-bold">
                                Acciones
                            </div>
                        </template>
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center items-center">
                                <!-- Botón Ver Más -->
                                <button
                                    class="flex bg-green-500 hover:bg-green-600 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="openMoreActionsModal(slotProps.data)"
                                    title="Más acciones"
                                >
                                    <FontAwesomeIcon :icon="faListDots" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Más</span>
                                </button>

                                <!-- Botón Editar -->
                                <button
                                    class="flex bg-blue-500 hover:bg-blue-600 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="editAerolinea(slotProps.data)"
                                    title="Editar aerolínea"
                                >
                                    <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Editar</span>
                                </button>

                                <!-- Botón Eliminar -->
                                <button
                                    class="flex bg-red-500 hover:bg-red-600 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="confirmDeleteAerolinea(slotProps.data)"
                                    title="Eliminar aerolínea"
                                >
                                    <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Eliminar</span>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- 📝 Modal de formulario -->
        <Dialog
            v-model:visible="dialog"
            :header="btnTitle + ' Aerolínea'"
            :modal="true"
            :closable="false"
            :style="dialogStyle"
            :draggable="false"
        >
            <div class="space-y-6">
                <!-- Campo Nombre -->
                <div class="w-full flex flex-col">
                    <label for="nombre" class="text-sm font-medium text-gray-700 mb-2">
                        Nombre de la Aerolínea: <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        v-model.trim="aerolinea.nombre"
                        id="nombre"
                        placeholder="Ingresa el nombre de la aerolínea"
                        :class="{ 'border-red-300': submitted && !aerolinea.nombre }"
                        class="w-full"
                    />
                    <small class="text-red-500 mt-1" v-if="submitted && !aerolinea.nombre">
                        ⚠️ El nombre es obligatorio
                    </small>
                </div>

                <!-- Campo Fecha -->
                <div class="w-full flex flex-col">
                    <label for="fecha" class="text-sm font-medium text-gray-700 mb-2">
                        Fecha de Fundación: <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        v-model="aerolinea.fecha"
                        id="fecha"
                        :showIcon="true"
                        dateFormat="yy-mm-dd"
                        :class="{ 'border-red-300': submitted && !aerolinea.fecha }"
                        class="w-full"
                        :manualInput="false"
                        placeholder="Selecciona la fecha de fundación"
                    />
                    <small class="text-red-500 mt-1" v-if="submitted && !aerolinea.fecha">
                        ⚠️ La fecha es obligatoria
                    </small>
                </div>

                <!-- Campo Imágenes -->
                <div class="w-full flex flex-col">
                    <label for="imagenes" class="text-sm font-medium text-gray-700 mb-2">
                        Imágenes: <span class="text-red-500">*</span>
                    </label>
                    <FileUpload
                        mode="basic"
                        name="imagenes[]"
                        accept="image/*"
                        :auto="true"
                        chooseLabel="Seleccionar imágenes"
                        @select="onImageSelect"
                        :customUpload="true"
                        :multiple="true"
                        class="w-full"
                    />
                    <small class="text-red-500 mt-1" v-if="submitted && imagenPreviewList.length === 0">
                        ⚠️ Al menos una imagen es obligatoria
                    </small>
                </div>

                <!-- Vista previa de imágenes -->
                <div v-if="imagenPreviewList.length > 0" class="w-full">
                    <label class="text-sm font-medium text-gray-700 mb-3 block">Vista previa de imágenes:</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div
                            v-for="(img, index) in imagenPreviewList"
                            :key="index"
                            class="relative group"
                        >
                            <img
                                :src="img.startsWith('data:image') ? img : IMAGE_PATH + img"
                                alt="Vista previa"
                                class="w-full h-24 object-cover rounded-lg border group-hover:opacity-75 transition-opacity"
                            />
                            <button
                                @click="removeImage(index)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 shadow opacity-0 group-hover:opacity-100 transition-opacity"
                                title="Eliminar imagen"
                            >
                                <FontAwesomeIcon :icon="faXmark" class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-center gap-4 w-full mt-6">
                    <button
                        type="button"
                        class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="saveOrUpdate"
                        :disabled="isLoading"
                    >
                        <FontAwesomeIcon
                            :icon="isLoading ? faSpinner : faCheck"
                            :class="[
                                'h-5 text-white',
                                { 'animate-spin': isLoading }
                            ]"
                        />
                        <span v-if="!isLoading">{{ btnTitle }}</span>
                        <span v-else>Guardando...</span>
                    </button>
                    <button
                        type="button"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="hideDialog"
                        :disabled="isLoading"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        <span>Cancelar</span>
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- 🗑️ Modal de eliminar -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Eliminar aerolínea"
            :modal="true"
            :style="dialogStyle"
            :closable="false"
            :draggable="false"
        >
            <div class="flex items-center gap-3">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                <div class="flex flex-col">
                    <span>¿Estás seguro de eliminar la aerolínea: <b>{{ selectedAerolinea?.nombre }}</b>?</span>
                    <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-center gap-4 w-full">
                    <button
                        type="button"
                        class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="deleteAerolinea"
                        :disabled="isDeleting"
                    >
                        <FontAwesomeIcon
                            :icon="isDeleting ? faSpinner : faCheck"
                            :class="[
                                'h-5',
                                { 'animate-spin': isDeleting }
                            ]"
                        />
                        <span v-if="!isDeleting">Eliminar</span>
                        <span v-else>Eliminando...</span>
                    </button>
                    <button
                        type="button"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="deleteDialog = false"
                        :disabled="isDeleting"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        <span>Cancelar</span>
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- 🎭 Componente de modales separado -->
        <AerolineaModals
            v-model:visible="showMoreActionsModal"
            v-model:detailsVisible="showDetailsModal"
            v-model:carouselVisible="showImageDialog"
            v-model:deleteVisible="deleteDialog"
            v-model:unsavedChangesVisible="showUnsavedChangesModal"
            :aerolinea="selectedAerolinea"
            :dialogStyle="dialogStyle"
            :selectedImages="selectedImages"
            :carouselIndex="carouselIndex"
            :imagePath="IMAGE_PATH"
            :isDeleting="isDeleting"
            @update:carouselIndex="carouselIndex = $event"
            @duplicate="duplicateAerolinea"
            @changeStatus="changeAerolineaStatus"
            @generateReport="generateAerolineaReport"
            @archive="archiveAerolinea"
            @viewDetails="openDetailsModal"
            @openImageModal="openImageModal"
            @deleteAerolinea="deleteAerolinea"
            @cancelDelete="deleteDialog = false"
            @closeWithoutSaving="closeWithoutSaving"
            @continueEditing="continueEditing"
        />
    </AuthenticatedLayout>
</template>

<style>
/* Estilos para el dropdown del Select de PrimeVue */
.p-select-overlay {
    border: 2px solid #9ca3af !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

.p-select-option {
    border-bottom: 1px solid #e5e7eb !important;
    padding: 8px 12px !important;
    transition: background-color 0.2s ease !important;
}

.p-select-option:last-child {
    border-bottom: none !important;
}

.p-select-option:hover {
    background-color: #f3f4f6 !important;
}

.p-select-option[aria-selected="true"] {
    background-color: #dbeafe !important;
    color: #1e40af !important;
}

/* Estilos para el paginador */
.p-paginator-current {
    display: none !important;
}

@media (min-width: 640px) {
    .p-paginator-current {
        display: inline !important;
    }
    .p-paginator {
        justify-content: center !important;
    }
}

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
