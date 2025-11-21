<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import FileUpload from "primevue/fileupload";
import Toast from "primevue/toast";
import Select from "primevue/select";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faCheck, faEye, faExclamationTriangle, faFilter, faGlobe, faImages, faListDots, faPencil, faPlus, faSpinner, faTags, faTrashCan, faXmark } from '@fortawesome/free-solid-svg-icons';
import HotelModals from "./Components/HotelComponents/Modales.vue";
import axios from "axios";
import Carousel from "primevue/carousel";
axios.defaults.withCredentials = true;

const toast = useToast();

// 📊 Estados reactivos
const hoteles = ref([]);
const hotel = ref({
    id: null,
    nombre: "",
    direccion: "",
    descripcion: "",
    estado: "",
    provincia: null,
    imagenes: [],
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
const originalHotelData = ref(null);

// Variables para modales de hoteles
const moreActionsDialog = ref(false);
const showImageCarouselDialog = ref(false);
const selectedHotel = ref(null);

// Variables de loading
const isNavigatingToPaises = ref(false);
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const isUploadingImages = ref(false);
const isOpeningGallery = ref(false);
const isLoading = ref(false);
const isLoadingTable = ref(true);

// 📂 Datos de apoyo
const paises = ref([]);
const provincias = ref([]);
const provinciasFiltradasPorPais = ref([]);
const selectedPais = ref("");
const selectedProvincia = ref("");
const selectedEstado = ref("");

// 🔍 Filtros
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    provincia: { value: null, matchMode: FilterMatchMode.EQUALS },
    estado: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const estados = ref([
    { label: "Disponible", value: "DISPONIBLE" },
    { label: "No Disponible", value: "NO_DISPONIBLE" },
]);

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");
const carouselIndex = ref(0);
const fileInput = ref(null);

// 🎯 URLs y constantes
const url = "/api/hoteles";
const IMAGE_PATH = "/storage/hoteles/";

// 🔍 Hoteles filtrados
const filteredHoteles = computed(() => {
    let filtered = hoteles.value;

    // Filtro por búsqueda global
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(hotel =>
            hotel.nombre.toLowerCase().includes(searchTerm) ||
            (hotel.direccion && hotel.direccion.toLowerCase().includes(searchTerm)) ||
            (hotel.descripcion && hotel.descripcion.toLowerCase().includes(searchTerm)) ||

            (hotel.provincia?.nombre && hotel.provincia.nombre.toLowerCase().includes(searchTerm))
        );
    }

    // Filtro por provincia
    if (filters.value.provincia.value) {
        filtered = filtered.filter(hotel =>
            hotel.provincia_id == filters.value.provincia.value
        );
    }

    // Filtro por estado
    if (filters.value.estado.value) {
        filtered = filtered.filter(hotel => hotel.estado === filters.value.estado.value);
    }

    return filtered;
});

// Computed para verificar si se puede agregar más imágenes
const canAddMoreImages = computed(() => {
    return imagenPreviewList.value.length < 5;
});

// Computed para el texto del botón de imágenes
const imageButtonText = computed(() => {
    if (isUploadingImages.value) return 'Cargando...';
    if (isOpeningGallery.value) return 'Abriendo...';
    if (!canAddMoreImages.value) return 'Límite alcanzado';
    return 'Seleccionar';
});

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
});

// 👀 Watcher para detectar cambios en el modal
watch([hotel, imagenPreviewList, removedImages], () => {
    if (originalHotelData.value && dialog.value) {
        nextTick(() => {
            const current = {
                ...hotel.value,
                imagenes_actuales: [...imagenPreviewList.value]
            };

            const hasChanges = JSON.stringify(current) !== JSON.stringify({
                ...originalHotelData.value,
                imagenes_actuales: originalHotelData.value.imagenes_originales
            }) || removedImages.value.length > 0;

            const isCreatingNew = !originalHotelData.value.id;
            const hasAnyData = hotel.value.nombre ||
                              hotel.value.direccion ||
                              hotel.value.descripcion ||
                              hotel.value.estado ||
                              hotel.value.provincia ||
                              imagenPreviewList.value.length > 0;

            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true, flush: 'post' });

// 🔄 Función para resetear formulario
function resetForm() {
    hotel.value = {
        id: null,
        nombre: "",
        direccion: "",
        descripcion: "",
        estado: "",
        provincia: null,
        imagenes: [],
    };
    selectedPais.value = "";
    provinciasFiltradasPorPais.value = [];
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
}

// Función para cargar todos los datos con toast unificado
const cargarTodosLosDatos = async () => {
    try {
        // Mostrar toast de carga
        toast.add({
            severity: "info",
            summary: "Cargando datos...",
            life: 2000
        });

        await Promise.all([
            fetchHoteles(),
            fetchPaises(),
            fetchProvincias()
        ]);

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Datos cargados",
            life: 2000
        });
    } catch (error) {
        // Los errores específicos ya son manejados en cada función individual
    }
};

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
    cargarTodosLosDatos();
    forceSelectTruncation();
});

const fetchHoteles = async () => {
    try {
        const response = await axios.get(url);
        // Mapear imágenes para cada hotel, SOLO nombres de archivo
        hoteles.value = (response.data.data || response.data || []).map((hotel) => ({
            ...hotel,
            imagenes: (hotel.imagenes || []).map((img) =>
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
            detail: "No se pudieron cargar los hoteles",
            life: 4000,
        });
    }
};

const fetchHotelesWithToasts = async () => {
    isLoadingTable.value = true;

    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando hoteles...",
        life: 2000
    });

    try {
        const response = await axios.get(url);
        hoteles.value = (response.data.data || response.data || []).map((hotel) => ({
            ...hotel,
            imagenes: (hotel.imagenes || []).map((img) =>
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
            summary: "Hoteles cargados",
            life: 2000
        });

    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los hoteles.",
            life: 4000
        });
    } finally {
        isLoadingTable.value = false;
    }
};

const fetchPaises = async () => {
    try {
        const response = await axios.get("/api/paises");
        paises.value = response.data.data || response.data || [];
    } catch (err) {
        paises.value = [];
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los países",
            life: 4000,
        });
    }
};

const fetchProvincias = async (paisId = null) => {
    try {
        const url = paisId ? `/api/provincias?pais_id=${paisId}` : "/api/provincias";
        const response = await axios.get(url);
        const provinciasList = response.data.data || response.data || [];
        
        if (paisId) {
            provinciasFiltradasPorPais.value = provinciasList;
        } else {
            provincias.value = provinciasList;
        }
    } catch (err) {
        if (paisId) {
            provinciasFiltradasPorPais.value = [];
        } else {
            provincias.value = [];
        }
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las provincias",
            life: 4000,
        });
    }
};



// 🔍 Funciones para manejar filtros
const onPaisChange = async () => {
    // Limpiar provincia seleccionada cuando cambie el país
    hotel.value.provincia = null;
    
    if (selectedPais.value) {
        await fetchProvincias(selectedPais.value);
    } else {
        provinciasFiltradasPorPais.value = [];
    }
};

const onProvinciaFilterChange = () => {
    filters.value.provincia.value = selectedProvincia.value === "" ? null : selectedProvincia.value;
    forceSelectTruncation();
};

const onEstadoFilterChange = () => {
    filters.value.estado.value = selectedEstado.value === "" ? null : selectedEstado.value;
    forceSelectTruncation();
};

// 👀 Watchers para forzar truncado cuando cambien los valores
watch([selectedProvincia, selectedEstado], () => {
    forceSelectTruncation();
}, { deep: true });

const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        selectedPais.value = "";
        selectedProvincia.value = "";
        selectedEstado.value = "";
        filters.value.global.value = null;
        filters.value.provincia.value = null;
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

// Función para manejar el clic en el enlace de países
const handlePaisesClick = () => {
    isNavigatingToPaises.value = true;
    // El estado de loading se resetea automáticamente cuando se cambia de página
};



// 📝 CRUD Operations
const openNew = () => {
    resetForm();
    btnTitle.value = "Guardar";
    submitted.value = false;
    dialog.value = true;
    nextTick(() => {
        originalHotelData.value = {
            ...hotel.value,
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
};

const editHotel = async (h) => {
    resetForm();
    submitted.value = false;
    hotel.value = { ...h };

    hotel.value.provincia = h.provincia ? h.provincia.id : null;
    
    // Cargar el país correspondiente si hay provincia
    if (h.provincia && h.provincia.pais) {
        selectedPais.value = h.provincia.pais.id;
        await fetchProvincias(h.provincia.pais.id);
    }
    
    imagenPreviewList.value = Array.isArray(h.imagenes)
        ? h.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
        : [];
    hasUnsavedChanges.value = false;
    btnTitle.value = "Actualizar";
    dialog.value = true;
    nextTick(() => {
        originalHotelData.value = {
            ...hotel.value,
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
};

const saveOrUpdate = async () => {
    submitted.value = true;

    // Validar nombre específicamente
    if (!hotel.value.nombre || hotel.value.nombre.length < 3 || hotel.value.nombre.length > 100) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.",
            life: 4000
        });
        return;
    }

    // Validar dirección específicamente
    if (!hotel.value.direccion || hotel.value.direccion.length < 10 || hotel.value.direccion.length > 255) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.",
            life: 4000
        });
        return;
    }

    // Validar descripción específicamente
    if (!hotel.value.descripcion || hotel.value.descripcion.length < 10 || hotel.value.descripcion.length > 500) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.",
            life: 4000
        });
        return;
    }

    // Validar campos obligatorios
    if (!hotel.value.estado || !selectedPais.value || !hotel.value.provincia || imagenPreviewList.value.length === 0) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que todos los campos obligatorios estén completos.",
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
        formData.append("nombre", hotel.value.nombre || "");
        formData.append("direccion", hotel.value.direccion || "");
        formData.append("descripcion", hotel.value.descripcion || "");
        formData.append("estado", hotel.value.estado || "");
        formData.append("provincia_id", hotel.value.provincia || "");


        // Agregar imágenes nuevas
        imagenFiles.value.forEach(f => formData.append("imagenes[]", f));

        // Agregar imágenes eliminadas
        removedImages.value.forEach(img => {
            const fileName = img.includes("/") ? img.split("/").pop() : img;
            formData.append("removed_images[]", fileName);
        });

        let response;
        if (!hotel.value.id) {
            response = await axios.post(url, formData, {
                headers: {"Content-Type":"multipart/form-data"}
            });
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El hotel ha sido creado correctamente.",
                life: 5000
            });
        } else {
            formData.append("_method","PUT");
            response = await axios.post(`${url}/${hotel.value.id}`, formData, {
                headers: {"Content-Type":"multipart/form-data"}
            });
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El hotel ha sido actualizado correctamente.",
                life: 5000
            });
        }

        await fetchHoteles();
        dialog.value = false;
        hasUnsavedChanges.value = false;
        originalHotelData.value = null;
        resetForm();
    } catch (err) {
        toast.add({
            severity: "warn",
            summary: "Error de validación",
            detail: "Por favor revisa los campos e intenta nuevamente.",
            life: 6000
        });
    } finally {
        isLoading.value = false;
    }
};

const confirmDeleteHotel = (h) => {
    hotel.value = { ...h };
    deleteDialog.value = true;
};

// 🚀 Función para eliminar con mejor manejo de errores
const deleteHotel = async () => {
    isDeleting.value = true;
    try {
        await axios.delete(`${url}/${hotel.value.id}`);
        await fetchHoteles();
        deleteDialog.value = false;
        toast.add({
            severity: "success",
            summary: "¡Eliminado!",
            detail: "El hotel ha sido eliminado correctamente.",
            life: 5000
        });
    } catch (err) {
        deleteDialog.value = false;

        // 🎯 Manejo específico de errores 422 - Restricciones de integridad
        if (err.response?.status === 422) {
            const errorData = err.response.data;
            let mensaje = errorData.error || "El hotel está siendo utilizado en el sistema.";

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
        // 🎯 Manejo de errores 404 - Hotel no encontrado
        else if (err.response?.status === 404) {
            toast.add({
                severity: "error",
                summary: "Hotel no encontrado",
                detail: "El hotel que intentas eliminar no existe o ya fue eliminado.",
                life: 5000
            });
            // Recargar la lista para reflejar el estado actual
            await fetchHoteles();
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
                detail: `No se pudo eliminar el hotel: ${errorMsg}`,
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
    originalHotelData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// Funciones para manejar las acciones de modales
const handleGenerateReport = (hotel) => {
    toast.add({
        severity: "info",
        summary: "Generar Reporte",
        detail: `Funcionalidad de generar reporte del hotel "${hotel.nombre}" en desarrollo.`,
        life: 5000
    });
    moreActionsDialog.value = false;
};

const handleArchiveHotel = (hotel) => {
    toast.add({
        severity: "info",
        summary: "Archivar Hotel",
        detail: `Funcionalidad de archivar hotel "${hotel.nombre}" en desarrollo.`,
        life: 5000
    });
    moreActionsDialog.value = false;
};

// Funciones para manejar los modales de hoteles
const openMoreActionsModal = (hotelData) => {
    selectedHotel.value = hotelData;
    moreActionsDialog.value = true;
};

const handleViewDetails = (hotel) => {
    moreActionsDialog.value = false;
    selectedHotel.value = hotel;
    showImageDialog.value = true;
};

const openImageModal = (index) => {
    if (selectedHotel.value && selectedHotel.value.imagenes && selectedHotel.value.imagenes.length > 0) {
        selectedImages.value = selectedHotel.value.imagenes.map(img => {
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
        selectedHotel.value = event.data;
        showImageDialog.value = true;
    }
};

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

const viewImages = (imagenesHotel) => {
    selectedImages.value = (imagenesHotel||[]).map(img=>IMAGE_PATH+(typeof img==="string"?img:img.nombre));
    carouselIndex.value = 0;
    showImageCarouselDialog.value = true;
};

// ✅ Validaciones en tiempo real
const validateNombre = () => {
    if (hotel.value.nombre) {
        hotel.value.nombre = hotel.value.nombre.toUpperCase();
        if (hotel.value.nombre.length > 50) {
            hotel.value.nombre = hotel.value.nombre.substring(0, 50);
        }
    }
};

const validateDireccion = () => {
    if (hotel.value.direccion && hotel.value.direccion.length > 200) {
        hotel.value.direccion = hotel.value.direccion.substring(0, 200);
    }
};

const validateDescripcion = () => {
    if (hotel.value.descripcion) {
        hotel.value.descripcion = hotel.value.descripcion.toUpperCase();
        if (hotel.value.descripcion.length > 255) {
            hotel.value.descripcion = hotel.value.descripcion.substring(0, 255);
        }
    }
};

function getEstadoLabel(estado) {
    const found = estados.value.find((e) => e.value === estado);
    return found ? found.label : "";
}

function sanitizeDropdownInput(value) {
    return value.replace(/^\s+/, "").replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, "");
}

function onInputDropdown(field, event) {
    if (typeof event === "string") {
        hotel.value[field] = sanitizeDropdownInput(event);
    }
}

function onFilterDropdown(field, event) {
    if (event && event.value) {
        event.value = sanitizeDropdownInput(event.value);
    }
}

// Funciones para manejar paste (pegar) y convertir a mayúsculas
const handlePasteNombre = (event) => {
    event.preventDefault();
    const input = event.target;
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const upperCaseText = pastedText.toUpperCase();
    
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const currentText = hotel.value.nombre || '';
    
    // Insertar texto en la posición del cursor
    const newText = currentText.substring(0, start) + upperCaseText + currentText.substring(end);
    
    // Respetar límite de caracteres
    hotel.value.nombre = newText.length > 100 ? newText.substring(0, 100) : newText;
    
    // Restaurar posición del cursor después del texto pegado
    setTimeout(() => {
        const newPosition = start + upperCaseText.length;
        input.setSelectionRange(newPosition, newPosition);
    }, 0);
};

const handlePasteDescripcion = (event) => {
    event.preventDefault();
    const input = event.target;
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const upperCaseText = pastedText.toUpperCase();
    
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const currentText = hotel.value.descripcion || '';
    
    // Insertar texto en la posición del cursor
    const newText = currentText.substring(0, start) + upperCaseText + currentText.substring(end);
    
    // Respetar límite de caracteres
    hotel.value.descripcion = newText.length > 500 ? newText.substring(0, 500) : newText;
    
    // Restaurar posición del cursor después del texto pegado
    setTimeout(() => {
        const newPosition = start + upperCaseText.length;
        input.setSelectionRange(newPosition, newPosition);
    }, 0);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Hoteles" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Catálogo de Hoteles</h1>
                <p class="text-gray-600">Gestión completa del catálogo de hoteles</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Hoteles</h3>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                    <Link
                        :href="route('controlPaisesProvincias')"
                        @click="handlePaisesClick"
                        :class="{'opacity-50 cursor-not-allowed': isNavigatingToPaises}"
                        class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2">
                        <FontAwesomeIcon
                            :icon="isNavigatingToPaises ? faSpinner : faGlobe"
                            :class="{'animate-spin': isNavigatingToPaises, 'h-4': true}"
                        />
                        <span class="block sm:hidden">{{ isNavigatingToPaises ? 'Car...' : 'Países' }}</span>
                        <span class="hidden sm:block">{{ isNavigatingToPaises ? 'Cargando...' : 'Control países' }}</span>
                    </Link>
                    <button
                        class="bg-red-500 border flex justify-center border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                        @click="openNew">
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" />
                        <span class="block sm:hidden">Agregar</span>
                        <span class="hidden sm:block">Agregar hotel</span>
                    </button>
                </div>
            </div>

            <DataTable
                :value="filteredHoteles"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} hoteles"
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
                                    {{ filteredHoteles.length }} resultado{{ filteredHoteles.length !== 1 ? 's' : '' }}
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
                            <div>
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar hoteles..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div>
                                    <select
                                        v-model="selectedProvincia"
                                        @change="onProvinciaFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                    >
                                        <option value="" disabled selected hidden>Departamento/Provincia</option>
                                        <option
                                            v-for="provincia in provincias"
                                            :key="provincia.id"
                                            :value="provincia.id"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ provincia.nombre }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        v-model="selectedEstado"
                                        @change="onEstadoFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                    >
                                        <option value="" disabled selected hidden>Estado</option>
                                        <option
                                            v-for="estado in estados"
                                            :key="estado.value"
                                            :value="estado.value"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ estado.label }}
                                        </option>
                                    </select>
                                </div>
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

                <Column field="direccion" header="Dirección" class="w-28 hidden md:table-cell">
                    <template #body="slotProps">
                        <div
                            class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 100px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.direccion"
                        >
                            {{ slotProps.data.direccion }}
                        </div>
                    </template>
                </Column>

                <Column field="provincia.nombre" header="Departamento/Provincia" class="w-32 hidden sm:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.provincia?.nombre || 'Sin departamento/provincia' }}
                        </div>
                    </template>
                </Column>

                <Column field="estado" header="Estado" class="w-28">
                    <template #body="slotProps">
                        <span :class="'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' + (slotProps.data.estado === 'DISPONIBLE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')">
                            {{ getEstadoLabel(slotProps.data.estado) || slotProps.data.estado }}
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
                                @click="editHotel(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteHotel(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!--Modal de formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Hotel'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model.trim="hotel.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="100"
                                :class="{'p-invalid': submitted && (!hotel.nombre || hotel.nombre.length < 3 || hotel.nombre.length > 100)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md uppercase"
                                placeholder="HOTEL PARADISE, ETC."
                                @input="validateNombre"
                                @paste="handlePasteNombre"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="hotel.nombre && hotel.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres. Actual: {{ hotel.nombre.length }}/3
                        </small>
                        <small class="text-orange-500 ml-28" v-if="hotel.nombre && hotel.nombre.length >= 90 && hotel.nombre.length <= 100">
                            Caracteres restantes: {{ 100 - hotel.nombre.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !hotel.nombre">
                            El nombre es obligatorio.
                        </small>
                    </div>

                    <!-- Dirección -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label for="direccion" class="w-24 flex items-center gap-1 mt-2">
                                Dirección: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Textarea
                                v-model.trim="hotel.direccion"
                                id="direccion"
                                name="direccion"
                                :maxlength="255"
                                rows="3"
                                :class="{'p-invalid': submitted && (!hotel.direccion || hotel.direccion.length < 10 || hotel.direccion.length > 255)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                @input="validateDireccion"
                                placeholder="Calle 123, Ciudad, País, etc."
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="hotel.direccion && hotel.direccion.length < 10">
                            La dirección debe tener al menos 10 caracteres. Actual: {{ hotel.direccion.length }}/10
                        </small>
                        <small class="text-orange-500 ml-28" v-if="hotel.direccion && hotel.direccion.length >= 230 && hotel.direccion.length <= 255">
                            Caracteres restantes: {{ 255 - hotel.direccion.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !hotel.direccion">
                            La dirección es obligatoria.
                        </small>
                    </div>

                    <!-- Descripción -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label for="descripcion" class="w-24 flex items-center gap-1 mt-2">
                                Descripción: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Textarea
                                v-model.trim="hotel.descripcion"
                                id="descripcion"
                                name="descripcion"
                                :maxlength="500"
                                rows="4"
                                :class="{'p-invalid': submitted && (!hotel.descripcion || hotel.descripcion.length < 10 || hotel.descripcion.length > 500)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md uppercase"
                                @input="validateDescripcion"
                                @paste="handlePasteDescripcion"
                                placeholder="HOTEL DE LUJO CON TODAS LAS COMODIDADES, ETC."
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="hotel.descripcion && hotel.descripcion.length < 10">
                            La descripción debe tener al menos 10 caracteres. Actual: {{ hotel.descripcion.length }}/10
                        </small>
                        <small class="text-orange-500 ml-28" v-if="hotel.descripcion && hotel.descripcion.length >= 470 && hotel.descripcion.length <= 500">
                            Caracteres restantes: {{ 500 - hotel.descripcion.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !hotel.descripcion">
                            La descripción es obligatoria.
                        </small>
                    </div>

                    <!-- Estado -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4 sm:gap-5">
                            <label for="estado" class="w-36 sm:w-32 flex items-center gap-1">
                                Estado: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Select
                                v-model="hotel.estado"
                                :options="estados"
                                optionLabel="label"
                                optionValue="value"
                                id="estado"
                                name="estado"
                                class="w-full rounded-md border-2 border-gray-400 hover:border-gray-500"
                                placeholder="Seleccionar"
                                :class="{'p-invalid': submitted && !hotel.estado}"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !hotel.estado">
                            El estado es obligatorio.
                        </small>
                    </div>

                    <!-- Categoría -->
                    <!-- País -->-full flex flex-col">
                        <div class="flex items-center gap-4 sm:gap-5">
                            <label for="pais" class="w-36 sm:w-32 flex items-center gap-1">
                                País: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Select
                                v-model="selectedPais"
                                :options="paises"
                                optionLabel="nombre"
                                optionValue="id"
                                id="pais"
                                name="pais"
                                :filter="true"
                                filterPlaceholder="Buscar país..."
                                :showClear="true"
                                class="w-full rounded-md border-2 border-gray-400 hover:border-gray-500"
                                placeholder="Seleccionar país"
                                :class="{'p-invalid': submitted && !selectedPais}"
                                @change="onPaisChange"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !selectedPais">
                            El país es obligatorio.
                        </small>
                    </div>

                    <!-- Provincia -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4 sm:gap-5">
                            <label for="provincia" class="w-36 sm:w-32 flex items-center gap-1">
                                Depa/
                                <br/>
                                Provincia: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <Select
                                v-model="hotel.provincia"
                                :options="provinciasFiltradasPorPais"
                                optionLabel="nombre"
                                optionValue="id"
                                id="provincia"
                                name="provincia"
                                :filter="true"
                                filterPlaceholder="Buscar departamento/provincia..."
                                :showClear="true"
                                :disabled="!selectedPais"
                                class="w-full rounded-md border-2 border-gray-400 hover:border-gray-500"
                                :placeholder="selectedPais ? 'Seleccionar departamento/provincia' : 'Primero seleccione un país'"
                                :class="{'p-invalid': submitted && !hotel.provincia}"
                                @input="onInputDropdown('provincia', $event)"
                                @filter="onFilterDropdown('provincia', $event)"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !hotel.provincia">
                            El departamento/provincia es obligatorio.
                        </small>
                    </div>

                    <!-- Imágenes -->
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
                                    Máximo 5 imágenes por hotel • Formatos: JPG, PNG, GIF • Tamaño máximo: 2MB por imagen
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

            <!-- Componente de Modales de Hoteles -->
            <HotelModals
                v-model:visible="moreActionsDialog"
                v-model:details-visible="showImageDialog"
                v-model:carousel-visible="showImageCarouselDialog"
                v-model:delete-visible="deleteDialog"
                v-model:unsaved-changes-visible="unsavedChangesDialog"
                v-model:carousel-index="carouselIndex"
                :hotel="selectedHotel || hotel"
                :dialog-style="dialogStyle"
                :selected-images="selectedImages"
                :image-path="IMAGE_PATH"
                :is-deleting="isDeleting"
                @delete-hotel="deleteHotel"
                @cancel-delete="() => deleteDialog = false"
                @close-without-saving="closeDialogWithoutSaving"
                @continue-editing="continueEditing"
                @view-details="handleViewDetails"
                @open-image-modal="openImageModal"
            />
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

/* Estilo para inputs en mayúsculas */
.uppercase {
  text-transform: uppercase !important;
}
/* Fin del estilo para inputs en mayúsculas */
</style>
