<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import ShiftBreakdown from '@/Components/ShiftBreakdown.vue';

const props = defineProps({
    auth: Object,
    shifts: Object,
});

const shifts = ref(props.shifts?.data || []);
const loading = ref(false);
const submitting = ref(false);
const lastCreatedShift = ref(null);
const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
    fecha_inicio: '',
    fecha_fin: '',
    notas: '',
});

const errors = ref({});
const processingRow = ref(null);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const fetchShifts = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/shifts');
        shifts.value = response.data.data || response.data;
    } catch (error) {
        errorMessage.value = 'Error al cargar los turnos.';
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const submitForm = async () => {
    submitting.value = true;
    errors.value = {};
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await axios.post('/api/v1/shifts', form);
        lastCreatedShift.value = response.data.data;
        successMessage.value = response.data.meta?.message || 'Turno registrado correctamente.';
        form.fecha_inicio = '';
        form.fecha_fin = '';
        form.notas = '';
        fetchShifts();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            if (error.response.data.message && Object.keys(errors.value).length === 0) {
                errorMessage.value = error.response.data.message;
            }
        } else {
            errorMessage.value = 'Error al procesar la solicitud.';
        }
        console.error(error);
    } finally {
        submitting.value = false;
    }
};

const updateShiftStatus = async (id, action) => {
    const actionMap = {
        approve: 'aprobar',
        reject: 'rechazar',
        void: 'anular',
    };

    if (!confirm(`Estas seguro de que deseas ${actionMap[action]} este turno?`)) return;

    processingRow.value = id;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await axios.post(`/api/v1/shifts/${id}/${action}`);
        successMessage.value = response.data.meta?.message || `Turno ${actionMap[action]} exitosamente.`;

        const index = shifts.value.findIndex((shift) => shift.id === id);
        if (index !== -1 && response.data.data) {
            shifts.value[index] = response.data.data;
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.[0] || error.response?.data?.message || 'Error al procesar la accion.';
        console.error(error);
    } finally {
        processingRow.value = null;
    }
};

onMounted(() => {
    fetchShifts();
});
</script>

<template>
    <Head title="Gestion de Turnos" />

    <AppLayout title="Gestion de Turnos">
        <div class="space-y-6">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Gestion de turnos</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Registra, consulta y audita turnos desde la interfaz operativa.
                </p>
            </div>

            <div v-if="errorMessage" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                {{ errorMessage }}
            </div>
            <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ successMessage }}
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="mb-4 text-lg font-medium text-slate-900 dark:text-white">Registrar nuevo turno</h3>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha y hora de inicio</label>
                                <input
                                    v-model="form.fecha_inicio"
                                    type="datetime-local"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                    required
                                />
                                <p v-if="errors.fecha_inicio" class="mt-1 text-xs text-red-600">{{ errors.fecha_inicio[0] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha y hora de fin</label>
                                <input
                                    v-model="form.fecha_fin"
                                    type="datetime-local"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                    required
                                />
                                <p v-if="errors.fecha_fin" class="mt-1 text-xs text-red-600">{{ errors.fecha_fin[0] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Notas</label>
                                <textarea
                                    v-model="form.notas"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                    placeholder="Opcional"
                                ></textarea>
                                <p v-if="errors.notas" class="mt-1 text-xs text-red-600">{{ errors.notas[0] }}</p>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                            >
                                <span v-if="submitting">Procesando...</span>
                                <span v-else>Registrar turno</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div v-if="lastCreatedShift" class="rounded-2xl border border-indigo-200 bg-white p-6 shadow-sm dark:border-indigo-500/20 dark:bg-slate-900">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Resultado del registro</h3>
                            <button @click="lastCreatedShift = null" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Cerrar</button>
                        </div>
                        <ShiftBreakdown :shift="lastCreatedShift" />
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-slate-900 dark:text-white">Historial de turnos</h3>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                    <thead class="bg-slate-50 dark:bg-slate-950/40">
                                        <tr>
                                            <th v-if="auth.user.role === 'admin'" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Empleado</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Inicio</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Fin</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Estado</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500">Horas</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Total pago</th>
                                            <th v-if="['admin', 'supervisor'].includes(auth.user.role)" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                        <tr v-if="loading">
                                            <td colspan="7" class="px-6 py-4 text-center text-sm italic text-slate-500">Cargando datos...</td>
                                        </tr>
                                        <tr v-else-if="shifts.length === 0">
                                            <td colspan="7" class="px-6 py-4 text-center text-sm italic text-slate-500">No se encontraron turnos.</td>
                                        </tr>
                                        <tr v-for="shift in shifts" :key="shift.id" class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td v-if="auth.user.role === 'admin'" class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">
                                                {{ shift.employee?.name || 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500">{{ formatDate(shift.fecha_inicio) }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-500">{{ formatDate(shift.fecha_fin) }}</td>
                                            <td class="px-6 py-4">
                                                <span
                                                    :class="{
                                                        'inline-flex rounded-full px-2 text-xs font-semibold leading-5': true,
                                                        'bg-yellow-100 text-yellow-800': shift.status === 'pending',
                                                        'bg-green-100 text-green-800': shift.status === 'approved',
                                                        'bg-red-100 text-red-800': shift.status === 'rejected' || shift.is_voided,
                                                    }"
                                                >
                                                    {{ shift.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm text-slate-500">
                                                <div class="font-bold">{{ shift.total_hours }}h</div>
                                                <div class="text-xs text-slate-400">D: {{ shift.diurnas_hours }} | N: {{ shift.nocturnas_hours }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium text-green-600">
                                                {{ formatCurrency(shift.total_pago) }}
                                            </td>
                                            <td v-if="['admin', 'supervisor'].includes(auth.user.role)" class="px-6 py-4 text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <template v-if="shift.status === 'pending'">
                                                        <button
                                                            @click="updateShiftStatus(shift.id, 'approve')"
                                                            :disabled="processingRow === shift.id"
                                                            class="inline-flex items-center rounded border border-green-600 px-2 py-1 text-xs font-medium text-green-600 transition-colors hover:bg-green-600 hover:text-white disabled:opacity-50"
                                                        >
                                                            Aprobar
                                                        </button>
                                                        <button
                                                            @click="updateShiftStatus(shift.id, 'reject')"
                                                            :disabled="processingRow === shift.id"
                                                            class="inline-flex items-center rounded border border-red-600 px-2 py-1 text-xs font-medium text-red-600 transition-colors hover:bg-red-600 hover:text-white disabled:opacity-50"
                                                        >
                                                            Rechazar
                                                        </button>
                                                    </template>
                                                    <template v-else-if="shift.status === 'approved' && !shift.is_voided">
                                                        <button
                                                            @click="updateShiftStatus(shift.id, 'void')"
                                                            :disabled="processingRow === shift.id"
                                                            class="inline-flex items-center rounded border border-slate-400 px-2 py-1 text-xs font-medium text-slate-500 transition-colors hover:bg-slate-400 hover:text-white disabled:opacity-50"
                                                        >
                                                            Anular
                                                        </button>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
