<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faCheck, faExclamationTriangle, faFilter, faHandPointUp, faMagnifyingGlass, faPencil, faPlus, faSignOut, faSpinner, faTrashCan, faXmark } from '@fortawesome/free-solid-svg-icons';

const toast = useToast();

// Datos
const paises = ref([]);
const provincias = ref([]);

// UI
const modoSeleccionado = ref("País");
const busquedaGeneral = ref("");
const modalAgregar = ref(false);
const modalEditar = ref(false);
const modalEliminar = ref(false);
const modalCambiosSinGuardar = ref(false);

// Estados de carga
const isLoading = ref(false);
const isDeleting = ref(false);
const isNavigatingToHoteles = ref(false);

// Variable reactiva para el ancho de ventana
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

// Computed para mostrar el nombre correcto del tipo seleccionado
const tipoSeleccionadoDisplay = computed(() => {
    return modoSeleccionado.value === 'Provincia' ? 'Departamento/Provincia' : modoSeleccionado.value;
});

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);

// Formularios
const nuevoItem = ref({ id:null, nombre:"", pais_id:null });
const itemEdit = ref({ id:null, nombre:"", pais_id:null });
const itemEliminar = ref(null);

// Opciones para el select
const opcionesMostrar = ref([
  { label: 'Países', value: 'País' },
  { label: 'Depa.', value: 'Provincia' }
]);

// Funciones para manejo de ventana
const updateWindowWidth = () => {
    if (typeof window !== 'undefined') {
        windowWidth.value = window.innerWidth;
    }
};

// Función para manejar navegación a hoteles
const handleHotelesClick = () => {
    isNavigatingToHoteles.value = true;
};

// Cargar datos
const cargarTodosLosDatos = async () => {
  try {
    // Mostrar toast de carga
    toast.add({
      severity: "info",
      summary: "Cargando datos...",
      life: 2000
    });

    await Promise.all([cargarPaises(), cargarProvincias()]);

    // Mostrar toast de éxito
    toast.add({
      severity: "success",
      summary: "Datos cargados",
      life: 2000
    });
  } catch (error) {
    // Los errores específicos ya son manejados en cada función individual
  }
};

onMounted(() => {
  cargarTodosLosDatos();
  if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateWindowWidth);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateWindowWidth);
    }
});

const cargarPaises = async () => {
  try {
    const res = await axios.get("/api/paises");
    paises.value = res.data;
  } catch (error) {
    toast.add({ severity:"error", summary:"Error", detail:"No se pudieron cargar los países", life: 3000});
  }
};

const cargarProvincias = async () => {
  try {
    const res = await axios.get("/api/provincias");
    provincias.value = res.data;
  } catch {
    toast.add({ severity:"error", summary:"Error", detail:"No se pudieron cargar los departamentos/provincias", life: 3000 });
  }
};

// Datos filtrados
const datosFiltrados = computed(() => {
  let lista = modoSeleccionado.value==="País"? paises.value : provincias.value;
  if(busquedaGeneral.value){
    lista = lista.filter(i=>i.nombre.toLowerCase().includes(busquedaGeneral.value.toLowerCase()));
  }
  return lista;
});

// Métodos
const selectedPaisAgregar = ref('');
const tipoAgregar = ref('');

function abrirModalAgregar(){
  tipoAgregar.value = '';
  nuevoItem.value={id:null,nombre:"",pais_id:null};
  hasUnsavedChanges.value = false;
  modalAgregar.value=true;
}

async function guardarItem(){
  try{
    isLoading.value = true;

    // VALIDACIÓN MEJORADA: Verificar si no hay tipo seleccionado
    if (!tipoAgregar.value) {
      toast.add({severity:"warn", summary:"Atención", detail:"Debe seleccionar qué desea agregar (País o Departamento/Provincia)", life: 4000});
      return;
    }

    // VALIDACIÓN MEJORADA: Verificar nombre vacío o solo espacios
    if (!nuevoItem.value.nombre || nuevoItem.value.nombre.trim() === "") {
      toast.add({severity:"warn", summary:"Campo requerido", detail:"El nombre es obligatorio", life: 4000});
      return;
    }

    // VALIDACIÓN: Longitud máxima
    if (nuevoItem.value.nombre.trim().length > 50) {
      toast.add({severity:"warn", summary:"Límite excedido", detail:"El nombre no puede tener más de 50 caracteres", life: 4000});
      return;
    }
    // VALIDACIÓN ESPECÍFICA PARA DEPARTAMENTOS/PROVINCIAS
    if(tipoAgregar.value === "Provincia" && !nuevoItem.value.pais_id) {
      toast.add({severity:"warn", summary:"Campo requerido", detail:"Debe seleccionar un país para el departamento/provincia", life: 4000});
      return;
    }

    if(tipoAgregar.value==="País"){
      const response = await axios.post("/api/paises",{nombre:nuevoItem.value.nombre.trim().toUpperCase()});
      await cargarPaises();
      toast.add({severity:"success", summary:"Guardado", detail:"País agregado correctamente", life: 3000});
    } else if(tipoAgregar.value==="Provincia"){
      const response = await axios.post("/api/provincias",{
        nombre:nuevoItem.value.nombre.trim().toUpperCase(),
        pais_id:nuevoItem.value.pais_id
      });
      await cargarProvincias();
      toast.add({severity:"success", summary:"Guardado", detail:"Departamento/Provincia agregado correctamente", life: 3000});
    }

    modalAgregar.value=false;
    nuevoItem.value = { id:null, nombre:"", pais_id:null };
    tipoAgregar.value = '';
    hasUnsavedChanges.value = false;
  } catch(error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;

      if (errors?.nombre) {
        // Mostrar mensaje específico del backend
        toast.add({
          severity:"error",
          summary:"Ya existe",
          detail: errors.nombre[0],
          life: 5000
        });
      } else {
        toast.add({
          severity:"error",
          summary:"Error de validación",
          detail: error.response.data.message || "Datos inválidos",
          life: 5000
        });
      }
    } else {
      toast.add({
        severity:"error",
        summary:"Error",
        detail:"No se pudo guardar. Intente nuevamente.",
        life: 4000
      });
    }
  } finally {
    isLoading.value = false;
  }
}

function abrirModalEditar(item){
  itemEdit.value={...item};
  // Asegurar que el nombre siempre esté en mayúsculas
  if(itemEdit.value.nombre) {
    itemEdit.value.nombre = itemEdit.value.nombre.toUpperCase();
  }
  if(modoSeleccionado.value==="Provincia" && item.pais) {
    itemEdit.value.pais_id=item.pais.id;
  }
  hasUnsavedChanges.value = false;
  modalEditar.value=true;
}

async function actualizarItem(){
  try {
    isLoading.value = true;

    // VALIDACIÓN MEJORADA: Verificar nombre vacío
    if (!itemEdit.value.nombre || itemEdit.value.nombre.trim() === "") {
      toast.add({severity:"warn", summary:"Campo requerido", detail:"El nombre es obligatorio", life: 4000});
      return;
    }

    // VALIDACIÓN: Longitud máxima
    if (itemEdit.value.nombre.trim().length > 50) {
      toast.add({severity:"warn", summary:"Límite excedido", detail:"El nombre no puede tener más de 50 caracteres", life: 4000});
      return;
    }

    //  VALIDACIÓN ESPECÍFICA PARA DEPARTAMENTOS/PROVINCIAS
    if(modoSeleccionado.value === "Provincia" && !itemEdit.value.pais_id) {
      toast.add({severity:"warn", summary:"Campo requerido", detail:"Debe seleccionar un país para el departamento/provincia", life: 4000});
      return;
    }

    if(modoSeleccionado.value === "País"){
      await axios.put(`/api/paises/${itemEdit.value.id}`, {
        nombre: itemEdit.value.nombre.trim().toUpperCase()
      });

      await cargarPaises();
      toast.add({severity:"success", summary:"Actualizado", detail:"País actualizado correctamente", life: 3000});
      modalEditar.value = false;
      hasUnsavedChanges.value = false;
    } else {
      await axios.put(`/api/provincias/${itemEdit.value.id}`, {
        nombre: itemEdit.value.nombre.trim().toUpperCase(),
        pais_id: itemEdit.value.pais_id
      });
      await cargarProvincias();
      toast.add({severity:"success", summary:"Actualizado", detail:"Departamento/Provincia actualizado correctamente", life: 3000});
      modalEditar.value = false;
      hasUnsavedChanges.value = false;
    }
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      if (errors?.nombre) {
        toast.add({severity:"error", summary:"Error de validación", detail: errors.nombre[0], life: 5000});
      } else {
        toast.add({severity:"error", summary:"Error de validación", detail: error.response.data.message || "Datos inválidos", life: 5000});
      }
    } else {
      const mensaje = error.response?.data?.message || "No se pudo actualizar";
      toast.add({severity:"error", summary:"Error", detail: mensaje, life: 4000});
    }
  } finally {
    isLoading.value = false;
  }
}

async function eliminarItem(){
  try {
    isDeleting.value = true;

    if (!itemEliminar.value?.id) {
      toast.add({severity:"error", summary:"Error", detail:"No se puede eliminar: ID no válido", life: 3000});
      return;
    }

    if(modoSeleccionado.value === "País"){
      await axios.delete(`/api/paises/${itemEliminar.value.id}`);
      await cargarPaises();
      toast.add({severity:"success", summary:"Eliminado", detail:"País eliminado correctamente", life: 3000});
    } else {
      await axios.delete(`/api/provincias/${itemEliminar.value.id}`);
      await cargarProvincias();
      toast.add({severity:"success", summary:"Eliminado", detail:"Departamento/Provincia eliminado correctamente", life: 3000});
    }

    modalEliminar.value = false;
    itemEliminar.value = null;
  } catch (error) {
    // 🎯 Manejar casos específicos como países con departamentos/provincias asociados
    if (error.response?.status === 422) {
      toast.add({severity:"warn", summary:"No se puede eliminar", detail: error.response.data.message, life: 5000});
    } else {
      const mensaje = error.response?.data?.message || "No se pudo eliminar";
      toast.add({severity:"error", summary:"Error", detail: mensaje, life: 4000});
    }
  } finally {
    isDeleting.value = false;
  }
}

function confirmarEliminar(item) {
  itemEliminar.value = item;
  modalEliminar.value = true;
}

// ✅ MEJORAR VALIDACIÓN EN TIEMPO REAL
const validateNombre = (item, isEdit = false) => {
  const target = isEdit ? itemEdit : nuevoItem;
  if (target.value.nombre && target.value.nombre.length > 50) {
    target.value.nombre = target.value.nombre.substring(0, 50);
  }
};

// Variables para controlar cambios sin guardar
const hasUnsavedChanges = ref(false);
const pendingAction = ref(null);

// Función para convertir texto a mayúsculas y validar caracteres
const convertirAMayuscula = (texto) => {
  // Eliminar números y caracteres especiales, solo permitir letras y espacios
  const textoLimpio = texto.replace(/[^A-Za-zÀ-ÿ\s]/g, '');
  // Convertir a mayúsculas
  return textoLimpio.toUpperCase();
};

// Función para manejar input en tiempo real para nuevo item
const handleInputNuevoItem = (event) => {
  const textoConvertido = convertirAMayuscula(event.target.value);
  nuevoItem.value.nombre = textoConvertido;
};

// Función para manejar input en tiempo real para editar item
const handleInputEditarItem = (event) => {
  const textoConvertido = convertirAMayuscula(event.target.value);
  itemEdit.value.nombre = textoConvertido;
};

// Función para validar keypress (prevenir números y caracteres especiales)
const validarKeypress = (event) => {
  // Permitir solo letras (incluye tildes), espacios, backspace, delete, etc.
  const regex = /^[A-Za-zÀ-ÿ\s]$/;
  const isControlKey = event.ctrlKey || event.metaKey;
  const isNavigationKey = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key);

  if (!regex.test(event.key) && !isControlKey && !isNavigationKey) {
    event.preventDefault();
  }
};

// Función para manejar evento paste (pegar) y validar contenido
const handlePaste = (event, isEdit = false) => {
  event.preventDefault();

  // Obtener texto del portapapeles
  const pastedText = (event.clipboardData || window.clipboardData).getData('text');

  // Limpiar y convertir el texto pegado
  const textoLimpio = convertirAMayuscula(pastedText);

  // Aplicar el texto limpio al campo correspondiente
  if (isEdit) {
    itemEdit.value.nombre = textoLimpio;
  } else {
    nuevoItem.value.nombre = textoLimpio;
  }
};

// Funciones para el modal de cambios sin guardar
const checkForUnsavedChanges = (action) => {
  if (hasUnsavedChanges.value) {
    pendingAction.value = action;
    modalCambiosSinGuardar.value = true;
    return true;
  }
  return false;
};

const closeWithoutSaving = () => {
  hasUnsavedChanges.value = false;
  modalCambiosSinGuardar.value = false;
  if (pendingAction.value) {
    pendingAction.value();
    pendingAction.value = null;
  }
};

const continueEditing = () => {
  modalCambiosSinGuardar.value = false;
  pendingAction.value = null;
};

// Detectar cambios en los formularios
const watchFormChanges = () => {
  // Watch para el formulario de agregar
  watch([() => nuevoItem.value.nombre, () => nuevoItem.value.pais_id, () => tipoAgregar.value], () => {
    if (modalAgregar.value && (nuevoItem.value.nombre?.trim() || nuevoItem.value.pais_id || tipoAgregar.value)) {
      hasUnsavedChanges.value = true;
    }
  }, { deep: true });

  // Watch para el formulario de editar
  watch([() => itemEdit.value.nombre, () => itemEdit.value.pais_id], () => {
    if (modalEditar.value) {
      hasUnsavedChanges.value = true;
    }
  }, { deep: true });
};

// Inicializar watchers
onMounted(() => {
  watchFormChanges();
});
</script>

<template>
  <Head title="Países y Departamentos/Provincias" />
  <AuthenticatedLayout>
    <Toast class="z-[9999]" />

    <div class="container mx-auto px-4 py-6">
      <!-- Header con botón volver -->
      <div class="mb-6">
        <!-- Botón volver -->
        <Link
          :href="route('hoteles')"
          @click="handleHotelesClick"
          :class="{'opacity-50 cursor-not-allowed': isNavigatingToHoteles}"
          class="inline-flex p-2 items-center bg-blue-500 hover:bg-blue-600 text-white hover:text-gray-200 rounded-md transition-colors duration-200"
          title="Regresar a Hoteles">
          <FontAwesomeIcon
            :icon="isNavigatingToHoteles ? faSpinner : faArrowLeft"
            :class="{'animate-spin': isNavigatingToHoteles}"
            class="h-5 w-5 mr-2"
          />
          <span class="font-medium">{{ isNavigatingToHoteles ? 'Cargando...' : 'Volver a Hoteles' }}</span>
        </Link>
      </div>

      <div class="bg-white rounded-lg shadow-md">
        <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center gap-4 p-4">
          <div class="text-center sm:text-left">
            <h1 class="text-3xl font-bold text-blue-600 mb-2">Control de Países y Departamentos</h1>
            <p class="text-gray-600">Gestión de países y departamentos y/o provincias del sistema</p>
          </div>
          <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
            <button
              class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2"
              @click="abrirModalAgregar">
              <FontAwesomeIcon :icon="faPlus" class="h-4 w-4" />
              <span>Agregar {{ modoSeleccionado.toLowerCase() }}</span>
            </button>
          </div>
        </div>

      <!-- 📊 TABLA OPTIMIZADA -->
      <DataTable
        :value="datosFiltrados"
        dataKey="id"
        :paginator="true"
        :rows="rowsPerPage"
        :rowsPerPageOptions="rowsPerPageOptions"
        v-model:rowsPerPage="rowsPerPage"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
        :currentPageReportTemplate="`Mostrando {first} a {last} de {totalRecords} ${modoSeleccionado === 'País' ? 'países' : 'provincias'}`"
        class="overflow-x-auto"
        responsiveLayout="scroll"
        :pt="{
          root: { class: 'text-sm' },
          wrapper: { class: 'text-sm' },
          table: { class: 'text-sm' },
          thead: { class: 'text-sm' },
          headerRow: { class: 'text-sm' },
          headerCell: { class: 'text-sm font-medium py-3 px-2' },
          tbody: { class: 'text-sm' },
          bodyRow: { class: 'h-16 text-sm' },
          bodyCell: { class: 'py-3 px-2 text-sm' },
          paginator: { class: 'text-xs sm:text-sm' },
          paginatorWrapper: { class: 'flex flex-wrap justify-center sm:justify-between items-center gap-2 p-2' }
        }"
      >
        <template #header>
          <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <h3 class="text-base font-medium text-gray-800 flex items-center gap-1">
                  <i class="pi pi-filter text-blue-600 text-sm"></i>
                  <span>Filtros</span>
                </h3>
                <div class="bg-blue-100 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                  {{ datosFiltrados.length }} resultado{{ datosFiltrados.length !== 1 ? 's' : '' }}
                </div>
              </div>
              <div class="flex items-center gap-2">
                <label for="tipo-estado" class="text-sm font-medium text-gray-700 hidden sm:block">Mostrar:</label>
                <select
                  id="tipo-estado"
                  v-model="modoSeleccionado"
                  class="w-24 sm:w-32 h-8 text-sm border border-blue-300 rounded-md px-1 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                >
                  <option value="" disabled selected hidden>Mostrar</option>
                  <option
                    v-for="opcion in opcionesMostrar"
                    :key="opcion.value"
                    :value="opcion.value"
                    class="truncate text-gray-900 text-lg"
                  >
                    {{ opcion.label }}
                  </option>
                </select>
              </div>
            </div>
            <div class="space-y-3">
              <div class="relative">
                <FontAwesomeIcon :icon="faMagnifyingGlass" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                <InputText
                  v-model="busquedaGeneral"
                  v-if="modoSeleccionado==='Provincia'"
                  placeholder="Buscar departamentos/provincias..."
                  class="w-full h-9 text-sm rounded-md pl-10"
                  style="background-color: white; border-color: #93c5fd;"
                />
                <InputText
                  v-model="busquedaGeneral"
                  v-else
                  placeholder="Buscar países..."
                  class="w-full h-9 text-sm rounded-md pl-10"
                  style="background-color: white; border-color: #93c5fd;"
                />
              </div>
            </div>
          </div>
        </template>

        <Column field="nombre" header="Nombre" sortable class="w-96 min-w-28">
          <template #body="slotProps">
            <div class="text-sm font-medium leading-relaxed">
              {{ slotProps.data.nombre }}
            </div>
          </template>
        </Column>

        <Column v-if="modoSeleccionado==='Provincia'" field="pais.nombre" header="País" sortable class="w-96 min-w-20">
          <template #body="slotProps">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {{ slotProps.data.pais?.nombre || 'Sin país' }}
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
                class="flex bg-blue-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                @click="abrirModalEditar(slotProps.data)">
                <FontAwesomeIcon :icon="faPencil" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                <span class="hidden md:block text-white">Editar</span>
              </button>
              <button
                class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                @click="confirmarEliminar(slotProps.data)">
                <FontAwesomeIcon :icon="faTrashCan" class="h-3 w-6 sm:h-4 sm:w-7 text-white" />
                <span class="hidden md:block text-white">Eliminar</span>
              </button>
            </div>
          </template>
        </Column>
      </DataTable>

      <!-- 📝 Modal Agregar CON VALIDACIÓN VISUAL MEJORADA -->
      <Dialog v-model:visible="modalAgregar" header="Agregar" :modal="true" :closable="false" :style="dialogStyle" :draggable="false">
        <div class="flex flex-col gap-4">
          <div class="w-full flex flex-col">
            <label class="text-sm font-medium text-gray-700 mb-2">
              Tipo: <span class="text-red-500">*</span>
            </label>
            <select
              v-model="tipoAgregar"
              class="w-full h-9 text-sm border border-gray-500 rounded-md px-3 py-1 bg-white text-gray-700 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-500 truncate"
              :class="{ 'border-red-300': !tipoAgregar }"
            >
              <option value="" disabled selected hidden>Seleccione qué desea agregar</option>
              <option value="País" class="truncate">País</option>
              <option value="Provincia" class="truncate">Departamento/Provincia</option>
            </select>
          </div>

          <div class="w-full flex flex-col">
            <label class="text-sm font-medium text-gray-700 mb-2">
              Nombre: <span class="text-red-500">*</span>
            </label>
            <InputText
              v-model="nuevoItem.nombre"
              placeholder="NOMBRE (MÁXIMO 50 CARACTERES)"
              class="w-full uppercase flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md text-gray-600"
              :class="{ 'border-red-300': !nuevoItem.nombre || nuevoItem.nombre.trim() === '' }"
              :disabled="!tipoAgregar"
              maxlength="50"
              @keypress="validarKeypress"
              @input="handleInputNuevoItem"
              @paste="handlePaste($event, false)"
            />
            <small class="text-orange-500 mt-1" v-if="nuevoItem.nombre && nuevoItem.nombre.length >= 40 && nuevoItem.nombre.length <= 50">
              Caracteres restantes: {{ 50 - nuevoItem.nombre.length }}
            </small>
          </div>

          <div v-if="tipoAgregar==='Provincia'" class="w-full flex flex-col">
            <label class="text-sm font-medium text-gray-700 mb-2">
              País: <span class="text-red-500">*</span>
            </label>
            <select
              v-model="nuevoItem.pais_id"
              class="w-full h-9 text-sm border border-gray-300 rounded-md px-3 py-1 bg-white text-gray-700 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-500 truncate"
              :class="{ 'border-red-300': tipoAgregar === 'Provincia' && !nuevoItem.pais_id }"
            >
              <option value="" disabled selected hidden>Seleccione un país</option>
              <option
                v-for="pais in paises"
                :key="pais.id"
                :value="pais.id"
                class="truncate"
              >
                {{ pais.nombre }}
              </option>
            </select>
          </div>
        </div>
        <template #footer>
          <div class="flex justify-center gap-4 w-full mt-6">
            <button
              class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="guardarItem"
              :disabled="!nuevoItem.nombre?.trim() || !tipoAgregar || nuevoItem.nombre.trim().length > 50 || (tipoAgregar === 'Provincia' && !nuevoItem.pais_id) || isLoading">
              <FontAwesomeIcon
                :icon="isLoading ? faSpinner : faCheck"
                :class="[
                  'h-5 text-white',
                  { 'animate-spin': isLoading }
                ]"
              />
              <span v-if="!isLoading">Guardar</span>
              <span v-else>Guardando...</span>
            </button>
            <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="() => { if (!checkForUnsavedChanges(() => { modalAgregar = false; hasUnsavedChanges = false; })) { modalAgregar = false; hasUnsavedChanges = false; } }" :disabled="isLoading">
              <FontAwesomeIcon :icon="faXmark" class="h-5" />Cancelar
            </button>
          </div>
        </template>
      </Dialog>

      <!-- ✏️ Modal Editar CON VALIDACIÓN VISUAL MEJORADA -->
      <Dialog v-model:visible="modalEditar" :header="`Editar ${tipoSeleccionadoDisplay}`" :modal="true" :closable="false" :style="dialogStyle" :draggable="false">
        <div class="flex flex-col gap-4">
          <div v-if="modoSeleccionado==='Provincia'" class="w-full flex flex-col">
            <label class="text-sm font-medium text-gray-700 mb-2">
              País: <span class="text-red-500">*</span>
            </label>
            <select
              v-model="itemEdit.pais_id"
              class="w-full h-9 text-sm border border-gray-300 rounded-md px-3 py-1 bg-white text-gray-700 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
              :class="{ 'border-red-300': modoSeleccionado === 'Provincia' && !itemEdit.pais_id }"
            >
              <option value="" disabled selected hidden>Seleccione un país</option>
              <option
                v-for="pais in paises"
                :key="pais.id"
                :value="pais.id"
                class="truncate"
              >
                {{ pais.nombre }}
              </option>
            </select>
          </div>

          <div class="w-full flex flex-col">
            <label class="text-sm font-medium text-gray-700 mb-2">
              Nombre: <span class="text-red-500">*</span>
            </label>
            <InputText
              v-model="itemEdit.nombre"
              placeholder="NOMBRE (MÁXIMO 50 CARACTERES)"
              class="w-full uppercase flex-1 border-2 border-gray-400 hover:border-gray-500 focus:border-gray-500 focus:ring-0 focus:shadow-none rounded-md text-gray-600"
              :class="{ 'border-red-300': !itemEdit.nombre || itemEdit.nombre.trim() === '' }"
              maxlength="50"
              @keypress="validarKeypress"
              @input="handleInputEditarItem"
              @paste="handlePaste($event, true)"
            />
            <small class="text-orange-500 mt-1" v-if="itemEdit.nombre && itemEdit.nombre.length >= 40 && itemEdit.nombre.length <= 50">
              Caracteres restantes: {{ 50 - itemEdit.nombre.length }}
            </small>
          </div>
        </div>
        <template #footer>
          <div class="flex justify-center gap-4 w-full mt-6">
            <button
              class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="actualizarItem"
              :disabled="!itemEdit.nombre?.trim() || itemEdit.nombre.trim().length > 50 || (modoSeleccionado === 'Provincia' && !itemEdit.pais_id) || isLoading">
              <FontAwesomeIcon
                :icon="isLoading ? faSpinner : faCheck"
                :class="[
                  'h-5 text-white',
                  { 'animate-spin': isLoading }
                ]"
              />
              <span v-if="!isLoading">Actualizar</span>
              <span v-else>Actualizando...</span>
            </button>
            <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2" @click="() => { if (!checkForUnsavedChanges(() => { modalEditar = false; hasUnsavedChanges = false; })) { modalEditar = false; hasUnsavedChanges = false; } }" :disabled="isLoading">
              <FontAwesomeIcon :icon="faXmark" class="h-5" />Cancelar
            </button>
          </div>
        </template>
      </Dialog>

      <!-- 🗑️ Modal Confirmar eliminar -->
      <Dialog v-model:visible="modalEliminar" :header="`Eliminar ${tipoSeleccionadoDisplay}`" :modal="true" :closable="false" :style="dialogStyle" :draggable="false">
        <div class="flex items-center gap-3">
          <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
          <div class="flex flex-col">
            <span>¿Estás seguro de eliminar {{ tipoSeleccionadoDisplay.toLowerCase() }}: <b>{{ itemEliminar?.nombre }}</b>?</span>
            <span class="text-red-600 text-sm font-medium mt-1">Esta acción es irreversible.</span>
          </div>
        </div>
        <template #footer>
          <div class="flex justify-center gap-4 w-full">
            <button
              type="button"
              class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="eliminarItem"
              :disabled="!itemEliminar || !itemEliminar.nombre || isDeleting">
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
              @click="modalEliminar=false" :disabled="isDeleting">
              <FontAwesomeIcon :icon="faXmark" class="h-5" /><span>Cancelar</span>
            </button>
          </div>
        </template>
      </Dialog>

      <!-- Modal de Cambios sin guardar -->
      <Dialog v-model:visible="modalCambiosSinGuardar" header="Cambios sin guardar" :modal="true" :style="dialogStyle" :closable="false" :draggable="false">
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
              @click="closeWithoutSaving">
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

/* Estilo para inputs en mayúsculas */
.uppercase {
  text-transform: uppercase !important;
}
/* Fin del estilo para inputs en mayúsculas */
</style>
