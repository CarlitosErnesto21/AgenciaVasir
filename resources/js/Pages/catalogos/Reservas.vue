<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faCheck, faXmark, faCalendarDays, faClockRotateLeft,
  faUsers, faDollarSign,
  faCalendarCheck, faInfoCircle,
  faSpinner, faListDots, faRefresh,
  faHandPointUp
} from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'
import ReservaModales from './Components/ReservasComponents/Modales.vue'

// Configuración de Toast
const toast = useToast()

// Estado reactivo
const reservas = ref([])
const tours = ref([])
const loading = ref(false)
const loadingTours = ref(false)
const filtros = ref({
  busqueda: '',
  fechaDesde: null,
  fechaHasta: null,
  estadoReserva: '', // Siempre vacío por defecto
  estadoTour: null,
  tourSeleccionado: '' // Nuevo filtro para buscar por tour específico
})

// Estados para modales
const modalMasAcciones = ref(false)
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

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

// Variables adicionales para UI
const isClearingFilters = ref(false)
const procesando = ref(false)
const confirmandoReserva = ref(false)
const rechazandoReserva = ref(false)
const reprogramandoReserva = ref(false)
const finalizandoReserva = ref(false)
const isReloading = ref(false)
const isFinalizandoAutomatico = ref(false)

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
const reservasFiltradas = computed(() => {
  let filtered = reservas.value

  // Filtrar por estado de reserva si está seleccionado
  if (filtros.value.estadoReserva) {
    filtered = filtered.filter(reserva => reserva.estado === filtros.value.estadoReserva)
  }

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

  // Ordenar: PENDIENTES primero, luego por fecha más reciente
  const resultado = filtered.sort((a, b) => {
    // Prioridad por estado: PENDIENTE primero
    const prioridadEstado = { 'PENDIENTE': 1, 'CONFIRMADA': 2, 'REPROGRAMADA': 3, 'FINALIZADA': 4, 'RECHAZADA': 5 }
    const prioridadA = prioridadEstado[a.estado] || 6
    const prioridadB = prioridadEstado[b.estado] || 6

    if (prioridadA !== prioridadB) {
      return prioridadA - prioridadB
    }

    // Si tienen el mismo estado, ordenar por fecha (más reciente primero)
    return new Date(b.fecha_reserva) - new Date(a.fecha_reserva)
  })
  return resultado
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

// Computed para información del tour seleccionado
const infoTourSeleccionado = computed(() => {
  if (!filtros.value.tourSeleccionado || filtros.value.tourSeleccionado === '') return null
  
  const tour = tours.value.find(t => t.id == filtros.value.tourSeleccionado)
  const reservasDelTour = reservasFiltradas.value.length
  
  return {
    nombre: tour?.nombre || 'Tour no encontrado',
    totalReservas: reservasDelTour
  }
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

// Función para cargar todas las reservas
const cargarReservas = async () => {
  loading.value = true
  try {
    const params = {
      busqueda: filtros.value.busqueda || undefined,
      fecha_inicio: filtros.value.fechaDesde || undefined,
      fecha_fin: filtros.value.fechaHasta || undefined,
      tour_id: filtros.value.tourSeleccionado || undefined
    }
    const response = await axios.get('/api/reservas', { params, withCredentials: true })
    reservas.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
    // Reservas cargadas exitosamente
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

// Función para cargar reservas con toasts informativos
const cargarReservasWithToasts = async () => {
  loading.value = true

  // Mostrar toast de carga con duración automática
  toast.add({
    severity: 'info',
    summary: 'Cargando reservas...',
    life: 2000
  })

  try {
    const params = {
      busqueda: filtros.value.busqueda || undefined,
      fecha_inicio: filtros.value.fechaDesde || undefined,
      fecha_fin: filtros.value.fechaHasta || undefined,
      tour_id: filtros.value.tourSeleccionado || undefined
    }

    const response = await axios.get('/api/reservas', { params, withCredentials: true })
    reservas.value = response.data.data || []

    // Mostrar toast de éxito
    toast.add({
      severity: 'success',
      summary: 'Reservas cargadas',
      life: 2000
    })

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
  confirmandoReserva.value = true
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
  } finally {
    confirmandoReserva.value = false
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
  finalizandoReserva.value = true
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
  } finally {
    finalizandoReserva.value = false
  }
}

// Función para ver detalles de reserva
const verDetallesReserva = (reserva) => {
  reservaSeleccionada.value = reserva
  modalDetalles.value = true
}

// Función para abrir modal de más acciones
const abrirModalMasAcciones = (reserva) => {
  reservaSeleccionada.value = reserva
  // Cargar el tour relacionado si existe
  const tour = tours.value.find(t =>
    reserva.detallesTours?.some(dt => dt.tour_id === t.id) ||
    reserva.entidad_nombre === t.nombre
  )
  tourSeleccionado.value = tour
  modalMasAcciones.value = true
}

// Función para manejar el clic en la fila de la tabla
const onRowClick = (event) => {
  // Verificar si el clic fue en un botón para evitar abrir el modal
  const target = event.originalEvent.target
  const isButton = target.closest('button')

  if (!isButton) {
    verDetallesReserva(event.data)
  }
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
  const fechaObj = new Date(fecha)

  // Si la hora es 00:00, mostrar solo la fecha
  if (fechaObj.getHours() === 0 && fechaObj.getMinutes() === 0) {
    return fechaObj.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }) + ' (Sin hora especificada)'
  }

  return fechaObj.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Función para limpiar filtros
const limpiarFiltros = async () => {
  isClearingFilters.value = true

  try {
    // Simular un pequeño delay para mostrar el loading
    await new Promise(resolve => setTimeout(resolve, 300))

    filtros.value = {
      busqueda: '',
      fechaDesde: null,
      fechaHasta: null,
      estadoReserva: '', // Limpiar filtro de estado
      estadoTour: null,
      tourSeleccionado: '' // Limpiar filtro de tour
    }

    toast.add({
      severity: "success",
      summary: "Filtros limpiados",
      life: 2000
    })

  } finally {
    isClearingFilters.value = false
  }
}

// Función para obtener fecha mínima (hoy)
const getMinDate = () => {
  return new Date()
}

// Función para recargar reservas con toasts
const recargarReservasWithToasts = async () => {
  isReloading.value = true

  // Mostrar toast de carga
  toast.add({
    severity: 'info',
    summary: 'Cargando reservas...',
    life: 2000
  })

  try {
    await cargarReservas()

    // Mostrar toast de éxito
    setTimeout(() => {
      toast.add({
        severity: 'success',
        summary: 'Reservas actualizadas',
        detail: `${reservas.value.length} registros cargados`,
        life: 2000
      })
    }, 300)

  } catch (error) {
    console.error('Error al cargar las reservas:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las reservas',
      life: 3000
    })
  } finally {
    // Desactivar loading después de un breve delay
    setTimeout(() => {
      isReloading.value = false
    }, 500)
  }
}

// Función para ejecutar finalización automática
const ejecutarFinalizacionAutomatica = async () => {
  try {
    isFinalizandoAutomatico.value = true

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
    isFinalizandoAutomatico.value = false
  }
}

// Funciones para manejar eventos del componente Modales
const handleConfirmarReserva = async (reserva) => {
  await confirmarReserva(reserva)
  // Cerrar el modal después de completar la operación
  modalMasAcciones.value = false
}

const handleRechazarReserva = async (data) => {
  rechazandoReserva.value = true
  procesando.value = true
  try {
    await axios.put(`/api/reservas/${data.reserva.id}/rechazar`, {
      motivo: data.motivo
    })

    // Actualizar estado local
    const index = reservas.value.findIndex(r => r.id === data.reserva.id)
    if (index !== -1) {
      reservas.value[index].estado = 'RECHAZADA'
    }

    modalRechazar.value = false
    modalMasAcciones.value = false
    toast.add({
      severity: 'success',
      summary: '¡Rechazada!',
      detail: `Reserva de ${data.reserva.cliente?.user?.name || data.reserva.cliente?.nombres} rechazada correctamente`,
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
  } finally {
    procesando.value = false
    rechazandoReserva.value = false
  }
}

const handleReprogramarReserva = async (data) => {
  reprogramandoReserva.value = true
  procesando.value = true
  try {
    const reprogramacionData = {
      fecha_nueva: data.fechaNueva,
      motivo: data.motivo,
      observaciones: data.observaciones
    }

    await axios.put(`/api/reservas/${data.reserva.id}/reprogramar`, reprogramacionData)

    // Si hay un tour asociado, también reprogramarlo
    if (tourSeleccionado.value) {
      try {
        // Calcular nueva fecha de regreso (agregar la duración original del tour)
        const fechaOriginalSalida = new Date(tourSeleccionado.value.fecha_salida)
        const fechaOriginalRegreso = new Date(tourSeleccionado.value.fecha_regreso)
        const duracionTour = fechaOriginalRegreso.getTime() - fechaOriginalSalida.getTime()

        const nuevaFechaRegreso = new Date(new Date(data.fechaNueva).getTime() + duracionTour)

        const tourData = {
          estado: 'REPROGRAMADO',
          fecha_salida: data.fechaNueva,
          fecha_regreso: nuevaFechaRegreso,
          motivo_reprogramacion: `Reprogramado por reserva: ${data.motivo}`,
          observaciones: data.observaciones
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
    const index = reservas.value.findIndex(r => r.id === data.reserva.id)
    if (index !== -1) {
      reservas.value[index].estado = 'REPROGRAMADA'
      reservas.value[index].fecha_reserva = data.fechaNueva
    }

    modalReprogramar.value = false
    modalMasAcciones.value = false

    // Recargar datos para mostrar cambios
    await Promise.all([cargarReservas(), cargarTours()])

    toast.add({
      severity: 'success',
      summary: '¡Reprogramada!',
      detail: `Reserva de ${data.reserva.cliente?.user?.name || data.reserva.cliente?.nombres} reprogramada correctamente`,
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
  } finally {
    procesando.value = false
    reprogramandoReserva.value = false
  }
}

const handleFinalizarReserva = async (reserva) => {
  await finalizarReserva(reserva)
  // Cerrar el modal después de completar la operación
  modalMasAcciones.value = false
}

const handleVerDetalles = (reserva) => {
  verDetallesReserva(reserva)
}

// Watch para recargar cuando cambien algunos filtros
watch(() => [filtros.value.busqueda, filtros.value.fechaDesde, filtros.value.fechaHasta, filtros.value.tourSeleccionado], () => {
  // Debounce para la búsqueda
  clearTimeout(window.searchTimeout)
  window.searchTimeout = setTimeout(() => {
    cargarReservas()
  }, 500)
}, { deep: true })

// 🔧 Función para forzar truncado en selects
const forceSelectTruncation = () => {
  nextTick(() => {
    setTimeout(() => {
      const selects = document.querySelectorAll('.p-select')
      selects.forEach(select => {
        const label = select.querySelector('.p-select-label, .p-placeholder')
        if (label) {
          label.style.overflow = 'hidden'
          label.style.textOverflow = 'ellipsis'
          label.style.whiteSpace = 'nowrap'
          label.style.maxWidth = 'calc(100% - 2.5rem)'
          label.style.display = 'block'
        }
      })
    }, 100)
  })
}

// 👀 Watchers para forzar truncado cuando cambien los valores
watch([filtros], () => {
  forceSelectTruncation()
}, { deep: true })

// Cargar datos iniciales
onMounted(() => {
  // Limpiar filtros y cargar reservas/tours al iniciar
  limpiarFiltros().then(() => {
    Promise.all([cargarReservasWithToasts(), cargarTours()])
    forceSelectTruncation()
  })
})
</script>


<template>
  <AuthenticatedLayout>
    <Head title="Gestión de Reservas" />
    <Toast class="z-[9999]" />

    <div class="container mx-auto px-4 py-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Reservas de Tours</h1>
        <p class="text-gray-600">Administra todas las reservas de tours</p>
      </div>

      <!-- Estadísticas como tarjetas -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-6 mb-8">
        <Card class="bg-gradient-to-r from-yellow-500 to-yellow-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Pendientes</p>
                <p class="text-xl sm:text-3xl font-bold">{{ estadisticas.pendientes }}</p>
              </div>
              <FontAwesomeIcon :icon="faClockRotateLeft" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-green-500 to-green-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Confirmadas</p>
                <p class="text-xl sm:text-3xl font-bold">{{ estadisticas.confirmadas }}</p>
              </div>
              <FontAwesomeIcon :icon="faCheck" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-blue-500 to-blue-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Reprogramadas</p>
                <p class="text-xl sm:text-3xl font-bold">{{ estadisticas.reprogramadas }}</p>
              </div>
              <FontAwesomeIcon :icon="faCalendarDays" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-red-500 to-red-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Rechazadas</p>
                <p class="text-xl sm:text-3xl font-bold">{{ estadisticas.rechazadas }}</p>
              </div>
              <FontAwesomeIcon :icon="faXmark" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>

        <Card class="bg-gradient-to-r from-purple-500 to-purple-600">
          <template #content>
            <div class="flex items-center justify-between text-white p-2 sm:p-4">
              <div>
                <p class="text-xs sm:text-sm opacity-90">Ingresos</p>
                <p class="text-sm sm:text-3xl font-bold">${{ estadisticas.totalIngresos.toFixed(2) }}</p>
              </div>
              <FontAwesomeIcon :icon="faDollarSign" class="text-2xl sm:text-4xl opacity-75" />
            </div>
          </template>
        </Card>
      </div>

      <!-- Sección de contenido principal -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="flex flex-col sm:flex-row lg:justify-between lg:items-center mb-4 gap-4 p-6">
          <div>
            <h3 class="text-2xl sm:text-3xl text-blue-600 font-bold text-center sm:text-start">Reservas por Estado</h3>
            <p class="text-gray-500 text-sm text-center sm:text-start mt-1">
              📊 Gestiona las reservas organizadas por su estado actual
            </p>
            <p class="text-blue-600 text-xs text-center sm:text-start mt-1 font-medium">
                <FontAwesomeIcon :icon="faHandPointUp" class="h-4 w-4 text-yellow-500" />
                Haz clic en cualquier fila para ver los detalles.
            </p>
          </div>
        </div>

        <!-- Filtros -->
        <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4 mx-4">
          <div class="flex flex-col sm:flex-row items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <h3 class="text-base font-medium text-gray-800 flex items-center gap-2">
                <i class="pi pi-filter text-blue-600 text-sm"></i><span>Filtros</span>
              </h3>
              <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                {{ reservas.length }} resultado{{ reservas.length !== 1 ? 's' : '' }}
              </div>
            </div>
            <!-- Botones para móvil en grid de 3 columnas -->
            <div class="grid grid-cols-3 gap-2 w-full sm:hidden mt-2">
              <button
                @click="limpiarFiltros"
                :disabled="isClearingFilters"
                class="bg-red-500 hover:bg-red-600 border border-red-500 px-2 py-1 text-xs text-white shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1"
              >
                <FontAwesomeIcon
                  v-if="isClearingFilters"
                  :icon="faSpinner"
                  class="animate-spin h-3 w-3"
                />
                <span class="truncate">{{ isClearingFilters ? 'Limpiando...' : 'Limpiar' }}</span>
              </button>
              <button
                @click="recargarReservasWithToasts"
                :disabled="isReloading"
                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 text-xs shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1"
              >
                <FontAwesomeIcon
                  :icon="isReloading ? faSpinner : faRefresh"
                  :class="{ 'animate-spin': isReloading }"
                  class="h-3 w-3"
                />
                <span class="truncate">{{ isReloading ? 'Recargando...' : 'Recargar' }}</span>
              </button>
              <button
                @click="ejecutarFinalizacionAutomatica"
                :disabled="isFinalizandoAutomatico"
                class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-1 text-xs shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1"
                title="Finaliza automáticamente las reservas cuyas fechas de tour han pasado"
              >
                <FontAwesomeIcon
                  v-if="isFinalizandoAutomatico"
                  :icon="faSpinner"
                  class="animate-spin h-3 w-3"
                />
                <FontAwesomeIcon v-else :icon="faCalendarCheck" class="h-3" />
                <span v-if="!isFinalizandoAutomatico" class="truncate">Finalizar</span>
              </button>
            </div>
            <div class="flex gap-2">
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
              <button
                @click="recargarReservasWithToasts"
                :disabled="isReloading"
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 text-sm shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2 hidden sm:flex"
              >
                <FontAwesomeIcon
                  :icon="isReloading ? faSpinner : faRefresh"
                  :class="{ 'animate-spin': isReloading }"
                  class="h-3 w-3"
                />
                <span>{{ isReloading ? 'Recargando...' : 'Recargar' }}</span>
              </button>
              <button
                @click="ejecutarFinalizacionAutomatica"
                :disabled="isFinalizandoAutomatico"
                class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 text-sm shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2 hidden sm:flex"
                title="Finaliza automáticamente las reservas cuyas fechas de tour han pasado"
              >
                <FontAwesomeIcon
                  v-if="isFinalizandoAutomatico"
                  :icon="faSpinner"
                  class="animate-spin h-3 w-3"
                />
                <FontAwesomeIcon v-else :icon="faCalendarCheck" class="h-3" />
                <span v-if="!isFinalizandoAutomatico" class="hidden sm:inline">Finalización Automática</span>
              </button>
            </div>
          </div>
          <div class="space-y-3">
            <div>
              <InputText
                v-model="filtros.busqueda"
                placeholder="🔍 Buscar reservas..."
                class="w-full h-9 text-sm rounded-md"
                style="background-color: white; border-color: #93c5fd;"
              />
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-3">
              <div class="col-span-1">
                <select
                  v-model="filtros.estadoReserva"
                  class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                >
                  <option value="" disabled selected hidden>Estado</option>
                  <option
                    v-for="estado in estadosReservas"
                    :key="estado.value"
                    :value="estado.value"
                    class="truncate text-gray-900"
                  >
                    {{ estado.label }}
                  </option>
                </select>
              </div>
              
              <div class="col-span-1">
                <select
                  v-model="filtros.tourSeleccionado"
                  class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                  :disabled="loadingTours"
                >
                  <option value="" disabled selected hidden>Filtrar por Tour</option>
                  <option
                    v-for="tour in tours"
                    :key="tour.id"
                    :value="tour.id"
                    class="truncate text-gray-900"
                  >
                    {{ tour.nombre }}
                  </option>
                </select>
              </div>

              <div class="col-span-1 hidden md:block">
                <DatePicker
                  v-model="filtros.fechaDesde"
                  placeholder="Fecha desde"
                  dateFormat="yy-mm-dd"
                  class="w-full h-9 text-sm"
                  style="background-color: white; border-color: #93c5fd;"
                  showIcon
                />
              </div>

              <div class="col-span-1 hidden md:block">
                <DatePicker
                  v-model="filtros.fechaHasta"
                  placeholder="Fecha hasta"
                  dateFormat="yy-mm-dd"
                  class="w-full h-9 text-sm"
                  style="background-color: white; border-color: #93c5fd;"
                  showIcon
                />
              </div>

              <!-- Calendars para móviles -->
              <div class="col-span-2 flex gap-3 md:hidden">
                <DatePicker
                  v-model="filtros.fechaDesde"
                  placeholder="Fecha desde"
                  dateFormat="dd/mm/yy"
                  class="flex-1 h-9 text-sm rounded-md border border-blue-300"
                  showIcon
                />
                <DatePicker
                  v-model="filtros.fechaHasta"
                  placeholder="Fecha hasta"
                  dateFormat="dd/mm/yy"
                  class="flex-1 h-9 text-sm rounded-md border border-blue-300"
                  showIcon
                />
              </div>
            </div>
            
            <!-- Indicador del tour seleccionado -->
            <div v-if="infoTourSeleccionado" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <span class="text-blue-600 font-medium">🏝️ Mostrando reservas para:</span>
                  <span class="text-blue-800 font-semibold">{{ infoTourSeleccionado.nombre }}</span>
                </div>
                <div class="text-blue-600">
                  <span class="font-medium">{{ infoTourSeleccionado.totalReservas }}</span>
                  <span>{{ infoTourSeleccionado.totalReservas === 1 ? ' reserva encontrada' : ' reservas encontradas' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de reservas mejorada -->
        <DataTable
          :value="reservasFiltradas"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 20, 50]"
              paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
              currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reservas"
              responsiveLayout="scroll"
              class="mt-2 sm:mt-4 overflow-x-auto"
              @row-click="onRowClick"
              :pt="{
                root: { class: 'text-xs sm:text-sm' },
                wrapper: { class: 'text-xs sm:text-sm overflow-x-auto' },
                table: { class: 'text-xs sm:text-sm min-w-full' },
                thead: { class: 'text-xs sm:text-sm' },
                headerRow: { class: 'text-xs sm:text-sm' },
                headerCell: { class: 'text-xs sm:text-sm font-medium py-2 sm:py-3 px-1 sm:px-2' },
                tbody: { class: 'text-xs sm:text-sm' },
                bodyRow: { class: 'text-xs sm:text-sm cursor-pointer hover:bg-blue-50 transition-colors duration-200' },
                bodyCell: { class: 'py-2 sm:py-3 px-1 sm:px-2 text-xs sm:text-sm' }
              }"
            >
              <template #empty>
                <div class="text-center py-12">
                  <FontAwesomeIcon :icon="faInfoCircle" class="text-4xl text-gray-400 mb-4" />
                  <p class="text-gray-500 text-lg">No se encontraron reservas</p>
                  <p class="text-gray-400 text-sm mt-2">
                    Ajusta los filtros para encontrar las reservas que buscas
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
                      class="text-xs sm:text-sm font-medium leading-relaxed overflow-hidden"
                      style="max-width: 120px; text-overflow: ellipsis; white-space: nowrap;"
                      :title="slotProps.data.entidad_nombre"
                    >
                      {{ slotProps.data.entidad_nombre || 'N/A' }}
                    </div>
                    <div class="text-xs text-gray-500 capitalize hidden sm:block">
                      Tours
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
              <Column header="Acciones" class="w-20 sm:w-24 lg:w-28 min-w-16">
                <template #body="slotProps">
                  <div class="flex justify-center">
                    <!-- Botón Más Acciones -->
                    <button
                      class="bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-1 text-xs shadow-md hover:shadow-lg hover:-translate-y-1"
                      @click="abrirModalMasAcciones(slotProps.data)"
                      title="Más acciones"
                    >
                      <FontAwesomeIcon :icon="faListDots" class="h-3 w-4 sm:h-4 sm:w-5" />
                      <span class="hidden md:inline">Más</span>
                    </button>
                  </div>
                </template>
              </Column>
            </DataTable>

      <!-- Componente de Modales -->
      <ReservaModales
        v-model:visible="modalMasAcciones"
        v-model:detalles-visible="modalDetalles"
        v-model:rechazar-visible="modalRechazar"
        v-model:reprogramar-visible="modalReprogramar"
        :reserva="reservaSeleccionada"
        :tour="tourSeleccionado"
        :dialog-style="dialogStyle"
        :procesando="procesando"
        :confirmando-reserva="confirmandoReserva"
        :rechazando-reserva="rechazandoReserva"
        :reprogramando-reserva="reprogramandoReserva"
        :finalizando-reserva="finalizandoReserva"
        :estados-reservas="estadosReservas"
        :estados-tours="estadosTours"
        @confirmar="handleConfirmarReserva"
        @rechazar="handleRechazarReserva"
        @reprogramar="handleReprogramarReserva"
        @finalizar="handleFinalizarReserva"
        @ver-detalles="handleVerDetalles"
      />


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

/* Estilos para truncar texto en Select - PrimeVue específico */
.p-select .p-select-label,
.p-select .p-placeholder,
.p-select-label,
.p-placeholder {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
    display: block !important;
}

/* Contenedor principal del Select */
.p-select {
    overflow: hidden !important;
    max-width: 100% !important;
}

/* Input interno del Select */
.p-select .p-inputtext,
.p-select input {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
    width: 100% !important;
}

/* Forzar truncado en todos los elementos internos */
.p-select * {
    max-width: 100% !important;
    box-sizing: border-box !important;
}

/* Específico para el valor mostrado */
.p-select .p-select-display-chip,
.p-select .p-select-clear-icon ~ *,
.p-select .p-select-trigger ~ * {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: calc(100% - 2.5rem) !important;
}

/* Extra específico para móviles */
@media (max-width: 768px) {
    .p-select .p-select-label,
    .p-select .p-placeholder {
        font-size: 0.875rem !important;
        max-width: calc(100% - 2rem) !important;
    }
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
