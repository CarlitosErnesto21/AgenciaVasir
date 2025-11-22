<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick, onUnmounted } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import Select from "primevue/select";
import { faArrowLeft, faBusSimple, faCheck, faExclamationTriangle, faInfoCircle, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark } from "@fortawesome/free-solid-svg-icons";
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
const detailsDialog = ref(false);
const unsavedChangesDialog = ref(false);
const submitted = ref(false);
const hasUnsavedChanges = ref(false);
const originalTransporteData = ref(null);

// Variables de loading para los botones
const isLoading = ref(false);
const isDeleting = ref(false);
const isClearingFilters = ref(false);
const isNavigatingToTours = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
    capacidad: { value: null, matchMode: FilterMatchMode.EQUALS },
    estado: { value: null, matchMode: FilterMatchMode.EQUALS },
});
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);
const btnTitle = ref("Guardar");

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
            (t.capacidad && t.capacidad.toString().includes(searchTerm)) ||
            t.numero_placa.toLowerCase().includes(searchTerm) ||
            t.marca.toLowerCase().includes(searchTerm) ||
            t.estado.toLowerCase().includes(searchTerm)
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

    // Filtro por estado
    if (filters.value.estado.value) {
        filtered = filtered.filter(t =>
            t.estado === filters.value.estado.value
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

// Función para validar marca
const validateMarca = () => {
    if (transporte.value.marca) {
        // Convertir a mayúsculas
        transporte.value.marca = transporte.value.marca.toUpperCase();

        // Eliminar caracteres no permitidos (solo letras con tildes y espacios)
        transporte.value.marca = transporte.value.marca.replace(/[^A-ZÁÉÍÓÚÑÜ\s]/g, '');

        // Eliminar espacios extra: solo un espacio entre palabras, sin espacios al inicio o final
        transporte.value.marca = transporte.value.marca
            .trim() // Eliminar espacios al inicio y final
            .replace(/\s+/g, ' '); // Reemplazar múltiples espacios con un solo espacio

        // Limitar longitud máxima
        if (transporte.value.marca.length > 30) {
            transporte.value.marca = transporte.value.marca.substring(0, 30);
        }
    }
};

// Función para prevenir la escritura de caracteres no permitidos en marca
const preventInvalidCharsMarca = (event) => {
    const char = event.key;

    // Permitir teclas de control (Backspace, Delete, Tab, Enter, etc.)
    if (event.ctrlKey || event.metaKey ||
        ['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(char)) {
        return;
    }

    // Solo permitir letras (incluidas las tildadas) y espacios
    const isAllowed = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(char);

    if (!isAllowed) {
        event.preventDefault();
        return;
    }

    // Prevenir espacios múltiples consecutivos
    if (char === ' ') {
        const input = event.target;
        const cursorPosition = input.selectionStart;
        const currentValue = transporte.value.marca || '';

        // Si ya hay un espacio en la posición anterior o si estamos al inicio, prevenir
        if (cursorPosition === 0 || currentValue[cursorPosition - 1] === ' ') {
            event.preventDefault();
        }
    }
};

// Función para manejar el pegado de texto en marca
const handlePasteMarca = (event) => {
    event.preventDefault();

    // Obtener el texto del portapapeles
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');

    // Filtrar solo caracteres permitidos y convertir a mayúsculas
    let filteredText = pastedText.toUpperCase().replace(/[^A-ZÁÉÍÓÚÑÜ\s]/g, '');

    // Eliminar espacios extra: solo un espacio entre palabras, sin espacios al inicio o final
    filteredText = filteredText
        .trim() // Eliminar espacios al inicio y final
        .replace(/\s+/g, ' '); // Reemplazar múltiples espacios con un solo espacio

    // Obtener la posición actual del cursor
    const input = event.target;
    const start = input.selectionStart;
    const end = input.selectionEnd;

    // Construir el nuevo valor
    const currentValue = transporte.value.marca || '';
    let newValue = currentValue.substring(0, start) + filteredText + currentValue.substring(end);

    // Aplicar trim completo al resultado final
    newValue = newValue
        .trim()
        .replace(/\s+/g, ' ');

    // Limitar a 30 caracteres
    transporte.value.marca = newValue.substring(0, 30);

    // Establecer la nueva posición del cursor
    nextTick(() => {
        const newCursorPosition = start + filteredText.length;
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    });
};

// Validaciones reactivas para la marca
const marcaErrors = computed(() => {
    const errors = [];
    const marca = transporte.value.marca || '';

    if (marca.length > 0 && marca.length < 2) {
        errors.push('La marca debe tener al menos 2 caracteres.');
    }

    if (marca.length > 0 && /[^A-ZÁÉÍÓÚÑÜ\s]/.test(marca)) {
        errors.push('La marca solo puede contener letras y espacios (con tildes permitidas).');
    }

    // Validar espacios múltiples o espacios al inicio/final
    if (marca.length > 0 && (marca !== marca.trim() || /\s{2,}/.test(marca))) {
        errors.push('No se permiten espacios extra al inicio, final o múltiples espacios consecutivos.');
    }

    return errors;
});

// Función para validar nombre (descripción)
const validateNombre = () => {
    if (transporte.value.nombre) {
        // Convertir a mayúsculas
        transporte.value.nombre = transporte.value.nombre.toUpperCase();

        // Eliminar caracteres no permitidos (solo letras con tildes, números y espacios)
        transporte.value.nombre = transporte.value.nombre.replace(/[^A-ZÁÉÍÓÚÑÜ0-9\s]/g, '');

        // Eliminar espacios extra: solo un espacio entre palabras, sin espacios al inicio o final
        transporte.value.nombre = transporte.value.nombre
            .trim() // Eliminar espacios al inicio y final
            .replace(/\s+/g, ' '); // Reemplazar múltiples espacios con un solo espacio

        // Limitar longitud máxima
        if (transporte.value.nombre.length > 50) {
            transporte.value.nombre = transporte.value.nombre.substring(0, 50);
        }
    }
};

// Función para prevenir la escritura de caracteres no permitidos en nombre (descripción)
const preventInvalidCharsNombre = (event) => {
    const char = event.key;

    // Permitir teclas de control (Backspace, Delete, Tab, Enter, etc.)
    if (event.ctrlKey || event.metaKey ||
        ['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(char)) {
        return;
    }

    // Solo permitir letras (incluidas las tildadas), números y espacios
    const isAllowed = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s]$/.test(char);

    if (!isAllowed) {
        event.preventDefault();
        return;
    }

    // Prevenir espacios múltiples consecutivos
    if (char === ' ') {
        const input = event.target;
        const cursorPosition = input.selectionStart;
        const currentValue = transporte.value.nombre || '';

        // Si ya hay un espacio en la posición anterior o si estamos al inicio, prevenir
        if (cursorPosition === 0 || currentValue[cursorPosition - 1] === ' ') {
            event.preventDefault();
        }
    }
};

// Función para manejar el pegado de texto en nombre (descripción)
const handlePasteNombre = (event) => {
    event.preventDefault();

    // Obtener el texto del portapapeles
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');

    // Filtrar solo caracteres permitidos y convertir a mayúsculas
    let filteredText = pastedText.toUpperCase().replace(/[^A-ZÁÉÍÓÚÑÜ0-9\s]/g, '');

    // Eliminar espacios extra: solo un espacio entre palabras, sin espacios al inicio o final
    filteredText = filteredText
        .trim() // Eliminar espacios al inicio y final
        .replace(/\s+/g, ' '); // Reemplazar múltiples espacios con un solo espacio

    // Obtener la posición actual del cursor
    const input = event.target;
    const start = input.selectionStart;
    const end = input.selectionEnd;

    // Construir el nuevo valor
    const currentValue = transporte.value.nombre || '';
    let newValue = currentValue.substring(0, start) + filteredText + currentValue.substring(end);

    // Aplicar trim completo al resultado final
    newValue = newValue
        .trim()
        .replace(/\s+/g, ' ');

    // Limitar a 50 caracteres
    transporte.value.nombre = newValue.substring(0, 50);

    // Establecer la nueva posición del cursor
    nextTick(() => {
        const newCursorPosition = start + filteredText.length;
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    });
};

// Validaciones reactivas para el nombre (descripción)
const nombreErrors = computed(() => {
    const errors = [];
    const nombre = transporte.value.nombre || '';

    if (nombre.length > 0 && nombre.length < 3) {
        errors.push('La descripción debe tener al menos 3 caracteres.');
    }

    if (nombre.length > 0 && /[^A-ZÁÉÍÓÚÑÜ0-9\s]/.test(nombre)) {
        errors.push('La descripción solo puede contener letras con tildes, números y espacios.');
    }

    // Validar espacios múltiples o espacios al inicio/final
    if (nombre.length > 0 && (nombre !== nombre.trim() || /\s{2,}/.test(nombre))) {
        errors.push('No se permiten espacios extra al inicio, final o múltiples espacios consecutivos.');
    }

    return errors;
});

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
    fetchTransportesWithToasts();

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

const fetchTransportes = async () => {
    try {
        // Mostrar toast de carga
        toast.add({
            severity: "info",
            summary: "Cargando transportes...",
            life: 2000
        });

        const response = await axios.get("/api/transportes");
        transportes.value = response.data.sort((a, b) => b.id - a.id);

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Transportes cargados",
            life: 2000
        });
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "No se pudieron cargar los transportes.", life: 4000 });
    }
};

const fetchTransportesWithToasts = async () => {
    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando transportes...",
        life: 2000
    });

    try {
        const response = await axios.get("/api/transportes");
        transportes.value = response.data.sort((a, b) => b.id - a.id);

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Transportes cargados",
            life: 2000
        });

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
    transporte.value = {
        ...t,
        marca: t.marca ? t.marca.toUpperCase().trim().replace(/\s+/g, ' ') : t.marca, // Convertir marca a mayúsculas y limpiar espacios extra
        nombre: t.nombre ? t.nombre.toUpperCase().trim().replace(/\s+/g, ' ') : t.nombre // Convertir descripción a mayúsculas y limpiar espacios extra
    };
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
        toast.add({ severity: "warn", summary: "Descripción inválida", detail: "La descripción debe tener entre 3 y 50 caracteres.", life: 4000 });
        return;
    }

    // Validar que la descripción solo contenga letras con tildes, números y espacios
    if (/[^A-ZÁÉÍÓÚÑÜ0-9\s]/.test(transporte.value.nombre)) {
        toast.add({ severity: "warn", summary: "Descripción inválida", detail: "La descripción solo puede contener letras con tildes, números y espacios.", life: 4000 });
        return;
    }
    if (!transporte.value.capacidad || transporte.value.capacidad < 1) {
        toast.add({ severity: "warn", summary: "Campos requeridos", detail: "La capacidad debe ser al menos 1.", life: 4000 });
        return;
    }
    if (!transporte.value.marca || transporte.value.marca.length < 2 || transporte.value.marca.length > 30) {
        toast.add({ severity: "warn", summary: "Marca inválida", detail: "La marca debe tener entre 2 y 30 caracteres.", life: 4000 });
        return;
    }

    // Validar que la marca solo contenga letras con tildes y espacios
    if (/[^A-ZÁÉÍÓÚÑÜ\s]/.test(transporte.value.marca)) {
        toast.add({ severity: "warn", summary: "Marca inválida", detail: "La marca solo puede contener letras y espacios (con tildes permitidas).", life: 4000 });
        return;
    }

    isLoading.value = true;
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
            toast.add({ severity: "success", summary: "¡Creado!", detail: "Transporte creado correctamente.", life: 4000 });
        } else {
            response = await axios.put(`/api/transportes/${transporte.value.id}`, {
                numero_placa: transporte.value.numero_placa,
                nombre: transporte.value.nombre,
                capacidad: transporte.value.capacidad,
                marca: transporte.value.marca,
                estado: transporte.value.estado,
            });
            toast.add({ severity: "success", summary: "¡Actualizado!", detail: "Transporte actualizado correctamente.", life: 4000 });
        }
        await fetchTransportes();
        dialog.value = false;
        hasUnsavedChanges.value = false;
        originalTransporteData.value = null;
        resetForm();
    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
            const errors = err.response.data.errors;

            // Mostrar error de placa si existe
            if (errors.numero_placa) {
                toast.add({ severity: "error", summary: "Error", detail: errors.numero_placa[0], life: 4000 });
            }
            // Mostrar error de nombre si existe
            else if (errors.nombre) {
                toast.add({ severity: "error", summary: "Error", detail: errors.nombre[0], life: 4000 });
            }
            // Mostrar el primer error disponible si hay otros
            else {
                const firstErrorKey = Object.keys(errors)[0];
                toast.add({ severity: "error", summary: "Error", detail: errors[firstErrorKey][0], life: 4000 });
            }
        } else {
            toast.add({ severity: "error", summary: "Error", detail: "No se pudo guardar el transporte.", life: 4000 });
        }
    } finally {
        isLoading.value = false;
    }
};

const confirmDeleteTransporte = (t) => {
    transporte.value = { ...t };
    deleteDialog.value = true;
};

const viewTransporteDetails = (t) => {
    transporte.value = { ...t };
    detailsDialog.value = true;
};

const deleteTransporte = async () => {
    isDeleting.value = true;
    try {
        await axios.delete(`/api/transportes/${transporte.value.id}`);
        await fetchTransportes();
        deleteDialog.value = false;
        toast.add({ severity: "success", summary: "¡Eliminado!", detail: "Transporte eliminado correctamente.", life: 4000 });
    } catch (err) {
        deleteDialog.value = false;

        // Verificar si es un error de tours asociados
        if (err.response && err.response.status === 400 && err.response.data && err.response.data.error === 'TOURS_ASOCIADOS') {
            const toursCount = err.response.data.tours_count;
            const tourText = toursCount === 1 ? 'tour asociado' : 'tours asociados';
            toast.add({
                severity: "warn",
                summary: "No se puede eliminar",
                detail: `El transporte "${transporte.value.nombre}" tiene ${toursCount} ${tourText}. Para eliminarlo, primero debe cambiar el transporte de los tours asociados o eliminarlos.`,
                life: 9000
            });
        } else {
            toast.add({ severity: "error", summary: "Error", detail: "No se pudo eliminar el transporte.", life: 4000 });
        }
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
    originalTransporteData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// Variable para controlar el mensaje de límite máximo
const showMaxLimitMessage = ref(false);

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

// Función para prevenir teclas no válidas antes de escribir
const onKeyDown = (event) => {
    const currentValue = event.target.value;
    const key = event.key;
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].includes(event.keyCode) ||
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

// Función para limpiar filtros con loading
const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        // Limpiar todos los filtros
        filters.value.global.value = null;
        filters.value.estado.value = null;

        // Mostrar toast de confirmación
        toast.add({
            severity: "success",
            summary: "Filtros limpiados",
            detail: "Se han eliminado todos los filtros aplicados.",
            life: 2000
        });
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Hubo un problema al limpiar los filtros.",
            life: 3000
        });
    } finally {
        isClearingFilters.value = false;
    }
};

// Función para manejar el clic en la fila
const onRowClick = (event) => {
    // Verificar si el clic fue en un botón para evitar abrir el modal
    const target = event.originalEvent.target;
    const isButton = target.closest('button');

    if (!isButton) {
        viewTransporteDetails(event.data);
    }
};

// Función para manejar el clic en el enlace de Tours
const handleToursClick = () => {
    isNavigatingToTours.value = true;
};
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Control de Transportes" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Control de Transportes</h1>
                <p class="text-gray-600">Gestión de vehículos y transportes</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('tours')"
                            @click="handleToursClick"
                            :class="{'opacity-50 cursor-not-allowed': isNavigatingToTours}"
                            class="flex items-center text-blue-600 hover:text-blue-700 transition-colors duration-200 px-3 py-2 rounded-lg"
                            title="Regresar a Tours">
                            <FontAwesomeIcon
                                :icon="isNavigatingToTours ? faSpinner : faArrowLeft"
                                :class="{'animate-spin': isNavigatingToTours, 'h-5 w-5': true}"
                            />
                        </Link>
                        <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Transportes</h3>
                    </div>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2"
                            @click="openNew">
                            <FontAwesomeIcon :icon="faPlus" class="h-4 w-4" />
                            <span>Agregar</span>
                        </button>
                    </div>
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
                    paginator: { class: 'text-xs sm:text-sm custom-paginator' },
                    paginatorWrapper: { class: 'flex flex-wrap justify-center sm:justify-between items-center gap-2 p-2' }
                }"
            >
                <template #header>
                    <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4">
                        <div class="flex flex-col sm:flex-row items-center justify-between mb-3">
                            <div class="gap-3 items-center flex">
                                <h3 class="text-base font-medium text-gray-800 flex items-center gap-2 mb-2">
                                    <i class="pi pi-filter text-blue-600 text-sm"></i><span>Filtros</span>
                                </h3>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium mb-2 sm:mb-0">
                                    {{ filteredTransportes.length }} resultado{{ filteredTransportes.length !== 1 ? 's' : '' }}
                                </div>
                                <button
                                    @click="clearFilters"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm border border-gray-300 transition-colors duration-200 h-8 inline sm:hidden mb-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="isClearingFilters"
                                    title="Limpiar filtros"
                                >
                                    <FontAwesomeIcon
                                        v-if="isClearingFilters"
                                        :icon="faSpinner"
                                        class="h-3 w-3 animate-spin mr-1"
                                    />
                                    <span v-if="isClearingFilters">Limpiando...</span>
                                    <span v-else>Limpiar filtros</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <Select
                                    v-model="filters['estado'].value"
                                    :options="[
                                        { label: 'DISPONIBLE', value: 'DISPONIBLE' },
                                        { label: 'NO DISPONIBLE', value: 'NO_DISPONIBLE' }
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Filtrar por estado"
                                    class="w-full h-8 text-sm border rounded-md sm:w-full"
                                    style="background-color: white; border-color: #93c5fd;"
                                />
                                <button
                                    @click="clearFilters"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 w-full rounded-md text-sm border border-gray-300 transition-colors duration-200 h-8 hidden sm:inline disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="isClearingFilters"
                                    title="Limpiar filtros"
                                >
                                    <FontAwesomeIcon
                                        v-if="isClearingFilters"
                                        :icon="faSpinner"
                                        class="h-3 w-3 animate-spin mr-1"
                                    />
                                    <span v-if="isClearingFilters">Limpiando...</span>
                                    <span v-else>Limpiar filtros</span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <InputText v-model="filters['global'].value" placeholder="🔍 Buscar transportes..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;" />
                            </div>
                        </div>
                    </div>
                </template>
                <Column field="numero_placa" header="Placa" sortable class="w-32 min-w-16">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 85px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.numero_placa"
                        >
                            {{ slotProps.data.numero_placa?.toUpperCase() }}
                        </div>
                    </template>
                </Column>
                <Column field="nombre" header="Descripción" sortable class="w-36 min-w-10">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed overflow-hidden"
                            style="max-width: 75px; text-overflow: ellipsis; white-space: nowrap;"
                            :title="slotProps.data.nombre"
                        >
                            {{ slotProps.data.nombre }}
                        </div>
                    </template>
                </Column>
                <Column field="capacidad" header="Capacidad" class="w-32 min-w-20 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed text-center sm:text-left">
                            {{ slotProps.data.capacidad }}
                        </div>
                    </template>
                </Column>
                <Column field="marca" header="Marca" class="w-32 min-w-24 hidden md:table-cell">
                    <template #body="slotProps">
                        <div class="text-sm leading-relaxed">
                            {{ slotProps.data.marca }}
                        </div>
                    </template>
                </Column>
                <Column field="estado" header="Estado" class="w-0 min-w-24 hidden md:table-cell">
                    <template #body="slotProps">
                        <span :class="slotProps.data.estado === 'DISPONIBLE' ? 'bg-green-100 text-green-800 px-2 py-1 rounded-full' : 'bg-red-100 text-red-800 px-2 py-1 rounded-full'">
                            {{ slotProps.data.estado === 'NO_DISPONIBLE' ? 'NO DISPONIBLE' : slotProps.data.estado }}
                        </span>
                    </template>
                </Column>
                <Column :exportable="false" class="w-40 min-w-28">
                    <template #header>
                        <div class="text-center w-full font-bold">
                            Acciones
                        </div>
                    </template>
                    <template #body="slotProps">
                        <div class="flex gap-2 h-full justify-center items-center">
                            <button
                                class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editTransporte(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteTransporte(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Modal de formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Transporte'" :modal="true" :style="dialogStyle"
                :closable="false" :draggable="false">
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="numero_placa" class="w-24 flex items-center gap-1">
                                Placa: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText v-model.trim="transporte.numero_placa" @input="(e) => transporte.numero_placa = e.target.value.toUpperCase()" id="numero_placa" name="numero_placa" :maxlength="10" :class="{'p-invalid': submitted && (!transporte.numero_placa || transporte.numero_placa.length < 5 || transporte.numero_placa.length > 10),}" class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md" placeholder="ABC123"/>
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
                                Descripción: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model="transporte.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="50"
                                :class="{'p-invalid': (submitted && (!transporte.nombre || nombreErrors.length > 0)) || nombreErrors.length > 0}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                placeholder="AUTOBUS TURÍSTICO, VAN EJECUTIVA 2024, etc."
                                @keydown="preventInvalidCharsNombre"
                                @paste="handlePasteNombre"
                                @input="validateNombre"
                            />
                        </div>

                        <!-- Mostrar errores de validación en tiempo real -->
                        <div v-if="nombreErrors.length > 0" class="ml-28 mt-1">
                            <small v-for="error in nombreErrors" :key="error" class="text-red-500 block">
                                {{ error }}
                            </small>
                        </div>

                        <!-- Mostrar contador de caracteres -->
                        <small class="text-gray-500 ml-28 mt-1" v-if="transporte.nombre && transporte.nombre.length > 0 && nombreErrors.length === 0">
                            Caracteres: {{ transporte.nombre.length }}/50
                        </small>
                        <small class="text-orange-500 ml-28 mt-1" v-if="transporte.nombre && transporte.nombre.length >= 45 && transporte.nombre.length <= 50 && nombreErrors.length === 0">
                            Caracteres restantes: {{ 50 - transporte.nombre.length }}
                        </small>

                        <!-- Error de campo obligatorio -->
                        <small class="text-red-500 ml-28 mt-1" v-if="submitted && !transporte.nombre">
                            La descripción es obligatoria.
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
                                placeholder="1-500"
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
                            <InputText
                                v-model="transporte.marca"
                                id="marca"
                                name="marca"
                                :maxlength="30"
                                :class="{'p-invalid': (submitted && (!transporte.marca || marcaErrors.length > 0)) || marcaErrors.length > 0}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                placeholder="TOYOTA, MERCEDES, etc."
                                @keydown="preventInvalidCharsMarca"
                                @paste="handlePasteMarca"
                                @input="validateMarca"
                            />
                        </div>

                        <!-- Mostrar errores de validación en tiempo real -->
                        <div v-if="marcaErrors.length > 0" class="ml-28 mt-1">
                            <small v-for="error in marcaErrors" :key="error" class="text-red-500 block">
                                {{ error }}
                            </small>
                        </div>

                        <!-- Mostrar contador de caracteres -->
                        <small class="text-gray-500 ml-28 mt-1" v-if="transporte.marca && transporte.marca.length > 0 && marcaErrors.length === 0">
                            Caracteres: {{ transporte.marca.length }}/30
                        </small>
                        <small class="text-orange-500 ml-28 mt-1" v-if="transporte.marca && transporte.marca.length >= 25 && transporte.marca.length <= 30 && marcaErrors.length === 0">
                            Caracteres restantes: {{ 30 - transporte.marca.length }}
                        </small>

                        <!-- Error de campo obligatorio -->
                        <small class="text-red-500 ml-28 mt-1" v-if="submitted && !transporte.marca">
                            La marca es obligatoria.
                        </small>
                    </div>
                    <div class="w-full flex flex-col">
                        <div v-if="!transporte.id" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-2">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-500 rounded-full p-2">
                                    <FontAwesomeIcon :icon="faInfoCircle" class="w-6 h-4 text-white" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-blue-800">Estado por defecto</h4>
                                    <p class="text-sm text-blue-700">Este transporte se creará como <span class="font-semibold">DISPONIBLE</span></p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex items-center gap-4">
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
                            <span v-else>{{ transporte.id ? 'Actualizando...' : 'Guardando...' }}</span>
                        </button>
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-10 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="hideDialog" :disabled="isLoading">
                            <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />Cerrar
                        </button>
                    </div>
                </template>
            </Dialog>

            <!-- Modal de confirmación de eliminación -->
            <Dialog v-model:visible="deleteDialog" header="Eliminar transporte" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar el transporte: <b>{{ transporte.nombre }}</b>?</span>
                        <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="deleteTransporte"
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

            <!-- Modal de cambios sin guardar -->
            <Dialog v-model:visible="unsavedChangesDialog" header="Cambios sin guardar" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <span>Tienes información sin guardar. ¿Deseas salir sin guardar?</span>
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

            <!-- Modal de detalles -->
            <Dialog v-model:visible="detailsDialog" header="Detalles del Transporte" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-sm font-semibold text-gray-700">Placa:</label>
                            <p class="text-lg font-mono text-gray-900 mt-1">{{ transporte.numero_placa?.toUpperCase() }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-sm font-semibold text-gray-700">Nombre:</label>
                            <p class="text-lg text-gray-900 mt-1">{{ transporte.nombre }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-sm font-semibold text-gray-700">Capacidad:</label>
                            <p class="text-lg text-gray-900 mt-1">{{ transporte.capacidad }} personas</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-sm font-semibold text-gray-700">Marca:</label>
                            <p class="text-lg text-gray-900 mt-1">{{ transporte.marca }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg sm:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">Estado:</label>
                            <div class="mt-2">
                                <span :class="transporte.estado === 'DISPONIBLE' ? 'bg-green-100 text-green-800 px-3 py-2 rounded-full font-medium' : 'bg-red-100 text-red-800 px-3 py-2 rounded-full font-medium'">
                                    {{ transporte.estado === 'NO_DISPONIBLE' ? 'NO DISPONIBLE' : transporte.estado }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center w-full">
                        <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="detailsDialog = false">
                            <FontAwesomeIcon :icon="faXmark" class="h-5 text-white" />Cerrar
                        </button>
                    </div>
                </template>
            </Dialog>
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
