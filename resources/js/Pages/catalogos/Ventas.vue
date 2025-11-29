<template>
  <AuthenticatedLayout>
    <Head title="Ventas" />
    <Toast class="z-[9999]" />

    <div class="container mx-auto px-4 py-6">
      <div class="mb-6 text-center sm:text-left">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">Gestión de Ventas de Productos</h1>
        <p class="text-gray-600">Visualiza y administra todas las ventas de productos realizadas en el sistema.</p>
      </div>

      <!-- Tarjetas de estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white rounded-lg p-4 shadow-md">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-yellow-100 text-sm">Ventas Pendientes</div>
              <div class="text-2xl font-bold">{{ estadisticas.pendientes }}</div>
            </div>
            <FontAwesomeIcon :icon="faClock" class="text-3xl text-yellow-200" />
          </div>
        </div>

        <div class="bg-gradient-to-r from-green-400 to-green-600 text-white rounded-lg p-4 shadow-md">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-green-100 text-sm">Ventas Completadas</div>
              <div class="text-2xl font-bold">{{ estadisticas.completadas }}</div>
            </div>
            <FontAwesomeIcon :icon="faCheck" class="text-3xl text-green-200" />
          </div>
        </div>

        <div class="bg-gradient-to-r from-red-400 to-red-600 text-white rounded-lg p-4 shadow-md">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-red-100 text-sm">Ventas Canceladas</div>
              <div class="text-2xl font-bold">{{ estadisticas.canceladas }}</div>
            </div>
            <FontAwesomeIcon :icon="faXmark" class="text-3xl text-red-200" />
          </div>
        </div>

        <div class="bg-gradient-to-r from-blue-400 to-blue-600 text-white rounded-lg p-4 shadow-md">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-blue-100 text-sm">Total Vendido</div>
              <div class="text-2xl font-bold">${{ formatCurrency(estadisticas.totalVendido) }}</div>
            </div>
            <FontAwesomeIcon :icon="faDollarSign" class="text-3xl text-blue-200" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md">

        <DataTable
          :value="ventasFiltradas"
          dataKey="id"
          :paginator="true"
          :rows="rowsPerPage"
          :rowsPerPageOptions="rowsPerPageOptions"
          v-model:rowsPerPage="rowsPerPage"
          paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} ventas"
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
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                  <div class="flex items-center gap-3">
                    <h3 class="text-base font-medium text-gray-800 flex items-center gap-2">
                      <FontAwesomeIcon :icon="faFilter" class="text-blue-600 text-sm" />
                      <span>Filtros</span>
                    </h3>
                    <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded text-sm font-medium">
                      {{ ventasFiltradas.length }} resultado{{ ventasFiltradas.length !== 1 ? 's' : '' }}
                    </div>
                  </div>
                  <!-- Botones para móviles en dos columnas -->
                  <div class="grid grid-cols-2 gap-2 sm:hidden">
                    <button
                      class="bg-blue-500 hover:bg-blue-600 border border-blue-500 px-3 py-2 text-sm text-white shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                      @click="fetchVentasWithToasts"
                      :disabled="isReloading">
                      <FontAwesomeIcon
                        :icon="isReloading ? faSpinner : faRefresh"
                        :class="{ 'animate-spin': isReloading }"
                        class="h-3 w-3"
                      />
                      <span>{{ isReloading ? 'Recargando...' : 'Recargar' }}</span>
                    </button>
                    <button
                      class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-2 text-sm text-white shadow-md rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                      @click="limpiarFiltros"
                      :disabled="isClearingFilters">
                      <FontAwesomeIcon
                        v-if="isClearingFilters"
                        :icon="faSpinner"
                        class="animate-spin h-3 w-3"
                      />
                      <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar' }}</span>
                    </button>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button
                    class="bg-blue-500 hover:bg-blue-600 border border-blue-500 px-3 py-1 text-sm text-white shadow-md rounded-md hidden sm:flex disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                    @click="fetchVentasWithToasts"
                    :disabled="isReloading">
                    <FontAwesomeIcon
                      :icon="isReloading ? faSpinner : faRefresh"
                      :class="{ 'animate-spin': isReloading }"
                      class="h-3 w-3"
                    />
                    <span>{{ isReloading ? 'Recargando...' : 'Recargar' }}</span>
                  </button>
                  <button
                    class="bg-red-500 hover:bg-red-600 border border-red-500 px-3 py-1 text-sm text-white shadow-md rounded-md hidden sm:flex disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                    @click="limpiarFiltros"
                    :disabled="isClearingFilters">
                    <FontAwesomeIcon
                      v-if="isClearingFilters"
                      :icon="faSpinner"
                      class="animate-spin h-3 w-3"
                    />
                    <span>{{ isClearingFilters ? 'Limpiando...' : 'Limpiar filtros' }}</span>
                  </button>
                </div>
              </div>
              <div class="space-y-3">
                <!-- Búsqueda - Full width en todas las pantallas -->
                <div class="relative">
                  <FontAwesomeIcon :icon="faMagnifyingGlass" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                  <InputText
                    v-model="globalFilter"
                    placeholder="Buscar ventas..."
                    class="w-full h-9 text-sm rounded-md pl-10"
                    style="background-color: white; border-color: #93c5fd;"
                  />
                </div>

                <!-- Estado - Full width en móviles, parte del grid en desktop -->
                <div class="sm:hidden">
                  <select
                    v-model="selectedEstado"
                    @change="onEstadoFilterChange"
                    class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                  >
                    <option value="" disabled selected hidden>Estado</option>
                    <option
                      v-for="estado in estadosOptions"
                      :key="estado.value"
                      :value="estado.value"
                      class="truncate"
                    >
                      {{ estado.name }}
                    </option>
                  </select>
                </div>

                <!-- Fechas - 50/50 en móviles -->
                <div class="grid grid-cols-2 gap-3 sm:hidden">
                  <div>
                    <DatePicker
                      v-model="filtros.fechaDesde"
                      placeholder="Fecha Desde"
                      class="w-full h-9 text-sm border"
                      style="background-color: white; border-color: #93c5fd;"
                      @date-select="aplicarFiltros"
                      @clear="aplicarFiltros"
                      :showIcon="true"
                      dateFormat="dd/mm/yy"
                      :maxDate="filtros.fechaHasta"
                    />
                  </div>
                  <div>
                    <DatePicker
                      v-model="filtros.fechaHasta"
                      placeholder="Fecha Hasta"
                      class="w-full h-9 text-sm border"
                      style="background-color: white; border-color: #93c5fd;"
                      @date-select="aplicarFiltros"
                      @clear="aplicarFiltros"
                      :showIcon="true"
                      dateFormat="dd/mm/yy"
                      :minDate="filtros.fechaDesde"
                    />
                  </div>
                </div>

                <!-- Layout para desktop - hidden en móviles -->
                <div class="hidden sm:grid sm:grid-cols-3 gap-3">
                  <div>
                    <select
                      v-model="selectedEstado"
                      @change="onEstadoFilterChange"
                      class="w-full h-9 text-sm border border-blue-300 rounded-md px-3 py-1 bg-white text-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 truncate"
                    >
                      <option value="" disabled selected hidden>Estado</option>
                      <option
                        v-for="estado in estadosOptions"
                        :key="estado.value"
                        :value="estado.value"
                        class="truncate text-gray-900"
                      >
                        {{ estado.name }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <DatePicker
                      v-model="filtros.fechaDesde"
                      placeholder="Fecha desde"
                      class="w-full h-9 text-sm border"
                      style="background-color: white; border-color: #93c5fd;"
                      @date-select="aplicarFiltros"
                      @clear="aplicarFiltros"
                      :showIcon="true"
                      dateFormat="dd/mm/yy"
                      :maxDate="filtros.fechaHasta"
                    />
                  </div>
                  <div>
                    <DatePicker
                      v-model="filtros.fechaHasta"
                      placeholder="Fecha hasta"
                      class="w-full h-9 text-sm border"
                      style="background-color: white; border-color: #93c5fd;"
                      @date-select="aplicarFiltros"
                      @clear="aplicarFiltros"
                      :showIcon="true"
                      dateFormat="dd/mm/yy"
                      :minDate="filtros.fechaDesde"
                    />
                  </div>
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
          </template>

          <Column field="fecha" header="Fecha" sortable class="w-24 hidden md:table-cell">
            <template #body="slotProps">
              <div class="text-sm leading-relaxed">
                {{ formatDate(slotProps.data.fecha) }}
              </div>
            </template>
          </Column>

          <Column field="cliente.user.name" header="Cliente" sortable class="w-32 min-w-24">
            <template #body="slotProps">
              <div class="flex items-center gap-2">
                <FontAwesomeIcon :icon="faUser" class="text-gray-500 text-xs" />
                <span class="text-sm font-medium">{{ slotProps.data.cliente?.user?.name || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <Column field="total" header="Total" class="w-24">
            <template #body="slotProps">
              <div class="text-sm font-bold text-green-600">
                ${{ formatCurrency(slotProps.data.total) }}
              </div>
            </template>
          </Column>

          <Column field="estado" header="Estado" class="w-28 hidden sm:table-cell">
            <template #body="slotProps">
              <span :class="'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' + getEstadoClass(slotProps.data.estado)">
                {{ getEstadoLabel(slotProps.data.estado) }}
              </span>
            </template>
          </Column>

          <Column header="Items" class="w-16 hidden md:table-cell">
            <template #body="slotProps">
              <div class="text-sm text-center">
                {{ slotProps.data.detalle_ventas?.length || 0 }}
              </div>
            </template>
          </Column>

          <Column :exportable="false" class="w-64 min-w-32">
            <template #header>
              <div class="text-center w-full font-bold">
                Acciones
              </div>
            </template>
            <template #body="slotProps">
              <div class="flex flex-wrap gap-2 justify-center items-center">
                <!-- Botón "Ver" eliminado - se puede hacer clic en la fila para ver detalles -->
                <!-- Botón "Procesar" eliminado - las ventas se crean directamente como completadas -->
                <button
                  v-if="slotProps.data.estado === 'completada'"
                  class="flex bg-orange-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                  @click="confirmarCancelar(slotProps.data)"
                  title="Cancelar venta">
                  <FontAwesomeIcon :icon="faXmark" class="h-4 w-5 sm:h-4 sm:w-6 text-white" />
                  <span class="hidden lg:block text-white ml-1">Cancelar</span>
                </button>
                <button
                  class="flex bg-red-500 border p-1 py-2 sm:p-2 text-sm shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300"
                  @click="confirmarEliminacion(slotProps.data)"
                  title="Eliminar venta">
                  <FontAwesomeIcon :icon="faTrashCan" class="h-4 w-5 sm:h-4 sm:w-6 text-white" />
                  <span class="hidden lg:block text-white ml-1">Eliminar</span>
                </button>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Modal de Detalles -->
    <Dialog
      v-model:visible="modalDetalles"
      :style="dialogStyle"
      :modal="true"
      :closable="false"
      :draggable="false"
    >
      <!-- Header personalizado con estado y fecha prominentes -->
      <template #header>
        <div class="w-full">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-1">
                Detalles de la Venta
              </h3>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
              <!-- Estado de la venta -->
              <div class="flex items-center gap-2">
                <span :class="'px-3 py-1.5 rounded-full text-sm font-bold shadow-md border-2 border-white ' + getEstadoClass(ventaSeleccionada.estado)">
                  {{ getEstadoLabel(ventaSeleccionada.estado) }}
                </span>
              </div>

              <!-- Fecha de la venta -->
              <div class="flex items-center gap-2">
                <FontAwesomeIcon :icon="faCalendar" class="text-blue-600 text-lg" />
                <div class="text-right">
                  <p class="text-sm font-bold text-blue-700 leading-tight">
                    {{ formatDate(ventaSeleccionada.fecha) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="ventaSeleccionada" class="space-y-4">
        <!-- Información General -->
        <div class="bg-white rounded-lg border p-4">
          <div class="mb-4">
            <h4 class="text-lg font-semibold text-gray-800">Información General</h4>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <FontAwesomeIcon :icon="faHashtag" class="text-gray-500 text-sm" />
                <strong>ID:</strong> #{{ ventaSeleccionada.id }}
              </div>
              <div class="flex items-center gap-2">
                <FontAwesomeIcon :icon="faUser" class="text-gray-500 text-sm" />
                <strong>Cliente:</strong> {{ ventaSeleccionada.cliente?.user?.name || 'N/A' }}
              </div>
              <div class="flex flex-col gap-1">
                <span class="font-medium text-gray-700">Email:</span>
                <div v-if="ventaSeleccionada.cliente?.user?.email" class="flex flex-wrap items-center gap-2">
                  <span class="text-xs sm:text-sm break-all">{{ ventaSeleccionada.cliente.user.email }}</span>
                  <a
                    @click="abrirGmail(ventaSeleccionada.cliente.user.email)"
                    href="#"
                    class="inline-flex items-center gap-1 px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md transition-colors duration-200 cursor-pointer"
                    title="Abrir Gmail para enviar correo"
                  >
                    <FontAwesomeIcon :icon="faEnvelope" class="h-3 w-3" />
                    <span class="hidden sm:inline">Enviar Email</span>
                  </a>
                </div>
                <span v-else class="text-gray-500 italic text-xs sm:text-sm">Email no disponible</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="font-medium text-gray-700">Teléfono:</span>
                <div v-if="ventaSeleccionada.cliente?.telefono" class="flex flex-wrap items-center gap-2">
                  <span class="text-xs sm:text-sm">{{ ventaSeleccionada.cliente.telefono }}</span>
                  <div class="flex gap-1">
                    <a
                      :href="`https://wa.me/${ventaSeleccionada.cliente.telefono.replace(/[^0-9]/g, '')}`"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-1 px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs rounded-md transition-colors duration-200"
                      title="Enviar mensaje por WhatsApp"
                    >
                      <FontAwesomeIcon :icon="faWhatsapp" class="h-3 w-3" />
                      <span class="hidden sm:inline">WhatsApp</span>
                    </a>
                    <a
                      :href="`tel:${ventaSeleccionada.cliente.telefono}`"
                      class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md transition-colors duration-200"
                      title="Llamar por teléfono"
                    >
                      <FontAwesomeIcon :icon="faPhone" class="h-3 w-3" />
                      <span class="hidden sm:inline">Llamar</span>
                    </a>
                  </div>
                </div>
                <span v-else class="text-gray-500 italic text-xs sm:text-sm">Teléfono no disponible</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Generador de Enlaces de Pago Wompi -->
        <div v-if="ventaSeleccionada.estado === 'pendiente'" class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border-2 border-yellow-300 p-4">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <FontAwesomeIcon :icon="faLink" class="text-orange-600 text-lg" />
              <h4 class="text-lg font-semibold text-gray-800">Generar Enlace de Pago Wompi</h4>
            </div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                <FontAwesomeIcon :icon="faDollarSign" class="mr-1" />
                Total: ${{ formatCurrency(ventaSeleccionada.total) }}
              </span>
            </div>
          </div>

          <div class="space-y-3">
            <p class="text-sm text-gray-600">
              <FontAwesomeIcon :icon="faInfo" class="text-blue-500 mr-1" />
              <strong>¿El cliente aún no ha pagado?</strong><br>
              Genera un enlace de pago personalizado para que el cliente pueda pagar esta venta directamente.
            </p>

            <!-- Botón para generar enlace -->
            <div v-if="!showEnlaceWompi" class="flex justify-center">
              <button
                @click="generarEnlaceWompi"
                :disabled="generandoEnlace"
                class="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl"
              >
                <FontAwesomeIcon
                  :icon="generandoEnlace ? faSpinner : faLink"
                  :class="{ 'animate-spin': generandoEnlace }"
                />
                {{ generandoEnlace ? 'Generando enlace...' : 'Generar Enlace de Pago' }}
              </button>
            </div>

            <!-- Enlace generado -->
            <div v-if="showEnlaceWompi && enlaceWompiGenerado" class="space-y-3">
              <div class="bg-white rounded-lg border border-yellow-300 p-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">Enlace de Pago Generado:</span>
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <FontAwesomeIcon :icon="faCheckCircle" class="mr-1" />
                    ¡Listo para usar!
                  </span>
                </div>

                <div class="bg-gray-50 rounded-md p-3 mb-3">
                  <p class="text-sm text-gray-800 break-all font-mono">
                    {{ enlaceWompiGenerado.payment_link }}
                  </p>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span>Referencia: {{ enlaceWompiGenerado.reference }}</span>
                  <span>ID: {{ enlaceWompiGenerado.link_id }}</span>
                </div>
              </div>

              <!-- Botones de acción para el enlace -->
              <div class="flex flex-wrap gap-2 justify-center">
                <button
                  @click="copiarEnlace"
                  :class="[
                    'px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium',
                    copiedSuccess
                      ? 'bg-green-500 text-white'
                      : 'bg-blue-500 hover:bg-blue-600 text-white'
                  ]"
                >
                  <FontAwesomeIcon :icon="copiedSuccess ? faCheckCircle : faCopy" />
                  {{ copiedSuccess ? '¡Copiado!' : 'Copiar Enlace' }}
                </button>

                <button
                  @click="abrirEnlace"
                  class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium"
                >
                  <FontAwesomeIcon :icon="faExternalLinkAlt" />
                  Ver Enlace
                </button>

                <button
                  @click="limpiarEnlace"
                  class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium"
                >
                  <FontAwesomeIcon :icon="faTrash" />
                  Limpiar
                </button>
              </div>

              <!-- Instrucciones de uso -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <h5 class="text-sm font-medium text-blue-800 mb-2">
                  <FontAwesomeIcon :icon="faLightbulb" class="mr-1" />
                  Instrucciones de uso:
                </h5>
                <ul class="text-xs text-blue-700 space-y-1">
                  <li>• Copia el enlace y envíalo al cliente por WhatsApp, email o SMS</li>
                  <li>• El cliente podrá pagar correctamente su carrito</li>
                  <li>• Recibirás notificación automática cuando se complete el pago</li>
                  <li>• El enlace es seguro y está protegido por Wompi</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Productos Vendidos -->
        <div class="bg-white rounded-lg border p-4">
          <h4 class="text-lg font-semibold text-gray-800 mb-4">
            Productos Vendidos ({{ ventaSeleccionada.detalle_ventas?.length || 0 }})
          </h4>
          <DataTable
            :value="ventaSeleccionada.detalle_ventas"
            class="text-sm"
            stripedRows
          >
            <Column field="producto.nombre" header="Producto">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <FontAwesomeIcon :icon="faBox" class="text-blue-500 text-sm" />
                  <span class="font-medium">{{ data.producto?.nombre }}</span>
                </div>
              </template>
            </Column>
            <Column field="producto.categoria.nombre" header="Categoría">
              <template #body="{ data }">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ data.producto?.categoria?.nombre || 'Sin categoría' }}
                </span>
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
            <Column field="subtotal" header="Subtotal" style="width: 120px; font-size: 17px">
              <template #body="{ data }">
                <span class="font-bold text-green-600">
                  ${{ formatCurrency(data.subtotal) }}
                </span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <button
            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="cerrarModal"
          >
            <FontAwesomeIcon :icon="faXmark" class="h-5" />
            Cerrar
          </button>
        </div>
      </template>
    </Dialog>

    <!-- Modal de Confirmación Unificado -->
    <Dialog
      v-model:visible="modalConfirmacion"
      :style="dialogStyle"
      :header="accionConfirmacion.titulo"
      :modal="true"
      :closable="false"
      :draggable="false"
    >
      <div v-if="accionConfirmacion.venta" class="space-y-4">
        <div :class="[
          'flex items-center gap-3 p-4 rounded-lg border',
          accionConfirmacion.color === 'green' ? 'bg-green-50 border-green-200' :
          accionConfirmacion.color === 'orange' ? 'bg-orange-50 border-orange-200' :
          'bg-red-50 border-red-200'
        ]">
          <FontAwesomeIcon
            :icon="accionConfirmacion.icono"
            :class="[
              'text-2xl',
              accionConfirmacion.color === 'green' ? 'text-green-500' :
              accionConfirmacion.color === 'orange' ? 'text-orange-500' :
              'text-red-500'
            ]"
          />
          <div>
            <h4 :class="[
              'text-lg font-semibold',
              accionConfirmacion.color === 'green' ? 'text-green-800' :
              accionConfirmacion.color === 'orange' ? 'text-orange-800' :
              'text-red-800'
            ]">
              {{ accionConfirmacion.titulo }}
            </h4>
            <p :class="[
              accionConfirmacion.color === 'green' ? 'text-green-700' :
              accionConfirmacion.color === 'orange' ? 'text-orange-700' :
              'text-red-700'
            ]">
              {{ accionConfirmacion.mensaje }}
            </p>
          </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg border">
          <h5 class="font-semibold text-gray-800 mb-2">Información de la venta:</h5>
          <div class="space-y-2 text-sm">
            <div><strong>ID:</strong> #{{ accionConfirmacion.venta.id }}</div>
            <div><strong>Cliente:</strong> {{ accionConfirmacion.venta.cliente?.user?.name || 'N/A' }}</div>
            <div><strong>Email:</strong> {{ accionConfirmacion.venta.cliente?.user?.email || 'N/A' }}</div>
            <div><strong>Total:</strong> ${{ formatCurrency(accionConfirmacion.venta.total) }}</div>
            <div><strong>Estado:</strong>
              <span :class="'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-1 ' + getEstadoClass(accionConfirmacion.venta.estado)">
                {{ getEstadoLabel(accionConfirmacion.venta.estado) }}
              </span>
            </div>
          </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div class="flex items-start gap-2">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="text-yellow-500 text-sm mt-0.5" />
            <div class="text-sm text-yellow-800">
              <strong>Importante:</strong> {{ accionConfirmacion.importante }}
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <button
            :class="[
              'text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2',
              accionConfirmacion.color === 'green' ? 'bg-green-500 hover:bg-green-600' :
              accionConfirmacion.color === 'orange' ? 'bg-orange-500 hover:bg-orange-600' :
              'bg-red-500 hover:bg-red-600'
            ]"
            @click="ejecutarAccion"
            :disabled="
              (accionConfirmacion.tipo === 'procesar' && processingVentas.has(accionConfirmacion.venta?.id)) ||
              (accionConfirmacion.tipo === 'cancelar' && cancellingVentas.has(accionConfirmacion.venta?.id)) ||
              (accionConfirmacion.tipo === 'eliminar' && isDeleting)
            "
          >
            <FontAwesomeIcon
              :icon="
                (accionConfirmacion.tipo === 'procesar' && processingVentas.has(accionConfirmacion.venta?.id)) ? faSpinner :
                (accionConfirmacion.tipo === 'cancelar' && cancellingVentas.has(accionConfirmacion.venta?.id)) ? faSpinner :
                (accionConfirmacion.tipo === 'eliminar' && isDeleting) ? faSpinner :
                accionConfirmacion.icono
              "
              :class="[
                'h-4',
                {
                  'animate-spin':
                    (accionConfirmacion.tipo === 'procesar' && processingVentas.has(accionConfirmacion.venta?.id)) ||
                    (accionConfirmacion.tipo === 'cancelar' && cancellingVentas.has(accionConfirmacion.venta?.id)) ||
                    (accionConfirmacion.tipo === 'eliminar' && isDeleting)
                }
              ]"
            />
            {{
              (accionConfirmacion.tipo === 'procesar' && processingVentas.has(accionConfirmacion.venta?.id)) ? 'Procesando...' :
              (accionConfirmacion.tipo === 'cancelar' && cancellingVentas.has(accionConfirmacion.venta?.id)) ? 'Cancelando...' :
              (accionConfirmacion.tipo === 'eliminar' && isDeleting) ? 'Eliminando...' :
              accionConfirmacion.textoBoton
            }}
          </button>
            <button
                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
                @click="cancelarAccion"
                :disabled="
                (accionConfirmacion.tipo === 'procesar' && processingVentas.has(accionConfirmacion.venta?.id)) ||
                (accionConfirmacion.tipo === 'cancelar' && cancellingVentas.has(accionConfirmacion.venta?.id)) ||
                (accionConfirmacion.tipo === 'eliminar' && isDeleting)
                "
            >
                <FontAwesomeIcon :icon="faXmark" class="h-4" />
                Cerrar
            </button>
        </div>
      </template>
    </Dialog>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
  faCheck,
  faClock,
  faExclamationTriangle,
  faFilter,
  faPencil,
  faSpinner,
  faTrashCan,
  faXmark,
  faUser,
  faHashtag,
  faCalendar,
  faDollarSign,
  faBox,
  faRefresh,
  faEnvelope,
  faHandPointUp,
  faMagnifyingGlass,
  faLink,
  faInfo,
  faCheckCircle,
  faCopy,
  faExternalLinkAlt,
  faTrash,
  faLightbulb,
  faPhone
} from "@fortawesome/free-solid-svg-icons";
import { faWhatsapp } from "@fortawesome/free-brands-svg-icons";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import axios from 'axios';
axios.defaults.withCredentials = true;

const toast = useToast();

// Props
const props = defineProps({
  ventas: {
    type: [Array, Object],
    default: () => []
  }
});

// 📊 Estados reactivos
const ventas = ref(Array.isArray(props.ventas) ? props.ventas : (props.ventas?.data || []));
const ventaSeleccionada = ref(null);

// 👀 Watcher para actualizar ventas cuando cambien los props
watch(() => props.ventas, (newVentas) => {
  ventas.value = Array.isArray(newVentas) ? newVentas : (newVentas?.data || []);
}, { deep: true });

// 📝 Modal states
const modalDetalles = ref(false);
const modalConfirmacion = ref(false);
const accionConfirmacion = ref({
  tipo: null, // 'procesar', 'cancelar', 'eliminar'
  venta: null,
  titulo: '',
  mensaje: '',
  color: '',
  icono: null,
  textoBoton: '',
  importante: ''
});

// 🔗 Estados para generador de enlaces de Wompi
const generandoEnlace = ref(false);
const enlaceWompiGenerado = ref(null);
const showEnlaceWompi = ref(false);
const copiedSuccess = ref(false);

// Variables de loading
const isClearingFilters = ref(false);
const isDeleting = ref(false); // Solo para el modal
const isReloading = ref(false); // Solo para el botón de recargar

// Loading individual por venta
const processingVentas = ref(new Set());
const cancellingVentas = ref(new Set());

// 🔍 Filtros
const globalFilter = ref('');
const selectedEstado = ref("");
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  estado: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const filtros = ref({
  estado: null,
  fechaDesde: null,
  fechaHasta: null
});

const estadosOptions = ref([
  { name: 'Pendiente', value: 'pendiente' },
  { name: 'Completada', value: 'completada' },
  { name: 'Cancelada', value: 'cancelada' }
]);

// 📄 Paginación
const rowsPerPage = ref(10);
const rowsPerPageOptions = ref([5, 10, 20, 50]);

// 🔍 Ventas filtradas
const ventasFiltradas = computed(() => {
  let filtered = ventas.value;

  // Filtro por búsqueda global
  if (globalFilter.value) {
    const searchTerm = globalFilter.value.toLowerCase();
    filtered = filtered.filter(venta =>
      venta.cliente?.user?.name?.toLowerCase().includes(searchTerm) ||
      venta.id.toString().includes(searchTerm) ||
      venta.estado.toLowerCase().includes(searchTerm)
    );
  }

  // Filtro por estado
  if (selectedEstado.value) {
    filtered = filtered.filter(venta => venta.estado === selectedEstado.value);
  }

  // Filtro por fecha desde
  if (filtros.value.fechaDesde) {
    const fechaDesde = new Date(filtros.value.fechaDesde);
    filtered = filtered.filter(venta => new Date(venta.fecha) >= fechaDesde);
  }

  // Filtro por fecha hasta
  if (filtros.value.fechaHasta) {
    const fechaHasta = new Date(filtros.value.fechaHasta);
    filtered = filtered.filter(venta => new Date(venta.fecha) <= fechaHasta);
  }

  // 📋 Ordenamiento: Pendientes primero, luego completadas, luego canceladas
  return filtered.sort((a, b) => {
    // Prioridad 1: Estado (pendientes primero para atención inmediata)
    const estadoPrioridad = { 'pendiente': 1, 'completada': 2, 'cancelada': 3 };
    const prioridadA = estadoPrioridad[a.estado.toLowerCase()] || 4;
    const prioridadB = estadoPrioridad[b.estado.toLowerCase()] || 4;

    if (prioridadA !== prioridadB) {
      return prioridadA - prioridadB;
    }
    const fechaA = new Date(a.fecha);
    const fechaB = new Date(b.fecha);
    return fechaB - fechaA;
  });
});

const estadisticas = computed(() => {
  const stats = {
    pendientes: 0,
    completadas: 0,
    canceladas: 0,
    totalVendido: 0
  };

  ventas.value.forEach(venta => {
    switch (venta.estado) {
      case 'pendiente':
        stats.pendientes++;
        break;
      case 'completada':
        stats.completadas++;
        stats.totalVendido += parseFloat(venta.total || 0);
        break;
      case 'cancelada':
        stats.canceladas++;
        break;
    }
  });

  return stats;
});

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
  if (windowWidth.value < 640) {
    return { width: '95vw', maxWidth: '380px' };
  } else if (windowWidth.value < 768) {
    return { width: '80vw' };
  } else {
    return { width: '70vw', maxWidth: '900px' };
  }
});

// 🔍 Funciones para manejar filtros
const onEstadoFilterChange = () => {
  filters.value.estado.value = selectedEstado.value === "" ? null : selectedEstado.value;
};

const limpiarFiltros = async () => {
  isClearingFilters.value = true;

  try {
    // Simular un pequeño delay para mostrar el loading
    await new Promise(resolve => setTimeout(resolve, 300));

    selectedEstado.value = "";
    globalFilter.value = '';
    filters.value.global.value = null;
    filters.value.estado.value = null;
    filtros.value = {
      estado: null,
      fechaDesde: null,
      fechaHasta: null
    };

    toast.add({
      severity: "success",
      summary: "Filtros limpiados",
      life: 2000
    });
  } finally {
    isClearingFilters.value = false;
  }
};

const aplicarFiltros = () => {
  // Los filtros se aplican automáticamente a través del computed
};

// 🔄 Función para recargar ventas con toasts
const fetchVentasWithToasts = async () => {
  isReloading.value = true;

  // Mostrar toast de carga
  toast.add({
    severity: "info",
    summary: "Cargando ventas...",
    life: 2000
  });

  try {
    // Usar Inertia para recargar la página actual con los datos actualizados
    await router.reload({
      only: ['ventas'], // Solo recargar los datos de ventas
      preserveScroll: true, // Mantener la posición del scroll
      preserveState: true // Mantener el estado de los filtros
    });

    // Mostrar toast de éxito después de la recarga
    setTimeout(() => {
      toast.add({
        severity: "success",
        summary: "Ventas actualizadas",
        detail: `${ventas.value.length} registros cargados`,
        life: 2000
      });
    }, 300);

  } catch (error) {
    console.error('Error al cargar las ventas:', error);
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "No se pudieron cargar las ventas",
      life: 3000
    });
  } finally {
    // El loading se desactiva después de la recarga
    setTimeout(() => {
      isReloading.value = false;
    }, 500);
  }
};

// 📝 Funciones principales
// ❌ELIMINADO: verDetalles - Se puede hacer clic en la fila para ver detalles

// ❌ELIMINADO: confirmarProcesar - Las ventas se crean directamente como completadas
const confirmarProcesar = (venta) => {
  // Función deshabilitada - las ventas ya no requieren procesamiento manual
  console.warn('confirmarProcesar() - Función deshabilitada: las ventas se crean como completadas');
};

// ❌ELIMINADO: procesarVenta - Las ventas se crean directamente como completadas
const procesarVenta = async () => {
  const venta = accionConfirmacion.value.venta;
  if (!venta) return;

  try {
    // Función deshabilitada - las ventas ya no requieren procesamiento manual
    console.warn('procesarVenta() - Función deshabilitada: las ventas se crean como completadas');

    toast.add({
      severity: 'info',
      summary: 'Información',
      detail: 'Las ventas se procesan automáticamente al crear el pago',
      life: 3000
    });

    modalConfirmacion.value = false;
    accionConfirmacion.value = { tipo: null, venta: null };
  } finally {
    processingVentas.value.delete(venta.id);
  }
};

const confirmarCancelar = (venta) => {
  accionConfirmacion.value = {
    tipo: 'cancelar',
    venta: venta,
    titulo: '¿Cancelar esta venta?',
    mensaje: 'Esta acción cancelará la venta y restaurará el stock si estaba completada.',
    color: 'orange',
    icono: faXmark,
    textoBoton: 'Sí, Cancelar',
    importante: 'Si la venta estaba completada, se restaurará automáticamente el stock de todos los productos.'
  };
  modalConfirmacion.value = true;
};

const cancelarVenta = async () => {
  const venta = accionConfirmacion.value.venta;
  if (!venta) return;

  cancellingVentas.value.add(venta.id);
  try {
    const response = await axios.post(`/api/ventas/${venta.id}/cancelar`);

    // Actualizar la venta en la lista local sin recargar
    const index = ventas.value.findIndex(v => v.id === venta.id);
    if (index !== -1) {
      ventas.value[index] = { ...ventas.value[index], estado: 'cancelada' };
    }

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Venta cancelada correctamente',
      life: 3000
    });

    modalConfirmacion.value = false;
    accionConfirmacion.value = { tipo: null, venta: null };
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al cancelar la venta',
      life: 3000
    });
  } finally {
    cancellingVentas.value.delete(venta.id);
  }
};

const confirmarEliminacion = (venta) => {
  accionConfirmacion.value = {
    tipo: 'eliminar',
    venta: venta,
    titulo: '¿Eliminar esta venta?',
    mensaje: 'Esta acción no se puede deshacer.',
    color: 'red',
    icono: faExclamationTriangle,
    textoBoton: 'Sí, Eliminar',
    importante: venta.estado === 'completada'
      ? 'Se restaurará el stock de todos los productos de esta venta.'
      : 'Esta venta será eliminada permanentemente del sistema.'
  };
  modalConfirmacion.value = true;
};

const eliminarVenta = async () => {
  const venta = accionConfirmacion.value.venta;
  if (!venta) return;

  isDeleting.value = true;
  try {
    const response = await axios.delete(`/api/ventas/${venta.id}/eliminar`);

    // Remover la venta de la lista local
    const index = ventas.value.findIndex(v => v.id === venta.id);
    if (index !== -1) {
      ventas.value.splice(index, 1);
    }

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Venta eliminada correctamente',
      life: 3000
    });

    modalConfirmacion.value = false;
    accionConfirmacion.value = { tipo: null, venta: null };
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al eliminar la venta',
      life: 3000
    });
  } finally {
    isDeleting.value = false;
  }
};

// Función unificada para ejecutar la acción confirmada
const ejecutarAccion = () => {
  switch (accionConfirmacion.value.tipo) {
    case 'procesar':
      procesarVenta();
      break;
    case 'cancelar':
      cancelarVenta();
      break;
    case 'eliminar':
      eliminarVenta();
      break;
  }
};

const cancelarAccion = () => {
  modalConfirmacion.value = false;
  accionConfirmacion.value = { tipo: null, venta: null };
};

// 🔗 Funciones para generar enlaces de pago Wompi
const generarEnlaceWompi = async () => {
  if (!ventaSeleccionada.value) return;

  generandoEnlace.value = true;
  enlaceWompiGenerado.value = null;
  showEnlaceWompi.value = false;

  try {
    const venta = ventaSeleccionada.value;
    const productos = venta.detalle_ventas || [];

    // Preparar datos para Wompi
    const productosData = productos.map(detalle => ({
      id: detalle.producto.id,
      nombre: detalle.producto.nombre,
      precio: detalle.precio_unitario,
      cantidad: detalle.cantidad,
      imagen: detalle.producto.primera_imagen || null,
      subtotal: detalle.subtotal
    }));

    const descripcion = `Venta #${venta.id} - ${productos.length} producto(s) - Cliente: ${venta.cliente?.user?.name || 'Cliente'}`;

    const response = await axios.post('/api/wompi/payment-link', {
      customer_email: venta.cliente?.user?.email || '',
      amount: parseFloat(venta.total),
      description: descripcion,
      reference: `VENTA-${venta.id}-${Date.now()}`,
      customer_name: venta.cliente?.user?.name || 'Cliente',
      venta_id: venta.id,
      productos: productosData
    });

    if (response.data.success) {
      enlaceWompiGenerado.value = {
        payment_link: response.data.payment_link,
        reference: response.data.reference,
        link_id: response.data.link_id
      };
      showEnlaceWompi.value = true;

      toast.add({
        severity: 'success',
        summary: '¡Enlace generado!',
        detail: 'Enlace de pago de Wompi creado exitosamente',
        life: 4000
      });
    } else {
      throw new Error(response.data.message || 'Error al generar enlace');
    }

  } catch (error) {
    console.error('Error generando enlace Wompi:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'No se pudo generar el enlace de pago',
      life: 5000
    });
  } finally {
    generandoEnlace.value = false;
  }
};

// Función para copiar enlace al portapapeles
const copiarEnlace = async () => {
  if (!enlaceWompiGenerado.value?.payment_link) return;

  try {
    await navigator.clipboard.writeText(enlaceWompiGenerado.value.payment_link);
    copiedSuccess.value = true;

    toast.add({
      severity: 'success',
      summary: '¡Copiado!',
      detail: 'Enlace copiado al portapapeles',
      life: 3000
    });

    setTimeout(() => {
      copiedSuccess.value = false;
    }, 3000);
  } catch (error) {
    // Fallback para navegadores que no soportan clipboard API
    const textArea = document.createElement('textarea');
    textArea.value = enlaceWompiGenerado.value.payment_link;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);

    toast.add({
      severity: 'info',
      summary: 'Enlace copiado',
      detail: 'Enlace copiado usando método alternativo',
      life: 3000
    });
  }
};

// Función para abrir enlace en nueva pestaña
const abrirEnlace = () => {
  if (enlaceWompiGenerado.value?.payment_link) {
    window.open(enlaceWompiGenerado.value.payment_link, '_blank');
  }
};

// Función para limpiar el enlace generado
const limpiarEnlace = () => {
  enlaceWompiGenerado.value = null;
  showEnlaceWompi.value = false;
  copiedSuccess.value = false;
};

// Función para cerrar el modal y limpiar estado
const cerrarModal = () => {
  modalDetalles.value = false;
  // Limpiar estado del generador de enlaces al cerrar
  setTimeout(() => {
    limpiarEnlace();
  }, 200);
};

// Función para manejar el clic en la fila
const onRowClick = (event) => {
  // Verificar si el clic fue en un botón para evitar abrir el modal
  const target = event.originalEvent.target;
  const isButton = target.closest('button');

  if (!isButton) {
    ventaSeleccionada.value = event.data;
    modalDetalles.value = true;
  }
};

// Helpers
const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
};

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const getEstadoClass = (estado) => {
  switch (estado) {
    case 'pendiente':
      return 'bg-yellow-100 text-yellow-800';
    case 'completada':
      return 'bg-green-100 text-green-800';
    case 'cancelada':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getEstadoLabel = (estado) => {
  switch (estado) {
    case 'pendiente':
      return 'Pendiente de Pago';
    case 'completada':
      return 'Completada';
    case 'cancelada':
      return 'Cancelada';
    default:
      return 'Desconocido';
  }
};

// Función para abrir Gmail
const abrirGmail = (email) => {
  if (!email) return;

  // Detectar si es móvil
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  if (isMobile) {
    // En móviles, usar mailto: que el sistema operativo maneje
    window.location.href = `mailto:${email}`;
  } else {
    // En escritorio, abrir Gmail web directamente
    window.open(`https://mail.google.com/mail/?view=cm&fs=1&to=${email}`, '_blank');
  }
};

// 🔄 Auto-refresh de ventas y toasts de carga
let refreshInterval = null;

const refreshVentas = () => {
  // Recargar datos usando Inertia
  router.reload({
    only: ['ventas'],
    preserveScroll: true,
    preserveState: true
  });
};

onMounted(() => {
  // Toast de carga inicial
  toast.add({
    severity: "info",
    summary: "Cargando ventas...",
    life: 2000
  });

  // Toast de éxito después de cargar
  setTimeout(() => {
    toast.add({
      severity: "success",
      summary: "Ventas cargadas",
      life: 2000
    });
  }, 1000);

  // 🔄 Auto-refresh cada 30 segundos para actualizar estados de pago
  refreshInterval = setInterval(() => {
    refreshVentas();
  }, 30000); // 30 segundos
});

// Limpiar interval al salir del componente
onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});

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
</style>
