<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          <i class="pi pi-shopping-cart mr-2 text-blue-600"></i>
          Gestión de Ventas
        </h2>
        <div class="flex gap-2">
          <Button
            label="Nueva Venta"
            icon="pi pi-plus"
            class="p-button-success p-button-sm"
            @click="openCreateModal"
          />
          <Button
            label="Exportar"
            icon="pi pi-download"
            class="p-button-info p-button-sm p-button-outlined"
            @click="exportarVentas"
          />
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <!-- Filtros y Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <!-- Tarjetas de resumen -->
          <Card class="bg-gradient-to-r from-green-400 to-green-600 text-white">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-green-100 text-sm">Ventas Completadas</div>
                  <div class="text-2xl font-bold">{{ estadisticas.completadas }}</div>
                </div>
                <i class="pi pi-check-circle text-3xl text-green-200"></i>
              </div>
            </template>
          </Card>

          <Card class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-yellow-100 text-sm">Ventas Pendientes</div>
                  <div class="text-2xl font-bold">{{ estadisticas.pendientes }}</div>
                </div>
                <i class="pi pi-clock text-3xl text-yellow-200"></i>
              </div>
            </template>
          </Card>

          <Card class="bg-gradient-to-r from-red-400 to-red-600 text-white">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-red-100 text-sm">Ventas Canceladas</div>
                  <div class="text-2xl font-bold">{{ estadisticas.canceladas }}</div>
                </div>
                <i class="pi pi-times-circle text-3xl text-red-200"></i>
              </div>
            </template>
          </Card>

          <Card class="bg-gradient-to-r from-blue-400 to-blue-600 text-white">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-blue-100 text-sm">Total Vendido</div>
                  <div class="text-2xl font-bold">${{ formatCurrency(estadisticas.totalVendido) }}</div>
                </div>
                <i class="pi pi-dollar text-3xl text-blue-200"></i>
              </div>
            </template>
          </Card>
        </div>

        <!-- Filtros -->
        <Card class="mb-6">
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <Select
                  v-model="filtros.estado"
                  :options="estadosOptions"
                  optionLabel="name"
                  optionValue="value"
                  placeholder="Todos los estados"
                  class="w-full"
                  @change="aplicarFiltros"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                <DatePicker
                  v-model="filtros.fechaDesde"
                  placeholder="Seleccionar fecha"
                  class="w-full"
                  @date-select="aplicarFiltros"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                <DatePicker
                  v-model="filtros.fechaHasta"
                  placeholder="Seleccionar fecha"
                  class="w-full"
                  @date-select="aplicarFiltros"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                <InputText
                  v-model="filtros.cliente"
                  placeholder="Buscar cliente"
                  class="w-full"
                  @input="aplicarFiltros"
                />
              </div>
              <div class="flex items-end">
                <Button
                  label="Limpiar Filtros"
                  icon="pi pi-times"
                  class="p-button-outlined w-full"
                  @click="limpiarFiltros"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Tabla de Ventas -->
        <Card>
          <template #content>
            <DataTable
              :value="ventasFiltradas"
              :paginator="true"
              :rows="10"
              :loading="loading"
              :rowsPerPageOptions="[5, 10, 20, 50]"
              :totalRecords="ventasFiltradas.length"
              :globalFilterFields="['cliente.nombre', 'empleado.nombre', 'metodo_pago.nombre']"
              scrollable
              scrollHeight="60vh"
              class="p-datatable-sm"
              stripedRows
              responsiveLayout="scroll"
            >
              <template #header>
                <div class="flex justify-between items-center">
                  <h5 class="text-lg font-semibold text-gray-800">
                    Lista de Ventas ({{ ventasFiltradas.length }})
                  </h5>
                  <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText
                      v-model="globalFilter"
                      placeholder="Buscar ventas..."
                      class="p-inputtext-sm"
                    />
                  </span>
                </div>
              </template>

              <Column field="id" header="ID" sortable style="width: 80px">
                <template #body="{ data }">
                  <Tag :value="`#${data.id}`" severity="info" />
                </template>
              </Column>

              <Column field="fecha" header="Fecha" sortable style="width: 120px">
                <template #body="{ data }">
                  <span class="text-gray-700">{{ formatDate(data.fecha) }}</span>
                </template>
              </Column>

              <Column field="cliente.nombre" header="Cliente" sortable>
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-user text-gray-500"></i>
                    <span class="font-medium">{{ data.cliente?.nombre || 'N/A' }}</span>
                  </div>
                </template>
              </Column>

              <Column field="empleado.nombre" header="Empleado" sortable>
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-user-edit text-blue-500"></i>
                    <span>{{ data.empleado?.nombre || 'N/A' }}</span>
                  </div>
                </template>
              </Column>

              <Column field="metodo_pago.nombre" header="Método Pago" sortable>
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-credit-card text-green-500"></i>
                    <span>{{ data.metodo_pago?.nombre || 'N/A' }}</span>
                  </div>
                </template>
              </Column>

              <Column field="total" header="Total" sortable style="width: 120px">
                <template #body="{ data }">
                  <span class="font-bold text-green-600">${{ formatCurrency(data.total) }}</span>
                </template>
              </Column>

              <Column field="estado" header="Estado" sortable style="width: 120px">
                <template #body="{ data }">
                  <Tag
                    :value="data.estado"
                    :severity="getEstadoSeverity(data.estado)"
                    :icon="getEstadoIcon(data.estado)"
                  />
                </template>
              </Column>

              <Column header="Productos" style="width: 100px">
                <template #body="{ data }">
                  <Tag
                    :value="`${data.detalle_ventas?.length || 0} items`"
                    severity="info"
                    class="text-xs"
                  />
                </template>
              </Column>

              <Column header="Acciones" style="width: 200px">
                <template #body="{ data }">
                  <div class="flex gap-1">
                    <Button
                      icon="pi pi-eye"
                      class="p-button-sm p-button-info p-button-text"
                      v-tooltip="'Ver detalles'"
                      @click="verDetalles(data)"
                    />
                    <Button
                      v-if="data.estado === 'pendiente'"
                      icon="pi pi-pencil"
                      class="p-button-sm p-button-warning p-button-text"
                      v-tooltip="'Editar venta'"
                      @click="editarVenta(data)"
                    />
                    <Button
                      v-if="data.estado === 'pendiente'"
                      icon="pi pi-check"
                      class="p-button-sm p-button-success p-button-text"
                      v-tooltip="'Procesar venta'"
                      @click="procesarVenta(data)"
                    />
                    <Button
                      v-if="data.estado === 'completada'"
                      icon="pi pi-times"
                      class="p-button-sm p-button-danger p-button-text"
                      v-tooltip="'Cancelar venta'"
                      @click="cancelarVenta(data)"
                    />
                    <Button
                      v-if="data.estado !== 'completada'"
                      icon="pi pi-trash"
                      class="p-button-sm p-button-danger p-button-text"
                      v-tooltip="'Eliminar venta'"
                      @click="eliminarVenta(data)"
                    />
                    <Button
                      icon="pi pi-print"
                      class="p-button-sm p-button-info p-button-text"
                      v-tooltip="'Imprimir'"
                      @click="imprimirVenta(data)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
    </div>

    <!-- Modal de Detalles -->
    <Dialog
      v-model:visible="modalDetalles"
      :style="{ width: '80vw' }"
      header="Detalles de la Venta"
      :modal="true"
      :closable="true"
    >
      <div v-if="ventaSeleccionada" class="space-y-6">
        <!-- Información General -->
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span>Información General</span>
              <Tag
                :value="ventaSeleccionada.estado"
                :severity="getEstadoSeverity(ventaSeleccionada.estado)"
                :icon="getEstadoIcon(ventaSeleccionada.estado)"
              />
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <i class="pi pi-hashtag text-gray-500"></i>
                  <strong>ID:</strong> #{{ ventaSeleccionada.id }}
                </div>
                <div class="flex items-center gap-2">
                  <i class="pi pi-calendar text-gray-500"></i>
                  <strong>Fecha:</strong> {{ formatDate(ventaSeleccionada.fecha) }}
                </div>
                <div class="flex items-center gap-2">
                  <i class="pi pi-user text-gray-500"></i>
                  <strong>Cliente:</strong> {{ ventaSeleccionada.cliente?.nombre }}
                </div>
              </div>
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <i class="pi pi-user-edit text-gray-500"></i>
                  <strong>Empleado:</strong> {{ ventaSeleccionada.empleado?.nombre }}
                </div>
                <div class="flex items-center gap-2">
                  <i class="pi pi-credit-card text-gray-500"></i>
                  <strong>Método Pago:</strong> {{ ventaSeleccionada.metodo_pago?.nombre }}
                </div>
                <div class="flex items-center gap-2">
                  <i class="pi pi-dollar text-gray-500"></i>
                  <strong>Total:</strong> 
                  <span class="text-green-600 font-bold text-lg">
                    ${{ formatCurrency(ventaSeleccionada.total) }}
                  </span>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Productos Vendidos -->
        <Card>
          <template #title>
            Productos Vendidos ({{ ventaSeleccionada.detalle_ventas?.length || 0 }})
          </template>
          <template #content>
            <DataTable
              :value="ventaSeleccionada.detalle_ventas"
              class="p-datatable-sm"
              stripedRows
            >
              <Column field="producto.nombre" header="Producto">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-box text-blue-500"></i>
                    <span class="font-medium">{{ data.producto?.nombre }}</span>
                  </div>
                </template>
              </Column>
              <Column field="producto.categoria.nombre" header="Categoría">
                <template #body="{ data }">
                  <Tag :value="data.producto?.categoria?.nombre || 'Sin categoría'" />
                </template>
              </Column>
              <Column field="cantidad" header="Cantidad" style="width: 100px">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.cantidad }}</span>
                </template>
              </Column>
              <Column field="precio_unitario" header="Precio Unit." style="width: 120px">
                <template #body="{ data }">
                  <span>${{ formatCurrency(data.precio_unitario) }}</span>
                </template>
              </Column>
              <Column field="subtotal" header="Subtotal" style="width: 120px">
                <template #body="{ data }">
                  <span class="font-bold text-green-600">
                    ${{ formatCurrency(data.subtotal) }}
                  </span>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
    </Dialog>

    <!-- Toast para notificaciones -->
    <Toast />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import axios from 'axios'

// Composables
const toast = useToast()

// Estados reactivos
const ventas = ref([])
const loading = ref(false)
const modalDetalles = ref(false)
const ventaSeleccionada = ref(null)
const globalFilter = ref('')

// Filtros
const filtros = ref({
  estado: null,
  fechaDesde: null,
  fechaHasta: null,
  cliente: ''
})

// Opciones para filtros
const estadosOptions = [
  { name: 'Todos', value: null },
  { name: 'Pendiente', value: 'pendiente' },
  { name: 'Completada', value: 'completada' },
  { name: 'Cancelada', value: 'cancelada' }
]

// Computed
const ventasFiltradas = computed(() => {
  let resultado = ventas.value

  // Filtrar por estado
  if (filtros.value.estado) {
    resultado = resultado.filter(venta => venta.estado === filtros.value.estado)
  }

  // Filtrar por fecha desde
  if (filtros.value.fechaDesde) {
    const fechaDesde = new Date(filtros.value.fechaDesde)
    resultado = resultado.filter(venta => new Date(venta.fecha) >= fechaDesde)
  }

  // Filtrar por fecha hasta
  if (filtros.value.fechaHasta) {
    const fechaHasta = new Date(filtros.value.fechaHasta)
    resultado = resultado.filter(venta => new Date(venta.fecha) <= fechaHasta)
  }

  // Filtrar por cliente
  if (filtros.value.cliente) {
    const clienteBusqueda = filtros.value.cliente.toLowerCase()
    resultado = resultado.filter(venta => 
      venta.cliente?.nombre?.toLowerCase().includes(clienteBusqueda)
    )
  }

  // Filtro global
  if (globalFilter.value) {
    const filtroGlobal = globalFilter.value.toLowerCase()
    resultado = resultado.filter(venta =>
      venta.cliente?.nombre?.toLowerCase().includes(filtroGlobal) ||
      venta.empleado?.nombre?.toLowerCase().includes(filtroGlobal) ||
      venta.metodo_pago?.nombre?.toLowerCase().includes(filtroGlobal) ||
      venta.id.toString().includes(filtroGlobal)
    )
  }

  return resultado
})

const estadisticas = computed(() => {
  const stats = {
    completadas: 0,
    pendientes: 0,
    canceladas: 0,
    totalVendido: 0
  }

  ventas.value.forEach(venta => {
    switch (venta.estado) {
      case 'completada':
        stats.completadas++
        stats.totalVendido += parseFloat(venta.total || 0)
        break
      case 'pendiente':
        stats.pendientes++
        break
      case 'cancelada':
        stats.canceladas++
        break
    }
  })

  return stats
})

// Métodos
const cargarVentas = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/ventas')
    ventas.value = response.data
  } catch (error) {
    console.error('Error cargando ventas:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las ventas',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const verDetalles = (venta) => {
  ventaSeleccionada.value = venta
  modalDetalles.value = true
}

const procesarVenta = async (venta) => {
  try {
    await axios.post(`/api/ventas/${venta.id}/procesar`)
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Venta procesada correctamente',
      life: 3000
    })
    await cargarVentas()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al procesar la venta',
      life: 3000
    })
  }
}

const cancelarVenta = async (venta) => {
  try {
    await axios.post(`/api/ventas/${venta.id}/cancelar`)
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Venta cancelada correctamente',
      life: 3000
    })
    await cargarVentas()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al cancelar la venta',
      life: 3000
    })
  }
}

const eliminarVenta = async (venta) => {
  if (confirm('¿Está seguro de eliminar esta venta?')) {
    try {
      await axios.delete(`/api/ventas/${venta.id}`)
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Venta eliminada correctamente',
        life: 3000
      })
      await cargarVentas()
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Error al eliminar la venta',
        life: 3000
      })
    }
  }
}

const aplicarFiltros = () => {
  // Los filtros se aplican automáticamente através del computed
}

const limpiarFiltros = () => {
  filtros.value = {
    estado: null,
    fechaDesde: null,
    fechaHasta: null,
    cliente: ''
  }
  globalFilter.value = ''
}

const openCreateModal = () => {
  // TODO: Implementar modal de creación
  toast.add({
    severity: 'info',
    summary: 'Información',
    detail: 'Función de crear venta pendiente de implementar',
    life: 3000
  })
}

const editarVenta = (venta) => {
  // TODO: Implementar edición
  toast.add({
    severity: 'info',
    summary: 'Información',
    detail: 'Función de editar venta pendiente de implementar',
    life: 3000
  })
}

const exportarVentas = () => {
  // TODO: Implementar exportación
  toast.add({
    severity: 'info',
    summary: 'Información',
    detail: 'Función de exportar pendiente de implementar',
    life: 3000
  })
}

const imprimirVenta = (venta) => {
  // TODO: Implementar impresión
  toast.add({
    severity: 'info',
    summary: 'Información',
    detail: 'Función de imprimir pendiente de implementar',
    life: 3000
  })
}

// Helpers
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return parseFloat(amount).toLocaleString('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const getEstadoSeverity = (estado) => {
  switch (estado) {
    case 'completada': return 'success'
    case 'pendiente': return 'warning'
    case 'cancelada': return 'danger'
    default: return 'info'
  }
}

const getEstadoIcon = (estado) => {
  switch (estado) {
    case 'completada': return 'pi pi-check'
    case 'pendiente': return 'pi pi-clock'
    case 'cancelada': return 'pi pi-times'
    default: return 'pi pi-question'
  }
}

// Lifecycle
onMounted(() => {
  cargarVentas()
})
</script>

<style scoped>
.p-datatable .p-datatable-tbody > tr > td {
  padding: 0.75rem;
}

.p-card .p-card-body {
  padding: 1rem;
}

.p-button.p-button-sm {
  padding: 0.25rem 0.5rem;
}

/* Animaciones suaves */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
