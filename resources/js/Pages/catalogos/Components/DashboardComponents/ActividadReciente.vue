<template>
    <!-- Actividad Reciente -->
    <div class="bg-gray-50 rounded-lg shadow-xl border border-gray-100 p-4 sm:p-6">
    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 tracking-wide">
        <FontAwesomeIcon :icon="faClock" class="text-blue-500 mr-2" />
        Actividad Reciente
    </h3>
    <div class="space-y-2 sm:space-y-3 max-h-48 sm:max-h-64 overflow-y-auto">
            <!-- Ventas Recientes -->
            <div v-for="venta in (dashboardData.ventas || []).slice(0, 3)" :key="'venta-' + venta.id"
                class="flex items-center justify-between p-2 sm:p-3 bg-white rounded-lg border border-gray-100 shadow-sm">
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                    <div class="p-1.5 sm:p-2 bg-yellow-100 rounded-full flex-shrink-0">
                        <FontAwesomeIcon :icon="faShoppingCart" class="text-yellow-500 text-xs sm:text-sm" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                            Venta #{{ venta.id }}
                            <span v-if="venta.detalle_ventas && venta.detalle_ventas.length > 0" class="hidden sm:inline text-gray-500">
                                • {{ venta.detalle_ventas.length }} producto{{ venta.detalle_ventas.length !== 1 ? 's' : '' }}
                            </span>
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ new Date(venta.created_at || venta.fecha).toLocaleString('es-ES', {
                                year: 'numeric', month: '2-digit', day: '2-digit',
                                hour: '2-digit', minute: '2-digit', hour12: true
                            }) }}
                            <span v-if="venta.cliente && venta.cliente.user" class="ml-1">• {{ venta.cliente.user.name }}</span>
                        </p>
                    </div>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-yellow-600 flex-shrink-0 ml-2">
                    <span class="hidden sm:inline">${{ Number(venta.total || 0).toLocaleString('es-ES') }}</span>
                    <span class="sm:hidden">${{ formatValueForMobile('$' + (venta.total || 0)) }}</span>
                </span>
            </div>

            <!-- Reservas Recientes -->
            <div v-for="reserva in (dashboardData.reservas || []).slice(0, 3)" :key="'reserva-' + reserva.id"
                class="flex items-center justify-between p-2 sm:p-3 rounded-lg border shadow-sm bg-white"
                :class="{
                    'border-yellow-100': reserva.estado === 'PENDIENTE',
                    'border-green-100': reserva.estado === 'CONFIRMADA',
                    'border-blue-100': reserva.estado === 'EN_CURSO',
                    'border-purple-100': reserva.estado === 'FINALIZADA',
                    'border-red-100': reserva.estado === 'CANCELADA',
                    'border-orange-100': reserva.estado === 'REPROGRAMADA'
                }">
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                    <div class="p-1.5 sm:p-2 rounded-full flex-shrink-0"
                        :class="{
                            'bg-yellow-100': reserva.estado === 'PENDIENTE',
                            'bg-green-100': reserva.estado === 'CONFIRMADA',
                            'bg-blue-100': reserva.estado === 'EN_CURSO',
                            'bg-purple-100': reserva.estado === 'FINALIZADA',
                            'bg-red-100': reserva.estado === 'CANCELADA',
                            'bg-orange-100': reserva.estado === 'REPROGRAMADA'
                        }">
                        <FontAwesomeIcon
                            :icon="reserva.estado === 'EN_CURSO' ? faTruck : faCalendar"
                            class="text-xs sm:text-sm"
                            :class="{
                                'text-yellow-500': reserva.estado === 'PENDIENTE',
                                'text-green-500': reserva.estado === 'CONFIRMADA',
                                'text-blue-500': reserva.estado === 'EN_CURSO',
                                'text-purple-500': reserva.estado === 'FINALIZADA',
                                'text-red-500': reserva.estado === 'CANCELADA',
                                'text-orange-500': reserva.estado === 'REPROGRAMADA'
                            }" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                            {{ reserva.entidad_nombre || reserva.tour_nombre || 'Tour/Servicio' }}
                            <span v-if="reserva.mayores_edad" class="hidden sm:inline text-gray-500 font-normal">
                                • {{ reserva.mayores_edad }}{{ reserva.menores_edad ? '+' + reserva.menores_edad : '' }} p
                            </span>
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ new Date(reserva.fecha_reserva).toLocaleString('es-ES', {
                                year: 'numeric', month: '2-digit', day: '2-digit',
                                hour: '2-digit', minute: '2-digit', hour12: true
                            }) }}
                            • {{ (reserva.cliente && reserva.cliente.user && reserva.cliente.user.name) ||
                               (reserva.cliente && reserva.cliente.nombres) ||
                               'Cliente no asignado' }}
                        </p>
                    </div>
                </div>
                <span class="inline-flex px-2 sm:px-3 py-1 text-xs font-bold rounded-full flex-shrink-0 ml-2 shadow"
                      :class="{
                          'bg-yellow-100 text-yellow-800': reserva.estado === 'PENDIENTE',
                          'bg-green-100 text-green-800': reserva.estado === 'CONFIRMADA',
                          'bg-blue-100 text-blue-800': reserva.estado === 'EN_CURSO',
                          'bg-purple-100 text-purple-800': reserva.estado === 'FINALIZADA',
                          'bg-red-100 text-red-800': reserva.estado === 'CANCELADA',
                          'bg-orange-100 text-orange-800': reserva.estado === 'REPROGRAMADA'
                      }">
                    <span class="hidden sm:inline">{{ reserva.estado }}</span>
                    <span class="sm:hidden">{{ reserva.estado ? reserva.estado.substring(0, 3) : '' }}</span>
                </span>
            </div>

            <div v-if="(!dashboardData.ventas || dashboardData.ventas.length === 0) && (!dashboardData.reservas || dashboardData.reservas.length === 0)"
                class="text-center py-6 sm:py-8">
                <FontAwesomeIcon :icon="faBox" class="text-gray-400 text-xl sm:text-2xl mb-2" />
                <p class="text-gray-500 text-xs sm:text-sm font-semibold">No hay actividad reciente</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClock, faShoppingCart, faCalendar, faBox, faTruck } from '@fortawesome/free-solid-svg-icons';

defineProps({
    dashboardData: {
        type: Object,
        required: true
    },
    formatValueForMobile: {
        type: Function,
        required: true
    }
});
</script>
