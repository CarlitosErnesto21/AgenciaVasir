<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faCheck, faXmark, faCalendarDays, faClockRotateLeft,
  faUsers,
  faCalendarCheck, faInfoCircle, faExclamationTriangle,
  faSpinner, faListDots,
  faHandPointUp, faArrowLeft, faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'
import ReservaModales from './Components/ReservasComponents/Modales.vue'
import CambiarEstado from './Components/TourComponents/CambiarEstado.vue'

// Configuración de Toast y Confirm
const toast = useToast()
const confirm = useConfirm()

// Estado reactivo
const reservas = ref([])
const tours = ref([])
const loading = ref(false)
const loadingTours = ref(false)

// Variables para el tour específico
const tourId = ref(null)
const tourActual = ref(null)
const loadingTour = ref(false)
const loadingVolver = ref(false)
const accionesTourLoading = ref({
  cambiarEstado: false
})

// Modales para acciones de tour
const modalCambiarEstadoTour = ref(false)
const modalReprogramarTour = ref(false)
const datosReprogramacion = ref({
  fechaSalida: null,
  fechaRegreso: null,
  motivo: ''
})
const filtros = ref({
  busqueda: '',
  fechaDesde: null,
  fechaHasta: null,
  estado: null
})

// Estados para modales
const modalMasAcciones = ref(false)
const modalRechazar = ref(false)
const modalCancelarTour = ref(false) // Modal separado para cancelar tour
const modalDetalles = ref(false)
const reservaSeleccionada = ref(null)
const tourSeleccionado = ref(null)
const motivoRechazo = ref('')
const motivoCancelacionTour = ref('') // Motivo separado para cancelar tour
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
const cancelandoTour = ref(false)
// Se removieron: isReloading, isFinalizandoAutomatico

// Estados disponibles para reservas (unificados)
const estadosReservas = [
  { label: 'Pendiente', value: 'PENDIENTE', severity: 'warn', color: 'bg-yellow-100 text-yellow-800', icon: faClockRotateLeft },
  { label: 'Confirmada', value: 'CONFIRMADA', severity: 'success', color: 'bg-green-100 text-green-800', icon: faCheck },
  { label: 'En Curso', value: 'EN_CURSO', severity: 'info', color: 'bg-blue-100 text-blue-800', icon: faCalendarDays },
  { label: 'Finalizada', value: 'FINALIZADA', severity: 'secondary', color: 'bg-gray-100 text-gray-800', icon: faCalendarCheck },
  { label: 'Rechazada', value: 'CANCELADA', severity: 'danger', color: 'bg-red-100 text-red-800', icon: faXmark },
  { label: 'Reprogramada', value: 'REPROGRAMADA', severity: 'info', color: 'bg-purple-100 text-purple-800', icon: faCalendarDays }
]

// NOTA: Las reservas ya NO se reprograman ni finalizan individualmente.
// Solo se manejan a través del TOUR asociado desde la vista de Tours.

// Estados disponibles para tours
const estadosTours = [
  { label: 'Disponible', value: 'DISPONIBLE', color: 'bg-green-100 text-green-800' },
  { label: 'Completo', value: 'COMPLETO', color: 'bg-orange-100 text-orange-800' },
  { label: 'En Curso', value: 'EN_CURSO', color: 'bg-blue-100 text-blue-800' },
  { label: 'Finalizado', value: 'FINALIZADO', color: 'bg-gray-100 text-gray-800' },
  { label: 'Cancelada', value: 'CANCELADA', color: 'bg-red-100 text-red-800' },
  { label: 'Reprogramada', value: 'REPROGRAMADA', color: 'bg-purple-100 text-purple-800' }
]

// Computed para verificar si se cumple el cupo mínimo
const cumpleCupoMinimo = computed(() => {
  if (!tourActual.value) return false

  // Contar personas de reservas CONFIRMADAS y REPROGRAMADAS (ambas son válidas)
  const personasActivas = reservasFiltradas.value
    .filter(reserva => ['CONFIRMADA', 'REPROGRAMADA'].includes(reserva.estado))
    .reduce((total, reserva) => {
      const adultos = reserva.mayores_edad || 0
      const menores = reserva.menores_edad || 0
      return total + adultos + menores
    }, 0)

  const cupoMinimo = tourActual.value.cupo_min || 0

  return personasActivas >= cupoMinimo
})

// Computed para verificar si el tour tiene reservas activas (para habilitar/deshabilitar cancelación)
const tieneReservasActivas = computed(() => {
  if (!tourActual.value || !reservas.value) return false

  return reservas.value.some(reserva =>
    ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO', 'REPROGRAMADA'].includes(reserva.estado)
  )
})

// Computed para verificar si todas las reservas están confirmadas (no hay pendientes)
const todasReservasConfirmadas = computed(() => {
  if (!tourActual.value || !reservas.value || reservas.value.length === 0) return false

  // No debe haber ninguna reserva PENDIENTE
  const hayPendientes = reservas.value.some(reserva => reserva.estado === 'PENDIENTE')

  // Debe haber al menos una reserva CONFIRMADA
  const hayConfirmadas = reservas.value.some(reserva => reserva.estado === 'CONFIRMADA')

  return !hayPendientes && hayConfirmadas
})

// Computed específico para habilitar botón EN CURSO
const puedeIniciarCurso = computed(() => {
  if (!tourActual.value || !reservas.value || reservas.value.length === 0) return false

  // Para tours REPROGRAMADA: no debe haber reservas PENDIENTES y debe cumplir cupo mínimo
  if (tourActual.value.estado === 'REPROGRAMADA') {
    const hayPendientes = reservas.value.some(reserva => reserva.estado === 'PENDIENTE')
    return !hayPendientes && cumpleCupoMinimo.value
  }

  // Para tours DISPONIBLE: debe cumplir cupo mínimo
  if (tourActual.value.estado === 'DISPONIBLE') {
    return cumpleCupoMinimo.value
  }

  // Para tours COMPLETO: debe tener todas las reservas confirmadas (sin pendientes)
  if (tourActual.value.estado === 'COMPLETO') {
    return todasReservasConfirmadas.value && cumpleCupoMinimo.value
  }

  return false
})// Computed para filtrar reservas (simplificado para un tour específico)
const reservasFiltradas = computed(() => {
  let filtered = reservas.value

  // Aplicar filtros básicos
  if (filtros.value.busqueda) {
    const busqueda = filtros.value.busqueda.toLowerCase()
    filtered = filtered.filter(reserva =>
      (reserva.cliente?.user?.name || reserva.cliente?.nombres || '').toLowerCase().includes(busqueda) ||
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

  if (filtros.value.estado) {
    filtered = filtered.filter(reserva => reserva.estado === filtros.value.estado)
  }

  // Ordenar: PENDIENTES primero, luego por fecha más reciente
  const resultado = filtered.sort((a, b) => {
    const prioridadEstado = { 'PENDIENTE': 1, 'CONFIRMADA': 2, 'EN_CURSO': 3, 'REPROGRAMADA': 4, 'COMPLETADA': 5, 'SUSPENDIDA': 6, 'CANCELADA': 7 }
    const prioridadA = prioridadEstado[a.estado] || 6
    const prioridadB = prioridadEstado[b.estado] || 6

    if (prioridadA !== prioridadB) {
      return prioridadA - prioridadB
    }

    return new Date(b.fecha_reserva) - new Date(a.fecha_reserva)
  })
  return resultado
})

// Computed para estadísticas
const estadisticas = computed(() => {
  return {
    pendientes: reservas.value.filter(r => r.estado === 'PENDIENTE').length,
    confirmadas: reservas.value.filter(r => r.estado === 'CONFIRMADA').length
  }
})

// Funciones para cargar tour específico
const cargarTour = async (id) => {
  loadingTour.value = true
  try {
    const response = await axios.get(`/api/tours/${id}`)
    tourActual.value = response.data
  } catch (error) {
    console.error('Error cargando tour:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la información del tour',
      life: 4000
    })
  } finally {
    loadingTour.value = false
  }
}

// Función para obtener tour ID desde URL
const obtenerTourIdDesdeUrl = () => {
  const urlParams = new URLSearchParams(window.location.search)
  const tourParam = urlParams.get('tour')
  return tourParam
}

// El modal ahora solo se usa para Reprogramar desde cambiarEstadoDirecto()

// Función para cambiar estado directamente
const cambiarEstadoDirecto = async (nuevoEstado) => {
  if (!tourActual.value) return

  // Para Reprogramar, abrir modal específico
  if (nuevoEstado === 'REPROGRAMADA') {
    cerrarTodosLosModales() // Cerrar todos los modales primero

    datosReprogramacion.value = {
      fechaSalida: tourActual.value.fecha_salida ? new Date(tourActual.value.fecha_salida) : null,
      fechaRegreso: tourActual.value.fecha_regreso ? new Date(tourActual.value.fecha_regreso) : null,
      motivo: ''
    }
    modalReprogramarTour.value = true
    return
  }

  // Para Cancelar, abrir modal específico para cancelar tour
  if (nuevoEstado === 'CANCELADA') {
    abrirModalCancelarTour()
    return
  }

  // Mostrar confirmación para otros cambios de estado
  const mensajesConfirmacion = {
    'EN_CURSO': {
      header: 'Cambio de Estado del Tour',
      message: '¡Tienes un cambio de estado pendiente!',
      detail: '¿Deseas marcar este tour como "En Curso"?'
    },
    'FINALIZADO': {
      header: 'Finalizar Tour',
      message: '¡Tienes un cambio de estado pendiente!',
      detail: '¿Deseas finalizar este tour permanentemente?'
    }
  }

  const configuracion = mensajesConfirmacion[nuevoEstado]
  if (!configuracion) return

  confirm.require({
    message: {
      header: configuracion.message,
      content: configuracion.detail
    },
    header: configuracion.header,
    icon: 'pi pi-exclamation-triangle',
    position: 'center',
    rejectClass: 'bg-blue-500 hover:bg-blue-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2',
    acceptClass: 'bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 ml-3',
    rejectLabel: '✓ Continuar',
    acceptLabel: '✕ Cancelar',
    accept: () => {
      // No hacer nada si se cancela (ahora accept es cancelar)
    },
    reject: async () => {
      // Ejecutar cambio de estado (ahora reject es continuar)
      await ejecutarCambioEstado(nuevoEstado)
    }
  })
}

// Función separada para ejecutar el cambio de estado
const ejecutarCambioEstado = async (nuevoEstado) => {
  accionesTourLoading.value.cambiarEstado = true

  try {
    const response = await axios.put(`/api/tours/${tourActual.value.id}/cambiar-estado`, {
      estado: nuevoEstado
    })

    tourActual.value.estado = nuevoEstado

    toast.add({
      severity: 'success',
      summary: 'Estado actualizado',
      detail: `El tour ahora está ${nuevoEstado.toLowerCase()}`,
      life: 3000
    })

    // Recargar reservas para reflejar cambios
    await cargarReservas()

  } catch (error) {
    console.error('Error actualizando estado:', error)
    console.error('Respuesta del servidor:', error.response?.data)

    // Manejo específico para errores de validación (422)
    if (error.response?.status === 422) {
      toast.add({
        severity: 'warn',
        summary: 'Validación fallida',
        detail: error.response.data.message || 'No se pudo actualizar el estado',
        life: 8000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'No se pudo actualizar el estado',
        life: 4000
      })
    }
  } finally {
    accionesTourLoading.value.cambiarEstado = false
  }
}

// Función para manejar actualización de estado del tour
const handleEstadoTourActualizado = async (tourActualizado) => {
  tourActual.value = tourActualizado
  modalCambiarEstadoTour.value = false

  // Mensaje específico según el estado
  const mensajes = {
    'CANCELADA': {
      severity: 'warn',
      summary: 'Tour cancelado',
      detail: `El tour ha sido cancelado. Se enviaron notificaciones de cancelación a ${tourActualizado.reservas_canceladas || 0} cliente(s) con reservas.`
    },
    'FINALIZADO': {
      severity: 'success',
      summary: 'Tour finalizado',
      detail: `El tour ha sido finalizado correctamente. Se enviaron notificaciones a ${tourActualizado.reservas_finalizadas || 0} cliente(s).`
    },
    default: {
      severity: 'success',
      summary: 'Estado actualizado',
      detail: 'El estado del tour se ha actualizado correctamente'
    }
  }

  const mensaje = mensajes[tourActualizado.estado] || mensajes.default

  toast.add({
    severity: mensaje.severity,
    summary: mensaje.summary,
    detail: mensaje.detail,
    life: 5000
  })

  // Recargar reservas para reflejar cambios
  await cargarReservas()
}

// Función para limpiar el estado del scroll
const limpiarScrollModal = () => {
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

// Función para cerrar todos los modales
const cerrarTodosLosModales = () => {
  modalMasAcciones.value = false
  modalRechazar.value = false
  modalCancelarTour.value = false
  modalDetalles.value = false
  modalCambiarEstadoTour.value = false
  modalReprogramarTour.value = false

  // Limpiar variables relacionadas
  reservaSeleccionada.value = null
  tourSeleccionado.value = null
  motivoRechazo.value = ''
  motivoCancelacionTour.value = ''

  // Limpiar estados de loading
  cancelandoTour.value = false

  // Restaurar scroll
  limpiarScrollModal()
}// Función para manejar reprogramación
const handleReprogramarTour = async () => {
  if (!datosReprogramacion.value.fechaSalida || !datosReprogramacion.value.fechaRegreso) {
    toast.add({
      severity: 'warn',
      summary: 'Campos requeridos',
      detail: 'Debe seleccionar las nuevas fechas de salida y regreso',
      life: 4000
    })
    return
  }

  if (!datosReprogramacion.value.motivo.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Motivo requerido',
      detail: 'Debe ingresar un motivo para la reprogramación',
      life: 4000
    })
    return
  }

  // Validar que la fecha de regreso sea al menos 1 hora después de la salida
  const diferenciaHoras = (datosReprogramacion.value.fechaRegreso - datosReprogramacion.value.fechaSalida) / (1000 * 60 * 60)
  if (diferenciaHoras < 1) {
    toast.add({
      severity: 'warn',
      summary: 'Fechas inválidas',
      detail: 'La fecha y hora de regreso debe ser al menos 1 hora después de la salida',
      life: 4000
    })
    return
  }

  accionesTourLoading.value.cambiarEstado = true

  try {
    // Formatear fechas para envío al servidor
    const formatearFechaISO = (fecha) => {
      if (fecha instanceof Date) {
        // Usar zona horaria local en lugar de UTC
        const year = fecha.getFullYear()
        const month = String(fecha.getMonth() + 1).padStart(2, '0')
        const day = String(fecha.getDate()).padStart(2, '0')
        const hours = String(fecha.getHours()).padStart(2, '0')
        const minutes = String(fecha.getMinutes()).padStart(2, '0')
        const seconds = String(fecha.getSeconds()).padStart(2, '0')
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
      }
      return fecha
    }

    // Reprogramar el tour y sus reservas
    const response = await axios.put(`/api/tours/${tourActual.value.id}/cambiar-estado`, {
      estado: 'REPROGRAMADA',
      fecha_salida: formatearFechaISO(datosReprogramacion.value.fechaSalida),
      fecha_regreso: formatearFechaISO(datosReprogramacion.value.fechaRegreso),
      motivo_reprogramacion: datosReprogramacion.value.motivo,
      reprogramar_reservas: true // Indicar que también se reprogramen las reservas
    })

    tourActual.value = { ...tourActual.value, ...response.data }
    cerrarTodosLosModales() // Usar función centralizada para cerrar modales

    // Mostrar información sobre reservas reprogramadas
    const reservasReprogramadas = response.data.reservas_reprogramadas || 0

    toast.add({
      severity: 'success',
      summary: 'Tour y reservas reprogramados',
      detail: `El tour ha sido reprogramado correctamente. ${reservasReprogramadas} reserva(s) fueron reprogramada(s) y se enviaron notificaciones por correo.`,
      life: 5000
    })

    await cargarReservas()

  } catch (error) {
    console.error('Error reprogramando tour:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo reprogramar el tour',
      life: 4000
    })
  } finally {
    accionesTourLoading.value.cambiarEstado = false
  }
}

// Función para manejar el click en volver a Tours
const handleVolverATours = () => {
  loadingVolver.value = true
}

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

// Función para cargar reservas de un tour específico
const cargarReservas = async () => {
  if (!tourId.value) return

  loading.value = true
  try {
    const params = {
      busqueda: filtros.value.busqueda || undefined,
      fecha_inicio: filtros.value.fechaDesde || undefined,
      fecha_fin: filtros.value.fechaHasta || undefined,
      tour_id: tourId.value
    }
    const response = await axios.get('/api/reservas', { params, withCredentials: true })
    reservas.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
  } catch (error) {
    console.error('Error cargando reservas:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las reservas del tour',
      life: 4000
    })
    reservas.value = []
  } finally {
    loading.value = false
  }
}

// Función de cargar reservas con toasts removida - se usa cargarReservas directamente

// Función cargarTours removida - ya no se necesita cargar lista completa

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

// Función para abrir modal de cancelar tour completo
const abrirModalCancelarTour = () => {
  cerrarTodosLosModales() // Cerrar todos los modales primero

  motivoCancelacionTour.value = ''
  modalCancelarTour.value = true
}

// Función para cancelar tour completo
const cancelarTourCompleto = async () => {
  if (!motivoCancelacionTour.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Campo requerido',
      detail: 'Debe ingresar un motivo para cancelar el tour',
      life: 4000
    })
    return
  }

  cancelandoTour.value = true

  try {
    // Cancelar tour completo y todas sus reservas
    const response = await axios.put(`/api/tours/${tourActual.value.id}/cambiar-estado`, {
      estado: 'CANCELADA',
      motivo_cancelacion: motivoCancelacionTour.value,
      cancelar_reservas: true
    })

    // Actualizar estado del tour
    tourActual.value.estado = 'CANCELADA'

    // Recargar reservas para mostrar los cambios
    await cargarReservas()

    cerrarTodosLosModales()

    const reservasCanceladas = response.data.reservas_canceladas || 0
    toast.add({
      severity: 'warn',
      summary: '¡Tour Cancelado!',
      detail: `El tour ha sido cancelado. Se cancelaron ${reservasCanceladas} reserva(s) y se enviaron notificaciones por correo.`,
      life: 5000
    })
  } catch (error) {
    console.error('Error cancelando tour:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo cancelar el tour',
      life: 4000
    })
  } finally {
    cancelandoTour.value = false
  }
}

// Función para abrir modal de reprogramación
// ELIMINADO: abrirModalReprogramar - ya no se reprograma individualmente

// ELIMINADO: reprogramarReserva - ahora se hace a nivel de TOUR

// ELIMINADO: finalizarReserva - ahora se hace a nivel de TOUR

// Función para ver detalles de reserva
const verDetallesReserva = (reserva) => {
  cerrarTodosLosModales() // Cerrar todos los modales primero

  reservaSeleccionada.value = reserva
  modalDetalles.value = true
}

// Función para abrir modal de más acciones
const abrirModalMasAcciones = (reserva) => {
  cerrarTodosLosModales() // Cerrar todos los modales primero

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

// Función para obtener acciones disponibles según el estado (estados unificados)
// NOTA: Se eliminó 'reprogramar' y 'finalizar' - ahora solo se maneja a nivel TOUR
const getAccionesDisponibles = (reserva) => {
  switch (reserva.estado) {
    case 'PENDIENTE':
      return ['confirmar', 'rechazar', 'detalles']
    case 'CONFIRMADA':
      return ['rechazar', 'detalles']
    case 'EN_CURSO':
      return ['detalles']
    case 'REPROGRAMADA':
      return ['rechazar', 'detalles']
    case 'CANCELADA':
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

// Función para obtener label formateado del estado de tour
const getLabelEstadoTour = (estado) => {
  const estadoObj = estadosTours.find(e => e.value === estado)
  return estadoObj?.label || estado
}

// Función para formatear fecha
const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A'

  const fechaObj = new Date(fecha)

  // Si la hora es 00:00, mostrar solo la fecha
  if (fechaObj.getHours() === 0 && fechaObj.getMinutes() === 0) {
    return fechaObj.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  }

  // Si tiene hora específica, mostrar fecha y hora en formato AM/PM
  return fechaObj.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
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
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

// Función para limpiar filtros (simplificada)
const limpiarFiltros = async () => {
  isClearingFilters.value = true

  try {
    filtros.value = {
      busqueda: '',
      fechaDesde: null,
      fechaHasta: null,
      estado: null
    }

    toast.add({
      severity: "success",
      summary: "Filtros limpiados",
      life: 1500
    })

  } finally {
    isClearingFilters.value = false
  }
}

// Función para obtener fecha mínima (hoy)
const getMinDate = () => {
  return new Date()
}

// Computed para fecha mínima de regreso segura
const minDateRegreso = computed(() => {
  if (datosReprogramacion.value.fechaSalida instanceof Date) {
    // Permitir misma fecha, solo agregamos 1 hora mínima de diferencia
    const minDate = new Date(datosReprogramacion.value.fechaSalida)
    minDate.setHours(minDate.getHours() + 1) // Al menos una hora después
    return minDate
  }
  return new Date()
})

// Funciones recargarReservasWithToasts y ejecutarFinalizacionAutomatica removidas

// Funciones para manejar eventos del componente Modales
const handleConfirmarReserva = async (reserva) => {
  confirmandoReserva.value = true
  try {
    await axios.put(`/api/reservas/${reserva.id}/confirmar`)

    // Actualizar estado local solamente (sin recargar)
    const index = reservas.value.findIndex(r => r.id === reserva.id)
    if (index !== -1) {
      reservas.value[index].estado = 'CONFIRMADA'
    }

    // Cerrar el modal después de completar la operación
    modalMasAcciones.value = false

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

const handleRechazarReserva = async (data) => {
  rechazandoReserva.value = true
  procesando.value = true
  try {
    const response = await axios.put(`/api/reservas/${data.reserva.id}/rechazar`, {
      motivo: data.motivo
    })

    // Eliminar la reserva de la lista local (el backend siempre elimina al rechazar)
    const index = reservas.value.findIndex(r => r.id === data.reserva.id)
    if (index !== -1) {
      reservas.value.splice(index, 1) // Eliminar de la lista
    }

    modalRechazar.value = false
    modalMasAcciones.value = false
    toast.add({
      severity: 'success',
      summary: '¡Eliminada!',
      detail: `Reserva de ${data.reserva.cliente?.user?.name || data.reserva.cliente?.nombres} eliminada correctamente`,
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

// ELIMINADO: handleReprogramarReserva - ahora se hace a nivel de TOUR

// ELIMINADO: handleFinalizarReserva - ahora se hace a nivel de TOUR

const handleVerDetalles = (reserva) => {
  verDetallesReserva(reserva)
}

// Watch para recargar cuando cambien algunos filtros
watch(() => [filtros.value.busqueda, filtros.value.fechaDesde, filtros.value.fechaHasta, filtros.value.estado], () => {
  // Debounce para la búsqueda
  clearTimeout(window.searchTimeout)
  window.searchTimeout = setTimeout(() => {
    cargarReservas()
  }, 500)
}, { deep: true })

// Watch para controlar el scroll del body cuando se abra/cierre el modal
watch(modalReprogramarTour, (newValue) => {
  if (newValue) {
    // Bloquear scroll cuando se abre el modal
    document.body.style.overflow = 'hidden'
    document.body.style.paddingRight = '15px' // Compensar por scrollbar
  } else {
    // Restaurar scroll cuando se cierra el modal
    document.body.style.overflow = ''
    document.body.style.paddingRight = ''
  }
})

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

// Cargar datos iniciales para tour específico
onMounted(async () => {
  try {
    // Obtener ID del tour desde URL
    tourId.value = obtenerTourIdDesdeUrl()

    if (!tourId.value) {
      toast.add({
        severity: 'warn',
        summary: 'Tour no especificado',
        detail: 'Redirigiendo a la lista de tours...',
        life: 3000
      })
      setTimeout(() => {
        router.visit('/tours')
      }, 2000)
      return
    }

    // Cargar tour y sus reservas
    await Promise.all([cargarTour(tourId.value), cargarReservas()])
    forceSelectTruncation()
  } catch (error) {
    console.error('Error cargando datos iniciales:', error)
  }
})

// Limpiar el scroll cuando se desmonte el componente
onUnmounted(() => {
  limpiarScrollModal()
})
</script>


<template>
  <AuthenticatedLayout>
    <Head title="Gestión de Reservas" />
    <Toast class="z-[9999]" />
    <ConfirmDialog
      class="z-[9999]"
      :style="dialogStyle"
      :draggable="false"
      :closable="false"
    >
      <template #message="slotProps">
        <div class="flex items-center gap-3">
          <FontAwesomeIcon :icon="faExclamationTriangle" class="h-8 w-8 text-red-500" />
          <div class="flex flex-col">
            <span>{{ slotProps.message.header }}</span>
            <span class="text-red-600 text-sm font-medium mt-1">{{ slotProps.message.content }}</span>
          </div>
        </div>
      </template>
    </ConfirmDialog>

    <div class="container mx-auto px-4 py-6">
      <!-- Header con botón volver -->
      <div class="mb-6">
        <!-- Botón volver -->
        <Link
          :href="route('tours')"
          @click="handleVolverATours"
          :class="{'opacity-50 cursor-not-allowed': loadingVolver}"
          class="flex items-center text-blue-600 hover:text-blue-700 transition-colors duration-200 px-3 py-2 rounded-lg"
          title="Regresar a Tours"
        >
          <FontAwesomeIcon
            :icon="loadingVolver ? faSpinner : faArrowLeft"
            :class="{'animate-spin': loadingVolver}"
            class="h-5 w-5 mr-2"
          />
          <span class="font-medium">Volver a Tours</span>
        </Link>
      </div>

      <!-- Sección de contenido principal -->
      <div class="bg-white rounded-lg shadow-md">
        <!-- Estados del Tour (cambio directo) -->
        <div v-if="tourActual" class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-blue-600 mb-2" style="word-break: break-all; overflow-wrap: break-word; max-width: 100%; white-space: pre-wrap;">{{ tourActual.nombre }}</h3>
          <div class="mb-4 space-y-2">
            <p class="text-sm text-blue-600 flex items-center gap-2">
              <FontAwesomeIcon :icon="faInfoCircle" class="h-4 w-4 text-blue-500" />
              Haz clic en cualquier tarjeta para cambiar el estado del tour
            </p>
            <p v-if="!cumpleCupoMinimo && tourActual.estado === 'DISPONIBLE' && tieneReservasActivas" class="text-sm text-amber-600 flex items-center gap-2 bg-amber-50 p-2 rounded-md">
              <FontAwesomeIcon :icon="faExclamationTriangle" class="h-4 w-4 text-amber-500" />
              <span>
                Cupo mínimo no cumplido ({{ tourActual.cupo_min }} personas requeridas).
                Actualmente hay {{ reservasFiltradas.filter(r => r.estado === 'CONFIRMADA').reduce((total, r) => total + (r.mayores_edad || 0) + (r.menores_edad || 0), 0) }} personas confirmadas.
                Solo se puede cancelar el tour.
              </span>
            </p>
            <p v-if="tourActual.estado === 'COMPLETO' && !todasReservasConfirmadas" class="text-sm text-blue-600 flex items-center gap-2 bg-blue-50 p-3 rounded-md border border-blue-200">
              <FontAwesomeIcon :icon="faInfoCircle" class="h-4 w-4 text-blue-500" />
              <span class="font-medium">
                Tour completo - Debe confirmar TODAS las reservas pendientes para poder poner el tour en curso
              </span>
            </p>
            <p v-if="tourActual.estado === 'REPROGRAMADA' && !puedeIniciarCurso" class="text-sm text-purple-600 flex items-center gap-2 bg-purple-50 p-3 rounded-md border border-purple-200">
              <FontAwesomeIcon :icon="faInfoCircle" class="h-4 w-4 text-purple-500" />
              <span class="font-medium">
                Tour reprogramado - Confirme las reservas pendientes para habilitarlo en curso
              </span>
            </p>
            <p v-if="!tieneReservasActivas && (tourActual.estado === 'DISPONIBLE' || tourActual.estado === 'REPROGRAMADA')" class="text-sm text-gray-600 flex items-center gap-2 bg-gray-50 p-2 rounded-md">
              <FontAwesomeIcon :icon="faInfoCircle" class="h-4 w-4 text-gray-500" />
              <span>
                Este tour no tiene reservas activas.
              </span>
            </p>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:flex lg:flex-wrap gap-2 md:gap-3">
            <!-- Estado Actual -->
            <div class="bg-gray-100 border-2 border-gray-300 rounded-lg px-2 py-2 md:px-4 md:py-3 flex-1 min-w-0">
              <p class="text-xs md:text-sm text-gray-600 mb-1">Estado Actual</p>
              <p class="text-xs md:text-sm font-bold text-gray-800">{{ getLabelEstadoTour(tourActual.estado) }}</p>
            </div>

            <!-- En Curso -->
            <div
              v-if="tourActual.estado !== 'EN_CURSO' && tourActual.estado !== 'FINALIZADO' && tourActual.estado !== 'CANCELADA'"
              :class="[
                'rounded-lg px-2 py-2 md:px-4 md:py-3 flex-1 min-w-0 transition-all duration-200',
                puedeIniciarCurso
                  ? 'bg-gradient-to-r from-blue-500 to-blue-600 cursor-pointer hover:shadow-lg hover:scale-105'
                  : 'bg-gray-400 cursor-not-allowed opacity-60'
              ]"
              @click="puedeIniciarCurso ? cambiarEstadoDirecto('EN_CURSO') : null"
            >
              <div class="flex items-center gap-2 text-white">
                <div class="flex-1">
                  <p class="text-xs md:text-sm opacity-90">En Curso</p>
                </div>
                <FontAwesomeIcon
                  :icon="accionesTourLoading.cambiarEstado ? faSpinner : faCalendarDays"
                  :class="{'animate-spin': accionesTourLoading.cambiarEstado}"
                  class="text-xs md:text-sm opacity-75"
                />
              </div>
            </div>

            <!-- Finalizado -->
            <div
              v-if="tourActual.estado !== 'FINALIZADO' && tourActual.estado !== 'CANCELADA' && tourActual.estado === 'EN_CURSO'"
              class="bg-gradient-to-r from-gray-500 to-gray-600 cursor-pointer hover:shadow-lg transition-all duration-200 hover:scale-105 rounded-lg px-2 py-2 md:px-4 md:py-3 flex-1 min-w-0"
              @click="cambiarEstadoDirecto('FINALIZADO')"
            >
              <div class="flex items-center gap-2 text-white">
                <div class="flex-1">
                  <p class="text-xs md:text-sm opacity-90">Finalizar</p>
                </div>
                <FontAwesomeIcon
                  :icon="accionesTourLoading.cambiarEstado ? faSpinner : faCalendarCheck"
                  :class="{'animate-spin': accionesTourLoading.cambiarEstado}"
                  class="text-xs md:text-sm opacity-75"
                />
              </div>
            </div>

            <!-- Reprogramar (con modal) - NO mostrar cuando está REPROGRAMADA -->
            <div
              v-if="tourActual.estado !== 'REPROGRAMADA' && tourActual.estado !== 'FINALIZADO' && tourActual.estado !== 'CANCELADA' && tourActual.estado !== 'EN_CURSO'"
              :class="[
                'rounded-lg px-2 py-2 md:px-4 md:py-3 flex-1 min-w-0 transition-all duration-200',
                (
                  (tourActual.estado === 'DISPONIBLE' && cumpleCupoMinimo) ||
                  (tourActual.estado === 'COMPLETO' && todasReservasConfirmadas)
                )
                  ? 'bg-gradient-to-r from-purple-500 to-purple-600 cursor-pointer hover:shadow-lg hover:scale-105'
                  : 'bg-gray-400 cursor-not-allowed opacity-60'
              ]"
              @click="(
                (tourActual.estado === 'DISPONIBLE' && cumpleCupoMinimo) ||
                (tourActual.estado === 'COMPLETO' && todasReservasConfirmadas)
              ) ? cambiarEstadoDirecto('REPROGRAMADA') : null"
            >
              <div class="flex items-center gap-2 text-white">
                <div class="flex-1">
                  <p class="text-xs md:text-sm opacity-90">Reprogramar</p>
                </div>
                <FontAwesomeIcon
                  :icon="accionesTourLoading.cambiarEstado ? faSpinner : faCalendarDays"
                  :class="{'animate-spin': accionesTourLoading.cambiarEstado}"
                  class="text-xs md:text-sm opacity-75"
                />
              </div>
            </div>

            <!-- Cancelar (con modal) - También disponible para REPROGRAMADA -->
            <div
              v-if="tourActual.estado !== 'CANCELADA' && tourActual.estado !== 'FINALIZADO' && tourActual.estado !== 'EN_CURSO'"
              :class="[
                'rounded-lg px-2 py-2 md:px-4 md:py-3 flex-1 min-w-0 transition-all duration-200',
                (
                  tieneReservasActivas ||
                  (tourActual.estado === 'COMPLETO') ||
                  (tourActual.estado === 'REPROGRAMADA')
                )
                  ? 'bg-gradient-to-r from-red-500 to-red-600 cursor-pointer hover:shadow-lg hover:scale-105'
                  : 'bg-gray-400 cursor-not-allowed opacity-60'
              ]"
              @click="(
                tieneReservasActivas ||
                (tourActual.estado === 'COMPLETO') ||
                (tourActual.estado === 'REPROGRAMADA')
              ) ? cambiarEstadoDirecto('CANCELADA') : null"
              :title="(
                !tieneReservasActivas && tourActual.estado !== 'COMPLETO'
              ) ? 'No se puede cancelar un tour sin reservas activas' : 'Cancelar tour y todas sus reservas'"
            >
              <div class="flex items-center gap-2 text-white">
                <div class="flex-1">
                  <p class="text-xs md:text-sm opacity-90">Cancelar</p>
                  <p v-if="!tieneReservasActivas" class="text-xs opacity-70">Sin reservas</p>
                </div>
                <FontAwesomeIcon
                  :icon="accionesTourLoading.cambiarEstado ? faSpinner : faXmark"
                  :class="{'animate-spin': accionesTourLoading.cambiarEstado}"
                  class="text-xs md:text-sm opacity-75"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Filtros simplificados -->
        <div class="bg-blue-50 p-3 rounded-lg shadow-sm border mb-4 mx-4">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-base font-medium text-gray-800 flex items-center gap-2">
                <i class="pi pi-filter text-blue-600 text-sm"></i><span>Filtros</span>
              </h3>
              <div class="bg-blue-50 border border-blue-200 text-blue-700 px-2 py-1 rounded text-xs font-medium">
                {{ reservasFiltradas.length }} resultado{{ reservasFiltradas.length !== 1 ? 's' : '' }}
              </div>
            </div>
            <button
              @click="limpiarFiltros"
              :disabled="isClearingFilters"
              class="bg-red-500 hover:bg-red-600 border border-red-500 px-2 py-1 text-xs text-white shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1 shrink-0"
            >
              <FontAwesomeIcon
                v-if="isClearingFilters"
                :icon="faSpinner"
                class="animate-spin h-3 w-3"
              />
              <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar' }}</span>
            </button>
          </div>
          <div class="space-y-3">
            <div class="relative">
              <FontAwesomeIcon :icon="faMagnifyingGlass" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <InputText
                v-model="filtros.busqueda"
                placeholder="Buscar por cliente..."
                class="w-full h-9 text-sm rounded-md pl-10"
                style="background-color: white; border-color: #93c5fd;"
              />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <DatePicker
                v-model="filtros.fechaDesde"
                placeholder="Fecha desde"
                dateFormat="dd/mm/yy"
                class="w-full h-9 text-sm"
                style="background-color: white; border-color: #93c5fd;"
                showIcon
              />
              <DatePicker
                v-model="filtros.fechaHasta"
                placeholder="Fecha hasta"
                dateFormat="dd/mm/yy"
                class="w-full h-9 text-sm"
                style="background-color: white; border-color: #93c5fd;"
                showIcon
              />
              <Select
                v-model="filtros.estado"
                :options="[
                  { label: 'Todos los estados', value: null },
                  { label: 'Pendientes', value: 'PENDIENTE' },
                  { label: 'Confirmadas', value: 'CONFIRMADA' }
                ]"
                optionLabel="label"
                optionValue="value"
                placeholder="Filtrar por estado"
                class="w-full h-9 text-sm"
                style="background-color: white; border-color: #93c5fd;"
              />
            </div>
            <!-- Texto de ayuda para la tabla -->
            <div class="px-1 mt-3">
              <p class="text-blue-600 text-xs font-medium flex items-center gap-1">
                <FontAwesomeIcon :icon="faHandPointUp" class="h-3 w-3 text-yellow-500" />
                Haz clic en cualquier fila para ver los detalles.
            </p>
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
              <Column field="fecha_reserva" header="Fecha" class="w-28 min-w-16">
                <template #body="slotProps">
                  <span class="font-medium text-xs sm:text-sm">{{ formatearFecha(slotProps.data.fecha_reserva) }}</span>
                </template>
              </Column>

              <!-- Columna Cliente -->
              <Column field="cliente.nombres" header="Cliente" class="w-16 hidden sm:table-cell">
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

              <!-- Columna Personas -->
              <Column header="Personas" class="w-20 sm:w-24 lg:w-28 min-w-16 hidden md:table-cell">
                <template #body="slotProps">
                  <div>
                    <div class="text-xs sm:text-sm">
                      <FontAwesomeIcon :icon="faUsers" class="text-gray-400 text-xs mr-1" />
                      <span class="font-medium">{{ (slotProps.data.mayores_edad || 0) + (slotProps.data.menores_edad || 0) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ slotProps.data.mayores_edad || 0 }}A • {{ slotProps.data.menores_edad || 0 }}N
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Columna Total -->
              <Column field="total" header="Total" class="w-12 min-w-12">
                <template #body="slotProps">
                  <span class="font-medium text-green-600 text-xs sm:text-sm">
                    ${{ Number(slotProps.data.total || 0).toFixed(2) }}
                  </span>
                </template>
              </Column>

              <!-- Columna Estado -->
              <Column field="estado" header="Estado" class="w-24 sm:w-28 lg:w-36 min-w-20 hidden lg:table-cell">
                <template #body="slotProps">
                  <span :class="getColorEstadoReserva(slotProps.data.estado)" class="px-2 py-1 rounded-full text-xs font-medium">
                    <span class="hidden xl:inline">{{ estadosReservas.find(e => e.value === slotProps.data.estado)?.label || slotProps.data.estado }}</span>
                    <span class="xl:hidden">{{ (estadosReservas.find(e => e.value === slotProps.data.estado)?.label || slotProps.data.estado).substring(0, 4) }}</span>
                  </span>
                </template>
              </Column>

              <!-- Columna Acciones -->
              <Column header="Acciones" class="w-24 sm:w-28 lg:w-32 min-w-20">
                <template #body="slotProps">
                  <!-- Botón Más Acciones -->
                  <button
                    class="bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded-md transition-all duration-200 ease-in-out inline-flex items-center gap-1 text-xs shadow-md hover:shadow-lg hover:-translate-y-1"
                    @click="abrirModalMasAcciones(slotProps.data)"
                    title="Más acciones"
                  >
                    <FontAwesomeIcon :icon="faListDots" class="h-3 w-4 sm:h-4 sm:w-5" />
                    <span class="hidden md:inline">Más</span>
                  </button>
                </template>
              </Column>
            </DataTable>

      <!-- Componente de Modales -->
      <ReservaModales
        v-model:visible="modalMasAcciones"
        v-model:detalles-visible="modalDetalles"
        v-model:rechazar-visible="modalRechazar"
        :reserva="reservaSeleccionada"
        :tour="tourSeleccionado"
        :dialog-style="dialogStyle"
        :procesando="procesando"
        :confirmando-reserva="confirmandoReserva"
        :rechazando-reserva="rechazandoReserva"
        :estados-reservas="estadosReservas"
        :estados-tours="estadosTours"
        @confirmar="handleConfirmarReserva"
        @rechazar="handleRechazarReserva"
        @ver-detalles="handleVerDetalles"
      />

      <!-- Modal de Cambiar Estado del Tour -->
      <CambiarEstado
        v-model:visible="modalCambiarEstadoTour"
        :tour="tourActual"
        :dialog-style="dialogStyle"
        @estado-actualizado="handleEstadoTourActualizado"
      />

      <!-- Modal de Cancelar Tour Completo -->
      <Dialog
        v-model:visible="modalCancelarTour"
        modal
        header="Cancelar Tour Completo"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
      >
        <div class="space-y-4">
          <!-- Advertencia sobre eliminación permanente -->
          <div class="bg-orange-50 p-4 rounded-lg border border-orange-200 mb-4">
            <div class="flex items-start gap-3">
              <FontAwesomeIcon :icon="faExclamationTriangle" class="text-orange-600 text-lg mt-1 flex-shrink-0" />
              <div>
                <h4 class="font-bold text-orange-800 mb-2">¡ADVERTENCIA - ELIMINACIÓN MASIVA PERMANENTE!</h4>
                <div class="text-sm text-orange-700 space-y-2">
                  <p><strong>• Esta acción NO se puede deshacer bajo ninguna circunstancia</strong></p>
                  <p>• Se eliminarán TODAS las reservas del tour de forma PERMANENTE</p>
                  <p>• Se eliminarán TODOS los detalles asociados a cada reserva</p>
                  <p>• Los cupos del tour serán liberados automáticamente</p>
                  <p>• Se enviarán emails de cancelación a TODOS los clientes</p>
                  <p>• No será posible recuperar ninguna información posteriormente</p>
                  <div class="mt-3 p-2 bg-orange-100 rounded border border-orange-300">
                    <p class="font-semibold text-orange-900 text-xs">
                      Asegúrese de que realmente desea eliminar toda la información antes de continuar
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Información del tour -->
          <div class="bg-red-50 p-4 rounded-lg border border-red-200">
            <div class="flex items-center gap-2 mb-2">
              <FontAwesomeIcon :icon="faExclamationTriangle" class="text-red-600" />
              <h4 class="font-semibold text-red-800">Cancelar Tour: {{ tourActual?.nombre }}</h4>
            </div>
            <p class="text-sm text-red-700">
              Esta acción cancelará el tour completo y TODAS las reservas asociadas.
              Se enviarán notificaciones por correo a todos los clientes.
            </p>
            <p class="text-xs text-red-600 mt-2">
              <strong>Reservas que serán ELIMINADAS PERMANENTEMENTE: {{ reservas.length }}</strong>
            </p>
          </div>

          <!-- Motivo de la cancelación -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Motivo de la Cancelación del Tour <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="motivoCancelacionTour"
              placeholder="Explique por qué se cancela el tour completo..."
              class="w-full p-3 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
              rows="4"
              maxlength="500"
            ></textarea>
            <small class="text-gray-500">{{ motivoCancelacionTour.length }}/500 caracteres</small>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              @click="modalCancelarTour = false"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              @click="cancelarTourCompleto"
              :disabled="!motivoCancelacionTour.trim() || cancelandoTour"
              class="px-4 py-2 bg-red-700 text-white rounded-md hover:bg-red-800 disabled:bg-red-300 flex items-center gap-2"
            >
              <FontAwesomeIcon
                :icon="cancelandoTour ? faSpinner : faXmark"
                :class="{'animate-spin': cancelandoTour}"
              />
              {{ cancelandoTour ? 'Eliminando Todo...' : 'Eliminar Tour y Reservas Definitivamente' }}
            </button>
          </div>
        </div>
      </Dialog>

      <!-- Modal específico para Reprogramar -->
      <Dialog
        v-model:visible="modalReprogramarTour"
        modal
        header="Reprogramar Tour"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
      >
        <div class="space-y-4">
          <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <h4 class="font-semibold text-blue-800 mb-2">{{ tourActual?.nombre }}</h4>
            <p class="text-sm text-blue-600">
              Fechas actuales: {{ formatearFechaHora(tourActual?.fecha_salida) }} - {{ formatearFechaHora(tourActual?.fecha_regreso) }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Salida:</label>
              <DatePicker
                v-model="datosReprogramacion.fechaSalida"
                showTime
                hourFormat="12"
                dateFormat="dd/mm/yy"
                showIcon
                placeholder="Seleccionar fecha y hora"
                class="w-full"
                :minDate="new Date()"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Regreso:</label>
              <DatePicker
                v-model="datosReprogramacion.fechaRegreso"
                showTime
                hourFormat="12"
                dateFormat="dd/mm/yy"
                showIcon
                placeholder="Seleccionar fecha y hora"
                class="w-full"
                :minDate="minDateRegreso"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de la Reprogramación</label>
            <textarea
              v-model="datosReprogramacion.motivo"
              placeholder="Ingrese el motivo de la reprogramación..."
              class="w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              rows="3"
            ></textarea>
            <p class="text-xs text-gray-500 mt-2">
              💡 Al reprogramar el tour, todas las reservas asociadas serán reprogramadas automáticamente y se enviarán correos de notificación a los clientes.
            </p>
          </div>

          <div class="flex justify-center items-center gap-3 pt-4">

            <button
              @click="handleReprogramarTour"
              :disabled="accionesTourLoading.cambiarEstado"
              class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <FontAwesomeIcon
                v-if="accionesTourLoading.cambiarEstado"
                :icon="faSpinner"
                class="animate-spin"
              />
              {{ accionesTourLoading.cambiarEstado ? 'Reprogramando...' : 'Reprogramar Tour' }}
            </button>
            <button
              @click="modalReprogramarTour = false; limpiarScrollModal()"
              class="border rounded-md bg-blue-500 hover:bg-blue-700 text-white px-6 py-2"
              :disabled="accionesTourLoading.cambiarEstado"
            >
              Cancelar
            </button>
          </div>
        </div>
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

/* Forzar alineación con ::deep() */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  vertical-align: middle !important;
  padding: 1rem 0.5rem !important;
  height: 60px !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  vertical-align: middle !important;
  padding: 1rem 0.5rem !important;
}

/* Alineación específica por columna con ::deep() - Headers */
:deep(.p-datatable .p-datatable-thead > tr > th:nth-child(1)) {
  text-align: center !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th:nth-child(2)) {
  text-align: left !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th:nth-child(3)) {
  text-align: center !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th:nth-child(4)) {
  text-align: right !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th:nth-child(5)) {
  text-align: center !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th:nth-child(6)) {
  text-align: center !important;
}

/* Alineación específica por columna con ::deep() - Contenido */
:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(1)) {
  text-align: center !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(2)) {
  text-align: left !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(3)) {
  text-align: center !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(4)) {
  text-align: right !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(5)) {
  text-align: center !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(6)) {
  text-align: center !important;
}

/* Responsive table styles */
@media (max-width: 640px) {
  .p-datatable .p-datatable-thead > tr > th,
  .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.5rem 0.25rem !important;
  }
}

@media (max-width: 768px) {
  .p-datatable .p-datatable-thead > tr > th,
  .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.75rem 0.5rem !important;
  }
}
</style>
