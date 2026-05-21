<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ClipboardCheck, LogIn, LogOut } from 'lucide-vue-next';

const props = defineProps({
    attendances: { type: Array, default: () => [] },
    activeAttendance: { type: Object, default: null },
});

const page = usePage();
const successMessage = ref(page.props.flash?.success ?? '');
const errorMessage = ref('');

watch(
    () => page.props.flash?.success,
    (value) => {
        if (value) {
            successMessage.value = value;
        }
    },
);

const checkInForm = useForm({});
const checkOutForm = useForm({});

const records = computed(() => props.attendances ?? []);
const activeSession = computed(() => props.activeAttendance);

const handleCheckIn = () => {
    errorMessage.value = '';
    successMessage.value = '';

    checkInForm.post(route('attendance.checkIn'), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = page.props.flash?.success || 'Check-in registrado exitosamente.';
        },
        onError: (errors) => {
            errorMessage.value = errors.attendance || Object.values(errors)[0] || 'Error al registrar check-in.';
        },
    });
};

const handleCheckOut = () => {
    if (!activeSession.value) {
        errorMessage.value = 'No hay sesión activa para cerrar.';
        return;
    }

    errorMessage.value = '';
    successMessage.value = '';

    checkOutForm.post(route('attendance.checkOut', activeSession.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = page.props.flash?.success || 'Check-out registrado exitosamente.';
        },
        onError: (errors) => {
            errorMessage.value = errors.attendance || Object.values(errors)[0] || 'Error al registrar check-out.';
        },
    });
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const formatTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleTimeString('es-CO', {
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

const statusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        approved: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        rejected: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    };
    return classes[status] || 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
};
</script>

<template>
    <AppLayout title="Asistencia">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                    <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">
                        <ClipboardCheck class="h-4 w-4" />
                        Asistencia
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Control de asistencia
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Registra entrada y salida desde el panel web con validación del servidor.
                    </p>
                </div>

                <div class="space-y-6 p-6">
                    <div
                        v-if="successMessage"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        {{ successMessage }}
                    </div>
                    <div
                        v-if="errorMessage"
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"
                    >
                        {{ errorMessage }}
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)]">
                        <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Estado actual</h2>
                            <div
                                v-if="activeSession"
                                class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                            >
                                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Sesión activa</p>
                                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">
                                    Entrada: <span class="font-semibold">{{ formatTime(activeSession.check_in) }}</span>
                                </p>
                            </div>
                            <div
                                v-else
                                class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/40"
                            >
                                <p class="text-sm text-slate-600 dark:text-slate-300">No hay sesión activa</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <PrimaryButton
                                v-if="!activeSession"
                                type="button"
                                class="w-full justify-center"
                                :disabled="checkInForm.processing"
                                @click="handleCheckIn"
                            >
                                <LogIn class="mr-2 h-4 w-4" />
                                {{ checkInForm.processing ? 'Registrando...' : 'Check In' }}
                            </PrimaryButton>
                            <PrimaryButton
                                v-else
                                type="button"
                                class="w-full justify-center bg-red-600 hover:bg-red-700 focus:ring-red-500"
                                :disabled="checkOutForm.processing"
                                @click="handleCheckOut"
                            >
                                <LogOut class="mr-2 h-4 w-4" />
                                {{ checkOutForm.processing ? 'Registrando...' : 'Check Out' }}
                            </PrimaryButton>
                            <InputError :message="checkInForm.errors.attendance || checkOutForm.errors.attendance" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-800">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Historial</h2>
                        </div>

                        <div v-if="records.length === 0" class="px-4 py-10 text-center text-sm text-slate-500">
                            No hay registros de asistencia.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-950/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Entrada</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Salida</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                    <tr v-for="record in records" :key="record.id">
                                        <td class="px-4 py-4 text-sm text-slate-900 dark:text-white">{{ formatDate(record.check_in) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ formatTime(record.check_in) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ record.check_out ? formatTime(record.check_out) : '—' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ record.total_hours ? `${record.total_hours}h` : '—' }}
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(record.status)">
                                                {{ statusLabel(record.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
