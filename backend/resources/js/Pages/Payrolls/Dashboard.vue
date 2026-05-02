<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    AlertCircle, 
    CheckCircle2, 
    CreditCard, 
    FileText, 
    Layers, 
    Play, 
    TrendingUp, 
    Users 
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recent_pending: Array,
});

const showGenerateModal = ref(false);
const showPayModal = ref(false);

const generateForm = useForm({
    period_start: props.stats.active_cycle?.fecha_inicio || '',
    period_end: props.stats.active_cycle?.fecha_fin || '',
});

const payForm = useForm({
    payroll_ids: props.recent_pending.map(p => p.id),
});

const submitBulkGenerate = () => {
    generateForm.post(route('payrolls.bulkStore'), {
        onSuccess: () => showGenerateModal.value = false,
    });
};

const submitBulkPay = () => {
    payForm.post(route('payrolls.bulkPay'), {
        onSuccess: () => showPayModal.value = false,
    });
};

const currency = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
});

const formatNumber = (value) => new Intl.NumberFormat('es-CO').format(value ?? 0);
</script>

<template>
    <AppLayout title="Operación de Nómina">
        <div class="space-y-8 pb-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Panel Operativo</h1>
                    <p class="mt-1 text-slate-500 dark:text-slate-400">Gestión masiva y monitoreo de pagos.</p>
                </div>
                <div class="flex gap-3">
                    <button 
                        @click="showGenerateModal = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all active:scale-95"
                    >
                        <Play class="h-4 w-4 fill-current" />
                        Generar Mes
                    </button>
                    <button 
                        @click="showPayModal = true"
                        :disabled="stats.pending_count === 0"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <CreditCard class="h-4 w-4" />
                        Pagar Pendientes
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-amber-500/5 transition-transform group-hover:scale-150"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <AlertCircle class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Pendientes</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumber(stats.pending_count) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-500/5 transition-transform group-hover:scale-150"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <TrendingUp class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Por Pagar</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ currency.format(stats.pending_amount) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-indigo-500/5 transition-transform group-hover:scale-150"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Pagado este mes</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ currency.format(stats.paid_this_month) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-slate-500/5 transition-transform group-hover:scale-150"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            <Layers class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Ciclo Activo</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white truncate max-w-[120px]">
                                {{ stats.active_cycle ? stats.active_cycle.fecha_inicio.substring(0, 10) : 'Ninguno' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Recent Pending -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <AlertCircle class="h-5 w-5 text-amber-500" />
                            Nóminas por Pagar
                        </h2>
                        <Link :href="route('payrolls.index', { estado: 'pending' })" class="text-sm font-medium text-indigo-600 hover:underline">Ver todas</Link>
                    </div>
                    
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                                <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-4">Empleado</th>
                                    <th class="px-6 py-4">Total</th>
                                    <th class="px-6 py-4 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="payroll in recent_pending" :key="payroll.id" class="group hover:bg-slate-50 transition-colors dark:hover:bg-slate-800/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 dark:bg-slate-800">
                                                {{ payroll.employee.nombre.charAt(0) }}
                                            </div>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ payroll.employee.nombre }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ currency.format(payroll.total_amount) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <Link 
                                            :href="route('payrolls.show', payroll.id)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-white hover:text-indigo-600 hover:shadow-sm transition-all dark:border-slate-700 dark:text-slate-500"
                                        >
                                            <FileText class="h-4 w-4" />
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="recent_pending.length === 0">
                                    <td colspan="3" class="px-6 py-10 text-center text-slate-500 italic">No hay nóminas pendientes de pago.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Useful Links / Alerts -->
                <div class="space-y-6">
                    <div class="rounded-3xl bg-indigo-600 p-6 text-white shadow-xl shadow-indigo-500/20">
                        <h3 class="text-lg font-bold">Resumen Mensual</h3>
                        <p class="mt-2 text-indigo-100 text-sm">Accede al análisis detallado de costos y proyecciones.</p>
                        <Link 
                            :href="route('payrolls.financial')"
                            class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-white/20 px-4 py-2.5 text-sm font-bold backdrop-blur-md hover:bg-white/30 transition-all"
                        >
                            Ver Reporte Completo
                        </Link>
                    </div>

                    <div v-if="stats.active_cycle" class="rounded-3xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900/50 dark:bg-amber-900/20">
                        <div class="flex items-start gap-3">
                            <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400 shrink-0" />
                            <div>
                                <h4 class="font-bold text-amber-900 dark:text-amber-300">Periodo Abierto</h4>
                                <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                                    El ciclo {{ stats.active_cycle.fecha_inicio.substring(0, 10) }} sigue abierto. Recuerda cerrarlo tras verificar los pagos.
                                </p>
                                <Link 
                                    :href="route('payrolls.periods')"
                                    class="mt-4 inline-block text-sm font-bold text-amber-800 underline dark:text-amber-300"
                                >
                                    Gestionar Periodos
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals (Simplified as pseudo-modals or using template if available) -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Generación Masiva</h3>
                <p class="mt-2 text-slate-500">Se generarán nóminas para todos los empleados con turnos aprobados en el rango.</p>
                
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Inicio</label>
                        <input v-model="generateForm.period_start" type="date" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-50 p-2.5 dark:border-slate-700 dark:bg-slate-800" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Fin</label>
                        <input v-model="generateForm.period_end" type="date" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-50 p-2.5 dark:border-slate-700 dark:bg-slate-800" />
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button @click="showGenerateModal = false" class="flex-1 rounded-xl bg-slate-100 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200 transition dark:bg-slate-800 dark:text-slate-300">Cancelar</button>
                    <button 
                        @click="submitBulkGenerate" 
                        :disabled="generateForm.processing"
                        class="flex-1 rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition active:scale-95 disabled:opacity-50"
                    >
                        {{ generateForm.processing ? 'Procesando...' : 'Iniciar' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showPayModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Confirmar Pago Masivo</h3>
                <p class="mt-2 text-slate-500 text-center">
                    ¿Estás seguro de marcar <span class="font-bold text-slate-900 dark:text-white">{{ stats.pending_count }}</span> nóminas como pagadas?
                    <br>
                    <span class="text-xl font-black text-emerald-600 mt-2 block">{{ currency.format(stats.pending_amount) }}</span>
                </p>
                
                <div class="mt-8 flex gap-3">
                    <button @click="showPayModal = false" class="flex-1 rounded-xl bg-slate-100 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200 transition dark:bg-slate-800 dark:text-slate-300">Cancelar</button>
                    <button 
                        @click="submitBulkPay" 
                        :disabled="payForm.processing"
                        class="flex-1 rounded-xl bg-emerald-600 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition active:scale-95 disabled:opacity-50"
                    >
                        {{ payForm.processing ? 'Pagando...' : 'Confirmar Pago' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
