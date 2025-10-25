<template>
  <AuthenticatedLayout>
    <Head title="Inventarios" />
    <Toast class="z-[9999]" />

    <div class="container mx-auto px-4 py-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">Inventarios</h1>
        <p class="text-gray-600">Monitoreo y análisis del estado del inventario</p>
      </div>

      <!-- Tarjetas de Resumen -->
      <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-8">
        <Card class="bg-gradient-to-r from-green-500 to-green-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Productos Disponibles</p>
                <p class="text-xl sm:text-3xl font-bold">{{ resumen.productos_disponibles || 0 }}</p>
              </div>
              <FontAwesomeIcon :icon="faCheckCircle" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-red-500 to-red-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Productos Agotados</p>
                <p class="text-xl sm:text-3xl font-bold">{{ resumen.productos_agotados || 0 }}</p>
              </div>
              <FontAwesomeIcon :icon="faTimesCircle" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-yellow-500 to-yellow-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Stock Bajo</p>
                <p class="text-xl sm:text-3xl font-bold">{{ resumen.productos_stock_bajo || 0 }}</p>
              </div>
              <FontAwesomeIcon :icon="faExclamationTriangle" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-blue-500 to-blue-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Valor Total</p>
                <p class="text-sm sm:text-3xl font-bold">${{ formatearPrecio(resumen.valor_total_inventario) }}</p>
              </div>
              <FontAwesomeIcon :icon="faDollarSign" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>
      </div>

      <!-- Tabla de Movimientos -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
          <div>
            <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Historial de Movimientos</h3>
            <p class="text-gray-500 text-sm text-center sm:text-start mt-1">
              📊 Vista de solo lectura - Para gestionar stock, ve a
              <span class="text-blue-600 font-medium">Productos > Más Acciones > Actualizar Stock</span>
            </p>
          </div>
          <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
            <button
              @click="exportarReporte"
              class="bg-green-500 border border-green-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2"
              :disabled="cargando"
            >
              <FontAwesomeIcon :icon="cargando ? faSpinner : faDownload" :class="{ 'animate-spin': cargando, 'h-4 w-4': true }" />
              <span>{{ cargando ? 'Exportando...' : 'Exportar Reporte' }}</span>
            </button>
          </div>
        </div>

        <DataTable
          :value="movimientosFiltrados"
          dataKey="id"
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[10, 25, 50]"
          :globalFilterFields="['producto.nombre', 'motivo', 'user.name']"
          paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} movimientos"
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
                    {{ movimientosFiltrados.length }} resultado{{ movimientosFiltrados.length !== 1 ? 's' : '' }}
                  </div>
                  <button
                    @click="limpiarFiltros"
                    :disabled="isClearingFilters"
                    class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md flex sm:hidden disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                  >
                    <FontAwesomeIcon
                      v-if="isClearingFilters"
                      :icon="faSpinner"
                      class="animate-spin h-3 w-3"
                    />
                    <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
                  </button>
                </div>
                <button
                  @click="limpiarFiltros"
                  :disabled="isClearingFilters"
                  class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md hidden sm:flex disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
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
                  <InputText
                    v-model="busqueda"
                    placeholder="🔍 Buscar movimientos..."
                    class="w-full h-9 text-sm rounded-md"
                    style="background-color: white; border-color: #93c5fd;"
                  />
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-3">
                  <div class="col-span-1">
                    <Select
                      v-model="filtros.producto_id"
                      :options="Array.isArray(productos) ? productos : []"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Producto"
                      class="w-full h-9 text-sm border"
                      style="background-color: white; border-color: #93c5fd;"
                      showClear
                    />
                  </div>

                  <div class="col-span-1">
                    <Select
                      v-model="filtros.tipo_movimiento"
                      :options="tiposMovimiento"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Tipo"
                      class="w-full h-9 text-sm border"
                      style="background-color: white; border-color: #93c5fd;"
                      showClear
                    />
                  </div>

                  <div class="col-span-1 hidden md:block">
                    <DatePicker
                      v-model="filtros.fecha_desde"
                      placeholder="Fecha desde"
                      dateFormat="yy-mm-dd"
                      class="w-full h-9 text-sm"
                      style="background-color: white; border-color: #93c5fd;"
                      showIcon
                    />
                  </div>

                  <div class="col-span-1 hidden md:block">
                    <DatePicker
                      v-model="filtros.fecha_hasta"
                      placeholder="Fecha hasta"
                      dateFormat="yy-mm-dd"
                      class="w-full h-9 text-sm"
                      style="background-color: white; border-color: #93c5fd;"
                      showIcon
                    />
                  </div>

                  <!-- DatePickers para móviles - en la fila de abajo -->
                  <div class="col-span-2 flex gap-3 md:hidden">
                    <DatePicker
                      v-model="filtros.fecha_desde"
                      placeholder="Fecha desde"
                      dateFormat="dd/mm/yy"
                      class="flex-1 h-9 text-sm rounded-md border border-blue-300"
                      showIcon
                    />
                    <DatePicker
                      v-model="filtros.fecha_hasta"
                      placeholder="Fecha hasta"
                      dateFormat="dd/mm/yy"
                      class="flex-1 h-9 text-sm rounded-md border border-blue-300"
                      showIcon
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>
          <Column field="fecha_movimiento" header="Fecha" sortable class="w-32">
            <template #body="{ data }">
              <div class="text-sm font-medium leading-relaxed">
                {{ formatearFecha(data.fecha_movimiento) }}
              </div>
            </template>
          </Column>

          <Column field="producto.nombre" header="Producto" sortable class="w-40">
            <template #body="{ data }">
              <div>
                <div
                  class="text-sm font-medium leading-relaxed overflow-hidden"
                  style="max-width: 150px; text-overflow: ellipsis; white-space: nowrap;"
                  :title="data.producto?.nombre"
                >
                  {{ data.producto?.nombre }}
                </div>
                <span class="block text-xs text-gray-500">{{ data.producto?.categoria?.nombre }}</span>
              </div>
            </template>
          </Column>

          <Column field="tipo_movimiento" header="Tipo" sortable class="w-24 hidden sm:table-cell">
            <template #body="{ data }">
              <Tag
                :value="data.tipo_movimiento"
                :severity="data.tipo_movimiento === 'ENTRADA' ? 'success' : 'danger'"
                class="text-xs"
              />
            </template>
          </Column>

          <Column field="cantidad" header="Cantidad" sortable class="w-20 hidden sm:table-cell">
            <template #body="{ data }">
              <span
                :class="data.tipo_movimiento === 'ENTRADA' ? 'text-green-600' : 'text-red-600'"
                class="text-sm font-medium"
              >
                {{ data.tipo_movimiento === 'ENTRADA' ? '+' : '-' }}{{ data.cantidad }}
              </span>
            </template>
          </Column>

          <Column field="motivo" header="Motivo" sortable class="w-32 hidden md:table-cell">
            <template #body="{ data }">
              <div
                class="text-sm leading-relaxed overflow-hidden"
                style="max-width: 120px; text-overflow: ellipsis; white-space: nowrap;"
                :title="data.motivo || 'Sin especificar'"
              >
                {{ data.motivo || 'Sin especificar' }}
              </div>
            </template>
          </Column>

          <Column field="user.name" header="Usuario" sortable class="w-28 hidden lg:table-cell">
            <template #body="{ data }">
              <div class="text-sm leading-relaxed">
                {{ data.user?.name || 'Sistema' }}
              </div>
            </template>
          </Column>

          <Column :exportable="false" class="w-32 min-w-24">
            <template #header>
              <div class="text-center w-full font-bold">
                Acciones
              </div>
            </template>
            <template #body="{ data }">
              <div class="flex gap-1 justify-center items-center">
                <button
                  class="flex bg-blue-500 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="verDetalle(data)"
                  :disabled="isViewingDetail"
                  title="Ver detalle"
                >
                  <FontAwesomeIcon
                    v-if="isViewingDetail"
                    :icon="faSpinner"
                    class="animate-spin text-white text-xs"
                  />
                  <FontAwesomeIcon v-else :icon="faEye" class="text-white text-xs" />
                  <span class="hidden md:block text-white ml-1">{{ isViewingDetail ? 'Cargando...' : 'Ver' }}</span>
                </button>
                <button
                  class="flex bg-green-500 border p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="verHistorialProducto(data.producto_id)"
                  :disabled="isViewingHistory"
                  title="Historial del producto"
                >
                  <FontAwesomeIcon
                    v-if="isViewingHistory"
                    :icon="faSpinner"
                    class="animate-spin text-white text-xs"
                  />
                  <FontAwesomeIcon v-else :icon="faHistory" class="text-white text-xs" />
                  <span class="hidden lg:block text-white ml-1">{{ isViewingHistory ? 'Cargando...' : 'Hist' }}</span>
                </button>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Modal Agregar Stock -->
    <!-- Modal Detalle -->    <!-- Modal Ajustar Stock -->
    <Dialog
      v-model:visible="mostrarModalAjustar"
      modal
      header="Ajustar Stock"
      :style="dialogStyle"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Producto</label>
          <Select
            v-model="formAjustar.producto_id"
            :options="Array.isArray(productos) ? productos : []"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccionar producto"
            class="w-full"
            :class="{ 'p-invalid': errores.producto_id }"
            @change="actualizarStockActual"
          />
          <small v-if="errores.producto_id" class="p-error">{{ errores.producto_id[0] }}</small>
        </div>

        <div v-if="stockActual !== null" class="p-4 bg-blue-50 rounded-lg">
          <p class="text-sm text-blue-700">
            <strong>Stock actual:</strong> {{ stockActual }} unidades
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Stock</label>
          <InputText
            v-model="formAjustar.nuevo_stock"
            placeholder="Nuevo stock"
            type="number"
            min="0"
            class="w-full"
            :class="{ 'p-invalid': errores.nuevo_stock }"
          />
          <small v-if="errores.nuevo_stock" class="p-error">{{ errores.nuevo_stock[0] }}</small>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
          <Textarea
            v-model="formAjustar.observacion"
            placeholder="Motivo del ajuste"
            rows="3"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <button
            @click="ajustarStock"
            :disabled="procesando"
            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <FontAwesomeIcon
              v-if="procesando"
              :icon="faSpinner"
              class="animate-spin h-5 text-white"
            />
            <FontAwesomeIcon v-else :icon="faPencil" class="h-5" />
            <span v-if="!procesando">Ajustar Stock</span>
            <span v-else>Ajustando...</span>
          </button>
          <button
            type="button"
            @click="mostrarModalAjustar = false"
            :disabled="procesando"
            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          >
            <FontAwesomeIcon :icon="faTimes" class="h-5" />Cancelar
          </button>
        </div>
      </template>
    </Dialog>

    <!-- Modal Detalle -->
    <Dialog
      v-model:visible="mostrarModalDetalle"
      modal
      header="Detalle del Movimiento"
      :style="dialogStyle"
    >
      <div v-if="movimientoSeleccionado" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <strong>Fecha:</strong> {{ formatearFecha(movimientoSeleccionado.fecha_movimiento) }}
          </div>
          <div>
            <strong>Tipo:</strong>
            <Tag
              :value="movimientoSeleccionado.tipo_movimiento"
              :severity="movimientoSeleccionado.tipo_movimiento === 'ENTRADA' ? 'success' : 'danger'"
            />
          </div>
          <div>
            <strong>Producto:</strong> {{ movimientoSeleccionado.producto?.nombre }}
          </div>
          <div>
            <strong>Cantidad:</strong>
            <span :class="movimientoSeleccionado.tipo_movimiento === 'ENTRADA' ? 'text-green-600' : 'text-red-600'">
              {{ movimientoSeleccionado.tipo_movimiento === 'ENTRADA' ? '+' : '-' }}{{ movimientoSeleccionado.cantidad }}
            </span>
          </div>
          <div>
            <strong>Usuario:</strong> {{ movimientoSeleccionado.user?.name || 'Sistema' }}
          </div>
          <div>
            <strong>Motivo:</strong> {{ movimientoSeleccionado.motivo || 'Sin especificar' }}
          </div>
        </div>

        <div v-if="movimientoSeleccionado.observacion">
          <strong>Observaciones:</strong>
          <p class="mt-1 p-3 bg-gray-50 rounded-lg">{{ movimientoSeleccionado.observacion }}</p>
        </div>

        <div v-if="movimientoSeleccionado.venta_id" class="p-3 bg-blue-50 rounded-lg">
          <strong>Relacionado con venta #{{ movimientoSeleccionado.venta_id }}</strong>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <button
            type="button"
            @click="mostrarModalDetalle = false"
            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          >
            <FontAwesomeIcon :icon="faTimes" class="h-5" />Cerrar
          </button>
        </div>
      </template>
    </Dialog>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import {
  faSpinner,
  faFilter,
  faCheckCircle,
  faTimesCircle,
  faExclamationTriangle,
  faDollarSign,
  faPlus,
  faPencil,
  faEye,
  faHistory,
  faTimes,
  faDownload
} from "@fortawesome/free-solid-svg-icons"
import axios from 'axios'

const toast = useToast()

// Estado reactivo
const movimientos = ref([])
const productos = ref([])
const resumen = ref({})
const cargando = ref(false)
const procesando = ref(false)
const busqueda = ref('')
const stockActual = ref(null)
const isClearingFilters = ref(false)
const isViewingDetail = ref(false)
const isViewingHistory = ref(false)

// Modales
const mostrarModalDetalle = ref(false)
const movimientoSeleccionado = ref(null)

// Filtros
const filtros = ref({
  producto_id: null,
  tipo_movimiento: null,
  fecha_desde: null,
  fecha_hasta: null
})

// Variables para manejo de datos
const errores = ref({})

// Opciones para dropdowns
const tiposMovimiento = [
  { label: 'Entrada', value: 'ENTRADA' },
  { label: 'Salida', value: 'SALIDA' }
]

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

// Computed
const movimientosFiltrados = computed(() => {
  let resultado = movimientos.value

  if (busqueda.value) {
    const termino = busqueda.value.toLowerCase()
    resultado = resultado.filter(movimiento =>
      movimiento.producto?.nombre?.toLowerCase().includes(termino) ||
      movimiento.motivo?.toLowerCase().includes(termino) ||
      movimiento.user?.name?.toLowerCase().includes(termino)
    )
  }

  return resultado
})

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
    if (windowWidth.value < 640) {
        return { width: '95vw', maxWidth: '380px' };
    } else if (windowWidth.value < 768) {
        return { width: '400px' };
    } else {
        return { width: '450px' };
    }
})

// Métodos
const cargarDatos = async () => {
  try {
    const [movimientosRes, productosRes, resumenRes] = await Promise.all([
      axios.get('/api/inventario', { params: filtros.value }),
      axios.get('/api/productos'),
      axios.get('/api/inventario/resumen')
    ])

    // Asegurar que movimientos siempre sea un array
    movimientos.value = movimientosRes.data.data || movimientosRes.data || []

    // Procesar productos con validación adicional
    const productosData = productosRes.data.data || productosRes.data || []
    productos.value = Array.isArray(productosData) ? productosData : []

    // Validar que productos tenga la estructura correcta
    if (productos.value.length > 0 && !Array.isArray(productos.value)) {
      console.warn('Los productos no tienen el formato esperado:', productos.value)
      productos.value = []
    }

    resumen.value = resumenRes.data || {}
  } catch (error) {
    console.error('Error al cargar datos:', error)
    // Establecer valores por defecto en caso de error
    movimientos.value = []
    productos.value = []
    resumen.value = {}

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar los datos del inventario',
      life: 4000
    })
  }
}

const cargarDatosWithToasts = async () => {
  cargando.value = true

  // Mostrar toast de carga con duración automática
  toast.add({
    severity: "info",
    summary: "Cargando inventario...",
    life: 2000
  })

  try {
    await cargarDatos()

    // Mostrar toast de éxito
    toast.add({
      severity: "success",
      summary: "Inventario cargado",
      life: 2000
    })
  } finally {
    cargando.value = false
  }
}

const limpiarFiltros = async () => {
  isClearingFilters.value = true

  try {
    // Simular un pequeño delay para mostrar el loading
    await new Promise(resolve => setTimeout(resolve, 300))

    filtros.value = {
      producto_id: null,
      tipo_movimiento: null,
      fecha_desde: null,
      fecha_hasta: null
    }

    toast.add({
      severity: "success",
      summary: "Filtros limpiados",
      life: 2000
    })

    await cargarDatos()
  } finally {
    isClearingFilters.value = false
  }
}

// Función para exportar reportes
const exportarReporte = async () => {
  cargando.value = true

  try {
    const response = await axios.get('/api/inventario/exportar', {
      responseType: 'blob',
      params: filtros.value
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `inventario_${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Reporte exportado correctamente',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al exportar el reporte',
      life: 4000
    })
  } finally {
    cargando.value = false
  }
}

const actualizarStockActual = () => {
  if (formAjustar.value.producto_id && Array.isArray(productos.value)) {
    const producto = productos.value.find(p => p.id === formAjustar.value.producto_id)
    stockActual.value = producto?.stock_actual || 0
  } else {
    stockActual.value = null
  }
}

const verDetalle = async (movimiento) => {
  isViewingDetail.value = true
  try {
    const response = await axios.get(`/api/inventario/${movimiento.id}`)
    movimientoSeleccionado.value = response.data
    mostrarModalDetalle.value = true
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar el detalle del movimiento',
      life: 4000
    })
  } finally {
    isViewingDetail.value = false
  }
}

const verHistorialProducto = async (productoId) => {
  isViewingHistory.value = true
  try {
    // Implementar navegación al historial específico del producto
    filtros.value.producto_id = productoId
    await cargarDatos()

    toast.add({
      severity: 'info',
      summary: 'Filtro aplicado',
      detail: 'Mostrando historial del producto seleccionado',
      life: 3000
    })
  } finally {
    isViewingHistory.value = false
  }
}

// Función para manejar el clic en la fila
const onRowClick = (event) => {
  // Verificar si el clic fue en un botón para evitar abrir el modal
  const target = event.originalEvent.target
  const isButton = target.closest('button')

  if (!isButton) {
    movimientoSeleccionado.value = event.data
    mostrarModalDetalle.value = true
  }
}

const formatearFecha = (fecha) => {
  const fechaObj = new Date(fecha)

  // Para dispositivos móviles, formato más compacto
  if (window.innerWidth < 640) {
    return fechaObj.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  // Para desktop, formato completo
  return fechaObj.toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatearPrecio = (precio) => {
  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(precio || 0)
}

// Watchers
watch(filtros, () => {
  cargarDatos()
}, { deep: true })

// Lifecycle
onMounted(() => {
  cargarDatosWithToasts()
})
</script>

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

/* Estilos para botones con efectos hover */
.transition-transform {
  transition: transform 0.3s ease;
}

.hover\:-translate-y-1:hover {
  transform: translateY(-0.25rem);
}

/* Responsive table styles */
@media (max-width: 640px) {
  .p-datatable .p-datatable-thead > tr > th,
  .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.5rem 0.25rem;
  }
}

@media (max-width: 768px) {
  .p-datatable .p-datatable-thead > tr > th,
  .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.75rem 0.5rem;
  }
}
</style>
