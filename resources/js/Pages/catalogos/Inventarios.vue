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
        <Card class="bg-gradient-to-r from-green-500 to-green-600 cursor-pointer hover:shadow-lg transition-shadow duration-200" @click="abrirModalProductos('disponibles')">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Productos Disponibles</p>
                <p class="text-xl sm:text-3xl font-bold">{{ resumen.productos_disponibles || 0 }}</p>
                <p class="text-xs opacity-75 mt-1">👆 Clic para ver detalles</p>
              </div>
              <FontAwesomeIcon :icon="faCheckCircle" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-red-500 to-red-600 cursor-pointer hover:shadow-lg transition-shadow duration-200" @click="abrirModalProductos('agotados')">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Productos Agotados</p>
                <p class="text-xl sm:text-3xl font-bold">{{ resumen.productos_agotados || 0 }}</p>
                <p class="text-xs opacity-75 mt-1">👆 Clic para ver detalles</p>
              </div>
              <FontAwesomeIcon :icon="faTimesCircle" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-yellow-500 to-yellow-600 cursor-pointer hover:shadow-lg transition-shadow duration-200" @click="abrirModalProductos('stock_bajo')">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Stock Bajo</p>
                <p class="text-xl sm:text-3xl font-bold">{{ resumen.productos_stock_bajo || 0 }}</p>
                <p class="text-xs opacity-75 mt-1">👆 Clic para ver detalles</p>
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
            <p class="text-blue-600 text-sm text-center sm:text-start mt-1">
                <FontAwesomeIcon :icon="faHandPointUp" class="h-4 w-4 text-yellow-500" />
                Haz clic en cualquier fila para ver los detalles.
            </p>
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
                    <select
                      v-model="filtros.producto_id"
                      @change="aplicarFiltros"
                      class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-700 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                    >
                      <option value="" disabled selected hidden>Producto</option>
                      <option
                        v-for="producto in Array.isArray(productos) ? productos : []"
                        :key="producto.id"
                        :value="producto.id"
                        class="truncate"
                      >
                        {{ producto.nombre }}
                      </option>
                    </select>
                  </div>

                  <div class="col-span-1">
                    <select
                      v-model="filtros.tipo_movimiento"
                      @change="aplicarFiltros"
                      class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-700 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                    >
                      <option value="" disabled selected hidden>Tipo</option>
                      <option
                        v-for="tipo in tiposMovimiento"
                        :key="tipo.value"
                        :value="tipo.value"
                        class="truncate"
                      >
                        {{ tipo.label }}
                      </option>
                    </select>
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

                  <!-- Calendars para móviles - en la fila de abajo -->
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
                :severity="data.tipo_movimiento === 'ENTRADA' ? 'success' :
                          data.tipo_movimiento === 'SALIDA' ? 'danger' : 'warning'"
                class="text-xs"
              />
            </template>
          </Column>

          <Column field="cantidad" header="Cantidad" sortable class="w-20 hidden sm:table-cell">
            <template #body="{ data }">
              <span
                :class="data.tipo_movimiento === 'ENTRADA' ? 'text-green-600' :
                       data.tipo_movimiento === 'SALIDA' ? 'text-red-600' : 'text-orange-600'"
                class="text-sm font-medium"
              >
                {{ data.tipo_movimiento === 'ENTRADA' ? '+' :
                   data.tipo_movimiento === 'SALIDA' ? '-' : '±' }}{{ data.cantidad }}
              </span>
            </template>
          </Column>

          <Column field="motivo" header="Motivo" sortable class="w-32 hidden md:table-cell">
            <template #body="{ data }">
              <div
                class="text-sm leading-relaxed overflow-hidden"
                style="max-width: 120px; text-overflow: ellipsis; white-space: nowrap;"
                :title="obtenerMotivoLegible(data.motivo)"
              >
                {{ obtenerMotivoLegible(data.motivo) }}
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


        </DataTable>
      </div>
    </div>

    <!-- Modal Detalle -->

    <!-- Modal Detalle -->
    <Dialog
      v-model:visible="mostrarModalDetalle"
      modal
      header="Detalle del Movimiento"
      :style="dialogStyle"
      :closable="false"
      :draggable="false"
    >
      <div v-if="movimientoSeleccionado" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <strong>Fecha:</strong> {{ formatearFecha(movimientoSeleccionado.fecha_movimiento) }}
          </div>
          <div>
            <strong>Tipo:</strong>
            <Tag
              :value="obtenerTipoMovimiento(movimientoSeleccionado)"
              :severity="obtenerSeveridadTipo(movimientoSeleccionado)"
            >
              {{ obtenerTipoMovimiento(movimientoSeleccionado) }}
            </Tag>
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
          <div class="col-span-2">
            <strong>Usuario:</strong> {{ movimientoSeleccionado.user?.name || 'Sistema' }}
            <div v-if="movimientoSeleccionado.user" class="text-sm text-gray-600 mt-1">
              <div><strong>Email:</strong> {{ movimientoSeleccionado.user.email }}</div>
              <div v-if="movimientoSeleccionado.user.roles && movimientoSeleccionado.user.roles.length > 0">
                <strong>Rol:</strong> {{ movimientoSeleccionado.user.roles[0].name }}
              </div>
            </div>
          </div>
          <div>
            <strong>Motivo:</strong> {{ obtenerMotivoLegible(movimientoSeleccionado.motivo) }}
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

    <!-- Modal de Productos por Estado -->
    <Dialog
      v-model:visible="mostrarModalProductos"
      modal
      :header="tituloModal"
      :style="dialogStyle"
      :closable="false"
      :draggable="false"
    >
      <div v-if="cargandoProductos" class="text-center py-8">
        <FontAwesomeIcon :icon="faSpinner" class="animate-spin h-8 w-8 text-blue-600 mb-3" />
        <p class="text-gray-600">Cargando productos...</p>
      </div>

      <div v-else-if="productosModalData.length === 0" class="text-center py-8">
        <FontAwesomeIcon :icon="faExclamationTriangle" class="h-12 w-12 text-gray-400 mb-3" />
        <p class="text-gray-600">No se encontraron productos en este estado.</p>
      </div>

      <div v-else class="space-y-4">
        <div class="max-h-96 overflow-y-auto">
          <div v-for="producto in productosModalData" :key="producto.id" class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
            <div class="flex-1">
              <h4 class="font-semibold text-gray-800">{{ producto.nombre }}</h4>
              <p class="text-sm text-gray-500">{{ producto.categoria?.nombre || 'Sin categoría' }}</p>
              <div class="flex gap-4 text-xs text-gray-600 mt-1">
                <span>Stock: <strong>{{ producto.stock_actual || 0 }}</strong></span>
                <span>Mínimo: <strong>{{ producto.stock_minimo || 1 }}</strong></span>
                <span v-if="producto.precio">Precio: <strong>${{ formatearPrecio(producto.precio) }}</strong></span>
              </div>
            </div>
            <div class="ml-4">
              <span
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800': tipoEstado === 'disponibles',
                  'bg-red-100 text-red-800': tipoEstado === 'agotados',
                  'bg-yellow-100 text-yellow-800': tipoEstado === 'stock_bajo'
                }"
              >
                {{ tipoEstado === 'disponibles' ? 'Disponible' :
                   tipoEstado === 'agotados' ? 'Agotado' : 'Stock Bajo' }}
              </span>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <p class="text-sm text-gray-600 mb-3">
            <strong>{{ productosModalData.length }}</strong> producto{{ productosModalData.length !== 1 ? 's' : '' }} encontrado{{ productosModalData.length !== 1 ? 's' : '' }}
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <Link
            href="/productos"
            @click="mostrarModalProductos = false"
            class="bg-green-500 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
          >
            <FontAwesomeIcon :icon="faEye" class="h-5" />
            Gestionar Productos
          </Link>
          <button
            type="button"
            @click="mostrarModalProductos = false"
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
import { Head, Link } from '@inertiajs/vue3'
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
  faHandPointUp
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
const isClearingFilters = ref(false)
const isViewingDetail = ref(false)
const isViewingHistory = ref(false)

// Modales
const mostrarModalDetalle = ref(false)
const movimientoSeleccionado = ref(null)
const mostrarModalProductos = ref(false)
const productosModalData = ref([])
const tituloModal = ref('')
const tipoEstado = ref('')
const cargandoProductos = ref(false)

// Filtros
const filtros = ref({
  producto_id: "",
  tipo_movimiento: "",
  fecha_desde: null,
  fecha_hasta: null
})

// Variables para manejo de datos
const errores = ref({})

// Opciones para dropdowns
const tiposMovimiento = [
  { label: 'Entrada', value: 'ENTRADA' },
  { label: 'Salida', value: 'SALIDA' },
  { label: 'Ajuste', value: 'AJUSTE' }
]

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

// Computed
const movimientosFiltrados = computed(() => {
  let resultado = movimientos.value

  // Filtro por producto
  if (filtros.value.producto_id && filtros.value.producto_id !== "") {
    resultado = resultado.filter(movimiento =>
      movimiento.producto_id == filtros.value.producto_id
    )
  }  // Filtro por tipo de movimiento
  if (filtros.value.tipo_movimiento && filtros.value.tipo_movimiento !== "") {
    resultado = resultado.filter(movimiento =>
      movimiento.tipo_movimiento === filtros.value.tipo_movimiento
    )
  }

  // Filtro por fecha desde
  if (filtros.value.fecha_desde) {
    resultado = resultado.filter(movimiento => {
      const fechaMovimiento = new Date(movimiento.fecha_movimiento)
      const fechaDesde = new Date(filtros.value.fecha_desde)
      return fechaMovimiento >= fechaDesde
    })
  }

  // Filtro por fecha hasta
  if (filtros.value.fecha_hasta) {
    resultado = resultado.filter(movimiento => {
      const fechaMovimiento = new Date(movimiento.fecha_movimiento)
      const fechaHasta = new Date(filtros.value.fecha_hasta)
      return fechaMovimiento <= fechaHasta
    })
  }

  // Filtro por búsqueda de texto
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
    // Filtrar solo los parámetros que tienen valores válidos
    const params = {}
    if (filtros.value.producto_id && filtros.value.producto_id !== "") {
      params.producto_id = filtros.value.producto_id
    }
    if (filtros.value.tipo_movimiento && filtros.value.tipo_movimiento !== "") {
      params.tipo_movimiento = filtros.value.tipo_movimiento
    }
    if (filtros.value.fecha_desde) {
      params.fecha_desde = filtros.value.fecha_desde
    }
    if (filtros.value.fecha_hasta) {
      params.fecha_hasta = filtros.value.fecha_hasta
    }

    const [movimientosRes, productosRes, resumenRes] = await Promise.all([
      axios.get('/api/inventario', { params }),
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
      producto_id: "",
      tipo_movimiento: "",
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

// Funciones para manejar clicks en las tarjetas
const abrirModalProductos = async (tipo) => {
  let cantidad = 0
  let endpoint = ''

  switch (tipo) {
    case 'disponibles':
      cantidad = resumen.value.productos_disponibles || 0
      endpoint = '/api/admin/productos/disponibles'
      tituloModal.value = 'Productos Disponibles'
      break
    case 'agotados':
      cantidad = resumen.value.productos_agotados || 0
      endpoint = '/api/inventario/agotados'
      tituloModal.value = 'Productos Agotados'
      break
    case 'stock_bajo':
      cantidad = resumen.value.productos_stock_bajo || 0
      endpoint = '/api/inventario/stock-bajo'
      tituloModal.value = 'Productos con Stock Bajo'
      break
  }

  if (cantidad === 0) {
    toast.add({
      severity: 'info',
      summary: 'Sin registros',
      detail: `No hay productos en estado: ${tituloModal.value.toLowerCase()}`,
      life: 3000
    })
    return
  }

  tipoEstado.value = tipo
  cargandoProductos.value = true

  try {
    const response = await axios.get(endpoint)
    productosModalData.value = response.data.data || response.data || []
    mostrarModalProductos.value = true
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar los productos',
      life: 4000
    })
  } finally {
    cargandoProductos.value = false
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

const obtenerMotivoLegible = (motivo) => {
  const motivosLegibles = {
    // Motivos actualmente utilizados en el sistema
    'venta': 'Venta',
    'ajuste_aumento': 'Ajuste (Aumento)',
    'ajuste_reduccion': 'Ajuste (Reducción)',
    'entrada_manual': 'Entrada Manual',
    'cancelacion_venta': 'Cancelación de Venta',
    'eliminacion_venta': 'Eliminación de Venta',
    // Fallback para valores que pueden venir en diferentes formatos
    '': 'Sin especificar',
    null: 'Sin especificar',
    undefined: 'Sin especificar'
  }

  // Si el motivo no está mapeado, intentamos mostrarlo tal como viene
  // pero capitalizado de manera amigable
  if (!motivosLegibles[motivo] && motivo) {
    // Capitalizar primera letra y reemplazar guiones bajos con espacios
    return motivo.charAt(0).toUpperCase() + motivo.slice(1).replace(/_/g, ' ')
  }

  return motivosLegibles[motivo] || 'Sin especificar'
}

const obtenerTipoMovimiento = (movimiento) => {
  return movimiento.tipo_movimiento || 'Sin definir'
}

const obtenerSeveridadTipo = (movimiento) => {
  const tipo = movimiento.tipo_movimiento
  if (tipo === 'ENTRADA') return 'success'
  if (tipo === 'SALIDA') return 'danger'
  if (tipo === 'AJUSTE') return 'warning'
  return 'secondary'
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
