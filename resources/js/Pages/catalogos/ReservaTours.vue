<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, onMounted, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faCheck, faXmark, faCalendarDays, faClockRotateLeft,
  faEye, faFilter, faUsers, faDollarSign,
  faCalendarCheck, faExclamationTriangle, faInfoCircle
} from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'
axios.defaults.withCredentials = true

// Configuración de Toast
const toast = useToast()

// Estado reactivo
const reservas = ref([])
const tours = ref([])
const loading = ref(false)
const loadingTours = ref(false)
const filtros = ref({
  tipo: 'tours',
  busqueda: '',
  fechaDesde: null,
  fechaHasta: null,
  estadoReserva: null,
  estadoTour: null
})

// Estados para modales
const modalReprogramar = ref(false)
const modalRechazar = ref(false)
const modalDetalles = ref(false)
const modalCambiarEstadoTour = ref(false)
const reservaSeleccionada = ref(null)
const tourSeleccionado = ref(null)
const motivoRechazo = ref('')
const fechaNuevaReprogramacion = ref(null)
const motivoReprogramacion = ref('')
const observacionesReprogramacion = ref('')

// Pestaña activa
const pestanaActiva = ref(0)

// Estados disponibles para reservas
const estadosReservas = [
  { label: 'Pendientes', value: 'PENDIENTE', severity: 'warn', color: 'bg-yellow-100 text-yellow-800', icon: faClockRotateLeft },
  { label: 'Confirmadas', value: 'CONFIRMADA', severity: 'success', color: 'bg-green-100 text-green-800', icon: faCheck },
  { label: 'Rechazadas', value: 'RECHAZADA', severity: 'danger', color: 'bg-red-100 text-red-800', icon: faXmark },
  { label: 'Reprogramadas', value: 'REPROGRAMADA', severity: 'info', color: 'bg-blue-100 text-blue-800', icon: faCalendarDays },
  { label: 'Finalizadas', value: 'FINALIZADA', severity: 'secondary', color: 'bg-gray-100 text-gray-800', icon: faCalendarCheck }
]

// Estados disponibles para tours
const estadosTours = [
  { label: 'Disponible', value: 'DISPONIBLE', color: 'bg-green-100 text-green-800' },
  { label: 'Agotado', value: 'AGOTADO', color: 'bg-red-100 text-red-800' },
  { label: 'En Curso', value: 'EN_CURSO', color: 'bg-blue-100 text-blue-800' },
  { label: 'Completado', value: 'COMPLETADO', color: 'bg-gray-100 text-gray-800' },
  { label: 'Cancelado', value: 'CANCELADO', color: 'bg-red-100 text-red-800' },
  { label: 'Suspendido', value: 'SUSPENDIDO', color: 'bg-orange-100 text-orange-800' },
  { label: 'Reprogramado', value: 'REPROGRAMADO', color: 'bg-purple-100 text-purple-800' }
]

// Computed para filtrar reservas por estado
const reservasPorEstado = computed(() => {
  const estadoActual = estadosReservas[pestanaActiva.value]?.value
  let filtered = reservas.value.filter(reserva => reserva.estado === estadoActual)

  // Aplicar filtros adicionales
  if (filtros.value.busqueda) {
    const busqueda = filtros.value.busqueda.toLowerCase()
    filtered = filtered.filter(reserva =>
      (reserva.cliente?.user?.name || reserva.cliente?.nombres || '').toLowerCase().includes(busqueda) ||
      (reserva.entidad_nombre || '').toLowerCase().includes(busqueda) ||
      (reserva.cliente?.user?.email || reserva.cliente?.correo || '').toLowerCase().includes(busqueda)
    )
  }

  if (filtros.value.fechaDesde) {
    filtered = filtered.filter(reserva =>
      new Date(reserva.fecha_reserva) >= new Date(filtros.value.fechaDesde)
    )
  }

  if (filtros.value.fechaHasta) {
    filtered = filtered.filter(reserva =>
      new Date(reserva.fecha_reserva) <= new Date(filtros.value.fechaHasta)
    )
  }

  return filtered
})

// Computed para estadísticas
const estadisticas = computed(() => {
  return {
    pendientes: reservas.value.filter(r => r.estado === 'PENDIENTE').length,
    confirmadas: reservas.value.filter(r => r.estado === 'CONFIRMADA').length,
    reprogramadas: reservas.value.filter(r => r.estado === 'REPROGRAMADA').length,
    rechazadas: reservas.value.filter(r => r.estado === 'RECHAZADA').length,
    finalizadas: reservas.value.filter(r => r.estado === 'FINALIZADA').length,
    totalIngresos: reservas.value
      .filter(r => ['CONFIRMADA', 'FINALIZADA'].includes(r.estado))
      .reduce((sum, r) => sum + (Number(r.total) || 0), 0)
  }
})

// Función para cargar todas las reservas
const cargarReservas = async () => {
  loading.value = true
  try {
    const params = {
      tipo: filtros.value.tipo,
      busqueda: filtros.value.busqueda || undefined,
      fecha_inicio: filtros.value.fechaDesde || undefined,
      fecha_fin: filtros.value.fechaHasta || undefined
    }

    const response = await axios.get('/api/reservas', { params, withCredentials: true })
    reservas.value = response.data.data || []

  } catch (error) {
    console.error('Error cargando reservas:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las reservas',
      life: 4000
    })
    reservas.value = []
  } finally {
    loading.value = false
  }
}

// Función para cargar tours
const cargarTours = async () => {
  loadingTours.value = true
  try {
    const response = await axios.get('/api/tours')
    tours.value = response.data || []
  } catch (error) {
    console.error('Error cargando tours:', error)
    tours.value = []
  } finally {
    loadingTours.value = false
  }
}

// Función para confirmar reserva
const confirmarReserva = async (reserva) => {
  try {
    const response = await axios.put(`/api/reservas/${reserva.id}/confirmar`)

    // Actualizar estado local
    const index = reservas.value.findIndex(r => r.id === reserva.id)
    if (index !== -1) {
      reservas.value[index].estado = 'CONFIRMADA'
    }

    toast.add({
      severity: 'success',
      summary: '¡Confirmada!',
      detail: `Reserva de ${reserva.cliente?.user?.name || reserva.cliente?.nombres} confirmada correctamente`,
      life: 4000
    })

  } catch (error) {
    console.error('Error confirmando reserva:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo confirmar la reserva',
      life: 4000
    })
  }
}

// Función para abrir modal de rechazo
const abrirModalRechazar = (reserva) => {
  reservaSeleccionada.value = reserva
  motivoRechazo.value = ''
  modalRechazar.value = true
}

// Función para rechazar reserva
const rechazarReserva = async () => {
  if (!motivoRechazo.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Campo requerido',
      detail: 'Debe ingresar un motivo para rechazar la reserva',
      life: 4000
    })
    return
  }

  try {
    await axios.put(`/api/reservas/${reservaSeleccionada.value.id}/rechazar`, {
      motivo: motivoRechazo.value
    })

    // Actualizar estado local
    const index = reservas.value.findIndex(r => r.id === reservaSeleccionada.value.id)
    if (index !== -1) {
      reservas.value[index].estado = 'RECHAZADA'
    }

    modalRechazar.value = false
    toast.add({
      severity: 'success',
      summary: '¡Rechazada!',
      detail: `Reserva de ${reservaSeleccionada.value.cliente?.user?.name || reservaSeleccionada.value.cliente?.nombres} rechazada correctamente`,
      life: 4000
    })

  } catch (error) {
    console.error('Error rechazando reserva:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo rechazar la reserva',
      life: 4000
    })
  }
}

// Función para abrir modal de reprogramación
const abrirModalReprogramar = (reserva) => {
  reservaSeleccionada.value = reserva
  fechaNuevaReprogramacion.value = null
  motivoReprogramacion.value = ''
  observacionesReprogramacion.value = ''

  // Cargar el tour relacionado para poder reprogramarlo también
  const tour = tours.value.find(t =>
    reserva.detallesTours?.some(dt => dt.tour_id === t.id) ||
    reserva.entidad_nombre === t.nombre
  )
  tourSeleccionado.value = tour

  modalReprogramar.value = true
}

// Función para reprogramar reserva
const reprogramarReserva = async () => {
  if (!fechaNuevaReprogramacion.value || !motivoReprogramacion.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Campos requeridos',
      detail: 'Debe ingresar una nueva fecha y motivo para reprogramar',
      life: 4000
    })
    return
  }

  try {
    const reprogramacionData = {
      fecha_nueva: fechaNuevaReprogramacion.value,
      motivo: motivoReprogramacion.value,
      observaciones: observacionesReprogramacion.value
    }

    await axios.put(`/api/reservas/${reservaSeleccionada.value.id}/reprogramar`, reprogramacionData)

    // Si hay un tour asociado, también reprogramarlo
    if (tourSeleccionado.value) {
      try {
        // Calcular nueva fecha de regreso (agregar la duración original del tour)
        const fechaOriginalSalida = new Date(tourSeleccionado.value.fecha_salida)
        const fechaOriginalRegreso = new Date(tourSeleccionado.value.fecha_regreso)
        const duracionTour = fechaOriginalRegreso.getTime() - fechaOriginalSalida.getTime()

        const nuevaFechaRegreso = new Date(new Date(fechaNuevaReprogramacion.value).getTime() + duracionTour)

        const tourData = {
          estado: 'REPROGRAMADO',
          fecha_salida: fechaNuevaReprogramacion.value,
          fecha_regreso: nuevaFechaRegreso,
          motivo_reprogramacion: `Reprogramado por reserva: ${motivoReprogramacion.value}`,
          observaciones: observacionesReprogramacion.value
        }

        await axios.put(`/api/tours/${tourSeleccionado.value.id}/cambiar-estado`, tourData)

        toast.add({
          severity: 'info',
          summary: 'Tour actualizado',
          detail: `El tour "${tourSeleccionado.value.nombre}" también ha sido reprogramado`,
          life: 4000
        })
      } catch (tourError) {
        console.error('Error reprogramando tour:', tourError)
        toast.add({
          severity: 'warn',
          summary: 'Advertencia',
          detail: 'La reserva fue reprogramada pero hubo un error al actualizar el tour',
          life: 4000
        })
      }
    }

    // Actualizar estado local de la reserva
    const index = reservas.value.findIndex(r => r.id === reservaSeleccionada.value.id)
    if (index !== -1) {
      reservas.value[index].estado = 'REPROGRAMADA'
      reservas.value[index].fecha_reserva = fechaNuevaReprogramacion.value
    }

    modalReprogramar.value = false

    // Recargar datos para mostrar cambios
    await Promise.all([cargarReservas(), cargarTours()])

    toast.add({
      severity: 'success',
      summary: '¡Reprogramada!',
      detail: `Reserva de ${reservaSeleccionada.value.cliente?.user?.name || reservaSeleccionada.value.cliente?.nombres} reprogramada correctamente`,
      life: 4000
    })

  } catch (error) {
    console.error('Error reprogramando reserva:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo reprogramar la reserva',
      life: 4000
    })
  }
}

// Función para finalizar reserva
const finalizarReserva = async (reserva) => {
  try {
    await axios.put(`/api/reservas/${reserva.id}/finalizar`)

    // Actualizar estado local
    const index = reservas.value.findIndex(r => r.id === reserva.id)
    if (index !== -1) {
      reservas.value[index].estado = 'FINALIZADA'
    }

    toast.add({
      severity: 'success',
      summary: '¡Finalizada!',
      detail: `Reserva de ${reserva.cliente?.user?.name || reserva.cliente?.nombres} finalizada correctamente. Email de confirmación enviado.`,
      life: 4000
    })

  } catch (error) {
    console.error('Error finalizando reserva:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo finalizar la reserva',
      life: 4000
    })
  }
}

// Función para ver detalles de reserva
const verDetallesReserva = (reserva) => {
  reservaSeleccionada.value = reserva
  modalDetalles.value = true
}

// Función para obtener acciones disponibles según el estado
const getAccionesDisponibles = (reserva) => {
  switch (reserva.estado) {
    case 'PENDIENTE':
      return ['confirmar', 'rechazar', 'reprogramar', 'detalles']
    case 'CONFIRMADA':
      return ['rechazar', 'reprogramar', 'finalizar', 'detalles']
    case 'REPROGRAMADA':
      return ['rechazar', 'finalizar', 'detalles']
    case 'RECHAZADA':
      return ['detalles']
    case 'FINALIZADA':
      return ['detalles']
    default:
      return ['detalles']
  }
}

// Función para obtener color del estado de reserva
const getColorEstadoReserva = (estado) => {
  const estadoObj = estadosReservas.find(e => e.value === estado)
  return estadoObj?.color || 'bg-gray-100 text-gray-800'
}

// Función para obtener color del estado de tour
const getColorEstadoTour = (estado) => {
  const estadoObj = estadosTours.find(e => e.value === estado)
  return estadoObj?.color || 'bg-gray-100 text-gray-800'
}

// Función para formatear fecha
const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A'
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Función para formatear fecha con hora
const formatearFechaHora = (fecha) => {
  if (!fecha) return 'N/A'
  return new Date(fecha).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  filtros.value = {
    tipo: 'tours',
    busqueda: '',
    fechaDesde: null,
    fechaHasta: null,
    estadoReserva: null,
    estadoTour: null
  }
  cargarReservas()
}

// Función para obtener fecha mínima (hoy)
const getMinDate = () => {
  return new Date()
}

// Función para ejecutar finalización automática
const ejecutarFinalizacionAutomatica = async () => {
  try {
    loading.value = true

    const response = await axios.post('/api/reservas/finalizar-automaticamente')
    const data = response.data

    if (data.success) {
      toast.add({
        severity: 'success',
        summary: '¡Finalización automática completada!',
        detail: `${data.reservas_finalizadas} reserva(s) finalizada(s) de ${data.reservas_procesadas} procesada(s)`,
        life: 6000
      })

      // Recargar las reservas para mostrar los cambios
      await cargarReservas()
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Sin cambios',
        detail: data.message || 'No hay reservas pendientes de finalización',
        life: 4000
      })
    }
  } catch (error) {
    console.error('Error en finalización automática:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al ejecutar la finalización automática',
      life: 4000
    })
  } finally {
    loading.value = false
  }
}

// Watch para recargar cuando cambien algunos filtros
watch(() => [filtros.value.busqueda, filtros.value.fechaDesde, filtros.value.fechaHasta], () => {
  // Debounce para la búsqueda
  clearTimeout(window.searchTimeout)
  window.searchTimeout = setTimeout(() => {
    cargarReservas()
  }, 500)
}, { deep: true })

// Cargar datos iniciales
onMounted(() => {
  Promise.all([cargarReservas(), cargarTours()])
})
</script>


<template>
  <Head title="Gestión de Reservas" />

  <AuthenticatedLayout>
    <Toast class="z-[9999]" />

    <div class="px-2 sm:px-4 lg:px-6 xl:px-8 mt-4 sm:mt-6">
      <!-- Encabezado con estadísticas -->
      <div class="flex flex-col xl:flex-row xl:justify-between xl:items-start mb-4 sm:mb-6 gap-4 sm:gap-6">
        <div class="flex-1">
          <h3 class="text-xl sm:text-2xl lg:text-3xl text-blue-600 font-bold">Gestión de Reservas</h3>
          <p class="text-sm sm:text-base text-gray-600 mt-1">Administra todas las reservas de tours y hoteles</p>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 gap-2 sm:gap-3 lg:gap-4 text-center w-full xl:w-auto">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 sm:p-3 min-w-0">
            <div class="text-base sm:text-lg font-bold text-yellow-600 truncate">{{ estadisticas.pendientes }}</div>
            <div class="text-xs text-yellow-600">Pendientes</div>
          </div>
          <div class="bg-green-50 border border-green-200 rounded-lg p-2 sm:p-3 min-w-0">
            <div class="text-base sm:text-lg font-bold text-green-600 truncate">{{ estadisticas.confirmadas }}</div>
            <div class="text-xs text-green-600">Confirmadas</div>
          </div>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 sm:p-3 min-w-0">
            <div class="text-base sm:text-lg font-bold text-blue-600 truncate">{{ estadisticas.reprogramadas }}</div>
            <div class="text-xs text-blue-600">Reprogramadas</div>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-lg p-2 sm:p-3 min-w-0">
            <div class="text-base sm:text-lg font-bold text-red-600 truncate">{{ estadisticas.rechazadas }}</div>
            <div class="text-xs text-red-600">Rechazadas</div>
          </div>
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-2 sm:p-3 min-w-0 col-span-2 sm:col-span-1">
            <div class="text-base sm:text-lg font-bold text-green-600 truncate">${{ estadisticas.totalIngresos.toFixed(2) }}</div>
            <div class="text-xs text-gray-600">Ingresos</div>
          </div>
        </div>
      </div>

      <!-- Filtros mejorados -->
      <div class="bg-blue-50 p-3 sm:p-4 lg:p-6 rounded-lg shadow-sm border mb-4 sm:mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-3 sm:mb-4 gap-3 sm:gap-4">
          <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <h3 class="text-sm sm:text-base font-medium text-gray-800 flex items-center gap-2">
              <FontAwesomeIcon :icon="faFilter" class="text-blue-600 text-sm" />
              <span>Filtros</span>
            </h3>
            <div class="bg-blue-100 border border-blue-200 text-blue-700 px-2 sm:px-3 py-1 rounded text-xs sm:text-sm font-medium w-fit">
              {{ reservas.length }} reserva{{ reservas.length !== 1 ? 's' : '' }} total{{ reservas.length !== 1 ? 'es' : '' }}
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <button
              class="bg-red-500 hover:bg-red-600 text-white px-3 sm:px-4 py-2 rounded-md text-xs sm:text-sm transition-colors duration-200 flex items-center gap-2 justify-center w-full sm:w-auto"
              @click="limpiarFiltros"
            >
              <FontAwesomeIcon :icon="faXmark" class="h-3 sm:h-4" />
              <span class="hidden xs:inline">Limpiar filtros</span>
              <span class="xs:hidden">Limpiar</span>
            </button>

            <button
              class="bg-purple-500 hover:bg-purple-600 text-white px-3 sm:px-4 py-2 rounded-md text-xs sm:text-sm transition-colors duration-200 flex items-center gap-2 justify-center w-full sm:w-auto"
              @click="ejecutarFinalizacionAutomatica"
              :disabled="loading"
              title="Finaliza automáticamente las reservas cuyas fechas de tour han pasado"
            >
              <FontAwesomeIcon :icon="faCalendarCheck" class="h-3 sm:h-4" />
              <span v-if="!loading" class="hidden sm:inline">Finalización Automática</span>
              <span v-if="!loading" class="sm:hidden">Auto Finalizar</span>
              <span v-else class="hidden sm:inline">Procesando...</span>
              <span v-else class="sm:hidden">...</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <!-- Filtro por tipo -->
          <div class="w-full">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Tipo de Reserva</label>
            <Select
              v-model="filtros.tipo"
              :options="[
                { label: 'Tours', value: 'tours' },
                { label: 'Hoteles', value: 'hoteles' }
              ]"
              optionLabel="label"
              optionValue="value"
              class="w-full h-8 sm:h-9"
              style="background-color: white; border-color: #93c5fd;"
            />
          </div>

          <!-- Búsqueda -->
          <div class="w-full">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <InputText
              v-model="filtros.busqueda"
              placeholder="🔍 Cliente, servicio..."
              class="w-full h-8 sm:h-9 text-xs sm:text-sm"
              style="background-color: white; border-color: #93c5fd;"
            />
          </div>

          <!-- Fecha desde -->
          <div class="w-full">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Fecha desde</label>
            <DatePicker
              v-model="filtros.fechaDesde"
              dateFormat="dd/mm/yy"
              class="w-full h-8 sm:h-9"
              showIcon
              placeholder="Fecha inicio"
              style="background-color: white; border-color: #93c5fd;"
            />
          </div>

          <!-- Fecha hasta -->
          <div class="w-full">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Fecha hasta</label>
            <DatePicker
              v-model="filtros.fechaHasta"
              dateFormat="dd/mm/yy"
              class="w-full h-8 sm:h-9"
              showIcon
              placeholder="Fecha fin"
              style="background-color: white; border-color: #93c5fd;"
            />
          </div>
        </div>
      </div>

      <!-- Pestañas por estado con iconos -->
      <Tabs v-model:value="pestanaActiva" class="bg-white rounded-lg shadow overflow-hidden">
        <TabList class="flex flex-wrap justify-start overflow-x-auto scrollbar-thin">
          <Tab
            v-for="(estado, index) in estadosReservas"
            :key="estado.value"
            :value="index"
            class="flex items-center gap-1 sm:gap-2 px-2 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium whitespace-nowrap min-w-0"
          >
            <FontAwesomeIcon :icon="estado.icon" class="h-3 sm:h-4 flex-shrink-0" />
            <span class="hidden xs:inline truncate">{{ estado.label }}</span>
            <span class="xs:hidden truncate">{{ estado.label.substring(0, 4) }}</span>
            <span class="bg-gray-200 text-gray-700 px-1 sm:px-2 py-0.5 rounded-full text-xs flex-shrink-0">
              {{ reservas.filter(r => r.estado === estado.value).length }}
            </span>
          </Tab>
        </TabList>

        <TabPanels>
          <TabPanel
            v-for="(estado, index) in estadosReservas"
            :key="estado.value"
            :value="index"
          >
            <!-- Tabla de reservas mejorada -->
            <DataTable
              :value="reservasPorEstado"
              :loading="loading"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 20, 50]"
              paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
              currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reservas"
              responsiveLayout="scroll"
              class="mt-2 sm:mt-4 overflow-x-auto"
              :pt="{
                root: { class: 'text-xs sm:text-sm' },
                wrapper: { class: 'text-xs sm:text-sm overflow-x-auto' },
                table: { class: 'text-xs sm:text-sm min-w-full' },
                thead: { class: 'text-xs sm:text-sm' },
                headerRow: { class: 'text-xs sm:text-sm' },
                headerCell: { class: 'text-xs sm:text-sm font-medium py-2 sm:py-3 px-1 sm:px-2' },
                tbody: { class: 'text-xs sm:text-sm' },
                bodyRow: { class: 'text-xs sm:text-sm hover:bg-gray-50 transition-colors duration-200' },
                bodyCell: { class: 'py-2 sm:py-3 px-1 sm:px-2 text-xs sm:text-sm' }
              }"
            >
              <template #empty>
                <div class="text-center py-12">
                  <FontAwesomeIcon :icon="faInfoCircle" class="text-4xl text-gray-400 mb-4" />
                  <p class="text-gray-500 text-lg">No hay reservas {{ estado.label.toLowerCase() }}</p>
                  <p class="text-gray-400 text-sm mt-2">
                    {{ estado.value === 'PENDIENTE' ? 'Las nuevas reservas aparecerán aquí' : 'No se encontraron registros' }}
                  </p>
                </div>
              </template>

              <template #loading>
                <div class="text-center py-12">
                  <i class="pi pi-spin pi-spinner text-3xl text-blue-500 mb-4"></i>
                  <p class="text-gray-500 text-lg">Cargando reservas...</p>
                </div>
              </template>

              <!-- Columna Fecha -->
              <Column field="fecha_reserva" header="Fecha" sortable class="w-16 sm:w-20 lg:w-24 min-w-14">
                <template #body="slotProps">
                  <div class="text-xs sm:text-sm">
                    <div class="font-medium">{{ formatearFecha(slotProps.data.fecha_reserva) }}</div>
                    <div class="text-xs text-gray-500 hidden sm:block">
                      {{ new Date(slotProps.data.fecha_reserva).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Columna Cliente -->
              <Column field="cliente.nombres" header="Cliente" sortable class="w-24 sm:w-32 lg:w-40 min-w-20 hidden sm:table-cell">
                <template #body="slotProps">
                  <div class="flex items-center gap-1 sm:gap-3">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <FontAwesomeIcon :icon="faUsers" class="text-blue-600 text-xs sm:text-sm" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="font-medium text-gray-900 text-xs sm:text-sm truncate" :title="(slotProps.data.cliente?.user?.name) || (slotProps.data.cliente?.nombres) || 'N/A'">
                        {{ (slotProps.data.cliente?.user?.name) || (slotProps.data.cliente?.nombres) || 'N/A' }}
                      </div>
                      <div class="text-xs text-gray-500 truncate hidden sm:block" :title="(slotProps.data.cliente?.user?.email) || (slotProps.data.cliente?.correo) || 'N/A'">
                        {{ (slotProps.data.cliente?.user?.email) || (slotProps.data.cliente?.correo) || 'N/A' }}
                      </div>
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Columna Servicio -->
              <Column field="entidad_nombre" header="Servicio" sortable class="w-24 sm:w-32 lg:w-48 min-w-20">
                <template #body="slotProps">
                  <div class="min-w-0">
                    <div
                      class="font-medium text-gray-900 text-xs sm:text-sm truncate"
                      :title="slotProps.data.entidad_nombre"
                    >
                      {{ slotProps.data.entidad_nombre || 'N/A' }}
                    </div>
                    <div class="text-xs text-gray-500 capitalize hidden sm:block">
                      {{ slotProps.data.tipo || 'N/A' }}
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Columna Personas -->
              <Column header="Personas" class="w-16 sm:w-20 lg:w-24 min-w-14 hidden md:table-cell">
                <template #body="slotProps">
                  <div class="text-center">
                    <div class="text-xs sm:text-sm flex items-center justify-center gap-1">
                      <FontAwesomeIcon :icon="faUsers" class="text-gray-400 text-xs" />
                      <span class="font-medium">{{ (slotProps.data.mayores_edad || 0) + (slotProps.data.menores_edad || 0) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ slotProps.data.mayores_edad || 0 }}A • {{ slotProps.data.menores_edad || 0 }}N
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Columna Total -->
              <Column field="total" header="Total" sortable class="w-16 sm:w-20 lg:w-24 min-w-14">
                <template #body="slotProps">
                  <div class="text-right">
                    <div class="font-medium text-green-600 flex items-center justify-end gap-1 text-xs sm:text-sm">
                      <FontAwesomeIcon :icon="faDollarSign" class="text-xs" />
                      <span class="truncate">{{ Number(slotProps.data.total || 0).toFixed(2) }}</span>
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Columna Estado -->
              <Column field="estado" header="Estado" class="w-20 sm:w-24 lg:w-32 min-w-16 hidden lg:table-cell">
                <template #body="slotProps">
                  <span :class="getColorEstadoReserva(slotProps.data.estado)" class="px-1 sm:px-2 py-1 rounded-full text-xs font-medium">
                    <span class="hidden xl:inline">{{ estadosReservas.find(e => e.value === slotProps.data.estado)?.label || slotProps.data.estado }}</span>
                    <span class="xl:hidden">{{ (estadosReservas.find(e => e.value === slotProps.data.estado)?.label || slotProps.data.estado).substring(0, 4) }}</span>
                  </span>
                </template>
              </Column>

              <!-- Columna Acciones -->
              <Column header="Acciones" class="w-32 sm:w-40 lg:w-56 min-w-28">
                <template #body="slotProps">
                  <div class="flex gap-1 flex-wrap justify-center">
                    <!-- Botón Ver Detalles -->
                    <button
                      v-if="getAccionesDisponibles(slotProps.data).includes('detalles')"
                      class="bg-gray-500 hover:bg-gray-600 text-white px-1 sm:px-2 py-1 rounded text-xs transition-colors flex items-center gap-1"
                      @click="verDetallesReserva(slotProps.data)"
                      title="Ver detalles"
                    >
                      <FontAwesomeIcon :icon="faEye" class="text-xs" />
                      <span class="hidden lg:inline">Detalles</span>
                    </button>

                    <!-- Botón Confirmar -->
                    <button
                      v-if="getAccionesDisponibles(slotProps.data).includes('confirmar')"
                      class="bg-green-500 hover:bg-green-600 text-white px-1 sm:px-2 py-1 rounded text-xs transition-colors flex items-center gap-1"
                      @click="confirmarReserva(slotProps.data)"
                      title="Confirmar reserva"
                    >
                      <FontAwesomeIcon :icon="faCheck" class="text-xs" />
                      <span class="hidden lg:inline">Confirmar</span>
                    </button>

                    <!-- Botón Rechazar -->
                    <button
                      v-if="getAccionesDisponibles(slotProps.data).includes('rechazar')"
                      class="bg-red-500 hover:bg-red-600 text-white px-1 sm:px-2 py-1 rounded text-xs transition-colors flex items-center gap-1"
                      @click="abrirModalRechazar(slotProps.data)"
                      title="Rechazar reserva"
                    >
                      <FontAwesomeIcon :icon="faXmark" class="text-xs" />
                      <span class="hidden lg:inline">Rechazar</span>
                    </button>

                    <!-- Botón Reprogramar -->
                    <button
                      v-if="getAccionesDisponibles(slotProps.data).includes('reprogramar')"
                      class="bg-blue-500 hover:bg-blue-600 text-white px-1 sm:px-2 py-1 rounded text-xs transition-colors flex items-center gap-1"
                      @click="abrirModalReprogramar(slotProps.data)"
                      title="Reprogramar reserva"
                    >
                      <FontAwesomeIcon :icon="faCalendarDays" class="text-xs" />
                      <span class="hidden lg:inline">Reprogramar</span>
                    </button>

                    <!-- Botón Finalizar -->
                    <button
                      v-if="getAccionesDisponibles(slotProps.data).includes('finalizar')"
                      class="bg-purple-500 hover:bg-purple-600 text-white px-1 sm:px-2 py-1 rounded text-xs transition-colors flex items-center gap-1"
                      @click="finalizarReserva(slotProps.data)"
                      title="Finalizar reserva"
                    >
                      <FontAwesomeIcon :icon="faCalendarCheck" class="text-xs" />
                      <span class="hidden lg:inline">Finalizar</span>
                    </button>
                  </div>
                </template>
              </Column>
            </DataTable>
          </TabPanel>
        </TabPanels>
      </Tabs>

      <!-- Modal para ver detalles de reserva -->
      <Dialog
        v-model:visible="modalDetalles"
        modal
        header="Detalles de la Reserva"
        :style="{ width: '95vw', maxWidth: '800px' }"
        :closable="false"
        :draggable="false"
        :pt="{
          root: 'rounded-lg',
          header: 'text-sm sm:text-base lg:text-lg p-3 sm:p-4',
          content: 'p-2 sm:p-4 lg:p-6 max-h-[80vh] overflow-y-auto'
        }"
      >
        <div v-if="reservaSeleccionada" class="space-y-4 sm:space-y-6">
          <!-- Información del cliente -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4">
            <h4 class="font-semibold text-blue-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
              <FontAwesomeIcon :icon="faUsers" class="text-blue-600 text-sm sm:text-base" />
              Información del Cliente
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-xs sm:text-sm">
              <div class="break-words">
                <span class="font-medium text-gray-700">Nombre:</span>
                <span class="ml-2">{{ (reservaSeleccionada.cliente?.user?.name) || (reservaSeleccionada.cliente?.nombres) || 'N/A' }}</span>
              </div>
              <div class="break-words">
                <span class="font-medium text-gray-700">Email:</span>
                <span class="ml-2">{{ (reservaSeleccionada.cliente?.user?.email) || (reservaSeleccionada.cliente?.correo) || 'N/A' }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Teléfono:</span>
                <span class="ml-2">{{ reservaSeleccionada.cliente?.telefono || 'N/A' }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Documento:</span>
                <span class="ml-2">{{ reservaSeleccionada.cliente?.numero_documento || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <!-- Información del servicio -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4">
            <h4 class="font-semibold text-gray-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
              <FontAwesomeIcon :icon="faCalendarDays" class="text-gray-600 text-sm sm:text-base" />
              Información del Servicio
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-xs sm:text-sm">
              <div class="break-words">
                <span class="font-medium text-gray-700">Servicio:</span>
                <span class="ml-2">{{ reservaSeleccionada.entidad_nombre || 'N/A' }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Tipo:</span>
                <span class="ml-2 capitalize">{{ reservaSeleccionada.tipo || 'N/A' }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Fecha:</span>
                <span class="ml-2">{{ formatearFechaHora(reservaSeleccionada.fecha_reserva) }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Estado:</span>
                <span :class="getColorEstadoReserva(reservaSeleccionada.estado)" class="ml-2 px-2 py-1 rounded-full text-xs font-medium">
                  {{ estadosReservas.find(e => e.value === reservaSeleccionada.estado)?.label || reservaSeleccionada.estado }}
                </span>
              </div>
            </div>
          </div>

          <!-- Información de personas y precio -->
          <div class="bg-green-50 border border-green-200 rounded-lg p-3 sm:p-4">
            <h4 class="font-semibold text-green-800 mb-2 sm:mb-3 flex items-center gap-2 text-sm sm:text-base">
              <FontAwesomeIcon :icon="faDollarSign" class="text-green-600 text-sm sm:text-base" />
              Detalles de la Reserva
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3 text-xs sm:text-sm">
              <div>
                <span class="font-medium text-gray-700">Adultos:</span>
                <span class="ml-2">{{ reservaSeleccionada.mayores_edad || 0 }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Niños:</span>
                <span class="ml-2">{{ reservaSeleccionada.menores_edad || 0 }}</span>
              </div>
              <div class="col-span-2 sm:col-span-1">
                <span class="font-medium text-gray-700">Total:</span>
                <span class="ml-2 font-bold text-green-600">${{ Number(reservaSeleccionada.total || 0).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-center w-full p-2 sm:p-0">
            <button
              class="bg-blue-500 hover:bg-blue-600 text-white px-4 sm:px-6 py-2 rounded transition-colors flex items-center gap-2 text-sm sm:text-base"
              @click="modalDetalles = false"
            >
              <FontAwesomeIcon :icon="faXmark" class="h-3 sm:h-4" />
              Cerrar
            </button>
          </div>
        </template>
      </Dialog>

      <!-- Modal para rechazar reserva (mejorado) -->
      <Dialog
        v-model:visible="modalRechazar"
        modal
        header="Rechazar Reserva"
        :style="{ width: '95vw', maxWidth: '600px' }"
        :closable="false"
        :draggable="false"
        :pt="{
          root: 'rounded-lg',
          header: 'text-sm sm:text-base lg:text-lg p-3 sm:p-4',
          content: 'p-2 sm:p-4 lg:p-6'
        }"
      >
        <div class="space-y-3 sm:space-y-4">
          <div v-if="reservaSeleccionada" class="bg-red-50 border border-red-200 rounded-lg p-3 sm:p-4">
            <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
              <FontAwesomeIcon :icon="faExclamationTriangle" class="text-red-600 text-base sm:text-lg" />
              <h4 class="font-medium text-red-800 text-sm sm:text-base">Confirmar Rechazo</h4>
            </div>
            <div class="text-xs sm:text-sm space-y-1">
              <p class="break-words"><strong>Cliente:</strong> {{ (reservaSeleccionada.cliente?.user?.name) || (reservaSeleccionada.cliente?.nombres) || 'N/A' }}</p>
              <p class="break-words"><strong>Servicio:</strong> {{ reservaSeleccionada.entidad_nombre }}</p>
              <p><strong>Fecha:</strong> {{ formatearFecha(reservaSeleccionada.fecha_reserva) }}</p>
              <p><strong>Total:</strong> ${{ Number(reservaSeleccionada.total || 0).toFixed(2) }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
              Motivo del rechazo <span class="text-red-500">*</span>
            </label>
            <Textarea
              v-model="motivoRechazo"
              placeholder="Especifica el motivo por el cual se rechaza esta reserva..."
              rows="4"
              class="w-full text-xs sm:text-sm"
              maxlength="500"
              :pt="{
                root: 'w-full',
                textarea: 'text-xs sm:text-sm p-2 sm:p-3'
              }"
            />
            <small class="text-gray-500 text-xs mt-1">
              {{ motivoRechazo.length }}/500 caracteres
            </small>
          </div>
        </div>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-center gap-2 sm:gap-3 w-full p-2 sm:p-0">
            <button
              class="bg-gray-500 hover:bg-gray-600 text-white px-4 sm:px-6 py-2 rounded transition-colors flex items-center justify-center gap-2 text-sm sm:text-base order-2 sm:order-1"
              @click="modalRechazar = false"
            >
              <FontAwesomeIcon :icon="faXmark" class="h-3 sm:h-4" />
              Cancelar
            </button>
            <button
              class="bg-red-500 hover:bg-red-600 text-white px-4 sm:px-6 py-2 rounded transition-colors flex items-center justify-center gap-2 text-sm sm:text-base order-1 sm:order-2 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="rechazarReserva"
              :disabled="!motivoRechazo.trim()"
            >
              <FontAwesomeIcon :icon="faCheck" class="h-3 sm:h-4" />
              Rechazar Reserva
            </button>
          </div>
        </template>
      </Dialog>

      <!-- Modal para reprogramar reserva (mejorado con integración de tours) -->
      <Dialog
        v-model:visible="modalReprogramar"
        modal
        header="Reprogramar Reserva"
        :style="{ width: '95vw', maxWidth: '800px' }"
        :pt="{
          root: 'rounded-lg',
          header: 'text-sm sm:text-base lg:text-lg p-3 sm:p-4',
          content: 'p-2 sm:p-4 lg:p-6 max-h-[80vh] overflow-y-auto'
        }"
        :closable="false"
        :draggable="false"
      >
        <div class="space-y-6">
          <div v-if="reservaSeleccionada" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center gap-3 mb-3">
              <FontAwesomeIcon :icon="faCalendarDays" class="text-blue-600 text-lg" />
              <h4 class="font-medium text-blue-800">Información de la Reserva</h4>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              <div>
                <span class="font-medium text-gray-700">Cliente:</span>
                <span class="ml-2">{{ (reservaSeleccionada.cliente?.user?.name) || (reservaSeleccionada.cliente?.nombres) || 'N/A' }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Servicio:</span>
                <span class="ml-2">{{ reservaSeleccionada.entidad_nombre }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Fecha actual:</span>
                <span class="ml-2">{{ formatearFecha(reservaSeleccionada.fecha_reserva) }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">Total:</span>
                <span class="ml-2 font-bold text-green-600">${{ Number(reservaSeleccionada.total || 0).toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- Tour asociado -->
          <div v-if="tourSeleccionado" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center gap-3 mb-3">
              <FontAwesomeIcon :icon="faInfoCircle" class="text-purple-600 text-lg" />
              <h4 class="font-medium text-purple-800">Tour Asociado</h4>
            </div>
            <div class="text-sm space-y-1">
              <p><strong>Nombre:</strong> {{ tourSeleccionado.nombre }}</p>
              <p><strong>Estado actual:</strong>
                <span :class="getColorEstadoTour(tourSeleccionado.estado)" class="px-2 py-1 rounded-full text-xs font-medium ml-1">
                  {{ estadosTours.find(e => e.value === tourSeleccionado.estado)?.label || tourSeleccionado.estado }}
                </span>
              </p>
              <p class="text-purple-600 text-xs mt-2">
                ℹ️ Al reprogramar esta reserva, el tour también será marcado como "REPROGRAMADO"
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nueva fecha <span class="text-red-500">*</span>
              </label>
              <DatePicker
                v-model="fechaNuevaReprogramacion"
                showTime
                dateFormat="dd/mm/yy"
                class="w-full"
                showIcon
                placeholder="Seleccione la nueva fecha y hora"
                :minDate="getMinDate()"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Motivo de la reprogramación <span class="text-red-500">*</span>
            </label>
            <Textarea
              v-model="motivoReprogramacion"
              placeholder="Especifica el motivo de la reprogramación..."
              rows="3"
              class="w-full"
              maxlength="255"
            />
            <small class="text-gray-500 text-xs mt-1">
              {{ motivoReprogramacion.length }}/255 caracteres
            </small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Observaciones adicionales (opcional)
            </label>
            <Textarea
              v-model="observacionesReprogramacion"
              placeholder="Información adicional sobre la reprogramación..."
              rows="2"
              class="w-full"
              maxlength="500"
            />
            <small class="text-gray-500 text-xs mt-1">
              {{ observacionesReprogramacion.length }}/500 caracteres
            </small>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-center gap-3 w-full">
            <button
              class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded transition-colors flex items-center gap-2"
              @click="modalReprogramar = false"
            >
              <FontAwesomeIcon :icon="faXmark" class="h-4" />
              Cancelar
            </button>
            <button
              class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded transition-colors flex items-center gap-2"
              @click="reprogramarReserva"
              :disabled="!fechaNuevaReprogramacion || !motivoReprogramacion.trim()"
            >
              <FontAwesomeIcon :icon="faCalendarDays" class="h-4" />
              Reprogramar
            </button>
          </div>
        </template>
      </Dialog>
    </div>
  </AuthenticatedLayout>
</template>
