<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Wallet, 
    Plus, 
    Eye, 
    Calendar,
    Clock,
    CheckCircle2,
    Clock4
} from 'lucide-vue-next';

const props = defineProps({
    payrolls: Object,
    employees: Array,
});

const isCreateModalOpen = ref(false);

const form = useForm({
    employee_id: '',
    fecha_inicio: '',
    fecha_fin: '',
});

const submit = () => {
    form.post(route('payrolls.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
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

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <AppLayout title="Nómina Global">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <Wallet class="h-4 w-4" />
                        Administración
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Nómina Global
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Genera y gestiona las liquidaciones de tus empleados desde un solo lugar.
                    </p>
                </div>

                <div>
                    <button 
                        @click="isCreateModalOpen = true"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 font-bold"
                    >
                        <Plus class="w-5 h-5" />
                        Generar Nómina
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Empleado</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Periodo</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-center">Horas</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Total Pago</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-center">Estado</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="payroll in payrolls.data" :key="payroll.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
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
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                        <Calendar class="w-4 h-4 text-slate-400" />
                                        <span>{{ payroll.fecha_inicio }} - {{ payroll.fecha_fin }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-medium">
                                        <Clock class="w-3.5 h-3.5" />
                                        {{ payroll.total_hours }}h
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(payroll.total_pago) }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold capitalize"
                                        :class="getStatusBadge(payroll.estado)"
                                    >
                                        <CheckCircle2 v-if="payroll.estado === 'paid'" class="w-3.5 h-3.5" />
                                        <Clock4 v-else class="w-3.5 h-3.5" />
                                        {{ payroll.estado === 'paid' ? 'Pagado' : (payroll.estado === 'locked' ? 'Cerrado' : 'Pendiente') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link 
                                        :href="route('payrolls.show', payroll.id)"
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                                    >
                                        <Eye class="w-5 h-5" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="payrolls.data.length === 0">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <Wallet class="w-12 h-12 text-slate-200 dark:text-slate-700 mb-4" />
                                        <p class="text-slate-500 dark:text-slate-400 font-medium">No se han generado nóminas todavía.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click="isCreateModalOpen = false" class="absolute inset-0"></div>
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl p-8 animate-slide-up border border-slate-200 dark:border-slate-800">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-2 text-slate-900 dark:text-white">
                        <Plus class="w-6 h-6 text-indigo-600" />
                        Generar Nueva Nómina
                    </h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Seleccionar Empleado</label>
                            <select 
                                v-model="form.employee_id"
                                class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all outline-none"
                                required
                            >
                                <option value="">Seleccione un empleado...</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.user.name }} ({{ emp.documento }})
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Fecha Inicio</label>
                                <input 
                                    type="date" 
                                    v-model="form.fecha_inicio"
                                    class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all outline-none"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Fecha Fin</label>
                                <input 
                                    type="date" 
                                    v-model="form.fecha_fin"
                                    class="w-full px-4 py-3 rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all outline-none"
                                    required
                                />
                            </div>
                        </div>

                        <div v-if="form.errors.error" class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl text-red-600 dark:text-red-400 text-sm">
                            {{ form.errors.error }}
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button 
                                type="button"
                                @click="isCreateModalOpen = false"
                                class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all disabled:opacity-50 shadow-lg shadow-indigo-500/20"
                            >
                                {{ form.processing ? 'Procesando...' : 'Generar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
