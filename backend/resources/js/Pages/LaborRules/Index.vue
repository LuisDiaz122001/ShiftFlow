<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Scale, Trash2 } from 'lucide-vue-next';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
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
    laborRules: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { successMessage, errorMessage, clearMessages, setFormErrors } = useFlashMessages();
const isEditing = ref(false);
const editingId = ref(null);
const deletingId = ref(null);

const filterForm = useForm({
    from: props.filters.from ?? '',
});

const defaultForm = () => ({
    vigente_desde: '',
    hora_diurna_inicio: '06:00',
    hora_nocturna_inicio: '21:00',
    recargo_nocturno: '35',
    recargo_dominical: '75',
    extra_diurna: '25',
    extra_nocturna: '75',
    porcentaje_salud: '4',
    porcentaje_pension: '4',
    horas_max_diarias: '8',
});

const form = useForm(defaultForm());

const rows = computed(() => props.laborRules.data ?? []);
const isCreateMode = computed(() => !isEditing.value);

const toTimeInput = (value) => (value ? String(value).substring(0, 5) : '');

const applyFilters = () => {
    filterForm.get(route('labor-rules.index'), { preserveState: true, replace: true });
};

const resetForm = () => {
    form.defaults(defaultForm());
    form.reset();
    form.clearErrors();
    isEditing.value = false;
    editingId.value = null;
};

const startCreate = () => {
    resetForm();
    clearMessages();
};

const startEdit = (rule) => {
    clearMessages();
    isEditing.value = true;
    editingId.value = rule.id;
    form.vigente_desde = rule.vigente_desde?.substring(0, 10) ?? '';
    form.hora_diurna_inicio = toTimeInput(rule.hora_diurna_inicio);
    form.hora_nocturna_inicio = toTimeInput(rule.hora_nocturna_inicio);
    form.recargo_nocturno = rule.recargo_nocturno?.toString() ?? '';
    form.recargo_dominical = rule.recargo_dominical?.toString() ?? '';
    form.extra_diurna = rule.extra_diurna?.toString() ?? '';
    form.extra_nocturna = rule.extra_nocturna?.toString() ?? '';
    form.porcentaje_salud = rule.porcentaje_salud?.toString() ?? '';
    form.porcentaje_pension = rule.porcentaje_pension?.toString() ?? '';
    form.horas_max_diarias = rule.horas_max_diarias?.toString() ?? '';
};

const submitForm = () => {
    clearMessages();

    if (isCreateMode.value) {
        form.post(route('labor-rules.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
            onError: () => setFormErrors(form),
        });
    } else {
        form.put(route('labor-rules.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
            onError: () => setFormErrors(form),
        });
    }
};

const removeItem = (rule) => {
    if (!confirm(`¿Eliminar la regla vigente desde ${rule.vigente_desde}?`)) return;

    deletingId.value = rule.id;
    router.delete(route('labor-rules.destroy', rule.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === rule.id) resetForm();
        },
        onError: () => {
            errorMessage.value = 'No fue posible eliminar la regla.';
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Reglas laborales">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AdminPageHeader
                    badge="Configuración"
                    title="Reglas laborales"
                    description="Parámetros de recargos, extras y deducciones aplicados al cálculo de turnos."
                    badge-class="bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                >
                    <template #icon><Scale class="h-4 w-4" /></template>
                    <template #actions>
                        <input v-model="filterForm.from" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="applyFilters" />
                        <PrimaryButton type="button" @click="startCreate"><Plus class="mr-2 h-4 w-4" />Nueva regla</PrimaryButton>
                    </template>
                </AdminPageHeader>

                <div class="space-y-4 px-6 pb-2">
                    <FlashAlerts :success="successMessage" :error="errorMessage" />
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(380px,1fr)]">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-950/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Vigente desde</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Horario</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Recargos %</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <tr v-if="rows.length === 0">
                                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">Sin reglas registradas.</td>
                                    </tr>
                                    <tr v-for="rule in rows" :key="rule.id">
                                        <td class="px-4 py-4 font-medium text-slate-900 dark:text-white">{{ rule.vigente_desde }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            Diurna: {{ toTimeInput(rule.hora_diurna_inicio) }} / Nocturna: {{ toTimeInput(rule.hora_nocturna_inicio) }}
                                        </td>
                                        <td class="px-4 py-4 text-sm">Noc. {{ rule.recargo_nocturno }}% · Dom. {{ rule.recargo_dominical }}%</td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="rounded-xl border px-3 py-2 text-sm" @click="startEdit(rule)"><Pencil class="mr-1 h-4 w-4 inline" />Editar</button>
                                                <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-sm text-red-600" :disabled="deletingId === rule.id" @click="removeItem(rule)"><Trash2 class="mr-1 h-4 w-4 inline" />Eliminar</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="laborRules.links" />
                    </div>

                    <FormPanel
                        :title="isCreateMode ? 'Crear regla' : 'Editar regla'"
                        :show-cancel="isEditing"
                        @cancel="resetForm"
                        @submit="submitForm"
                    >
                        <div class="space-y-2">
                            <InputLabel for="vigente_desde" value="Vigente desde" />
                            <input id="vigente_desde" v-model="form.vigente_desde" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                            <InputError :message="form.errors.vigente_desde" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <InputLabel for="hora_diurna_inicio" value="Inicio diurno" />
                                <input id="hora_diurna_inicio" v-model="form.hora_diurna_inicio" type="time" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.hora_diurna_inicio" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="hora_nocturna_inicio" value="Inicio nocturno" />
                                <input id="hora_nocturna_inicio" v-model="form.hora_nocturna_inicio" type="time" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.hora_nocturna_inicio" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2" v-for="field in ['recargo_nocturno','recargo_dominical','extra_diurna','extra_nocturna']" :key="field">
                                <InputLabel :for="field" :value="field.replace(/_/g, ' ')" />
                                <input :id="field" v-model="form[field]" type="number" min="0" step="0.01" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors[field]" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="space-y-2">
                                <InputLabel for="porcentaje_salud" value="% Salud" />
                                <input id="porcentaje_salud" v-model="form.porcentaje_salud" type="number" min="0" max="100" step="0.01" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.porcentaje_salud" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="porcentaje_pension" value="% Pensión" />
                                <input id="porcentaje_pension" v-model="form.porcentaje_pension" type="number" min="0" max="100" step="0.01" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.porcentaje_pension" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="horas_max_diarias" value="Horas máx." />
                                <input id="horas_max_diarias" v-model="form.horas_max_diarias" type="number" min="1" max="24" step="0.5" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.horas_max_diarias" />
                            </div>
                        </div>
                        <PrimaryButton type="submit" :disabled="form.processing" class="w-full justify-center">{{ form.processing ? 'Guardando...' : 'Guardar regla' }}</PrimaryButton>
                    </FormPanel>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
