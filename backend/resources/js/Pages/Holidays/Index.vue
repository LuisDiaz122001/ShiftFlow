<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CalendarDays, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
    holidays: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { successMessage, errorMessage, clearMessages, setFormErrors } = useFlashMessages();
const isEditing = ref(false);
const editingId = ref(null);
const deletingId = ref(null);

const filterForm = useForm({
    year: props.filters.year ?? '',
    search: props.filters.search ?? '',
});

const form = useForm({
    fecha: '',
    nombre: '',
});

const rows = computed(() => props.holidays.data ?? []);
const isCreateMode = computed(() => !isEditing.value);

const applyFilters = () => {
    filterForm.get(route('holidays.index'), { preserveState: true, replace: true });
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    isEditing.value = false;
    editingId.value = null;
};

const startCreate = () => {
    resetForm();
    clearMessages();
};

const startEdit = (holiday) => {
    clearMessages();
    isEditing.value = true;
    editingId.value = holiday.id;
    form.fecha = holiday.fecha?.substring(0, 10) ?? '';
    form.nombre = holiday.nombre ?? '';
};

const submitForm = () => {
    clearMessages();

    if (isCreateMode.value) {
        form.post(route('holidays.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
            onError: () => setFormErrors(form),
        });
    } else {
        form.put(route('holidays.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
            onError: () => setFormErrors(form),
        });
    }
};

const removeItem = (holiday) => {
    if (!confirm(`¿Eliminar el festivo "${holiday.nombre}"?`)) return;

    deletingId.value = holiday.id;
    router.delete(route('holidays.destroy', holiday.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === holiday.id) resetForm();
        },
        onError: () => {
            errorMessage.value = 'No fue posible eliminar el festivo.';
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Festivos">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AdminPageHeader
                    badge="Calendario"
                    title="Días festivos"
                    description="Fechas que impactan el cálculo de recargos dominicales y festivos."
                    badge-class="bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    <template #icon><CalendarDays class="h-4 w-4" /></template>
                    <template #actions>
                        <input v-model="filterForm.search" type="search" placeholder="Buscar nombre..." class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @keyup.enter="applyFilters" />
                        <input v-model="filterForm.year" type="number" min="2000" max="2100" placeholder="Año" class="w-24 rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="applyFilters" />
                        <SecondaryButton type="button" @click="applyFilters">Filtrar</SecondaryButton>
                        <PrimaryButton type="button" @click="startCreate"><Plus class="mr-2 h-4 w-4" />Nuevo festivo</PrimaryButton>
                    </template>
                </AdminPageHeader>

                <div class="space-y-4 px-6 pb-2">
                    <FlashAlerts :success="successMessage" :error="errorMessage" />
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-950/40">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nombre</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr v-if="rows.length === 0">
                                    <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">Sin festivos registrados.</td>
                                </tr>
                                <tr v-for="holiday in rows" :key="holiday.id">
                                    <td class="px-4 py-4 text-sm font-medium text-slate-900 dark:text-white">{{ holiday.fecha }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ holiday.nombre }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="rounded-xl border px-3 py-2 text-sm" @click="startEdit(holiday)"><Pencil class="mr-1 h-4 w-4 inline" />Editar</button>
                                            <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-sm text-red-600" :disabled="deletingId === holiday.id" @click="removeItem(holiday)"><Trash2 class="mr-1 h-4 w-4 inline" />Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :links="holidays.links" />
                    </div>

                    <FormPanel
                        :title="isCreateMode ? 'Crear festivo' : 'Editar festivo'"
                        :show-cancel="isEditing"
                        @cancel="resetForm"
                        @submit="submitForm"
                    >
                        <div class="space-y-2">
                            <InputLabel for="fecha" value="Fecha" />
                            <input id="fecha" v-model="form.fecha" type="date" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required />
                            <InputError :message="form.errors.fecha" />
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="nombre" value="Nombre" />
                            <input id="nombre" v-model="form.nombre" type="text" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required />
                            <InputError :message="form.errors.nombre" />
                        </div>
                        <PrimaryButton type="submit" :disabled="form.processing" class="w-full justify-center">{{ form.processing ? 'Guardando...' : 'Guardar' }}</PrimaryButton>
                    </FormPanel>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
