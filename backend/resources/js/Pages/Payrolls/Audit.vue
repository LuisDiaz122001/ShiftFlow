<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    users: Array,
    actions: Object,
});

const form = useForm({
    user_id: props.filters.user_id || '',
    action: props.filters.action || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
});

const submit = () => {
    form.get(route('payrolls.audit'), {
        preserveState: true,
    });
};

const getActionBadge = (action) => {
    const colors = {
        create: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        pay: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        cancel: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        blocked_attempt: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        close_period: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return colors[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>

<template>
    <AppLayout title="Auditoría de Nómina">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Auditoría de Actividad de Nómina
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Búsqueda</label>
                            <input v-model="form.search" type="text" placeholder="ID Nómina, Empleado..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuario</label>
                            <select v-model="form.user_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Todos los usuarios</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Acción</label>
                            <select v-model="form.action" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Todas las acciones</option>
                                <option v-for="(label, key) in actions" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                            <input v-model="form.date_from" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="flex items-end gap-2">
                            <div class="flex-grow">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                                <input v-model="form.date_to" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-lg transition duration-150 ease-in-out">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Logs -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha y Hora</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsable</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acción</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entidad</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metadata</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ new Date(log.created_at).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ log.user ? log.user.name : 'Sistema' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm', getActionBadge(log.action)]">
                                            {{ actions[log.action] || log.action }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <div v-if="log.payroll">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">Nómina #{{ log.payroll_id }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">{{ log.payroll.employee?.nombre }}</div>
                                        </div>
                                        <div v-else-if="log.metadata?.cycle_id" class="text-xs">
                                            Ciclo #{{ log.metadata.cycle_id }}
                                        </div>
                                        <span v-else class="italic text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                        {{ log.ip_address }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="max-w-xs overflow-hidden text-ellipsis" :title="JSON.stringify(log.metadata)">
                                            <span v-if="log.metadata?.reason" class="text-red-500 dark:text-red-400 font-medium">
                                                {{ log.metadata.reason }}
                                            </span>
                                            <span v-else-if="log.metadata?.period">
                                                Periodo: {{ log.metadata.period }}
                                            </span>
                                            <span v-else class="text-gray-400 italic">Sin detalles</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No se encontraron registros de auditoría que coincidan con los filtros.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                        <Pagination :links="logs.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
