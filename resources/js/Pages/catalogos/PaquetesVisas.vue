<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faFilter, faImages, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark, faTags } from "@fortawesome/free-solid-svg-icons";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import axios from "axios";
axios.defaults.withCredentials = true;

const toast = useToast();

// 📊 Estados reactivos
const paquetes = ref([]);
const paquete = ref({
    id: null,
    nombre: "",
    descripcion: "",
    precio: null,
    incluye: "",
    no_incluye: "",
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
const originalPaqueteData = ref(null);

// Variables para modales de paquetes
const showImageCarouselDialog = ref(false);
const selectedPaquete = ref(null);

// Variables para listas de incluye/no incluye (como en Tours.vue)
const incluyeLista = ref([]);
const noIncluyeLista = ref([]);
const nuevoItemIncluye = ref("");
const nuevoItemNoIncluye = ref("");

// Variables de loading
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const isUploadingImages = ref(false);
const isOpeningGallery = ref(false);
const isLoading = ref(false);
const isLoadingTable = ref(true);

// 🔍 Filtros
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");
const carouselIndex = ref(0);
const fileInput = ref(null);

// 🎯 URLs y constantes
const url = "/api/paquetes-visas";
const IMAGE_PATH = "/storage/paquetesvisas/";

// 🔍 Paquetes filtrados
const filteredPaquetes = computed(() => {
    let filtered = paquetes.value;

    // Filtro por búsqueda global
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(paquete =>
            paquete.nombre.toLowerCase().includes(searchTerm) ||
            (paquete.descripcion && paquete.descripcion.toLowerCase().includes(searchTerm)) ||
            paquete.precio.toString().includes(searchTerm)
        );
    }

    return filtered;
});

// Computed para verificar si se puede agregar más imágenes
const canAddMoreImages = computed(() => {
    return imagenPreviewList.value.length < 1; // Máximo 1 imagen
});

// Computed para el texto del botón de imágenes
const imageButtonText = computed(() => {
    if (isUploadingImages.value) return 'Cargando...';
    if (isOpeningGallery.value) return 'Abriendo...';
    if (!canAddMoreImages.value) return 'Límite alcanzado';
    return 'Seleccionar';
});

// Computed para verificar si los campos requeridos están completos
const isFormValid = computed(() => {
    const nombreValido = paquete.value.nombre && paquete.value.nombre.trim().length >= 3;
    const precioValido = paquete.value.precio && parseFloat(paquete.value.precio) > 0;

    return nombreValido && precioValido;
});

// 👀 Watcher para detectar cambios en el modal
watch([paquete, imagenPreviewList, removedImages, incluyeLista, noIncluyeLista], () => {
    if (dialog.value) {
        if (originalPaqueteData.value) {
            // Modo edición - comparar con datos originales
            const currentData = {
                nombre: paquete.value.nombre,
                descripcion: paquete.value.descripcion,
                precio: paquete.value.precio,
                incluye_lista: [...incluyeLista.value],
                no_incluye_lista: [...noIncluyeLista.value],
            };

            const currentImages = imagenPreviewList.value.map(img => img.name || img.nombre);
            const originalImages = originalPaqueteData.value.imagenes ?
                originalPaqueteData.value.imagenes.map(img => img.nombre) : [];

            const originalIncluye = textoALista(originalPaqueteData.value.incluye || '');
            const originalNoIncluye = textoALista(originalPaqueteData.value.no_incluye || '');

            const hasDataChanges = JSON.stringify(currentData) !== JSON.stringify({
                nombre: originalPaqueteData.value.nombre,
                descripcion: originalPaqueteData.value.descripcion,
                precio: originalPaqueteData.value.precio,
                incluye_lista: originalIncluye,
                no_incluye_lista: originalNoIncluye,
            });

            const hasImageChanges = JSON.stringify(currentImages.sort()) !== JSON.stringify(originalImages.sort()) ||
                                    removedImages.value.length > 0;

            hasUnsavedChanges.value = hasDataChanges || hasImageChanges;
        } else {
            // Modo creación - detectar si hay algún dato ingresado
            const hasName = paquete.value.nombre && paquete.value.nombre.trim().length > 0;
            const hasDescription = paquete.value.descripcion && paquete.value.descripcion.trim().length > 0;
            const hasPrice = paquete.value.precio && parseFloat(paquete.value.precio) > 0;
            const hasIncluye = incluyeLista.value.length > 0;
            const hasNoIncluye = noIncluyeLista.value.length > 0;
            const hasImages = imagenPreviewList.value.length > 0;

            hasUnsavedChanges.value = hasName || hasDescription || hasPrice || hasIncluye || hasNoIncluye || hasImages;
        }
    }
}, { deep: true, flush: 'post' });

// 🔄 Función para resetear formulario
function resetForm() {
    paquete.value = {
        id: null,
        nombre: "",
        descripcion: "",
        precio: null,
        incluye: "",
        no_incluye: "",
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;

    // Limpiar listas
    incluyeLista.value = [];
    noIncluyeLista.value = [];
    nuevoItemIncluye.value = "";
    nuevoItemNoIncluye.value = "";
}

// Funciones para manejar listas de incluye/no incluye
const agregarItemIncluye = () => {
    if (nuevoItemIncluye.value.trim()) {
        incluyeLista.value.push(nuevoItemIncluye.value.trim());
        nuevoItemIncluye.value = "";
    }
};

const eliminarItemIncluye = (index) => {
    incluyeLista.value.splice(index, 1);
};

const agregarItemNoIncluye = () => {
    if (nuevoItemNoIncluye.value.trim()) {
        noIncluyeLista.value.push(nuevoItemNoIncluye.value.trim());
        nuevoItemNoIncluye.value = "";
    }
};

const eliminarItemNoIncluye = (index) => {
    noIncluyeLista.value.splice(index, 1);
};

// Funciones para convertir entre texto y lista
const textoALista = (texto) => {
    return texto ? texto.split('|').filter(item => item.trim()).map(item => item.trim()) : [];
};

const listaATexto = (lista) => {
    return lista.join('|');
};

// Función para manejar resize de ventana
const handleResize = () => {
    windowWidth.value = window.innerWidth;
};

// 📊 Cargar datos
onMounted(() => {
    fetchPaquetesWithToasts();

    // Listener para resize de ventana para hacer responsivos los modales
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    // Cleanup del listener de resize
    window.removeEventListener('resize', handleResize);
});

const fetchPaquetes = async () => {
    try {
        const response = await axios.get(url);
        if (response.data.success) {
            paquetes.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching paquetes:', error);
    }
};

const fetchPaquetesWithToasts = async () => {
    isLoadingTable.value = true;

    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando paquetes...",
        life: 2000
    });

    try {
        const response = await axios.get(url);

        if (response.data.success) {
            paquetes.value = response.data.data.sort((a, b) => {
                const dateA = new Date(a.created_at);
                const dateB = new Date(b.created_at);
                return dateB - dateA;
            });

            // Mostrar toast de éxito
            toast.add({
                severity: "success",
                summary: "Paquetes cargados",
                life: 2000
            });

            // Si no hay paquetes, mostrar mensaje informativo adicional
            if (paquetes.value.length === 0) {
                toast.add({
                    severity: 'info',
                    summary: 'Sin registros',
                    detail: 'No hay paquetes de visa registrados aún.',
                    life: 4000
                });
            }
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Advertencia',
                detail: 'No se pudieron cargar los paquetes correctamente.',
                life: 4000
            });
        }
    } catch (error) {
        console.error('Error al cargar paquetes:', error);

        if (error.response?.status === 401) {
            toast.add({
                severity: 'error',
                summary: 'No autorizado',
                detail: 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error de conexión',
                detail: 'No se pudieron cargar los paquetes. Verifica tu conexión.',
                life: 5000
            });
        }
    } finally {
        isLoadingTable.value = false;
    }
};

// 🔍 Funciones para manejar filtros
const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        await new Promise(resolve => setTimeout(resolve, 300));

        filters.value.global.value = null;

        toast.add({
            severity: 'success',
            summary: 'Filtros limpiados',
            detail: 'Se han restablecido todos los filtros.',
            life: 3000
        });
    } finally {
        isClearingFilters.value = false;
    }
};

// 📝 CRUD Operations
const openNew = () => {
    resetForm();
    originalPaqueteData.value = null;
    hasUnsavedChanges.value = false;
    btnTitle.value = "Guardar";
    submitted.value = false;
    dialog.value = true;

    nextTick(() => {
        const nombreInput = document.querySelector('input[placeholder="INGRESE EL NOMBRE DEL PAQUETE"]');
        if (nombreInput) {
            nombreInput.focus();
        }
    });
};

const editPaquete = (p) => {
    paquete.value = { ...p };
    originalPaqueteData.value = JSON.parse(JSON.stringify(p));

    // Cargar imágenes existentes
    imagenPreviewList.value = p.imagenes ? p.imagenes.map(img => ({
        id: img.id,
        name: img.nombre,
        url: IMAGE_PATH + img.nombre,
        isExisting: true
    })) : [];

    // Cargar listas desde el texto existente
    incluyeLista.value = textoALista(p.incluye);
    noIncluyeLista.value = textoALista(p.no_incluye);

    imagenFiles.value = [];
    removedImages.value = [];
    hasUnsavedChanges.value = false;
    btnTitle.value = "Actualizar";
    submitted.value = false;
    dialog.value = true;
};

const saveOrUpdate = async () => {
    submitted.value = true;
    isLoading.value = true;

    // Limpiar y validar el nombre antes de enviar (aplicar limpieza final)
    paquete.value.nombre = cleanNombreInput(paquete.value.nombre || '');

    // Validaciones
    if (!paquete.value.nombre?.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Campo requerido',
            detail: 'El nombre del paquete es obligatorio.',
            life: 4000
        });
        isLoading.value = false;
        return;
    }

    if (!/^[A-Z0-9\s]+$/.test(paquete.value.nombre.trim())) {
        toast.add({
            severity: 'warn',
            summary: 'Formato inválido',
            detail: 'El nombre solo puede contener letras, números y espacios.',
            life: 4000
        });
        isLoading.value = false;
        return;
    }

    if (!paquete.value.precio || paquete.value.precio <= 0) {
        toast.add({
            severity: 'warn',
            summary: 'Campo requerido',
            detail: 'El precio debe ser mayor a 0.',
            life: 4000
        });
        isLoading.value = false;
        return;
    }

    try {
        const formData = new FormData();

        // Datos básicos
        formData.append('nombre', paquete.value.nombre.trim());
        if (paquete.value.descripcion?.trim()) {
            formData.append('descripcion', paquete.value.descripcion.trim());
        }
        formData.append('precio', paquete.value.precio);

        // Usar las listas para crear los strings separados por |
        if (incluyeLista.value.length > 0) {
            formData.append('incluye', listaATexto(incluyeLista.value));
        } else {
            formData.append('incluye', ''); // Enviar cadena vacía para limpiar el campo
        }

        if (noIncluyeLista.value.length > 0) {
            formData.append('no_incluye', listaATexto(noIncluyeLista.value));
        } else {
            formData.append('no_incluye', ''); // Enviar cadena vacía para limpiar el campo
        }

        // Manejar imágenes
        if (imagenFiles.value.length > 0) {
            formData.append('imagenes', imagenFiles.value[0]);
        }

        // Para actualización, agregar imágenes eliminadas
        if (paquete.value.id && removedImages.value.length > 0) {
            removedImages.value.forEach(removedImage => {
                formData.append('removed_images[]', removedImage);
            });
        }

        let response;
        if (paquete.value.id) {
            // Para actualización con archivos, usar POST con _method=PUT
            formData.append('_method', 'PUT');
            response = await axios.post(`${url}/${paquete.value.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
        } else {
            // Para creación
            response = await axios.post(url, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
        }

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: paquete.value.id
                    ? 'Paquete actualizado correctamente'
                    : 'Paquete creado correctamente',
                life: 4000
            });

            dialog.value = false;
            await fetchPaquetes();
            resetForm();
            hasUnsavedChanges.value = false;
        }
    } catch (error) {
        console.error('Error saving paquete:', error);

        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            const firstError = Object.values(errors)[0]?.[0] || 'Error de validación';

            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: firstError,
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo guardar el paquete. Intenta nuevamente.',
                life: 5000
            });
        }
    } finally {
        isLoading.value = false;
    }
};

const confirmDeletePaquete = (p) => {
    paquete.value = p;
    deleteDialog.value = true;
};

const deletePaquete = async () => {
    isDeleting.value = true;

    try {
        const response = await axios.delete(`${url}/${paquete.value.id}`);

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Paquete eliminado correctamente',
                life: 4000
            });

            deleteDialog.value = false;
            await fetchPaquetes();
        }
    } catch (error) {
        console.error('Error deleting paquete:', error);

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo eliminar el paquete. Intenta nuevamente.',
            life: 5000
        });
    } finally {
        isDeleting.value = false;
    }
};

// 🚪 Funciones para cerrar modales
const hideDialog = () => {
    if (hasUnsavedChanges.value) {
        unsavedChangesDialog.value = true;
    } else {
        dialog.value = false;
        resetForm();
    }
};

const closeDialogWithoutSaving = () => {
    dialog.value = false;
    unsavedChangesDialog.value = false;
    hasUnsavedChanges.value = false;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// Funciones para manejar los modales de paquetes

const openImageModal = (index) => {
    if (paquete.value.imagenes && paquete.value.imagenes.length > 0) {
        selectedImages.value = paquete.value.imagenes.map(img => ({
            id: img.id,
            nombre: img.nombre,
            url: IMAGE_PATH + img.nombre
        }));
        carouselIndex.value = index;
        showImageCarouselDialog.value = true;
    } else {
        isOpeningGallery.value = true;
        setTimeout(() => {
            isOpeningGallery.value = false;
            toast.add({
                severity: 'info',
                summary: 'Sin imágenes',
                detail: 'Este paquete no tiene imágenes.',
                life: 3000
            });
        }, 300);
    }
};

// Función para manejar el clic en la fila
const onRowClick = (event) => {
    // Verificar si el clic fue en un botón para evitar abrir el modal
    const target = event.originalEvent.target;
    const isButton = target.closest('button');

    if (!isButton) {
        selectedPaquete.value = event.data;
        showImageDialog.value = true;
    }
};

// Variable reactiva para el ancho de ventana
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
});// 🖼️ Manejo de imágenes
const handleImageUploadClick = async () => {
    if (!canAddMoreImages.value) {
        toast.add({
            severity: 'warn',
            summary: 'Límite alcanzado',
            detail: 'Solo se permite 1 imagen por paquete.',
            life: 3000
        });
        return;
    }

    isUploadingImages.value = true;
    await new Promise(resolve => setTimeout(resolve, 200));
    fileInput.value?.click();
    isUploadingImages.value = false;
};

const onImageSelect = async (event) => {
    const files = Array.from(event.target.files);

    if (files.length === 0) return;

    // Verificar límite de imágenes (1 máximo)
    if (imagenPreviewList.value.length + files.length > 1) {
        toast.add({
            severity: 'warn',
            summary: 'Límite excedido',
            detail: 'Solo se permite 1 imagen por paquete.',
            life: 4000
        });
        return;
    }

    for (const file of files) {
        // Validar tipo de archivo
        if (!file.type.startsWith('image/')) {
            toast.add({
                severity: 'error',
                summary: 'Archivo inválido',
                detail: `${file.name} no es una imagen válida.`,
                life: 4000
            });
            continue;
        }

        // Validar tamaño (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            toast.add({
                severity: 'error',
                summary: 'Archivo muy grande',
                detail: `${file.name} excede los 2MB permitidos.`,
                life: 4000
            });
            continue;
        }

        // Crear preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagenPreviewList.value.push({
                name: file.name,
                url: e.target.result,
                isExisting: false
            });
        };
        reader.readAsDataURL(file);

        // Guardar archivo para envío
        imagenFiles.value.push(file);
    }

    // Limpiar input
    event.target.value = '';
};

const removeImage = (index) => {
    const imageToRemove = imagenPreviewList.value[index];

    if (imageToRemove.isExisting) {
        removedImages.value.push(imageToRemove.name);
    } else {
        const fileIndex = imagenFiles.value.findIndex(file => file.name === imageToRemove.name);
        if (fileIndex !== -1) {
            imagenFiles.value.splice(fileIndex, 1);
        }
    }

    imagenPreviewList.value.splice(index, 1);
};

const viewImages = (imagenesPaquete) => {
    if (imagenesPaquete && imagenesPaquete.length > 0) {
        selectedImages.value = imagenesPaquete.map(img => ({
            id: img.id,
            nombre: img.nombre,
            url: IMAGE_PATH + img.nombre
        }));
        carouselIndex.value = 0;
        showImageCarouselDialog.value = true;
    } else {
        toast.add({
            severity: 'info',
            summary: 'Sin imágenes',
            detail: 'Este paquete no tiene imágenes.',
            life: 3000
        });
    }
};

// ✅ Validaciones en tiempo real
const validateNombre = () => {
    if (!paquete.value.nombre || !paquete.value.nombre.trim()) {
        return false;
    }
    
    const nombre = paquete.value.nombre.trim();
    // Verificar longitud mínima y que solo contenga letras, números y espacios
    return nombre.length >= 3 && /^[A-Z0-9\s]+$/.test(nombre);
};

const validateDescripcion = () => {
    return !paquete.value.descripcion || paquete.value.descripcion.trim().length >= 10;
};

// Función para prevenir teclas no válidas en campo de precio
const onPriceKeyDown = (event) => {
    const allowedKeys = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'Home', 'End', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        'F5'
    ];

    const isNumberKey = (event.key >= '0' && event.key <= '9');
    const isDecimalKey = event.key === '.';
    const isCtrlA = event.ctrlKey && event.key === 'a';
    const isCtrlC = event.ctrlKey && event.key === 'c';
    const isCtrlV = event.ctrlKey && event.key === 'v';
    const isCtrlX = event.ctrlKey && event.key === 'x';
    const isCtrlZ = event.ctrlKey && event.key === 'z';

    if (allowedKeys.includes(event.key) || isNumberKey || isCtrlA || isCtrlC || isCtrlV || isCtrlX || isCtrlZ) {
        if (isDecimalKey) {
            const currentValue = event.target.value;
            if (currentValue.includes('.')) {
                event.preventDefault();
                return;
            }
        }
        return;
    }

    if (isDecimalKey) {
        const currentValue = event.target.value;
        if (currentValue.includes('.')) {
            event.preventDefault();
            return;
        }
        return;
    }

    event.preventDefault();
};

// Función para manejar paste en campo de precio
const onPricePaste = (event) => {
    event.preventDefault();

    const paste = (event.clipboardData || window.clipboardData).getData('text');
    const numericValue = paste.replace(/[^0-9.]/g, '');

    const decimalCount = (numericValue.match(/\./g) || []).length;
    if (decimalCount <= 1) {
        const currentValue = event.target.value;
        const start = event.target.selectionStart;
        const end = event.target.selectionEnd;

        const newValue = currentValue.substring(0, start) + numericValue + currentValue.substring(end);

        if (!isNaN(newValue) && newValue !== '') {
            paquete.value.precio = parseFloat(newValue);
        }
    }
};

// Funciones para validar y limpiar el nombre del paquete
const cleanNombreInput = (value) => {
    if (!value) return '';
    
    return value
        .replace(/[^A-Za-z0-9\s]/g, '') // Eliminar caracteres especiales, mantener letras, números y espacios
        .replace(/\s{2,}/g, ' ') // Reemplazar 2 o más espacios consecutivos por uno solo
        .toUpperCase() // Convertir a mayúsculas
        .trim(); // Quitar espacios al inicio y final
};

const onNombreInput = (event) => {
    let inputValue = event.target.value;
    
    // Aplicar transformaciones básicas sin ser muy agresivo durante la escritura
    inputValue = inputValue
        .replace(/[^A-Za-z0-9\s]/g, '') // Eliminar caracteres especiales
        .toUpperCase(); // Convertir a mayúsculas
    
    // Solo limpiar espacios múltiples si hay más de un espacio consecutivo
    const cleanedValue = inputValue.replace(/\s{3,}/g, ' '); // Solo reemplazar 3 o más espacios
    
    paquete.value.nombre = cleanedValue;
    
    // Actualizar el input solo si es necesario
    if (event.target.value !== cleanedValue) {
        const cursorPosition = event.target.selectionStart;
        event.target.value = cleanedValue;
        // Mantener la posición del cursor
        nextTick(() => {
            const newPosition = Math.min(cursorPosition, cleanedValue.length);
            event.target.setSelectionRange(newPosition, newPosition);
        });
    }
};

const onNombreKeyDown = (event) => {
    const allowedKeys = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'Home', 'End', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        'F5'
    ];

    const isLetterOrNumber = /^[A-Za-z0-9]$/.test(event.key);
    const isSpace = event.key === ' ';
    const isCtrlA = event.ctrlKey && event.key === 'a';
    const isCtrlC = event.ctrlKey && event.key === 'c';
    const isCtrlV = event.ctrlKey && event.key === 'v';
    const isCtrlX = event.ctrlKey && event.key === 'x';
    const isCtrlZ = event.ctrlKey && event.key === 'z';

    // Permitir espacios, pero prevenir espacios múltiples
    if (isSpace) {
        const currentValue = event.target.value;
        const cursorPosition = event.target.selectionStart;
        const charBefore = currentValue.charAt(cursorPosition - 1);
        
        // Prevenir espacio si el carácter anterior ya es un espacio o si está al inicio
        if (charBefore === ' ' || cursorPosition === 0) {
            event.preventDefault();
            return;
        }
    }

    if (allowedKeys.includes(event.key) || isLetterOrNumber || isSpace || isCtrlA || isCtrlC || isCtrlV || isCtrlX || isCtrlZ) {
        return;
    }

    event.preventDefault();
};

const onNombrePaste = (event) => {
    event.preventDefault();

    const paste = (event.clipboardData || window.clipboardData).getData('text');
    const cleanedPaste = cleanNombreInput(paste);

    const currentValue = event.target.value;
    const start = event.target.selectionStart;
    const end = event.target.selectionEnd;

    const newValue = currentValue.substring(0, start) + cleanedPaste + currentValue.substring(end);
    const finalValue = cleanNombreInput(newValue);

    // Validar longitud máxima
    if (finalValue.length <= 100) {
        paquete.value.nombre = finalValue;
        event.target.value = finalValue;
        
        // Posicionar cursor
        nextTick(() => {
            const newPosition = Math.min(start + cleanedPaste.length, finalValue.length);
            event.target.setSelectionRange(newPosition, newPosition);
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Paquetes de Visa" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Catálogo de Paquetes de Visa</h1>
                <p class="text-gray-600">Gestión completa de paquetes de visa disponibles</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Paquetes</h3>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            class="bg-red-500 border flex border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                            @click="openNew">
                            <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" />
                            <span class="block sm:hidden">Agregar</span>
                            <span class="hidden sm:block">Agregar paquete</span>
                        </button>
                    </div>
                </div>
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
                                    <FontAwesomeIcon :icon="faFilter" class="text-blue-600 text-sm" />
                                    <span>Filtros</span>
                                </h3>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                                    {{ filteredPaquetes.length }} resultado{{ filteredPaquetes.length !== 1 ? 's' : '' }}
                                </div>
                                <button
                                    class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md flex sm:hidden disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                                    @click="clearFilters"
                                    :disabled="isClearingFilters"
                                >
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
                                :disabled="isClearingFilters"
                            >
                                <FontAwesomeIcon
                                    v-if="isClearingFilters"
                                    :icon="faSpinner"
                                    class="animate-spin h-3 w-3"
                                />
                                <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar paquetes..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="py-12 text-center">
                        <FontAwesomeIcon :icon="faTags" class="mb-4 h-12 w-12 text-gray-300" />
                        <p class="text-xl font-semibold text-gray-500">No hay paquetes</p>
                        <p class="text-gray-400">No se encontraron paquetes de visa</p>
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
                            {{ slotProps.data.descripcion || 'Sin descripción' }}
                        </div>
                    </template>
                </Column>

                <Column field="precio" header="Precio" sortable class="w-16 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{ isNaN(Number(slotProps.data.precio)) ? "" : `$${Number(slotProps.data.precio).toFixed(2)}` }}
                        </div>
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
                                class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editPaquete(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeletePaquete(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Modal Crear/Editar Paquete -->
        <Dialog
            v-model:visible="dialog"
            :header="paquete.id ? 'Editar Paquete' : 'Nuevo Paquete'"
            :modal="true"
            :style="dialogStyle"
            :closable="false"
            :draggable="false"
        >
            <div class="space-y-6">
                <!-- Información básica -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="paquete.nombre"
                            type="text"
                            placeholder="INGRESE EL NOMBRE DEL PAQUETE"
                            maxlength="100"
                            @input="onNombreInput"
                            @keydown="onNombreKeyDown"
                            @paste="onNombrePaste"
                            :class="[
                                'w-full rounded-lg border px-3 py-2 transition-colors focus:outline-none focus:ring-1 uppercase',
                                (submitted && !validateNombre()) || (!paquete.nombre?.trim() && !isFormValid)
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                    : paquete.nombre?.trim() && paquete.nombre.trim().length >= 3
                                        ? 'border-green-300 focus:border-green-500 focus:ring-green-500'
                                        : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                            ]"
                        />
                        <div v-if="submitted && !validateNombre()" class="text-sm text-red-600">
                            El nombre es requerido (mínimo 3 caracteres, solo letras, números y espacios)
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Precio <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="paquete.precio"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            @keydown="onPriceKeyDown"
                            @paste="onPricePaste"
                            :class="[
                                'w-full rounded-lg border px-3 py-2 transition-colors focus:outline-none focus:ring-1',
                                (submitted && (!paquete.precio || paquete.precio <= 0)) || (!paquete.precio && !isFormValid)
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                    : paquete.precio && parseFloat(paquete.precio) > 0
                                        ? 'border-green-300 focus:border-green-500 focus:ring-green-500'
                                        : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                            ]"
                        />
                        <div v-if="submitted && (!paquete.precio || paquete.precio <= 0)" class="text-sm text-red-600">
                            El precio es requerido y debe ser mayor a 0
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea
                        v-model="paquete.descripcion"
                        rows="3"
                        placeholder="Descripción del paquete (opcional)"
                        maxlength="255"
                        :class="[
                            'w-full rounded-lg border px-3 py-2 transition-colors focus:outline-none focus:ring-1',
                            submitted && !validateDescripcion()
                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                        ]"
                    ></textarea>
                    <div v-if="submitted && !validateDescripcion()" class="text-sm text-red-600">
                        La descripción debe tener al menos 10 caracteres
                    </div>
                    <!-- Contador de caracteres -->
                    <div v-if="paquete.descripcion && paquete.descripcion.length > 245" class="text-sm text-orange-500">
                        Caracteres restantes: {{ 255 - paquete.descripcion.length }}
                    </div>
                    <div v-if="paquete.descripcion && paquete.descripcion.length >= 255" class="text-sm text-red-600 font-semibold">
                        No se permiten más caracteres
                    </div>
                </div>

                <!-- Incluye y No Incluye en columna -->
                <div class="space-y-6">
                    <!-- Incluye -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Incluye</label>
                        <div class="flex gap-2 mb-3">
                            <input
                                v-model="nuevoItemIncluye"
                                type="text"
                                placeholder="Agregar nuevo elemento..."
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 transition-colors focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                @keyup.enter="agregarItemIncluye"
                            />
                            <button
                                type="button"
                                @click="agregarItemIncluye"
                                :disabled="!nuevoItemIncluye.trim()"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                            >
                                <FontAwesomeIcon :icon="faPlus" class="h-4"/>
                            </button>
                        </div>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <div v-for="(item, index) in incluyeLista" :key="index" class="flex items-center justify-between bg-gray-50 p-2 rounded-lg border">
                                <span class="flex-1">{{ item }}</span>
                                <button type="button" @click="eliminarItemIncluye(index)" class="text-red-500 hover:text-red-700 p-1">
                                    <FontAwesomeIcon :icon="faXmark" class="h-4"/>
                                </button>
                            </div>
                        </div>
                        <div v-if="incluyeLista.length === 0" class="text-gray-500 text-sm">
                            Sin elementos agregados.
                        </div>
                    </div>

                    <!-- No Incluye -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">No Incluye</label>
                        <div class="flex gap-2 mb-3">
                            <input
                                v-model="nuevoItemNoIncluye"
                                type="text"
                                placeholder="Agregar nuevo elemento..."
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 transition-colors focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                @keyup.enter="agregarItemNoIncluye"
                            />
                            <button
                                type="button"
                                @click="agregarItemNoIncluye"
                                :disabled="!nuevoItemNoIncluye.trim()"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                            >
                                <FontAwesomeIcon :icon="faPlus" class="h-4"/>
                            </button>
                        </div>
                        <div class="space-y-2 max-h-40 overflow-y-auto" v-if="noIncluyeLista.length > 0">
                            <div v-for="(item, index) in noIncluyeLista" :key="index" class="flex items-center justify-between bg-gray-50 p-2 rounded-lg border">
                                <span class="flex-1">{{ item }}</span>
                                <button type="button" @click="eliminarItemNoIncluye(index)" class="text-red-500 hover:text-red-700 p-1">
                                    <FontAwesomeIcon :icon="faXmark" class="h-4"/>
                                </button>
                            </div>
                        </div>
                        <div v-if="noIncluyeLista.length === 0" class="text-gray-500 text-sm">
                            Sin elementos agregados.
                        </div>
                    </div>
                </div>                <!-- Imágenes -->
                <div class="w-full flex flex-col">
                    <div class="flex items-center gap-4">
                        <label for="imagenes" class="w-24 flex items-center gap-1">Imágenes:</label>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <input type="file" id="imagenes" name="imagenes" accept="image/*" @change="onImageSelect" class="hidden" ref="fileInput"/>
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
                                    <span class="text-sm font-medium" :class="imagenPreviewList.length >= 1 ? 'text-red-600' : 'text-gray-600'">
                                        {{ imagenPreviewList.length }}/1
                                    </span>
                                    <span class="text-xs text-gray-500">imagen</span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mb-2">
                                Máximo 1 imagen por paquete • Formatos: JPG, PNG, GIF • Tamaño máximo: 2MB
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center ml-2 sm:ml-5">
                    <div v-for="(img, index) in imagenPreviewList" :key="index" class="relative w-full max-w-sm">
                        <img :src="img.url" alt="Vista previa" class="w-full h-48 object-cover rounded-lg border shadow"/>
                        <button @click="removeImage(index)" class="absolute top-2 right-2 bg-gray-600/80 hover:bg-gray-700/80 text-white font-bold py-1 px-2 rounded-full shadow transition-colors" style="transform: translate(50%, -50%)">
                            <FontAwesomeIcon :icon="faXmark" class="text-xs" />
                        </button>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-center gap-4 w-full mt-6">
                    <button
                        :class="[
                            'border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed',
                            !isFormValid || isLoading
                                ? 'bg-gray-400 text-white cursor-not-allowed'
                                : 'bg-red-500 hover:bg-red-700 text-white'
                        ]"
                        @click="saveOrUpdate"
                        :disabled="!isFormValid || isLoading"
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

        <!-- Modal Confirmar Eliminación -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Eliminar paquete"
            :modal="true"
            :style="dialogStyle"
            :closable="false"
            :draggable="false"
        >
            <div class="flex items-center gap-3">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                <div class="flex flex-col">
                    <span>¿Estás seguro de eliminar el paquete: <b>{{ paquete.nombre }}</b>?</span>
                    <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-center gap-4 w-full">
                    <button
                        type="button"
                        class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="deletePaquete"
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

        <!-- Modal Cambios sin guardar -->
        <Dialog
            v-model:visible="unsavedChangesDialog"
            header="Cambios sin guardar"
            :modal="true"
            :style="dialogStyle"
            :closable="false"
            :draggable="false"
        >
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
                        class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="closeDialogWithoutSaving"
                    >
                        <FontAwesomeIcon :icon="faSignOut" class="h-4" />
                        <span>Salir sin guardar</span>
                    </button>
                    <button
                        type="button"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="continueEditing"
                    >
                        <FontAwesomeIcon :icon="faPencil" class="h-4" />
                        <span>Continuar</span>
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- Modal Carrusel de Imágenes -->
        <Dialog
            v-model:visible="showImageCarouselDialog"
            header="Galería de Imágenes"
            :modal="true"
            :closable="false"
            :style="dialogStyle"
            :draggable="false"
        >
            <div v-if="selectedImages.length" class="flex flex-col items-center justify-center">
                <!-- Info de imagen actual -->
                <div class="mb-4 text-sm text-gray-600 text-center">
                    Imagen {{ carouselIndex + 1 }} de {{ selectedImages.length }}
                </div>

                <!-- Imagen actual -->
                <div class="relative mb-4">
                    <img
                        :src="selectedImages[carouselIndex]?.url"
                        :alt="selectedImages[carouselIndex]?.nombre"
                        class="max-h-96 max-w-full rounded-lg object-contain"
                    />
                </div>

                <!-- Indicadores de página -->
                <div v-if="selectedImages.length > 1" class="mb-4 flex gap-2">
                    <button
                        v-for="(image, index) in selectedImages"
                        :key="index"
                        @click="carouselIndex = index"
                        :class="[
                            'h-3 w-3 rounded-full transition-all',
                            index === carouselIndex ? 'bg-blue-500' : 'bg-gray-300 hover:bg-gray-400'
                        ]"
                    ></button>
                </div>

                <!-- Controles de navegación -->
                <div v-if="selectedImages.length > 1" class="flex gap-4">
                    <button
                        @click="carouselIndex = carouselIndex === 0 ? selectedImages.length - 1 : carouselIndex - 1"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-all duration-200 ease-in-out"
                    >
                        Anterior
                    </button>
                    <button
                        @click="carouselIndex = carouselIndex === selectedImages.length - 1 ? 0 : carouselIndex + 1"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-all duration-200 ease-in-out"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
            <div v-else class="text-center text-gray-500 py-8">No hay imágenes para este paquete.</div>
            <template #footer>
                <div class="flex justify-center w-full mt-6">
                    <button
                        type="button"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="showImageCarouselDialog = false"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        Cerrar
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- Modal Detalles del Paquete -->
        <Dialog
            v-model:visible="showImageDialog"
            header="Detalles del Paquete"
            :modal="true"
            :style="{ width: '90vw', maxWidth: '800px' }"
            :closable="false"
            :draggable="false"
        >
            <div v-if="selectedPaquete" class="space-y-6">
                <!-- Información principal -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ selectedPaquete.nombre }}</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Precio:</span>
                                <span class="ml-2 text-lg font-bold text-green-600">${{ parseFloat(selectedPaquete.precio).toFixed(2) }}</span>
                            </div>
                            <div v-if="selectedPaquete.descripcion">
                                <span class="text-sm font-medium text-gray-700">Descripción:</span>
                                <p class="mt-1 text-sm text-gray-600">{{ selectedPaquete.descripcion }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Imagen del paquete -->
                    <div v-if="selectedPaquete.imagenes && selectedPaquete.imagenes.length > 0" class="flex justify-center">
                        <div class="relative">
                            <img
                                :src="IMAGE_PATH + selectedPaquete.imagenes[0].nombre"
                                :alt="selectedPaquete.nombre"
                                class="w-full max-w-sm h-48 object-cover rounded-lg border shadow"
                            />
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center bg-gray-100 rounded-lg h-48">
                        <div class="text-center text-gray-500">
                            <FontAwesomeIcon :icon="faImages" class="h-12 w-12 mb-2" />
                            <p>Sin imagen</p>
                        </div>
                    </div>
                </div>

                <!-- Incluye -->
                <div v-if="selectedPaquete.incluye" class="space-y-2">
                    <h4 class="text-md font-semibold text-gray-900">✅ Incluye:</h4>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <ul class="space-y-1">
                            <li v-for="item in textoALista(selectedPaquete.incluye)" :key="item" class="flex items-start gap-2 text-sm text-gray-700">
                                <FontAwesomeIcon :icon="faCheck" class="h-4 w-4 text-green-600 mt-0.5 flex-shrink-0" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- No Incluye -->
                <div v-if="selectedPaquete.no_incluye" class="space-y-2">
                    <h4 class="text-md font-semibold text-gray-900">❌ No Incluye:</h4>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <ul class="space-y-1">
                            <li v-for="item in textoALista(selectedPaquete.no_incluye)" :key="item" class="flex items-start gap-2 text-sm text-gray-700">
                                <FontAwesomeIcon :icon="faXmark" class="h-4 w-4 text-red-600 mt-0.5 flex-shrink-0" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>


            </div>
            <template #footer>
                <div class="flex justify-center w-full mt-6">
                    <button
                        type="button"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                        @click="showImageDialog = false"
                    >
                        <FontAwesomeIcon :icon="faXmark" class="h-5" />
                        Cerrar
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- Toast Component -->
        <Toast />
    </AuthenticatedLayout>
</template>

<style>
/* Estilos para el paginador */
.p-paginator-current {
    margin-left: auto;
}

@media (min-width: 640px) {
    .p-paginator-current {
        margin-left: 0;
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
