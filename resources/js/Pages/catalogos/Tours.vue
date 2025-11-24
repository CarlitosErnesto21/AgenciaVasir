<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick, onUnmounted } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faBusSimple, faCheck, faHandPointUp, faListDots, faPencil, faPlus, faSpinner, faTrashCan, faXmark } from "@fortawesome/free-solid-svg-icons";
import DatePicker from "primevue/datepicker";
import TourModals from "./Components/TourComponents/Modales.vue";
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
const isLoadingTable = ref(true);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    categoria: { value: null, matchMode: FilterMatchMode.EQUALS },
    'transporte.nombre': { value: null, matchMode: FilterMatchMode.EQUALS },
    estado: { value: null, matchMode: FilterMatchMode.EQUALS },
    fecha_salida: { value: null, matchMode: FilterMatchMode.DATE_IS },
});
const selectedCategoria = ref("");
const selectedTipoTransporte = ref("");
const selectedEstado = ref("");
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
    { label: 'Completo', value: 'COMPLETO' },
    { label: 'En Curso', value: 'EN_CURSO' },
    { label: 'Finalizado', value: 'FINALIZADO' },
    { label: 'Cancelada', value: 'CANCELADA' },
    { label: 'Reprogramada', value: 'REPROGRAMADA' }
]);
const showImageDialog = ref(false);
const showImageCarouselDialog = ref(false);
// Removido showCambiarEstadoDialog ya que no se usará más
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

// Computed para validar cupos con mensajes específicos
const cupoMinError = computed(() => {
    if (!tour.value.cupo_min) return '';

    const cupoMin = parseInt(tour.value.cupo_min) || 0;
    const cupoMax = parseInt(tour.value.cupo_max) || 0;

    // Validar que el cupo mínimo sea mayor a 0
    if (cupoMin <= 0) {
        return 'El cupo mínimo debe ser al menos 1.';
    }

    const transporteSeleccionado = tipoTransportes.value.find(t => t.id === tour.value.transporte_id);
    const capacidadTransporte = transporteSeleccionado ? transporteSeleccionado.capacidad : null;

    if (capacidadTransporte && cupoMin > capacidadTransporte) {
        return `El cupo mínimo no puede ser mayor que la capacidad del transporte (${capacidadTransporte}).`;
    }

    if (tour.value.cupo_max && cupoMax > 0 && cupoMin >= cupoMax) {
        return 'El cupo mínimo debe ser menor que el cupo máximo.';
    }

    return '';
});

const cupoMaxError = computed(() => {
    if (!tour.value.cupo_max) return '';

    const cupoMin = parseInt(tour.value.cupo_min) || 0;
    const cupoMax = parseInt(tour.value.cupo_max) || 0;

    // Validar que el cupo máximo sea mayor a 0
    if (cupoMax <= 0) {
        return 'El cupo máximo debe ser al menos 1.';
    }

    const transporteSeleccionado = tipoTransportes.value.find(t => t.id === tour.value.transporte_id);
    const capacidadTransporte = transporteSeleccionado ? transporteSeleccionado.capacidad : null;

    if (capacidadTransporte && cupoMax > capacidadTransporte) {
        return `El cupo máximo no puede ser mayor que la capacidad del transporte (${capacidadTransporte}).`;
    }

    if (tour.value.cupo_min && cupoMin > 0 && cupoMax <= cupoMin) {
        return 'El cupo máximo debe ser mayor que el cupo mínimo.';
    }

    return '';
});

// Computed para obtener el límite máximo de cupos basado en el transporte seleccionado
const maxCupoAllowed = computed(() => {
    const transporteSeleccionado = tipoTransportes.value.find(t => t.id === tour.value.transporte_id);
    return transporteSeleccionado ? transporteSeleccionado.capacidad : 30;
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

// Watcher para ajustar cupos cuando cambia el transporte
watch(() => tour.value.transporte_id, (newTransporteId, oldTransporteId) => {
    if (newTransporteId && newTransporteId !== oldTransporteId && dialog.value) {
        const transporteSeleccionado = tipoTransportes.value.find(t => t.id === newTransporteId);
        if (transporteSeleccionado) {
            const capacidadMaxima = transporteSeleccionado.capacidad;

            // Ajustar cupo_min si excede la nueva capacidad
            if (tour.value.cupo_min && tour.value.cupo_min > capacidadMaxima) {
                tour.value.cupo_min = capacidadMaxima;
            }

            // Ajustar cupo_max si excede la nueva capacidad
            if (tour.value.cupo_max && tour.value.cupo_max > capacidadMaxima) {
                tour.value.cupo_max = capacidadMaxima;
            }
        }
    }
});

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

// ?? Función para forzar truncado en selects
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

// Obtener tours, categorías y tipos de transporte
onMounted(() => {
    fetchToursWithToasts();
    fetchTipoTransportes();
    forceSelectTruncation();

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
    isLoadingTable.value = true;

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
    } finally {
        isLoadingTable.value = false;
    }
};

const fetchToursWithToasts = async () => {
    isLoadingTable.value = true;

    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando tours...",
        life: 2000
    });

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

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Tours cargados",
            life: 2000
        });

    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los tours.", life: 4000 });
    } finally {
        isLoadingTable.value = false;
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
    filters.value.categoria.value = selectedCategoria.value === "" ? null : selectedCategoria.value;
    forceSelectTruncation();
};

const onTipoTransporteFilterChange = () => {
    if (selectedTipoTransporte.value && selectedTipoTransporte.value !== "") {
        // Encontrar el transporte seleccionado para obtener su nombre
        const transporteSeleccionado = tipoTransportes.value.find(t => t.id === selectedTipoTransporte.value);
        filters.value['transporte.nombre'].value = transporteSeleccionado ? transporteSeleccionado.nombre : null;
    } else {
        filters.value['transporte.nombre'].value = null;
    }
    forceSelectTruncation();
};

const onEstadoFilterChange = () => {
    filters.value.estado.value = selectedEstado.value === "" ? null : selectedEstado.value;
    forceSelectTruncation();
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
                life: 5000
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
                life: 5000
            });
        }
    }
};

// ?? Watchers para forzar truncado cuando cambien los valores
watch([selectedCategoria, selectedTipoTransporte, selectedEstado], () => {
    forceSelectTruncation();
}, { deep: true });

const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        selectedCategoria.value = "";
        selectedTipoTransporte.value = "";
        selectedEstado.value = "";
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
            life: 2000
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
    // Validar cupos con la capacidad del transporte
    if (cupoMinError.value) {
        toast.add({ severity: "warn", summary: "Error en cupo mínimo", detail: cupoMinError.value, life: 4000 });
        return;
    }
    if (cupoMaxError.value) {
        toast.add({ severity: "warn", summary: "Error en cupo máximo", detail: cupoMaxError.value, life: 4000 });
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
    if (!tour.value.fecha_salida || !tour.value.precio || !tour.value.categoria || !tour.value.transporte_id || !tour.value.cupo_min || !tour.value.cupo_max || imagenPreviewList.value.length === 0) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "Por favor verifica que todos los campos obligatorios estén completos.", life: 4000 });
        return;
    }

    // Validar límite de imágenes
    if (imagenPreviewList.value.length > 5) {
        toast.add({
            severity: "warn",
            summary: "Demasiadas imágenes",
            detail: "No puedes subir más de 5 imágenes por tour.",
            life: 4000
        });
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
        // Formatear fecha_salida correctamente (mantener zona horaria local)
        if (tour.value.fecha_salida instanceof Date) {
            const fecha = tour.value.fecha_salida;
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            const hours = String(fecha.getHours()).padStart(2, '0');
            const minutes = String(fecha.getMinutes()).padStart(2, '0');
            const seconds = String(fecha.getSeconds()).padStart(2, '0');
            formData.append("fecha_salida", `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`);
        } else if (tour.value.fecha_salida) {
            formData.append("fecha_salida", tour.value.fecha_salida);
        }
        // Formatear fecha_regreso correctamente (mantener zona horaria local)
        if (horaRegresoCalendar.value instanceof Date) {
            const fecha = horaRegresoCalendar.value;
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            const hours = String(fecha.getHours()).padStart(2, '0');
            const minutes = String(fecha.getMinutes()).padStart(2, '0');
            const seconds = String(fecha.getSeconds()).padStart(2, '0');
            formData.append("fecha_regreso", `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`);
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
    const maxImages = 5; // Límite máximo de imágenes por tour

    // Resetear el estado de apertura de galería
    isOpeningGallery.value = false;

    if (!files || files.length === 0) {
        // Si no se seleccionaron archivos, volver al estado inicial
        return;
    }

    // Verificar límite total de imágenes
    const imagenesActuales = imagenPreviewList.value.length;
    const imagenesNuevas = files.length;
    const totalImagenes = imagenesActuales + imagenesNuevas;

    if (totalImagenes > maxImages) {
        const imagenesPermitidas = maxImages - imagenesActuales;
        toast.add({
            severity: "warn",
            summary: "Límite de imágenes excedido",
            detail: `Solo puedes subir un máximo de ${maxImages} imágenes por tour. Actualmente tienes ${imagenesActuales} imagen${imagenesActuales !== 1 ? 'es' : ''}, puedes agregar máximo ${imagenesPermitidas} más.`,
            life: 6000
        });
        return;
    }

    // Cambiar a estado de carga
    isUploadingImages.value = true;

    try {
        const processingPromises = [];
        let validFilesCount = 0;

        for (const file of files) {
            if (file instanceof File) {
                // Validar tamaño del archivo
                if (file.size > maxSize) {
                    toast.add({
                        severity: "warn",
                        summary: "Archivo muy grande",
                        detail: `El archivo "${file.name}" excede el tamaño máximo de 2MB.`,
                        life: 5000
                    });
                    continue; // Saltar este archivo
                }
                // Validar tipo de archivo
                if (!file.type.startsWith('image/')) {
                    toast.add({
                        severity: "warn",
                        summary: "Formato no válido",
                        detail: `El archivo "${file.name}" no es una imagen válida.`,
                        life: 4000
                    });
                    continue; // Saltar este archivo
                }

                imagenFiles.value.push(file);
                validFilesCount++;

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
        if (validFilesCount > 0) {
            toast.add({
                severity: "success",
                summary: "Imágenes cargadas",
                detail: `${validFilesCount} imagen${validFilesCount > 1 ? 'es' : ''} cargada${validFilesCount > 1 ? 's' : ''} correctamente. Total: ${imagenPreviewList.value.length}/${maxImages}`,
                life: 4000
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
        // Preparar las imágenes para el carrusel
        selectedImages.value = selectedTour.value.imagenes.map(img => {
            const imageName = typeof img === "string" ? img : img.nombre;
            return IMAGE_PATH + imageName;
        });

        // Asegurar que el índice esté dentro del rango válido
        const validIndex = Math.max(0, Math.min(index, selectedImages.value.length - 1));

        // Establecer el índice del carrusel ANTES de abrir el modal
        carouselIndex.value = validIndex;

        // Abrir el modal después de un pequeño delay para asegurar que todo esté listo
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

const validateNombre = () => {
    if (tour.value.nombre) {
        // Convertir a mayúsculas primero
        tour.value.nombre = tour.value.nombre.toUpperCase();

        // ? NO permite: @#$%&*()[]{}!¿?.,;:-_+=|\/~`"'<>
        tour.value.nombre = tour.value.nombre.replace(/[^A-ZÁÉÍÓÚÑ0-9\s]/g, '');

        // Reemplazar múltiples espacios consecutivos con uno solo
        tour.value.nombre = tour.value.nombre.replace(/\s+/g, ' ');

        // Limitar a 200 caracteres máximo
        if (tour.value.nombre.length > 200) {
            tour.value.nombre = tour.value.nombre.substring(0, 200);
        }

        // Eliminar espacios al inicio y al final
        tour.value.nombre = tour.value.nombre.trim();
    }
};

// Prevenir la entrada de caracteres especiales en tiempo real
const preventSpecialChars = (event) => {
    // Solo permitir: letras (a-z, A-Z), números (0-9), espacios, y caracteres acentuados (áéíóúñÁÉÍÓÚÑ)
    const allowedPattern = /[a-zA-ZáéíóúñÁÉÍÓÚÑ0-9\s]/;

    if (!allowedPattern.test(event.key)) {
        event.preventDefault();
    }
};

// Función para manejar paste en el campo nombre
const onNombrePaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');

    if (paste) {
        // Convertir a mayúsculas y limpiar caracteres especiales
        let cleanPaste = paste.toUpperCase();

        // Solo permitir: A-Z, 0-9, espacios, y vocales acentuadas (ÁÉÍÓÚ), Ñ
        cleanPaste = cleanPaste.replace(/[^A-ZÁÉÍÓÚÑ0-9\s]/g, '');

        // Reemplazar múltiples espacios consecutivos con uno solo
        cleanPaste = cleanPaste.replace(/\s+/g, ' ');

        // Eliminar espacios al inicio y al final
        cleanPaste = cleanPaste.trim();

        // Limitar a 200 caracteres máximo
        if (cleanPaste.length > 200) {
            cleanPaste = cleanPaste.substring(0, 200);
        }

        // Asignar el valor limpio al campo
        tour.value.nombre = cleanPaste;

        // Activar validación manual para actualizar la UI
        validateNombre();
    }
};

// Función para manejar paste en el campo punto de salida
const onPuntoSalidaPaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');

    if (paste) {
        // Convertir a mayúsculas
        let cleanPaste = paste.toUpperCase();

        // Reemplazar múltiples espacios consecutivos con uno solo
        cleanPaste = cleanPaste.replace(/\s+/g, ' ');

        // Eliminar espacios al inicio y al final
        cleanPaste = cleanPaste.trim();

        // Limitar a 200 caracteres máximo
        if (cleanPaste.length > 200) {
            cleanPaste = cleanPaste.substring(0, 200);
        }

        // Asignar el valor limpio al campo
        tour.value.punto_salida = cleanPaste;

        // Activar validación manual para actualizar la UI
        validatePuntoSalida();
    }
};

const validatePuntoSalida = () => {
    if (tour.value.punto_salida) {
        // Convertir a mayúsculas
        tour.value.punto_salida = tour.value.punto_salida.toUpperCase();

        // Reemplazar múltiples espacios consecutivos con uno solo
        tour.value.punto_salida = tour.value.punto_salida.replace(/\s+/g, ' ');

        // Limitar a 200 caracteres máximo
        if (tour.value.punto_salida.length > 200) {
            tour.value.punto_salida = tour.value.punto_salida.substring(0, 200);
        }

        // Eliminar espacios al inicio y al final
        tour.value.punto_salida = tour.value.punto_salida.trim();
    }
};

const validateCupos = () => {
    // Obtener la capacidad del transporte seleccionado
    const transporteSeleccionado = tipoTransportes.value.find(t => t.id === tour.value.transporte_id);
    const capacidadTransporte = transporteSeleccionado ? transporteSeleccionado.capacidad : null;

    const cupoMin = parseInt(tour.value.cupo_min) || 0;
    const cupoMax = parseInt(tour.value.cupo_max) || 0;

    // Validar que los cupos sean al menos 1
    if (tour.value.cupo_min && cupoMin < 1) {
        return false;
    }
    if (tour.value.cupo_max && cupoMax < 1) {
        return false;
    }

    // Validar que los cupos no excedan la capacidad del transporte
    if (capacidadTransporte) {
        if (tour.value.cupo_min && cupoMin > capacidadTransporte) {
            return false;
        }
        if (tour.value.cupo_max && cupoMax > capacidadTransporte) {
            return false;
        }
    }

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
        if (cupoMin >= cupoMax) {
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
        life: 5000
    });
    moreActionsDialog.value = false;
};

const handleChangeStatus = (tour) => {
    // Esta función ya no se usará, pero la mantenemos por compatibilidad
    moreActionsDialog.value = false;
};

const handleGenerateReport = (tour) => {
    toast.add({
        severity: "info",
        summary: "Generar Reporte",
        detail: `Funcionalidad de generar reporte del tour "${tour.nombre}" en desarrollo.`,
        life: 5000
    });
    moreActionsDialog.value = false;
};

const handleArchiveTour = (tour) => {
    toast.add({
        severity: "info",
        summary: "Archivar Tour",
        detail: `Funcionalidad de archivar tour "${tour.nombre}" en desarrollo.`,
        life: 5000
    });
    moreActionsDialog.value = false;
};

const handleViewReservations = (tour) => {
    // Navegar a la vista de reservas modificada con el ID del tour
    router.visit(`/catalogos/reservas?tour=${tour.id}`);
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

// Función handleEstadoActualizado removida - ya no se necesita aquí

// Función para prevenir teclas no válidas en campos de cupos
const onCupoKeyDown = (event) => {
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
    const maxCapacidad = maxCupoAllowed.value;

    // Prevenir que el valor sea 0 si es el primer carácter
    if (newValue === '0' || (currentValue === '' && key === '0')) {
        event.preventDefault();
        return;
    }

    // Validar que no exceda la capacidad del transporte o el límite de 3 dígitos
    if (newValue.length > 3 || num > maxCapacidad || num < 1) {
        event.preventDefault();
        return;
    }
};

// Función para manejar paste en campos de cupos
const onCupoPaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');
    const numericValue = paste.replace(/[^0-9]/g, '');

    if (numericValue) {
        let num = parseInt(numericValue);
        const maxCapacidad = maxCupoAllowed.value;

        // Asegurar que el número sea al menos 1
        if (num < 1) {
            num = 1;
        } else if (num > maxCapacidad) {
            num = maxCapacidad;
        }

        // Determinar qué campo se está editando y asignar el valor
        if (event.target.id.includes('cupo_min')) {
            tour.value.cupo_min = num;
        } else if (event.target.id.includes('cupo_max')) {
            tour.value.cupo_max = num;
        }
    }
};

const minCupoAllowed = computed(() => {
    //Si cupo_max es válido, retorna cupo_max -1, si no, retorna
    return tour.cupo_max ? Math.max(1, tour.cupo_max -1) : 1;
});

// Función para prevenir teclas no válidas en campos numéricos generales
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

    // Limitar a 2 dígitos y máximo 30
    if (newValue.length > 2 || num > 30) {
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
        if (num > 30) {
            num = 30;
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

    // Validar formato de precio: máximo 4 dígitos antes del punto y 2 después
    if (key !== '.') {
        const parts = currentValue.split('.');
        if (parts[0].length >= 4 && !currentValue.includes('.')) {
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
    tour.precio = formattedValue;

    // También actualizar el campo input para sincronización
    event.target.value = formattedValue;
};

</script>
<template>
    <AuthenticatedLayout>
        <Head title="Tours" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Catálogo de Tours</h1>
                <p class="text-gray-600">Gestión completa de paquetes turísticos</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <div class="w-full">
                        <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Tours</h3>
                        <p class="text-blue-600 text-xs text-center sm:text-start mt-1 font-medium flex items-center gap-1 justify-center sm:justify-start">
                            <FontAwesomeIcon :icon="faHandPointUp" class="h-4 w-4 text-yellow-500" />
                            Haz clic en cualquier fila para ver los detalles.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                    <Link
                        :href="route('transportes')"
                        @click="handleTransportesClick"
                        :class="{'opacity-50 cursor-not-allowed': isNavigatingToTransportes}"
                        class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2">
                        <FontAwesomeIcon
                            :icon="isNavigatingToTransportes ? faSpinner : faBusSimple"
                            :class="{'animate-spin': isNavigatingToTransportes, 'h-4': true}"
                        />
                        <span class="block sm:hidden">{{ isNavigatingToTransportes ? 'Cargando...' : 'Transportes' }}</span>
                        <span class="hidden sm:block">{{ isNavigatingToTransportes ? 'Cargando...' : 'Transportes' }}</span>
                    </Link>
                    <button
                        class="bg-red-500 flex border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                        @click="openNew">
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" />
                        <span>Agregar</span>
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
                                <InputText v-model="filters['global'].value" placeholder="?? Buscar tours..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div>
                                    <select
                                        v-model="selectedCategoria"
                                        @change="onCategoriaFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                    >
                                        <option value="" disabled selected hidden>Categoría</option>
                                        <option
                                            v-for="categoria in categoriasOptions"
                                            :key="categoria.value"
                                            :value="categoria.value"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ categoria.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        v-model="selectedTipoTransporte"
                                        @change="onTipoTransporteFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                    >
                                        <option value="" disabled selected hidden>Transporte</option>
                                        <option
                                            v-for="transporte in tipoTransportes"
                                            :key="transporte.id"
                                            :value="transporte.id"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ transporte.nombre }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <select
                                        v-model="selectedEstado"
                                        @change="onEstadoFilterChange"
                                        class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                                    >
                                        <option value="" disabled selected hidden>Estado</option>
                                        <option
                                            v-for="estado in estadosOptions"
                                            :key="estado.value"
                                            :value="estado.value"
                                            class="truncate text-gray-900 text-lg"
                                        >
                                            {{ estado.label }}
                                        </option>
                                    </select>
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
                                'bg-red-100 text-red-800': slotProps.data.estado === 'COMPLETO' || slotProps.data.estado === 'CANCELADA',
                                'bg-blue-100 text-blue-800': slotProps.data.estado === 'EN_CURSO',
                                'bg-gray-100 text-gray-800': slotProps.data.estado === 'FINALIZADO',
                                'bg-purple-100 text-purple-800': slotProps.data.estado === 'REPROGRAMADA'
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
                                class="flex bg-green-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="openMoreActionsModal(slotProps.data)"
                                title="Más acciones">
                                <FontAwesomeIcon :icon="faListDots" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Más</span>
                            </button>
                            <button
                                class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editTour(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteTour(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Modal del formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Tour'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="flex items-center gap-1">Tour: <span class="text-red-500 font-bold">*</span></label>
                            <InputText v-model.trim="tour.nombre" id="nombre" name="nombre" :maxlength="200" :class="{'p-invalid': submitted && (!tour.nombre || tour.nombre.length < 10 || tour.nombre.length > 200), }" class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md" placeholder="TOUR AL CERRO EL PITAL, ETC" @input="validateNombre" @keypress="preventSpecialChars" @paste="onNombrePaste"/>
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
                            <InputText v-model.trim="tour.punto_salida" id="punto_salida" name="punto_salida" :maxlength="200" :class="{'p-invalid': submitted && (!tour.punto_salida || tour.punto_salida.length < 5), }" placeholder="ATRIO DE CHALATENANGO, ETC"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                @input="validatePuntoSalida" @paste="onPuntoSalidaPaste"/>
                        </div>
                        <small class="text-red-500 ml-28" v-if="tour.punto_salida && tour.punto_salida.length < 5" >Debe tener al menos 5 caracteres. Actual: {{ tour.punto_salida.length }}/5</small>
                        <small class="text-orange-500 ml-28" v-if="tour.punto_salida && tour.punto_salida.length >= 180 && tour.punto_salida.length <= 200">Caracteres restantes: {{ 200 - tour.punto_salida.length }}</small>
                        <small class="text-red-500 ml-28" v-if="submitted && !tour.punto_salida">El punto de salida es obligatorio.</small>
                        <small class="text-red-500 ml-28" v-if="submitted && tour.punto_salida && tour.punto_salida.length < 5">El punto de salida debe tener al menos 5 caracteres.</small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-3.5">
                            <label for="transporte_id" class="w-24 flex items-center gap-1">Transporte:<span class="text-red-500 font-bold">*</span></label>
                            <Select v-model="tour.transporte_id" :options="tipoTransportes" optionLabel="nombre" optionValue="id" id="transporte_id" name="transporte_id"
                            class="w-full rounded-md border-2 border-gray-400 hover:border-gray-500" placeholder="Seleccionar" :class="{'p-invalid': submitted && !tour.transporte_id, }" />
                        </div>
                        <small class="text-red-500 ml-28" v-if="submitted && !tour.transporte_id">El tipo de transporte es obligatorio.</small>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="w-full">
                            <label for="cupo_min" class="flex items-center gap-1 mb-2">
                                Cupo mínimo: <span class="text-red-500 font-bold">*</span>
                                <!-- <small class="text-gray-500 text-xs" v-if="tour.transporte_id">(Máx: {{ maxCupoAllowed }})</small> -->
                                 <small class="text-gray-500 text-xs" v-if="tour.transporte_id">
                                    (Mín: {{ minCupoAllowed }})
                                </small>
                            </label>
                            <InputText
                                v-model="tour.cupo_min"
                                id="cupo_min"
                                name="cupo_min"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                :max="maxCupoAllowed"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :class="{'p-invalid': (submitted && !tour.cupo_min) || cupoMinError}"
                                :placeholder="`1-${maxCupoAllowed}`"
                                @keydown="onCupoKeyDown"
                                @paste="onCupoPaste"
                                @input="validateCupos"
                            />
                            <small class="text-red-500 block text-xs mt-1" v-if="submitted && !tour.cupo_min">El cupo mínimo es obligatorio.</small>
                            <small class="text-red-500 block text-xs mt-1" v-if="cupoMinError">{{ cupoMinError }}</small>
                        </div>
                        <div class="w-full">
                            <label for="cupo_max" class="flex items-center gap-1 mb-2">
                                Cupo máximo: <span class="text-red-500 font-bold">*</span>
                                <small class="text-gray-500 text-xs" v-if="tour.transporte_id">(Máx: {{ maxCupoAllowed }})</small>
                            </label>
                            <InputText
                                v-model="tour.cupo_max"
                                id="cupo_max"
                                name="cupo_max"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                :max="maxCupoAllowed"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                :class="{'p-invalid': (submitted && !tour.cupo_max) || cupoMaxError}"
                                :placeholder="`1-${maxCupoAllowed}`"
                                @keydown="onCupoKeyDown"
                                @paste="onCupoPaste"
                                @input="validateCupos"
                            />
                            <small class="text-red-500 block text-xs mt-1" v-if="submitted && !tour.cupo_max">El cupo máximo es obligatorio.</small>
                            <small class="text-red-500 block text-xs mt-1" v-if="cupoMaxError">{{ cupoMaxError }}</small>
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
                                    :class="{'p-invalid': submitted && (!tour.precio || tour.precio < 0.01 || tour.precio > 9999.99),}"
                                    placeholder="0.00"
                                    @keydown="onPriceKeyDown"
                                    @paste="onPricePaste"
                                />
                            </div>
                        </div>
                        <small class="text-red-500 ml-28" v-if=" submitted && (tour.precio == null || tour.precio < 0.01 || tour.precio > 9999.99)">El precio es obligatorio, debe ser mayor o igual a 0.01 y menor o igual a 9999.99.</small>
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
                                    Máximo 5 imágenes por tour • Formatos: JPG, PNG, GIF • Tamaño máximo: 2MB por imagen
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
                            :disabled="isLoading || !!cupoMinError || !!cupoMaxError"
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


            <!-- Componente de Modales de Tours -->
            <TourModals
                v-model:visible="moreActionsDialog"
                v-model:details-visible="showImageDialog"
                v-model:carousel-visible="showImageCarouselDialog"
                v-model:delete-visible="deleteDialog"
                v-model:unsaved-changes-visible="unsavedChangesDialog"
                v-model:carousel-index="carouselIndex"
                :tour="selectedTour || tour"
                :dialog-style="dialogStyle"
                :selected-images="selectedImages"
                :image-path="IMAGE_PATH"
                :is-deleting="isDeleting"
                @duplicate="handleDuplicateTour"
                @change-status="handleChangeStatus"
                @generate-report="handleGenerateReport"
                @archive="handleArchiveTour"
                @delete-tour="deleteTour"
                @cancel-delete="() => deleteDialog = false"
                @close-without-saving="closeDialogWithoutSaving"
                @continue-editing="continueEditing"
                @view-details="handleViewDetails"
                @view-reservations="handleViewReservations"
                @open-image-modal="openImageModal"
            />

            <!-- Modal de Cambiar Estado removido - ahora se maneja desde ReservasPorTour -->
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

</style>
