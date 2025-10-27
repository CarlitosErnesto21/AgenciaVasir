<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4">
            <h1 class="text-sm sm:text-lg lg:text-2xl font-bold text-white flex items-center">
                <span class="text-sm sm:text-lg lg:text-2xl mr-1 sm:mr-2 lg:mr-3">🏢</span>
                <span class="hidden sm:inline">Información Corporativa</span>
                <span class="sm:hidden">Info Corporativa</span>
            </h1>
            <p class="text-red-100 mt-0.5 sm:mt-1 lg:mt-2 text-xs sm:text-sm lg:text-base hidden sm:block">Configure el contenido que aparece en la página "Sobre Nosotros"</p>
        </div>

        <!-- Content -->
        <div class="p-3 sm:p-4 lg:p-5">
            <div class="space-y-4">
                <!-- Información Básica (Descripción, Misión, Visión) -->
                <div class="bg-red-50 rounded-lg p-3 sm:p-4 border border-red-200">
                    <h3 class="text-sm sm:text-base font-semibold text-gray-800 mb-3 flex items-center">
                        <span class="text-base sm:text-lg mr-2">📝</span>
                        Información Básica
                    </h3>

                    <!-- Descripción de la Empresa - Ancho completo -->
                    <div class="mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                            Descripción de la Empresa
                        </label>
                        <textarea
                            v-model="currentFormData.description"
                            rows="4"
                            class="w-full px-3 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none shadow-sm"
                            placeholder="Describe brevemente qué es VASIR, su propósito y lo que ofrece a los clientes..."
                        ></textarea>
                    </div>

                    <!-- Misión y Visión - Grid 2 columnas -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <!-- Misión -->
                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-2">
                                <span class="text-sm mr-1">🎯</span>
                                Misión Corporativa
                            </label>
                            <textarea
                                v-model="currentFormData.mission"
                                rows="4"
                                class="w-full px-3 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none shadow-sm"
                                placeholder="Nuestra misión es..."
                            ></textarea>
                        </div>

                        <!-- Visión -->
                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-2">
                                <span class="text-sm mr-1">🌟</span>
                                Visión Corporativa
                            </label>
                            <textarea
                                v-model="currentFormData.vision"
                                rows="4"
                                class="w-full px-3 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none shadow-sm"
                                placeholder="Nuestra visión es..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Valores Corporativos -->
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm sm:text-base font-semibold text-gray-800 flex items-center">
                            <span class="text-base sm:text-lg mr-2">⭐</span>
                            Valores Corporativos
                        </h3>
                        <button
                            @click="showAddValueForm = !showAddValueForm"
                            type="button"
                            class="px-3 py-1.5 text-xs sm:text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center"
                        >
                            <span class="mr-1">+</span>
                            <span class="hidden sm:inline">Agregar</span>
                            <span class="sm:hidden">+</span>
                        </button>
                    </div>

                    <!-- Formulario compacto para agregar nuevo valor -->
                    <div v-if="showAddValueForm" class="bg-white rounded-lg p-3 mb-3 border border-red-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <input
                                    v-model="newValue.titulo"
                                    type="text"
                                    maxlength="100"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                    placeholder="Título del valor (ej: Integridad)"
                                />
                            </div>
                            <div>
                                <textarea
                                    v-model="newValue.descripcion"
                                    rows="1"
                                    maxlength="500"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                    placeholder="Descripción del valor..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button
                                @click="addValue"
                                :disabled="!newValue.titulo || !newValue.descripcion"
                                type="button"
                                class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 transition-colors duration-200 flex items-center"
                            >
                                Agregar
                            </button>
                            <button
                                @click="cancelAddValue"
                                type="button"
                                class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                            >
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
                                'bg-white rounded-lg p-3 mb-2 border border-gray-200 hover:border-purple-300 transition-colors duration-200',
                                {
                                    'border-green-300 bg-green-50': value.isNew,
                                    'border-blue-300 bg-blue-50': value.isModified
                                }
                            ]"
                        >
                            <!-- Modo visualización compacto -->
                            <div v-if="editingValueId !== value.id" class="flex items-start justify-between">
                                <div class="flex-1 mr-3">
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-sm font-semibold text-gray-800">{{ value.titulo }}</h5>
                                        <span v-if="value.isNew" class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Nuevo</span>
                                        <span v-if="value.isModified" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded">Modificado</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ value.descripcion }}</p>
                                </div>
                                <div class="flex gap-1 flex-shrink-0">
                                    <button
                                        @click="startEditValue(value)"
                                        type="button"
                                        class="px-3 py-2 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors duration-200 flex items-center hover:shadow"
                                        title="Editar valor"
                                    >
                                        <FontAwesomeIcon :icon="faPencil" class="mr-1" />
                                        Editar
                                    </button>
                                    <button
                                        @click="deleteValue(value.id)"
                                        type="button"
                                        class="px-3 py-2 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors duration-200 flex items-center hover:shadow"
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
                                        class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 transition-colors duration-200 flex items-center"
                                    >
                                        Guardar
                                    </button>
                                    <button
                                        @click="cancelEditValue"
                                        type="button"
                                        class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje cuando no hay valores -->
                        <div v-if="currentCompanyValues.filter(v => !v.isDeleted).length === 0" class="text-center py-6">
                            <span class="text-3xl mb-2 block">💎</span>
                            <p class="text-sm text-gray-500">No hay valores corporativos</p>
                            <p class="text-xs text-gray-400 mt-1">Agrega el primer valor</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 mt-4 pt-3 border-t border-gray-200">
                <button
                    @click="handleReset"
                    :disabled="!canReset || isSaving"
                    type="button"
                    class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors duration-200"
                >
                    Restablecer
                </button>
                <button
                    @click="handleSave"
                    :disabled="!canSave || isSaving"
                    type="button"
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors duration-200 flex items-center justify-center"
                >
                    <FontAwesomeIcon
                        v-if="isSaving"
                        :icon="faSpinner"
                        class="animate-spin mr-2"
                    />
                    {{ isSaving ? 'Guardando...' : 'Guardar Configuración' }}
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
import { faSpinner, faEdit, faTrash, faPencil } from '@fortawesome/free-solid-svg-icons'
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
