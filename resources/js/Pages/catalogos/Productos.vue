<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faFilter, faHandPointUp, faImages, faListDots, faMagnifyingGlass, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark, faTags } from "@fortawesome/free-solid-svg-icons";
import ProductoModals from "./Components/ProductoComponents/Modales.vue";
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
    stock_actual: null,
    stock_minimo: null,
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

// Variables para modales de productos
const moreActionsDialog = ref(false);
const showImageCarouselDialog = ref(false);
const selectedProduct = ref(null);
const updateStockDialog = ref(false);

// Variables de loading
const isNavigatingToCategorias = ref(false);
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const isUploadingImages = ref(false);
const isOpeningGallery = ref(false);
const isLoading = ref(false);
const isLoadingTable = ref(true);

// 📂 Datos de apoyo
const categorias = ref([]);
const selectedCategoria = ref("");
const selectedEstado = ref("");

// 📋 Estados para modal de nueva categoría
const nuevaCategoriaDialog = ref(false);
const nuevaCategoria = ref({
    nombre: ""
});
const isCreatingCategory = ref(false);

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
const IMAGE_PATH = "/storage/productos/";

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

// Computed para verificar si se puede agregar más imágenes
const canAddMoreImages = computed(() => {
    return imagenPreviewList.value.length < 5;
});

// Computed para categorías con texto truncado (para móviles)
const categoriasConTextoTruncado = computed(() => {
    return categorias.value.map(categoria => ({
        ...categoria,
        nombreTruncado: categoria.nombre.length > 20
            ? categoria.nombre.substring(0, 17) + '...'
            : categoria.nombre
    }));
});

// Computed para el texto del botón de imágenes
const imageButtonText = computed(() => {
    if (isUploadingImages.value) return 'Cargando...';
    if (isOpeningGallery.value) return 'Abriendo...';
    if (!canAddMoreImages.value) return 'Límite alcanzado';
    return 'Seleccionar';
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
        stock_actual: null,
        stock_minimo: null,
        categoria_id: null,
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
}

// 🎨 Función para forzar truncado en selects
const forceSelectTruncation = () => {
    nextTick(() => {
        setTimeout(() => {
            const selects = document.querySelectorAll('.p-select');
            selects.forEach(select => {
                const label = select.querySelector('.p-select-label, .p-placeholder');
                if (label) {
                    label.style.overflow = 'hidden';
                    label.style.textOverflow = 'ellipsis';
                    label.style.whiteSpace = 'nowrap';
                    label.style.maxWidth = 'calc(100% - 2.5rem)';
                    label.style.display = 'block';
                }
            });
        }, 100);
    });
};

// 📊 Cargar datos
onMounted(() => {
    fetchProductosWithToasts();
    fetchCategorias();
    forceSelectTruncation();
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

const fetchProductosWithToasts = async () => {
    isLoadingTable.value = true;

    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando productos...",
        life: 2000
    });

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

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Productos cargados",
            life: 2000
        });

    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los productos.",
            life: 4000
        });
    } finally {
        isLoadingTable.value = false;
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
    filters.value.categoria.value = selectedCategoria.value === "" ? null : selectedCategoria.value;
};

const onEstadoFilterChange = () => {
    filters.value.estado.value = selectedEstado.value === "" ? null : selectedEstado.value;
};

const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        selectedCategoria.value = "";
        selectedEstado.value = "";
        filters.value.global.value = null;
        filters.value.categoria.value = null;
        filters.value.estado.value = null;

        toast.add({
            severity: "success",
            summary: "Filtros limpiados",
            life: 2000
        });
    } finally {
        isClearingFilters.value = false;
    }
};

// Función para manejar el clic en el enlace de categorías
const handleCategoriasClick = () => {
    isNavigatingToCategorias.value = true;
    // El estado de loading se resetea automáticamente cuando se cambia de página
};

// 📋 Funciones para el modal de nueva categoría
const crearNuevaCategoria = async () => {
    if (!nuevaCategoria.value.nombre || nuevaCategoria.value.nombre.length < 3) {
        toast.add({
            severity: "warn",
            summary: "Validación",
            detail: "El nombre de la categoría debe tener al menos 3 caracteres.",
            life: 4000
        });
        return;
    }

    if (nuevaCategoria.value.nombre.length > 30) {
        toast.add({
            severity: "warn",
            summary: "Validación",
            detail: "El nombre de la categoría no puede exceder 30 caracteres.",
            life: 4000
        });
        return;
    }

    isCreatingCategory.value = true;
    try {
        const response = await axios.post(`/api/categorias-productos`, {
            nombre: nuevaCategoria.value.nombre
        });

        toast.add({
            severity: "success",
            summary: "¡Éxito!",
            detail: "La categoría ha sido creada correctamente.",
            life: 5000
        });

        // Guardar el ID de la nueva categoría creada
        const responseData = response.data;
        const nuevaCategoriaCreada = responseData.categoria || responseData.data || responseData;
        const nuevaCategoriaId = nuevaCategoriaCreada?.id;



        // Recargar categorías
        await fetchCategorias();

        // Forzar truncado después de recargar categorías
        forceSelectTruncation();

        // Seleccionar automáticamente la nueva categoría creada después de recargar
        if (nuevaCategoriaId) {
            // Usar nextTick para asegurar que el DOM se actualice
            await nextTick();            // Verificar que la categoría existe en la lista
            const categoriaExiste = categorias.value.find(cat => cat.id === nuevaCategoriaId);

            if (categoriaExiste) {
                producto.value.categoria_id = nuevaCategoriaId;
            }
        }

        // Cerrar modal y limpiar formulario
        cerrarModalCategoria();

    } catch (error) {
        console.error('Error al crear categoría:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al crear la categoría',
            life: 4000
        });
    } finally {
        isCreatingCategory.value = false;
    }
};

// Función para manejar el input del nombre de categoría (conversión a mayúsculas)
const onNombreCategoriaInput = (event) => {
    nuevaCategoria.value.nombre = event.target.value.toUpperCase();
};

const cerrarModalCategoria = () => {
    nuevaCategoriaDialog.value = false;
    nuevaCategoria.value.nombre = "";
};

// 🔍 Función para truncar texto
const truncateText = (text, maxLength = 10) => {
    if (!text) return text;
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

// 🔍 Categorías con nombres truncados para el select
const categoriasConTruncado = computed(() => {
    return categorias.value.map(categoria => ({
        ...categoria,
        nombreTruncado: truncateText(categoria.nombre, 20),
        nombre: categoria.nombre // Mantenemos el nombre completo para búsquedas
    }));
});

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

    // Verificar nombres duplicados //agregado
    if (checkNombreDuplicado()) {
        toast.add({
            severity: "warn",
            summary: "Nombre duplicado",
            detail: "Ya existe un producto con este nombre. Por favor elige un nombre diferente.",
            life: 5000
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

    // Validar campos de stock solo para productos nuevos
    if (!producto.value.id && (producto.value.stock_actual === null || producto.value.stock_minimo === null)) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Para productos nuevos, el stock actual y mínimo son obligatorios.",
            life: 4000
        });
        return;
    }

    // Validar precio
    if (producto.value.precio <= 0 || producto.value.precio > 9999.99) {
        toast.add({
            severity: "warn",
            summary: "Verificar datos",
            detail: "Por favor revisa los valores ingresados y corrige cualquier inconsistencia.",
            life: 4000
        });
        return;
    }

    // Validar stock solo para productos nuevos
    if (!producto.value.id &&
        ((producto.value.stock_actual !== null && producto.value.stock_actual < 0) ||
         (producto.value.stock_minimo !== null && producto.value.stock_minimo < 1))) {
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
        isLoading.value = true;

        const formData = new FormData();
        formData.append("nombre", producto.value.nombre || "");
        formData.append("descripcion", producto.value.descripcion || "");
        formData.append("precio", producto.value.precio || "");
        formData.append("categoria_id", producto.value.categoria_id || "");

        // Solo incluir campos de stock para productos nuevos
        if (!producto.value.id) {
            formData.append("stock_actual", producto.value.stock_actual || "0");
            formData.append("stock_minimo", producto.value.stock_minimo || "1");
        }

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
        console.error('Error al guardar producto:', err);

        // Manejar errores de validación específicos
        if (err.response && err.response.status === 422) {
            const errors = err.response.data.errors;
            let errorMessage = "Por favor revisa los siguientes errores:";

            if (errors) {
                const errorMessages = [];
                if (errors.nombre) {
                    errors.nombre.forEach(msg => {
                        if (msg.includes('Ya existe un producto')) {
                            errorMessages.push("Ya existe un producto con este nombre.");
                        } else if (msg.includes('solo puede contener')) {
                            errorMessages.push("El nombre solo puede contener letras mayúsculas, acentos y espacios.");
                        } else {
                            errorMessages.push(msg);
                        }
                    });
                }
                if (errors.descripcion) {
                    errors.descripcion.forEach(msg => {
                        if (msg.includes('solo puede contener')) {
                            errorMessages.push("La descripción solo puede contener letras mayúsculas, acentos, espacios y signos de puntuación básicos.");
                        } else {
                            errorMessages.push(msg);
                        }
                    });
                }
                // Agregar otros errores si existen
                Object.keys(errors).forEach(field => {
                    if (field !== 'nombre' && field !== 'descripcion') {
                        errors[field].forEach(msg => errorMessages.push(msg));
                    }
                });

                if (errorMessages.length > 0) {
                    errorMessage = errorMessages.join(' ');
                }
            }

            toast.add({
                severity: "error",
                summary: "Error de validación",
                detail: errorMessage,
                life: 8000
            });
        } else {
            // Error genérico
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Ocurrió un error inesperado. Por favor intenta nuevamente.",
                life: 6000
            });
        }
    } finally {
        isLoading.value = false;
    }
};

const confirmDeleteProduct = (p) => {
    producto.value = { ...p };
    deleteDialog.value = true;
};

// 🚀 MEJORADO: Función para eliminar con mejor manejo de errores
const deleteProduct = async () => {
    isDeleting.value = true;

    try {
        const response = await axios.delete(`${url}/${producto.value.id}`);

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

            let mensaje = errorData.error || errorData.message || "El producto está protegido contra eliminación.";

            // Mostrar detalles específicos con mejor formato
            if (errorData.details && Array.isArray(errorData.details)) {
                mensaje += "\n\n Restricciones de seguridad:\n• " + errorData.details.join("\n• ");

                // Agregar instrucciones de limpieza
                mensaje += "\n\n Para eliminar este producto debe:";
                mensaje += "\n• Eliminar primero todas las ventas asociadas";
                mensaje += "\n• Limpiar todo el historial de movimientos de inventario";
                mensaje += "\n• Luego podrá eliminar el producto de forma segura";
            }

            toast.add({
                severity: "error",
                summary: "Producto Protegido",
                detail: mensaje,
                life: 15000
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


    } finally {
        isDeleting.value = false;
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

// Funciones para manejar los modales de productos
const openMoreActionsModal = (productData) => {
    selectedProduct.value = productData;
    moreActionsDialog.value = true;
};

const handleUpdateStock = (product) => {
    moreActionsDialog.value = false;
    selectedProduct.value = product;
    updateStockDialog.value = true;
};

const handleViewDetails = (product) => {
    moreActionsDialog.value = false;
    selectedProduct.value = product;
    showImageDialog.value = true;
};

const handleStockUpdated = async (responseData) => {
    // El backend devuelve { data: { producto: {...}, movimiento: {...} } }
    const updatedProduct = responseData.data?.producto || responseData.producto || responseData;

    if (!updatedProduct || !updatedProduct.id) {
        console.error('No se pudo obtener el producto actualizado');
        return;
    }

    // Actualizar el producto en la lista principal (reactivo)
    const index = productos.value.findIndex(p => p.id === updatedProduct.id);
    if (index !== -1) {
        // Mantener las relaciones existentes y solo actualizar campos específicos
        productos.value[index] = {
            ...productos.value[index],
            stock_actual: updatedProduct.stock_actual,
            stock_minimo: updatedProduct.stock_minimo,
            updated_at: updatedProduct.updated_at
        };
    }

    // Actualizar selectedProduct si es el mismo producto que se está viendo
    if (selectedProduct.value && selectedProduct.value.id === updatedProduct.id) {
        selectedProduct.value = {
            ...selectedProduct.value,
            stock_actual: updatedProduct.stock_actual,
            stock_minimo: updatedProduct.stock_minimo,
            updated_at: updatedProduct.updated_at
        };
    }

    // Cerrar el modal de actualización de stock
    updateStockDialog.value = false;

    // Mostrar toast de confirmación
    toast.add({
        severity: "success",
        summary: "Stock actualizado",
        detail: `El stock de "${updatedProduct.nombre}" se ha actualizado correctamente.`,
        life: 3000
    });

    // Forzar reactividad en caso de que Vue no detecte el cambio
    await nextTick();
};

const openImageModal = (index) => {
    if (selectedProduct.value && selectedProduct.value.imagenes && selectedProduct.value.imagenes.length > 0) {
        selectedImages.value = selectedProduct.value.imagenes.map(img => {
            const imageName = typeof img === "string" ? img : img.nombre;
            return IMAGE_PATH + imageName;
        });

        const validIndex = Math.max(0, Math.min(index, selectedImages.value.length - 1));
        carouselIndex.value = validIndex;

        setTimeout(() => {
            showImageCarouselDialog.value = true;
        }, 50);
    } else {
        toast.add({
            severity: "warn",
            summary: "Sin imágenes",
            detail: "No hay imágenes disponibles para mostrar.",
            life: 5000
        });
    }
};

// Función para manejar el clic en la fila
const onRowClick = (event) => {
    // Verificar si el clic fue en un botón para evitar abrir el modal
    const target = event.originalEvent.target;
    const isButton = target.closest('button');

    if (!isButton) {
        selectedProduct.value = event.data;
        showImageDialog.value = true;
    }
};

// Variable reactiva para el ancho de ventana (si no existe)
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
    if (windowWidth.value < 640) {
        return { width: '95vw', maxWidth: '380px' };
    } else if (windowWidth.value < 768) {
        return { width: '400px' };
    } else {
        return { width: '450px' };
    }
});

// 🖼️ Manejo de imágenes
// Función para manejar el clic del botón de subir imágenes
const handleImageUploadClick = async () => {
    isOpeningGallery.value = true;

    try {
        // Simular una pequeña demora para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 100));
        fileInput.value.click();
    } finally {
        // Restablecer el estado después de un breve delay
        setTimeout(() => {
            isOpeningGallery.value = false;
        }, 200);
    }
};

const onImageSelect = async (event) => {
    const files = event.target ? event.target.files : event.files;
    const maxSize = 2 * 1024 * 1024; // 2MB en bytes

    if (!files || files.length === 0) return;

    // Verificar límite de imágenes
    const imagenesTotales = imagenPreviewList.value.length + files.length;
    if (imagenesTotales > 5) {
        toast.add({
            severity: "warn",
            summary: "Límite de imágenes",
            detail: `Solo puedes subir máximo 5 imágenes. Actualmente tienes ${imagenPreviewList.value.length} y estás intentando agregar ${files.length}.`,
            life: 5000
        });
        return;
    }

    isUploadingImages.value = true;

    try {
        for (const file of files) {
            if (file instanceof File) {
                // Verificar límite individual antes de procesar cada archivo
                if (imagenPreviewList.value.length >= 5) {
                    toast.add({
                        severity: "warn",
                        summary: "Límite alcanzado",
                        detail: "Has alcanzado el límite máximo de 5 imágenes.",
                        life: 4000
                    });
                    break;
                }

                // Validar tamaño del archivo
                if (file.size > maxSize) {
                    toast.add({
                        severity: "warn",
                        summary: "Archivo demasiado grande",
                        detail: `El archivo "${file.name}" excede el tamaño máximo de 2MB.`,
                        life: 5000
                    });
                    continue;
                }

                // Validar tipo de archivo
                if (!file.type.startsWith('image/')) {
                    toast.add({
                        severity: "warn",
                        summary: "Formato no válido",
                        detail: `El archivo "${file.name}" no es una imagen válida.`,
                        life: 4000
                    });
                    continue;
                }

                // Simular procesamiento
                await new Promise(resolve => setTimeout(resolve, 100));

                imagenFiles.value.push(file);
                const reader = new FileReader();
                reader.onload = (e) => imagenPreviewList.value.push(e.target.result);
                reader.readAsDataURL(file);
            }
        }

        // Mostrar mensaje de éxito
        if (files.length > 0) {
            toast.add({
                severity: "success",
                summary: "Imágenes cargadas",
                detail: `Se han cargado ${files.length} imagen(es) correctamente.`,
                life: 3000
            });
        }

    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Ocurrió un error al procesar las imágenes.",
            life: 4000
        });
    } finally {
        isUploadingImages.value = false;
        // Limpiar el input para permitir seleccionar los mismos archivos nuevamente
        if (event.target) {
            event.target.value = '';
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
    carouselIndex.value = 0;
    showImageCarouselDialog.value = true;
};

// ✅ Validaciones en tiempo real
const validateNombre = () => {
    if (producto.value.nombre) {
        // Convertir a mayúsculas
        producto.value.nombre = producto.value.nombre.toUpperCase();

        // Filtrar solo caracteres válidos: letras con acentos, Ñ y espacios
        producto.value.nombre = producto.value.nombre.replace(/[^A-ZÁÉÍÓÚÑÜ\s]/g, '');

        // Limitar longitud
        if (producto.value.nombre.length > 100) {
            producto.value.nombre = producto.value.nombre.substring(0, 100);
        }
    }
};

// Función para manejar teclas en el campo nombre
const onNombreKeyDown = (event) => {
    const key = event.key;

    // Permitir teclas de control (Backspace, Tab, Enter, Delete, flechas, etc.)
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].includes(event.keyCode) ||
        (event.ctrlKey && [65, 67, 86, 88, 90, 89].includes(event.keyCode))) {
        return;
    }

    // Permitir solo letras (incluye acentuadas), espacios y Ñ
    if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(key)) {
        event.preventDefault();
        return;
    }
};

// Función para manejar paste en el campo nombre
const onNombrePaste = (event) => {
    event.preventDefault();

    const paste = (event.clipboardData || window.clipboardData).getData('text');

    if (!paste) return;

    // Convertir a mayúsculas y filtrar solo caracteres válidos
    let cleanText = paste.toUpperCase().replace(/[^A-ZÁÉÍÓÚÑÜ\s]/g, '');

    // Obtener texto actual y posición del cursor
    const input = event.target;
    const currentValue = input.value || '';
    const cursorStart = input.selectionStart || 0;
    const cursorEnd = input.selectionEnd || 0;

    // Construir nuevo valor
    const beforeCursor = currentValue.substring(0, cursorStart);
    const afterCursor = currentValue.substring(cursorEnd);
    let newValue = beforeCursor + cleanText + afterCursor;

    // Limitar longitud
    if (newValue.length > 100) {
        newValue = newValue.substring(0, 100);
    }

    // Actualizar el valor
    producto.value.nombre = newValue;

    // Restaurar posición del cursor
    setTimeout(() => {
        const newCursorPosition = Math.min(cursorStart + cleanText.length, newValue.length);
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    }, 0);
};

// Función para verificar si el nombre ya existe//agregado
const checkNombreDuplicado = () => {
    if (!producto.value.nombre || producto.value.nombre.length < 3) return false;

    const nombreNormalizado = producto.value.nombre.trim().toUpperCase();

    return productos.value.some(p => {
        // Excluir el producto actual si estamos editando
        if (producto.value.id && p.id === producto.value.id) return false;

        return p.nombre.trim().toUpperCase() === nombreNormalizado;
    });
};

// Función para manejar teclas en el campo descripción
const onDescripcionKeyDown = (event) => {
    const key = event.key;

    // Permitir teclas de control (Backspace, Tab, Enter, Delete, flechas, etc.)
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].includes(event.keyCode) ||
        (event.ctrlKey && [65, 67, 86, 88, 90, 89].includes(event.keyCode))) {
        return;
    }

    // Permitir letras (incluye acentuadas), números, espacios, Ñ y caracteres especiales
    if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,;:!?¡¿()\/\-_@#$%&*+=\[\]{}|\\~`"']$/.test(key)) {
        event.preventDefault();
        return;
    }
};

// Función para manejar paste en el campo descripción
const onDescripcionPaste = (event) => {
    event.preventDefault();

    const paste = (event.clipboardData || window.clipboardData).getData('text');

    if (!paste) return;

    // Convertir a mayúsculas y filtrar solo caracteres válidos
    let cleanText = paste.toUpperCase().replace(/[^A-ZÁÉÍÓÚÑÜ0-9\s.,;:!?¡¿()\/\-_@#$%&*+=\[\]{}|\\~`"']/g, '');

    // Obtener texto actual y posición del cursor
    const input = event.target;
    const currentValue = input.value || '';
    const cursorStart = input.selectionStart || 0;
    const cursorEnd = input.selectionEnd || 0;

    // Construir nuevo valor
    const beforeCursor = currentValue.substring(0, cursorStart);
    const afterCursor = currentValue.substring(cursorEnd);
    let newValue = beforeCursor + cleanText + afterCursor;

    // Limitar longitud
    if (newValue.length > 255) {
        newValue = newValue.substring(0, 255);
    }

    // Actualizar el valor
    producto.value.descripcion = newValue;

    // Restaurar posición del cursor
    setTimeout(() => {
        const newCursorPosition = Math.min(cursorStart + cleanText.length, newValue.length);
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    }, 0);
};

const validateDescripcion = () => {
    if (producto.value.descripcion) {
        // Convertir a mayúsculas
        producto.value.descripcion = producto.value.descripcion.toUpperCase();

        // Filtrar solo caracteres válidos: letras con acentos, números, espacios y caracteres especiales
        producto.value.descripcion = producto.value.descripcion.replace(/[^A-ZÁÉÍÓÚÑÜ0-9\s.,;:!?¡¿()\/\-_@#$%&*+=\[\]{}|\\~`"']/g, '');

        // Limitar longitud
        if (producto.value.descripcion.length > 255) {
            producto.value.descripcion = producto.value.descripcion.substring(0, 255);
        }
    }
};

// Función para prevenir teclas no válidas en campo de precio
const onPriceKeyDown = (event) => {
    const currentValue = event.target.value;
    const key = event.key;

    // Permitir teclas de control
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].includes(event.keyCode) ||
        (event.ctrlKey && [65, 67, 86, 88].includes(event.keyCode))) {
        return;
    }

    // Permitir números y punto decimal
    if (!/[0-9.]/.test(key)) {
        event.preventDefault();
        return;
    }

    // No permitir más de un punto decimal
    if (key === '.' && currentValue.includes('.')) {
        event.preventDefault();
        return;
    }

    // Validar formato de precio: máximo 3 dígitos antes del punto y 2 después
    if (key !== '.') {
        const parts = currentValue.split('.');
        if (parts[0].length >= 3 && !currentValue.includes('.')) {
            event.preventDefault();
            return;
        }
        if (parts.length > 1 && parts[1].length >= 2) {
            event.preventDefault();
            return;
        }
    }

    // Validar que no exceda 9999.99
    const newValue = currentValue + key;
    const numValue = parseFloat(newValue);
    if (numValue > 9999.99) {
        event.preventDefault();
        return;
    }
};

// Función para manejar paste en campo de precio
const onPricePaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');

    // Filtrar solo números y punto decimal, removiendo cualquier otro carácter
    let numericValue = paste.replace(/[^\d.]/g, '');

    // Si no hay contenido numérico válido, no hacer nada
    if (!numericValue || numericValue === '.') return;

    // Verificar que solo tenga un punto decimal
    const dotCount = (numericValue.match(/\./g) || []).length;
    if (dotCount > 1) {
        // Si hay múltiples puntos, mantener solo el primero
        const firstDotIndex = numericValue.indexOf('.');
        numericValue = numericValue.substring(0, firstDotIndex + 1) + numericValue.substring(firstDotIndex + 1).replace(/\./g, '');
    }

    // Convertir a número y validar
    const num = parseFloat(numericValue);
    if (isNaN(num)) return;

    // Limitar a máximo 9999.99
    const limitedNum = Math.min(num, 9999.99);

    // Formatear a máximo 2 decimales
    const formattedValue = limitedNum.toFixed(2);

    // Actualizar el modelo de Vue
    producto.value.precio = formattedValue;

    // También actualizar el campo input para sincronización
    event.target.value = formattedValue;
};

// Función para prevenir teclas no válidas en campos de stock
const onStockKeyDown = (event) => {
    const currentValue = event.target.value;
    const key = event.key;

    // Permitir teclas de control (Backspace, Tab, Escape, Enter, Delete, Home, End, Arrow keys)
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

// Función para limpiar valor en caso de paste en campos de stock
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

// Función para prevenir teclas no válidas en campo de stock mínimo
const onStockMinimoKeyDown = (event) => {
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

    // Limitar a máximo 3 dígitos y máximo 100, mínimo 1
    if (newValue.length > 3 || num > 100) {
        event.preventDefault();
        return;
    }
};

// Función para limpiar valor en caso de paste en stock mínimo
const onStockMinimoPaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');
    const numericValue = paste.replace(/[^0-9]/g, '');

    if (numericValue) {
        let num = parseInt(numericValue);
        if (num > 100) {
            num = 100;
        }
        if (num < 1) {
            num = 1;
        }
        event.target.value = num.toString();
        // Triggear el evento input para actualizar v-model
        event.target.dispatchEvent(new Event('input', { bubbles: true }));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Productos" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center gap-4 p-4">
                    <div class="w-full">
                        <h1 class="text-3xl font-bold text-blue-600 mb-2 text-center sm:text-start">Catálogo de Productos</h1>
                        <p class="text-gray-600 text-center sm:text-start">Gestión completa del inventario de productos</p>
                    </div>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                    <Link
                        :href="route('catProductos')"
                        @click="handleCategoriasClick"
                        :class="{'opacity-50 cursor-not-allowed': isNavigatingToCategorias}"
                        class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2">
                        <FontAwesomeIcon
                            :icon="isNavigatingToCategorias ? faSpinner : faTags"
                            :class="{'animate-spin': isNavigatingToCategorias, 'h-4': true}"
                        />
                        <span class="block sm:hidden">{{ isNavigatingToCategorias ? 'Cargando...' : 'Categorías' }}</span>
                        <span class="hidden sm:block">{{ isNavigatingToCategorias ? 'Cargando...' : 'Categorías' }}</span>
                    </Link>
                    <button
                        class="bg-red-500 border flex border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                        @click="openNew">
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" />
                        <span>Agregar</span>
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
                responsiveLayout="scroll"
                @row-click="onRowClick"
                :pt="{
                    root: { class: 'text-sm' },
                    wrapper: { class: 'text-sm' },
                    table: { class: 'text-sm' },
                    thead: { class: 'text-sm' },
                    headerRow: { class: 'text-sm' },
                    headerCell: { class: 'text-sm font-medium py-3 px-2' },
                    tbody: { class: 'text-sm' },
                    bodyRow: { class: 'h-20 text-sm cursor-pointer hover:bg-blue-50 transition-colors duration-200' },
                    bodyCell: { class: 'py-3 px-2 text-sm' },
                    paginator: { class: 'text-xs sm:text-sm' },
                    paginatorWrapper: { class: 'flex flex-wrap justify-center sm:justify-between items-center gap-2 p-2' }
                }"
            >
                <template #header>
                    <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4">
                        <div class="flex flex-col sm:flex-row items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base font-medium text-gray-800 flex items-center gap-2">
                                    <i class="pi pi-filter text-blue-600 text-sm"></i><span>Filtros</span>
                                </h3>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                                    {{ filteredProductos.length }} resultado{{ filteredProductos.length !== 1 ? 's' : '' }}
                                </div>
                                <button
                                    class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md flex sm:hidden disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                                    @click="clearFilters"
                                    :disabled="isClearingFilters">
                                    <FontAwesomeIcon
                                        v-if="isClearingFilters"
                                        :icon="faSpinner"
                                        class="animate-spin h-3 w-3"
                                    />
                                    <span>{{ isClearingFilters ? 'Limp...' : 'Limpiar' }}</span>
                                </button>
                            </div>
                            <button
                                class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md hidden sm:flex disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                                @click="clearFilters"
                                :disabled="isClearingFilters">
                                <FontAwesomeIcon
                                    v-if="isClearingFilters"
                                    :icon="faSpinner"
                                    class="animate-spin h-3 w-3"
                                />
                                <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div class="relative">
                                <FontAwesomeIcon :icon="faMagnifyingGlass" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <InputText v-model="filters['global'].value" placeholder="Buscar productos..." class="w-full h-9 text-sm rounded-md pl-10" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-3">
                                <div>
                                    <select
                                        v-model="selectedCategoria"
                                        @change="onCategoriaFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                        :class="selectedCategoria === '' || selectedCategoria === null ? 'text-gray-400' : 'text-gray-900'"
                                    >
                                        <option value="" disabled select hiddene hiddend hidden>Categoría</option>
                                        <option
                                            v-for="categoria in categorias"
                                            :key="categoria.id"
                                            :value="categoria.id"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ categoria.nombre }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        v-model="selectedEstado"
                                        @change="onEstadoFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                        :class="selectedEstado === '' || selectedEstado === null ? 'text-gray-400' : 'text-gray-900'"
                                    >
                                        <option value="" disabled select hiddene hiddend hidden>Estado</option>
                                        <option
                                            v-for="estado in estadosOptions"
                                            :key="estado.value"
                                            :value="estado.value"
                                            class="text-gray-900 truncate text-lg"
                                        >
                                            {{ estado.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Texto de ayuda para la tabla -->
                            <div class="px-1 mt-3">
                                <p class="text-blue-600 text-xs font-medium flex items-center gap-1">
                                    <FontAwesomeIcon :icon="faHandPointUp" class="h-3 w-3 text-yellow-500" />
                                    Haz clic en cualquier fila para ver los detalles.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
                <Column field="nombre" header="Nombre" sortable class="w-28">
                    <template #body="slotProps">
                        <div
                            class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 100px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.nombre"
                        >
                            {{ slotProps.data.nombre }}
                        </div>
                    </template>
                </Column>

                <Column field="descripcion" header="Descripción" class="w-28 hidden md:table-cell">
                    <template #body="slotProps">
                        <div
                            class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 100px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.descripcion"
                        >
                            {{ slotProps.data.descripcion }}
                        </div>
                    </template>
                </Column>

                <Column field="categoria.nombre" header="Categoría" class="w-32 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.categoria?.nombre || 'Sin categoría' }}
                        </div>
                    </template>
                </Column>
                <Column field="precio" header="Precio" class="w-16">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{ isNaN(Number(slotProps.data.precio)) ? "" : `$${Number(slotProps.data.precio).toFixed(2)}` }}
                        </div>
                    </template>
                </Column>

                <Column field="estado" header="Estado" class="w-28 hidden sm:table-cell">
                    <template #body="slotProps">
                        <span :class="'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' + getEstadoStock(slotProps.data).class">
                            {{ getEstadoStock(slotProps.data).label }}
                        </span>
                    </template>
                </Column>

                <Column :exportable="false" class="w-52 min-w-36">
                    <template #header>
                        <div class="text-center w-full font-bold">
                            Acciones
                        </div>
                    </template>
                    <template #body="slotProps">
                        <div class="flex gap-2 justify-center items-center">
                            <button
                                class="flex bg-green-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="openMoreActionsModal(slotProps.data)">
                                <FontAwesomeIcon :icon="faListDots" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Más</span>
                            </button>
                            <button
                               class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editProduct(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteProduct(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!--Modal de formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Producto'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model="producto.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="100"
                                :class="{'p-invalid': submitted && (!producto.nombre || producto.nombre.length < 3 || producto.nombre.length > 100)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                placeholder="TAZA PARA CAFÉ, ETC."
                                @input="validateNombre"
                                @keydown="onNombreKeyDown"
                                @paste="onNombrePaste"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="producto.nombre && producto.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres. Actual: {{ producto.nombre.length }}/3
                        </small>
                        <small class="text-red-500 ml-28" v-if="producto.nombre && producto.nombre.length >= 3 && checkNombreDuplicado()">
                            ⚠️ Ya existe un producto con este nombre.
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
                                v-model="producto.descripcion"
                                id="descripcion"
                                name="descripcion"
                                :maxlength="255"
                                rows="3"
                                :class="{'p-invalid': submitted && (!producto.descripcion || producto.descripcion.length < 10 || producto.descripcion.length > 255)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                @input="validateDescripcion"
                                @keydown="onDescripcionKeyDown"
                                @paste="onDescripcionPaste"
                                placeholder="TAZA DE CERÁMICA IDEAL PARA CAFÉ CALIENTE - 250ML, ETC."
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
                        <div class="flex items-center gap-4 sm:gap-5">
                            <label for="categoria_id" class="w-36 sm:w-32 flex items-center gap-1">
                                Categ.: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <div class="flex items-center gap-2 flex-1">
                                <Select
                                    v-model="producto.categoria_id"
                                    :options="categoriasConTruncado"
                                    optionLabel="nombreTruncado"
                                    optionValue="id"
                                    id="categoria_id"
                                    name="categoria_id"
                                    :filter="true"
                                    filterPlaceholder="Buscar categoría..."
                                    :showClear="false"
                                    class="w-12 sm:w-64 ml-8 rounded-md border-2 border-gray-400 hover:border-gray-500 truncate-select"
                                    placeholder="Seleccionar categoría"
                                    :class="{'p-invalid': submitted && !producto.categoria_id}"
                                />
                                <button
                                    type="button"
                                    @click="nuevaCategoriaDialog = true"
                                    class="bg-green-500 hover:bg-green-600 text-white p-1 rounded-full transition-all duration-200 ease-in-out -ml-1 flex items-center justify-center min-w-[10px]"
                                    title="Agregar nueva categoría"
                                >
                                    <FontAwesomeIcon :icon="faPlus" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !producto.categoria_id">
                            La categoría es obligatoria.
                        </small>
                    </div>

                    <!-- Precio -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-12 sm:gap-11">
                            <label for="precio" class="w-24 flex items-center gap-1">
                                Precio: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <div class="w-full relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 pointer-events-none">$</span>
                                <InputText
                                    v-model="producto.precio"
                                    id="precio"
                                    name="precio"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full pl-8 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                    :class="{'p-invalid': submitted && (!producto.precio || producto.precio <= 0 || producto.precio > 9999.99)}"
                                    placeholder="0.00"
                                    @keydown="onPriceKeyDown"
                                    @paste="onPricePaste"
                                />
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && (producto.precio == null || producto.precio <= 0 || producto.precio > 9999.99)">
                            El precio es obligatorio, debe ser mayor a 0 y menor o igual a 9999.99.
                        </small>
                    </div>

            <!-- Stock -->
            <div class="flex gap-4">
                <div class="flex-1">
                    <label for="stock_actual" class="block mb-2" :class="producto.id !== null ? 'text-gray-500' : ''">
                        Stock actual:
                        <span v-if="producto.id === null" class="text-red-500 font-bold">*</span>
                        <span v-else class="text-gray-400 text-xs">(Deshabilitado)</span>
                    </label>
                    <InputText
                        v-model="producto.stock_actual"
                        id="stock_actual"
                        name="stock_actual"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        max="9999"
                        :disabled="producto.id !== null"
                        :class="{
                            'bg-gray-100 cursor-not-allowed': producto.id !== null,
                            'border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none': producto.id === null,
                            'border-2 border-gray-300': producto.id !== null
                        }"
                        class="w-full rounded-md"
                        placeholder="0"
                        @keydown="onStockKeyDown"
                        @paste="onStockPaste"
                    />
                    <small v-if="producto.id !== null" class="text-blue-600 text-xs mt-1 block">
                        Para actualizar el stock usa el botón "Más" → "Actualizar Stock"
                    </small>
                </div>
                <div class="flex-1">
                    <label for="stock_minimo" class="block mb-2" :class="producto.id !== null ? 'text-gray-500' : ''">
                        Stock mínimo:
                        <span v-if="producto.id === null" class="text-red-500 font-bold">*</span>
                        <span v-else class="text-gray-400 text-xs">(Deshabilitado)</span>
                    </label>
                    <InputText
                        v-model="producto.stock_minimo"
                        id="stock_minimo"
                        name="stock_minimo"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        max="100"
                        :disabled="producto.id !== null"
                        :class="{
                            'bg-gray-100 cursor-not-allowed': producto.id !== null,
                            'border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none': producto.id === null,
                            'border-2 border-gray-300': producto.id !== null
                        }"
                        class="w-full rounded-md"
                        placeholder="1"
                        @keydown="onStockMinimoKeyDown"
                        @paste="onStockMinimoPaste"
                    />
                    <small v-if="producto.id !== null" class="text-blue-600 text-xs mt-1 block">
                        Para actualizar el stock mínimo usa "Actualizar Stock"
                    </small>
                </div>
            </div>                    <!-- Imágenes -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="imagenes" class="w-24 flex items-center gap-1">Imágenes:<span class="text-red-500 font-bold">*</span></label>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple @change="onImageSelect" class="hidden" ref="fileInput"/>
                                    <button type="button"
                                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 outline-none focus:outline-none active:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                        @click="handleImageUploadClick"
                                        :disabled="isUploadingImages || isOpeningGallery || !canAddMoreImages">
                                        <FontAwesomeIcon
                                            :icon="isUploadingImages ? faSpinner : (isOpeningGallery ? faSpinner : faPlus)"
                                            :class="{ 'animate-spin': isUploadingImages || isOpeningGallery }"
                                        />
                                        {{ imageButtonText }}
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium" :class="imagenPreviewList.length >= 5 ? 'text-red-600' : 'text-gray-600'">
                                            {{ imagenPreviewList.length }}/5
                                        </span>
                                        <span class="text-xs text-gray-500">imágenes</span>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 mb-2">
                                    Máximo 5 imágenes por producto • Formatos: JPG, PNG, GIF • Tamaño máximo: 2MB por imagen
                                </div>
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && imagenPreviewList.length === 0">Las imágenes son obligatorias (al menos una).</small>
                    </div>
                    <div class="grid grid-cols-3 ml-2 sm:ml-5 gap-y-2">
                        <div v-for="(img, index) in imagenPreviewList" :key="index" class="relative w-24 sm:w-28 h-24 sm:h-28">
                            <img :src=" img.startsWith('data:image') ? img : IMAGE_PATH + img " alt="Vista previa" class="w-full h-full object-cover rounded border"/>
                            <button @click="removeImage(index)" class="absolute top-2 right-2 bg-gray-600/80 hover:bg-gray-700/80 text-white font-bold py-1 px-2 rounded-full shadow" style="transform: translate(50%, -50%)"> <i class="pi pi-times text-xs"></i></button>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-center gap-4 w-full mt-6">
                        <button
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
                            <span v-else>{{ btnTitle === 'Guardar' ? 'Guardando...' : 'Actualizando...' }}</span>
                        </button>
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="hideDialog" :disabled="isLoading">
                            <FontAwesomeIcon :icon="faXmark" class="h-5" />Cancelar
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- Modal para crear nueva categoría -->
            <Dialog
                v-model:visible="nuevaCategoriaDialog"
                header="Agregar Nueva Categoría"
                :modal="true"
                :style="dialogStyle"
                :closable="false"
                :draggable="false"
            >
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombreCategoria" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model.trim="nuevaCategoria.nombre"
                                id="nombreCategoria"
                                name="nombreCategoria"
                                :maxlength="30"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :class="{
                                    'border-red-400 focus:border-red-500': nuevaCategoria.nombre && nuevaCategoria.nombre.length > 30,
                                    'border-orange-400 focus:border-orange-500': nuevaCategoria.nombre && nuevaCategoria.nombre.length > 25 && nuevaCategoria.nombre.length <= 30
                                }"
                                placeholder="CATEGORÍA (MAX 30 CARACTERES)"
                                style="text-transform: uppercase;"
                                @input="onNombreCategoriaInput"
                                @keyup.enter="crearNuevaCategoria"
                            />
                        </div>
                        <small class="text-gray-500 ml-28 text-xs" v-if="nuevaCategoria.nombre">
                            Caracteres: {{ nuevaCategoria.nombre.length }}/30
                        </small>
                        <small class="text-red-500 ml-28" v-if="nuevaCategoria.nombre && nuevaCategoria.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres.
                        </small>
                        <small class="text-orange-500 ml-28" v-if="nuevaCategoria.nombre && nuevaCategoria.nombre.length > 25 && nuevaCategoria.nombre.length <= 30">
                            Te quedan {{ 30 - nuevaCategoria.nombre.length }} caracteres
                        </small>
                        <small class="text-red-500 ml-28" v-if="nuevaCategoria.nombre && nuevaCategoria.nombre.length > 30">
                            Has excedido el límite por {{ nuevaCategoria.nombre.length - 30 }} caracteres
                        </small>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-center gap-4 w-full mt-6">
                        <button
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="crearNuevaCategoria"
                            :disabled="isCreatingCategory || !nuevaCategoria.nombre || nuevaCategoria.nombre.length < 3 || nuevaCategoria.nombre.length > 30"
                        >
                            <FontAwesomeIcon
                                :icon="isCreatingCategory ? faSpinner : faCheck"
                                :class="[
                                    'h-5 text-white',
                                    { 'animate-spin': isCreatingCategory }
                                ]"
                            />
                            <span v-if="!isCreatingCategory">Guardar</span>
                            <span v-else>Guardando...</span>
                        </button>
                        <button
                            type="button"
                            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="cerrarModalCategoria"
                            :disabled="isCreatingCategory"
                        >
                            <FontAwesomeIcon :icon="faXmark" class="h-5" />Cancelar
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- Componente de Modales de Productos -->
            <ProductoModals
                v-model:visible="moreActionsDialog"
                v-model:details-visible="showImageDialog"
                v-model:carousel-visible="showImageCarouselDialog"
                v-model:delete-visible="deleteDialog"
                v-model:unsaved-changes-visible="unsavedChangesDialog"
                v-model:update-stock-visible="updateStockDialog"
                v-model:carousel-index="carouselIndex"
                :producto="selectedProduct || producto"
                :dialog-style="dialogStyle"
                :selected-images="selectedImages"
                :image-path="IMAGE_PATH"
                :is-deleting="isDeleting"
                @update-stock="handleUpdateStock"
                @delete-product="deleteProduct"
                @cancel-delete="() => deleteDialog = false"
                @close-without-saving="closeDialogWithoutSaving"
                @continue-editing="continueEditing"
                @view-details="handleViewDetails"
                @open-image-modal="openImageModal"
                @stock-updated="handleStockUpdated"
            />
            </div>
        </div>
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

/* Estilos para truncar texto en Select - PrimeVue específico */
.p-select .p-select-label,
.p-select .p-placeholder,
.p-select-label,
.p-placeholder {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
    display: block !important;
}

/* Contenedor principal del Select */
.p-select {
    overflow: hidden !important;
    max-width: 100% !important;
}

/* Input interno del Select */
.p-select .p-inputtext,
.p-select input {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
    width: 100% !important;
}

/* Forzar truncado en todos los elementos internos */
.p-select * {
    max-width: 100% !important;
    box-sizing: border-box !important;
}

/* Específico para el valor mostrado */
.p-select .p-select-display-chip,
.p-select .p-select-clear-icon ~ *,
.p-select .p-select-trigger ~ * {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: calc(100% - 2.5rem) !important;
}

/* Extra específico para móviles */
@media (max-width: 768px) {
    .p-select .p-select-label,
    .p-select .p-placeholder {
        font-size: 0.875rem !important;
        max-width: calc(100% - 2rem) !important;
    }
}
/* Fin de los estilos para el select */


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
/* Fin de los estilos para el paginador */

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
/* Fin de la animación para el spinner de loading */

/* Estilos para truncar texto en selects (especialmente en móviles) */
.p-select .p-select-label {
  width: 100% !important;
  max-width: 100% !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

/* Asegurar que el select mantenga su ancho en móviles */
@media (max-width: 640px) {
  .p-select {
    min-width: 200px !important;
  }

  .p-select .p-select-label {
    max-width: 180px !important;
  }
}

/* Para tablets */
@media (min-width: 641px) and (max-width: 768px) {
  .p-select .p-select-label {
    max-width: 220px !important;
  }
}
/* Fin de estilos para truncar texto en selects */

/* Estilos para select nativo con placeholder */
select:invalid {
    color: #9ca3af !important; /* Color gris para placeholder */
}

select option {
    color: #111827 !important; /* Color normal para opciones */
}

select option:disabled {
    color: #9ca3af !important;
}

/* Mejorar apariencia general del select nativo */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
    background-position: right 0.5rem center !important;
    background-repeat: no-repeat !important;
    background-size: 1.5em 1.5em !important;
    padding-right: 2.5rem !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
}

/* Truncado específico para Select de categoría */
.truncate-select .p-select-label,
.truncate-select .p-placeholder {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: calc(100% - 1.5rem) !important;
    display: block !important;
    padding-right: 0rem !important;
}

.truncate-select .p-inputtext {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: calc(100% - 1.5rem) !important;
    display: block !important;
    padding-right: 0rem !important;
}

/* Asegurar que el contenedor del select también respete el ancho */
.truncate-select {
    position: relative !important;
    max-width: none !important;
    padding-right: 1.2rem !important;
}

.truncate-select .p-select {
    min-width: 0 !important;
}

/* Reducir espacio del trigger (flecha) - Escritorio */
.truncate-select .p-select-trigger {
    position: absolute !important;
    right: 0 !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 1.5rem !important;
    height: 1.5rem !important;
    margin: 0 !important;
    padding: 0 !important;
    flex-shrink: 0 !important;
}

.truncate-select .p-select-dropdown {
    position: absolute !important;
    right: 0 !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 1.5rem !important;
    height: 1.5rem !important;
    margin: 0 !important;
    padding: 0 !important;
    flex-shrink: 0 !important;
}

/* Responsivo: en móviles aún más pequeño */
@media (max-width: 640px) {
    .truncate-select .p-select-label,
    .truncate-select .p-placeholder,
    .truncate-select .p-inputtext {
        max-width: calc(100% - 1.2rem) !important;
        padding-right: 0 !important;
    }

    .truncate-select .p-select-trigger {
        position: absolute !important;
        right: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 1.5rem !important;
        height: 1.5rem !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .truncate-select .p-select-dropdown {
        position: absolute !important;
        right: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 1.5rem !important;
        height: 1.5rem !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .truncate-select {
        position: relative !important;
        padding-right: 1.2rem !important;
    }
}

/* Truncado en las opciones del dropdown */
.p-select-overlay .p-select-option {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
}

.p-select-overlay .p-select-option-label {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    display: block !important;
}
</style>
