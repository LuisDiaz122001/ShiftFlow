<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChevronLeft,
    Calendar,
    Clock,
    DollarSign,
    CheckCircle2,
    Clock4,
    ArrowRight,
    FileText,
    Download
} from 'lucide-vue-next';
import ShiftBreakdown from '@/Components/ShiftBreakdown.vue';

const props = defineProps({
    payroll: Object,
    shifts: Array,
});

const formPay = useForm({});

const markAsPaid = () => {
    if (confirm('¿Está seguro de marcar esta nómina como pagada?')) {
        formPay.patch(route('payrolls.pay', props.payroll.id));
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value);
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
</script>

<template>
    <AppLayout :title="'Liquidación #' + payroll.id">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('payrolls.index')"
                        class="p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-indigo-600 transition-all shadow-sm"
                    >
                        <ChevronLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Liquidación #{{ payroll.id }}</h1>
                        <div class="flex items-center gap-2 mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                            <span>{{ payroll.employee.user.name }}</span>
                            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span>{{ payroll.fecha_inicio }} al {{ payroll.fecha_fin }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl border text-sm font-bold capitalize"
                        :class="getStatusBadge(payroll.estado)"
                    >
                        <CheckCircle2 v-if="payroll.estado === 'paid'" class="w-4 h-4" />
                        <Clock4 v-else class="w-4 h-4" />
                        {{ payroll.estado === 'paid' ? 'Pagado' : (payroll.estado === 'locked' ? 'Cerrado' : 'Pendiente') }}
                    </span>

                    <a 
                        v-if="payroll.estado === 'locked' || payroll.estado === 'paid'"
                        :href="route('payrolls.export', payroll.id)"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-2xl font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <Download class="w-4 h-4" />
                        PDF
                    </a>

                    <button 
                        v-if="payroll.estado !== 'paid'"
                        @click="markAsPaid"
                        class="px-5 py-2.5 bg-emerald-600 text-white rounded-2xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/25"
                    >
                        Marcar como Pagada
                    </button>
                </div>
            </div>

            <!-- Summary Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Total Horas</p>
                    <div class="flex items-end justify-between relative z-10">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">{{ payroll.total_hours }}h</h3>
                        <Clock class="w-8 h-8 text-indigo-500/20" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Horas Diurnas</p>
                    <div class="flex items-end justify-between relative z-10">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">{{ payroll.diurnas_hours }}h</h3>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                            D
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Horas Nocturnas</p>
                    <div class="flex items-end justify-between relative z-10">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">{{ payroll.nocturnas_hours }}h</h3>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            N
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-600 p-6 rounded-3xl border border-indigo-500 shadow-xl shadow-indigo-500/20 relative overflow-hidden">
                    <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest mb-3">Neto a Pagar</p>
                    <div class="flex items-end justify-between text-white relative z-10">
                        <h3 class="text-3xl font-black">{{ formatCurrency(payroll.total_pago) }}</h3>
                        <DollarSign class="w-8 h-8 opacity-40" />
                    </div>
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Shifts Detail Section -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl">
                        <FileText class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Turnos Incluidos</h3>
                </div>

                <div class="space-y-4">
                    <div 
                        v-for="shift in shifts" 
                        :key="shift.id"
                        class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-sm"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="flex items-center gap-6">
                                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl text-slate-400 dark:text-slate-500">
                                    <Clock class="w-6 h-6" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-3 text-slate-900 dark:text-white font-bold text-lg">
                                        <span>{{ new Date(shift.fecha_inicio).toLocaleDateString() }}</span>
                                        <ArrowRight class="w-4 h-4 text-slate-300 dark:text-slate-700" />
                                        <span>{{ new Date(shift.fecha_fin).toLocaleDateString() }}</span>
                                    </div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                        {{ new Date(shift.fecha_inicio).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }} - 
                                        {{ new Date(shift.fecha_fin).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Individual Shift Breakdown -->
                            <div class="flex-grow lg:max-w-2xl">
                                <ShiftBreakdown 
                                    v-if="shift.calculation"
                                    :calculation="shift.calculation"
                                />
                                <div v-else class="text-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl text-slate-400 italic">
                                    Cálculos no disponibles para este turno
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
