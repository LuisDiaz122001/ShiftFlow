<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { FileText, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import FlashAlerts from '@/Components/Admin/FlashAlerts.vue';
import FormPanel from '@/Components/Admin/FormPanel.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFlashMessages } from '@/Composables/useFlashMessages';

const props = defineProps({
    contracts: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    estados: { type: Array, default: () => [] },
});

const { successMessage, errorMessage, clearMessages, setFormErrors } = useFlashMessages();
const isEditing = ref(false);
const editingId = ref(null);
const deletingId = ref(null);

const filterForm = useForm({
    employee_id: props.filters.employee_id ?? '',
    estado: props.filters.estado ?? '',
});

const form = useForm({
    employee_id: '',
    salario_base: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'activo',
});

const rows = computed(() => props.contracts.data ?? []);
const isCreateMode = computed(() => !isEditing.value);

const applyFilters = () => {
    filterForm.get(route('contracts.index'), { preserveState: true, replace: true });
};

const resetForm = () => {
    form.reset();
    form.estado = 'activo';
    form.clearErrors();
    isEditing.value = false;
    editingId.value = null;
};

const startCreate = () => {
    resetForm();
    clearMessages();
};

const startEdit = (contract) => {
    clearMessages();
    isEditing.value = true;
    editingId.value = contract.id;
    form.employee_id = contract.employee_id;
    form.salario_base = contract.salario_base?.toString() ?? '';
    form.fecha_inicio = contract.fecha_inicio?.substring(0, 10) ?? '';
    form.fecha_fin = contract.fecha_fin ? contract.fecha_fin.substring(0, 10) : '';
    form.estado = contract.estado;
};

const submitForm = () => {
    clearMessages();

    if (!form.fecha_fin) {
        form.fecha_fin = null;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => resetForm(),
        onError: () => setFormErrors(form),
    };

    if (isCreateMode.value) {
        form.post(route('contracts.store'), options);
    } else {
        form.put(route('contracts.update', editingId.value), options);
    }
};

const removeItem = (contract) => {
    if (!confirm(`¿Eliminar el contrato de ${contract.employee?.nombre}?`)) return;

    deletingId.value = contract.id;
    clearMessages();

    router.delete(route('contracts.destroy', contract.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === contract.id) resetForm();
        },
        onError: () => {
            errorMessage.value = 'No fue posible eliminar el contrato.';
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(Number(value ?? 0));
</script>

<template>
    <AppLayout title="Contratos">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AdminPageHeader
                    badge="Contratos"
                    title="Gestión de contratos"
                    description="Administra salarios y vigencias contractuales por empleado."
                    badge-class="bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"
                >
                    <template #icon><FileText class="h-4 w-4" /></template>
                    <template #actions>
                        <SecondaryButton type="button" @click="applyFilters">Aplicar filtros</SecondaryButton>
                        <PrimaryButton type="button" @click="startCreate"><Plus class="mr-2 h-4 w-4" />Nuevo contrato</PrimaryButton>
                    </template>
                </AdminPageHeader>

                <div class="space-y-4 px-6 pb-2">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select v-model="filterForm.employee_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="applyFilters">
                            <option value="">Todos los empleados</option>
                            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                        </select>
                        <select v-model="filterForm.estado" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="applyFilters">
                            <option value="">Todos los estados</option>
                            <option v-for="estado in estados" :key="estado" :value="estado">{{ estado }}</option>
                        </select>
                    </div>
                    <FlashAlerts :success="successMessage" :error="errorMessage" />
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(360px,0.95fr)]">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-950/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Empleado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Vigencia</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Salario</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <tr v-if="rows.length === 0">
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Sin contratos registrados.</td>
                                    </tr>
                                    <tr v-for="contract in rows" :key="contract.id">
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ contract.employee?.nombre }}</div>
                                            <div class="text-xs text-slate-500">{{ contract.employee?.documento }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ contract.fecha_inicio }} — {{ contract.fecha_fin || 'Indefinido' }}
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium">{{ formatCurrency(contract.salario_base) }}</td>
                                        <td class="px-4 py-4 capitalize text-sm">{{ contract.estado }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="inline-flex items-center rounded-xl border px-3 py-2 text-sm" @click="startEdit(contract)">
                                                    <Pencil class="mr-2 h-4 w-4" />Editar
                                                </button>
                                                <button type="button" class="inline-flex items-center rounded-xl border border-red-200 px-3 py-2 text-sm text-red-600" :disabled="deletingId === contract.id" @click="removeItem(contract)">
                                                    <Trash2 class="mr-2 h-4 w-4" />{{ deletingId === contract.id ? '...' : 'Eliminar' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="contracts.links" />
                    </div>

                    <FormPanel
                        :title="isCreateMode ? 'Crear contrato' : 'Editar contrato'"
                        :description="isCreateMode ? 'Registra un nuevo contrato laboral.' : 'Actualiza la vigencia o el salario.'"
                        :show-cancel="isEditing"
                        @cancel="resetForm"
                        @submit="submitForm"
                    >
                        <div class="space-y-2">
                            <InputLabel for="employee_id" value="Empleado" />
                            <select id="employee_id" v-model="form.employee_id" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required>
                                <option value="" disabled>Seleccione</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                            </select>
                            <InputError :message="form.errors.employee_id" />
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="salario_base" value="Salario base" />
                            <input id="salario_base" v-model="form.salario_base" type="number" min="0" step="0.01" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                            <InputError :message="form.errors.salario_base" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <InputLabel for="fecha_inicio" value="Inicio" />
                                <input id="fecha_inicio" v-model="form.fecha_inicio" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.fecha_inicio" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="fecha_fin" value="Fin (opcional)" />
                                <input id="fecha_fin" v-model="form.fecha_fin" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.fecha_fin" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="estado" value="Estado" />
                            <select id="estado" v-model="form.estado" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                <option v-for="estado in estados" :key="estado" :value="estado">{{ estado }}</option>
                            </select>
                            <InputError :message="form.errors.estado" />
                        </div>
                        <div class="flex gap-3 pt-2">
                            <PrimaryButton type="submit" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Guardar' }}</PrimaryButton>
                            <SecondaryButton type="button" @click="resetForm">Limpiar</SecondaryButton>
                        </div>
                    </FormPanel>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
