<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import {
    CheckCircle2,
    CircleX,
    Pencil,
    Plus,
    RefreshCw,
    Trash2,
    Users,
} from 'lucide-vue-next';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const employees = ref([]);
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,
});
const loading = ref(false);
const submitting = ref(false);
const deletingId = ref(null);
const globalError = ref('');
const successMessage = ref('');
const isEditing = ref(false);
const editingEmployeeId = ref(null);

const form = reactive({
    nombre: '',
    documento: '',
    telefono: '',
    salario_base: '',
    activo: true,
    email: '',
    password: '',
});

const errors = reactive({
    nombre: '',
    documento: '',
    telefono: '',
    salario_base: '',
    activo: '',
    email: '',
    password: '',
});

const hasEmployees = computed(() => employees.value.length > 0);
const isCreateMode = computed(() => !isEditing.value);
const formTitle = computed(() => (isEditing.value ? 'Editar empleado' : 'Crear empleado'));
const formDescription = computed(() =>
    isEditing.value
        ? 'Actualiza los datos laborales y, si aplica, el correo o la contraseña del usuario asociado.'
        : 'Crea el usuario y el perfil del empleado usando la API existente.',
);
const paginationLabel = computed(() => {
    if (!pagination.total) {
        return 'Sin registros';
    }

    return `Mostrando ${pagination.from}-${pagination.to} de ${pagination.total}`;
});

onMounted(() => {
    loadEmployees();
});

async function loadEmployees(page = 1) {
    loading.value = true;
    clearGlobalMessages();

    try {
        const response = await axios.get(route('employees.data'), {
            params: { page },
        });

        employees.value = response.data.data ?? [];
        pagination.currentPage = response.data.meta?.current_page ?? 1;
        pagination.lastPage = response.data.meta?.last_page ?? 1;
        pagination.total = response.data.meta?.total ?? 0;
        pagination.from = response.data.meta?.from ?? 0;
        pagination.to = response.data.meta?.to ?? 0;
    } catch (error) {
        globalError.value = resolveErrorMessage(error, 'No fue posible cargar los empleados.');
    } finally {
        loading.value = false;
    }
}

function startCreate() {
    resetForm();
    clearGlobalMessages();
}

function startEdit(employee) {
    resetErrors();
    clearGlobalMessages();
    isEditing.value = true;
    editingEmployeeId.value = employee.id;
    form.nombre = employee.nombre ?? '';
    form.documento = employee.documento ?? '';
    form.telefono = employee.telefono ?? '';
    form.salario_base = employee.salario_base?.toString() ?? '';
    form.activo = Boolean(employee.activo);
    form.email = employee.email ?? employee.user?.email ?? '';
    form.password = '';
}

function cancelEdit() {
    resetForm();
    clearGlobalMessages();
}

async function submitForm() {
    submitting.value = true;
    resetErrors();
    clearGlobalMessages();

    const payload = {
        nombre: form.nombre,
        documento: form.documento,
        telefono: normalizeNullable(form.telefono),
        salario_base: form.salario_base,
        activo: form.activo,
        email: form.email,
    };

    if (isCreateMode.value || form.password.trim() !== '') {
        payload.password = form.password;
    }

    try {
        if (isCreateMode.value) {
            await axios.post(route('employees.store'), payload);
            successMessage.value = 'Empleado creado correctamente.';
            await loadEmployees(1);
        } else {
            await axios.put(route('employees.update', editingEmployeeId.value), payload);
            successMessage.value = 'Empleado actualizado correctamente.';
            await loadEmployees(pagination.currentPage);
        }

        resetForm();
    } catch (error) {
        handleFormError(error);
    } finally {
        submitting.value = false;
    }
}

async function removeEmployee(employee) {
    const confirmed = window.confirm(`¿Eliminar a ${employee.nombre} y su usuario asociado?`);

    if (!confirmed) {
        return;
    }

    deletingId.value = employee.id;
    clearGlobalMessages();

    try {
        await axios.delete(route('employees.destroy', employee.id));
        successMessage.value = 'Empleado eliminado correctamente.';

        const nextPage =
            employees.value.length === 1 && pagination.currentPage > 1
                ? pagination.currentPage - 1
                : pagination.currentPage;

        await loadEmployees(nextPage);

        if (editingEmployeeId.value === employee.id) {
            resetForm();
        }
    } catch (error) {
        globalError.value = resolveErrorMessage(error, 'No fue posible eliminar el empleado.');
    } finally {
        deletingId.value = null;
    }
}

function resetForm() {
    form.nombre = '';
    form.documento = '';
    form.telefono = '';
    form.salario_base = '';
    form.activo = true;
    form.email = '';
    form.password = '';
    isEditing.value = false;
    editingEmployeeId.value = null;
    resetErrors();
}

function resetErrors() {
    Object.keys(errors).forEach((key) => {
        errors[key] = '';
    });
}

function clearGlobalMessages() {
    globalError.value = '';
    successMessage.value = '';
}

function handleFormError(error) {
    const validationErrors = error?.response?.data?.errors;

    if (validationErrors && typeof validationErrors === 'object' && !Array.isArray(validationErrors)) {
        Object.keys(errors).forEach((field) => {
            errors[field] = Array.isArray(validationErrors[field]) ? validationErrors[field][0] : '';
        });

        if (!globalError.value && error?.response?.data?.message) {
            globalError.value = error.response.data.message;
        }

        return;
    }

    globalError.value = resolveErrorMessage(error, 'No fue posible guardar el empleado.');
}

function resolveErrorMessage(error, fallback) {
    const data = error?.response?.data;

    if (Array.isArray(data?.errors) && data.errors.length > 0) {
        return data.errors[0];
    }

    if (typeof data?.message === 'string' && data.message !== '') {
        return data.message;
    }

    return fallback;
}

function normalizeNullable(value) {
    const trimmed = typeof value === 'string' ? value.trim() : value;

    return trimmed === '' ? null : trimmed;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0));
}
</script>

<template>
    <AppLayout title="Empleados">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-6 border-b border-slate-200 px-6 py-6 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <Users class="h-4 w-4" />
                            Administración
                        </div>
                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Gestión de empleados
                        </h1>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Administra usuarios laborales desde una sola pantalla. La interfaz consume la API existente y refleja las validaciones del backend.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <SecondaryButton @click="loadEmployees(pagination.currentPage)" :disabled="loading">
                            <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': loading }" />
                            Recargar
                        </SecondaryButton>
                        <PrimaryButton @click="startCreate">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo empleado
                        </PrimaryButton>
                    </div>
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(360px,0.95fr)]">
                    <div class="space-y-4">
                        <div
                            v-if="successMessage"
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            {{ successMessage }}
                        </div>

                        <div
                            v-if="globalError"
                            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"
                        >
                            {{ globalError }}
                        </div>

                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                            <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Empleados registrados</h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ paginationLabel }}</p>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ pagination.total }} total
                                </div>
                            </div>

                            <div v-if="loading" class="space-y-3 p-4">
                                <div
                                    v-for="row in 5"
                                    :key="row"
                                    class="h-16 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800"
                                ></div>
                            </div>

                            <div v-else-if="!hasEmployees" class="px-4 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                    <Users class="h-6 w-6" />
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">No hay empleados</h3>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    Crea el primer empleado desde el formulario lateral.
                                </p>
                            </div>

                            <div v-else class="overflow-x-auto scrollbar-thin">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                    <thead class="bg-slate-50 dark:bg-slate-950/40">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Empleado</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Documento</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contacto</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Salario</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Estado</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                        <tr v-for="employee in employees" :key="employee.id" class="align-top">
                                            <td class="px-4 py-4">
                                                <div class="font-semibold text-slate-900 dark:text-white">{{ employee.nombre }}</div>
                                                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ employee.email }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ employee.documento }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                                <div>{{ employee.telefono || 'Sin teléfono' }}</div>
                                                <div class="mt-1 text-xs text-slate-400 dark:text-slate-500">User ID: {{ employee.user_id }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(employee.salario_base) }}</td>
                                            <td class="px-4 py-4">
                                                <span
                                                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold"
                                                    :class="employee.activo ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                                >
                                                    <CheckCircle2 v-if="employee.activo" class="h-4 w-4" />
                                                    <CircleX v-else class="h-4 w-4" />
                                                    {{ employee.activo ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-300"
                                                        @click="startEdit(employee)"
                                                    >
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Editar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-500/20 dark:text-red-300 dark:hover:bg-red-500/10"
                                                        :disabled="deletingId === employee.id"
                                                        @click="removeEmployee(employee)"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        {{ deletingId === employee.id ? 'Eliminando...' : 'Eliminar' }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-if="pagination.lastPage > 1"
                                class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p class="text-sm text-slate-500 dark:text-slate-400">Página {{ pagination.currentPage }} de {{ pagination.lastPage }}</p>
                                <div class="flex gap-2">
                                    <SecondaryButton
                                        :disabled="pagination.currentPage <= 1 || loading"
                                        @click="loadEmployees(pagination.currentPage - 1)"
                                    >
                                        Anterior
                                    </SecondaryButton>
                                    <SecondaryButton
                                        :disabled="pagination.currentPage >= pagination.lastPage || loading"
                                        @click="loadEmployees(pagination.currentPage + 1)"
                                    >
                                        Siguiente
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ formTitle }}</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ formDescription }}</p>
                            </div>
                            <button
                                v-if="isEditing"
                                type="button"
                                class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white"
                                @click="cancelEdit"
                            >
                                Cancelar
                            </button>
                        </div>

                        <form class="mt-6 space-y-5" @submit.prevent="submitForm">
                            <div class="space-y-2">
                                <InputLabel for="nombre" value="Nombre" />
                                <input
                                    id="nombre"
                                    v-model="form.nombre"
                                    type="text"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    autocomplete="name"
                                />
                                <InputError :message="errors.nombre" />
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <InputLabel for="documento" value="Documento" />
                                    <input
                                        id="documento"
                                        v-model="form.documento"
                                        type="text"
                                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    />
                                    <InputError :message="errors.documento" />
                                </div>

                                <div class="space-y-2">
                                    <InputLabel for="telefono" value="Teléfono" />
                                    <input
                                        id="telefono"
                                        v-model="form.telefono"
                                        type="text"
                                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        autocomplete="tel"
                                    />
                                    <InputError :message="errors.telefono" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <InputLabel for="salario_base" value="Salario base" />
                                    <input
                                        id="salario_base"
                                        v-model="form.salario_base"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    />
                                    <InputError :message="errors.salario_base" />
                                </div>

                                <div class="space-y-2">
                                    <InputLabel for="email" value="Email" />
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        autocomplete="email"
                                    />
                                    <InputError :message="errors.email" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <InputLabel for="password" :value="isCreateMode ? 'Password' : 'Password (opcional)'" />
                                    <span class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ isCreateMode ? 'Mínimo 8 caracteres' : 'Déjalo vacío para conservar la actual' }}
                                    </span>
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    autocomplete="new-password"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">Empleado activo</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Controla la disponibilidad administrativa del empleado.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="relative inline-flex h-7 w-12 items-center rounded-full transition"
                                    :class="form.activo ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"
                                    @click="form.activo = !form.activo"
                                >
                                    <span
                                        class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                                        :class="form.activo ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </label>

                            <InputError :message="errors.activo" />

                            <div class="flex flex-wrap gap-3 pt-2">
                                <PrimaryButton type="submit" :disabled="submitting">
                                    {{ submitting ? 'Guardando...' : isCreateMode ? 'Crear empleado' : 'Guardar cambios' }}
                                </PrimaryButton>
                                <SecondaryButton type="button" @click="cancelEdit">
                                    Limpiar
                                </SecondaryButton>
                                <DangerButton
                                    v-if="isEditing"
                                    type="button"
                                    @click="removeEmployee({ id: editingEmployeeId, nombre: form.nombre })"
                                >
                                    Eliminar actual
                                </DangerButton>
                            </div>
                        </form>
                    </aside>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
