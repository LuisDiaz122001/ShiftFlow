<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Clock } from 'lucide-vue-next';

const props = defineProps({
    shifts: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
    canModerate: { type: Boolean, default: false },
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);
const isAdmin = computed(() => authUser.value?.role === 'admin');

const processingRow = ref(null);
const successMessage = ref(page.props.flash?.success ?? '');
const errorMessage = ref('');

const filterForm = useForm({
    status: props.filters.status ?? '',
});

const form = useForm({
    fecha_inicio: '',
    fecha_fin: '',
    notas: '',
    employee_id: '',
});

const shiftRows = computed(() => props.shifts.data ?? []);

const applyFilters = () => {
    filterForm.get(route('shifts.index'), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    filterForm.status = '';
    applyFilters();
};

const submitForm = () => {
    errorMessage.value = '';
    successMessage.value = '';

    form.post(route('shifts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = page.props.flash?.success || 'Turno registrado correctamente.';
            form.reset();
            form.clearErrors();
        },
        onError: () => {
            errorMessage.value = form.errors.error || 'No fue posible registrar el turno.';
        },
    });
};

const updateShiftStatus = (shiftId, action) => {
    const actionMap = {
        approve: 'aprobar',
        reject: 'rechazar',
        void: 'anular',
    };

    if (!confirm(`¿Deseas ${actionMap[action]} este turno?`)) {
        return;
    }

    processingRow.value = shiftId;
    errorMessage.value = '';
    successMessage.value = '';

    router.post(route(`shifts.${action}`, shiftId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = page.props.flash?.success || `Turno ${actionMap[action]} correctamente.`;
        },
        onError: (errors) => {
            errorMessage.value = errors.error || 'No fue posible procesar la acción.';
        },
        onFinish: () => {
            processingRow.value = null;
        },
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value ?? 0);

const formatDate = (dateString) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const statusLabel = (status) => {
    const labels = {
        pending: 'Pendiente',
        approved: 'Aprobado',
        rejected: 'Rechazado',
    };
    return labels[status] || status;
};

const statusClass = (shift) => {
    if (shift.is_voided) {
        return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    }
    const map = {
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        approved: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        rejected: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    };
    return map[shift.status] || 'bg-slate-100 text-slate-600';
};

</script>

<template>
    <AppLayout title="Gestión de Turnos">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                            <Clock class="h-4 w-4" />
                            Turnos
                        </div>
                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Gestión de turnos
                        </h1>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Registra turnos y consulta el historial usando rutas web con sesión (Inertia).
                        </p>
                    </div>

                    <div v-if="isAdmin" class="flex items-center gap-3">
                        <select
                            v-model="filterForm.status"
                            class="rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            @change="applyFilters"
                        >
                            <option value="">Todos los estados</option>
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ statusLabel(status) }}
                            </option>
                        </select>
                        <SecondaryButton type="button" @click="clearFilters">Limpiar</SecondaryButton>
                    </div>
                </div>

                <div
                    v-if="successMessage"
                    class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    {{ successMessage }}
                </div>
                <div
                    v-if="errorMessage"
                    class="mx-6 mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"
                >
                    {{ errorMessage }}
                </div>

                <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                            <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-800">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Historial de turnos</h2>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                    <thead class="bg-slate-50 dark:bg-slate-950/40">
                                        <tr>
                                            <th v-if="isAdmin" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Empleado</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Inicio</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fin</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Pago</th>
                                            <th v-if="canModerate" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                        <tr v-if="shiftRows.length === 0">
                                            <td :colspan="isAdmin ? 7 : 6" class="px-4 py-10 text-center text-sm text-slate-500">
                                                No se encontraron turnos.
                                            </td>
                                        </tr>
                                        <tr v-for="shift in shiftRows" :key="shift.id" class="align-top hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                            <td v-if="isAdmin" class="px-4 py-4 text-sm font-medium text-slate-900 dark:text-white">
                                                {{ shift.employee?.nombre || 'N/A' }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(shift.fecha_inicio) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(shift.fecha_fin) }}</td>
                                            <td class="px-4 py-4">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(shift)">
                                                    {{ shift.is_voided ? 'Anulado' : statusLabel(shift.status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-center text-sm text-slate-600 dark:text-slate-300">
                                                <div class="font-bold">{{ shift.total_hours }}h</div>
                                                <div class="text-xs text-slate-400">D: {{ shift.diurnas_hours }} | N: {{ shift.nocturnas_hours }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm font-medium text-emerald-600">
                                                {{ formatCurrency(shift.total_pago) }}
                                            </td>
                                            <td v-if="canModerate" class="px-4 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <template v-if="shift.status === 'pending'">
                                                        <button
                                                            type="button"
                                                            class="rounded-lg border border-emerald-600 px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-600 hover:text-white disabled:opacity-50"
                                                            :disabled="processingRow === shift.id"
                                                            @click="updateShiftStatus(shift.id, 'approve')"
                                                        >
                                                            Aprobar
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="rounded-lg border border-red-600 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-600 hover:text-white disabled:opacity-50"
                                                            :disabled="processingRow === shift.id"
                                                            @click="updateShiftStatus(shift.id, 'reject')"
                                                        >
                                                            Rechazar
                                                        </button>
                                                    </template>
                                                    <button
                                                        v-else-if="shift.status === 'approved' && !shift.is_voided"
                                                        type="button"
                                                        class="rounded-lg border border-slate-400 px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-500 hover:text-white disabled:opacity-50"
                                                        :disabled="processingRow === shift.id"
                                                        @click="updateShiftStatus(shift.id, 'void')"
                                                    >
                                                        Anular
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <Pagination :links="shifts.links" />
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Registrar turno</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ isAdmin ? 'Los turnos creados por admin quedan aprobados automáticamente.' : 'Tus turnos quedan en estado pendiente hasta aprobación.' }}
                        </p>

                        <form class="mt-6 space-y-4" @submit.prevent="submitForm">
                            <div v-if="isAdmin" class="space-y-2">
                                <InputLabel for="employee_id" value="Empleado" />
                                <select
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    required
                                >
                                    <option value="" disabled>Seleccione empleado</option>
                                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                        {{ employee.nombre }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.employee_id" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="fecha_inicio" value="Fecha y hora de inicio" />
                                <input
                                    id="fecha_inicio"
                                    v-model="form.fecha_inicio"
                                    type="datetime-local"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    required
                                />
                                <InputError :message="form.errors.fecha_inicio" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="fecha_fin" value="Fecha y hora de fin" />
                                <input
                                    id="fecha_fin"
                                    v-model="form.fecha_fin"
                                    type="datetime-local"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    required
                                />
                                <InputError :message="form.errors.fecha_fin" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="notas" value="Notas (opcional)" />
                                <textarea
                                    id="notas"
                                    v-model="form.notas"
                                    rows="3"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                />
                                <InputError :message="form.errors.notas" />
                            </div>

                            <InputError :message="form.errors.error" />

                            <PrimaryButton type="submit" :disabled="form.processing" class="w-full justify-center">
                                {{ form.processing ? 'Procesando...' : 'Registrar turno' }}
                            </PrimaryButton>
                        </form>

                    </aside>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
