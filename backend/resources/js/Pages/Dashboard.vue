<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/Admin/Breadcrumbs.vue';
import MetricCard from '@/Components/Dashboard/MetricCard.vue';
import { computed, reactive, ref, watch, nextTick, onBeforeUnmount } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import useChart from '@/Composables/useChart';
import {
    AlertCircle,
    Ban,
    BarChart3,
    CalendarCheck,
    CheckCircle,
    Clock3,
    Filter,
    TrendingUp,
    UserCheck,
    Users,
    Wallet,
} from 'lucide-vue-next';

const props = defineProps({
    stats: { type: Object, required: true },
    role: { type: String, required: true },
    employeesCount: { type: Number, default: 0 },
    employees: { type: Array, default: () => [] },
    topEmployees: { type: Array, default: () => [] },
    hoursPerDay: { type: Array, default: () => [] },
    payrollTrend: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});

const isNavigating = ref(false);
const stopRouterStart = router.on('start', () => {
    isNavigating.value = true;
});
const stopRouterFinish = router.on('finish', () => {
    isNavigating.value = false;
});

const form = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    employee_id: props.filters.employee_id ?? '',
});

const quickPayrollForm = useForm({
    employee_id: '',
    period_start: '',
    period_end: '',
});

const isAdminOrSupervisor = computed(() => ['admin', 'supervisor'].includes(props.role));
const canGenerateQuickPayroll = computed(() => quickPayrollForm.period_start && quickPayrollForm.period_end);

const generatePayroll = () => {
    quickPayrollForm.post(route('payrolls.store'), {
        preserveScroll: true,
        onSuccess: () => {
            quickPayrollForm.reset();
        },
    });
};

const goToPayrolls = () => router.get(route('payrolls.index'));
const goToPayrollFinancial = () => router.get(route('payrolls.financial'));

const statusDistribution = computed(() => {
    const items = [
        { key: 'pending', label: 'Pendientes', value: props.stats.pending_count ?? 0, color: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' },
        { key: 'approved', label: 'Aprobados', value: props.stats.approved_count ?? 0, color: 'bg-emerald-500', badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' },
        { key: 'void', label: 'Anulados', value: props.stats.void_count ?? 0, color: 'bg-rose-500', badge: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' },
    ];

    const total = items.reduce((sum, item) => sum + item.value, 0);

    return items.map((item) => ({
        ...item,
        percentage: total > 0 ? Math.round((item.value / total) * 100) : 0,
    }));
});

const cards = computed(() => {
    const operational = [
        { id: 'hours-today', title: 'Horas hoy', value: props.stats.hours_today, icon: Clock3, variant: 'info', formatType: 'hours' },
        { id: 'hours-week', title: 'Horas semana', value: props.stats.hours_week, icon: BarChart3, variant: 'info', formatType: 'hours' },
        { id: 'hours-month', title: 'Horas mes', value: props.stats.hours_month, icon: TrendingUp, variant: 'info', formatType: 'hours' },
        { id: 'pending', title: 'Turnos pendientes', value: props.stats.pending_count, icon: AlertCircle, variant: 'warning', formatType: 'number' },
        { id: 'attendance', title: 'Asistencias hoy', value: props.stats.attendance_today, icon: CalendarCheck, variant: 'success', formatType: 'number' },
        { id: 'attendance-open', title: 'Check-in abiertos', value: props.stats.attendance_open, icon: UserCheck, variant: 'warning', formatType: 'number' },
    ];

    const rangeCards = [
        { id: 'hours', title: 'Horas en rango', value: props.stats.total_hours_worked, icon: BarChart3, variant: 'info', formatType: 'hours' },
        { id: 'paid', title: 'Pago turnos (rango)', value: props.stats.total_paid, icon: Wallet, variant: 'success', formatType: 'currency' },
        { id: 'payroll-acc', title: 'Nómina acumulada', value: props.stats.payroll_accumulated, icon: Wallet, variant: 'success', formatType: 'currency' },
        { id: 'approved', title: 'Turnos aprobados', value: props.stats.approved_count, icon: CheckCircle, variant: 'success', formatType: 'number' },
        { id: 'void', title: 'Turnos anulados', value: props.stats.void_count, icon: Ban, variant: 'danger', formatType: 'number' },
    ];

    if (!isAdminOrSupervisor.value) {
        return [...operational, ...rangeCards];
    }

    return [
        { id: 'employees', title: 'Empleados activos', value: props.stats.active_employees, icon: Users, variant: 'info', formatType: 'number' },
        { id: 'employees-total', title: 'Empleados totales', value: props.employeesCount, icon: Users, variant: 'info', formatType: 'number' },
        ...operational,
        ...rangeCards,
    ];
});

const hoursChartCanvas = ref(null);
const payrollChartCanvas = ref(null);
const { renderChart: renderHoursChart, destroyChart: destroyHoursChart } = useChart();
const { renderChart: renderPayrollChart, destroyChart: destroyPayrollChart } = useChart();

const toNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
};

const formatDayLabel = (value) => {
    if (typeof value !== 'string' || !value) {
        return '';
    }

    const parsed = new Date(`${value}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
    }).format(parsed);
};

const formatMonthLabel = (value) => {
    if (typeof value !== 'string' || !value) {
        return '';
    }

    const [year, month] = value.split('-').map((item) => Number(item));
    if (!year || !month) {
        return value;
    }

    const parsed = new Date(year, month - 1, 1);
    return new Intl.DateTimeFormat('es-CO', {
        month: 'short',
        year: 'numeric',
    }).format(parsed);
};

const normalizeHoursPerDay = (hours) => {
    const entries = Array.isArray(hours) ? hours : [];

    return {
        labels: entries.map((entry, index) => formatDayLabel(entry?.date ?? String(index))),
        values: entries.map((entry) => toNumber(entry?.total_hours)),
    };
};

const normalizePayrollTrend = (trend) => {
    const entries = Array.isArray(trend) ? trend : [];

    return {
        labels: entries.map((entry) => formatMonthLabel(entry?.month)),
        values: entries.map((entry) => toNumber(entry?.total)),
    };
};

const hoursChartData = computed(() => normalizeHoursPerDay(props.hoursPerDay));
const payrollChartData = computed(() => normalizePayrollTrend(props.payrollTrend));

const renderCharts = async () => {
    await nextTick();

    if (import.meta.env.DEV) {
        console.debug('[Dashboard] chart payload', {
            hoursPerDay: props.hoursPerDay,
            payrollTrend: props.payrollTrend,
            normalizedHours: hoursChartData.value,
            normalizedPayroll: payrollChartData.value,
        });
    }

    if (hoursChartData.value.labels.length > 0) {
        renderHoursChart(hoursChartCanvas, hoursChartData.value, {
            label: 'Horas trabajadas',
            yAxisLabel: 'Horas',
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.15)',
        });
    } else {
        destroyHoursChart();
    }

    if (payrollChartData.value.labels.length > 0) {
        renderPayrollChart(payrollChartCanvas, payrollChartData.value, {
            label: 'Nómina',
            yAxisLabel: 'COP',
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.15)',
            tooltipLabel: (ctx) => new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                maximumFractionDigits: 0,
            }).format(ctx.parsed.y),
        });
    } else {
        destroyPayrollChart();
    }
};

watch([hoursChartData, payrollChartData], renderCharts, { deep: true, immediate: true });

onBeforeUnmount(() => {
    stopRouterStart?.();
    stopRouterFinish?.();
    destroyHoursChart();
    destroyPayrollChart();
});

const applyFilters = () => {
    router.get(route('dashboard'), {
        date_from: form.date_from || undefined,
        date_to: form.date_to || undefined,
        employee_id: form.employee_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    form.date_from = '';
    form.date_to = '';
    form.employee_id = '';
    applyFilters();
};

const payrollEmployeeOptions = computed(() => {
    return [{ id: '', nombre: 'Todos' }, ...props.employees];
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div v-if="isNavigating" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/20 backdrop-blur-sm">
            <div class="rounded-2xl bg-white px-6 py-4 text-sm font-medium shadow-lg dark:bg-slate-900">Actualizando métricas...</div>
        </div>

        <div class="space-y-8">
            <Breadcrumbs :items="[{ label: 'Dashboard' }]" />

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Panel de control
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Resumen operativo por periodo para el perfil
                        <span class="font-semibold capitalize text-indigo-600 dark:text-indigo-400">{{ role }}</span>.
                    </p>
                </div>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                        <Filter class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Filtros</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Refina las métricas por fecha y empleado.</p>
                    </div>
                </div>

                <form class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" @submit.prevent="applyFilters">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha desde</label>
                        <input
                            v-model="form.date_from"
                            type="date"
                            class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha hasta</label>
                        <input
                            v-model="form.date_to"
                            type="date"
                            class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        />
                    </div>

                    <div v-if="isAdminOrSupervisor">
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Empleado</label>
                        <select
                            v-model="form.employee_id"
                            class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        >
                            <option value="">Todos</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                        >
                            Aplicar
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                            @click="resetFilters"
                        >
                            Limpiar
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="isAdminOrSupervisor" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Gestión de Nómina</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Accede rápidamente al módulo de nómina y genera liquidaciones sin salir del dashboard.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="goToPayrolls"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            Ver nóminas
                        </button>
                        <button
                            type="button"
                            @click="goToPayrollFinancial"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            Análisis financiero
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-2 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Empleado</label>
                            <select
                                v-model="quickPayrollForm.employee_id"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                                <option v-for="employee in payrollEmployeeOptions" :key="employee.id" :value="employee.id">{{ employee.nombre }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Periodo desde</label>
                            <input
                                v-model="quickPayrollForm.period_start"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Periodo hasta</label>
                            <input
                                v-model="quickPayrollForm.period_end"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            />
                        </div>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            @click="generatePayroll"
                            :disabled="!canGenerateQuickPayroll"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600"
                        >
                            Crear nómina
                        </button>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <MetricCard
                    v-for="card in cards"
                    :key="card.id"
                    v-bind="card"
                />
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Distribución de turnos por estado</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Participación relativa de turnos pendientes, aprobados y anulados en el rango actual.
                    </p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="item in statusDistribution"
                        :key="item.key"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="h-3 w-3 rounded-full" :class="item.color"></span>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ item.label }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="item.badge">
                                    {{ item.value }} turnos
                                </span>
                                <span class="w-12 text-right text-sm font-semibold text-slate-900 dark:text-white">{{ item.percentage }}%</span>
                            </div>
                        </div>

                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="item.color"
                                :style="{ width: `${item.percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Horas trabajadas por día</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Evolución diaria de horas trabajadas en el periodo seleccionado.
                    </p>
                </div>

                <div v-if="hoursPerDay.length === 0" class="rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700">
                    Sin horas en el rango seleccionado. Ajusta las fechas o ejecuta <code class="text-xs">php artisan app:demo-data --fresh</code>.
                </div>
                <div v-else class="h-96">
                    <canvas ref="hoursChartCanvas" class="h-full w-full"></canvas>
                </div>
            </section>

            <section v-if="isAdminOrSupervisor" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tendencia de nómina (6 meses)</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Suma de nóminas pendientes y pagadas por mes.</p>
                </div>
                <div v-if="payrollTrend.length === 0" class="rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center text-sm text-slate-500">
                    Sin datos de nómina. Procesa un periodo desde Nómina → Periodos.
                </div>
                <div v-else class="h-80">
                    <canvas ref="payrollChartCanvas" class="h-full w-full"></canvas>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Top 5 empleados por horas</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Acumulado de horas aprobadas en el rango seleccionado.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-950/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Empleado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Horas trabajadas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            <tr v-if="topEmployees.length === 0">
                                <td colspan="2" class="px-6 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No hay datos para el rango seleccionado.
                                </td>
                            </tr>
                            <tr v-for="employee in topEmployees" :key="employee.employee_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ employee.user_name || employee.nombre }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ employee.total_hours }} h
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
