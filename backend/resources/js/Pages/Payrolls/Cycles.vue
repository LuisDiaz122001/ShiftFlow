<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CalendarRange, Lock, Play, Plus, ShieldAlert } from 'lucide-vue-next';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import Breadcrumbs from '@/Components/Admin/Breadcrumbs.vue';
import FlashAlerts from '@/Components/Admin/FlashAlerts.vue';
import FormPanel from '@/Components/Admin/FormPanel.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFlashMessages } from '@/Composables/useFlashMessages';

const props = defineProps({
    cycles: { type: Object, required: true },
});

const { successMessage, errorMessage, setFormErrors } = useFlashMessages();
const closingCycleId = ref(null);
const processingCycleId = ref(null);

const createForm = useForm({
    fecha_inicio: '',
    fecha_fin: '',
    fecha_pago: '',
});

const closeForm = useForm({});
const processForm = useForm({ force: false });

const submitCreate = () => {
    createForm.post(route('payrolls.periods.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
        onError: () => setFormErrors(createForm),
    });
};

const processCycle = (cycle) => {
    const isRegenerate = cycle.estado === 'generated';
    const message = isRegenerate
        ? `El periodo ${cycle.fecha_inicio} → ${cycle.fecha_fin} ya fue procesado. ¿Regenerar nóminas?`
        : `¿Procesar el periodo ${cycle.fecha_inicio} → ${cycle.fecha_fin} y generar nóminas para todos los empleados con turnos aprobados?`;

    if (!confirm(message)) {
        return;
    }

    processingCycleId.value = cycle.id;
    processForm.force = isRegenerate;

    processForm.post(route('payrolls.periods.process', cycle.id), {
        preserveScroll: true,
        onFinish: () => {
            processingCycleId.value = null;
            processForm.force = false;
        },
        onError: (errors) => {
            errorMessage.value = errors.error || 'No fue posible procesar el periodo.';
        },
    });
};

const closeCycle = (cycle) => {
    if (!confirm(`¿Cerrar el periodo ${cycle.fecha_inicio} a ${cycle.fecha_fin}? Esta acción es irreversible.`)) {
        return;
    }

    closingCycleId.value = cycle.id;
    closeForm.post(route('payrolls.periods.close', cycle.id), {
        preserveScroll: true,
        onFinish: () => {
            closingCycleId.value = null;
        },
        onError: (errors) => {
            errorMessage.value = errors.error || 'No fue posible cerrar el periodo.';
        },
    });
};

const getStatusBadge = (status) => {
    const colors = {
        open: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        generated: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300',
        closed: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    };
    const labels = { open: 'Abierto', generated: 'Generado', closed: 'Cerrado' };
    return { color: colors[status] || colors.closed, label: labels[status] || status };
};

const canProcess = (cycle) => ['open', 'generated'].includes(cycle.estado);
const canClose = (cycle) => cycle.estado !== 'closed';

const formatMoney = (value) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value ?? 0);
</script>

<template>
    <AppLayout title="Periodos de nómina">
        <div class="space-y-6">
            <Breadcrumbs
                :items="[
                    { label: 'Operación', href: route('payrolls.dashboard') },
                    { label: 'Periodos' },
                ]"
            />

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AdminPageHeader
                    badge="Nómina"
                    title="Periodos contables"
                    description="Crea, procesa y cierra periodos. El procesamiento genera nóminas desde turnos aprobados."
                    badge-class="bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
                >
                    <template #icon><CalendarRange class="h-4 w-4" /></template>
                </AdminPageHeader>

                <div class="space-y-4 px-6 pb-2">
                    <FlashAlerts :success="successMessage" :error="errorMessage" />
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(340px,0.95fr)]">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-950/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Rango</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Pago</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nóminas</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <tr v-if="!cycles.data?.length">
                                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                                            No hay periodos. Crea uno o ejecuta datos demo.
                                        </td>
                                    </tr>
                                    <tr v-for="cycle in cycles.data" :key="cycle.id">
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900 dark:text-white">
                                            {{ cycle.fecha_inicio }} → {{ cycle.fecha_fin }}
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ cycle.fecha_pago }}</td>
                                        <td class="px-4 py-4">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="getStatusBadge(cycle.estado).color">
                                                {{ getStatusBadge(cycle.estado).label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-sm">
                                            {{ cycle.total_payrolls }} · {{ formatMoney(cycle.total_amount) }}
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <SecondaryButton
                                                    v-if="canProcess(cycle)"
                                                    type="button"
                                                    :disabled="processingCycleId === cycle.id || closeForm.processing"
                                                    @click="processCycle(cycle)"
                                                >
                                                    <Play class="mr-2 h-4 w-4" />
                                                    {{ processingCycleId === cycle.id ? 'Procesando...' : cycle.estado === 'generated' ? 'Regenerar' : 'Procesar' }}
                                                </SecondaryButton>
                                                <button
                                                    v-if="canClose(cycle)"
                                                    type="button"
                                                    class="inline-flex items-center rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                                                    :disabled="closingCycleId === cycle.id || processingCycleId === cycle.id"
                                                    @click="closeCycle(cycle)"
                                                >
                                                    <Lock class="mr-2 h-4 w-4" />
                                                    {{ closingCycleId === cycle.id ? 'Cerrando...' : 'Cerrar' }}
                                                </button>
                                                <span v-else class="text-xs font-semibold text-emerald-600">Cerrado</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="cycles.links" />
                    </div>

                    <FormPanel title="Crear periodo" description="Validación anti-duplicados y solapamiento." @submit="submitCreate">
                        <div class="space-y-2">
                            <InputLabel for="fecha_inicio" value="Fecha inicio" />
                            <input id="fecha_inicio" v-model="createForm.fecha_inicio" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required />
                            <InputError :message="createForm.errors.fecha_inicio" />
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="fecha_fin" value="Fecha fin" />
                            <input id="fecha_fin" v-model="createForm.fecha_fin" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required />
                            <InputError :message="createForm.errors.fecha_fin" />
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="fecha_pago" value="Fecha de pago" />
                            <input id="fecha_pago" v-model="createForm.fecha_pago" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required />
                            <InputError :message="createForm.errors.fecha_pago" />
                        </div>
                        <PrimaryButton type="submit" :disabled="createForm.processing" class="w-full justify-center">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creando...' : 'Crear periodo' }}
                        </PrimaryButton>
                    </FormPanel>
                </div>
            </section>

            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-500/20 dark:bg-amber-500/10">
                <div class="flex gap-4">
                    <ShieldAlert class="h-8 w-8 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div class="text-sm text-amber-800 dark:text-amber-300">
                        <p class="font-semibold">Flujo recomendado</p>
                        <ol class="mt-2 list-decimal space-y-1 pl-5">
                            <li>Crear periodo (abierto).</li>
                            <li>Registrar y aprobar turnos en el rango.</li>
                            <li><strong>Procesar</strong> para generar nóminas.</li>
                            <li>Marcar nóminas como pagadas y cerrar el periodo.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
