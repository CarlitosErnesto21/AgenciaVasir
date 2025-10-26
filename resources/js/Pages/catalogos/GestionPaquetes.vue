<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faFilter, faImages, faPencil, faPlus, faSignOut, faTrashCan, faXmark, faSpinner, faListDots, faSuitcase } from "@fortawesome/free-solid-svg-icons";
import PaqueteModals from "./Components/GestionPaqueteComponents/Modales.vue";
import axios from "axios";
axios.defaults.withCredentials = true;
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

// 📊 Estados reactivos
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
const moreActionsDialog = ref(false);
const showDetailsModal = ref(false);
const showImageCarouselDialog = ref(false);
const selectedPaquete = ref(null);

// Variables de loading
const isLoading = ref(false);
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const isUploadingImages = ref(false);
const isOpeningGallery = ref(false);
const isLoadingTable = ref(true);

// 📂 Datos de apoyo
const paises = ref([]);
const provincias = ref([]);

// Variables responsive para diálogos
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

// Variables para listas dinámicas
const incluyeLista = ref([]);
const nuevoItemIncluye = ref("");

// Variables de loading mejoradas
const isNavigatingToCategorias = ref(false);

const handleResize = () => {
    if (typeof window !== 'undefined') windowWidth.value = window.innerWidth;
};


// 🔍 Filtros
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

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");
const carouselIndex = ref(0);
const fileInput = ref(null);

// 🎯 URLs y constantes
const url = "/api/paquetes";
const IMAGE_PATH = "/storage/paquetes/";

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
    await fetchPaquetesWithToasts();
    await fetchPaises();
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleResize);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleResize);
    }
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

const fetchPaquetesWithToasts = async () => {
    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando paquetes...",
        life: 2000
    });

    try {
        const { data } = await axios.get(url);
        // Forzar a array si es posible
        const paquetesArray = Array.isArray(data) ? data : (data.data || data.paquetes || []);
        paquetes.value = paquetesArray.map(p => ({
            ...p,
            pais: p.pais || (p.pais_id ? paises.value.find(pa => pa.id === p.pais_id) : null),
            provincia: p.provincia || (p.provincia_id ? provincias.value.find(pr => pr.id === p.provincia_id) : null),
            imagenes: (p.imagenes || []).map(img => typeof img === "string" ? img : img.nombre)
        }));

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Paquetes cargados",
            life: 2000
        });

    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los paquetes.", life: 4000 });
    }
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
    originalPaqueteData.value = null;
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

// 🖼️ Manejo de imágenes mejorado
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
    // Asegurar formato compatible con Laravel (YYYY-MM-DD HH:MM:SS) cuando sean Date
    if (paquete.value.fecha_salida instanceof Date) {
        formData.append("fecha_salida", paquete.value.fecha_salida.toISOString().slice(0, 19).replace('T', ' '));
    } else if (paquete.value.fecha_salida) {
        formData.append("fecha_salida", paquete.value.fecha_salida);
    }
    if (paquete.value.fecha_regreso instanceof Date) {
        formData.append("fecha_regreso", paquete.value.fecha_regreso.toISOString().slice(0, 19).replace('T', ' '));
    } else if (paquete.value.fecha_regreso) {
        formData.append("fecha_regreso", paquete.value.fecha_regreso);
    }
    imagenFiles.value.forEach(img => formData.append('imagenes[]', img));
    removedImages.value.forEach(img => {
        const fileName = img.includes("/") ? img.split("/").pop() : img;
        formData.append("removed_images[]", fileName);
    });
    isLoading.value = true;
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
        // Mostrar errores de validación devueltos por Laravel (422)
        if (err.response && err.response.status === 422 && err.response.data) {
            const errors = err.response.data.errors || err.response.data;
            console.error('Validation errors:', errors);
            if (typeof errors === 'object') {
                Object.keys(errors).forEach((key) => {
                    const msgs = errors[key];
                    if (Array.isArray(msgs)) {
                        msgs.forEach(m => toast.add({ severity: 'warn', summary: 'Validación', detail: m, life: 6000 }));
                    } else if (typeof msgs === 'string') {
                        toast.add({ severity: 'warn', summary: 'Validación', detail: msgs, life: 6000 });
                    }
                });
            } else if (typeof errors === 'string') {
                toast.add({ severity: 'warn', summary: 'Validación', detail: errors, life: 6000 });
            }
        } else {
            console.error(err);
            toast.add({ severity: "error", summary: "Error", detail: "Revisa los campos e intenta nuevamente.", life: 5000 });
        }
    } finally {
        isLoading.value = false;
    }
}
const confirmDeletePaquete = (row) => {
    paquete.value = { ...row };
    deleteDialog.value = true;
}
const deletePaquete = async () => {
    isDeleting.value = true;
    try {
        await axios.delete(`${url}/${paquete.value.id}`);
        await fetchPaquetes();
        deleteDialog.value = false;
        toast.add({ severity: "success", summary: "Eliminado", detail: "Paquete eliminado", life: 4000 });
    } catch (err) {
        if (err.response && err.response.status === 422 && err.response.data) {
            const errors = err.response.data.errors || err.response.data;
            console.error('Delete validation errors:', errors);
            if (typeof errors === 'object') {
                Object.keys(errors).forEach((key) => {
                    const msgs = errors[key];
                    if (Array.isArray(msgs)) {
                        msgs.forEach(m => toast.add({ severity: 'warn', summary: 'Validación', detail: m, life: 6000 }));
                    } else if (typeof msgs === 'string') {
                        toast.add({ severity: 'warn', summary: 'Validación', detail: msgs, life: 6000 });
                    }
                });
            } else if (typeof errors === 'string') {
                toast.add({ severity: 'warn', summary: 'Validación', detail: errors, life: 6000 });
            }
        } else {
            console.error(err);
            toast.add({ severity: "error", summary: "Error", detail: "No se pudo eliminar el paquete.", life: 5000 });
        }
    } finally {
        isDeleting.value = false;
    }
}

// --------- FUNCIONES PARA MODALES DE PAQUETES
const openMoreActionsModal = (paqueteData) => {
    selectedPaquete.value = paqueteData;
    moreActionsDialog.value = true;
};

const openDetailsModal = (paqueteData) => {
    selectedPaquete.value = paqueteData;
    showDetailsModal.value = true;
};

const openImageModal = (index) => {
    carouselIndex.value = index;
    selectedImages.value = selectedPaquete.value?.imagenes || [];
    showImageCarouselDialog.value = true;
};

const duplicatePaquete = (paqueteItem) => {
    // Crear un nuevo paquete basado en el seleccionado
    paquete.value = {
        id: null,
        nombre: `${paqueteItem.nombre} (Copia)`,
        descripcion: paqueteItem.descripcion,
        precio: paqueteItem.precio,
        duracion: paqueteItem.duracion,
        fecha_salida: paqueteItem.fecha_salida,
        fecha_regreso: paqueteItem.fecha_regreso,
        pais_id: paqueteItem.pais_id,
        provincia_id: paqueteItem.provincia_id,
        condiciones: paqueteItem.condiciones || [],
        recordatorio: paqueteItem.recordatorio || [],
        imagenes: [],
    };

    // Configurar las listas
    condicionesLista.value = Array.isArray(paqueteItem.condiciones) ? [...paqueteItem.condiciones] : [];
    recordatorioLista.value = Array.isArray(paqueteItem.recordatorio) ? [...paqueteItem.recordatorio] : [];

    imagenPreviewList.value = Array.isArray(paqueteItem.imagenes)
        ? paqueteItem.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
        : [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
    btnTitle.value = "Guardar";
    hasUnsavedChanges.value = false;

    // Establecer datos originales para duplicación
    originalPaqueteData.value = {
        ...paqueteItem,
        id: null,
        nombre: `${paqueteItem.nombre} (Copia)`,
        imagenes_originales: Array.isArray(paqueteItem.imagenes)
            ? paqueteItem.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
            : []
    };

    dialog.value = true;

    toast.add({
        severity: "info",
        summary: "Paquete duplicado",
        detail: "Se ha creado una copia del paquete para editar",
        life: 3000,
    });
};

const changePaqueteStatus = (paqueteItem) => {
    toast.add({
        severity: "info",
        summary: "Cambio de estado",
        detail: `Función para cambiar estado de: ${paqueteItem.nombre}`,
        life: 3000,
    });
};

const generatePaqueteReport = (paqueteItem) => {
    toast.add({
        severity: "info",
        summary: "Generar reporte",
        detail: `Generando reporte para: ${paqueteItem.nombre}`,
        life: 3000,
    });
};

const archivePaquete = (paqueteItem) => {
    toast.add({
        severity: "info",
        summary: "Archivar paquete",
        detail: `Archivando paquete: ${paqueteItem.nombre}`,
        life: 3000,
    });
};

// --------- FILTERS
const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        selectedPais.value = null;
        selectedProvincia.value = null;
        selectedFechaInicio.value = null;
        selectedFechaFin.value = null;
        filters.value.global.value = null;
        filters.value.pais_id.value = null;
        filters.value.provincia_id.value = null;
        filters.value.fecha_salida.value = null;

        toast.add({
            severity: "success",
            summary: "Filtros limpiados",
            life: 2000
        });
    } finally {
        isClearingFilters.value = false;
    }
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
    <AuthenticatedLayout>
        <Head title="Paquetes" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Catálogo de Paquetes</h1>
                <p class="text-gray-600">Gestión completa de paquetes turísticos</p>
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
                                    {{ filteredPaquetes.length }} resultado{{ filteredPaquetes.length !== 1 ? 's' : '' }}
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
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar paquetes..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-3">
                                <div>
                                    <select
                                        v-model="selectedPais"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                        :class="selectedPais === '' || selectedPais === null ? 'text-gray-400' : 'text-gray-900'"
                                    >
                                        <option value="" disabled selected hidden>País</option>
                                        <option
                                            v-for="pais in paises"
                                            :key="pais.id"
                                            :value="pais.id"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ pais.nombre }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        v-model="selectedProvincia"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                        :class="selectedProvincia === '' || selectedProvincia === null ? 'text-gray-400' : 'text-gray-900'"
                                    >
                                        <option value="" disabled selected hidden>Provincia</option>
                                        <option
                                            v-for="provincia in provincias"
                                            :key="provincia.id"
                                            :value="provincia.id"
                                            class="text-gray-900 truncate text-lg"
                                        >
                                            {{ provincia.nombre }}
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

                <Column field="incluye" header="Incluye" class="w-28 hidden md:table-cell">
                    <template #body="slotProps">
                        <div
                            class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 100px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.incluye"
                        >
                            {{ slotProps.data.incluye }}
                        </div>
                    </template>
                </Column>

                <Column field="pais.nombre" header="País" class="w-32 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.pais?.nombre || 'Sin país' }}
                        </div>
                    </template>
                </Column>
                <Column field="provincia.nombre" header="Provincia" class="w-32 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.provincia?.nombre || 'Sin provincia' }}
                        </div>
                    </template>
                </Column>
                <Column field="fecha_salida" header="Fecha" class="w-16">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.fecha_salida }}
                        </div>
                    </template>
                </Column>
                <Column field="precio" header="Precio" class="w-16">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{isNaN(Number(slotProps.data.precio)) ? "" : `$${Number(slotProps.data.precio).toFixed(2)}` }}
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
                                class="flex bg-green-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="openMoreActionsModal(slotProps.data)">
                                <FontAwesomeIcon :icon="faListDots" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Más</span>
                            </button>
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
            <!--Modal de formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Paquete'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model.trim="paquete.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="100"
                                :class="{'p-invalid': submitted && (!paquete.nombre || paquete.nombre.length < 6 || paquete.nombre.length > 100)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                placeholder="Paquete Costa Rica, etc."
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="paquete.nombre && paquete.nombre.length < 6">
                            El nombre debe tener al menos 6 caracteres. Actual: {{ paquete.nombre.length }}/6
                        </small>
                        <small class="text-orange-500 ml-28" v-if="paquete.nombre && paquete.nombre.length >= 90 && paquete.nombre.length <= 100">
                            Caracteres restantes: {{ 100 - paquete.nombre.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !paquete.nombre">
                            El nombre es obligatorio.
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
                    <!-- Provincia -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4 sm:gap-5">
                            <label for="provincia_id" class="w-36 sm:w-32 flex items-center gap-1">
                                Provincia: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <select
                                v-model="paquete.provincia_id"
                                id="provincia_id"
                                name="provincia_id"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md h-9 text-sm px-3 py-1 bg-white truncate"
                                :class="[
                                    {'border-red-500': submitted && !paquete.provincia_id},
                                    paquete.provincia_id === '' || paquete.provincia_id === null ? 'text-gray-400' : 'text-gray-900'
                                ]"
                            >
                                <option value="" disabled selected hidden>Provincia</option>
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
                        <small class="text-red-500 ml-28" v-if="submitted && !paquete.provincia_id">
                            La provincia es obligatoria.
                        </small>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="flex items-center gap-1 mb-2">Fecha salida:<span class="text-red-500 font-bold">*</span></label>
                            <DatePicker v-model="paquete.fecha_salida" class="w-full" :minDate="getMinDate()" :maxDate="getMaxDateSalida()" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Fecha de salida" />
                        </div>
                        <div class="flex-1">
                            <label class="flex items-center gap-1 mb-2">Fecha regreso:<span class="text-red-500 font-bold">*</span></label>
                            <DatePicker v-model="paquete.fecha_regreso" class="w-full" :minDate="getMinDateRegreso()" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Fecha de regreso" />
                        </div>
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
                                    v-model="paquete.precio"
                                    id="precio"
                                    name="precio"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full pl-8 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                    :class="{'p-invalid': submitted && (!paquete.precio || paquete.precio <= 0 || paquete.precio > 9999.99)}"
                                    placeholder="0.00"
                                />
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && (paquete.precio == null || paquete.precio <= 0 || paquete.precio > 9999.99)">
                            El precio es obligatorio, debe ser mayor a 0 y menor o igual a 9999.99.
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
                                    Máximo 5 imágenes por paquete • Formatos: JPG, PNG, GIF • Tamaño máximo: 2MB por imagen
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
                            @click="savePaquete"
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
            <!-- Modal de Eliminar Paquete -->
            <Dialog v-model:visible="deleteDialog" header="Eliminar paquete" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
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
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="deleteDialog = false" :disabled="isDeleting">
                            <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cancelar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
            <!-- Modal de Cambios sin guardar -->
            <Dialog v-model:visible="unsavedChangesDialog" header="Cambios sin guardar" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¡Tienes información sin guardar!</span>
                        <span class="text-red-600 text-sm font-medium mt-1">¿Deseas salir sin guardar?</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-3 w-full">
                        <button type="button" class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="closeDialogWithoutSaving">
                            <FontAwesomeIcon :icon="faSignOut" class="h-4" /><span>Salir sin guardar</span>
                        </button>
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="continueEditing">
                            <FontAwesomeIcon :icon="faPencil" class="h-4" /><span>Continuar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
            <!-- Modal de Imágenes -->
            <Dialog v-model:visible="showImageDialog" header="Imágenes del paquete" :modal="true" :closable="false" :style="{ width: '700px' }" :draggable="false">
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
            <!-- 🎭 Componente de modales separado -->
            <PaqueteModals
                v-model:visible="moreActionsDialog"
                v-model:detailsVisible="showDetailsModal"
                v-model:carouselVisible="showImageCarouselDialog"
                v-model:deleteVisible="deleteDialog"
                v-model:unsavedChangesVisible="unsavedChangesDialog"
                :paquete="selectedPaquete"
                :dialogStyle="dialogStyle"
                :selectedImages="selectedImages"
                :carouselIndex="carouselIndex"
                :imagePath="IMAGE_PATH"
                :isDeleting="isDeleting"
                @update:carouselIndex="carouselIndex = $event"
                @duplicate="duplicatePaquete"
                @changeStatus="changePaqueteStatus"
                @generateReport="generatePaqueteReport"
                @archive="archivePaquete"
                @viewDetails="openDetailsModal"
                @openImageModal="openImageModal"
                @deletePaquete="deletePaquete"
                @cancelDelete="deleteDialog = false"
                @closeWithoutSaving="closeDialogWithoutSaving"
                @continueEditing="continueEditing"
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
</style>
