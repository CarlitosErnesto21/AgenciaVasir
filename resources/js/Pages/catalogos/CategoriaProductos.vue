<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from 'primevue/usetoast'
import { FilterMatchMode } from "@primevue/core/api"
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faCheck, faExclamationTriangle, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark } from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'

const toast = useToast()

// Estados reactivos
const categorias = ref([])
const categoria = ref({
    id: null,
    nombre: ""
})

// Estados de carga
const isLoading = ref(false)
const isDeleting = ref(false)
const isNavigatingToProductos = ref(false)

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
    if (windowWidth.value < 640) {
        return { width: '95vw', maxWidth: '380px' }
    } else if (windowWidth.value < 768) {
        return { width: '400px' }
    } else {
        return { width: '450px' }
    }
})

// Modal states
const dialog = ref(false)
const deleteDialog = ref(false)
const unsavedChangesDialog = ref(false)
const submitted = ref(false)
const hasUnsavedChanges = ref(false)
const originalCategoriaData = ref(null)

// Filtros
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

// Paginación
const rowsPerPage = ref(10)
const rowsPerPageOptions = ref([5, 10, 20, 50])
const btnTitle = ref("Guardar")

// Categorías filtradas
const categoriasFiltradas = computed(() => {
    let filtered = categorias.value
    if (filters.value.global.value) {
        const searchTerm = filters.value.global.value.toLowerCase()
        filtered = filtered.filter(categoria =>
            categoria.nombre.toLowerCase().includes(searchTerm)
        )
    }
    return filtered
})



// Watcher para detectar cambios en el modal
watch([categoria], () => {
    if (originalCategoriaData.value && dialog.value) {
        nextTick(() => {
            const current = { ...categoria.value }
            const hasChanges = JSON.stringify(current) !== JSON.stringify(originalCategoriaData.value)
            const isCreatingNew = !originalCategoriaData.value.id
            const hasAnyData = categoria.value.nombre
            hasUnsavedChanges.value = hasChanges || (isCreatingNew && hasAnyData)
        })
    }
}, { deep: true, flush: 'post' })

// Función para resetear formulario
function resetForm() {
    categoria.value = { id: null, nombre: "" }
    submitted.value = false
}

// Funciones para manejo de ventana
const updateWindowWidth = () => {
    if (typeof window !== 'undefined') {
        windowWidth.value = window.innerWidth
    }
}

// Función para manejar navegación a productos
const handleProductosClick = () => {
    isNavigatingToProductos.value = true
}

// Cargar datos
onMounted(() => {
    cargarCategoriasWithToasts()
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateWindowWidth)
    }
})

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateWindowWidth)
    }
})

const cargarCategorias = async () => {
    try {
        const response = await axios.get(`/api/categorias-productos`)
        categorias.value = (response.data.data || response.data || []).map(cat => ({
            ...cat,
            categoria_id: cat.id
        })).sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `No se pudieron cargar las categorías de productos.`,
            life: 4000
        })
    }
}

const cargarCategoriasWithToasts = async () => {
    // Mostrar toast de carga
    toast.add({
        severity: "info",
        summary: "Cargando categorías...",
        life: 2000
    });

    try {
        const response = await axios.get(`/api/categorias-productos`)
        categorias.value = (response.data.data || response.data || []).map(cat => ({
            ...cat,
            categoria_id: cat.id
        })).sort((a, b) => new Date(b.created_at) - new Date(a.created_at))

        // Mostrar toast de éxito
        toast.add({
            severity: "success",
            summary: "Categorías cargadas",
            life: 2000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `No se pudieron cargar las categorías de productos.`,
            life: 4000
        })
    }
}

// CRUD Operations
const openNew = () => {
    resetForm()
    btnTitle.value = "Guardar"
    submitted.value = false
    dialog.value = true
    nextTick(() => {
        originalCategoriaData.value = { ...categoria.value }
        hasUnsavedChanges.value = false
    })
}

const editCategoria = (c) => {
    resetForm()
    submitted.value = false
    categoria.value = { ...c }
    btnTitle.value = "Actualizar"
    dialog.value = true
    nextTick(() => {
        originalCategoriaData.value = { ...categoria.value }
        hasUnsavedChanges.value = false
    })
}

const saveOrUpdate = async () => {
    submitted.value = true
    isLoading.value = true

    if (!categoria.value.nombre || categoria.value.nombre.length < 3 || categoria.value.nombre.length > 50) {
        toast.add({
            severity: "warn",
            summary: "Campos requeridos",
            detail: "El nombre debe tener entre 3 y 50 caracteres.",
            life: 4000
        })
        isLoading.value = false
        return
    }



    try {
        if (!categoria.value.id) {
            await axios.post(`/api/categorias-productos`, { nombre: categoria.value.nombre })
            toast.add({ severity: "success", summary: "¡Éxito!", detail: "La categoría ha sido creada correctamente.", life: 5000 })
        } else {
            await axios.put(`/api/categorias-productos/${categoria.value.id}`, {
                nombre: categoria.value.nombre
            })
            toast.add({ severity: "success", summary: "¡Éxito!", detail: "La categoría ha sido actualizada correctamente.", life: 5000 })
        }

        await cargarCategorias()
        dialog.value = false
        hasUnsavedChanges.value = false
        originalCategoriaData.value = null
        resetForm()
    } catch (error) {
        console.error('Error al procesar categoría:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al procesar la categoría',
            life: 3000
        });
    } finally {
        isLoading.value = false
    }
}

const confirmDeleteCategoria = (c) => {
    categoria.value = { ...c }
    deleteDialog.value = true
}

const deleteCategoria = async () => {
    isDeleting.value = true
    try {
        await axios.delete(`/api/categorias-productos/${categoria.value.id}`)
        await cargarCategorias()
        deleteDialog.value = false
        toast.add({ severity: "success", summary: "¡Eliminada!", detail: "La categoría ha sido eliminada correctamente.", life: 5000 })
    } catch (err) {
        deleteDialog.value = false
        const errorMsg = err.response?.data?.error || err.message || "Error desconocido"
        toast.add({ severity: "error", summary: "Error", detail: `No se pudo eliminar la categoría: ${errorMsg}`, life: 6000 })
    } finally {
        isDeleting.value = false
    }
}

// Funciones para cerrar modales
const hideDialog = () => {
    if (hasUnsavedChanges.value) {
        unsavedChangesDialog.value = true
    } else {
        closeDialogWithoutSaving()
    }
}

const closeDialogWithoutSaving = () => {
    dialog.value = false
    unsavedChangesDialog.value = false
    hasUnsavedChanges.value = false
    originalCategoriaData.value = null
    resetForm()
}

const continueEditing = () => {
    unsavedChangesDialog.value = false
}

const validateNombre = () => {
    if (categoria.value.nombre && categoria.value.nombre.length > 50) {
        categoria.value.nombre = categoria.value.nombre.substring(0, 50)
    }
}

// Función para manejar input en tiempo real con toast informativo
const onNombreInput = (event) => {
    const value = event.target.value;
    const invalidChars = value.match(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g);

    if (invalidChars && invalidChars.length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Caracteres no permitidos',
            detail: 'Solo se permiten letras, espacios y acentos. Se han removido los caracteres no válidos.',
            life: 3000
        });
    }

    // Limpiar caracteres no válidos y convertir a mayúsculas
    const cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '').toUpperCase();
    event.target.value = cleanValue;
    categoria.value.nombre = cleanValue;

    validateNombre();
};

// Función para manejar paste
const onNombrePaste = (event) => {
    event.preventDefault();
    const paste = (event.clipboardData || window.clipboardData).getData('text');

    // Limpiar el texto pegado y convertir a mayúsculas
    const cleanPaste = paste
        .replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '') // Solo letras, espacios y acentos
        .replace(/\s+/g, ' ') // Múltiples espacios a uno solo
        .trim() // Eliminar espacios al inicio y final
        .toUpperCase(); // Convertir a mayúsculas

    if (cleanPaste !== paste.toUpperCase()) {
        toast.add({
            severity: 'info',
            summary: 'Texto limpiado',
            detail: 'Se han removido números y caracteres especiales del texto pegado y convertido a mayúsculas.',
            life: 3000
        });
    }

    // Limitar a 50 caracteres
    const limitedPaste = cleanPaste.length > 50 ? cleanPaste.substring(0, 50) : cleanPaste;

    // Actualizar el modelo
    categoria.value.nombre = limitedPaste;
    event.target.value = limitedPaste;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Control de Categorías de Productos" />
        <Toast class="z-[9999]" />

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Control de Categorías</h1>
                <p class="text-gray-600">Gestión de categorías de productos</p>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('productos')"
                            @click="handleProductosClick"
                            :class="{'opacity-50 cursor-not-allowed': isNavigatingToProductos}"
                            class="flex items-center text-blue-600 hover:text-blue-700 transition-colors duration-200 px-3 py-2 rounded-lg"
                            title="Regresar a Productos">
                            <FontAwesomeIcon
                                :icon="isNavigatingToProductos ? faSpinner : faArrowLeft"
                                :class="{'animate-spin': isNavigatingToProductos, 'h-5 w-5': true}"
                            />
                        </Link>
                        <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Lista de Categorías</h3>
                    </div>
                    <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
                        <button
                            class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2"
                            @click="openNew">
                            <FontAwesomeIcon :icon="faPlus" class="h-4 w-4" />
                            <span>Agregar categoría</span>
                        </button>
                    </div>
                </div>

            <DataTable
                :value="categoriasFiltradas"
                dataKey="id"
                :paginator="true"
                :rows="rowsPerPage"
                :rowsPerPageOptions="rowsPerPageOptions"
                v-model:rowsPerPage="rowsPerPage"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} categorías"
                class="overflow-x-auto max-w-full"
                responsiveLayout="scroll"
            >
                <template #header>
                    <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4">
                        <InputText v-model="filters['global'].value" placeholder="🔍 Buscar categorías..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                    </div>
                </template>

                <Column field="nombre" header="Nombre" sortable class="w-96 min-w-52">
                    <template #body="slotProps">
                        <div class="text-sm font-medium leading-relaxed">
                            {{ slotProps.data.nombre }}
                        </div>
                    </template>
                </Column>

                <Column :exportable="false" class="w-52 min-w-28">
                    <template #header>
                        <div class="text-center w-full font-bold">
                            Acciones
                        </div>
                    </template>
                    <template #body="slotProps">
                        <div class="flex gap-2 justify-center items-center">
                            <button
                                class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="editCategoria(slotProps.data)">
                                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Editar</span>
                            </button>
                            <button
                                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                                @click="confirmDeleteCategoria(slotProps.data)">
                                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                                <span class="hidden md:block text-white">Eliminar</span>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Modal de formulario -->
            <Dialog v-model:visible="dialog" :header="btnTitle + ' Categoría'" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24 flex items-center gap-1">
                                Nombre: <span class="text-red-500 font-bold">*</span>
                            </label>
                            <InputText
                                v-model.trim="categoria.nombre"
                                id="nombre"
                                name="nombre"
                                :maxlength="50"
                                :class="{'p-invalid': submitted && (!categoria.nombre || categoria.nombre.length < 3 || categoria.nombre.length > 50)}"
                                class="flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md"
                                @input="onNombreInput"
                                @paste="onNombrePaste"
                                placeholder="Nombre de la categoría"
                            />
                        </div>
                        <small class="text-red-500 ml-28" v-if="categoria.nombre && categoria.nombre.length < 3">
                            El nombre debe tener al menos 3 caracteres. Actual: {{ categoria.nombre.length }}/3
                        </small>

                        <small class="text-orange-500 ml-28" v-if="categoria.nombre && categoria.nombre.length >= 45 && categoria.nombre.length <= 50">
                            Caracteres restantes: {{ 50 - categoria.nombre.length }}
                        </small>
                        <small class="text-red-500 ml-28" v-if="submitted && !categoria.nombre">
                            El nombre es obligatorio.
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

            <!-- Modal de confirmación de eliminación -->
            <Dialog v-model:visible="deleteDialog" header="Eliminar categoría" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
                    <div class="flex flex-col">
                        <span>¿Estás seguro de eliminar la categoría: <b>{{ categoria.nombre }}</b>?</span>
                        <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="deleteCategoria"
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
                    <div class="flex flex-col">
                        <span>¡Tienes información sin guardar!</span>
                        <span class="text-red-600 text-sm font-medium mt-1">¿Deseas salir sin guardar?</span>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-3 w-full">
                        <button
                            type="button"
                            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="closeDialogWithoutSaving"
                        >
                            <FontAwesomeIcon :icon="faSignOut" class="h-4" />
                            <span>Salir sin guardar</span>
                        </button>
                        <button
                            type="button"
                            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                            @click="continueEditing"
                        >
                            <FontAwesomeIcon :icon="faPencil" class="h-4" />
                            <span>Continuar</span>
                        </button>
                    </div>
                </template>
            </Dialog>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
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
</style>
