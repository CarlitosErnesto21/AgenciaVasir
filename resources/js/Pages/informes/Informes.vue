<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import DatePicker from 'primevue/datepicker'
import Toast from 'primevue/toast'

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
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', updateWindowWidth)
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

// Nuevo: Tipo de informe
const tipoInforme = ref('tours')
const tiposInformes = [
  {
    id: 'tours',
    nombre: 'Informe de Tours',
    descripcion: 'Cupos vendidos mensuales por tour',
    icono: 'M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h8m-9 0v10a2 2 0 002 2h8a2 2 0 002-2V7H7z',
    endpoint: '/descargar-informe',
    requiereFechas: true,
    color: 'red'
  },
  {
    id: 'inventario',
    nombre: 'Informe de Inventario',
    descripcion: 'Estado del inventario, stock y movimientos',
    icono: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 12l6-3-6-3v6z',
    endpoint: '/descargar-informe-inventario',
    requiereFechas: false,
    color: 'green'
  }
]

// Fecha mínima para el DatePicker de mes único (marzo 2019)
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

const puedeGenerar = computed(() => {
  const informeSeleccionado = tiposInformes.find(t => t.id === tipoInforme.value)

  // Si el informe no requiere fechas (como inventario), siempre se puede generar
  if (!informeSeleccionado?.requiereFechas) {
    return true
  }

  // Si requiere fechas, validar según el modo de selección
  return modoSeleccion.value === 'unico' ? !!mesUnico.value : !!(desde.value && hasta.value && mesesFiltrados.value.length)
})

function showToast(type, summary, detail, life = 3500) {
  toast.add({ severity: type, summary, detail, life })
}

async function descargarPDF() {
  const informeSeleccionado = tiposInformes.find(t => t.id === tipoInforme.value)
  if (!informeSeleccionado) {
    showToast('error', 'Error', 'Tipo de informe no válido.')
    return
  }

  let params = new URLSearchParams()

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
            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
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
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h8m-9 0v10a2 2 0 002 2h8a2 2 0 002-2V7H7z"/>
                </svg>
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
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                  </svg>
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
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" :d="informe.icono"/>
                        </svg>
                      </div>
                      <div class="flex-1">
                        <h4 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ informe.nombre }}</h4>
                        <p class="text-xs sm:text-sm text-gray-600">{{ informe.descripcion }}</p>
                        <div class="mt-2">
                          <span v-if="!informe.requiereFechas" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            📊 Instantáneo
                          </span>
                          <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            ⚙️ Configurable
                          </span>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Configuración de Fechas (solo si es necesario) -->
              <div v-if="tiposInformes.find(t => t.id === tipoInforme)?.requiereFechas" class="space-y-3 sm:space-y-4">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
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
              <div v-if="tiposInformes.find(t => t.id === tipoInforme)?.requiereFechas" class="space-y-4 sm:space-y-6">
                <!-- Mes Único -->
                <div v-show="modoSeleccion === 'unico'" class="space-y-2 sm:space-y-3">
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h8m-9 0v10a2 2 0 002 2h8a2 2 0 002-2V7H7z"/>
                    </svg>
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
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h8m-9 0v10a2 2 0 002 2h8a2 2 0 002-2V7H7z"/>
                    </svg>
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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="hidden sm:inline">Limpiar Selección</span>
                    <span class="sm:hidden">Limpiar</span>
                  </button>

                  <button
                    @click="descargarPDF"
                    :disabled="!puedeGenerar"
                    class="flex items-center justify-center gap-2 sm:gap-3 px-6 sm:px-8 py-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white rounded-lg sm:rounded-xl font-semibold text-base sm:text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-lg order-1 sm:order-2"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="hidden sm:inline">Generar Informe PDF</span>
                    <span class="sm:hidden">Generar PDF</span>
                  </button>
                </div>

                <!-- Status Info -->
                <div v-if="!puedeGenerar" class="mt-3 sm:mt-4 p-3 sm:p-4 bg-amber-50 border border-amber-200 rounded-lg sm:rounded-xl">
                  <div class="flex items-start gap-2 text-amber-700">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium text-xs sm:text-sm">
                      {{ tiposInformes.find(t => t.id === tipoInforme)?.requiereFechas
                         ? (windowWidth < 640 ? 'Seleccione un período válido' : 'Seleccione un período válido para generar el informe')
                         : (windowWidth < 640 ? 'Configure los parámetros necesarios' : 'Configure los parámetros necesarios para generar el informe') }}
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
                        {{ tiposInformes.find(t => t.id === tipoInforme)?.nombre }}
                      </h4>
                      <p class="text-xs sm:text-sm text-red-700">
                        {{ tiposInformes.find(t => t.id === tipoInforme)?.descripcion }}
                      </p>
                      <div v-if="!tiposInformes.find(t => t.id === tipoInforme)?.requiereFechas" class="mt-1.5 sm:mt-2 text-xs text-red-600 font-medium">
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
