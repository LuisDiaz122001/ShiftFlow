<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref } from 'vue';

const props = defineProps({
    cycles: Object,
});

const closingCycleId = ref(null);

const closeForm = useForm({});

const closeCycle = (cycle) => {
    if (!confirm(`¿Estás seguro de cerrar el periodo ${cycle.fecha_inicio} a ${cycle.fecha_fin}? Esta acción es irreversible y bloqueará cualquier cambio futuro.`)) {
        return;
    }

    closingCycleId.value = cycle.id;
    closeForm.post(route('payrolls.periods.close', cycle.id), {
        onFinish: () => closingCycleId.value = null,
        onSuccess: () => {
            // Success handled by flash messages in Laravel/Inertia
        }
    });
};

const getStatusBadge = (status) => {
    const colors = {
        open: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        generated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        closed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    const labels = {
        open: 'Abierto',
        generated: 'Generado',
        closed: 'Cerrado',
    };
    return { color: colors[status] || 'bg-gray-100 text-gray-800', label: labels[status] || status };
};
</script>

<template>
    <AppLayout title="Gestión de Periodos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Periodos Contables
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/20">
                        <div>
                            <h3 class="text-lg font-bold">Ciclos de Nómina</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Control de inmutabilidad y cierres financieros por periodo.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rango de Fechas</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Nóminas</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto Acumulado</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="cycle in cycles.data" :key="cycle.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-bold">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar-alt mr-2 text-gray-400"></i>
                                            {{ cycle.fecha_inicio }} <span class="mx-2 text-gray-400">→</span> {{ cycle.fecha_fin }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full shadow-sm', getStatusBadge(cycle.estado).color]">
                                            {{ getStatusBadge(cycle.estado).label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ cycle.total_payrolls }}</span> registros
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-extrabold">
                                        ${{ parseFloat(cycle.total_amount || 0).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button 
                                            v-if="cycle.estado !== 'closed'"
                                            @click="closeCycle(cycle)"
                                            :disabled="closingCycleId === cycle.id"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring focus:ring-red-300 disabled:opacity-25 transition"
                                        >
                                            <span v-if="closingCycleId === cycle.id" class="animate-spin mr-2">
                                                <i class="fas fa-circle-notch"></i>
                                            </span>
                                            <i v-else class="fas fa-lock mr-2"></i>
                                            Cerrar Periodo
                                        </button>
                                        <div v-else class="text-green-600 dark:text-green-400 font-bold flex items-center justify-end">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            CONTABILIDAD CERRADA
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="cycles.data.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No se han registrado periodos de nómina aún.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                        <Pagination :links="cycles.links" />
                    </div>
                </div>

                <!-- Card Informativa -->
                <div class="mt-8 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/10 dark:to-orange-900/10 p-6 rounded-xl border border-yellow-200 dark:border-yellow-700/50 shadow-sm">
                    <div class="flex items-start">
                        <div class="bg-yellow-400 rounded-full p-2 text-white shadow-md">
                            <i class="fas fa-shield-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-bold text-yellow-800 dark:text-yellow-400">Seguridad Contable Estricta</h4>
                            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300 leading-relaxed">
                                El cierre de un periodo de nómina es un evento final que garantiza la integridad de los datos financieros. Una vez ejecutado:
                            </p>
                            <ul class="mt-3 list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                                <li>No se podrán generar nuevas nóminas para este rango de fechas.</li>
                                <li>Las nóminas existentes se volverán <span class="font-bold underline">inmutables</span> (no se pueden editar ni cancelar).</li>
                                <li>Cualquier intento de modificación será registrado en el sistema de auditoría.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
