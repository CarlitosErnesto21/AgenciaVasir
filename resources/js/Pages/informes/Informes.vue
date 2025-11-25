<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import DatePicker from 'primevue/datepicker'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  faFileLines,
  faCalendarDays,
  faUsers,
  faShoppingCart,
  faChartLine,
  faDownload,
  faSearch,
  faTimes,
  faCheck,
  faCogs,
  faRefresh,
  faExclamationCircle,
  faExclamationTriangle,
  faSpinner,
  faUser,
  faFileAlt,
  faChartBar
} from '@fortawesome/free-solid-svg-icons'

// Reactive window size for responsive iframe
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

// Computed property for responsive iframe height
const iframeHeight = computed(() => {
  if (windowWidth.value < 768) {
    return '400' // Mobile: 400px
  } else if (windowWidth.value < 1024) {
    return '500' // Tablet: 500px
  } else {
    return '700' // Desktop: 700px
  }
})

// Update window width on resize
const updateWindowWidth = () => {
  if (typeof window !== 'undefined') {
    windowWidth.value = window.innerWidth
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    // Actualizar el valor inicial una vez montado
    windowWidth.value = window.innerWidth
    window.addEventListener('resize', updateWindowWidth)
  }

  // Agregar event listener al input para evitar problemas de re-renderizado de Vue
  nextTick(() => {
    const input = searchInputRef.value
    if (input) {
      input.addEventListener('input', handleSearchInput)
    }
  })
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', updateWindowWidth)
  }

  // Limpiar timeout al desmontar el componente
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  // Limpiar event listener del input
  const input = searchInputRef.value
  if (input) {
    input.removeEventListener('input', handleSearchInput)
  }
})

function generarMeses() {
  const meses = []
  const nombres = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ]
  let fecha = new Date(2022, 0, 1)
  const hoy = new Date()
  while (fecha <= hoy) {
    const value = `${fecha.getFullYear()}-${(fecha.getMonth() + 1).toString().padStart(2, '0')}`
    const label = `${nombres[fecha.getMonth()]} ${fecha.getFullYear()}`
    meses.push({ value, label })
    fecha.setMonth(fecha.getMonth() + 1)
  }
  return meses
}

const mesesDisponibles = generarMeses()
const desde = ref(null)
const hasta = ref(null)
const today = new Date()
const modoSeleccion = ref('unico')
const mesUnico = ref(null)
const pdfUrl = ref(null)
const toast = useToast()

// Variables para búsqueda de clientes
const clienteSeleccionado = ref(null)
const busquedaCliente = ref('')
const clientesEncontrados = ref([])
const buscandoClientes = ref(false)
const searchInputRef = ref(null)
let searchTimeout = null

// Nuevo: Tipo de informe
const tipoInforme = ref('tours')
const tiposInformes = [
  {
    id: 'tours',
    nombre: 'Informe de Tours',
    descripcion: 'Cupos vendidos mensuales por tour',
    icono: faChartLine,
    endpoint: `${window.location.origin}/descargar-informe`,
    requiereFechas: true,
    requiereCliente: false,
    color: 'red'
  },
  {
    id: 'inventario',
    nombre: 'Informe de Inventario',
    descripcion: 'Estado del inventario, stock y movimientos',
    icono: faFileLines,
    endpoint: `${window.location.origin}/descargar-informe-inventario`,
    requiereFechas: false,
    requiereCliente: false,
    color: 'green'
  },
  {
    id: 'reservas-cliente',
    nombre: 'Reservas de Cliente',
    descripcion: 'Historial de reservas de un cliente específico',
    icono: faUsers,
    endpoint: `${window.location.origin}/descargar-informe-reservas-cliente`,
    requiereFechas: false,
    requiereCliente: true,
    color: 'blue'
  },
  {
    id: 'ventas-cliente',
    nombre: 'Ventas de Cliente',
    descripcion: 'Historial de compras de un cliente específico',
    icono: faShoppingCart,
    endpoint: `${window.location.origin}/descargar-informe-ventas-cliente`,
    requiereFechas: false,
    requiereCliente: true,
    color: 'purple'
  }
]

// Fecha mínima para el Calendar de mes único (marzo 2019)
const minMesUnico = new Date(2019, 2, 1) // Mes 2 = marzo (0-indexed)

const formatMonth = date =>
  date ? `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}` : ''

const mesesFiltrados = computed(() => {
  if (!desde.value || !hasta.value) return []
  const desdeStr = formatMonth(desde.value)
  const hastaStr = formatMonth(hasta.value)
  const desdeIdx = mesesDisponibles.findIndex(m => m.value === desdeStr)
  const hastaIdx = mesesDisponibles.findIndex(m => m.value === hastaStr)
  if (desdeIdx === -1 || hastaIdx === -1 || desdeIdx > hastaIdx) return []
  return mesesDisponibles.slice(desdeIdx, hastaIdx + 1).map(m => m.value)
})

const informeSeleccionado = computed(() => {
  return tiposInformes.find(t => t.id === tipoInforme.value)
})

const requiereCliente = computed(() => {
  return informeSeleccionado.value?.requiereCliente || false
})

const requiereFechas = computed(() => {
  return informeSeleccionado.value?.requiereFechas || false
})

const puedeGenerar = computed(() => {
  // Si requiere cliente, verificar que esté seleccionado
  if (requiereCliente.value && !clienteSeleccionado.value) {
    return false
  }

  // Si el informe no requiere fechas, verificar si solo necesita cliente o nada más
  if (!requiereFechas.value) {
    return requiereCliente.value ? !!clienteSeleccionado.value : true
  }

  // Si requiere fechas, validar según el modo de selección
  const fechasValidas = modoSeleccion.value === 'unico' ? !!mesUnico.value : !!(desde.value && hasta.value && mesesFiltrados.value.length)

  return fechasValidas && (requiereCliente.value ? !!clienteSeleccionado.value : true)
});

function showToast(type, summary, detail, life = 3500) {
  toast.add({ severity: type, summary, detail, life })
}

// Función para manejar cambios en la búsqueda (con debounce)
function handleSearchInput(event) {
  const value = event.target.value

  // Limpiar el timeout anterior
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  // Si no hay texto suficiente, limpiar resultados inmediatamente
  if (!value || value.length < 2) {
    clientesEncontrados.value = []
    busquedaCliente.value = value
    return
  }

  // Establecer nuevo timeout para buscar
  searchTimeout = setTimeout(() => {
    busquedaCliente.value = value
    buscarClientes()
  }, 300)
}// Función para buscar clientes
async function buscarClientes() {
  const query = busquedaCliente.value

  if (!query || query.length < 2) {
    clientesEncontrados.value = []
    return
  }

  // Deshabilitar el input manualmente para evitar re-render
  if (searchInputRef.value) {
    searchInputRef.value.disabled = true
  }

  buscandoClientes.value = true

  try {
    const response = await fetch(`/api/clientes/buscar?q=${encodeURIComponent(query)}`)
    const data = await response.json()
    clientesEncontrados.value = data.clientes || []
  } catch (error) {
    console.error('Error buscando clientes:', error)
    showToast('error', 'Error', 'Error al buscar clientes')
    clientesEncontrados.value = []
  } finally {
    buscandoClientes.value = false

    // Habilitar el input manualmente
    if (searchInputRef.value) {
      searchInputRef.value.disabled = false

      // Restaurar foco de manera más agresiva
      setTimeout(() => {
        if (document.activeElement !== searchInputRef.value) {
          searchInputRef.value.focus()
          // Si aún no funciona, intentar con más delay
          setTimeout(() => {
            if (document.activeElement !== searchInputRef.value) {
              searchInputRef.value.focus()
            }
          }, 50)
        }
      }, 10)
    }
  }
}

// Watcher para restaurar foco automáticamente después de las búsquedas
watch(buscandoClientes, (newVal, oldVal) => {
  if (newVal === false && oldVal === true) {
    // Cuando termina la búsqueda, restaurar el foco si se perdió
    nextTick(() => {
      if (searchInputRef.value && document.activeElement !== searchInputRef.value) {
        searchInputRef.value.focus()
      }
    })
  }
})

// Función para seleccionar cliente
function seleccionarCliente(cliente) {
  // Limpiar timeout si existe
  if (searchTimeout) {
    clearTimeout(searchTimeout)
    searchTimeout = null
  }

  clienteSeleccionado.value = cliente
  busquedaCliente.value = cliente.user?.name || cliente.name || 'Cliente'
  clientesEncontrados.value = []
  buscandoClientes.value = false
}

// Función para limpiar selección de cliente
function limpiarCliente() {
  // Limpiar timeout si existe
  if (searchTimeout) {
    clearTimeout(searchTimeout)
    searchTimeout = null
  }

  clienteSeleccionado.value = null
  busquedaCliente.value = ''
  clientesEncontrados.value = []
  buscandoClientes.value = false
}

async function descargarPDF() {
  const informeSeleccionado = tiposInformes.find(t => t.id === tipoInforme.value)
  if (!informeSeleccionado) {
    showToast('error', 'Error', 'Tipo de informe no válido.')
    return
  }

  let params = new URLSearchParams()

  // Agregar cliente si es requerido
  if (informeSeleccionado.requiereCliente) {
    if (!clienteSeleccionado.value) {
      showToast('error', 'Error', 'Seleccione un cliente para generar el informe.')
      return
    }
    // Usar el ID del cliente o el ID del usuario según lo que esté disponible
    const clienteId = clienteSeleccionado.value.id || clienteSeleccionado.value.user_id
    params.append('cliente_id', clienteId)
  }

  // Solo agregar parámetros de fecha si el informe los requiere
  if (informeSeleccionado.requiereFechas) {
    let meses = []
    if (modoSeleccion.value === 'unico') {
      if (!mesUnico.value) {
        showToast('error', 'Error', 'Seleccione un mes para generar el informe.')
        return
      }
      meses = [formatMonth(mesUnico.value)]
    } else {
      if (!desde.value || !hasta.value || !mesesFiltrados.value.length) {
        showToast('error', 'Error', 'Seleccione un rango válido de meses para generar el informe.')
        return
      }
      meses = mesesFiltrados.value
    }
    meses.forEach(mes => params.append('meses[]', mes))
  }

  try {
    showToast('info', 'Generando informe...', '', 2000)
    const response = await fetch(`${informeSeleccionado.endpoint}?${params.toString()}`)
    const contentType = response.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      const errorData = await response.json()
      showToast('warn', 'Sin datos', errorData.message || 'No se encontraron datos para generar el informe', 5000)
      return
    }
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    // Detectar si es móvil o tablet
    const isMobileOrTablet = windowWidth.value < 1024
    if (isMobileOrTablet) {
      // Descargar el PDF directamente y usar el nombre del backend
      const blob = await response.blob()
      let filename = 'informe.pdf'
      const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition')
      if (disposition) {
        const match = disposition.match(/filename="?([^";]+)"?/i)
        if (match && match[1]) {
          filename = match[1]
        }
      }
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      a.remove()
      window.URL.revokeObjectURL(url)
      showToast('success', 'Descarga iniciada', `El informe PDF (${filename}) se está descargando.`, 2500)
    } else {
      // Abrir en nueva ventana (solo escritorio) y permitir vista previa
      window.open(`${informeSeleccionado.endpoint}?${params.toString()}&preview=1`, '_blank')
      showToast('success', 'Informe abierto', 'El informe se abrió en una nueva ventana.', 2500)
    }
  } catch (error) {
    console.error('Error al abrir el informe:', error)
    showToast('error', 'Error', 'Error al abrir el informe. Por favor, inténtelo de nuevo.', 5000)
  }
}

function limpiarFechas() {
  mesUnico.value = null
  desde.value = null
  hasta.value = null
  pdfUrl.value = null
  limpiarCliente()
}
</script>

<template>
  <Head title="Centro de Informes - VASIR" />
  <AuthenticatedLayout>
    <Toast />
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
        <!-- Header Section -->
        <div class="text-center mb-8 sm:mb-12">
          <div class="inline-flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-r from-red-600 to-red-500 rounded-xl sm:rounded-2xl mb-4 sm:mb-6 shadow-lg">
            <FontAwesomeIcon :icon="faChartBar" class="w-6 h-6 sm:w-8 sm:h-8 text-white" />
          </div>
          <h1 class="text-2xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-3 sm:mb-4 tracking-tight px-2">
            Centro de <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-red-500">Informes</span>
          </h1>
          <p class="text-base sm:text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed px-4">
            <span class="hidden sm:inline">Genere informes detallados sobre los tours más vendidos y la gestión de inventario en formato PDF.</span>
            <span class="sm:hidden">Genere informes detallados de tours e inventario en PDF.</span>
          </p>
        </div>

        <!-- Main Card -->
        <div class="max-w-4xl mx-auto">
          <div class="bg-white/80 backdrop-blur-sm rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl border border-white/20 overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-red-600 to-red-500 px-4 py-4 sm:px-8 sm:py-6">
              <h2 class="text-lg sm:text-2xl font-bold text-white flex items-center gap-2 sm:gap-3">
                <FontAwesomeIcon :icon="faCogs" class="w-5 h-5 sm:w-6 sm:h-6" />
                <span class="hidden sm:inline">Configuración del Informe</span>
                <span class="sm:hidden">Configurar Informe</span>
              </h2>
              <p class="text-red-100 mt-1 sm:mt-2 text-sm sm:text-base">
                <span class="hidden sm:inline">Seleccione el período para generar su informe personalizado</span>
                <span class="sm:hidden">Configure su informe PDF</span>
              </p>
            </div>

            <!-- Card Body -->
            <div class="p-4 sm:p-8 space-y-6 sm:space-y-8">
              <!-- Selector de Tipo de Informe -->
              <div class="space-y-3 sm:space-y-4">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <FontAwesomeIcon :icon="faFileAlt" class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                  <span class="hidden sm:inline">Seleccionar Tipo de Informe</span>
                  <span class="sm:hidden">Tipo de Informe</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                  <label v-for="informe in tiposInformes" :key="informe.id" class="relative cursor-pointer">
                    <input
                      type="radio"
                      v-model="tipoInforme"
                      :value="informe.id"
                      class="sr-only peer"
                    />
                    <div :class="`flex items-center p-3 sm:p-5 bg-gradient-to-r from-${informe.color}-50 to-${informe.color}-100 rounded-lg sm:rounded-xl border-2 border-${informe.color}-200 transition-all duration-200 peer-checked:border-${informe.color}-500 peer-checked:from-${informe.color}-100 peer-checked:to-${informe.color}-200 hover:border-${informe.color}-300 hover:shadow-md`">
                      <div :class="`flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 mr-3 sm:mr-4 bg-${informe.color}-500 rounded-lg sm:rounded-xl shadow-lg`">
                        <FontAwesomeIcon :icon="informe.icono" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                      </div>
                      <div class="flex-1">
                        <h4 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ informe.nombre }}</h4>
                        <p class="text-xs sm:text-sm text-gray-600">{{ informe.descripcion }}</p>
                        <div class="mt-2">
                          <span v-if="!informe.requiereFechas" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <FontAwesomeIcon :icon="faChartLine" class="mr-1" /> Instantáneo
                          </span>
                          <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <FontAwesomeIcon :icon="faCogs" class="mr-1" /> Configurable
                          </span>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Búsqueda de Cliente (solo si es necesario) -->
              <div v-show="requiereCliente" class="space-y-3 sm:space-y-4">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <FontAwesomeIcon :icon="faUser" class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                  <span class="hidden sm:inline">Seleccionar Cliente</span>
                  <span class="sm:hidden">Cliente</span>
                </h3>
                <div class="bg-blue-50 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-blue-200">
                  <label for="busqueda-cliente" class="block text-sm font-medium text-gray-700 mb-3">
                    Buscar cliente por nombre:
                  </label>
                  <div class="relative">
                    <input
                      id="busqueda-cliente"
                      ref="searchInputRef"
                      type="text"
                      placeholder="Escriba el nombre del cliente..."
                      class="w-full border border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                    />
                    <div v-if="buscandoClientes" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                      <FontAwesomeIcon :icon="faSpinner" class="animate-spin h-4 w-4 text-blue-500" />
                    </div>
                  </div>

                  <!-- Lista de clientes encontrados -->
                  <div v-if="clientesEncontrados.length > 0" class="mt-2 max-h-40 overflow-y-auto border border-gray-300 rounded-lg bg-white shadow-lg">
                    <button
                      v-for="cliente in clientesEncontrados"
                      :key="cliente.id || cliente.user_id"
                      @click="seleccionarCliente(cliente)"
                      class="w-full p-3 text-left hover:bg-blue-50 border-b border-gray-100 last:border-b-0 flex items-center gap-3"
                    >
                      <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-sm">
                        {{ (cliente.user?.name || cliente.name || 'C').charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-medium text-gray-900">{{ cliente.user?.name || cliente.name || 'Cliente' }}</div>
                        <div class="text-sm text-gray-500">{{ cliente.user?.email || cliente.email || 'Sin email' }}</div>
                      </div>
                    </button>
                  </div>

                  <!-- Cliente seleccionado -->
                  <div v-if="clienteSeleccionado" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-semibold">
                      <FontAwesomeIcon :icon="faCheck" class="w-4 h-4" />
                      </div>
                      <div>
                        <div class="font-medium text-green-900">{{ clienteSeleccionado.user?.name || clienteSeleccionado.name }}</div>
                        <div class="text-sm text-green-700">Cliente seleccionado</div>
                      </div>
                    </div>
                    <button
                      @click="limpiarCliente"
                      class="text-red-600 hover:text-red-800 p-1"
                      title="Quitar selección"
                    >
                      <FontAwesomeIcon :icon="faTimes" class="w-4 h-4" />
                    </button>
                  </div>

                  <!-- Mensaje cuando no hay resultados -->
                  <div v-if="busquedaCliente.length >= 2 && !buscandoClientes && clientesEncontrados.length === 0" class="mt-2 p-3 text-center text-gray-500 text-sm">
                    No se encontraron clientes con ese nombre
                  </div>
                </div>
              </div>

              <!-- Configuración de Fechas (solo si es necesario) -->
              <div v-show="requiereFechas" class="space-y-3 sm:space-y-4">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <FontAwesomeIcon :icon="faCalendarDays" class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                  <span class="hidden sm:inline">Configurar Período</span>
                  <span class="sm:hidden">Período</span>
                </h3>
                <div class="grid grid-cols-1 gap-3 sm:gap-4">
                  <label class="relative cursor-pointer">
                    <input
                      type="radio"
                      v-model="modoSeleccion"
                      value="unico"
                      class="sr-only peer"
                    />
                    <div class="flex items-center p-3 sm:p-4 rounded-lg sm:rounded-xl border-2 transition-all duration-200 hover:border-red-300"
                         :class="modoSeleccion === 'unico' ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-gray-50'">
                      <div class="flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3 border-2 rounded-full transition-all duration-200"
                           :class="modoSeleccion === 'unico' ? 'border-red-500 bg-red-500' : 'border-gray-300 bg-white'">
                        <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full transition-opacity duration-200"
                             :class="modoSeleccion === 'unico' ? 'opacity-100' : 'opacity-0'"></div>
                      </div>
                      <div class="flex-1">
                        <h4 class="text-sm sm:text-base font-semibold text-gray-900">Informe Mensual</h4>
                        <p class="text-xs sm:text-sm text-gray-600">
                          <span class="hidden sm:inline">Generar informe para un mes específico</span>
                          <span class="sm:hidden">Un mes específico</span>
                        </p>
                      </div>
                    </div>
                  </label>
                  <label class="relative cursor-pointer">
                    <input
                      type="radio"
                      v-model="modoSeleccion"
                      value="rango"
                      class="sr-only peer"
                    />
                    <div class="flex items-center p-3 sm:p-4 rounded-lg sm:rounded-xl border-2 transition-all duration-200 hover:border-red-300"
                         :class="modoSeleccion === 'rango' ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-gray-50'">
                      <div class="flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3 border-2 rounded-full transition-all duration-200"
                           :class="modoSeleccion === 'rango' ? 'border-red-500 bg-red-500' : 'border-gray-300 bg-white'">
                        <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full transition-opacity duration-200"
                             :class="modoSeleccion === 'rango' ? 'opacity-100' : 'opacity-0'"></div>
                      </div>
                      <div class="flex-1">
                        <h4 class="text-sm sm:text-base font-semibold text-gray-900">Informe por Rango</h4>
                        <p class="text-xs sm:text-sm text-gray-600">
                          <span class="hidden sm:inline">Generar informe para múltiples meses</span>
                          <span class="sm:hidden">Múltiples meses</span>
                        </p>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
              <!-- Selección de Fechas (solo si el informe requiere fechas) -->
              <div v-show="requiereFechas" class="space-y-4 sm:space-y-6">
                <!-- Mes Único -->
                <div v-show="modoSeleccion === 'unico'" class="space-y-2 sm:space-y-3">
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <FontAwesomeIcon :icon="faCalendarDays" class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                    <span class="hidden sm:inline">Seleccionar Mes</span>
                    <span class="sm:hidden">Mes</span>
                  </h3>
                  <div class="bg-red-50 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-red-200">
                    <label for="mes-unico-picker" class="block text-sm font-medium text-gray-700 mb-3">
                      Mes para el informe:
                    </label>
                    <DatePicker
                      id="mes-unico-picker"
                      v-model="mesUnico"
                      view="month"
                      dateFormat="MM yy"
                      showIcon
                      class="w-full border border-gray-300 rounded-lg shadow-sm"
                      placeholder="Seleccione un mes específico"
                      :manualInput="false"
                      :maxDate="today"
                      :minDate="minMesUnico"
                      :disabled="modoSeleccion !== 'unico'"
                    />
                  </div>
                </div>

                <!-- Rango de Meses -->
                <div v-show="modoSeleccion === 'rango'" class="space-y-2 sm:space-y-3">
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <FontAwesomeIcon :icon="faCalendarDays" class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                    <span class="hidden sm:inline">Rango de Fechas</span>
                    <span class="sm:hidden">Rango</span>
                  </h3>
                  <div class="bg-red-50 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-red-200 space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label for="desde-picker" class="block text-sm font-medium text-gray-700 mb-2">
                          Desde:
                        </label>
                        <DatePicker
                          id="desde-picker"
                          v-model="desde"
                          view="month"
                          dateFormat="MM yy"
                          showIcon
                          class="w-full border border-gray-300 rounded-lg shadow-sm"
                          placeholder="Mes inicial"
                          :manualInput="false"
                          :maxDate="today"
                          :disabled="modoSeleccion !== 'rango'"
                        />
                      </div>
                      <div>
                        <label for="hasta-picker" class="block text-sm font-medium text-gray-700 mb-2">
                          Hasta:
                        </label>
                        <DatePicker
                          id="hasta-picker"
                          v-model="hasta"
                          view="month"
                          dateFormat="MM yy"
                          showIcon
                          class="w-full border border-gray-300 rounded-lg shadow-sm"
                          placeholder="Mes final"
                          :manualInput="false"
                          :maxDate="today"
                          :minDate="desde"
                          :disabled="modoSeleccion !== 'rango'"
                        />
                      </div>
                    </div>
                    <div v-if="mesesFiltrados.length > 0" class="mt-3 p-3 bg-white rounded-lg border border-red-200">
                      <p class="text-sm text-gray-600 mb-2">
                        <strong>{{ mesesFiltrados.length }}</strong> mes(es) seleccionado(s)
                      </p>
                      <div class="text-xs text-gray-500">
                        {{ mesesFiltrados.slice(0, 3).join(', ') }}{{ mesesFiltrados.length > 3 ? '...' : '' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Acciones -->
              <div class="border-t border-gray-200 pt-4 sm:pt-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-4 sm:justify-between sm:items-center">
                  <button
                    @click="limpiarFechas"
                    class="flex items-center justify-center gap-2 px-4 py-2.5 sm:py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg sm:rounded-xl transition-all duration-200 font-medium text-sm sm:text-base order-2 sm:order-1"
                  >
                    <FontAwesomeIcon :icon="faRefresh" class="w-4 h-4" />
                    <span class="hidden sm:inline">Limpiar Selección</span>
                    <span class="sm:hidden">Limpiar</span>
                  </button>

                  <button
                    @click="descargarPDF"
                    :disabled="!puedeGenerar"
                    class="flex items-center justify-center gap-2 sm:gap-3 px-6 sm:px-8 py-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white rounded-lg sm:rounded-xl font-semibold text-base sm:text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-lg order-1 sm:order-2"
                  >
                    <FontAwesomeIcon :icon="faDownload" class="w-5 h-5" />
                    <span class="hidden sm:inline">Generar Informe PDF</span>
                    <span class="sm:hidden">Generar PDF</span>
                  </button>
                </div>

                <!-- Status Info -->
                <div v-if="!puedeGenerar" class="mt-3 sm:mt-4 p-3 sm:p-4 bg-amber-50 border border-amber-200 rounded-lg sm:rounded-xl">
                  <div class="flex items-start gap-2 text-amber-700">
                    <FontAwesomeIcon :icon="faExclamationCircle" class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0 mt-0.5" />
                    <span class="font-medium text-xs sm:text-sm">
                      {{ (() => {
                        if (requiereCliente && !clienteSeleccionado) {
                          return windowWidth < 640 ? 'Seleccione un cliente' : 'Seleccione un cliente para generar el informe'
                        } else if (requiereFechas) {
                          return windowWidth < 640 ? 'Seleccione un período válido' : 'Seleccione un período válido para generar el informe'
                        } else {
                          return windowWidth < 640 ? 'Configure los parámetros necesarios' : 'Configure los parámetros necesarios para generar el informe'
                        }
                      })() }}
                    </span>
                  </div>
                </div>

                <!-- Info sobre el informe actual -->
                <div class="mt-3 sm:mt-4 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg sm:rounded-xl">
                  <div class="flex items-start gap-2 sm:gap-3">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                      <h4 class="text-sm sm:text-base font-semibold text-red-900 mb-1">
                        {{ informeSeleccionado?.nombre }}
                      </h4>
                      <p class="text-xs sm:text-sm text-red-700">
                        {{ informeSeleccionado?.descripcion }}
                      </p>
                      <div v-if="!requiereFechas" class="mt-1.5 sm:mt-2 text-xs text-red-600 font-medium">
                        ✨ <span class="hidden sm:inline">Este informe se genera con datos actuales del sistema</span>
                        <span class="sm:hidden">Datos actuales del sistema</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
