<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChevronLeft,
    Calendar,
    DollarSign,
    CheckCircle2,
    Clock4,
    FileText,
    Download,
    Receipt,
} from 'lucide-vue-next';

const props = defineProps({
    payroll: Object,
    shifts: Array,
});

const formPay = useForm({});

const markAsPaid = () => {
    if (confirm('Esta seguro de marcar esta nomina como pagada?')) {
        formPay.patch(route('payrolls.updateStatus', props.payroll.id), {
            estado: 'paid',
        });
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
}).format(value ?? 0);

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
</script>

<template>
    <AppLayout :title="'Nomina #' + payroll.id">
        <div class="space-y-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('payrolls.index')"
                        class="p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-indigo-600 transition-all shadow-sm"
                    >
                        <ChevronLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Nomina #{{ payroll.id }}</h1>
                        <div class="flex items-center gap-2 mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                            <span>{{ payroll.employee.user.name }}</span>
                            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span>{{ payroll.cycle?.fecha_inicio ?? '-' }} al {{ payroll.cycle?.fecha_fin ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl border text-sm font-bold capitalize" :class="getStatusBadge(payroll.estado)">
                        <CheckCircle2 v-if="payroll.estado === 'paid'" class="w-4 h-4" />
                        <Clock4 v-else class="w-4 h-4" />
                        {{ payroll.estado === 'paid' ? 'Pagado' : payroll.estado }}
                    </span>

                    <a
                        :href="route('payrolls.pdf', payroll.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-2xl font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <Download class="w-4 h-4" />
                        Descargar PDF
                    </a>

                    <button
                        v-if="payroll.estado !== 'paid'"
                        @click="markAsPaid"
                        class="px-5 py-2.5 bg-emerald-600 text-white rounded-2xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/25"
                    >
                        Marcar como pagada
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Sueldo base</p>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white">{{ formatCurrency(payroll.salario_base_pagado) }}</h3>
                </div>

                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Recargos</p>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white">{{ formatCurrency(payroll.recargos_pagados) }}</h3>
                </div>

                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Deducciones</p>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white">
                        {{ formatCurrency((payroll.deduccion_salud ?? 0) + (payroll.deduccion_pension ?? 0)) }}
                    </h3>
                </div>

                <div class="bg-indigo-600 p-6 rounded-3xl border border-indigo-500 shadow-xl shadow-indigo-500/20">
                    <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest mb-3">Neto a pagar</p>
                    <div class="flex items-end justify-between text-white">
                        <h3 class="text-3xl font-black">{{ formatCurrency(payroll.neto_pagado) }}</h3>
                        <DollarSign class="w-8 h-8 opacity-40" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] gap-6">
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
                        <FileText class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Detalle contable</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-950/40">
                                <tr>
                                    <th class="px-6 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Concepto</th>
                                    <th class="px-6 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-right font-bold text-slate-500 uppercase tracking-wider">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="detail in payroll.details" :key="detail.id">
                                    <td class="px-6 py-4 text-slate-900 dark:text-white font-medium">{{ detail.label }}</td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400 uppercase">{{ detail.type }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-slate-900 dark:text-white">{{ formatCurrency(detail.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <Calendar class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Ciclo asociado</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500 dark:text-slate-400">Inicio</dt>
                                <dd class="font-semibold text-slate-900 dark:text-white">{{ payroll.cycle?.fecha_inicio ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500 dark:text-slate-400">Fin</dt>
                                <dd class="font-semibold text-slate-900 dark:text-white">{{ payroll.cycle?.fecha_fin ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500 dark:text-slate-400">Pago</dt>
                                <dd class="font-semibold text-slate-900 dark:text-white">{{ payroll.fecha_pago ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500 dark:text-slate-400">Version</dt>
                                <dd class="font-semibold text-slate-900 dark:text-white">{{ payroll.version }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <Receipt class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Turnos del ciclo</h3>
                        </div>
                        <div class="space-y-3">
                            <div v-for="shift in shifts" :key="shift.id" class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ new Date(shift.fecha_inicio).toLocaleDateString() }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ new Date(shift.fecha_inicio).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                            -
                                            {{ new Date(shift.fecha_fin).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(shift.total_pago) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ shift.total_hours }} h</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="shifts.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                                No hay turnos aprobados asociados a este ciclo.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
