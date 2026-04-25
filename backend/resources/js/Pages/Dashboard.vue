<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Users } from 'lucide-vue-next';
import MetricCard from '@/Components/Dashboard/MetricCard.vue';
import { computed } from 'vue';
import { 
    Clock, 
    CheckCircle, 
    Wallet, 
    AlertCircle,
    BarChart3
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    role: {
        type: String,
        required: true,
    },
    employeesCount: {
        type: Number,
        default: 0,
    }
});

// Configuración dinámica de tarjetas por rol (Escalable y Limpio)
const cards = computed(() => {
    // Definimos las tarjetas base para Admin/Supervisor
    if (['admin', 'supervisor'].includes(props.role)) {
        return [
            { id: 'ec', title: 'Empleados Registrados', value: props.employeesCount, icon: Users, variant: 'info', formatType: 'number' },
            { id: 'pc', title: 'Turnos por Aprobar', value: props.stats.pending_shifts, icon: AlertCircle, variant: 'warning', formatType: 'number' },
            { id: 'ah', title: 'Total Horas Aprobadas', value: props.stats.approved_hours, icon: BarChart3, variant: 'info', formatType: 'hours' },
            { id: 'ep', title: 'Nómina Estimada', value: props.stats.estimated_pay, icon: Wallet, variant: 'success', formatType: 'currency' },
        ];
    }

    // Tarjetas para Empleado
    return [
        { id: 'ah', title: 'Horas Aprobadas', value: props.stats.approved_hours, icon: CheckCircle, variant: 'success', formatType: 'hours' },
        { id: 'pc', title: 'Turnos Pendientes', value: props.stats.pending_shifts, icon: Clock, variant: 'warning', formatType: 'number' },
        { id: 'ep', title: 'Pago Estimado', value: props.stats.estimated_pay, icon: Wallet, variant: 'info', formatType: 'currency' },
    ];
});
</script>

<template>
    <AppLayout title="Dashboard">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Panel de Control
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Resumen de actividad para perfil: <span class="capitalize font-semibold text-indigo-600 dark:text-indigo-400">{{ role }}</span>
            </p>
        </div>

        <!-- Grid de Métricas (Renderizado dinámico) -->
        <div class="grid gap-6 mb-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard 
                v-for="card in cards" 
                :key="card.id"
                v-bind="card"
            />
        </div>

        <!-- Sección de Información Adicional -->
        <div class="p-6 bg-white border border-slate-200 rounded-2xl dark:bg-slate-900 dark:border-slate-800/50">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Actividad Reciente</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Próximamente: Lista detallada de los últimos turnos procesados y notificaciones del sistema.
            </p>
        </div>
    </AppLayout>
</template>
