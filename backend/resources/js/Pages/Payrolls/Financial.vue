<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import useChart from '@/Composables/useChart';
import { ArrowUpRight, FileText } from 'lucide-vue-next';

const props = defineProps({
    summary: Object,
    monthlyPayments: Array,
    topEmployees: Array,
});

const chartCanvas = ref(null);
const { renderChart, destroyChart } = useChart();

const chartData = computed(() => ({
    labels: props.monthlyPayments.map((payment) => payment.label),
    values: props.monthlyPayments.map((payment) => Number(payment.total_paid)),
}));

const currency = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
});

const formatNumber = (value) => new Intl.NumberFormat('es-CO').format(value ?? 0);

const renderFinancialChart = () => {
    renderChart(chartCanvas, chartData.value, {
        label: 'Total pagado',
        borderColor: '#0EA5E9',
        backgroundColor: 'rgba(14, 165, 233, 0.18)',
        xAxisLabel: 'Mes',
        yAxisLabel: 'COP',
        tooltipLabel: (context) => currency.format(context.parsed.y),
        showLegend: false,
    });
};

onMounted(() => {
    renderFinancialChart();
});

watch(chartData, () => {
    renderFinancialChart();
});

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<template>
    <AppLayout title="Resumen financiero">
        <div class="space-y-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">
                        <ArrowUpRight class="h-4 w-4" />
                        Informe de costos
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Análisis financiero de nómina
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Visualiza los pagos de nómina, horas y empleados más costosos de los últimos 12 meses.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2">
                    <button
                        @click="() => window.print()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition shadow-lg shadow-slate-500/20 active:scale-95 print:hidden"
                    >
                        <FileText class="h-4 w-4" />
                        Exportar PDF
                    </button>
                    <Link
                        :href="route('payrolls.index')"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 print:hidden"
                    >
                        Volver a nóminas
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total pagado</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ currency.format(summary.total_paid) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Horas totales</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(summary.total_hours) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Empleados pagados</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(summary.employee_count) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Promedio por empleado</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ currency.format(summary.avg_pay_per_employee) }}</p>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pagos mensuales</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Últimos 12 meses de nómina pagada.</p>
                    </div>
                </div>
                <div class="mt-6 h-72">
                    <canvas ref="chartCanvas" class="h-full w-full"></canvas>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Top 5 empleados</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Empleados con mayor costo de nómina en el período.</p>
                    </div>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-sm uppercase tracking-[0.2em] text-slate-500">
                                <th class="px-4 py-3">Empleado</th>
                                <th class="px-4 py-3">Documento</th>
                                <th class="px-4 py-3 text-right">Costo total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="employee in topEmployees"
                                :key="employee.id"
                                class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-950"
                            >
                                <td class="px-4 py-4 text-sm text-slate-900 dark:text-slate-100">{{ employee.name }}</td>
                                <td class="px-4 py-4 text-sm text-slate-500 dark:text-slate-400">{{ employee.documento }}</td>
                                <td class="px-4 py-4 text-right text-sm font-semibold text-slate-900 dark:text-slate-100">{{ currency.format(employee.total_cost) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
