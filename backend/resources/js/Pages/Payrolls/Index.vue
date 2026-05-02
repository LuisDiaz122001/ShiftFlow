<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Wallet,
    Eye,
    Calendar,
    Receipt,
    CheckCircle2,
    Clock4,
    ShieldCheck,
} from 'lucide-vue-next';

const props = defineProps({
    payrolls: Object,
    employees: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canGenerate: { type: Boolean, default: false },
});

const filterForm = useForm({
    employee_id: props.filters.employee_id ?? '',
    estado: props.filters.estado ?? '',
    period_start: props.filters.period_start ?? '',
    period_end: props.filters.period_end ?? '',
});

const generateForm = useForm({
    employee_id: props.filters.employee_id ?? '',
    period_start: props.filters.period_start ?? '',
    period_end: props.filters.period_end ?? '',
});

const payForm = useForm({});

const totalPayrollAmount = computed(() => props.payrolls.data.reduce((sum, payroll) => sum + Number(payroll.total_pagado ?? 0), 0));
const totalPayrolls = computed(() => props.payrolls.data.length);

const applyFilters = () => {
    filterForm.get(route('payrolls.index'), {
        preserveState: true,
        replace: true,
    });
};

const generatePayroll = () => {
    generateForm.post(route('payrolls.store'));
};

const markAsPaid = (payrollId) => {
    if (! confirm('¿Seguro que desea marcar esta nómina como pagada?')) {
        return;
    }

    payForm.patch(route('payrolls.updateStatus', payrollId), {
        estado: 'paid',
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20';
        case 'locked':
            return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
        default:
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/20';
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
}).format(value ?? 0);
</script>

<template>
    <AppLayout title="Nomina">
        <div class="space-y-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <Wallet class="h-4 w-4" />
                        Nomina por ciclo
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Liquidaciones generadas
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Este listado solo muestra nominas generadas desde ciclos de pago.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <Link
                            :href="route('payrolls.dashboard')"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition active:scale-95"
                        >
                            <ShieldCheck class="h-4 w-4" />
                            Panel Operativo
                        </Link>
                        <Link
                            :href="route('payrolls.financial')"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            Ver análisis financiero
                        </Link>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 p-4 border border-slate-200 dark:border-slate-800">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total nóminas</p>
                        <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ totalPayrolls }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 p-4 border border-slate-200 dark:border-slate-800">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total devengado</p>
                        <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(totalPayrollAmount) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Filtros</h2>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Empleado</label>
                            <select
                                v-model="filterForm.employee_id"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                                <option value="">Todos</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Estado</label>
                            <select
                                v-model="filterForm.estado"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                                <option value="">Todos</option>
                                <option value="pending">pending</option>
                                <option value="paid">paid</option>
                                <option value="cancelled">cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Periodo desde</label>
                            <input
                                v-model="filterForm.period_start"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Periodo hasta</label>
                            <input
                                v-model="filterForm.period_end"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                        >
                            Aplicar filtros
                        </button>
                        <button
                            type="button"
                            @click="() => { filterForm.reset(); applyFilters(); }"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            Limpiar
                        </button>
                    </div>
                </section>

                <section v-if="canGenerate" class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Generar nómina</h2>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Empleado</label>
                            <select
                                v-model="generateForm.employee_id"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                                <option value="" disabled>Seleccione un empleado</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Inicio del periodo</label>
                            <input
                                v-model="generateForm.period_start"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Fin del periodo</label>
                            <input
                                v-model="generateForm.period_end"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            />
                        </div>
                        <button
                            type="button"
                            @click="generatePayroll"
                            class="mt-2 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition"
                        >
                            Generar nómina
                        </button>
                    </div>
                </section>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Empleado</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Ciclo</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Devengado</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Neto</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-center">Estado</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="payroll in payrolls.data" :key="payroll.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ payroll.employee.user.name.substring(0, 1).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">{{ payroll.employee.user.name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ payroll.employee.documento }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="w-4 h-4 text-slate-400" />
                                        <span>
                                            {{ payroll.cycle?.fecha_inicio ?? '-' }} - {{ payroll.cycle?.fecha_fin ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                        <Receipt class="w-3.5 h-3.5" />
                                        Pago: {{ payroll.fecha_pago ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-900 dark:text-white">
                                    {{ formatCurrency(payroll.total_pagado) }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">
                                    {{ formatCurrency(payroll.neto_pagado) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold capitalize" :class="getStatusBadge(payroll.estado)">
                                        <CheckCircle2 v-if="payroll.estado === 'paid'" class="w-3.5 h-3.5" />
                                        <Clock4 v-else class="w-3.5 h-3.5" />
                                        {{ payroll.estado === 'paid' ? 'Pagado' : payroll.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="route('payrolls.show', payroll.id)"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                                        >
                                            <Eye class="w-5 h-5" />
                                        </Link>
                                        <button
                                            v-if="canGenerate && payroll.estado !== 'paid'"
                                            @click="markAsPaid(payroll.id)"
                                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition"
                                        >
                                            Pagar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="payrolls.data.length === 0">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <Wallet class="w-12 h-12 text-slate-200 dark:text-slate-700 mb-4" />
                                        <p class="text-slate-500 dark:text-slate-400 font-medium">No existen nominas generadas.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
