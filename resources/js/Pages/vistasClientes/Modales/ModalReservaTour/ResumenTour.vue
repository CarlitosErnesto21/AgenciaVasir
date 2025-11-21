<script setup>
import { faUmbrella, faUmbrellaBeach } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed } from 'vue'

// Props del componente
const props = defineProps({
  tourSeleccionado: {
    type: Object,
    default: null
  }
})

// Función para formatear fechas
const formatearFecha = (fecha) => {
  if (!fecha) return ''
  const date = new Date(fecha)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

// Computed para datos de la tabla
const tourData = computed(() => {
  if (!props.tourSeleccionado) return []

  return [{
    id: 1,
    nombre: props.tourSeleccionado.nombre,
    incluye: props.tourSeleccionado.incluye || '---',
    no_incluye: props.tourSeleccionado.no_incluye || '---',
    punto_salida: props.tourSeleccionado.punto_salida,
    fecha_salida: formatearFecha(props.tourSeleccionado.fecha_salida),
    fecha_regreso: formatearFecha(props.tourSeleccionado.fecha_regreso),
  }]
})
</script>

<template>
  <div class="bg-gradient-to-br from-white via-red-50 to-pink-50 rounded-2xl p-4 sm:p-6 border-2 border-red-200 shadow-lg">
    <h4 class="font-bold bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent mb-4 sm:mb-5 flex items-center text-base sm:text-lg">
      <span class="mr-3 p-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl shadow-md">
        <FontAwesomeIcon :icon="faUmbrellaBeach" class="h-5 w-5" />
      </span>
      <span>Detalles Generales del Tour</span>
    </h4>

    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-inner border border-gray-100 overflow-hidden">
      <DataTable
        :value="tourData"
        stripedRows
        size="small"
        class="text-xs sm:text-sm"
        :pt="{
          table: { class: 'min-w-full' },
          header: { class: 'bg-gradient-to-r from-red-500 to-pink-500 border-0' },
          headerRow: { class: 'bg-transparent' },
          headerCell: { class: 'p-3 sm:p-4 text-xs sm:text-sm font-bold text-white border-r border-red-400/30 last:border-r-0 backdrop-blur-sm' },
          bodyRow: { class: 'hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-300 border-b border-gray-100 last:border-b-0' },
          bodyCell: { class: 'p-3 sm:p-4 text-xs sm:text-sm border-r border-gray-100 last:border-r-0 font-medium' }
        }"
        responsiveLayout="scroll"
      >
        <Column field="nombre" header="Tour" class="min-w-[120px]">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <span class="font-semibold text-blue-700 text-xs">{{ slotProps.data.nombre }}</span>
            </div>
          </template>
        </Column>
        <Column field="incluye" header="Incluye" class="min-w-[100px]">
          <template #body="slotProps">
                <span class="text-blue-700 text-xs leading-relaxed">{{ slotProps.data.incluye }}</span>
          </template>
        </Column>
        <Column field="no_incluye" header="No Incluye" class="min-w-[100px]">
          <template #body="slotProps">
                <span class="text-red-700 text-xs leading-relaxed">{{ slotProps.data.no_incluye }}</span>
          </template>
        </Column>
        <Column field="punto_salida" header="Punto de Salida" class="min-w-[100px]">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <span class="text-blue-700 font-medium text-xs">{{ slotProps.data.punto_salida }}</span>
            </div>
          </template>
        </Column>
        <Column field="fecha_salida" header="Fecha Salida" class="min-w-[120px]">
          <template #body="slotProps">
            <span class="text-blue-700 font-semibold text-xs">{{ slotProps.data.fecha_salida }}</span>
          </template>
        </Column>
        <Column field="fecha_regreso" header="Fecha Regreso" class="min-w-[120px]">
          <template #body="slotProps">
            <span class="text-purple-700 font-semibold text-xs">{{ slotProps.data.fecha_regreso }}</span>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>
