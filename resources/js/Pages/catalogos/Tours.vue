<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick, onUnmounted } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faBusSimple, faCheck, faExclamationTriangle, faListDots, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark } from "@fortawesome/free-solid-svg-icons";
import DatePicker from "primevue/datepicker";
import TourModals from "./Components/TourComponents/Modales.vue";
import CambiarEstado from "./Components/TourComponents/CambiarEstado.vue";
import axios from "axios";

const toast = useToast();
// Variables para listas de incluye/no incluye
const incluyeLista = ref([]);
const noIncluyeLista = ref([]);
const nuevoItemIncluye = ref("");
const nuevoItemNoIncluye = ref("");
const tours = ref([]);
const tour = ref({
    id: null, nombre: "", categoria: null, incluye: "", no_incluye: "", cupo_min: null, cupo_max: null,
    punto_salida: "", fecha_salida: null, fecha_regreso: null, estado: "DISPONIBLE", precio: null, transporte_id: null, imagenes: [],
});
const imagenPreviewList = ref([]);
const imagenFiles = ref([]);
const removedImages = ref([]);
const selectedTours = ref(null);
const dialog = ref(false);
const deleteDialog = ref(false);
const moreActionsDialog = ref(false);
const unsavedChangesDialog = ref(false);
const submitted = ref(false);
const hasUnsavedChanges = ref(false);
const originalTourData = ref(null);

// Variables de loading para los botones
const isLoading = ref(false);
const isDeleting = ref(false);
const isUploadingImages = ref(false);
const isOpeningGallery = ref(false);
const isClearingFilters = ref(false);
const isNavigatingToTransportes = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    categoria: { value: null, matchMode: FilterMatchMode.EQUALS },
    'transporte.nombre': { value: null, matchMode: FilterMatchMode.EQUALS },
    estado: { value: null, matchMode: FilterMatchMode.EQUALS },
    fecha_salida: { value: null, matchMode: FilterMatchMode.DATE_IS },
});
const selectedCategoria = ref(null);
const selectedTipoTransporte = ref(null);
const selectedEstado = ref(null);
const selectedFechaInicio = ref(null);
const selectedFechaFin = ref(null);
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");
const horaRegresoCalendar = ref(null);
const url = "/api/tours";
const IMAGE_PATH = "/storage/tours/";
const tipoTransportes = ref([]);
const categoriasOptions = ref([
    { label: 'Nacional', value: 'NACIONAL' },
    { label: 'Internacional', value: 'INTERNACIONAL' }
]);
const estadosOptions = ref([
    { label: 'Disponible', value: 'DISPONIBLE' },
    { label: 'Agotado', value: 'AGOTADO' },
    { label: 'En Curso', value: 'EN_CURSO' },
    { label: 'Completado', value: 'COMPLETADO' },
    { label: 'Cancelado', value: 'CANCELADO' },
    { label: 'Suspendido', value: 'SUSPENDIDO' },
    { label: 'Reprogramado', value: 'REPROGRAMADO' }
]);
const showImageDialog = ref(false);
const showImageCarouselDialog = ref(false);
const showCambiarEstadoDialog = ref(false);
const selectedTour = ref(null);
const selectedImages = ref([]);
const carouselIndex = ref(0);
const fileInput = ref(null);

// Computed property para obtener tours filtrados
const filteredTours = computed(() => {
    let filtered = tours.value;
    // Filtro por búsqueda global
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(tour =>
            tour.nombre.toLowerCase().includes(searchTerm) ||
            (tour.incluye && tour.incluye.toLowerCase().includes(searchTerm)) ||
            (tour.no_incluye && tour.no_incluye.toLowerCase().includes(searchTerm)) ||
            tour.punto_salida.toLowerCase().includes(searchTerm)
        );
    }
    // Filtro por categoría
    if (filters.value.categoria.value) {
        filtered = filtered.filter(tour =>
            tour.categoria === filters.value.categoria.value
        );
    }
    // Filtro por tipo de transporte
    if (filters.value['transporte.nombre'].value) {
        filtered = filtered.filter(tour =>
            tour['transporte.nombre'] === filters.value['transporte.nombre'].value
        );
    }
    // Filtro por estado
    if (filters.value.estado.value) {
        filtered = filtered.filter(tour =>
            tour.estado === filters.value.estado.value
        );
    }
    // Filtro por rango de fechas de salida
    if (selectedFechaInicio.value || selectedFechaFin.value) {
        filtered = filtered.filter(tour => {
            if (!tour.fecha_salida) return false;
            const fechaTour = new Date(tour.fecha_salida);
            let cumpleFiltro = true;
            if (selectedFechaInicio.value) {
                const fechaInicio = new Date(selectedFechaInicio.value);
                cumpleFiltro = cumpleFiltro && fechaTour >= fechaInicio;
            }
            if (selectedFechaFin.value) {
                const fechaFin = new Date(selectedFechaFin.value);
                fechaFin.setHours(23, 59, 59, 999); // Incluir todo el día final
                cumpleFiltro = cumpleFiltro && fechaTour <= fechaFin;
            }
            return cumpleFiltro;
        });
    }
    return filtered;
});

// Watcher para detectar cambios en el modal
watch([tour, horaRegresoCalendar, incluyeLista, noIncluyeLista, imagenPreviewList, removedImages], () => {
    if (originalTourData.value && dialog.value) {
        // Usar nextTick para evitar que se active inmediatamente después de cargar datos
        nextTick(() => {
            const current = {
                ...tour.value,
                fecha_regreso: horaRegresoCalendar.value,
                incluye_lista: [...incluyeLista.value],
                no_incluye_lista: [...noIncluyeLista.value],
                imagenes_actuales: [...imagenPreviewList.value]
            };
            // Comparar datos actuales con originales
            const hasChanges = JSON.stringify(current) !== JSON.stringify({
                ...originalTourData.value,
                imagenes_actuales: originalTourData.value.imagenes_originales
            }) || removedImages.value.length > 0;
            // Para tours nuevos, también verificar si hay algún dato ingresado
            const isCreatingNew = !originalTourData.value.id;
            const hasAnyData = tour.value.nombre ||
                              tour.value.punto_salida ||
                              tour.value.fecha_salida ||
                              horaRegresoCalendar.value ||
                              tour.value.precio ||
                              tour.value.categoria ||
                              tour.value.transporte_id ||
                              incluyeLista.value.length > 0 ||
                              noIncluyeLista.value.length > 0 ||
                              imagenPreviewList.value.length > 0;

            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true, flush: 'post' });

function resetForm() {
    tour.value = {
        id: null,
        nombre: "",
        categoria: null,
        incluye: "",
        no_incluye: "",
        cupo_min: null,
        cupo_max: null,
        punto_salida: "",
        fecha_salida: null,
        fecha_regreso: null,
        estado: "DISPONIBLE",
        precio: null,
        transporte_id: null,
        imagenes: [],
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    horaRegresoCalendar.value = null;
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

// Obtener tours, categorías y tipos de transporte
onMounted(() => {
    fetchTours();
    fetchTipoTransportes();

    if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleResize);
    }
});

// Listener para cambios de tamaño de ventana
const handleResize = () => {
    windowWidth.value = window.innerWidth;
};

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleResize);
    }
});

const fetchTours = async () => {
    try {
        const response = await axios.get(url);
        tours.value = response.data.map((t) => ({
            ...t,
            imagenes: (t.imagenes || []).map((img) =>
                typeof img === "string" ? img : img.nombre
            ),
            transporte_nombre: t.transporte?.nombre || "",
            transporte_capacidad: t.transporte?.capacidad || "",
            // Agregar campos para filtros
            'transporte.nombre': t.transporte?.nombre,
        })).sort((a, b) => {
            // Ordenar por fecha de creación descendente (más recientes primero)
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA;
        });
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los tours.", life: 4000 });
    }
};

const fetchTipoTransportes = async () => {
    try {
        const response = await axios.get("/api/transportes");
        tipoTransportes.value = response.data || [];
    } catch {
        tipoTransportes.value = [];
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los transportes.", life: 4000 });
    }
};

// Funciones para manejar filtros
const onCategoriaFilterChange = () => {
    filters.value.categoria.value = selectedCategoria.value;
};

const onTipoTransporteFilterChange = () => {
    if (selectedTipoTransporte.value) {
        // Encontrar el transporte seleccionado para obtener su nombre
        const transporteSeleccionado = tipoTransportes.value.find(t => t.id === selectedTipoTransporte.value);
        filters.value['transporte.nombre'].value = transporteSeleccionado ? transporteSeleccionado.nombre : null;
    } else {
        filters.value['transporte.nombre'].value = null;
    }
};

const onEstadoFilterChange = () => {
    filters.value.estado.value = selectedEstado.value;
};

const onFechaInicioFilterChange = () => {
    // Si la fecha inicio es posterior a la fecha fin, limpiar fecha fin
    if (selectedFechaInicio.value && selectedFechaFin.value) {
        const fechaInicio = new Date(selectedFechaInicio.value);
        const fechaFin = new Date(selectedFechaFin.value);
        if (fechaInicio > fechaFin) {
            selectedFechaFin.value = null;
            toast.add({
                severity: "warn",
                summary: "Fecha ajustada",
                detail: "La fecha 'hasta' se limpió porque era anterior a la fecha 'desde'.",
                life: 3000
            });
        }
    }
};

const onFechaFinFilterChange = () => {
    // Si la fecha fin es anterior a la fecha inicio, limpiar fecha inicio
    if (selectedFechaInicio.value && selectedFechaFin.value) {
        const fechaInicio = new Date(selectedFechaInicio.value);
        const fechaFin = new Date(selectedFechaFin.value);
        if (fechaFin < fechaInicio) {
            selectedFechaInicio.value = null;
            toast.add({
                severity: "warn",
                summary: "Fecha ajustada",
                detail: "La fecha 'desde' se limpió porque era posterior a la fecha 'hasta'.",
                life: 3000
            });
        }
    }
};

const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        selectedCategoria.value = null;
        selectedTipoTransporte.value = null;
        selectedEstado.value = null;
        selectedFechaInicio.value = null;
        selectedFechaFin.value = null;
        filters.value.global.value = null;
        filters.value.categoria.value = null;
        filters.value['transporte.nombre'].value = null;
        filters.value.estado.value = null;
        filters.value.fecha_salida.value = null;

        toast.add({
            severity: "success",
            summary: "Filtros limpiados",
            detail: "Los filtros han sido restablecidos correctamente.",
            life: 3000
        });
    } finally {
        isClearingFilters.value = false;
    }
};

// Función para manejar el clic en el enlace de transportes
const handleTransportesClick = () => {
    isNavigatingToTransportes.value = true;
    // El estado de loading se resetea automáticamente cuando se cambia de página
};

const openNew = () => {
    resetForm();
    btnTitle.value = "Guardar";
    submitted.value = false; // Asegurar que no hay estado de validación
    dialog.value = true;
    // Configurar estado inicial para detectar cambios en tours nuevos
    nextTick(() => {
        originalTourData.value = {
            ...tour.value,
            fecha_regreso: horaRegresoCalendar.value,
            incluye_lista: [...incluyeLista.value],
            no_incluye_lista: [...noIncluyeLista.value],
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
};

const editTour = (t) => {
    resetForm();
    submitted.value = false; // Limpiar estado de validación
    tour.value = {
        ...t,
        transporte_id: t.transporte?.id || t.transporte_id || null,
    };
    // Cargar fecha_salida correctamente
    if (t.fecha_salida) {
        if (typeof t.fecha_salida === 'string') {
            tour.value.fecha_salida = new Date(t.fecha_salida);
        } else {
            tour.value.fecha_salida = t.fecha_salida;
        }
    }
    // Cargar fecha_regreso correctamente para horaRegresoCalendar
    if (t.fecha_regreso) {
        if (typeof t.fecha_regreso === 'string') {
            horaRegresoCalendar.value = new Date(t.fecha_regreso);
        } else {
            horaRegresoCalendar.value = t.fecha_regreso;
        }
    }
    imagenPreviewList.value = (t.imagenes || []).map(img => typeof img === "string" ? img : img.nombre);
    // Cargar listas desde el texto existente
    incluyeLista.value = textoALista(t.incluye);
    noIncluyeLista.value = textoALista(t.no_incluye);
    hasUnsavedChanges.value = false;
    btnTitle.value = "Actualizar";
    dialog.value = true;
    // Guardar datos originales después de que todo esté cargado
    nextTick(() => {
        originalTourData.value = {
            ...tour.value,
            fecha_regreso: horaRegresoCalendar.value,
            incluye_lista: [...incluyeLista.value],
            no_incluye_lista: [...noIncluyeLista.value],
            imagenes_originales: [...imagenPreviewList.value]
        };
        hasUnsavedChanges.value = false;
    });
};

const saveOrUpdate = async () => {
    submitted.value = true;
    if (!horaRegresoCalendar.value) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Por favor verifica que todos los campos obligatorios estén completos.", life: 4000 });
        return;
    }
    // Validar nombre específicamente
    if (!tour.value.nombre || tour.value.nombre.length < 10 || tour.value.nombre.length > 200) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.", life: 4000 });
        return;
    }
    // Validar punto_salida específicamente
    if (!tour.value.punto_salida || tour.value.punto_salida.length < 5 || tour.value.punto_salida.length > 200) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Por favor verifica que todos los campos obligatorios estén completos y cumplan los requisitos.", life: 4000 });
        return;
    }
    // Validar cupos si están definidos
    if (tour.value.cupo_min && tour.value.cupo_max && tour.value.cupo_min >= tour.value.cupo_max) {
        toast.add({ severity: "warn", summary: "Verificar datos", detail: "Por favor revisa los valores ingresados y corrige cualquier inconsistencia.", life: 4000 });
        return;
    }
    // Validar fechas
    if (!validateFechaSalida()) {
        toast.add({ severity: "warn", summary: "Verificar fechas", detail: "Por favor revisa las fechas ingresadas y asegúrate de que sean correctas.", life: 4000 });
        return;
    }
    if (!validateFechaRegreso()) {
        toast.add({ severity: "warn", summary: "Verificar fechas", detail: "Por favor revisa las fechas ingresadas y asegúrate de que sean correctas.", life: 4000 });
        return;
    }
    if (!tour.value.fecha_salida || !tour.value.precio || !tour.value.categoria || !tour.value.transporte_id || imagenPreviewList.value.length === 0) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Por favor verifica que todos los campos obligatorios estén completos.", life: 4000 });
        return;
    }
    // Validar tamaño de imágenes antes de enviar
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

    isLoading.value = true;
    try {
        const formData = new FormData();
        // Campos obligatorios con validación
        formData.append("nombre", tour.value.nombre || "");
        // Campos opcionales - solo agregar si tienen contenido
        if (incluyeLista.value.length > 0) {
            formData.append("incluye", listaATexto(incluyeLista.value));
        } else {
            formData.append("incluye", ""); // Enviar cadena vacía para limpiar el campo
        }
        formData.append("punto_salida", tour.value.punto_salida || "");
        // Formatear fecha_salida correctamente
        if (tour.value.fecha_salida instanceof Date) {
            formData.append("fecha_salida", tour.value.fecha_salida.toISOString().slice(0, 19).replace('T', ' '));
        } else if (tour.value.fecha_salida) {
            formData.append("fecha_salida", tour.value.fecha_salida);
        }
        // Formatear fecha_regreso correctamente
        if (horaRegresoCalendar.value instanceof Date) {
            formData.append("fecha_regreso", horaRegresoCalendar.value.toISOString().slice(0, 19).replace('T', ' '));
        } else if (horaRegresoCalendar.value) {
            formData.append("fecha_regreso", horaRegresoCalendar.value);
        }
        // Campos numéricos con validación
        formData.append("precio", tour.value.precio || "");
        formData.append("categoria", tour.value.categoria || "");
        formData.append("transporte_id", tour.value.transporte_id || "");

        // Agregar campos opcionales
        if (noIncluyeLista.value.length > 0) {
            formData.append("no_incluye", listaATexto(noIncluyeLista.value));
        } else {
            formData.append("no_incluye", ""); // Enviar cadena vacía para limpiar el campo
        }
        if (tour.value.cupo_min) {
            formData.append("cupo_min", tour.value.cupo_min);
        }
        if (tour.value.cupo_max) {
            formData.append("cupo_max", tour.value.cupo_max);
        }
        imagenFiles.value.forEach(f => formData.append("imagenes[]", f));
        removedImages.value.forEach(img => {
            // Manejo seguro para obtener el nombre del archivo
            const fileName = img.includes("/") ? img.split("/").pop() : img;
            formData.append("removed_images[]", fileName);
        });
        let response;
        if (!tour.value.id) {
            response = await axios.post(url, formData, { headers: {"Content-Type":"multipart/form-data"} });
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El tour ha sido creado correctamente.",
                life: 5000
            });
        } else {
            formData.append("_method","PUT");
            response = await axios.post(`${url}/${tour.value.id}`, formData, { headers: {"Content-Type":"multipart/form-data"} });
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El tour ha sido actualizado correctamente.",
                life: 5000
            });
        }
        await fetchTours();
        dialog.value = false;
        hasUnsavedChanges.value = false;
        originalTourData.value = null;
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

const confirmDeleteTour = (t) => { tour.value = { ...t }; deleteDialog.value = true; };

const deleteTour = async () => {
    isDeleting.value = true;
    try {
        await axios.delete(`${url}/${tour.value.id}`);
        await fetchTours();
        deleteDialog.value = false;
        toast.add({
            severity: "success",
            summary: "¡Eliminado!",
            detail: "El tour ha sido eliminado correctamente.",
            life: 5000
        });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo eliminar el tour. Inténtalo de nuevo.",
            life: 5000
        });
    } finally {
        isDeleting.value = false;
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
    originalTourData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// Función para manejar el clic del botón de subir imágenes
const handleImageUploadClick = async () => {
    isOpeningGallery.value = true;

    // Simular un pequeño delay para mostrar el estado "Abriendo galería"
    await new Promise(resolve => setTimeout(resolve, 300));

    // Hacer clic en el input file
    fileInput.value.click();

    // El estado se mantendrá hasta que se seleccionen archivos o se cancele
    // Se resetea en onImageSelect o cuando se detecta que no se seleccionó nada
    setTimeout(() => {
        if (!isUploadingImages.value) {
            isOpeningGallery.value = false;
        }
    }, 1000);
};

const onImageSelect = async (event) => {
    const files = event.target ? event.target.files : event.files;
    const maxSize = 2 * 1024 * 1024; // 2MB en bytes

    // Resetear el estado de apertura de galería
    isOpeningGallery.value = false;

    if (!files || files.length === 0) {
        // Si no se seleccionaron archivos, volver al estado inicial
        return;
    }

    // Cambiar a estado de carga
    isUploadingImages.value = true;

    try {
        const processingPromises = [];

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
                    continue; // Saltar este archivo
                }
                // Validar tipo de archivo
                if (!file.type.startsWith('image/')) {
                    toast.add({
                        severity: "warn",
                        summary: "Formato no válido",
                        detail: "Por favor selecciona únicamente archivos de imagen válidos.",
                        life: 4000
                    });
                    continue; // Saltar este archivo
                }

                imagenFiles.value.push(file);

                // Crear promesa para procesar la imagen
                const imagePromise = new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagenPreviewList.value.push(e.target.result);
                        resolve();
                    };
                    reader.readAsDataURL(file);
                });

                processingPromises.push(imagePromise);
            }
        }

        // Esperar a que todas las imágenes se procesen
        await Promise.all(processingPromises);

        // Mostrar mensaje de éxito si se procesaron imágenes
        if (processingPromises.length > 0) {
            toast.add({
                severity: "success",
                summary: "Imágenes cargadas",
                detail: `${processingPromises.length} imagen${processingPromises.length > 1 ? 'es' : ''} cargada${processingPromises.length > 1 ? 's' : ''} correctamente.`,
                life: 3000
            });
        }
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error al cargar imágenes",
            detail: "Hubo un problema al procesar las imágenes. Inténtalo de nuevo.",
            life: 4000
        });
    } finally {
        // Volver al estado inicial
        isUploadingImages.value = false;
        isOpeningGallery.value = false;

        // Limpiar el input file para permitir seleccionar los mismos archivos nuevamente
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

const openImageModal = (index) => {
    if (selectedTour.value && selectedTour.value.imagenes && selectedTour.value.imagenes.length > 0) {
        selectedImages.value = selectedTour.value.imagenes.map(img => {
            const imageName = typeof img === "string" ? img : img.nombre;
            return IMAGE_PATH + imageName;
        });
        carouselIndex.value = index;
        showImageCarouselDialog.value = true;
    } else {
        toast.add({
            severity: "warn",
            summary: "Sin imágenes",
            detail: "No hay imágenes disponibles para mostrar.",
            life: 3000
        });
    }
};

const validateNombre = () => {
    // Limitar a 200 caracteres máximo
    if (tour.value.nombre && tour.value.nombre.length > 200) {
        tour.value.nombre = tour.value.nombre.substring(0, 200);
    }
};

const validatePuntoSalida = () => {
    // Limitar a 200 caracteres máximo
    if (tour.value.punto_salida && tour.value.punto_salida.length > 200) {
        tour.value.punto_salida = tour.value.punto_salida.substring(0, 200);
    }
};

const validateCupos = () => {
    // Si se llena cupo_min, asegurar que cupo_max sea al menos cupo_min + 1
    if (tour.value.cupo_min && !tour.value.cupo_max) {
        // Si hay mínimo pero no máximo, sugerir un máximo
        return true;
    }
    // Si se llena cupo_max, asegurar que cupo_min sea menor
    if (tour.value.cupo_max && !tour.value.cupo_min) {
        // Si hay máximo pero no mínimo, está bien
        return true;
    }
    // Si ambos están llenos, validar la lógica
    if (tour.value.cupo_min && tour.value.cupo_max) {
        if (tour.value.cupo_min >= tour.value.cupo_max) {
            return false;
        }
    }
    return true;
};

const validateFechaSalida = () => {
    if (!tour.value.fecha_salida) return true;
    const now = new Date();
    const fechaSalida = new Date(tour.value.fecha_salida);
    // Validar que la fecha de salida sea futura (al menos 1 hora después de ahora)
    const minTime = new Date(now.getTime() + 60 * 60 * 1000); // 1 hora después de ahora
    if (fechaSalida < minTime) {
        return false;
    }

    // Si hay fecha de regreso, validar que salida sea anterior al regreso con mínimo 1 hora de diferencia
    if (horaRegresoCalendar.value) {
        const fechaRegreso = new Date(horaRegresoCalendar.value);
        const diferenciaHoras = (fechaRegreso - fechaSalida) / (1000 * 60 * 60);
        if (diferenciaHoras < 1) {
            return false;
        }
    }
    return true;
};

//Función para validar la fecha de regreso
const validateFechaRegreso = () => {
    if (!horaRegresoCalendar.value) return true;
    const fechaRegreso = new Date(horaRegresoCalendar.value);
    // Si hay fecha de salida, validar que regreso sea posterior a salida con mínimo 1 hora de diferencia
    if (tour.value.fecha_salida) {
        const fechaSalida = new Date(tour.value.fecha_salida);
        const diferenciaHoras = (fechaRegreso - fechaSalida) / (1000 * 60 * 60);
        // Validar que haya al menos 1 hora de diferencia
        if (diferenciaHoras < 1) {
            return false;
        }
    }
    return true;
};

// Función para obtener fecha mínima (1 hora desde ahora)
const getMinDate = () => {
    const now = new Date();
    now.setHours(now.getHours() + 1); // 1 hora desde ahora
    return now;
};

// Función para obtener fecha mínima de regreso (fecha salida + 1 hora)
const getMinDateRegreso = () => {
    if (!tour.value.fecha_salida) return getMinDate();
    const fechaSalida = new Date(tour.value.fecha_salida);
    fechaSalida.setHours(fechaSalida.getHours() + 1); // 1 hora después de la salida
    return fechaSalida;
};

// Función para obtener fecha máxima de salida (fecha regreso - 1 hora)
const getMaxDateSalida = () => {
    if (!horaRegresoCalendar.value) return null;
    const fechaRegreso = new Date(horaRegresoCalendar.value);
    fechaRegreso.setHours(fechaRegreso.getHours() - 1); // 1 hora antes del regreso
    return fechaRegreso;
};

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
    if (windowWidth.value < 640) { // sm
        return { width: '95vw', maxWidth: '380px' };
    } else if (windowWidth.value < 768) { // md
        return { width: '400px' };
    } else {
        return { width: '450px' };
    }
});

// Función para abrir el modal de más acciones (corregida para usar selectedTour)
const openMoreActionsModal = (tourData) => {
    selectedTour.value = tourData;
    moreActionsDialog.value = true;
};

// Funciones para manejar las acciones de los modales
const handleDuplicateTour = (tour) => {
    toast.add({
        severity: "info",
        summary: "Duplicar Tour",
        detail: `Funcionalidad de duplicar tour "${tour.nombre}" en desarrollo.`,
        life: 3000
    });
    moreActionsDialog.value = false;
};

const handleChangeStatus = (tour) => {
    moreActionsDialog.value = false;
    selectedTour.value = tour;
    showCambiarEstadoDialog.value = true;
};

const handleGenerateReport = (tour) => {
    toast.add({
        severity: "info",
        summary: "Generar Reporte",
        detail: `Funcionalidad de generar reporte del tour "${tour.nombre}" en desarrollo.`,
        life: 3000
    });
    moreActionsDialog.value = false;
};

const handleArchiveTour = (tour) => {
    toast.add({
        severity: "info",
        summary: "Archivar Tour",
        detail: `Funcionalidad de archivar tour "${tour.nombre}" en desarrollo.`,
        life: 3000
    });
    moreActionsDialog.value = false;
};

// Función para ver detalles desde el modal de Más Acciones
const handleViewDetails = (tour) => {
    moreActionsDialog.value = false;
    selectedTour.value = tour;
    showImageDialog.value = true;
};

// Función para manejar el clic en la fila
const onRowClick = (event) => {
    // Verificar si el clic fue en un botón para evitar abrir el modal
    const target = event.originalEvent.target;
    const isButton = target.closest('button');

    if (!isButton) {
        selectedTour.value = event.data;
        showImageDialog.value = true;
    }
};

// Función para manejar la actualización de estado
const handleEstadoActualizado = async (tourActualizado) => {
    // Actualizar el tour en la lista
    const index = tours.value.findIndex(t => t.id === tourActualizado.id);
    if (index !== -1) {
        tours.value[index] = tourActualizado;
    }
    showCambiarEstadoDialog.value = false;
};

// Función para prevenir teclas no válidas en campos numéricos
const onKeyDown = (event) => {
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

    // Limitar a 3 dígitos y máximo 500
    if (newValue.length > 3 || num > 500) {
        event.preventDefault();
        return;
    }
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
        }
        event.target.value = num.toString();
        // Triggear el evento input para actualizar v-model
        event.target.dispatchEvent(new Event('input', { bubbles: true }));
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

    // Validar que no exceda 999.99
    const newValue = currentValue + key;
    const numValue = parseFloat(newValue);
    if (numValue > 999.99) {
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

    // Limitar a máximo 999.99
    const limitedNum = Math.min(num, 999.99);

    // Formatear a máximo 2 decimales
    const formattedValue = limitedNum.toFixed(2);

    // Actualizar el modelo de Vue
    tour.precio = formattedValue;

    // También actualizar el campo input para sincronización
    event.target.value = formattedValue;
};

</script>
<template>
    <Head title="Tours" />
    <AuthenticatedLayout>
        <Toast class="z-[9999]" />
        <div class="px-auto md:px-2 mt-6">
            <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4">
                <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start ml-0 sm:ml-4">Catálogo de Tours</h3>
                <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end mr-0 sm:mr-4">
                    <Link
                        :href="route('transportes')"
                        @click="handleTransportesClick"
                        :class="{'opacity-50 cursor-not-allowed': isNavigatingToTransportes}"
                        class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2">
                        <FontAwesomeIcon
                            :icon="isNavigatingToTransportes ? faSpinner : faBusSimple"
                            :class="{'animate-spin': isNavigatingToTransportes, 'h-4': true}"
                        />
                        <span>{{ isNavigatingToTransportes ? 'Cargando...' : 'Control Transportes' }}</span>
                    </Link>
                    <button
                        class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300" @click="openNew">
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" /><span>&nbsp;Agregar Tour</span>
                    </button>
                </div>
            </div>
            <DataTable
                :value="filteredTours"
                v-model:selection="selectedTours"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tours"
                class="overflow-x-auto max-w-full px-auto"
                @row-click="onRowClick"

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
                                    {{ filteredTours.length }} resultado{{ filteredTours.length !== 1 ? 's' : '' }}
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
                                    <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
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
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar tours..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div>
                                    <Select v-model="selectedCategoria" :options="categoriasOptions" optionLabel="label" optionValue="value" placeholder="Categoría" class="w-full h-9 text-sm border" style="background-color: white; border-color: #93c5fd;"
                                        @change="onCategoriaFilterChange" :clearable="true"
                                    />
                                </div>
                                <div>
                                    <Select v-model="selectedTipoTransporte" :options="tipoTransportes" optionLabel="nombre" optionValue="id" placeholder="Transporte" class="w-full h-9 text-sm border" style="background-color: white; border-color: #93c5fd;"
                                        @change="onTipoTransporteFilterChange" :clearable="true"
                                    />
                                </div>
                                <div>
                                    <Select v-model="selectedEstado" :options="estadosOptions" optionLabel="label" optionValue="value" placeholder="Estado" class="w-full h-9 text-sm border" style="background-color: white; border-color: #93c5fd;"
                                        @change="onEstadoFilterChange" :clearable="true"
                                    />
                                </div>
                                <div class="col-span-2 sm:col-span-1 md:col-span-1 lg:col-span-1 hidden sm:block">
                                    <DatePicker
                                        v-model="selectedFechaInicio"
                                        placeholder="Fecha desde"
                                        class="w-full h-9 text-sm rounded-md border border-blue-300"
                                        @date-select="onFechaInicioFilterChange"
                                        @clear="onFechaInicioFilterChange"
                                        :showIcon="true"
                                        dateFormat="dd/mm/yy"
                                        :maxDate="selectedFechaFin"
                                    />
                                </div>
                                <div class="col-span-2 sm:col-span-1 md:col-span-1 lg:col-span-1 hidden sm:block">
                                    <DatePicker
                                        v-model="selectedFechaFin"
                                        placeholder="Fecha hasta"
                                        class="w-full h-9 text-sm rounded-md border border-blue-300"
                                        @date-select="onFechaFinFilterChange"
                                        @clear="onFechaFinFilterChange"
                                        :showIcon="true"
                                        dateFormat="dd/mm/yy"
                                        :minDate="selectedFechaInicio"
                                    />
                                </div>
                                <div class="flex w-80 sm:hidden">
                                    <DatePicker
                                        v-model="selectedFechaInicio"
                                        placeholder="Fecha desde"
                                        class="h-9 text-sm rounded-md"
                                        @date-select="onFechaInicioFilterChange"
                                        @clear="onFechaInicioFilterChange"
                                        :showIcon="true"
                                        dateFormat="dd/mm/yy"
                                        :maxDate="selectedFechaFin"
                                    />
                                    <DatePicker
                                        v-model="selectedFechaFin"
                                        placeholder="Fecha hasta"
                                        class="h-9 text-sm rounded-md"
                                        @date-select="onFechaFinFilterChange"
                                        @clear="onFechaFinFilterChange"
                                        :showIcon="true"
                                        dateFormat="dd/mm/yy"
                                        :minDate="selectedFechaInicio"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <Column field="nombre" header="Nombre" sortable class="w-36 min-w-24">
                    <template #body="slotProps">
                        <div
                            class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 85px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.nombre"
                        >
                            {{ slotProps.data.nombre }}
                        </div>
                    </template>
                </Column>
                <Column field="punto_salida" header="Punto salida" class="w-32 min-w-32 hidden md:table-cell">
                    <template #body="slotProps">
                        <div
                            class="text-sm leading-relaxed overflow-hidden"
                            style="max-width: 128px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.punto_salida"
                        >
                            {{ slotProps.data.punto_salida }}
                        </div>
                    </template>
                </Column>
                <Column field="fecha_salida" header="Fecha salida" class="w-36 min-w-36 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{slotProps.data.fecha_salida ? new Date(slotProps.data.fecha_salida).toLocaleString("es-ES",{day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", hour12: true,}): ""}}
                        </div>
                    </template>
                </Column>
                <Column field="precio" header="Precio" class="w-24 min-w-18">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{isNaN(Number(slotProps.data.precio)) ? "" : `$${Number(slotProps.data.precio).toFixed(2)}` }}
                        </div>
                    </template>
                </Column>
                <Column field="estado" header="Estado" class="w-32 min-w-24 hidden lg:table-cell">
                    <template #body="slotProps">
                        <span
                            :class="{
                                'bg-green-100 text-green-800': slotProps.data.estado === 'DISPONIBLE',
                                'bg-red-100 text-red-800': slotProps.data.estado === 'AGOTADO' || slotProps.data.estado === 'CANCELADO',
                                'bg-blue-100 text-blue-800': slotProps.data.estado === 'EN_CURSO',
                                'bg-gray-100 text-gray-800': slotProps.data.estado === 'COMPLETADO',
                                'bg-orange-100 text-orange-800': slotProps.data.estado === 'SUSPENDIDO',
                                'bg-purple-100 text-purple-800': slotProps.data.estado === 'REPROGRAMADO'
                            }"
                            class="px-2 py-1 rounded-full text-xs font-medium"
                        >
                            {{ estadosOptions.find(e => e.value === slotProps.data.estado)?.label || slotProps.data.estado }}
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
                                class="flex bg-green-500 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="openMoreActionsModal(slotProps.data)"
                                title="Más acciones">
                                <FontAwesomeIcon :icon="faListDots" class="h-4 w-7 text-white" />
                                <span class="hidden md:block text-white">Más</span>
                            </button>
                            <button
                                class="flex bg-blue-500 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editTour(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-4 w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteTour(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-4 w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Tour'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="flex items-center gap-1">Tour: <span class="text-red-500 font-bold">*</span></label>
                            <InputText v-model.trim="tour.nombre" id="nombre" name="nombre" :maxlength="200" :class="{'p-invalid': submitted && (!tour.nombre || tour.nombre.length < 10 || tour.nombre.length > 200), }" class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md" placeholder="Tour al Cerro El Pital, etc" @input="validateNombre"/>
                        </div>
                        <small class="text-red-500 ml-28" v-if="tour.nombre && tour.nombre.length < 10">
                            El nombre debe tener al menos 10 caracteres. Actual: {{ tour.nombre.length }}/10
                        </small>
                        <small class="text-orange-500 ml-28" v-if="tour.nombre && tour.nombre.length >= 180 && tour.nombre.length <= 200">
                            Caracteres restantes: {{ 200 - tour.nombre.length }}.
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !tour.nombre">
                            El nombre es obligatorio.
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label for="incluye" class="w-12 flex items-center gap-1 mt-2">
                                Incluye:
                            </label>
                            <div class="flex w-full flex-col">
                                <div class="flex gap-2 mb-3">
                                    <input v-model="nuevoItemIncluye" type="text" placeholder="Agregar nuevo elemento..."
                                        class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                        @keyup.enter="agregarItemIncluye"
                                    />
                                    <button type="button" @click="agregarItemIncluye" :disabled="!nuevoItemIncluye.trim()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
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
                                <!-- Mensaje cuando no hay items -->
                                <div v-if="incluyeLista.length === 0" class="text-gray-500 text-sm mt-2">
                                    Sin elementos agregados.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-start gap-4">
                            <label for="no_incluye" class="w-12 flex items-center gap-1 mt-2">
                                No incluye:
                            </label>
                            <div class="flex w-full flex-col">
                                <div class="flex gap-2 mb-3">
                                    <input v-model="nuevoItemNoIncluye" type="text" placeholder="Agregar nuevo elemento..."
                                        class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                        @keyup.enter="agregarItemNoIncluye"/>
                                    <button type="button" @click="agregarItemNoIncluye" :disabled="!nuevoItemNoIncluye.trim()"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
                                        <FontAwesomeIcon :icon="faPlus" class="h-5"/>
                                    </button>
                                </div>
                                <div class="space-y-2 max-h-40 overflow-y-auto" v-if="noIncluyeLista.length > 0">
                                    <div v-for="(item, index) in noIncluyeLista" :key="index" class="flex items-center justify-between bg-gray-50 p-2 rounded-md border">
                                        <span class="flex-1">{{ item }}</span>
                                        <button type="button" @click="eliminarItemNoIncluye(index)" class="text-red-500 hover:text-red-700 p-1">
                                            <FontAwesomeIcon :icon="faXmark" class="h-5"/>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="noIncluyeLista.length === 0" class="text-gray-500 text-sm mt-2">
                                    Sin elementos agregados.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <div class="flex items-start gap-4">
                            <label for="punto_salida" class="flex w-12 items-center">
                                Punto salida:
                                    <span class="text-red-500 font-bold">
                                        *
                                    </span>
                                </label>
                            <InputText v-model.trim="tour.punto_salida" id="punto_salida" name="punto_salida" :maxlength="200" :class="{'p-invalid': submitted && (!tour.punto_salida || tour.punto_salida.length < 5), }" placeholder="Atrio de Chalatenango, etc"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                @input="validatePuntoSalida"/>
                        </div>
                        <small class="text-red-500 ml-28" v-if="tour.punto_salida && tour.punto_salida.length < 5" >Debe tener al menos 5 caracteres. Actual: {{ tour.punto_salida.length }}/5</small>
                        <small class="text-orange-500 ml-28" v-if="tour.punto_salida && tour.punto_salida.length >= 180 && tour.punto_salida.length <= 200">Caracteres restantes: {{ 200 - tour.punto_salida.length }}</small>
                        <small class="text-red-500 ml-28" v-if="submitted && !tour.punto_salida">El punto de salida es obligatorio.</small>
                        <small class="text-red-500 ml-28" v-if="submitted && tour.punto_salida && tour.punto_salida.length < 5">El punto de salida debe tener al menos 5 caracteres.</small>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="w-full">
                            <label for="cupo_min" class="block mb-2">Cupo mínimo:</label>
                            <InputText
                                v-model="tour.cupo_min"
                                id="cupo_min"
                                name="cupo_min"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                max="500"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :class="{'p-invalid': tour.cupo_min && tour.cupo_max && tour.cupo_min >= tour.cupo_max,}"
                                placeholder="1-500"
                                @keydown="onKeyDown"
                                @paste="onPaste"
                                @input="validateCupos"
                            />
                            <small class="text-red-500 block text-xs mt-1" v-if="tour.cupo_min && tour.cupo_max && tour.cupo_min >= tour.cupo_max" >Debe ser menor al máximo</small>
                        </div>
                        <div class="w-full">
                            <label for="cupo_max" class="block mb-2">Cupo máximo:</label>
                            <InputText
                                v-model="tour.cupo_max"
                                id="cupo_max"
                                name="cupo_max"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                max="500"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :class="{'p-invalid': tour.cupo_min && tour.cupo_max && tour.cupo_max <= tour.cupo_min,}"
                                placeholder="1-500"
                                @keydown="onKeyDown"
                                @paste="onPaste"
                                @input="validateCupos"
                            />
                            <small class="text-red-500 block text-xs mt-1" v-if="tour.cupo_min && tour.cupo_max && tour.cupo_max <= tour.cupo_min">Debe ser mayor al mínimo</small>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label for="fecha_salida" class="flex items-center gap-1 mb-2">Fecha y hora de salida:<span class="text-red-500 font-bold">*</span></label>
                            <DatePicker v-model="tour.fecha_salida" id="fecha_salida" name="fecha_salida" showIcon showTime hourFormat="12" dateFormat="yy-mm-dd" :minDate="getMinDate()" :maxDate="getMaxDateSalida()"
                                :class="{'p-invalid': (submitted && !tour.fecha_salida) || (tour.fecha_salida && !validateFechaSalida()) }"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :manualInput="false" @dateSelect="validateFechaSalida" @input="validateFechaSalida"
                            />
                            <small class="text-red-500 block text-xs mt-1" v-if="submitted && !tour.fecha_salida" >Fecha y hora de salida requerida.</small>
                        </div>
                        <div class="flex-1">
                            <label for="horaRegresoCalendar" class="flex items-center gap-1 mb-2">Fecha y hora regreso:<span class="text-red-500 font-bold">*</span></label>
                            <DatePicker v-model="horaRegresoCalendar" id="horaRegresoCalendar" name="horaRegresoCalendar" showIcon showTime hourFormat="12" dateFormat="yy-mm-dd" :minDate="getMinDateRegreso()" :manualInput="false"
                                :class="{'p-invalid': (submitted && !horaRegresoCalendar) || (horaRegresoCalendar && !validateFechaRegreso()) }"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                @dateSelect="validateFechaRegreso" @input="validateFechaRegreso"/>
                            <small class="text-red-500 block text-xs mt-1" v-if="submitted && !horaRegresoCalendar">Fecha y hora de regreso requerida.</small>
                        </div>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-10 sm:gap-9">
                            <label for="precio" class="w-24 flex items-center gap-1">Precio:<span class="text-red-500 font-bold">*</span></label>
                            <div class="w-full relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 pointer-events-none">$</span>
                                <InputText
                                    v-model="tour.precio"
                                    id="precio"
                                    name="precio"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full pl-8 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                    :class="{'p-invalid': submitted && (!tour.precio || tour.precio <= 0 || tour.precio > 999.99),}"
                                    placeholder="0.00"
                                    @keydown="onPriceKeyDown"
                                    @paste="onPricePaste"
                                />
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if=" submitted && (tour.precio == null || tour.precio <= 0 || tour.precio > 999.99)">El precio es obligatorio, debe ser mayor a 0 y menor o igual a 999.99.</small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-5">
                            <label for="categoria" class="w-24 flex items-center gap-1">Categoría:<span class="text-red-500 font-bold">*</span></label>
                            <Select v-model="tour.categoria" :options="categoriasOptions" optionLabel="label" optionValue="value" id="categoria" name="categoria"
                                class="w-full rounded-md border-2 border-gray-400 hover:border-gray-500" placeholder="Seleccionar" :class="{'p-invalid': submitted && !tour.categoria,}"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !tour.categoria">La categoría es obligatoria.</small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-3.5">
                            <label for="transporte_id" class="w-24 flex items-center gap-1">Transporte:<span class="text-red-500 font-bold">*</span></label>
                            <Select v-model="tour.transporte_id" :options="tipoTransportes" optionLabel="nombre" optionValue="id" id="transporte_id" name="transporte_id"
                            class="w-full rounded-md border-2 border-gray-400 hover:border-gray-500" placeholder="Seleccionar" :class="{'p-invalid': submitted && !tour.transporte_id, }" />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !tour.transporte_id">El tipo de transporte es obligatorio.</small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="imagenes" class="w-24 flex items-center gap-1">Imágenes:<span class="text-red-500 font-bold">*</span></label>
                            <div class="flex-1">
                                <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple @change="onImageSelect" class="hidden" ref="fileInput"/>
                                <button type="button"
                                    class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 outline-none focus:outline-none active:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="handleImageUploadClick"
                                    :disabled="isUploadingImages || isOpeningGallery">
                                    <FontAwesomeIcon
                                        :icon="isUploadingImages ? faSpinner : (isOpeningGallery ? faSpinner : faPlus)"
                                        :class="['h-4', { 'animate-spin': isUploadingImages || isOpeningGallery }]"
                                    />
                                    <span v-if="isOpeningGallery">Abriendo galería...</span>
                                    <span v-else-if="isUploadingImages">Cargando imágenes...</span>
                                    <span v-else>Subir imágenes</span>
                                </button>
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && imagenPreviewList.length === 0">Las imágenes son obligatorias (al menos una).</small>
                    </div>
                    <div class="grid grid-cols-2 ml-8 sm:ml-16">
                        <div v-for="(img, index) in imagenPreviewList" :key="index" class="relative w-32 h-32">
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
            <Dialog v-model:visible="deleteDialog" header="Eliminar tour" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar el tour: <b>{{ tour.nombre }}</b>?</span>
                        <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="deleteTour"
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

            <!-- Componente de Modales de Tours -->
            <TourModals
                v-model:visible="moreActionsDialog"
                v-model:details-visible="showImageDialog"
                v-model:carousel-visible="showImageCarouselDialog"
                v-model:carousel-index="carouselIndex"
                :tour="selectedTour || tour"
                :dialog-style="dialogStyle"
                :selected-images="selectedImages"
                :image-path="IMAGE_PATH"
                @duplicate="handleDuplicateTour"
                @change-status="handleChangeStatus"
                @generate-report="handleGenerateReport"
                @archive="handleArchiveTour"
                @view-details="handleViewDetails"
                @open-image-modal="openImageModal"
            />

            <!-- Modal de Cambiar Estado -->
            <CambiarEstado
                v-model:visible="showCambiarEstadoDialog"
                :tour="selectedTour"
                :dialog-style="dialogStyle"
                @estado-actualizado="handleEstadoActualizado"
            />

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
