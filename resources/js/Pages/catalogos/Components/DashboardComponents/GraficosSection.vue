<template>
    <!-- Top 5 Tours Más Reservados -->
    <div class="bg-gray-50 rounded-lg shadow-xl border border-[#fbeee6] p-4 sm:p-6 mt-4 sm:mt-6">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
            <i class="pi pi-star text-yellow-500 mr-2"></i>
            <span class="hidden sm:inline">Top 5 Tours Más Reservados</span>
            <span class="sm:hidden">Top Tours</span>
        </h3>

        <!-- Texto informativo -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3 sm:mb-4">
            <div class="flex items-start space-x-2">
                <i class="pi pi-info-circle text-blue-500 text-sm mt-0.5 flex-shrink-0"></i>
                <div class="text-xs sm:text-sm text-blue-700">
                    <p class="font-medium mb-1">Mostrando tours finalizados de los últimos 6 meses</p>
                    <p class="text-blue-600">Se actualiza automáticamente según la fecha actual. Solo incluye tours completados ordenados por cantidad de reservas.</p>
                </div>
            </div>
        </div>

        <div class="h-48 sm:h-64">
            <Chart v-if="chartDataDoughnut"
                type="doughnut"
                :data="chartDataDoughnut"
                :options="chartOptionsDoughnut"
                class="w-full h-full" />
            <div v-else class="flex items-center justify-center h-full">
                <p class="text-gray-500 text-xs sm:text-sm">No hay reservas registradas</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    // ...solo props para el doughnut
    resumenReservasData: {
        type: Array,
        required: false,
        default: () => []
    },
    reservasData: {
        type: Array,
        required: false,
        default: () => []
    }
});

const chartOptionsDoughnut = {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
        title: {
            display: true,
            text: 'Tours Más Reservados',
            font: { size: 18 }
        },
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                usePointStyle: true,
                color: '#374151',
                padding: 10,
                font: {
                    size: function(context) {
                        // Texto más pequeño en móviles
                        return window.innerWidth < 640 ? 10 : 12;
                    }
                },
                generateLabels: function(chart) {
                    const data = chart.data;
                    if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, i) => {
                            const dataset = data.datasets[0];
                            const value = dataset.data[i];
                            // Truncar labels para móviles
                            const truncatedLabel = window.innerWidth < 640 && label.length > 20
                                ? label.substring(0, 17) + '...'
                                : label;
                            return {
                                text: `${truncatedLabel} (${value})`,
                                fillStyle: dataset.backgroundColor[i],
                                strokeStyle: dataset.borderColor ? dataset.borderColor[i] : dataset.backgroundColor[i],
                                lineWidth: dataset.borderWidth || 0,
                                hidden: false,
                                index: i
                            };
                        });
                    }
                    return [];
                }
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return `${context.label}: ${context.parsed} reservas`;
                }
            }
        }
    }
};

const chartDataDoughnut = computed(() => {
    // Lógica igual que en Dashboard.vue
    const toursReservados = {};
    const resumen = props.resumenReservasData;
    const reservas = props.reservasData;

    if (resumen && resumen.length > 0) {
        resumen.forEach(res => {
            if (res.tipo === 'tours' && res.nombre) {
                const total = (res.total_pendientes || 0) + (res.total_confirmadas || 0);
                if (total > 0) {
                    const nombreCorto = res.nombre.length > 30 ? res.nombre.substring(0, 27) + '...' : res.nombre;
                    toursReservados[nombreCorto] = total;
                }
            }
        });
    }
    if (Object.keys(toursReservados).length === 0 && reservas && reservas.length > 0) {
        reservas.forEach(reserva => {
            if (reserva.entidad_nombre) {
                const nombreCorto = reserva.entidad_nombre.length > 30 ? reserva.entidad_nombre.substring(0, 27) + '...' : reserva.entidad_nombre;
                toursReservados[nombreCorto] = (toursReservados[nombreCorto] || 0) + 1;
            }
        });
    }
    let topTours = Object.entries(toursReservados)
        .sort(([,a], [,b]) => b - a)
        .slice(0, 5);
    if (topTours.length === 0) {
        topTours = [['Sin reservas', 0]];
    }
    return {
        labels: topTours.map(([nombre]) => nombre),
        datasets: [{
            label: 'Tours Más Reservados',
            data: topTours.map(([, cantidad]) => cantidad),
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    };
});
</script>
