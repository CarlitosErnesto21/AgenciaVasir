
<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';
import { useToast } from 'primevue/usetoast';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faInfoCircle, faLightbulb, faCheck } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
	empleado: {
		type: Object,
		default: () => ({})
	},
	userId: {
		type: Number,
		required: true
	}
});


const emit = defineEmits(['close']);
const toast = useToast();

// Manejo del tamaño de ventana para estilos responsivos
const windowWidth = ref(window.innerWidth);

const updateWindowWidth = () => {
	windowWidth.value = window.innerWidth;
};

onMounted(() => {
	window.addEventListener('resize', updateWindowWidth);
});

onUnmounted(() => {
	window.removeEventListener('resize', updateWindowWidth);
});

const dialogStyle = computed(() => {
	if (windowWidth.value < 640) {
		return { width: '95vw', maxWidth: '380px' };
	} else if (windowWidth.value < 768) {
		return { width: '400px' };
	} else {
		return { width: '450px' };
	}
});

const form = useForm({
	cargo: props.empleado?.cargo || 'PROPIETARIA',
	telefono: props.empleado?.telefono || '',
	user_id: props.userId,
});

const telefonoValidation = ref({
	isValid: false,
	country: null,
	formattedNumber: '',
	mensaje: ''
});

const submitted = ref(false);

// Validación y transformación de cargo
const validateCargo = () => {
	if (form.cargo) {
		// Solo letras y espacios, mayúsculas, máximo 25 caracteres
		form.cargo = form.cargo.toUpperCase().replace(/[^A-Z\s]/g, '').substring(0, 25);
	}
};

// Validación de teléfono usando vue-tel-input

const onValidate = (phoneObject) => {
	try {
		if (phoneObject && typeof phoneObject === 'object') {
			telefonoValidation.value.isValid = phoneObject.valid === true;
			telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode };
			telefonoValidation.value.formattedNumber = phoneObject.formatted || '';
			telefonoValidation.value.international = phoneObject.international || '';
			// Guardar SIEMPRE el valor internacional (+503 ...)
			if (phoneObject.valid === true && phoneObject.international) {
				form.telefono = phoneObject.international;
			}
			if (form.telefono && phoneObject.valid === false) {
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

const cargoErrors = computed(() => {
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

// Computed para filtrar errores de servidor que ya se muestran en toast
const shouldShowServerError = computed(() => {
	if (!form.errors.telefono) return false;
	// No mostrar errores de validación requerida o genéricos en el template
	if (form.errors.telefono === 'validation.required' || 
		form.errors.telefono.includes('required') ||
		form.errors.telefono.includes('requerido')) {
		return false;
	}
	return true;
});

const closeModal = () => {
	emit('close');
};



const submit = () => {
	submitted.value = true;
	validateCargo();
	// Debug: mostrar valores antes de enviar
	// eslint-disable-next-line no-console
	console.debug('[DEBUG] Enviando datos:', {
		cargo: form.cargo,
		telefono: form.telefono,
		telefonoValidation: telefonoValidation.value
	});

	// Asegurar que el teléfono tenga el código de país (internacional)
	if (telefonoValidation.value.isValid && telefonoValidation.value.international) {
		form.telefono = telefonoValidation.value.international;
	}

	if (cargoErrors.value.length > 0 || telefonoErrors.value.length > 0) {
		// eslint-disable-next-line no-console
		console.debug('[DEBUG] Errores de validación:', { cargoErrors: cargoErrors.value, telefonoErrors: telefonoErrors.value });
		return;
	}
	form.post(route('empleado.completar-datos'), {
		preserveScroll: true,
		onSuccess: () => {
			toast.add({
				severity: 'success',
				summary: '¡Datos guardados!',
				detail: 'Tus datos se han guardado correctamente.',
				life: 4000
			});
			// eslint-disable-next-line no-console
			console.debug('[DEBUG] Guardado exitoso, cerrando modal');
			closeModal();
		},
		onError: (errors) => {
			// eslint-disable-next-line no-console
			console.debug('[DEBUG] Errores al guardar:', errors);
			
			// Manejar errores específicos de validación
			if (errors.telefono) {
				if (errors.telefono.includes('required') || errors.telefono === 'validation.required') {
					toast.add({
						severity: 'warn',
						summary: 'Campo requerido',
						detail: 'El campo teléfono es requerido.',
						life: 4000
					});
				} else {
					toast.add({
						severity: 'warn',
						summary: 'Error en teléfono',
						detail: errors.telefono,
						life: 4000
					});
				}
			} else if (errors.cargo) {
				toast.add({
					severity: 'warn',
					summary: 'Error en cargo',
					detail: errors.cargo,
					life: 4000
				});
			} else {
				// Error genérico si no se puede identificar el campo específico
				toast.add({
					severity: 'error',
					summary: 'Error de validación',
					detail: 'Por favor revisa los campos y completa la información requerida.',
					life: 4000
				});
			}
		}
	});
};
</script>

<template>
	<div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg shadow-lg p-6" :style="dialogStyle">
			<h2 class="text-2xl font-bold mb-4 text-blue-600">Completa tus datos de Administrador</h2>

			<!-- Mensaje informativo -->
			<div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<p class="text-sm text-blue-700">
							<strong>Información importante:</strong> Estos datos se solicitarán solo una vez.
							Puedes modificar el cargo sugerido si es necesario.
						</p>
						<p class="text-xs text-blue-600 mt-2">
							<FontAwesomeIcon :icon="faLightbulb" class="mr-1 text-yellow-400"/>
                            Posteriormente podrás modificar toda la información en:
							<span class="font-medium">Mi Perfil → Editar Perfil → Información Personal</span>
						</p>
					</div>
				</div>
			</div>

			<form @submit.prevent="submit" class="space-y-5">
				<!-- Cargo -->
				<div class="w-full flex flex-col">
					<div class="flex items-center gap-4">
						<label for="cargo" class="w-24 flex items-center gap-1">Cargo <span class="text-red-500 font-bold">*</span></label>
						<input
							id="cargo"
							v-model="form.cargo"
							type="text"
							class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-blue-500 focus:ring-0 focus:shadow-none rounded-md px-2 py-1"
							maxlength="25"
							@input="validateCargo"
							required
						/>
					</div>
					<div v-if="form.errors.cargo || (submitted && cargoErrors.length > 0)" class="ml-28 mt-1">
						<small v-if="form.errors.cargo" class="text-red-500 block">{{ form.errors.cargo }}</small>
						<small v-for="error in cargoErrors" :key="error" class="text-red-500 block">{{ error }}</small>
					</div>
					<small class="text-gray-500 ml-28 mt-1" v-if="form.cargo && form.cargo.length > 0 && cargoErrors.length === 0">
						Caracteres: {{ form.cargo.length }}/25
					</small>
					<small class="text-orange-500 ml-28 mt-1" v-if="form.cargo && form.cargo.length >= 20 && form.cargo.length <= 25 && cargoErrors.length === 0">
						Caracteres restantes: {{ 25 - form.cargo.length }}
					</small>
				</div>

				<!-- Teléfono -->
				<div class="w-full flex flex-col">
					<div class="flex items-start gap-4">
						<label for="telefono" class="w-24 flex items-center gap-1 mt-2">Teléfono <span class="text-red-500 font-bold">*</span></label>
						<div class="flex-1">
							<VueTelInput
								v-model="form.telefono"
								id="telefono"
								name="telefono"
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
								:disabled="form.processing"
							/>
						</div>
					</div>
					<div v-if="shouldShowServerError || (submitted && telefonoErrors.length > 0)" class="ml-28 mt-1">
						<small v-if="shouldShowServerError" class="text-red-500 block">{{ form.errors.telefono }}</small>
						<small v-for="error in telefonoErrors" :key="error" class="text-red-500 block">{{ error }}</small>
					</div>
					<div v-if="telefonoValidation.mensaje" class="ml-28 mt-1">
						<small :class="[
							telefonoValidation.isValid ? 'text-green-600' : 'text-red-500',
							'block'
						]">
							{{ telefonoValidation.mensaje }}
						</small>
					</div>
				</div>

				<!-- Botones -->
				<div class="flex justify-center w-full mt-6">
					<button
						type="submit"
						:disabled="form.processing"
						class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
					>
						<FontAwesomeIcon v-if="!form.processing" :icon="faCheck" />
						<span v-if="!form.processing">Guardar</span>
						<span v-else>Guardando...</span>
					</button>
				</div>
			</form>
		</div>
	</div>
</template>
