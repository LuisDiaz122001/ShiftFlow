<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    CircleX,
    Pencil,
    Plus,
    Trash2,
    Users,
} from 'lucide-vue-next';
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
    employees: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { successMessage, errorMessage, clearMessages, setFormErrors } = useFlashMessages();
const isEditing = ref(false);
const editingEmployeeId = ref(null);
const deletingId = ref(null);

const filterForm = useForm({
    search: props.filters.search ?? '',
});

const form = useForm({
    nombre: '',
    documento: '',
    telefono: '',
    salario_base: '',
    activo: true,
    email: '',
    password: '',
});

const rows = computed(() => props.employees.data ?? []);
const isCreateMode = computed(() => !isEditing.value);
const paginationLabel = computed(() => {
    const meta = props.employees;
    if (!meta.total) return 'Sin registros';
    return `Mostrando ${meta.from}-${meta.to} de ${meta.total}`;
});

const applyFilters = () => {
    filterForm.get(route('employees.index'), { preserveState: true, replace: true });
};

const resetForm = () => {
    form.reset();
    form.activo = true;
    form.clearErrors();
    isEditing.value = false;
    editingEmployeeId.value = null;
};

const startCreate = () => {
    resetForm();
    clearMessages();
};

const startEdit = (employee) => {
    clearMessages();
    isEditing.value = true;
    editingEmployeeId.value = employee.id;
    form.nombre = employee.nombre ?? '';
    form.documento = employee.documento ?? '';
    form.telefono = employee.telefono ?? '';
    form.salario_base = employee.salario_base?.toString() ?? '';
    form.activo = Boolean(employee.activo);
    form.email = employee.user?.email ?? '';
    form.password = '';
};

const submitForm = () => {
    clearMessages();

    const options = {
        preserveScroll: true,
        onSuccess: () => resetForm(),
        onError: () => setFormErrors(form),
    };

    if (isCreateMode.value) {
        form.post(route('employees.store'), options);
        return;
    }

    const payload = {
        nombre: form.nombre,
        documento: form.documento,
        telefono: form.telefono || null,
        salario_base: form.salario_base,
        activo: form.activo,
        email: form.email,
    };

    if (form.password?.trim()) {
        payload.password = form.password;
    }

    router.put(route('employees.update', editingEmployeeId.value), payload, options);
};

const removeEmployee = (employee) => {
    if (!confirm(`¿Eliminar a ${employee.nombre} y su usuario asociado?`)) return;

    deletingId.value = employee.id;
    clearMessages();

    router.delete(route('employees.destroy', employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingEmployeeId.value === employee.id) resetForm();
        },
        onError: () => {
            errorMessage.value = 'No fue posible eliminar el empleado.';
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0));
</script>

<template>
    <AppLayout title="Empleados">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AdminPageHeader
                    badge="Administración"
                    title="Gestión de empleados"
                    description="Crea y administra usuarios laborales con validación del servidor vía Inertia."
                    badge-class="bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    <template #icon><Users class="h-4 w-4" /></template>
                    <template #actions>
                        <input
                            v-model="filterForm.search"
                            type="search"
                            placeholder="Buscar nombre, documento o email..."
                            class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            @keyup.enter="applyFilters"
                        />
                        <SecondaryButton type="button" @click="applyFilters">Buscar</SecondaryButton>
                        <PrimaryButton type="button" @click="startCreate"><Plus class="mr-2 h-4 w-4" />Nuevo empleado</PrimaryButton>
                    </template>
                </AdminPageHeader>

                <div class="space-y-4 px-6 pb-2">
                    <FlashAlerts :success="successMessage" :error="errorMessage" />
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(360px,0.95fr)]">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-800">
                            <p class="text-sm text-slate-500">{{ paginationLabel }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-950/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Empleado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Documento</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Salario</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <tr v-if="rows.length === 0">
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No hay empleados registrados.</td>
                                    </tr>
                                    <tr v-for="employee in rows" :key="employee.id">
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ employee.nombre }}</div>
                                            <div class="text-sm text-slate-500">{{ employee.user?.email }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm">{{ employee.documento }}</td>
                                        <td class="px-4 py-4 text-sm font-medium">{{ formatCurrency(employee.salario_base) }}</td>
                                        <td class="px-4 py-4">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold"
                                                :class="employee.activo ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600'"
                                            >
                                                <CheckCircle2 v-if="employee.activo" class="h-4 w-4" />
                                                <CircleX v-else class="h-4 w-4" />
                                                {{ employee.activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="rounded-xl border px-3 py-2 text-sm" @click="startEdit(employee)"><Pencil class="mr-2 h-4 w-4 inline" />Editar</button>
                                                <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-sm text-red-600" :disabled="deletingId === employee.id" @click="removeEmployee(employee)">
                                                    <Trash2 class="mr-2 h-4 w-4 inline" />{{ deletingId === employee.id ? 'Eliminando...' : 'Eliminar' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="employees.links" />
                    </div>

                    <FormPanel
                        :title="isCreateMode ? 'Crear empleado' : 'Editar empleado'"
                        :description="isCreateMode ? 'Se creará el usuario y el perfil laboral.' : 'La contraseña es opcional al editar.'"
                        :show-cancel="isEditing"
                        @cancel="resetForm"
                        @submit="submitForm"
                    >
                        <div class="space-y-2">
                            <InputLabel for="nombre" value="Nombre" />
                            <input id="nombre" v-model="form.nombre" type="text" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                            <InputError :message="form.errors.nombre" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <InputLabel for="documento" value="Documento" />
                                <input id="documento" v-model="form.documento" type="text" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.documento" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="telefono" value="Teléfono" />
                                <input id="telefono" v-model="form.telefono" type="text" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.telefono" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <InputLabel for="salario_base" value="Salario base" />
                                <input id="salario_base" v-model="form.salario_base" type="number" min="0" step="0.01" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.salario_base" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="email" value="Email" />
                                <input id="email" v-model="form.email" type="email" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="password" :value="isCreateMode ? 'Contraseña' : 'Contraseña (opcional)'" />
                            <input id="password" v-model="form.password" type="password" class="block w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                            <InputError :message="form.errors.password" />
                        </div>
                        <label class="flex items-center justify-between rounded-2xl border px-4 py-3 dark:border-slate-700">
                            <span class="text-sm font-medium">Empleado activo</span>
                            <button type="button" class="relative inline-flex h-7 w-12 items-center rounded-full" :class="form.activo ? 'bg-emerald-500' : 'bg-slate-300'" @click="form.activo = !form.activo">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white transition" :class="form.activo ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </label>
                        <InputError :message="form.errors.activo" />
                        <div class="flex flex-wrap gap-3 pt-2">
                            <PrimaryButton type="submit" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Guardar' }}</PrimaryButton>
                            <SecondaryButton type="button" @click="resetForm">Limpiar</SecondaryButton>
                            <DangerButton v-if="isEditing" type="button" @click="removeEmployee({ id: editingEmployeeId, nombre: form.nombre })">Eliminar</DangerButton>
                        </div>
                    </FormPanel>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
