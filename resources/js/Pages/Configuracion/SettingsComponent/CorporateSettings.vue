<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-red-600 via-red-700 to-red-800 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-white/5 to-blue-500/10"></div>
            <div class="relative">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white flex items-center mb-2">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-2 rounded-xl mr-3 shadow-lg">
                        <FontAwesomeIcon :icon="faBuilding" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                    </div>
                    <span class="bg-gradient-to-r from-white to-red-100 bg-clip-text text-transparent">
                        <span class="hidden sm:inline">Información Corporativa</span>
                        <span class="sm:hidden">Info Corporativa</span>
                    </span>
                </h1>
                <p class="text-red-100 text-sm sm:text-base ml-14 hidden sm:block font-medium">Gestión integral de identidad y valores empresariales</p>
            </div>
        </div>

        <!-- Content -->
        <div class="p-3 sm:p-4 lg:p-5">
            <div class="space-y-4">
                <!-- Información Básica (Descripción, Misión, Visión) -->
                <div class="bg-gradient-to-br from-white to-red-50/50 rounded-2xl p-6 border border-red-100/50 shadow-lg hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center mb-6">
                        <div class="bg-gradient-to-br from-red-500 to-red-600 p-3 rounded-xl shadow-lg mr-4">
                            <FontAwesomeIcon :icon="faFileText" class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Identidad Empresarial</h3>
                            <p class="text-sm text-red-600 font-medium">Definición de propósito y valores</p>
                        </div>
                    </div>

                    <!-- Descripción de la Empresa - Ancho completo -->
                    <div class="mb-6">
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="bg-gradient-to-br from-red-500 to-red-600 p-1.5 rounded-lg mr-2">
                                <FontAwesomeIcon :icon="faFileText" class="w-3 h-3 text-white" />
                            </div>
                            Descripción de la Empresa
                        </label>
                        <textarea
                            v-model="currentFormData.description"
                            rows="4"
                            class="w-full px-4 py-4 text-sm border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-red-500/20 focus:border-red-500 resize-none shadow-md hover:shadow-lg transition-all duration-300 bg-gradient-to-br from-white to-red-50/10"
                            placeholder="Describe la identidad, propósito y servicios que ofrece la empresa a sus clientes..."
                        ></textarea>
                    </div>

                    <!-- Misión y Visión - Grid 2 columnas -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Misión -->
                        <div class="bg-gradient-to-br from-blue-50/50 to-white rounded-xl p-4 border border-blue-100/50">
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                                <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-1.5 rounded-lg mr-2">
                                    <FontAwesomeIcon :icon="faBullseye" class="w-3 h-3 text-white" />
                                </div>
                                Misión Corporativa
                            </label>
                            <textarea
                                v-model="currentFormData.mission"
                                rows="4"
                                class="w-full px-4 py-3 text-sm border-2 border-blue-200/50 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 resize-none shadow-sm hover:shadow-md transition-all duration-300 bg-white/80"
                                placeholder="Defina el propósito fundamental y los objetivos principales de la empresa..."
                            ></textarea>
                        </div>

                        <!-- Visión -->
                        <div class="bg-gradient-to-br from-red-50/50 to-white rounded-xl p-4 border border-red-100/50">
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                                <div class="bg-gradient-to-br from-red-500 to-red-600 p-1.5 rounded-lg mr-2">
                                    <FontAwesomeIcon :icon="faStar" class="w-3 h-3 text-white" />
                                </div>
                                Visión Corporativa
                            </label>
                            <textarea
                                v-model="currentFormData.vision"
                                rows="4"
                                class="w-full px-4 py-3 text-sm border-2 border-red-200/50 rounded-xl focus:ring-4 focus:ring-red-500/20 focus:border-red-500 resize-none shadow-sm hover:shadow-md transition-all duration-300 bg-white/80"
                                placeholder="Describa las aspiraciones a largo plazo y el futuro deseado de la empresa..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Valores Corporativos -->
                <div class="bg-gradient-to-br from-gray-50 to-red-50/30 rounded-2xl p-6 border border-red-100/50 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-3 rounded-xl shadow-lg mr-4">
                                <FontAwesomeIcon :icon="faGem" class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Valores Corporativos</h3>
                                <p class="text-sm text-blue-600 font-medium">Principios y ética empresarial</p>
                            </div>
                        </div>
                        <button
                            @click="showAddValueForm = !showAddValueForm"
                            type="button"
                            class="group px-4 py-2.5 text-sm bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-700 hover:to-red-700 transition-all duration-300 flex items-center shadow-lg hover:shadow-xl transform hover:scale-105 font-semibold"
                        >
                            <FontAwesomeIcon :icon="faPlus" class="w-3 h-3 mr-2 group-hover:scale-110 transition-transform" />
                            <span class="hidden sm:inline">Agregar Valor</span>
                            <span class="sm:hidden">Agregar</span>
                        </button>
                    </div>

                    <!-- Formulario elegante para agregar nuevo valor -->
                    <div v-if="showAddValueForm" class="bg-gradient-to-br from-white to-red-50/30 rounded-xl p-5 mb-4 border-2 border-red-200/50 shadow-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wider">Título del Valor</label>
                                <input
                                    v-model="newValue.titulo"
                                    type="text"
                                    maxlength="100"
                                    class="w-full px-4 py-3 text-sm border-2 border-red-200/50 rounded-xl focus:ring-4 focus:ring-red-500/20 focus:border-red-500 transition-all duration-300 bg-white/80 shadow-sm hover:shadow-md"
                                    placeholder="Ej: Integridad, Excelencia, Compromiso"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wider">Descripción</label>
                                <textarea
                                    v-model="newValue.descripcion"
                                    rows="2"
                                    maxlength="500"
                                    class="w-full px-4 py-3 text-sm border-2 border-blue-200/50 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 resize-none transition-all duration-300 bg-white/80 shadow-sm hover:shadow-md"
                                    placeholder="Explique qué significa este valor para la empresa..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button
                                @click="addValue"
                                :disabled="!newValue.titulo || !newValue.descripcion"
                                type="button"
                                class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200 flex items-center shadow-sm hover:shadow-md"
                            >
                                <FontAwesomeIcon :icon="faPlus" class="w-3 h-3 mr-2" />
                                Agregar
                            </button>
                            <button
                                @click="cancelAddValue"
                                type="button"
                                class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 flex items-center"
                            >
                                <FontAwesomeIcon :icon="faTimes" class="w-3 h-3 mr-2" />
                                Cancelar
                            </button>
                        </div>
                    </div>

                    <!-- Lista compacta de valores existentes -->
                    <div class="max-h-48 overflow-y-auto">
                        <div
                            v-for="(value, index) in currentCompanyValues.filter(v => !v.isDeleted)"
                            :key="value.id"
                            :class="[
                                `value-container-${value.id}`,
                                'bg-gradient-to-br from-white to-gray-50/30 rounded-xl p-5 mb-4 border-2 border-gray-200/50 hover:border-purple-300/70 hover:shadow-lg transition-all duration-300 shadow-md group',
                                {
                                    'border-green-400/70 bg-gradient-to-br from-green-50 to-white shadow-green-100': value.isNew,
                                    'border-blue-400/70 bg-gradient-to-br from-blue-50 to-white shadow-blue-100': value.isModified
                                }
                            ]"
                        >
                            <!-- Modo visualización compacto -->
                            <div v-if="editingValueId !== value.id" class="flex items-start justify-between">
                                <div class="flex-1 mr-3">
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-sm font-semibold text-gray-800">{{ value.titulo }}</h5>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ value.descripcion }}</p>
                                </div>
                                <div class="flex gap-1 flex-shrink-0">
                                    <button
                                        @click="startEditValue(value)"
                                        type="button"
                                        class="px-3 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center hover:shadow"
                                        title="Editar valor"
                                    >
                                        <FontAwesomeIcon :icon="faPencil" class="mr-1" />
                                        Editar
                                    </button>
                                    <button
                                        @click="deleteValue(value.id)"
                                        type="button"
                                        class="px-3 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center hover:shadow"
                                        title="Eliminar valor"
                                    >
                                        <FontAwesomeIcon :icon="faTrash" class="mr-1"/>
                                        Eliminar
                                    </button>
                                </div>
                            </div>

                            <!-- Modo edición compacto -->
                            <div v-else class="space-y-2">
                                <input
                                    v-model="editingValue.titulo"
                                    type="text"
                                    maxlength="100"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                    placeholder="Título"
                                />
                                <textarea
                                    v-model="editingValue.descripcion"
                                    rows="2"
                                    maxlength="500"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                    placeholder="Descripción"
                                ></textarea>
                                <div class="flex gap-2" :class="`edit-buttons-${value.id}`">
                                    <button
                                        @click="saveEditValue"
                                        :disabled="!editingValue.titulo || !editingValue.descripcion"
                                        type="button"
                                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200 flex items-center shadow-sm hover:shadow-md"
                                    >
                                        <FontAwesomeIcon :icon="faSave" class="w-3 h-3 mr-2" />
                                        Guardar
                                    </button>
                                    <button
                                        @click="cancelEditValue"
                                        type="button"
                                        class="px-4 py-2 text-sm border border-blue-500 text-white rounded-lg bg-blue-500 hover:bg-blue-600 transition-all duration-200 flex items-center"
                                    >
                                        <FontAwesomeIcon :icon="faTimes" class="w-3 h-3 mr-2" />
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje cuando no hay valores -->
                        <div v-if="currentCompanyValues.filter(v => !v.isDeleted).length === 0" class="text-center py-12">
                            <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <FontAwesomeIcon :icon="faGem" class="w-10 h-10 text-purple-500" />
                            </div>
                            <h4 class="text-lg font-bold text-gray-700 mb-2">No hay valores corporativos definidos</h4>
                            <p class="text-sm text-gray-500 mb-4 max-w-md mx-auto">Los valores corporativos definen la cultura y principios éticos de la empresa</p>
                            <button
                                @click="showAddValueForm = true"
                                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:scale-105"
                            >
                                <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                                Agregar Primer Valor
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex flex-col sm:flex-row sm:justify-end gap-4 mt-8 pt-6 border-t border-gradient-to-r from-transparent via-gray-200 to-transparent">
                <button
                    @click="handleReset"
                    :disabled="!canReset || isSaving"
                    type="button"
                    class="px-6 py-3 text-sm border-2 border-blue-500 text-white rounded-xl bg-blue-500 hover:bg-blue-600 hover:border-blue-600 hover:shadow-lg disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-200 disabled:cursor-not-allowed transition-all duration-300 font-semibold transform hover:scale-105"
                >
                    Restablecer
                </button>
                <button
                    @click="handleSave"
                    :disabled="!canSave || isSaving"
                    type="button"
                    class="px-8 py-3 text-sm bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed transition-all duration-300 flex items-center justify-center font-semibold shadow-lg hover:shadow-xl transform hover:scale-105"
                >
                    <FontAwesomeIcon
                        v-if="isSaving"
                        :icon="faSpinner"
                        class="animate-spin mr-3 w-4 h-4"
                    />
                    {{ isSaving ? 'Guardando configuración...' : 'Guardar Configuración' }}
                </button>
            </div>
        </div>

        <!-- Modal de confirmación de reset -->
        <UnsavedChangesModal
            v-model:visible="showResetModal"
            title="¿Restablecer todos los cambios?"
            message="Esta acción restablecerá todos los campos a sus valores originales."
            subtitle="Los cambios no guardados se perderán."
            continueText="Cancelar"
            exitText="Restablecer"
            @continue-editing="cancelReset"
            @exit-without-saving="confirmReset"
        />

        <!-- Modal de confirmación de eliminación -->
        <UnsavedChangesModal
            v-model:visible="showDeleteModal"
            title="¿Eliminar valor corporativo?"
            message="¿Estás seguro de que deseas eliminar este valor corporativo?"
            subtitle="Esta acción no se puede deshacer una vez que guardes los cambios."
            continueText="Cancelar"
            exitText="Eliminar"
            @continue-editing="cancelDelete"
            @exit-without-saving="confirmDelete"
        />

        <!-- Toast para notificaciones -->
        <Toast />
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner, faEdit, faTrash, faPencil, faBuilding, faFileText, faBullseye, faStar, faGem, faPlus, faSave, faTimes } from '@fortawesome/free-solid-svg-icons'
import { ref, reactive, watch, nextTick, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import UnsavedChangesModal from './components/UnsavedChangesModal.vue'

const props = defineProps({
    settings: {
        type: Object,
        required: true
    },
    companyValues: {
        type: Array,
        default: () => []
    },
    isSaving: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['save', 'reset', 'unsaved-changes', 'settings-updated'])

// Inicializar toast para notificaciones
const toast = useToast()

// Variables reactivas para manejo de datos (sin useUnsavedChanges)
const hasUnsavedChanges = ref(false)
const currentFormData = ref({})
const originalFormData = ref({})
const isInitialized = ref(false)

// Inicializar datos
const initializeData = (data) => {
    if (!data) return

    const formattedData = {
        description: data.description || '',
        mission: data.mission || '',
        vision: data.vision || '',
        ...data
    }

    originalFormData.value = JSON.parse(JSON.stringify(formattedData))
    currentFormData.value = JSON.parse(JSON.stringify(formattedData))
    hasUnsavedChanges.value = false
    isInitialized.value = true
}

// Función para detectar cambios
const checkForChanges = () => {
    const hasChanges = JSON.stringify(currentFormData.value) !== JSON.stringify(originalFormData.value)
    hasUnsavedChanges.value = hasChanges
}

// Computed properties
const canSave = computed(() => hasUnsavedChanges.value)
const canReset = computed(() => hasUnsavedChanges.value)

// Estados para gestión de valores
const showAddValueForm = ref(false)
const editingValueId = ref(null)
const showResetModal = ref(false)
const showDeleteModal = ref(false)
const valueToDelete = ref(null)

// Formularios reactivos
const newValue = reactive({
    titulo: '',
    descripcion: ''
})

const editingValue = reactive({
    titulo: '',
    descripcion: ''
})

// Estado de valores corporativos para detectar cambios
const originalCompanyValues = ref([])
const currentCompanyValues = ref([])

// Inicializar valores corporativos
const initializeCompanyValues = () => {
    originalCompanyValues.value = JSON.parse(JSON.stringify(props.companyValues))
    currentCompanyValues.value = JSON.parse(JSON.stringify(props.companyValues))
}

// Detectar cambios en valores corporativos
const detectValuesChanges = () => {
    const originalStr = JSON.stringify(originalCompanyValues.value)
    const currentStr = JSON.stringify(currentCompanyValues.value)
    return originalStr !== currentStr
}

// Función personalizada para detectar cambios
const customCheckForChanges = () => {
    // Detectar cambios en campos básicos comparando con los datos originales
    const basicChanges = JSON.stringify(currentFormData.value) !== JSON.stringify(originalFormData.value)

    // Detectar cambios en valores corporativos
    const valuesChanges = detectValuesChanges()

    // Detectar si hay un formulario de agregar valor abierto con datos
    const pendingNewValue = showAddValueForm.value && (newValue.titulo || newValue.descripcion)

    // Detectar si hay una edición en curso
    const pendingEdit = editingValueId.value !== null

    hasUnsavedChanges.value = basicChanges || valuesChanges || pendingNewValue || pendingEdit
}

// Watchers para detectar todos los cambios
watch([currentFormData, currentCompanyValues, showAddValueForm, newValue, editingValueId], () => {
    // Solo detectar cambios después de la inicialización
    if (isInitialized.value) {
        nextTick(() => {
            customCheckForChanges()
        })
    }
}, { deep: true, flush: 'post' })

// Watcher para actualizar valores cuando cambien las props
watch(() => props.companyValues, (newValues) => {
    if (!hasUnsavedChanges.value) {
        initializeCompanyValues()
    }
}, { immediate: true, deep: true })

// Watcher para actualizar datos cuando cambien las props de settings
watch(() => props.settings, (newSettings) => {
    if (!hasUnsavedChanges.value) {
        isInitialized.value = false // Reset flag before re-initialization
        initializeData(newSettings)
    }
}, { immediate: true, deep: true })

// Watcher para emitir cambios al componente padre
watch(hasUnsavedChanges, (newValue) => {
    emit('unsaved-changes', newValue)
})

// Funciones para manejar los botones principales
const handleSave = async () => {
    try {
        // Marcar que estamos guardando para evitar que aparezca el modal
        const originalHasUnsavedChanges = hasUnsavedChanges.value
        hasUnsavedChanges.value = false

        // Preparar todos los datos para enviar
        const dataToSave = {
            // Campos básicos
            description: currentFormData.value.description,
            mission: currentFormData.value.mission,
            vision: currentFormData.value.vision,

            // Valores corporativos con sus operaciones
            companyValues: {
                // Valores nuevos a crear
                new: currentCompanyValues.value.filter(v => v.isNew && !v.isDeleted).map(v => ({
                    titulo: v.titulo,
                    descripcion: v.descripcion
                })),

                // Valores existentes a actualizar
                updated: currentCompanyValues.value.filter(v => v.isModified && !v.isDeleted && !v.isNew).map(v => ({
                    id: v.id,
                    titulo: v.titulo,
                    descripcion: v.descripcion
                })),

                // IDs de valores a eliminar
                deleted: originalCompanyValues.value.filter(original =>
                    currentCompanyValues.value.find(current =>
                        current.id === original.id && current.isDeleted
                    )
                ).map(v => v.id)
            }
        }

        // Enviar todos los datos en una sola petición
        router.post(route('settings.update'), dataToSave, {
            onSuccess: () => {
                // Actualizar datos originales con los datos guardados (tanto campos básicos como valores)
                originalFormData.value = JSON.parse(JSON.stringify(currentFormData.value))

                // Limpiar marcas de modificación en valores PRIMERO
                const cleanedValues = currentCompanyValues.value
                    .filter(v => !v.isDeleted)
                    .map(v => ({
                        id: v.id,
                        titulo: v.titulo,
                        descripcion: v.descripcion
                        // Remover isNew, isModified, isDeleted
                    }))

                // Actualizar AMBOS arrays con la misma estructura limpia
                currentCompanyValues.value = cleanedValues
                originalCompanyValues.value = JSON.parse(JSON.stringify(cleanedValues))

                cancelAddValue()
                cancelEditValue()

                // Emitir evento para que el padre recargue los datos
                emit('settings-updated')

                // Forzar re-verificación de cambios después de actualizar datos
                nextTick(() => {
                    customCheckForChanges()
                })
            },
            onError: (errors) => {
                // Si hay error, restaurar el estado original
                hasUnsavedChanges.value = originalHasUnsavedChanges
                console.error('Error al guardar configuración corporativa:', errors)

                // Función para traducir mensajes de error comunes
                const translateError = (error) => {
                    const translations = {
                        'The mission field is required.': 'El campo de misión es obligatorio.',
                        'The vision field is required.': 'El campo de visión es obligatorio.',
                        'The description field is required.': 'El campo de descripción es obligatorio.',
                        'The title field is required.': 'El campo de título es obligatorio.',
                        'required': 'es obligatorio',
                        'field is required': 'es obligatorio'
                    }

                    // Buscar traducción exacta primero
                    if (translations[error]) {
                        return translations[error]
                    }

                    // Buscar patrones comunes
                    if (error.includes('field is required')) {
                        return error.replace('field is required', 'es obligatorio').replace('The ', 'El campo ')
                    }

                    return error // Si no hay traducción, devolver el original
                }

                // Mostrar notificación de error con detalles específicos
                let errorMessage = 'Error al guardar la configuración corporativa.'

                if (errors && typeof errors === 'object') {
                    // Construir mensaje de error específico
                    const errorList = []

                    if (errors.mission) {
                        const originalError = Array.isArray(errors.mission) ? errors.mission[0] : errors.mission
                        errorList.push('• Misión: ' + translateError(originalError))
                    }
                    if (errors.vision) {
                        const originalError = Array.isArray(errors.vision) ? errors.vision[0] : errors.vision
                        errorList.push('• Visión: ' + translateError(originalError))
                    }
                    if (errors.description) {
                        const originalError = Array.isArray(errors.description) ? errors.description[0] : errors.description
                        errorList.push('• Descripción: ' + translateError(originalError))
                    }

                    // Agregar errores de valores corporativos
                    Object.keys(errors).forEach(key => {
                        if (key.startsWith('company_values.')) {
                            const valueIndex = key.split('.')[1]
                            const field = key.split('.')[2]
                            const originalError = Array.isArray(errors[key]) ? errors[key][0] : errors[key]

                            if (field === 'title') {
                                errorList.push(`• Valor corporativo ${parseInt(valueIndex) + 1} - Título: ${translateError(originalError)}`)
                            } else if (field === 'description') {
                                errorList.push(`• Valor corporativo ${parseInt(valueIndex) + 1} - Descripción: ${translateError(originalError)}`)
                            }
                        }
                    })

                    if (errorList.length > 0) {
                        errorMessage = 'Por favor corrija los siguientes errores:\n\n' + errorList.join('\n')
                    }
                }

                toast.add({
                    severity: 'error',
                    summary: 'Error de Validación',
                    detail: errorMessage,
                    life: 6000
                })
            }
        })

    } catch (error) {
        console.error('Error al guardar configuración corporativa:', error)

        toast.add({
            severity: 'error',
            summary: 'Error Interno',
            detail: 'Ocurrió un error inesperado al guardar la configuración. Por favor, inténtalo de nuevo.',
            life: 5000
        })
    }
}

const handleReset = () => {
    if (hasUnsavedChanges.value) {
        // Usar nuestro modal personalizado en lugar de confirm nativo
        showResetModal.value = true
    } else {
        // Si no hay cambios, resetear directamente
        performReset()
    }
}

// Función separada para realizar el reset
const performReset = () => {
    isInitialized.value = false
    currentFormData.value = JSON.parse(JSON.stringify(originalFormData.value))
    hasUnsavedChanges.value = false
    initializeCompanyValues()
    cancelAddValue()
    cancelEditValue()
    // No emitir evento reset para evitar conflicto con el modal personalizado
    isInitialized.value = true
}

// Funciones para el modal de reset
const confirmReset = () => {
    showResetModal.value = false
    performReset()
}

const cancelReset = () => {
    showResetModal.value = false
}

// Agregar nuevo valor (solo local, no va a BD hasta hacer "Guardar")
const addValue = () => {
    if (!newValue.titulo || !newValue.descripcion) return

    // Agregar al estado local con ID temporal
    const newValueItem = {
        id: `temp_${Date.now()}`, // ID temporal para identificarlo
        titulo: newValue.titulo,
        descripcion: newValue.descripcion,
        isNew: true // Marca para saber que es nuevo
    }

    currentCompanyValues.value.push(newValueItem)

    // Resetear formulario
    newValue.titulo = ''
    newValue.descripcion = ''
    showAddValueForm.value = false

    // Detectar cambios
    customCheckForChanges()
}

// Cancelar agregar valor
const cancelAddValue = () => {
    newValue.titulo = ''
    newValue.descripcion = ''
    showAddValueForm.value = false
}

// Iniciar edición de valor
const startEditValue = (value) => {
    editingValueId.value = value.id
    editingValue.titulo = value.titulo
    editingValue.descripcion = value.descripcion

    // Hacer scroll automático al contenedor completo del valor que se está editando
    nextTick(() => {
        setTimeout(() => {
            const valueContainer = document.querySelector(`.value-container-${value.id}`)
            if (valueContainer) {
                valueContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                    inline: 'nearest'
                })
            }
        }, 100)
    })
}

// Guardar edición de valor (solo local, no va a BD hasta hacer "Guardar")
const saveEditValue = () => {
    if (!editingValue.titulo || !editingValue.descripcion) return

    // Actualizar el estado local
    const index = currentCompanyValues.value.findIndex(v => v.id === editingValueId.value)
    if (index !== -1) {
        currentCompanyValues.value[index].titulo = editingValue.titulo
        currentCompanyValues.value[index].descripcion = editingValue.descripcion
        // Marcar como modificado si no es nuevo
        if (!currentCompanyValues.value[index].isNew) {
            currentCompanyValues.value[index].isModified = true
        }
    }

    editingValueId.value = null
    editingValue.titulo = ''
    editingValue.descripcion = ''

    // Detectar cambios
    customCheckForChanges()
}

// Cancelar edición de valor
const cancelEditValue = () => {
    editingValueId.value = null
    editingValue.titulo = ''
    editingValue.descripcion = ''
}

// Eliminar valor (solo local, no va a BD hasta hacer "Guardar")
const deleteValue = (id) => {
    valueToDelete.value = id
    showDeleteModal.value = true
}

// Confirmar eliminación
const confirmDelete = () => {
    if (!valueToDelete.value) return

    const valueIndex = currentCompanyValues.value.findIndex(v => v.id === valueToDelete.value)
    if (valueIndex !== -1) {
        const value = currentCompanyValues.value[valueIndex]

        if (value.isNew) {
            // Si es nuevo, simplemente removerlo de la lista
            currentCompanyValues.value.splice(valueIndex, 1)
        } else {
            // Si existe en BD, marcarlo para eliminación
            currentCompanyValues.value[valueIndex].isDeleted = true
        }
    }

    // Detectar cambios
    customCheckForChanges()

    showDeleteModal.value = false
    valueToDelete.value = null
}

// Cancelar eliminación
const cancelDelete = () => {
    showDeleteModal.value = false
    valueToDelete.value = null
}

// Watcher para re-inicializar cuando cambien los props
watch([() => props.settings, () => props.companyValues],
    ([newSettings, newCompanyValues]) => {
        if (newSettings) {
            initializeData(newSettings)
        }
        if (newCompanyValues) {
            initializeCompanyValues()
        }
    },
    { deep: true }
)

// Inicializar al montar
initializeCompanyValues()
</script>
