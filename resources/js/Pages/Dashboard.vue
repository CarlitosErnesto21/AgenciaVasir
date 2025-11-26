<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch } from "vue";
import Chart from 'primevue/chart';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTimes, faCheck, faClock, faExclamationTriangle, faWallet, faUsers, faBox, faCheckCircle } from '@fortawesome/free-solid-svg-icons';
import axios from 'axios';

// Importar componentes separados
import LoadingState from '@/Pages/Catalogos/Components/DashboardComponents/LoadingState.vue';
import MetricasCard from '@/Pages/Catalogos/Components/DashboardComponents/MetricasCard.vue';
import WidgetsSecundarios from '@/Pages/Catalogos/Components/DashboardComponents/WidgetsSecundarios.vue';
import ActividadReciente from '@/Pages/Catalogos/Components/DashboardComponents/ActividadReciente.vue';
import ModalesInteractivos from '@/Pages/Catalogos/Components/DashboardComponents/ModalesInteractivos.vue';
import GraficosSection from '@/Pages/Catalogos/Components/DashboardComponents/GraficosSection.vue';

import FormularioDatosCompletos from '@/Pages/Profile/FormularioDatosCompletos.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const user = page.props.user;
const isAdmin = computed(() => user && user.roles && user.roles.some(r => r.name === 'Administrador'));
const empleado = computed(() => user && user.empleado ? user.empleado : null);

const showCompletarDatos = ref(false);
const empleadoIncompleto = computed(() => {
    if (!user) return false;
    const emp = empleado.value;
    return isAdmin.value && (!emp || !emp.cargo || !emp.telefono);
});

// Mostrar el modal si el empleado está incompleto
onMounted(() => {
    if (empleadoIncompleto.value) {
        showCompletarDatos.value = true;
    }
});

watch(empleadoIncompleto, (nuevo) => {
    if (nuevo) {
        showCompletarDatos.value = true;
    }
});

const chartDataPie = ref();
const chartDataBar = ref();
const chartOptionsPie = ref();
const chartOptionsBar = ref();

const loading = ref(true);
const dashboardData = ref({});

const cacheData = ref(null);
const cacheExpiry = ref(null);
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutos de caché

const metrics = ref([
    {
        label: 'Ventas Hoy',
        value: '0',
        icon: 'pi pi-shopping-cart',
        color: 'from-[#ef233c] to-[#d90429]',   // Rojo vibrante degradado
        text: 'text-[#7b1f26]',                 // Rojo profundo
        key: 'ventas_hoy',
        category: 'ventas',
        description: 'Ventas del día'
    },
    {
        label: 'Ingresos Hoy',
        value: '$0',
        icon: 'pi pi-dollar',
        color: 'from-[#ffd600] to-[#ffb700]',   // Amarillo vibrante degradado
        text: 'text-[#a68700]',                 // Amarillo oscuro
        key: 'ingresos_hoy',
        category: 'ventas',
        description: 'Ingresos del día'
    },
    {
        label: 'Reservas del Mes',
        value: '0',
        icon: 'pi pi-calendar-plus',
        color: 'from-[#6c00f9] to-[#480ca8]',  // Púrpura intenso degradado
        text: 'text-[#240046]',                // Púrpura profundo
        key: 'reservas_mes',
        category: 'reservas',
        description: 'Reservas este mes'
    },
    {
        label: 'Tours Activos',
        value: '0',
        icon: 'pi pi-map-marker',
        color: 'from-[#0077b6] to-[#023e8a]',  // Azul fuerte degradado
        text: 'text-[#03045e]',                // Azul profundo
        key: 'tours_activos',
        category: 'reservas',
        description: 'Tours disponibles'
    }
]);

// 📈 Widgets adicionales con colores sólidos y contrastantes
const widgets = ref([
    {
        title: 'Reservas Pendientes',
        value: '0',
        icon: 'faClock',
        color: 'bg-[#fff3b0] border-[#ffd600]',     // Fondo amarillo pálido, borde amarillo vibrante
        iconColor: 'text-[#ffd600]',                // Amarillo fuerte
        key: 'reservas_pendientes'
    },
    {
        title: 'Productos Stock Bajo',
        value: '0',
        icon: 'faExclamationTriangle',
        color: 'bg-[#ffe5e5] border-[#ef233c]',     // Fondo rosado pálido, borde rojo vibrante
        iconColor: 'text-[#d90429]',                // Rojo fuerte
        key: 'productos_stock_bajo'
    },
    {
        title: 'Valor Inventario',
        value: '$0',
        icon: 'faWallet',
        color: 'bg-[#e5daff] border-[#6c00f9]',     // Fondo lavanda, borde púrpura intenso
        iconColor: 'text-[#480ca8]',                // Púrpura fuerte
        key: 'valor_total_inventario'
    },
    {
        title: 'Clientes Activos',
        value: '0',
        icon: 'faUsers',
        color: 'bg-[#d0f1ff] border-[#0077b6]',     // Fondo azul claro, borde azul vibrante
        iconColor: 'text-[#023e8a]',                // Azul fuerte
        key: 'clientes_activos'
    }
]);

// 🎯 Estados para interactividad
const showProductosStockBajoModal = ref(false);

const toggleProductosStockBajoModal = () => {
    showProductosStockBajoModal.value = !showProductosStockBajoModal.value;
};

// ✅ FUNCIÓN para formatear valores en mobile
const formatValueForMobile = (value, originalData = null) => {
    // Manejar valores numéricos directos (sin símbolo $)
    if (typeof value === 'number') {
        if (value >= 1000000) {
            return '$' + (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return '$' + (value / 1000).toFixed(1) + 'k';
        } else if (value >= 100) {
            return '$' + Math.round(value);
        } else {
            return '$' + value.toFixed(2);
        }
    }

    // Manejar valores string que contienen $
    if (typeof value === 'string' && value.includes('$')) {
        let numericValue;

        // Si tenemos acceso a los datos originales, usarlos
        if (originalData && originalData.valor_total_inventario) {
            numericValue = parseFloat(originalData.valor_total_inventario);
        } else {
            // Intentar extraer el número del valor formateado (manejar tanto . como , como separador decimal)
            const cleanValue = value.replace(/[$\s]/g, '').replace(/,/g, '.');
            numericValue = parseFloat(cleanValue);
        }

        if (isNaN(numericValue)) {
            return '$0';
        }

        if (numericValue >= 1000000) {
            return '$' + (numericValue / 1000000).toFixed(1) + 'M';
        } else if (numericValue >= 1000) {
            return '$' + (numericValue / 1000).toFixed(1) + 'k';
        } else if (numericValue >= 100) {
            return '$' + Math.round(numericValue);
        } else {
            return '$' + numericValue.toFixed(2);
        }
    }
    return value;
};

// ✅ FUNCIÓN OPTIMIZADA para obtener datos del dashboard
const fetchDashboardData = async (forceRefresh = false) => {
    // 🚀 VERIFICAR CACHÉ antes de hacer llamadas API
    const now = Date.now();
    if (!forceRefresh && cacheData.value && cacheExpiry.value && now < cacheExpiry.value) {
        dashboardData.value = cacheData.value;
        updateMetrics(
            dashboardData.value.inventario,
            dashboardData.value.ventas,
            dashboardData.value.reservas,
            dashboardData.value.resumenReservas,
            dashboardData.value.tours
        );
        updateCharts(
            dashboardData.value.inventario,
            dashboardData.value.ventas,
            dashboardData.value.stockBajo,
            dashboardData.value.reservas,
            dashboardData.value.resumenReservas
        );
        loading.value = false;
        return;
    }

    loading.value = true;

    try {
        // ⚡ CONFIGURACIÓN de timeout y headers optimizada
        const apiConfig = {
            headers: { 'Accept': 'application/json' },
            timeout: 10000 // 10 segundos timeout
        };

        // 🔄 LLAMADAS API OPTIMIZADAS EN PARALELO con Promise.allSettled para mejor manejo de errores
        const [
            inventarioResult,
            ventasResult,
            stockBajoResult,
            reservasResult,
            resumenReservasResult,
            toursResult
        ] = await Promise.allSettled([
            // 📊 Datos esenciales del inventario
            axios.get('/api/inventario/resumen', apiConfig),

            // 💰 Solo ventas de los últimos 30 días para optimizar
            axios.get('/api/ventas?desde=' + new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], apiConfig),

            // 📦 Stock bajo - solo los críticos
            axios.get('/api/inventario/stock-bajo?limit=20', apiConfig),

            // 🗓️ Solo reservas recientes (últimos 60 días) para dashboard
            axios.get('/api/reservas?per_page=100&desde=' + new Date(Date.now() - 60 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], apiConfig),

            // 📈 Resumen de reservas (más liviano)
            axios.get('/api/reservas/resumen', apiConfig),

            // 🗺️ Solo tours activos para optimizar
            axios.get('/api/tours?estado=activo,disponible&limit=50', apiConfig)
        ]);

        // ✅ EXTRACCIÓN SEGURA de datos con fallbacks
        const inventarioData = inventarioResult.status === 'fulfilled'
            ? (inventarioResult.value.data.data || inventarioResult.value.data || {})
            : {};

        const ventasData = ventasResult.status === 'fulfilled'
            ? (ventasResult.value.data.data || ventasResult.value.data || [])
            : [];

        const stockBajoData = stockBajoResult.status === 'fulfilled'
            ? (stockBajoResult.value.data.data || stockBajoResult.value.data || [])
            : [];

        const reservasData = reservasResult.status === 'fulfilled'
            ? (reservasResult.value.data.data || reservasResult.value.data || [])
            : [];

        const resumenReservasData = resumenReservasResult.status === 'fulfilled'
            ? (resumenReservasResult.value.data.data || resumenReservasResult.value.data || [])
            : [];

        const toursData = toursResult.status === 'fulfilled'
            ? (toursResult.value.data.data || toursResult.value.data || [])
            : [];

        // 🔄 Actualizar métricas y gráficos
        updateMetrics(inventarioData, ventasData, reservasData, resumenReservasData, toursData);
        updateCharts(inventarioData, ventasData, stockBajoData, reservasData, resumenReservasData);

        dashboardData.value = {
            inventario: inventarioData,
            ventas: ventasData,
            stockBajo: stockBajoData,
            reservas: reservasData,
            resumenReservas: resumenReservasData,
            tours: toursData
        };

        // 💾 GUARDAR EN CACHÉ para siguientes cargas
        cacheData.value = { ...dashboardData.value };
        cacheExpiry.value = Date.now() + CACHE_DURATION;

    } catch (error) {
        // En caso de error, usar valores por defecto
        updateMetrics({}, [], [], [], []);
        updateCharts({}, [], [], [], []);
    } finally {
        loading.value = false;
    }
};

// 🔄 FUNCIÓN para actualizar métricas
const updateMetrics = (inventarioData, ventasData, reservasData, resumenReservasData, toursData) => {
    const ventas = Array.isArray(ventasData) ? ventasData : [];
    const reservas = Array.isArray(reservasData) ? reservasData : [];
    const tours = Array.isArray(toursData) ? toursData : [];

    // Calcular ventas completadas de hoy (usando fecha local para evitar problemas de zona horaria)
    const ahora = new Date();
    const hoy = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}-${String(ahora.getDate()).padStart(2, '0')}`;
    const ventasHoy = ventas.filter(venta => {
        if (!venta.fecha) return false;
        const fechaVenta = venta.fecha.split('T')[0];
        const esDeHoy = fechaVenta === hoy;
        const esCompletada = venta.estado && venta.estado.toLowerCase() === 'completada';
        return esDeHoy && esCompletada;
    }).length;

    // Calcular ingresos de hoy de ventas completadas
    const ingresosHoyVentas = ventas.filter(venta => {
        if (!venta.fecha) return false;
        const fechaVenta = venta.fecha.split('T')[0];
        const esDeHoy = fechaVenta === hoy;
        const esCompletada = venta.estado && venta.estado.toLowerCase() === 'completada';
        return esDeHoy && esCompletada;
    }).reduce((total, venta) => total + (parseFloat(venta.total) || 0), 0);

    // Calcular reservas finalizadas del mes actual
    const mesActual = new Date().getMonth();
    const añoActual = new Date().getFullYear();
    const reservasDelMes = reservas.filter(reserva => {
        if (!reserva.fecha_reserva && !reserva.fecha) return false;
        const fechaReserva = new Date(reserva.fecha_reserva || reserva.fecha);
        const esDeMesActual = fechaReserva.getMonth() === mesActual && fechaReserva.getFullYear() === añoActual;
        const esFinalizada = reserva.estado && reserva.estado.toUpperCase() === 'FINALIZADA';
        return esDeMesActual && esFinalizada;
    }).length;

    // Tours activos (solo con estado DISPONIBLE)
    const toursActivos = tours.filter(tour =>
        tour.estado && tour.estado.toUpperCase() === 'DISPONIBLE'
    ).length;

    // ✅ Actualizar valores principales
    metrics.value[0].value = ventasHoy.toString();
    metrics.value[1].value = `$${ingresosHoyVentas.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    metrics.value[1].rawValue = ingresosHoyVentas; // Valor numérico para móviles
    metrics.value[2].value = reservasDelMes.toString();
    metrics.value[3].value = toursActivos.toString();

    // ✅ Actualizar widgets adicionales
    const reservasPendientes = reservas.filter(reserva =>
        reserva.estado && reserva.estado.toUpperCase() === 'PENDIENTE'
    ).length;
    const clientesActivos = new Set(reservas.map(r => r.cliente?.id).filter(id => id)).size;

    widgets.value[0].value = reservasPendientes.toString();
    widgets.value[1].value = (inventarioData.productos_stock_bajo || 0).toString();
    widgets.value[2].value = `$${Number(inventarioData.valor_total_inventario || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    widgets.value[2].rawValue = Number(inventarioData.valor_total_inventario || 0); // Valor numérico para móviles
    widgets.value[3].value = clientesActivos.toString();
};

// 📈 FUNCIÓN para actualizar gráficos
const updateCharts = (inventarioData, ventasData, stockBajoData, reservasData, resumenReservasData) => {
    const totalProductos = inventarioData.total_productos || 0;
    const stockBajo = inventarioData.productos_stock_bajo || 0;
    const agotados = inventarioData.productos_agotados || 0;
    const disponibles = inventarioData.productos_disponibles || Math.max(0, totalProductos - stockBajo - agotados);
    const ventas = Array.isArray(ventasData) ? ventasData : [];
    const reservas = Array.isArray(reservasData) ? reservasData : [];

    // 🥧 Gráfico PIE: Estado del inventario
    chartDataPie.value = {
        labels: ['Productos Disponibles', 'Stock Bajo', 'Agotados'],
        datasets: [{
            label: 'Estado del Inventario',
            data: [disponibles, stockBajo, agotados],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            hoverBackgroundColor: ['#34d399', '#fbbf24', '#f87171']
        }]
    };

    // 📊 Gráfico BAR: Reservas por estado
    const reservasPorEstado = reservas.reduce((acc, reserva) => {
        const estado = reserva.estado || 'Sin estado';
        acc[estado] = (acc[estado] || 0) + 1;
        return acc;
    }, {});

    if (Object.keys(reservasPorEstado).length === 0) {
        reservasPorEstado['Sin reservas'] = 0;
    }

    chartDataBar.value = {
        labels: Object.keys(reservasPorEstado),
        datasets: [{
            label: 'Reservas por Estado',
            data: Object.values(reservasPorEstado),
            backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#3b82f6']
        }]
    };

    // 🍩 Gráfico DOUGHNUT: Tours más reservados
        // Eliminar la lógica del doughnut chart
};

// Opciones de gráficos
const setChartOptionsPie = () => ({
    maintainAspectRatio: false,
    plugins: {
        title: {
            display: true,
            text: 'Estado del Inventario',
            font: { size: 18 }
        },
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                usePointStyle: true,
                color: '#374151'
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return `${context.label}: ${context.parsed} productos`;
                }
            }
        }
    }
});

const setChartOptionsBar = () => ({
    maintainAspectRatio: false,
    plugins: {
        title: {
            display: true,
            text: 'Reservas por Estado',
            font: { size: 18 }
        },
        legend: {
            display: false
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return `${context.label}: ${context.parsed.y} reservas`;
                }
            }
        }
    },
    scales: {
        x: {
            ticks: { color: '#374151' },
            grid: { color: '#e5e7eb' }
        },
        y: {
            beginAtZero: true,
            ticks: { color: '#374151' },
            grid: { color: '#e5e7eb' }
        }
    }
});


onMounted(() => {
    chartOptionsPie.value = setChartOptionsPie();
    chartOptionsBar.value = setChartOptionsBar();
    fetchDashboardData();
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <Toast class="z-[9999]" />
        <FormularioDatosCompletos
            v-if="showCompletarDatos"
            :empleado="empleado && empleado.value ? empleado.value : {}"
            :user-id="user && user.id ? user.id : null"
            @close="showCompletarDatos = false"
        />
        <!-- Estado de carga -->
        <LoadingState v-if="loading" />

        <!-- Contenido principal -->
        <div v-else class="px-2 sm:px-4 lg:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6 mt-1 sm:mt-1">
            <!-- Header con botón de actualizar -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl sm:text-3xl font-bold text-blue-600">Panel Administrativo</h1>
                <button
                    @click="fetchDashboardData(true)"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                >
                    <i class="pi pi-refresh" :class="{'animate-spin': loading}"></i>
                    <span>{{ loading ? 'Actualizando...' : 'Actualizar' }}</span>
                </button>
            </div>
            <!-- Métricas principales -->
            <MetricasCard
                :metrics="metrics"
                :dashboard-data="dashboardData"
                :format-value-for-mobile="formatValueForMobile"
            />

            <!-- 📈 LAYOUT PRINCIPAL TIPO GRID - Responsive -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

                <!-- Widgets secundarios -->
                <WidgetsSecundarios
                    :widgets="widgets"
                    :dashboard-data="dashboardData"
                    :format-value-for-mobile="formatValueForMobile"
                    @toggle-stock-modal="toggleProductosStockBajoModal"
                />

                <!-- COLUMNA CENTRAL: Gráfico Principal - Responsive -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">Inventario por Estado</h3>

                    <!-- Texto informativo -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-3 sm:mb-4">
                        <div class="flex items-start space-x-2">
                            <i class="pi pi-chart-pie text-purple-500 text-sm mt-0.5 flex-shrink-0"></i>
                            <div class="text-xs sm:text-sm text-purple-700">
                                <p class="font-medium mb-1">Estado actual del inventario</p>
                                <p class="text-purple-600">Productos disponibles (stock > mínimo), stock bajo (stock ≤ mínimo) y agotados (stock = 0).</p>
                            </div>
                        </div>
                    </div>

                    <div class="h-48 sm:h-64">
                        <Chart v-if="chartDataPie"
                            type="pie"
                            :data="chartDataPie"
                            :options="chartOptionsPie"
                            class="w-full h-full" />
                        <div v-else class="flex items-center justify-center h-full">
                            <p class="text-gray-500 text-xs sm:text-sm">No hay datos de inventario</p>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Estadísticas de Reservas - Responsive -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">Reservas por Estado</h3>

                    <!-- Texto informativo -->
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3 sm:mb-4">
                        <div class="flex items-start space-x-2">
                            <i class="pi pi-chart-bar text-orange-500 text-sm mt-0.5 flex-shrink-0"></i>
                            <div class="text-xs sm:text-sm text-orange-700">
                                <p class="font-medium mb-1">Distribución de reservas por estado</p>
                                <p class="text-orange-600">Muestra todas las reservas registradas agrupadas por su estado actual en el sistema.</p>
                            </div>
                        </div>
                    </div>

                    <div class="h-48 sm:h-64">
                        <Chart v-if="chartDataBar"
                            type="bar"
                            :data="chartDataBar"
                            :options="chartOptionsBar"
                            class="w-full h-full" />
                        <div v-else class="flex items-center justify-center h-full">
                            <p class="text-gray-500 text-xs sm:text-sm">No hay datos de reservas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🏆 SECCIÓN INFERIOR: Top Tours y Ventas - Responsive -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6">

                <!-- Top 5 Tours Más Reservados -->
                <GraficosSection
                    :resumen-reservas-data="dashboardData.resumenReservas"
                    :reservas-data="dashboardData.reservas"
                />

                <!-- Actividad Reciente -->
                <ActividadReciente
                    :dashboard-data="dashboardData"
                    :format-value-for-mobile="formatValueForMobile"
                />
            </div>
        </div>

        <!-- Modales interactivos -->
        <ModalesInteractivos
            :show-productos-stock-bajo-modal="showProductosStockBajoModal"
            :dashboard-data="dashboardData"
            @close-stock-modal="showProductosStockBajoModal = false"
        />
    </AuthenticatedLayout>
</template>
