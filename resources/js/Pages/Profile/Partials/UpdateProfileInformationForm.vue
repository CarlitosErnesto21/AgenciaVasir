<script setup>
import { nextTick, watch, computed, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';
import { useToast } from 'primevue/usetoast';
import DatePicker from 'primevue/datepicker';
import Toast from 'primevue/toast';
import axios from 'axios';

// --- Validación de edad mínima para fecha de nacimiento (exactamente como en ModalDatosCliente.vue) ---
const fechaNacimientoError = ref('');
function getFechaMaximaNacimiento() {
    const fechaMaxima = new Date();
    fechaMaxima.setFullYear(fechaMaxima.getFullYear() - 18);
    return fechaMaxima;
}
function validarEdadMinima(fechaNacimiento) {
    if (!fechaNacimiento) return { esValido: true, mensaje: '' };

    const hoy = new Date();
    const fechaNac = new Date(fechaNacimiento);
    const edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mesNacimiento = fechaNac.getMonth();
    const diaNacimiento = fechaNac.getDate();
    const mesActual = hoy.getMonth();
    const diaActual = hoy.getDate();

    // Ajustar la edad si aún no ha cumplido años este año
    const edadReal = edad - ((mesActual < mesNacimiento || (mesActual === mesNacimiento && diaActual < diaNacimiento)) ? 1 : 0);

    if (edadReal < 18) {
        return {
            esValido: false,
            mensaje: `Debe ser mayor de edad (18 años). Edad actual: ${edadReal} años`
        };
    }

    return { esValido: true, mensaje: '' };
}
function onFechaNacimientoChange(value) {
    if (!value) {
        fechaNacimientoError.value = 'La fecha de nacimiento es requerida';
        return;
    }
    const validacion = validarEdadMinima(value);
    if (!validacion.esValido) {
        fechaNacimientoError.value = validacion.mensaje;
        toast.add({
            severity: 'error',
            summary: 'Edad insuficiente',
            detail: validacion.mensaje,
            life: 4000
        });
    } else {
        fechaNacimientoError.value = '';
    }
}

const toast = useToast();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    cliente: {
        type: Object,
        default: null,
    },
    empleado: {
        type: Object,
        default: null,
    },
});


const user = usePage().props.auth.user;
const cliente = usePage().props.cliente;
const empleado = usePage().props.empleado;

const baseForm = {
    name: user.name,
    email: user.email,
};

let extraFields = {};
if (user.roles?.some(r => r.name === 'Cliente')) {
    extraFields = {
        numero_identificacion: cliente?.numero_identificacion || '',
        fecha_nacimiento: cliente?.fecha_nacimiento || '',
        genero: cliente?.genero || '',
        tipo_documento: cliente?.tipo_documento || 'PASAPORTE',
        direccion: cliente?.direccion || '',
        telefono: cliente?.telefono || '',
    };
} else if (user.roles?.some(r => r.name === 'Empleado') || user.roles?.some(r => r.name === 'Administrador')) {
    extraFields = {
        cargo: empleado?.cargo || '',
        telefono: empleado?.telefono || '',
    };
}

const form = useForm({
    ...baseForm,
    ...extraFields
});

// Funciones de formateo automático (exactamente como en ModalDatosCliente.vue)
const formatearDUI = (valor) => {
    // Solo permitir números (eliminar TODO lo que no sea dígito)
    const soloNumeros = valor.replace(/[^0-9]/g, '');

    // Limitar a 9 dígitos máximo
    const numerosLimitados = soloNumeros.substring(0, 9);

    // Agregar guión automáticamente después del 8vo dígito
    if (numerosLimitados.length > 8) {
        return numerosLimitados.substring(0, 8) + '-' + numerosLimitados.substring(8);
    }

    return numerosLimitados;
};

const formatearPasaporte = (valor) => {
    // Solo permitir A-Z y 0-9, convertir a mayúsculas, máximo 9 caracteres
    return valor.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 9);
};

const manejarEntradaDocumento = (event) => {
    const valor = event.target.value;
    let valorFormateado = '';

    if (form.tipo_documento === 'DUI') {
        valorFormateado = formatearDUI(valor);
        form.numero_identificacion = valorFormateado;
        // Actualizar el valor del input inmediatamente
        event.target.value = valorFormateado;
    } else if (form.tipo_documento === 'PASAPORTE') {
        valorFormateado = formatearPasaporte(valor);
        form.numero_identificacion = valorFormateado;
        // Actualizar el valor del input inmediatamente
        event.target.value = valorFormateado;
    }

    // Validar después de formatear
    validarNumeroIdentificacion();

    // Validar duplicados si el formato es válido
    if (form.numero_identificacion && form.numero_identificacion.length >= 3) {
        const isValidFormat = (form.tipo_documento === 'DUI' && /^\d{8}-\d{1}$/.test(form.numero_identificacion)) ||
                             (form.tipo_documento === 'PASAPORTE' && /^[A-Z0-9]{6,9}$/.test(form.numero_identificacion));

        if (isValidFormat) {
            validarDocumentoUnico(form.numero_identificacion);
        } else {
            documentoValidation.value.mensaje = '';
            documentoValidation.value.isValid = true;
        }
    }
};

// Validación en tiempo real del número de identificación
const validarNumeroIdentificacion = () => {
    if (!form.numero_identificacion) {
        return;
    }

    if (form.tipo_documento === 'DUI') {
        const duiRegex = /^\d{8}-\d{1}$/;
        if (!duiRegex.test(form.numero_identificacion)) {
            // Error se manejará en computed
        }
    } else if (form.tipo_documento === 'PASAPORTE') {
        const pasaporteRegex = /^[A-Z0-9]{6,9}$/;
        if (!pasaporteRegex.test(form.numero_identificacion)) {
            // Error se manejará en computed
        }
    }
};

// Limpiar número de identificación al cambiar el tipo de documento
watch(() => form.tipo_documento, () => {
    form.numero_identificacion = '';
    documentoValidation.value.mensaje = '';
    documentoValidation.value.isValid = true;
});

// Watchers para validación en tiempo real (como en ModalDatosCliente.vue)
watch(() => form.numero_identificacion, (newValue) => {
    validarNumeroIdentificacion();

    // Validar duplicados si el formato es válido
    if (newValue && newValue.length >= 3) {
        const isValidFormat = (form.tipo_documento === 'DUI' && /^\d{8}-\d{1}$/.test(newValue)) ||
                             (form.tipo_documento === 'PASAPORTE' && /^[A-Z0-9]{6,9}$/.test(newValue));

        if (isValidFormat) {
            validarDocumentoUnico(newValue);
        } else {
            documentoValidation.value.mensaje = '';
            documentoValidation.value.isValid = true;
        }
    } else {
        documentoValidation.value.mensaje = '';
        documentoValidation.value.isValid = true;
    }
});

// Watcher para validar nombre en tiempo real
watch(() => form.name, async (newValue) => {
    if (newValue && newValue.trim().length >= 3) {
        // Verificar formato válido antes de validar duplicados
        if (nameRegex.test(newValue.trim())) {
            await validarNombreUnico(newValue.trim());
        } else {
            nombreValidation.value.mensaje = '';
            nombreValidation.value.isValid = true;
        }
    } else {
        nombreValidation.value.mensaje = '';
        nombreValidation.value.isValid = true;
    }
});

// Watcher para validar email en tiempo real
watch(() => form.email, async (newValue) => {
    if (newValue && newValue.trim().length >= 5) {
        // Verificar formato válido antes de validar duplicados
        if (emailRegex.test(newValue.trim())) {
            await validarEmailUnico(newValue.trim());
        } else {
            emailValidation.value.mensaje = '';
            emailValidation.value.isValid = true;
        }
    } else {
        emailValidation.value.mensaje = '';
        emailValidation.value.isValid = true;
    }
});

// Watchers para limpiar errores cuando se completan los campos
watch(() => form.fecha_nacimiento, (newValue) => {
    if (newValue) {
        const validacion = validarEdadMinima(newValue);
        if (!validacion.esValido) {
            fechaNacimientoError.value = validacion.mensaje;
            toast.add({
                severity: 'error',
                summary: 'Edad insuficiente',
                detail: validacion.mensaje,
                life: 4000
            });
        } else {
            fechaNacimientoError.value = '';
        }
    }
});

// Watchers adicionales para limpiar errores (como en ModalDatosCliente.vue)
watch(() => form.genero, (newValue) => {
    // Los errores se manejan automáticamente en computed
});

watch(() => form.direccion, (newValue) => {
    // Los errores se manejan automáticamente en computed
});

watch(() => form.telefono, (newValue) => {
    // Si el teléfono es válido y cambió, revalidar duplicados
    if (newValue && telefonoValidation.value.isValid && newValue.length >= 8) {
        const telefonoActual = isCliente.value ? cliente?.telefono : empleado?.telefono;
        if (newValue !== telefonoActual) {
            validarTelefonoUnico(newValue);
        }
    }
});

// --- Validación de nombre, email, cargo y teléfono (como FormularioDatosCompletos.vue y RegisteredUserController) ---
const nameRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+(\s[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+)*$/;
const emailRegex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;

const nameErrors = computed(() => {
    const errors = [];
    const name = form.name?.trim() || '';
    if (!name) errors.push('El nombre es obligatorio.');
    else {
        if (name.length > 255) errors.push('El nombre no puede exceder 255 caracteres.');
        if (!nameRegex.test(name)) errors.push('El nombre solo puede contener letras y espacios.');
    }
    return errors;
});

const emailErrors = computed(() => {
    const errors = [];
    const email = form.email?.trim() || '';
    if (!email) errors.push('El correo electrónico es obligatorio.');
    else {
        if (email.length > 255) errors.push('El correo electrónico no puede exceder 255 caracteres.');
        if (!emailRegex.test(email)) errors.push('El formato del correo electrónico no es válido.');
    }
    return errors;
});
const telefonoValidation = ref({
    isValid: false,
    country: null,
    formattedNumber: '',
    mensaje: ''
});

const documentoValidation = ref({
    isValid: true,
    mensaje: ''
});

const nombreValidation = ref({
    isValid: true,
    mensaje: ''
});

const emailValidation = ref({
    isValid: true,
    mensaje: ''
});

const submitted = ref(false);

// Validar que el documento no esté duplicado
const validarDocumentoUnico = async (numeroIdentificacion) => {
    if (!numeroIdentificacion || numeroIdentificacion.length < 3) return;

    try {
        // Verificar si es el mismo documento que ya tenía el cliente
        const documentoActual = cliente?.numero_identificacion;
        const esMismoDocumento = numeroIdentificacion === documentoActual;

        if (esMismoDocumento) {
            documentoValidation.value.mensaje = '✓ Este es tu documento actual';
            documentoValidation.value.isValid = true;
            return;
        }

        const response = await axios.post('/api/clientes/validar-documento', {
            numero_identificacion: numeroIdentificacion,
            usuario_id: user.id // Excluir el usuario actual
        });

        if (response.data.disponible) {
            documentoValidation.value.mensaje = '✓ Número de identificación disponible';
            documentoValidation.value.isValid = true;
        } else {
            documentoValidation.value.isValid = false;
            documentoValidation.value.mensaje = response.data.message || 'Este número de identificación ya está registrado';
        }
    } catch (error) {
        console.error('[UpdateProfile] Error validando documento:', error);
        documentoValidation.value.isValid = false;
        if (error.response?.status === 403) {
            documentoValidation.value.mensaje = 'No tienes permisos para validar este documento';
        } else if (error.response?.status === 401) {
            documentoValidation.value.mensaje = 'Error de autenticación';
        } else {
            documentoValidation.value.mensaje = 'Error al validar documento';
        }
    }
};

const validateCargo = () => {
    if (form.cargo) {
        form.cargo = form.cargo.toUpperCase().replace(/[^A-Z\s]/g, '').substring(0, 25);
    }
};

const onValidate = async (phoneObject) => {
    try {
        if (phoneObject && typeof phoneObject === 'object') {
            telefonoValidation.value.isValid = phoneObject.valid === true;
            telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode };
            telefonoValidation.value.formattedNumber = phoneObject.formatted || '';

            // CLAVE: Actualizar el modelo inmediatamente como hace el modal
            if (phoneObject.valid === true && phoneObject.formatted) {
                form.telefono = phoneObject.formatted;
            }

            // Verificar si el teléfono actual es el mismo que ya tenía el usuario
            const telefonoActual = isCliente.value ? cliente?.telefono : empleado?.telefono;
            const esMismoTelefono = form.telefono === telefonoActual;

            if (phoneObject.valid === false) {
                // Siempre mostrar error cuando el teléfono es inválido
                telefonoValidation.value.mensaje = '✗ Número de teléfono inválido para ' + phoneObject.country;
                telefonoValidation.value.isValid = false;
            } else if (esMismoTelefono && phoneObject.valid === true) {
                telefonoValidation.value.mensaje = '✓ Número válido (tu teléfono actual)';
                telefonoValidation.value.isValid = true;
            } else if (phoneObject.valid === true) {
                // Validar duplicados vía API
                await validarTelefonoUnico(form.telefono);
            } else {
                telefonoValidation.value.mensaje = '';
            }
        }
    } catch (error) {
        console.error('[UpdateProfile] Error en validación:', error);
        telefonoValidation.value.mensaje = 'Error en validación';
    }
};

// Validar que el nombre no esté duplicado
const validarNombreUnico = async (nombre) => {
    if (!nombre || nombre.trim().length < 3) return;

    try {
        // Verificar si es el mismo nombre que ya tenía el usuario
        const nombreActual = user.name;
        const esMismoNombre = nombre.trim() === nombreActual;

        if (esMismoNombre) {
            nombreValidation.value.mensaje = '✓ Este es tu nombre actual';
            nombreValidation.value.isValid = true;
            return;
        }

        const response = await axios.post('/api/users/validar-nombre', {
            name: nombre.trim(),
            usuario_id: user.id // Excluir el usuario actual
        });

        if (response.data.disponible) {
            nombreValidation.value.mensaje = '✓ Nombre disponible';
            nombreValidation.value.isValid = true;
        } else {
            nombreValidation.value.isValid = false;
            nombreValidation.value.mensaje = response.data.message || 'Este nombre ya está registrado por otro usuario';
        }
    } catch (error) {
        console.error('[UpdateProfile] Error validando nombre:', error);
        nombreValidation.value.isValid = false;
        if (error.response?.status === 403) {
            nombreValidation.value.mensaje = 'No tienes permisos para validar este nombre';
        } else if (error.response?.status === 401) {
            nombreValidation.value.mensaje = 'Error de autenticación';
        } else {
            nombreValidation.value.mensaje = 'Error al validar nombre';
        }
    }
};

// Validar que el email no esté duplicado
const validarEmailUnico = async (email) => {
    if (!email || email.trim().length < 5) return;

    try {
        // Verificar si es el mismo email que ya tenía el usuario
        const emailActual = user.email;
        const esMismoEmail = email.trim().toLowerCase() === emailActual.toLowerCase();

        if (esMismoEmail) {
            emailValidation.value.mensaje = '✓ Este es tu email actual';
            emailValidation.value.isValid = true;
            return;
        }

        const response = await axios.post('/api/users/validar-email', {
            email: email.trim().toLowerCase(),
            usuario_id: user.id // Excluir el usuario actual
        });

        if (response.data.disponible) {
            emailValidation.value.mensaje = '✓ Email disponible';
            emailValidation.value.isValid = true;
        } else {
            emailValidation.value.isValid = false;
            emailValidation.value.mensaje = response.data.message || 'Este email ya está registrado por otro usuario';
        }
    } catch (error) {
        console.error('[UpdateProfile] Error validando email:', error);
        emailValidation.value.isValid = false;
        if (error.response?.status === 403) {
            emailValidation.value.mensaje = 'No tienes permisos para validar este email';
        } else if (error.response?.status === 401) {
            emailValidation.value.mensaje = 'Error de autenticación';
        } else {
            emailValidation.value.mensaje = 'Error al validar email';
        }
    }
};

// Validar que el teléfono no esté duplicado según el rol del usuario
const validarTelefonoUnico = async (telefono) => {
    if (!telefono || telefono.length < 8) return;

    try {
        if (isCliente.value) {
            // Solo validar en clientes para usuarios con rol Cliente
            const responseClientes = await axios.post('/api/clientes/validar-telefono', {
                telefono: telefono,
                usuario_id: user.id // Excluir el usuario actual
            });

            if (responseClientes.data.disponible) {
                telefonoValidation.value.mensaje = '✓ Número válido para ' + telefonoValidation.value.country?.name;
                telefonoValidation.value.isValid = true;
            } else {
                telefonoValidation.value.isValid = false;
                telefonoValidation.value.mensaje = responseClientes.data.message || 'Este teléfono ya está registrado por otro cliente';
            }
        } else if (isEmpleado.value) {
            // Solo validar en empleados para usuarios con rol Empleado/Administrador
            const responseEmpleados = await axios.get('/api/empleados/check-telefono', {
                params: {
                    telefono: telefono,
                    usuario_id: user.id // Excluir el usuario actual
                }
            });

            if (responseEmpleados.data.available) {
                telefonoValidation.value.mensaje = '✓ Número válido para ' + telefonoValidation.value.country?.name;
                telefonoValidation.value.isValid = true;
            } else {
                telefonoValidation.value.isValid = false;
                telefonoValidation.value.mensaje = responseEmpleados.data.message || 'Este teléfono ya está registrado por otro empleado';
            }
        } else {
            // Si no tiene rol definido, asumir válido
            telefonoValidation.value.mensaje = '✓ Número válido para ' + telefonoValidation.value.country?.name;
            telefonoValidation.value.isValid = true;
        }
    } catch (error) {
        console.error('[UpdateProfile] Error validando teléfono:', error);
        telefonoValidation.value.isValid = false;
        if (error.response?.status === 403) {
            telefonoValidation.value.mensaje = 'No tienes permisos para validar este teléfono';
        } else if (error.response?.status === 401) {
            telefonoValidation.value.mensaje = 'Error de autenticación';
        } else {
            telefonoValidation.value.mensaje = 'Error al validar teléfono';
        }
    }
};const cargoErrors = computed(() => {
    const errors = [];
    const cargo = form.cargo || '';
    if (cargo.length > 0 && cargo.length < 2) {
        errors.push('El cargo debe tener al menos 2 caracteres.');
    }
    if (cargo.length > 0 && /[^A-Z\s]/.test(cargo)) {
        errors.push('El cargo solo puede contener letras y espacios (sin tildes ni números).');
    }
    if (cargo.length > 25) {
        errors.push('El cargo no puede exceder 25 caracteres.');
    }
    return errors;
});

const telefonoErrors = computed(() => {
    const errors = [];
    const telefono = form.telefono || '';
    if (telefono.length > 0 && !telefonoValidation.value.isValid) {
        errors.push('El formato del teléfono no es válido.');
    }
    if (telefono.length > 30) {
        errors.push('El teléfono no puede exceder 30 caracteres.');
    }
    return errors;
});


// --- Validación de número de identificación (DUI/PASAPORTE), fecha, género, dirección, teléfono ---
const identificacionErrors = computed(() => {
    const errors = [];
    if (isCliente.value) {
        const tipo = form.tipo_documento;
        const num = (form.numero_identificacion || '').trim();
        if (!num) {
            errors.push('El número de identificación es obligatorio.');
        } else if (tipo === 'DUI') {
            const duiRegex = /^\d{8}-\d{1}$/;
            if (!duiRegex.test(num)) {
                errors.push('El DUI debe tener 9 dígitos (formato: 12345678-9)');
            }
        } else if (tipo === 'PASAPORTE') {
            const pasaporteRegex = /^[A-Z0-9]{6,9}$/;
            if (!pasaporteRegex.test(num)) {
                errors.push('El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)');
            }
        }
    }
    return errors;
});

const generoErrors = computed(() => {
    const errors = [];
    if (isCliente.value) {
        if (!form.genero) {
            errors.push('El género es requerido');
        } else if (form.genero !== 'MASCULINO' && form.genero !== 'FEMENINO') {
            errors.push('El género debe ser Masculino o Femenino');
        }
    }
    return errors;
});

const direccionErrors = computed(() => {
    const errors = [];
    if (isCliente.value) {
        if (!form.direccion || !form.direccion.trim()) {
            errors.push('La dirección es requerida');
        } else if (form.direccion.length > 200) {
            errors.push('La dirección no puede exceder 200 caracteres');
        }
    }
    return errors;
});

// --- Submit con validación previa ---
const submitForm = () => {
    submitted.value = true;
    validateCargo();
    // Validación nombre
    if (nameErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Nombre',
            detail: nameErrors.value[0],
            life: 4000
        });
        return;
    }

    // Verificar si el nombre no está validado por duplicados
    if (form.name && !nombreValidation.value.isValid) {
        toast.add({
            severity: 'warn',
            summary: 'Nombre no disponible',
            detail: nombreValidation.value.mensaje || 'Este nombre no está disponible. Por favor, use un nombre diferente.',
            life: 5000
        });
        return;
    }
    // Validación email
    if (emailErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Email',
            detail: emailErrors.value[0],
            life: 4000
        });
        return;
    }

    // Verificar si el email no está validado por duplicados
    if (form.email && !emailValidation.value.isValid) {
        toast.add({
            severity: 'warn',
            summary: 'Email no disponible',
            detail: emailValidation.value.mensaje || 'Este email no está disponible. Por favor, use un email diferente.',
            life: 5000
        });
        return;
    }
    // Validación número de identificación (solo para cliente)
    if (identificacionErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Identificación',
            detail: identificacionErrors.value[0],
            life: 4000
        });
        return;
    }
    // Validación fecha de nacimiento (mayor de edad)
    if (isCliente.value && fechaNacimientoError.value) {
        toast.add({
            severity: 'error',
            summary: 'Error en Fecha de Nacimiento',
            detail: fechaNacimientoError.value,
            life: 4000
        });
        return;
    }
    // Validación género
    if (generoErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Género',
            detail: generoErrors.value[0],
            life: 4000
        });
        return;
    }
    // Validación dirección
    if (direccionErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Dirección',
            detail: direccionErrors.value[0],
            life: 4000
        });
        return;
    }
    // Validación cargo
    if (cargoErrors.value && cargoErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Cargo',
            detail: cargoErrors.value[0],
            life: 4000
        });
        return;
    }
    // Validación teléfono
    if (telefonoErrors.value && telefonoErrors.value.length > 0) {
        toast.add({
            severity: 'error',
            summary: 'Error en Teléfono',
            detail: telefonoErrors.value[0],
            life: 4000
        });
        return;
    }

    // Verificar si el teléfono no está validado por duplicados
    if ((isCliente.value || isEmpleado.value) && form.telefono && !telefonoValidation.value.isValid) {
        toast.add({
            severity: 'warn',
            summary: 'Teléfono no disponible',
            detail: telefonoValidation.value.mensaje || 'Este número de teléfono no está disponible. Por favor, use un número diferente.',
            life: 5000
        });
        return;
    }

    // Verificar si el documento no está validado por duplicados
    if (isCliente.value && form.numero_identificacion && !documentoValidation.value.isValid) {
        toast.add({
            severity: 'warn',
            summary: 'Documento no disponible',
            detail: documentoValidation.value.mensaje || 'Este número de identificación no está disponible. Por favor, use un documento diferente.',
            life: 5000
        });
        return;
    }
    form.patch(route('profile.update'));
};

const roles = computed(() => user?.roles || []);
const isCliente = computed(() => roles.value.some(r => r.name === 'Cliente'));
const isEmpleado = computed(() => !isCliente.value && roles.value.some(r => r.name === 'Empleado' || r.name === 'Administrador'));

// Mostrar toast de éxito cuando la información se actualiza correctamente
watch(
    () => form.recentlySuccessful,
    (val) => {
        if (val) {
            toast.add({
                severity: 'success',
                summary: '¡Éxito!',
                detail: 'Información actualizada correctamente',
                life: 4000
            });
        }
    }
);
</script>

<template>
    <section class="bg-white/90 rounded-2xl shadow-lg border border-gray-100 p-4 sm:p-6 md:p-8">
        <form
            @submit.prevent="submitForm"
            class="space-y-8"
        >

            <!-- Encabezado de tipo de datos -->
            <template v-if="isCliente">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Datos de Cliente</h3>
            </template>
            <template v-else-if="isEmpleado">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Datos de Empleado</h3>
            </template>

            <!-- Campos en grid responsivo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Campo Nombre -->
                <div class="space-y-2">
                    <InputLabel for="name" value="Nombre Completo" class="text-gray-700 font-semibold text-base" />
                    <div class="relative">
                        <TextInput
                            id="name"
                            type="text"
                            class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md px-4 py-3 text-gray-900 text-base bg-white placeholder-gray-400 transition-colors"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Tu nombre completo"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    </div>
                    <!-- Mensaje de validación en tiempo real del nombre -->
                    <small
                        v-if="nombreValidation.mensaje"
                        :class="[
                            'block mt-1',
                            nombreValidation.isValid ? 'text-green-600' : 'text-red-500'
                        ]"
                    >
                        {{ nombreValidation.mensaje }}
                    </small>
                    <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
                    <InputError v-if="form.errors.name && !nombreValidation.mensaje" class="text-red-600 text-sm" :message="form.errors.name" />
                </div>

                <!-- Campo Email -->
                <div class="space-y-2">
                    <InputLabel for="email" value="Correo Electrónico" class="text-gray-700 font-semibold text-base" />
                    <div class="relative">
                        <TextInput
                            id="email"
                            type="email"
                            class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md px-4 py-3 text-gray-900 text-base bg-white placeholder-gray-400 transition-colors"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="tu@email.com"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                    </div>
                    <!-- Mensaje de validación en tiempo real del email -->
                    <small
                        v-if="emailValidation.mensaje"
                        :class="[
                            'block mt-1',
                            emailValidation.isValid ? 'text-green-600' : 'text-red-500'
                        ]"
                    >
                        {{ emailValidation.mensaje }}
                    </small>
                    <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
                    <InputError v-if="form.errors.email && !emailValidation.mensaje" class="text-red-600 text-sm" :message="form.errors.email" />
                </div>

            </div>

            <!-- Campos de Cliente -->
            <template v-if="isCliente">
                <div class="mt-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="tipo_documento" value="Tipo de Documento" />
                            <select id="tipo_documento" v-model="form.tipo_documento" class="mt-1 block w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md">
                                <option value="PASAPORTE">PASAPORTE</option>
                                <option value="DUI">DUI</option>
                            </select>
                            <InputError :message="form.errors.tipo_documento" />
                        </div>
                        <div>
                            <InputLabel for="numero_identificacion" value="Número de Identificación" />
                            <input
                                id="numero_identificacion"
                                :value="form.numero_identificacion"
                                @input="manejarEntradaDocumento"
                                :maxlength="form.tipo_documento === 'DUI' ? 10 : 9"
                                type="text"
                                required
                                class="mt-1 block w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md px-4 py-3 text-gray-900 text-base bg-white placeholder-gray-400"
                                :class="{ 'border-red-500': form.errors.numero_identificacion || (submitted && identificacionErrors[0]) }"
                                :placeholder="form.tipo_documento === 'DUI' ? 'Ingrese 9 dígitos (ej: 123456789)' : 'Ingrese su PASAPORTE (ej: A1B2C3D4)'"
                            />
                            <!-- Mensaje de validación en tiempo real del documento -->
                            <small
                                v-if="documentoValidation.mensaje"
                                :class="[
                                    'block mt-1',
                                    documentoValidation.isValid ? 'text-green-600' : 'text-red-500'
                                ]"
                            >
                                {{ documentoValidation.mensaje }}
                            </small>
                            <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
                            <InputError v-if="form.errors.numero_identificacion && !documentoValidation.mensaje" :message="(form.errors.numero_identificacion || (submitted && identificacionErrors[0])) || ''" />
                        </div>

                        <div>
                            <InputLabel for="fecha_nacimiento" value="Fecha de Nacimiento" />
                            <DatePicker
                                v-model="form.fecha_nacimiento"
                                :maxDate="getFechaMaximaNacimiento()"
                                dateFormat="dd/mm/yy"
                                placeholder="Seleccionar fecha de nacimiento (debe ser mayor de 18 años)"
                                showIcon
                                yearNavigator
                                yearRange="1920:2006"
                                class="mt-1 w-full"
                                :inputClass="`w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md px-4 py-3 text-gray-900 text-base bg-white placeholder-gray-400 ${(form.errors.fecha_nacimiento || (submitted && fechaNacimientoError)) ? 'border-red-500' : ''}`"
                                @update:modelValue="onFechaNacimientoChange"
                            />
                            <InputError :message="form.errors.fecha_nacimiento || (submitted && fechaNacimientoError) || ''" />
                        </div>
                        <div>
                            <InputLabel for="genero" value="Género" />
                            <select id="genero" v-model="form.genero" class="mt-1 block w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md">
                                <option value="">Seleccione...</option>
                                <option value="MASCULINO">Masculino</option>
                                <option value="FEMENINO">Femenino</option>
                            </select>
                            <InputError :message="form.errors.genero || (submitted && generoErrors[0]) || ''" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="telefono" value="Teléfono" />
                            <VueTelInput
                                v-model="form.telefono"
                                id="telefono"
                                name="telefono"
                                defaultCountry="SV"
                                :preferredCountries="['SV', 'GT', 'HN', 'CR', 'NI', 'PA', 'BZ']"
                                :validCharactersOnly="true"
                                :dropdownOptions="{ showDialCodeInSelection: true, showFlags: true, showSearchBox: true, showDialCodeInList: true }"
                                :inputOptions="{ placeholder: 'Número de teléfono' }"
                                mode="international"
                                class="mt-1 w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md"
                                @validate="onValidate"
                                :disabled="form.processing"
                            />
                            <!-- Mensaje de validación -->
                            <p
                                v-if="telefonoValidation.mensaje"
                                :class="[
                                    'text-xs mt-1',
                                    telefonoValidation.isValid ? 'text-green-600' : 'text-red-600'
                                ]"
                            >
                                {{ telefonoValidation.mensaje }}
                            </p>
                            <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
                            <InputError v-if="form.errors.telefono && !telefonoValidation.mensaje" :message="form.errors.telefono || (submitted && telefonoErrors[0]) || ''" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="direccion" value="Dirección" />
                            <TextInput id="direccion" v-model="form.direccion" class="mt-1 block w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md" required />
                            <InputError :message="form.errors.direccion || (submitted && direccionErrors[0]) || ''" />
                        </div>
                    </div>
                </div>
            </template>
            <!-- Campos de Empleado -->
            <template v-else-if="isEmpleado">
                <div class="mt-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="cargo" value="Cargo" />
                            <input
                                id="cargo"
                                v-model="form.cargo"
                                type="text"
                                class="mt-1 block w-full border-2 rounded-md px-2 py-2 focus:ring-0 focus:shadow-none outline-none"
                                :class="{
                                    'border-gray-400 hover:border-gray-500 focus:border-gray-500 bg-white': !roles.some(r => r.name === 'Empleado'),
                                    'border-gray-300 bg-gray-50 cursor-not-allowed': roles.some(r => r.name === 'Empleado')
                                }"
                                maxlength="25"
                                @input="validateCargo"
                                @keydown="(e) => { if (!/^[a-zA-Z\s]$/.test(e.key) && !e.ctrlKey && !e.metaKey && !['Backspace','Delete','Tab','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].includes(e.key)) e.preventDefault(); }"
                                @paste="(e) => { e.preventDefault(); const t = (e.clipboardData || window.clipboardData).getData('text').toUpperCase().replace(/[^A-Z\s]/g, ''); const input = e.target; const start = input.selectionStart; const end = input.selectionEnd; const current = form.cargo || ''; const nv = current.substring(0, start) + t + current.substring(end); form.cargo = nv.substring(0, 25); nextTick(() => { input.setSelectionRange(start + t.length, start + t.length); }); }"
                                :disabled="roles.some(r => r.name === 'Empleado')"
                                :readonly="roles.some(r => r.name === 'Empleado')"
                                required
                            />

                            <!-- Mensaje informativo para empleados -->
                            <div v-if="roles.some(r => r.name === 'Empleado')" class="mt-2 bg-blue-50 border border-blue-200 rounded-md p-3">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-blue-800 text-sm font-medium">
                                            Solo el Administrador puede modificar tu cargo
                                        </p>
                                        <p class="text-blue-700 text-xs mt-1">
                                            Si necesitas cambiar tu cargo, contacta al Administrador de tu empresa.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.errors.cargo" class="mt-1">
                                <small class="text-red-500 block">{{ form.errors.cargo }}</small>
                            </div>
                            <small class="text-gray-500 mt-1" v-if="form.cargo && form.cargo.length > 0 && cargoErrors.length === 0 && !roles.some(r => r.name === 'Empleado')">
                                Caracteres: {{ form.cargo.length }}/25
                            </small>
                            <small class="text-orange-500 mt-1" v-if="form.cargo && form.cargo.length >= 20 && form.cargo.length <= 25 && cargoErrors.length === 0 && !roles.some(r => r.name === 'Empleado')">
                                Caracteres restantes: {{ 25 - form.cargo.length }}
                            </small>
                        </div>
                        <div>
                            <InputLabel for="telefono" value="Teléfono" />
                            <VueTelInput
                                v-model="form.telefono"
                                id="telefono"
                                name="telefono"
                                defaultCountry="SV"
                                :preferredCountries="['SV', 'GT', 'HN', 'CR', 'NI', 'PA', 'BZ']"
                                :validCharactersOnly="true"
                                :dropdownOptions="{ showDialCodeInSelection: true, showFlags: true, showSearchBox: true, showDialCodeInList: true }"
                                :inputOptions="{ placeholder: 'Número de teléfono' }"
                                mode="international"
                                class="w-full border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none outline-none rounded-md"
                                @validate="onValidate"
                                :disabled="form.processing"
                            />
                            <!-- Mensaje de validación en tiempo real -->
                            <p
                                v-if="telefonoValidation.mensaje"
                                :class="[
                                    'text-xs mt-1',
                                    telefonoValidation.isValid ? 'text-green-600' : 'text-red-600'
                                ]"
                            >
                                {{ telefonoValidation.mensaje }}
                            </p>
                            <!-- Solo mostrar errores de formulario si no hay mensaje de validación en tiempo real -->
                            <div v-if="form.errors.telefono && !telefonoValidation.mensaje" class="mt-1">
                                <small class="text-red-500 block">{{ form.errors.telefono }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Alerta de verificación de email -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null"
                 class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 sm:p-4 mt-2 shadow-sm">
                <div class="flex flex-col sm:flex-row items-start gap-3">
                    <div class="flex-shrink-0 flex justify-center sm:block mb-2 sm:mb-0">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-yellow-800 text-base font-medium mb-3">
                            Tu dirección de correo electrónico no está verificada.
                        </p>
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-base font-semibold
                                   rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500
                                   transition-colors w-full sm:w-auto"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.82 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Reenviar verificación
                        </Link>

                        <div
                            v-show="status === 'verification-link-sent'"
                            class="mt-3 p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-base shadow-sm w-full"
                        >
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Enlace de verificación enviado a tu correo.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-8 border-t border-gray-100 mt-2">
                <PrimaryButton
                    :disabled="form.processing"
                    class="inline-flex items-center px-7 py-3 text-white text-base font-semibold rounded-lg shadow-md transition-colors w-full sm:w-auto
                        bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500
                        disabled:bg-red-800 disabled:text-white disabled:border-red-900 disabled:opacity-100 disabled:cursor-wait border-2 border-red-200"
                >
                    <template v-if="form.processing">
                        <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Guardando...
                    </template>
                    <template v-else>
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Cambios
                    </template>
                </PrimaryButton>
                <!-- Toast de éxito se muestra desde el script -->
            </div>
        </form>
    </section>

    <!-- Toast component para mostrar notificaciones -->
    <Toast class="z-[9999]" />
</template>

<style scoped>
.disabled\:bg-red-800:disabled {
    background-color: #991b1b !important;
}
.disabled\:border-red-900:disabled {
    border-color: #7f1d1d !important;
}
.disabled\:cursor-wait:disabled {
    cursor: wait !important;
}
</style>
