<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import Toast from 'primevue/toast';
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheck, faExclamationTriangle, faFilter, faListDots, faPencil, faPlus, faSpinner, faTrashCan, faXmark, faUsers, faKey } from "@fortawesome/free-solid-svg-icons";
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';
import Modales from "./GestionUsuarioComponent/Modales.vue";
import axios from "axios";
axios.defaults.withCredentials = true;

const toast = useToast();

// 📊 Estados reactivos
const empleados = ref([]);
const empleado = ref({
    id: null,
    nombre: "",
    email: "",
    password: "",
    password_confirmation: "",
    cargo: "",
    telefono: "",
});

// 📝 Modal states
const dialog = ref(false);
const deleteDialog = ref(false);
const passwordDialog = ref(false);
const unsavedChangesDialog = ref(false);
const submitted = ref(false);
const hasUnsavedChanges = ref(false);
const originalEmpleadoData = ref(null);

// Variables para modales de empleados
const moreActionsDialog = ref(false);
const selectedEmpleado = ref(null);

// Variables de loading
const isDeleting = ref(false);
const isClearingFilters = ref(false);
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

// 🎯 URLs y constantes
const url = "/api/empleados";

// 🔍 Empleados filtrados
const filteredEmpleados = computed(() => {
    let filtered = empleados.value;

    // Filtro por búsqueda global
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase();
        filtered = filtered.filter(empleado =>
            empleado.nombre.toLowerCase().includes(searchTerm) ||
            empleado.email.toLowerCase().includes(searchTerm) ||
            empleado.cargo.toLowerCase().includes(searchTerm) ||
            empleado.telefono.toLowerCase().includes(searchTerm)
        );
    }

    return filtered;
});

// Variables de validación de teléfono
const telefonoValidation = ref({
    isValid: false,
    country: null,
    formattedNumber: '',
    mensaje: ''
});

// Función para validar teléfono
const onValidate = (phoneObject) => {
    try {
        if (phoneObject && typeof phoneObject === 'object') {
            telefonoValidation.value.isValid = phoneObject.valid === true;
            telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode };
            telefonoValidation.value.formattedNumber = phoneObject.formatted || '';

            // Actualizar el teléfono con el formato correcto cuando es válido
            if (phoneObject.valid === true && phoneObject.formatted) {
                empleado.value.telefono = phoneObject.formatted;
            }

            if (empleado.value.telefono && phoneObject.valid === false) {
                telefonoValidation.value.mensaje = '✗ Número de teléfono inválido para ' + phoneObject.country;
            } else if (phoneObject.valid === true) {
                telefonoValidation.value.mensaje = '✓ Número válido para ' + phoneObject.country;
            } else {
                telefonoValidation.value.mensaje = '';
            }
        }
    } catch (error) {
        telefonoValidation.value.mensaje = 'Error en validación';
    }
};

// 👀 Watcher para detectar cambios en el modal
watch([empleado], () => {
    if (originalEmpleadoData.value && dialog.value) {
        nextTick(() => {
            const current = { ...empleado.value };
            delete current.password;
            delete current.password_confirmation;

            const original = { ...originalEmpleadoData.value };
            delete original.password;
            delete original.password_confirmation;

            const hasChanges = JSON.stringify(current) !== JSON.stringify(original);
            const isCreatingNew = !originalEmpleadoData.value.id;
            const hasAnyData = empleado.value.nombre ||
                              empleado.value.email ||
                              empleado.value.cargo ||
                              empleado.value.telefono;

            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData);
        });
    }
}, { deep: true, flush: 'post' });

// 🔄 Función para resetear formulario
function resetForm() {
    empleado.value = {
        id: null,
        nombre: "",
        email: "",
        password: "",
        password_confirmation: "",
        cargo: "",
        telefono: "",
    };
    telefonoValidation.value = {
        isValid: false,
        country: null,
        formattedNumber: '',
        mensaje: ''
    };
    submitted.value = false;
}

// 📊 Cargar datos
onMounted(() => {
    fetchEmpleadosWithToasts();
});

const fetchEmpleados = async () => {
    try {
        const response = await axios.get(url);
        empleados.value = (response.data.data || response.data || []).sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA;
        });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los empleados.",
            life: 4000
        });
    }
};

const fetchEmpleadosWithToasts = async () => {
    isLoadingTable.value = true;

    // Mostrar toast de carga con duración automática
    toast.add({
        severity: "info",
        summary: "Cargando empleados...",
        life: 2000
    });

    try {
        const response = await axios.get(url);
        empleados.value = (response.data.data || response.data || []).sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA;
        });

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Empleados cargados",
            life: 2000
        });

    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los empleados.",
            life: 4000
        });
    } finally {
        isLoadingTable.value = false;
    }
};

// 🔍 Funciones para manejar filtros
const clearFilters = async () => {
    isClearingFilters.value = true;

    try {
        // Simular un pequeño delay para mostrar el loading
        await new Promise(resolve => setTimeout(resolve, 300));

        filters.value.global.value = null;

        toast.add({
            severity: "success",
            summary: "Filtros limpiados",
            life: 2000
        });
    } finally {
        isClearingFilters.value = false;
    }
};

// 📝 CRUD Operations
const openNew = () => {
    resetForm();
    btnTitle.value = "Guardar";
    submitted.value = false;
    dialog.value = true;
    nextTick(() => {
        originalEmpleadoData.value = { ...empleado.value };
        hasUnsavedChanges.value = false;
    });
};

const editEmpleado = (emp) => {
    resetForm();
    submitted.value = false;
    empleado.value = {
        ...emp,
        nombre: emp.nombre || emp.name,
        cargo: emp.cargo ? emp.cargo.toUpperCase() : emp.cargo, // Convertir cargo a mayúsculas
        password: "",
        password_confirmation: ""
    };
    hasUnsavedChanges.value = false;
    btnTitle.value = "Actualizar";
    dialog.value = true;
    nextTick(() => {
        originalEmpleadoData.value = { ...empleado.value };
        hasUnsavedChanges.value = false;
    });
};

const saveOrUpdate = async () => {
    submitted.value = true;

    // Validar nombre específicamente
    if (!empleado.value.nombre || empleado.value.nombre.length < 3 || empleado.value.nombre.length > 255) {
        toast.add({
            severity: "warn",
            summary: "Nombre inválido",
            detail: "El nombre debe tener entre 3 y 255 caracteres.",
            life: 4000
        });
        return;
    }

    // Validar que el nombre no contenga números ni caracteres especiales
    if (/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]/.test(empleado.value.nombre)) {
        toast.add({
            severity: "warn",
            summary: "Nombre inválido",
            detail: "El nombre solo puede contener letras y espacios.",
            life: 4000
        });
        return;
    }

    // Validar que no exista duplicado
    if (isNombreDuplicated(empleado.value.nombre)) {
        toast.add({
            severity: "warn",
            summary: "Nombre duplicado",
            detail: "Ya existe un empleado con este nombre.",
            life: 4000
        });
        return;
    }

    // Validar email específicamente
    if (!empleado.value.email || !isValidEmail(empleado.value.email)) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor verifica que el correo electrónico sea válido.",
            life: 4000
        });
        return;
    }

    // Validar contraseña solo para nuevos empleados
    if (!empleado.value.id) {
        if (!empleado.value.password || empleado.value.password.length < 8 ||
            !/[A-Z]/.test(empleado.value.password) || !/[0-9]/.test(empleado.value.password) ||
            /[\s.]/.test(empleado.value.password)) {
            toast.add({
                severity: "warn",
                summary: "Contraseña inválida",
                detail: "La contraseña debe tener al menos 8 caracteres, incluir una mayúscula, un número y no contener espacios ni puntos.",
                life: 4000
            });
            return;
        }

        if (empleado.value.password !== empleado.value.password_confirmation) {
            toast.add({
                severity: "warn",
                summary: "Contraseñas no coinciden",
                detail: "Por favor verifica que las contraseñas coincidan.",
                life: 4000
            });
            return;
        }
    }

    // Validar cargo específicamente
    if (!empleado.value.cargo || empleado.value.cargo.length < 2) {
        toast.add({
            severity: "warn",
            summary: "Cargo inválido",
            detail: "El cargo debe tener al menos 2 caracteres.",
            life: 4000
        });
        return;
    }

    // Validar que el cargo no contenga números, tildes ni caracteres especiales
    if (/[^A-Z\s]/.test(empleado.value.cargo)) {
        toast.add({
            severity: "warn",
            summary: "Cargo inválido",
            detail: "El cargo solo puede contener letras y espacios (sin tildes ni números).",
            life: 4000
        });
        return;
    }

    // Validar teléfono
    if (!empleado.value.telefono) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "Por favor completa todos los campos obligatorios.",
            life: 4000
        });
        return;
    }

    // Validar teléfono
    if (!telefonoValidation.value.isValid) {
        toast.add({
            severity: "warn",
            summary: "Teléfono inválido",
            detail: "Por favor ingresa un número de teléfono válido.",
            life: 4000
        });
        return;
    }

    try {
        isLoading.value = true;

        const formData = {
            name: empleado.value.nombre,
            email: empleado.value.email,
            cargo: empleado.value.cargo,
            telefono: empleado.value.telefono,
        };

        // Solo incluir contraseña para empleados nuevos
        if (!empleado.value.id) {
            formData.password = empleado.value.password;
            formData.password_confirmation = empleado.value.password_confirmation;
        }

        let response;
        if (!empleado.value.id) {
            response = await axios.post(url, formData);
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El empleado ha sido creado correctamente.",
                life: 5000
            });
        } else {
            response = await axios.put(`${url}/${empleado.value.id}`, formData);
            toast.add({
                severity: "success",
                summary: "¡Éxito!",
                detail: "El empleado ha sido actualizado correctamente.",
                life: 5000
            });
        }

        await fetchEmpleados();
        dialog.value = false;
        hasUnsavedChanges.value = false;
        originalEmpleadoData.value = null;
        resetForm();
    } catch (err) {
        if (err.response?.status === 422) {
            const errors = err.response.data.errors || {};
            let errorMessage = "Por favor revisa los campos e intenta nuevamente.";

            if (errors.email) {
                errorMessage = errors.email[0];
            } else if (errors.name) {
                errorMessage = errors.name[0];
            }

            toast.add({
                severity: "warn",
                summary: "Error de validación",
                detail: errorMessage,
                life: 6000
            });
        } else {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Ocurrió un error al procesar la solicitud.",
                life: 6000
            });
        }
    } finally {
        isLoading.value = false;
    }
};

const confirmDeleteEmpleado = (emp) => {
    selectedEmpleado.value = { ...emp };
    deleteDialog.value = true;
};

// 🚀 MEJORADO: Función para eliminar con mejor manejo de errores
const deleteEmpleado = async () => {
    isDeleting.value = true;
    try {
        await axios.delete(`${url}/${selectedEmpleado.value.id}`);
        await fetchEmpleados();
        deleteDialog.value = false;
        toast.add({
            severity: "success",
            summary: "¡Eliminado!",
            detail: "El empleado ha sido eliminado correctamente.",
            life: 5000
        });
    } catch (err) {
        deleteDialog.value = false;

        // 🎯 Manejo específico de errores 422 - Restricciones de integridad
        if (err.response?.status === 422) {
            const errorData = err.response.data;
            let mensaje = errorData.error || "El empleado está siendo utilizado en el sistema.";

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
        // 🎯 Manejo de errores 404 - Empleado no encontrado
        else if (err.response?.status === 404) {
            toast.add({
                severity: "error",
                summary: "Empleado no encontrado",
                detail: "El empleado que intentas eliminar no existe o ya fue eliminado.",
                life: 5000
            });
            // Recargar la lista para reflejar el estado actual
            await fetchEmpleados();
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
                detail: `No se pudo eliminar el empleado: ${errorMsg}`,
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
    originalEmpleadoData.value = null;
    resetForm();
};

const continueEditing = () => {
    unsavedChangesDialog.value = false;
};

// Funciones para manejar los modales de empleados
const openMoreActionsModal = (empleadoData) => {
    selectedEmpleado.value = empleadoData;
    moreActionsDialog.value = true;
};

const handleChangePassword = (emp) => {
    moreActionsDialog.value = false;
    selectedEmpleado.value = emp;
    empleado.value = {
        id: emp.id,
        nombre: emp.nombre,
        email: emp.email,
        cargo: emp.cargo ? emp.cargo.toUpperCase() : emp.cargo, // Convertir cargo a mayúsculas
        telefono: emp.telefono,
        password: "",
        password_confirmation: ""
    };
    passwordDialog.value = true;
};

const updatePassword = async (passwordData) => {
    submitted.value = true;

    // Validar contraseña
    if (!passwordData.password || passwordData.password.length < 8 ||
        !/[A-Z]/.test(passwordData.password) || !/[0-9]/.test(passwordData.password) ||
        /[\s.]/.test(passwordData.password)) {
        toast.add({
            severity: "warn",
            summary: "Contraseña inválida",
            detail: "La contraseña debe tener al menos 8 caracteres, incluir una mayúscula, un número y no contener espacios ni puntos.",
            life: 4000
        });
        return;
    }

    if (passwordData.password !== passwordData.password_confirmation) {
        toast.add({
            severity: "warn",
            summary: "Contraseñas no coinciden",
            detail: "Por favor verifica que las contraseñas coincidan.",
            life: 4000
        });
        return;
    }

    try {
        isLoading.value = true;

        await axios.put(`${url}/${selectedEmpleado.value.id}/password`, {
            password: passwordData.password,
            password_confirmation: passwordData.password_confirmation
        });

        passwordDialog.value = false;
        submitted.value = false;
        resetForm();

        toast.add({
            severity: "success",
            summary: "¡Éxito!",
            detail: "La contraseña ha sido actualizada correctamente.",
            life: 5000
        });

    } catch (err) {
        if (err.response?.status === 422) {
            const errors = err.response.data.errors || {};
            let errorMessage = "Por favor revisa los campos e intenta nuevamente.";

            if (errors.password) {
                errorMessage = errors.password[0];
            }

            toast.add({
                severity: "warn",
                summary: "Error de validación",
                detail: errorMessage,
                life: 6000
            });
        } else {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Ocurrió un error al actualizar la contraseña.",
                life: 6000
            });
        }
    } finally {
        isLoading.value = false;
    }
};

const handleViewDetails = (emp) => {
    toast.add({
        severity: "info",
        summary: "Ver Detalles",
        detail: `Funcionalidad de visualizar detalles del empleado "${emp.nombre}" en desarrollo.`,
        life: 5000
    });
    moreActionsDialog.value = false;
};

const handleSendCredentials = (emp) => {
    toast.add({
        severity: "info",
        summary: "Enviar Credenciales",
        detail: `Funcionalidad de enviar credenciales al empleado "${emp.nombre}" en desarrollo.`,
        life: 5000
    });
    moreActionsDialog.value = false;
};

// Función para manejar el clic en la fila
const onRowClick = (event) => {
    // Verificar si el clic fue en un botón para evitar abrir el modal
    const target = event.originalEvent.target;
    const isButton = target.closest('button');

    if (!isButton) {
        selectedEmpleado.value = event.data;
        // Aquí podrías abrir un modal de detalles si lo deseas
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

// Función para validar email
const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

// ✅ Validaciones en tiempo real
const validateNombre = () => {
    if (empleado.value.nombre) {
        // Eliminar caracteres no permitidos (números y caracteres especiales)
        // Solo permitir letras (incluidas las tildadas), espacios y guiones
        empleado.value.nombre = empleado.value.nombre.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]/g, '');
        
        // Limitar longitud máxima
        if (empleado.value.nombre.length > 255) {
            empleado.value.nombre = empleado.value.nombre.substring(0, 255);
        }
    }
};

// Función para prevenir la escritura de caracteres no permitidos
const preventInvalidChars = (event) => {
    const char = event.key;
    
    // Permitir teclas de control (Backspace, Delete, Tab, Enter, etc.)
    if (event.ctrlKey || event.metaKey || 
        ['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(char)) {
        return;
    }
    
    // Solo permitir letras (incluidas las tildadas), espacios y guiones
    const isAllowed = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]$/.test(char);
    
    if (!isAllowed) {
        event.preventDefault();
    }
};

// Función para manejar el pegado de texto
const handlePaste = (event) => {
    event.preventDefault();
    
    // Obtener el texto del portapapeles
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    
    // Filtrar solo caracteres permitidos
    const filteredText = pastedText.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]/g, '');
    
    // Obtener la posición actual del cursor
    const input = event.target;
    const start = input.selectionStart;
    const end = input.selectionEnd;
    
    // Construir el nuevo valor
    const currentValue = empleado.value.nombre || '';
    const newValue = currentValue.substring(0, start) + filteredText + currentValue.substring(end);
    
    // Limitar a 255 caracteres
    empleado.value.nombre = newValue.substring(0, 255);
    
    // Establecer la nueva posición del cursor
    nextTick(() => {
        const newCursorPosition = start + filteredText.length;
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    });
};

const validateCargo = () => {
    if (empleado.value.cargo) {
        // Convertir a mayúsculas
        empleado.value.cargo = empleado.value.cargo.toUpperCase();
        
        // Eliminar números, caracteres especiales y letras con tildes
        // Solo permitir letras A-Z y espacios
        empleado.value.cargo = empleado.value.cargo.replace(/[^A-Z\s]/g, '');
        
        // Limitar longitud máxima
        if (empleado.value.cargo.length > 25) {
            empleado.value.cargo = empleado.value.cargo.substring(0, 25);
        }
    }
};

// Función para prevenir la escritura de caracteres no permitidos en cargo
const preventInvalidCharsCargo = (event) => {
    const char = event.key;
    
    // Permitir teclas de control (Backspace, Delete, Tab, Enter, etc.)
    if (event.ctrlKey || event.metaKey || 
        ['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(char)) {
        return;
    }
    
    // Solo permitir letras A-Z (se convertirán a mayúsculas) y espacios
    const isAllowed = /^[a-zA-Z\s]$/.test(char);
    
    if (!isAllowed) {
        event.preventDefault();
    }
};

// Función para manejar el pegado de texto en cargo
const handlePasteCargo = (event) => {
    event.preventDefault();
    
    // Obtener el texto del portapapeles
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    
    // Filtrar solo caracteres permitidos y convertir a mayúsculas
    const filteredText = pastedText.toUpperCase().replace(/[^A-Z\s]/g, '');
    
    // Obtener la posición actual del cursor
    const input = event.target;
    const start = input.selectionStart;
    const end = input.selectionEnd;
    
    // Construir el nuevo valor
    const currentValue = empleado.value.cargo || '';
    const newValue = currentValue.substring(0, start) + filteredText + currentValue.substring(end);
    
    // Limitar a 25 caracteres
    empleado.value.cargo = newValue.substring(0, 25);
    
    // Establecer la nueva posición del cursor
    nextTick(() => {
        const newCursorPosition = start + filteredText.length;
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    });
};

// Función para verificar si el nombre ya existe
const isNombreDuplicated = (nombre) => {
    if (!nombre || !empleados.value) return false;
    
    return empleados.value.some(emp => 
        emp.id !== empleado.value.id && // Excluir el empleado actual en caso de edición
        emp.nombre.toLowerCase().trim() === nombre.toLowerCase().trim()
    );
};

// Validaciones reactivas para el nombre
const nombreErrors = computed(() => {
    const errors = [];
    const nombre = empleado.value.nombre || '';
    
    if (nombre.length > 0 && nombre.length < 3) {
        errors.push('El nombre debe tener al menos 3 caracteres.');
    }
    
    if (nombre.length > 0 && /[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]/.test(nombre)) {
        errors.push('El nombre solo puede contener letras, espacios y guiones.');
    }
    
    if (nombre.length > 0 && isNombreDuplicated(nombre)) {
        errors.push('Ya existe un empleado con este nombre.');
    }
    
    return errors;
});

// Validaciones reactivas para el cargo
const cargoErrors = computed(() => {
    const errors = [];
    const cargo = empleado.value.cargo || '';
    
    if (cargo.length > 0 && cargo.length < 2) {
        errors.push('El cargo debe tener al menos 2 caracteres.');
    }
    
    if (cargo.length > 0 && /[^A-Z\s]/.test(cargo)) {
        errors.push('El cargo solo puede contener letras y espacios (sin tildes ni números).');
    }
    
    return errors;
});

// Validaciones reactivas de contraseña para el formulario principal
const passwordErrors = computed(() => {
    const errors = [];
    const value = empleado.value.password || '';
    if (value.length > 0 && value.length < 8) {
        errors.push('La contraseña debe tener al menos 8 caracteres.');
    }
    if (value.length > 0 && !/[A-Z]/.test(value)) {
        errors.push('La contraseña debe incluir al menos una letra mayúscula.');
    }
    if (value.length > 0 && !/[0-9]/.test(value)) {
        errors.push('La contraseña debe incluir al menos un número.');
    }
    if (value.length > 0 && /[\s.]/.test(value)) {
        errors.push('La contraseña no puede contener espacios ni puntos.');
    }
    return errors;
});

// Validación reactiva para confirmar contraseña en el formulario principal
const passwordConfirmationError = computed(() => {
    if (
        empleado.value.password_confirmation.length > 0 &&
        empleado.value.password !== empleado.value.password_confirmation
    ) {
        return 'Las contraseñas no coinciden.';
    }
    return '';
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Gestión de Usuarios" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Usuarios Internos</h1>
                <p class="text-gray-600">Gestión completa de empleados del sistema</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Empleados</h3>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                            @click="openNew">
                            <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 mr-1 text-white" /><span>&nbsp;Agregar empleado</span>
                        </button>
                    </div>
                </div>

                <DataTable
                    :value="filteredEmpleados"
                    dataKey="id"
                    :paginator="true"
                    :rows="rowsPerPage"
                    :rowsPerPageOptions="rowsPerPageOptions"
                    v-model:rowsPerPage="rowsPerPage"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} empleados"
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
                                        {{ filteredEmpleados.length }} resultado{{ filteredEmpleados.length !== 1 ? 's' : '' }}
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
                                        <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
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
                                    <InputText v-model="filters['global'].value" placeholder="🔍 Buscar empleados..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                                </div>
                            </div>
                        </div>
                    </template>
                    <Column field="nombre" header="Nombre" sortable class="w-28">
                        <template #body="slotProps">
                            <div
                                class="text-sm font-medium leading-relaxed overflow-hidden"
                                style="max-width: 150px; text-overflow: ellipsis; white-space: nowrap;"
                                :title="slotProps.data.nombre"
                            >
                                {{ slotProps.data.nombre }}
                            </div>
                        </template>
                    </Column>

                    <Column field="email" header="Email" class="w-28 hidden md:table-cell">
                        <template #body="slotProps">
                            <div
                                class="text-sm leading-relaxed overflow-hidden"
                                style="max-width: 200px; text-overflow: ellipsis; white-space: nowrap;"
                                :title="slotProps.data.email"
                            >
                                {{ slotProps.data.email }}
                            </div>
                        </template>
                    </Column>

                    <Column field="cargo" header="Cargo" class="w-32 hidden sm:table-cell">
                        <template #body="slotProps">
                            <div
                                class="text-sm leading-relaxed overflow-hidden"
                                style="max-width: 120px; text-overflow: ellipsis; white-space: nowrap;"
                                :title="slotProps.data.cargo"
                            >
                                {{ slotProps.data.cargo }}
                            </div>
                        </template>
                    </Column>

                    <Column field="telefono" header="Teléfono" class="w-16 hidden lg:table-cell">
                        <template #body="slotProps">
                            <div class="text-sm font-medium leading-relaxed">
                                {{ slotProps.data.telefono }}
                            </div>
                        </template>
                    </Column>

                    <Column field="estado" header="Estado" class="w-28 hidden sm:table-cell">
                        <template #body="slotProps">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Activo
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
                                    @click="editEmpleado(slotProps.data)">
                                    <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Editar</span>
                                </button>
                                <button
                                    class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                    @click="confirmDeleteEmpleado(slotProps.data)">
                                    <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                    <span class="hidden md:block text-white">Eliminar</span>
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!--Modal de formulario -->
                <Dialog v-model:visible="dialog" :header="btnTitle + ' Empleado'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                    <div class="space-y-4">
                        <!-- Nombre -->
                        <div class="w-full flex flex-col">
                            <div class="flex items-center gap-4">
                                <label for="nombre" class="w-24 flex items-center gap-1">
                                    Nombre: <span class="text-red-500 font-bold">*</span>
                                </label>
                                <InputText
                                    v-model.trim="empleado.nombre"
                                    id="nombre"
                                    name="nombre"
                                    :maxlength="255"
                                    :class="{'p-invalid': (submitted && (!empleado.nombre || nombreErrors.length > 0)) || nombreErrors.length > 0}"
                                    class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                    placeholder="Nombre completo del empleado (solo letras, espacios y guiones)"
                                    @keydown="preventInvalidChars"
                                    @paste="handlePaste"
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
                            <small class="text-gray-500 ml-28 mt-1" v-if="empleado.nombre && empleado.nombre.length > 0 && nombreErrors.length === 0">
                                Caracteres: {{ empleado.nombre.length }}/255
                            </small>
                            <small class="text-orange-500 ml-28 mt-1" v-if="empleado.nombre && empleado.nombre.length >= 230 && empleado.nombre.length <= 255 && nombreErrors.length === 0">
                                Caracteres restantes: {{ 255 - empleado.nombre.length }}
                            </small>
                            
                            <!-- Error de campo obligatorio -->
                            <small class="text-red-500 ml-28 mt-1" v-if="submitted && !empleado.nombre">
                                El nombre es obligatorio.
                            </small>
                        </div>

                        <!-- Email -->
                        <div class="w-full flex flex-col">
                            <div class="flex items-center gap-4">
                                <label for="email" class="w-24 flex items-center gap-1">
                                    Email: <span class="text-red-500 font-bold">*</span>
                                </label>
                                <InputText
                                    v-model.trim="empleado.email"
                                    id="email"
                                    name="email"
                                    type="email"
                                    :maxlength="255"
                                    :class="{'p-invalid': submitted && (!empleado.email || !isValidEmail(empleado.email))}"
                                    class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                    placeholder="correo@ejemplo.com"
                                />
                            </div>
                            <small class="text-red-500 ml-28" v-if="submitted && !empleado.email">
                                El email es obligatorio.
                            </small>
                            <small class="text-red-500 ml-28" v-if="empleado.email && !isValidEmail(empleado.email)">
                                El formato del email no es válido.
                            </small>
                        </div>

                        <!-- Contraseña - Solo para nuevos empleados -->
                        <div v-if="!empleado.id" class="w-full flex flex-col">
                            <div class="flex items-center gap-4">
                                <label for="password" class="w-24 flex items-center gap-1">
                                    Contraseña: <span class="text-red-500 font-bold">*</span>
                                </label>
                                <Password
                                    v-model="empleado.password"
                                    id="password"
                                    name="password"
                                    :class="{'p-invalid': submitted && passwordErrors.length > 0}"
                                    class="flex-1"
                                    placeholder="Contraseña segura"
                                    toggleMask
                                    :feedback="false"
                                    inputClass="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                />
                            </div>
                            <div v-if="passwordErrors.length > 0" class="ml-28 mt-1">
                                <small v-for="error in passwordErrors" :key="error" class="block text-red-500 text-xs">
                                    {{ error }}
                                </small>
                            </div>
                        </div>

                        <!-- Confirmar Contraseña - Solo para nuevos empleados -->
                        <div v-if="!empleado.id" class="w-full flex flex-col">
                            <div class="flex items-center gap-4">
                                <label for="password_confirmation" class="w-24 flex items-center gap-1">
                                    Confirmar: <span class="text-red-500 font-bold">*</span>
                                </label>
                                <Password
                                    v-model="empleado.password_confirmation"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :class="{'p-invalid': submitted && passwordConfirmationError}"
                                    class="flex-1"
                                    placeholder="Repetir contraseña"
                                    toggleMask
                                    :feedback="false"
                                    inputClass="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                />
                            </div>
                            <small v-if="passwordConfirmationError" class="text-red-500 ml-28 mt-1">
                                {{ passwordConfirmationError }}
                            </small>
                        </div>

                        <!-- Cargo -->
                        <div class="w-full flex flex-col">
                            <div class="flex items-center gap-4">
                                <label for="cargo" class="w-24 flex items-center gap-1">
                                    Cargo: <span class="text-red-500 font-bold">*</span>
                                </label>
                                <InputText
                                    v-model.trim="empleado.cargo"
                                    id="cargo"
                                    name="cargo"
                                    :maxlength="25"
                                    :class="{'p-invalid': (submitted && (!empleado.cargo || cargoErrors.length > 0)) || cargoErrors.length > 0}"
                                    class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                    placeholder="GERENTE, VENDEDOR, etc. (solo letras y espacios)"
                                    @keydown="preventInvalidCharsCargo"
                                    @paste="handlePasteCargo"
                                    @input="validateCargo"
                                />
                            </div>
                            
                            <!-- Mostrar errores de validación en tiempo real -->
                            <div v-if="cargoErrors.length > 0" class="ml-28 mt-1">
                                <small v-for="error in cargoErrors" :key="error" class="text-red-500 block">
                                    {{ error }}
                                </small>
                            </div>
                            
                            <!-- Mostrar contador de caracteres -->
                            <small class="text-gray-500 ml-28 mt-1" v-if="empleado.cargo && empleado.cargo.length > 0 && cargoErrors.length === 0">
                                Caracteres: {{ empleado.cargo.length }}/25
                            </small>
                            <small class="text-orange-500 ml-28 mt-1" v-if="empleado.cargo && empleado.cargo.length >= 20 && empleado.cargo.length <= 25 && cargoErrors.length === 0">
                                Caracteres restantes: {{ 25 - empleado.cargo.length }}
                            </small>
                            
                            <!-- Error de campo obligatorio -->
                            <small class="text-red-500 ml-28 mt-1" v-if="submitted && !empleado.cargo">
                                El cargo es obligatorio.
                            </small>
                        </div>

                        <!-- Teléfono -->
                        <div class="w-full flex flex-col">
                            <div class="flex items-start gap-4">
                                <label for="telefono" class="w-24 flex items-center gap-1 mt-2">
                                    Teléfono: <span class="text-red-500 font-bold">*</span>
                                </label>
                                <div class="flex-1">
                                    <VueTelInput
                                        v-model="empleado.telefono"
                                        defaultCountry="SV"
                                        :preferredCountries="['SV', 'GT', 'HN', 'CR', 'NI', 'PA', 'BZ']"
                                        :validCharactersOnly="true"
                                        :dropdownOptions="{
                                            showDialCodeInSelection: true,
                                            showFlags: true,
                                            showSearchBox: true,
                                            showDialCodeInList: true
                                        }"
                                        :inputOptions="{
                                            placeholder: 'Número de teléfono'
                                        }"
                                        mode="international"
                                        class="w-full border border-gray-300 rounded-lg"
                                        @validate="onValidate"
                                    />
                                </div>
                            </div>
                            <!-- Mensaje de validación -->
                            <div v-if="telefonoValidation.mensaje" class="ml-28 mt-1">
                                <small
                                    :class="[
                                        'text-xs flex items-center',
                                        telefonoValidation.isValid ? 'text-green-600' : 'text-red-600'
                                    ]"
                                >
                                    <span class="mr-1">
                                        {{ telefonoValidation.isValid ? '✓' : '⚠️' }}
                                    </span>
                                    {{ telefonoValidation.mensaje }}
                                </small>
                            </div>
                            <small class="text-red-500 ml-28" v-if="submitted && !empleado.telefono">
                                El teléfono es obligatorio.
                            </small>
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

                <!-- Componente de Modales de Empleados -->
                <Modales
                    v-model:visible="moreActionsDialog"
                    v-model:delete-visible="deleteDialog"
                    v-model:unsaved-changes-visible="unsavedChangesDialog"
                    v-model:password-visible="passwordDialog"
                    :empleado="selectedEmpleado || empleado"
                    :dialog-style="dialogStyle"
                    :is-deleting="isDeleting"
                    :is-loading="isLoading"
                    :submitted="submitted"
                    @change-password="handleChangePassword"
                    @send-credentials="handleSendCredentials"
                    @view-details="handleViewDetails"
                    @delete-empleado="deleteEmpleado"
                    @cancel-delete="() => deleteDialog = false"
                    @close-without-saving="closeDialogWithoutSaving"
                    @continue-editing="continueEditing"
                    @update-password="updatePassword"
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

/* Estilos para VueTelInput */
.vue-tel-input {
    border-radius: 0.375rem !important;
    border: 2px solid #9ca3af !important;
}

.vue-tel-input:hover {
    border-color: #6b7280 !important;
}

.vue-tel-input.focused {
    border-color: #6b7280 !important;
    box-shadow: none !important;
}

.vue-tel-input input {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}
</style>
