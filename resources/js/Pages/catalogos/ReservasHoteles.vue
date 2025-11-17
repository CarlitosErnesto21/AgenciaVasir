<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { FilterMatchMode } from "@primevue/core/api"
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faCheck, faXmark, faCalendarDays, faClockRotateLeft,
  faUsers, faHotel, faBed, faCalendarCheck, faInfoCircle,
  faSpinner, faRefresh, faFilter, faDollarSign, faListDots,
  faBars, faTimesCircle
} from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Toast from 'primevue/toast'

// Configuración de Toast
const toast = useToast()

// Estado reactivo
const reservasHoteles = ref([])
const loading = ref(false)
const filtros = ref({
  busqueda: '',
  fechaDesde: null,
  fechaHasta: null,
  estadoReserva: ''
})

// Estados para modales
const modalDetalles = ref(false)
const reservaSeleccionada = ref(null)

// Variables adicionales para UI
const isReloading = ref(false)
const isClearingFilters = ref(false)

// Variables para paginación
const rowsPerPage = ref(10)
const rowsPerPageOptions = ref([5, 10, 20, 50])

// Filtros de DataTable
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

// Estados disponibles para reservas de hoteles
const estadosReservas = [
  { label: 'Pendientes', value: 'PENDIENTE', severity: 'warn', color: 'bg-yellow-100 text-yellow-800', icon: faClockRotateLeft },
  { label: 'Confirmadas', value: 'CONFIRMADA', severity: 'success', color: 'bg-green-100 text-green-800', icon: faCheck },
  { label: 'Rechazadas', value: 'RECHAZADA', severity: 'danger', color: 'bg-red-100 text-red-800', icon: faXmark },
  { label: 'Reprogramadas', value: 'REPROGRAMADA', severity: 'info', color: 'bg-blue-100 text-blue-800', icon: faCalendarDays },
  { label: 'Finalizadas', value: 'FINALIZADA', severity: 'secondary', color: 'bg-gray-100 text-gray-800', icon: faCalendarCheck }
]

// Computed para estadísticas
const estadisticas = computed(() => {
  const stats = {
    pendientes: 0,
    confirmadas: 0,
    rechazadas: 0,
    reprogramadas: 0,
    finalizadas: 0,
    totalIngresos: 0
  }

  reservasHoteles.value.forEach(reserva => {
    switch (reserva.estado) {
      case 'PENDIENTE':
        stats.pendientes++
        break
      case 'CONFIRMADA':
        stats.confirmadas++
        stats.totalIngresos += parseFloat(reserva.total || 0)
        break
      case 'RECHAZADA':
        stats.rechazadas++
        break
      case 'REPROGRAMADA':
        stats.reprogramadas++
        break
      case 'FINALIZADA':
        stats.finalizadas++
        stats.totalIngresos += parseFloat(reserva.total || 0)
        break
    }
  })

  return stats
})

// Computed para filtrar reservas
const reservasFiltradas = computed(() => {
  let filtered = reservasHoteles.value

  // Aplicar filtro global del DataTable
  if (filters.value.global.value) {
    const searchTerm = filters.value.global.value.toLowerCase()
    filtered = filtered.filter(reserva =>
      (reserva.cliente?.nombres || '').toLowerCase().includes(searchTerm) ||
      (reserva.hotel?.nombre || '').toLowerCase().includes(searchTerm) ||
      (reserva.cliente?.correo || '').toLowerCase().includes(searchTerm) ||
      (reserva.id?.toString() || '').includes(searchTerm)
    )
  }

  return filtered
})

// Función para cargar reservas de hoteles
const cargarReservasHoteles = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/reservas-hoteles')
    reservasHoteles.value = response.data.data || response.data.reservas || response.data || []
  } catch (error) {
    console.error('Error al cargar reservas de hoteles:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las reservas de hoteles',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

// Función para recargar datos
const recargarDatos = async () => {
  isReloading.value = true
  await cargarReservasHoteles()
  isReloading.value = false

  toast.add({
    severity: 'success',
    summary: 'Datos actualizados',
    detail: 'Las reservas de hoteles han sido recargadas',
    life: 3000
  })
}

// Función para mostrar detalles
const mostrarDetalles = (reserva) => {
  reservaSeleccionada.value = reserva
  modalDetalles.value = true
}

// Función para formatear fechas
const formatearFecha = (fecha) => {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Función para obtener días de estadía
const obtenerDiasEstadia = (fechaEntrada, fechaSalida) => {
  if (!fechaEntrada || !fechaSalida) return 0
  const entrada = new Date(fechaEntrada)
  const salida = new Date(fechaSalida)
  const diferencia = salida.getTime() - entrada.getTime()
  return Math.ceil(diferencia / (1000 * 3600 * 24))
}

// Función para limpiar filtros
const clearFilters = () => {
  isClearingFilters.value = true
  filters.value.global.value = null

  setTimeout(() => {
    isClearingFilters.value = false
    toast.add({
      severity: 'success',
      summary: 'Filtro limpiado',
      detail: 'Se ha limpiado el filtro de búsqueda',
      life: 3000
    })
  }, 500)
}

// Función para obtener severity del estado
const getSeverity = (estado) => {
  const estadoObj = estadosReservas.find(e => e.value === estado)
  return estadoObj?.severity || 'secondary'
}

// Función para obtener color del estado
const getEstadoColor = (estado) => {
  const estadoObj = estadosReservas.find(e => e.value === estado)
  return estadoObj?.color || 'bg-gray-100 text-gray-800'
}

// Función para manejar clic en fila
const onRowClick = (event) => {
  mostrarDetalles(event.data)
}

// Funciones del ciclo de vida
onMounted(() => {
  cargarReservasHoteles()
})
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Reservas de Hoteles" />
    <Toast class="z-[9999]" />

    <div class="container mx-auto px-4 py-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Reservas de Hoteles</h1>
        <p class="text-gray-600">Administra todas las reservas de hoteles del sistema</p>
      </div>



      <!-- Sección de contenido principal -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
          <div>
            <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Reservas de Hoteles</h3>
            <p class="text-blue-600 text-xs text-center sm:text-start mt-1 font-medium">
              👆 Haz clic en cualquier fila para ver los detalles de la reserva
            </p>
          </div>
          <div class="flex items-center gap-2 w-full justify-center lg:w-auto lg:justify-end">
            <button
              @click="recargarDatos"
              :disabled="isReloading"
              class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300 flex items-center gap-2"
            >
              <FontAwesomeIcon :icon="isReloading ? faSpinner : faRefresh" :class="{ 'animate-spin': isReloading, 'h-4': true }" />
              <span class="block sm:hidden">{{ isReloading ? 'Cargando...' : 'Recargar' }}</span>
              <span class="hidden sm:block">{{ isReloading ? 'Recargando...' : 'Recargar datos' }}</span>
            </button>
          </div>
        </div>

        <!-- DataTable con filtros integrados -->
        <DataTable
          :value="reservasFiltradas"
          dataKey="id"
          :paginator="true"
          :rows="rowsPerPage"
          :rowsPerPageOptions="rowsPerPageOptions"
          v-model:rowsPerPage="rowsPerPage"
          paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reservas"
          class="overflow-x-auto max-w-full cursor-pointer"
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
                    <FontAwesomeIcon :icon="faFilter" class="text-blue-600 text-sm" />
                    <span>Filtros</span>
                  </h3>
                  <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                    {{ reservasFiltradas.length }} resultado{{ reservasFiltradas.length !== 1 ? 's' : '' }}
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
                    <span>{{ isClearingFilters ? 'Limp...' : 'Limpiar' }}</span>
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
                  <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar búsqueda' }}</span>
                </button>
              </div>
              <div class="space-y-3">
                <div>
                  <InputText v-model="filters['global'].value" placeholder="🔍 Buscar reservas..." class="w-full h-9 text-sm rounded-md" style="background-color: white; border-color: #93c5fd;"/>
                </div>
              </div>
            </div>
          </template>

          <Column field="cliente.nombres" header="Cliente" sortable class="w-52">
            <template #body="slotProps">
              <div class="flex flex-col">
                <span class="font-medium text-gray-900 text-sm">
                  {{ slotProps.data.cliente?.nombres || 'N/A' }}
                </span>
                <span class="text-xs text-gray-500 hidden sm:block">
                  {{ slotProps.data.cliente?.correo || 'N/A' }}
                </span>
              </div>
            </template>
          </Column>

          <Column
            field="hotel.nombre"
            header="Hotel"
            sortable
            headerClass="w-44 hidden-sm"
            bodyClass="hidden-sm"
          >
            <template #body="slotProps">
              <div class="flex flex-col">
                <span class="font-medium text-gray-900 text-sm">
                  {{ slotProps.data.hotel?.nombre || 'N/A' }}
                </span>
                <span class="text-xs text-gray-500">
                  {{ slotProps.data.hotel?.direccion || 'N/A' }}
                </span>
              </div>
            </template>
          </Column>

          <Column
            header="Fechas"
            headerClass="w-36 hidden-md"
            bodyClass="hidden-md"
          >
            <template #body="slotProps">
              <div class="flex flex-col text-xs">
                <span class="text-green-600 font-medium">
                  <FontAwesomeIcon :icon="faCalendarDays" class="mr-1" />
                  {{ formatearFecha(slotProps.data.fecha_entrada) }}
                </span>
                <span class="text-red-600 font-medium">
                  <FontAwesomeIcon :icon="faCalendarDays" class="mr-1" />
                  {{ formatearFecha(slotProps.data.fecha_salida) }}
                </span>
                <span class="text-xs text-blue-600 font-bold">
                  {{ obtenerDiasEstadia(slotProps.data.fecha_entrada, slotProps.data.fecha_salida) }} días
                </span>
              </div>
            </template>
          </Column>

          <Column
            header="Detalles"
            headerClass="w-32 hidden-lg"
            bodyClass="hidden-lg"
          >
            <template #body="slotProps">
              <div class="flex flex-col text-xs">
                <span class="text-blue-600">
                  <FontAwesomeIcon :icon="faUsers" class="mr-1" />
                  {{ slotProps.data.detalles?.cantidad_personas || slotProps.data.mayores_edad }} persona{{ (slotProps.data.detalles?.cantidad_personas || slotProps.data.mayores_edad) !== 1 ? 's' : '' }}
                </span>
                <span class="text-green-600">
                  <FontAwesomeIcon :icon="faBed" class="mr-1" />
                  {{ slotProps.data.detalles?.cantidad_habitaciones || 'N/A' }} hab.
                </span>
              </div>
            </template>
          </Column>

          <Column field="estado" header="Estado" sortable class="w-32">
            <template #body="slotProps">
              <span :class="[
                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                getEstadoColor(slotProps.data.estado)
              ]">
                {{ slotProps.data.estado }}
              </span>
            </template>
          </Column>

          <Column
            header="Total"
            headerClass="w-24 hidden-sm"
            bodyClass="hidden-sm"
          >
            <template #body="slotProps">
              <span class="font-bold text-green-600">
                ${{ parseFloat(slotProps.data.total || 0).toFixed(2) }}
              </span>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>    <!-- Modal de Detalles -->
    <div v-if="modalDetalles" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click="modalDetalles = false">
      <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <!-- Header del Modal -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl">
          <div class="flex items-center justify-between text-white">
            <h3 class="text-xl font-bold flex items-center gap-3">
              <FontAwesomeIcon :icon="faHotel" class="text-2xl" />
              Detalles de Reserva de Hotel
            </h3>
            <button
              @click="modalDetalles = false"
              class="hover:bg-white hover:bg-opacity-20 p-2 rounded-full transition-all"
            >
              <FontAwesomeIcon :icon="faTimesCircle" class="text-xl" />
            </button>
          </div>
        </div>

        <!-- Contenido del Modal -->
        <div v-if="reservaSeleccionada" class="p-6">
          <!-- Información Principal -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Información del Cliente -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center gap-2">
                <FontAwesomeIcon :icon="faUsers" class="text-blue-600" />
                Información del Cliente
              </h4>
              <div class="space-y-2 text-sm">
                <div>
                  <span class="font-medium text-gray-600">Nombre:</span>
                  <span class="ml-2 text-gray-900">{{ reservaSeleccionada.cliente?.nombres || 'N/A' }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-600">Email:</span>
                  <span class="ml-2 text-gray-900">{{ reservaSeleccionada.cliente?.correo || 'N/A' }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-600">Teléfono:</span>
                  <span class="ml-2 text-gray-900">{{ reservaSeleccionada.cliente?.telefono || 'N/A' }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-600">Identificación:</span>
                  <span class="ml-2 text-gray-900">{{ reservaSeleccionada.cliente?.numero_identificacion || 'N/A' }}</span>
                </div>
              </div>
            </div>

            <!-- Información del Hotel -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center gap-2">
                <FontAwesomeIcon :icon="faHotel" class="text-green-600" />
                Información del Hotel
              </h4>
              <div class="space-y-2 text-sm">
                <div>
                  <span class="font-medium text-gray-600">Hotel:</span>
                  <span class="ml-2 text-gray-900">{{ reservaSeleccionada.hotel?.nombre || 'N/A' }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-600">Dirección:</span>
                  <span class="ml-2 text-gray-900">{{ reservaSeleccionada.hotel?.direccion || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Detalles de la Reserva -->
          <div class="bg-blue-50 p-4 rounded-lg mb-6">
            <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center gap-2">
              <FontAwesomeIcon :icon="faCalendarDays" class="text-purple-600" />
              Detalles de la Reserva
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
              <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                <FontAwesomeIcon :icon="faCalendarDays" class="text-green-600 text-2xl mb-2" />
                <p class="font-medium text-gray-600">Fecha Entrada</p>
                <p class="text-lg font-bold text-gray-900">{{ formatearFecha(reservaSeleccionada.fecha_entrada) }}</p>
              </div>
              <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                <FontAwesomeIcon :icon="faCalendarDays" class="text-red-600 text-2xl mb-2" />
                <p class="font-medium text-gray-600">Fecha Salida</p>
                <p class="text-lg font-bold text-gray-900">{{ formatearFecha(reservaSeleccionada.fecha_salida) }}</p>
              </div>
              <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                <FontAwesomeIcon :icon="faUsers" class="text-blue-600 text-2xl mb-2" />
                <p class="font-medium text-gray-600">Personas</p>
                <p class="text-lg font-bold text-gray-900">{{ reservaSeleccionada.detalles?.cantidad_personas || reservaSeleccionada.mayores_edad || 'N/A' }}</p>
              </div>
              <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                <FontAwesomeIcon :icon="faBed" class="text-purple-600 text-2xl mb-2" />
                <p class="font-medium text-gray-600">Habitaciones</p>
                <p class="text-lg font-bold text-gray-900">{{ reservaSeleccionada.detalles?.cantidad_habitaciones || 'N/A' }}</p>
              </div>
            </div>
          </div>

          <!-- Información Adicional -->
          <div class="bg-yellow-50 p-4 rounded-lg">
            <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center gap-2">
              <FontAwesomeIcon :icon="faInfoCircle" class="text-yellow-600" />
              Información Adicional
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-600">Días de estadía:</span>
                <span class="ml-2 text-gray-900 font-bold">
                  {{ obtenerDiasEstadia(reservaSeleccionada.fecha_entrada, reservaSeleccionada.fecha_salida) }} días
                </span>
              </div>
              <div>
                <span class="font-medium text-gray-600">ID de Reserva:</span>
                <span class="ml-2 text-red-600 font-bold">#{{ reservaSeleccionada.id }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer del Modal -->
        <div class="bg-gray-50 px-6 py-4 rounded-b-xl flex justify-end">
          <button
            @click="modalDetalles = false"
            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 font-medium"
          >
            <FontAwesomeIcon :icon="faCheck" />
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
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

/* Responsive columns - hide on mobile using PrimeVue DataTable structure */
@media (max-width: 639px) {
  :deep(.p-datatable-tbody > tr > .hidden-sm),
  :deep(.p-datatable-thead > tr > .hidden-sm) {
    display: none !important;
  }
}

@media (max-width: 767px) {
  :deep(.p-datatable-tbody > tr > .hidden-md),
  :deep(.p-datatable-thead > tr > .hidden-md) {
    display: none !important;
  }
}

@media (max-width: 1023px) {
  :deep(.p-datatable-tbody > tr > .hidden-lg),
  :deep(.p-datatable-thead > tr > .hidden-lg) {
    display: none !important;
  }
}

/* Estilos para select nativo con placeholder */
select:invalid {
    color: #9ca3af !important;
}

select option {
    color: #111827 !important;
}

select option:disabled {
    color: #9ca3af !important;
}

/* Mejorar apariencia general del select nativo */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
    background-position: right 0.5rem center !important;
    background-repeat: no-repeat !important;
    background-size: 1.5em 1.5em !important;
    padding-right: 2.5rem !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
}

/* Estilos para filas clickeables */
:deep(.p-datatable-tbody > tr) {
  cursor: pointer !important;
  transition: background-color 0.2s ease;
}

:deep(.p-datatable-tbody > tr:hover) {
  background-color: #f8fafc !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

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
</style>
