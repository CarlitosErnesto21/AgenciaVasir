
<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';

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

import { useToast } from 'primevue/usetoast';
const emit = defineEmits(['close']);
const toast = useToast();

const form = useForm({
	cargo: props.empleado?.cargo || '',
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
		}
	});
};
</script>

<template>
	<div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
		<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
			<h2 class="text-2xl font-bold mb-4 text-blue-600">Completa tus datos de Administrador</h2>
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
					<div v-if="form.errors.telefono || (submitted && telefonoErrors.length > 0)" class="ml-28 mt-1">
						<small v-if="form.errors.telefono" class="text-red-500 block">{{ form.errors.telefono }}</small>
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
				<div class="flex justify-center gap-4 w-full mt-6">
					<button
						type="submit"
						:disabled="form.processing"
						class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
					>
						<span v-if="!form.processing">Guardar</span>
						<span v-else>Guardando...</span>
					</button>
					<button
						type="button"
						class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
						@click="closeModal"
						:disabled="form.processing"
					>
						Cancelar
					</button>
				</div>
			</form>
		</div>
	</div>
</template>
