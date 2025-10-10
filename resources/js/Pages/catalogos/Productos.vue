<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faFilter, faImages, faPencil, faPlus, faSignOut, faTrashCan, faXmark, faTags } from "@fortawesome/free-solid-svg-icons";
import axios from "axios";
axios.defaults.withCredentials = true;

const toast = useToast();

// 📊 Estados reactivos
const productos = ref([]);
const producto = ref({
    id: null,
    nombre: "",
    descripcion: "",
    precio: null,
    stock_actual: 0,
    stock_minimo: 1,
    categoria_id: null,
});

// 🖼️ Manejo de imágenes
const imagenPreviewList = ref([]);
const imagenFiles = ref([]);
const selectedImages = ref([]);
const removedImages = ref([]);

// 📝 Modal states
const dialog = ref(false);
const deleteDialog = ref(false);
const showImageDialog = ref(false);
const unsavedChangesDialog = ref(false);
const submitted = ref(false);
const hasUnsavedChanges = ref(false);
const originalProductData = ref(null);

// 📂 Datos de apoyo
const categorias = ref([]);
const selectedCategoria = ref(null);
const selectedEstado = ref(null);

// 🔍 Filtros
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    categoria: { value: null, matchMode: FilterMatchMode.EQUALS },
    estado: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const estadosOptions = ref([
    { label: 'Disponible', value: 'DISPONIBLE' },
    { label: 'Stock Bajo', value: 'STOCK_BAJO' },
    { label: 'Agotado', value: 'AGOTADO' }
]);

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");
const carouselIndex = ref(0);
const fileInput = ref(null);

// 🎯 URLs y constantes
const url = "/api/productos";
const IMAGE_PATH = "/storage/";

// 🔍 Productos filtrados
const filteredProductos = computed(() => {
    let filtered = productos.value;

    // Filtro por búsqueda global
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(producto =>
            producto.nombre.toLowerCase().includes(searchTerm) ||
            (producto.descripcion && producto.descripcion.toLowerCase().includes(searchTerm)) ||
            (producto.categoria?.nombre && producto.categoria.nombre.toLowerCase().includes(searchTerm))
        );
    }

    // Filtro por categoría
    if (filters.value.categoria.value) {
        filtered = filtered.filter(producto =>
            producto.categoria_id == filters.value.categoria.value
        );
    }

    // Filtro por estado de stock
    if (filters.value.estado.value) {
        filtered = filtered.filter(producto => {
            const estado = getEstadoStock(producto);
            return estado.value === filters.value.estado.value;
        });
    }

    return filtered;
});

// 🎨 Función para determinar estado del stock
const getEstadoStock = (producto) => {
    if (producto.stock_actual <= 0) {
        return {
            label: 'Agotado',
            value: 'AGOTADO',
            class: 'bg-red-100 text-red-800'
        };
    } else if (producto.stock_actual <= producto.stock_minimo) {
        return {
            label: 'Stock Bajo',
            value: 'STOCK_BAJO',
            class: 'bg-yellow-100 text-yellow-800'
        };
    } else {
        return {
            label: 'Disponible',
            value: 'DISPONIBLE',
            class: 'bg-green-100 text-green-800'
        };
    }
};

// 👀 Watcher para detectar cambios en el modal
watch([producto, imagenPreviewList, removedImages], () => {
    if (originalProductData.value && dialog.value) {
        nextTick(() => {
            const current = {
                ...producto.value,
                imagenes_actuales: [...imagenPreviewList.value]
            };

            const hasChanges = JSON.stringify(current) !== JSON.stringify({
                ...originalProductData.value,
                imagenes_actuales: originalProductData.value.imagenes_originales
            }) || removedImages.value.length > 0;

            const isCreatingNew = !originalProductData.value.id;
            const hasAnyData = producto.value.nombre ||
                              producto.value.descripcion ||
                              producto.value.precio ||
                              producto.value.categoria_id ||
                              imagenPreviewList.value.length > 0;

            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true, flush: 'post' });

// 🔄 Función para resetear formulario
function resetForm() {
    producto.value = {
        id: null,
        nombre: "",
        descripcion: "",
        precio: null,
        stock_actual: 0,
        stock_minimo: 1,
        categoria_id: null,
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
}

// 📊 Cargar datos
onMounted(() => {
    fetchProductos();
    fetchCategorias();
});

const fetchProductos = async () => {
    try {
        const response = await axios.get(url);
        productos.value = (response.data.data || response.data || []).map((p) => ({
            ...p,
            imagenes: (p.imagenes || []).map((img) =>
                typeof img === "string" ? img : img.nombre
            ),
        })).sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA;
        });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los productos.",
            life: 4000
        });
    }
};

const fetchCategorias = async () => {
    try {
        const response = await axios.get("/api/categorias-productos");
        categorias.value = response.data.data || response.data || [];
    } catch {
        categorias.value = [];
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las categorías.",
            life: 4000
        });
    }
};

// 🔍 Funciones para manejar filtros
const onCategoriaFilterChange = () => {
    filters.value.categoria.value = selectedCategoria.value;
};

const onEstadoFilterChange = () => {
    filters.value.estado.value = selectedEstado.value;
};

const clearFilters = () => {
    selectedCategoria.value = null;
    selectedEstado.value = null;
    filters.value.global.value = null;
    filters.value.categoria.value = null;
    filters.value.estado.value = null;
    toast.add({
        severity: "info",
        summary: "Filtros limpiados",
        detail: "Se han removido todos los filtros aplicados.",
        life: 3000
    });
};

// 📝 CRUD Operations
const openNew = () => {
    resetForm();
    btnTitle.value = "Guardar";
    submitted.value = false;
    dialog.value = true;
    nextTick(() => {
        originalProductData.value = {
            ...producto.value,
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
};

const editProduct = (p) => {
    resetForm();
    submitted.value = false;
    producto.value = { ...p };
    imagenPreviewList.value = (p.imagenes || []).map(img => typeof img === "string" ? img : img.nombre);
    hasUnsavedChanges.value = false;
    btnTitle.value = "Actualizar";
    dialog.value = true;
    nextTick(() => {
        originalProductData.value = {
            ...producto.value,
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
};

const saveOrUpdate = async () => {
    submitted.value = true;

    // Validar nombre específicamente
    if (!producto.value.nombre || producto.value.nombre.length < 3 || producto.value.nombre.length > 100) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.",
            life: 4000
        });
        return;
    }

    // Validar descripción específicamente
    if (!producto.value.descripcion || producto.value.descripcion.length < 10 || producto.value.descripcion.length > 255) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.",
            life: 4000
        });
        return;
    }

    // Validar campos obligatorios
    if (!producto.value.precio || !producto.value.categoria_id || imagenPreviewList.value.length === 0) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos.",
            life: 4000
        });
        return;
    }

    // Validar precio
    if (producto.value.precio <= 0 || producto.value.precio > 999.99) {
        toast.add({
            severity: "warn",
            summary: "Verificar datos",
            detail: "Por favor revisa los valores ingresados y corrige cualquier inconsistencia.",
            life: 4000
        });
        return;
    }

    // Validar stock
    if (producto.value.stock_actual < 0 || producto.value.stock_minimo < 1) {
        toast.add({
            severity: "warn",
            summary: "Verificar datos",
            detail: "Por favor revisa los valores de stock y corrige cualquier inconsistencia.",
            life: 4000
        });
        return;
    }

    // Validar tamaño de imágenes
    const maxSize = 2 * 1024 * 1024; // 2MB
    const oversizedFiles = imagenFiles.value.filter(file => file.size > maxSize);
    if (oversizedFiles.length > 0) {
        toast.add({
            severity: "warn",
            summary: "Verificar archivos",
            detail: "Por favor revisa el tamaño de las imágenes seleccionadas.",
            life: 4000
        });
        return;
    }

    try {
        const formData = new FormData();
        formData.append("nombre", producto.value.nombre || "");
        formData.append("descripcion", producto.value.descripcion || "");
        formData.append("precio", producto.value.precio || "");
        formData.append("stock_actual", producto.value.stock_actual || "0");
        formData.append("stock_minimo", producto.value.stock_minimo || "1");
        formData.append("categoria_id", producto.value.categoria_id || "");

        // Agregar imágenes nuevas
        imagenFiles.value.forEach(f => formData.append("imagenes[]", f));

        // Agregar imágenes eliminadas
        removedImages.value.forEach(img => {
            const fileName = img.includes("/") ? img.split("/").pop() : img;
            formData.append("removed_images[]", fileName);
        });

        let response;
        if (!producto.value.id) {
            response = await axios.post(url, formData, {
                headers: {"Content-Type":"multipart/form-data"}
            });
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El producto ha sido creado correctamente.",
                life: 5000
            });
        } else {
            formData.append("_method","PUT");
            response = await axios.post(`${url}/${producto.value.id}`, formData, {
                headers: {"Content-Type":"multipart/form-data"}
            });
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El producto ha sido actualizado correctamente.",
                life: 5000
            });
        }

        await fetchProductos();
        dialog.value = false;
        hasUnsavedChanges.value = false;
        originalProductData.value = null;
        resetForm();
    } catch (err) {
        toast.add({
            severity: "warn",
            summary: "Error de validación",
            detail: "Por favor revisa los campos e intenta nuevamente.",
            life: 6000
        });
    }
};

const confirmDeleteProduct = (p) => {
    producto.value = { ...p };
    deleteDialog.value = true;
};

// 🚀 MEJORADO: Función para eliminar con mejor manejo de errores
const deleteProduct = async () => {
    try {
        await axios.delete(`${url}/${producto.value.id}`);
        await fetchProductos();
        deleteDialog.value = false;
        toast.add({
            severity: "success",
            summary: "¡Eliminado!",
            detail: "El producto ha sido eliminado correctamente.",
            life: 5000
        });
    } catch (err) {
        deleteDialog.value = false;

        // 🎯 Manejo específico de errores 422 - Restricciones de integridad
        if (err.response?.status === 422) {
            const errorData = err.response.data;
            let mensaje = errorData.error || "El producto está siendo utilizado en el sistema.";

            // Si hay detalles específicos, mostrarlos en formato legible
            if (errorData.details && Array.isArray(errorData.details)) {
                mensaje += "\n\n📋 Detalles:\n• " + errorData.details.join("\n• ");
            }

            toast.add({
                severity: "warn",
                summary: "❌ No se puede eliminar",
                detail: mensaje,
                life: 10000
            });
        }
        // 🎯 Manejo de errores 404 - Producto no encontrado
        else if (err.response?.status === 404) {
            toast.add({
                severity: "error",
                summary: "Producto no encontrado",
                detail: "El producto que intentas eliminar no existe o ya fue eliminado.",
                life: 5000
            });
            // Recargar la lista para reflejar el estado actual
            await fetchProductos();
        }
        // 🎯 Manejo de errores 500 - Error del servidor
        else if (err.response?.status === 500) {
            toast.add({
                severity: "error",
                summary: "Error del servidor",
                detail: "Ocurrió un error interno. Por favor, contacta al administrador.",
                life: 6000
            });
        }
        // 🎯 Otros errores
        else {
            const errorMsg = err.response?.data?.error || err.message || "Error desconocido";
            toast.add({
                severity: "error",
                summary: "Error",
                detail: `No se pudo eliminar el producto: ${errorMsg}`,
                life: 6000
            });
        }
    }
};

// 🚪 Funciones para cerrar modales
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
    originalProductData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// 🖼️ Manejo de imágenes
const onImageSelect = (event) => {
    const files = event.target ? event.target.files : event.files;
    const maxSize = 2 * 1024 * 1024; // 2MB en bytes

    for (const file of files) {
        if (file instanceof File) {
            // Validar tamaño del archivo
            if (file.size > maxSize) {
                toast.add({
                    severity: "warn",
                    summary: "Archivo no válido",
                    detail: "Por favor selecciona archivos que cumplan con los requisitos de tamaño (máximo 2MB).",
                    life: 5000
                });
                continue;
            }

            // Validar tipo de archivo
            if (!file.type.startsWith('image/')) {
                toast.add({
                    severity: "warn",
                    summary: "Formato no válido",
                    detail: "Por favor selecciona únicamente archivos de imagen válidos.",
                    life: 4000
                });
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
        imagenPreviewList.value.splice(index,1);
        imagenFiles.value.splice(index,1);
    } else {
        removedImages.value.push(removed);
        imagenPreviewList.value.splice(index,1);
    }
};

const viewImages = (imagenesProducto) => {
    selectedImages.value = (imagenesProducto||[]).map(img=>IMAGE_PATH+(typeof img==="string"?img:img.nombre));
    showImageDialog.value = true;
};

// ✅ Validaciones en tiempo real
const validateNombre = () => {
    if (producto.value.nombre && producto.value.nombre.length > 100) {
        producto.value.nombre = producto.value.nombre.substring(0, 100);
    }
};

const validateDescripcion = () => {
    if (producto.value.descripcion && producto.value.descripcion.length > 255) {
        producto.value.descripcion = producto.value.descripcion.substring(0, 255);
    }
};
</script>

<template>
    <Head title="Productos" />
    <AuthenticatedLayout>
        <Toast class="z-[9999]" />
        <div class="py-4 sm:py-6 px-4 sm:px-7 mt-6 sm:mt-10 mx-auto bg-red-50 shadow-md rounded-lg h-screen-full">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-4 gap-4">
                <h3 class="text-lg sm:text-2xl text-blue-600 font-bold">Catálogo de Productos</h3>
                <div class="flex flex-col sm:flex-row items-center gap-2 w-full lg:w-auto lg:justify-end">
                    <Link :href="route('catProductos')"
                         class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300">
                        <FontAwesomeIcon :icon="faTags" class="h-4"/>
                        <span>&nbsp;Gestionar categorías</span>
                    </Link>
                    <button
                        class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                        @click="openNew">
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 text-white" />
                        <span>&nbsp;Agregar producto</span>
                    </button>
                </div>
            </div>

            <DataTable
                :value="filteredProductos"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} productos"
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
                                    <i class="pi pi-filter text-blue-600 text-sm"></i>
                                    <span>Filtros</span>
                                </h3>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                                    {{ filteredProductos.length }} resultado{{ filteredProductos.length !== 1 ? 's' : '' }}
                                </div>
                            </div>
                            <button class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md" @click="clearFilters">
                                <FontAwesomeIcon :icon="faFilter" class="h-4 w-4 text-white" />
                                <span>&nbsp;Limpiar filtros</span>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar productos..." class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-3">
                                <div>
                                    <Select v-model="selectedCategoria" :options="categorias" optionLabel="nombre" optionValue="id" placeholder="Categoría" class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;"
                                        @change="onCategoriaFilterChange" :clearable="true"
                                    />
                                </div>
                                <div>
                                    <Select v-model="selectedEstado" :options="estadosOptions" optionLabel="label" optionValue="value" placeholder="Estado" class="w-full h-9 text-sm" style="background-color: white; border-color: #93c5fd;"
                                        @change="onEstadoFilterChange" :clearable="true"
                                    />
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

                <Column field="descripcion" header="Descripción" class="w-48 min-w-48">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed whitespace-normal">
                            {{ slotProps.data.descripcion }}
                        </div>
                    </template>
                </Column>

                <Column field="categoria.nombre" header="Categoría" class="w-32 min-w-32">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.categoria?.nombre || 'Sin categoría' }}
                        </div>
                    </template>
                </Column>

                <Column field="stock_actual" header="Stock" class="w-24 min-w-24">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            <span class="font-medium">{{ slotProps.data.stock_actual }}</span>
                            <span class="text-gray-500">/ {{ slotProps.data.stock_minimo }} mín</span>
                        </div>
                    </template>
                </Column>

                <Column field="precio" header="Precio" class="w-24 min-w-24">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{ isNaN(Number(slotProps.data.precio)) ? "" : `$${Number(slotProps.data.precio).toFixed(2)}` }}
                        </div>
                    </template>
                </Column>

                <Column field="estado" header="Estado" class="w-28 min-w-28">
                    <template #body="slotProps">
                        <span :class="'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' + getEstadoStock(slotProps.data).class">
                            {{ getEstadoStock(slotProps.data).label }}
                        </span>
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
                                @click="editProduct(slotProps.data)">
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
                                @click="confirmDeleteProduct(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-4 w-4 text-red-600" />
                                &nbsp;Eliminar
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- 📝 Modal de formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Producto'" :modal="true" :style="{ width: '500px' }" :closable="false">
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model.trim="producto.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="100"
                                :class="{'p-invalid': submitted && (!producto.nombre || producto.nombre.length < 3 || producto.nombre.length > 100)}"
                                class="flex-1"
                                @input="validateNombre"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="producto.nombre && producto.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres. Actual: {{ producto.nombre.length }}/3
                        </small>
                        <small class="text-orange-500 ml-28" v-if="producto.nombre && producto.nombre.length >= 90 && producto.nombre.length <= 100">
                            Caracteres restantes: {{ 100 - producto.nombre.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !producto.nombre">
                            El nombre es obligatorio.
                        </small>
                    </div>

                    <!-- Descripción -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label for="descripcion" class="w-24 flex items-center gap-1 mt-2">
                                Descripción: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Textarea
                                v-model.trim="producto.descripcion"
                                id="descripcion"
                                name="descripcion"
                                :maxlength="255"
                                rows="3"
                                :class="{'p-invalid': submitted && (!producto.descripcion || producto.descripcion.length < 10 || producto.descripcion.length > 255)}"
                                class="flex-1"
                                @input="validateDescripcion"
                                placeholder="Describe el producto..."
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="producto.descripcion && producto.descripcion.length < 10">
                            La descripción debe tener al menos 10 caracteres. Actual: {{ producto.descripcion.length }}/10
                        </small>
                        <small class="text-orange-500 ml-28" v-if="producto.descripcion && producto.descripcion.length >= 230 && producto.descripcion.length <= 255">
                            Caracteres restantes: {{ 255 - producto.descripcion.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !producto.descripcion">
                            La descripción es obligatoria.
                        </small>
                    </div>

                    <!-- Categoría -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="categoria_id" class="w-24 flex items-center gap-1">
                                Categoría: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Select
                                v-model="producto.categoria_id"
                                :options="categorias"
                                optionLabel="nombre"
                                optionValue="id"
                                id="categoria_id"
                                name="categoria_id"
                                class="flex-1"
                                placeholder="Selecciona una categoría"
                                :class="{'p-invalid': submitted && !producto.categoria_id}"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !producto.categoria_id">
                            La categoría es obligatoria.
                        </small>
                    </div>

                    <!-- Precio -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="precio" class="w-24 flex items-center gap-1">
                                Precio: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputNumber
                                v-model="producto.precio"
                                id="precio"
                                name="precio"
                                mode="currency"
                                currency="USD"
                                :locale="'en-US'"
                                :min="0.01"
                                :max="999.99"
                                :maxFractionDigits="2"
                                :minFractionDigits="2"
                                :class="{'p-invalid': submitted && (!producto.precio || producto.precio <= 0 || producto.precio > 999.99)}"
                                class="flex-1"
                                placeholder="$0.00"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && (producto.precio == null || producto.precio <= 0 || producto.precio > 999.99)">
                            El precio es obligatorio, debe ser mayor a 0 y menor o igual a 999.99.
                        </small>
                    </div>

                    <!-- Stock -->
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label for="stock_actual" class="block mb-2">Stock actual:</label>
                            <InputNumber
                                v-model="producto.stock_actual"
                                id="stock_actual"
                                name="stock_actual"
                                :min="0"
                                :max="9999"
                                :step="1"
                                showButtons
                                :useGrouping="false"
                                class="w-full"
                                placeholder="0"
                            />
                        </div>
                        <div class="flex-1">
                            <label for="stock_minimo" class="block mb-2">Stock mínimo:</label>
                            <InputNumber
                                v-model="producto.stock_minimo"
                                id="stock_minimo"
                                name="stock_minimo"
                                :min="1"
                                :max="100"
                                :step="1"
                                showButtons
                                :useGrouping="false"
                                class="w-full"
                                placeholder="1"
                            />
                        </div>
                    </div>

                    <!-- Imágenes -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="imagenes" class="w-24 flex items-center gap-1">
                                Imágenes: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <div class="flex-1">
                                <input
                                    type="file"
                                    id="imagenes"
                                    name="imagenes[]"
                                    accept="image/*"
                                    multiple
                                    @change="onImageSelect"
                                    class="hidden"
                                    ref="fileInput"
                                />
                                <button
                                    type="button"
                                    class="bg-white hover:bg-red-50 text-red-500 hover:text-red-600 border border-red-500 hover:border-red-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 outline-none focus:outline-none active:outline-none"
                                    @click="$refs.fileInput.click()"
                                >
                                    <FontAwesomeIcon :icon="faPlus" class="h-4" />
                                    <span>Subir imágenes</span>
                                </button>
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && imagenPreviewList.length === 0">
                            Las imágenes son obligatorias (al menos una).
                        </small>
                    </div>

                    <!-- Vista previa de imágenes -->
                    <div class="flex gap-4 flex-wrap mt-4 ml-28">
                        <div v-for="(img, index) in imagenPreviewList" :key="index" class="relative w-32 h-32">
                            <img
                                :src="img.startsWith('data:image') ? img : IMAGE_PATH + img"
                                alt="Vista previa"
                                class="w-full h-full object-cover rounded border"
                            />
                            <button
                                @click="removeImage(index)"
                                class="absolute top-2 right-2 bg-gray-600/80 hover:bg-gray-700/80 text-white font-bold py-1 px-2 rounded-full shadow"
                                style="transform: translate(50%, -50%)"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            type="button"
                            class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="hideDialog"
                        >
                            <FontAwesomeIcon :icon="faXmark" class="h-5 text-green-600" />
                            Cancelar
                        </button>
                        <button
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="saveOrUpdate"
                        >
                            <FontAwesomeIcon :icon="faCheck" class="h-5 text-white" />
                            {{ btnTitle }}
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- 🗑️ Modal de confirmación de eliminación -->
            <Dialog v-model:visible="deleteDialog" header="Eliminar producto" :modal="true" :style="{ width: '350px' }" :closable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar el producto: <b>{{ producto.nombre }}</b>?</span>
                        <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            type="button"
                            class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="deleteDialog = false"
                        >
                            <FontAwesomeIcon :icon="faXmark" class="h-5" />
                            <span>No</span>
                        </button>
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="deleteProduct"
                        >
                            <FontAwesomeIcon :icon="faCheck" class="h-5" />
                            <span>Sí</span>
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- ⚠️ Modal de cambios sin guardar -->
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
                        <button
                            type="button"
                            class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="continueEditing"
                        >
                            <FontAwesomeIcon :icon="faPencil" class="h-4" />
                            <span>Continuar</span>
                        </button>
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="closeDialogWithoutSaving"
                        >
                            <FontAwesomeIcon :icon="faSignOut" class="h-4" />
                            <span>Salir sin guardar</span>
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- 🖼️ Modal de visualización de imágenes -->
            <Dialog v-model:visible="showImageDialog" header="Imágenes del producto" :modal="true" :closable="false" :style="{ width: '700px' }">
                <div v-if="selectedImages.length" class="flex flex-col items-center justify-center">
                    <Carousel
                        :value="selectedImages"
                        :numVisible="1"
                        :numScroll="1"
                        :circular="true"
                        v-model:page="carouselIndex"
                        class="w-full"
                        :showIndicators="selectedImages.length > 1"
                        :showNavigators="selectedImages.length > 1"
                        style="max-width: 610px"
                    >
                        <template #item="slotProps">
                            <div class="flex justify-center items-center w-full h-96">
                                <img
                                    :src="slotProps.data"
                                    alt="Imagen producto"
                                    class="w-auto h-full max-h-96 object-contain rounded shadow"
                                />
                            </div>
                        </template>
                    </Carousel>
                </div>
                <div v-else class="text-center text-gray-500 py-8">
                    No hay imágenes para este producto.
                </div>
                <template #footer>
                    <div class="flex justify-center w-full">
                        <button
                            type="button"
                            class="bg-white hover:bg-green-100 text-green-600 border border-green-600 px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="showImageDialog = false"
                        >
                            <FontAwesomeIcon :icon="faXmark" class="h-5" />
                            <span>Cerrar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
