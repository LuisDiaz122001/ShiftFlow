<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Asistencia</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Gestiona tu check-in y check-out
        </p>
      </div>

      <!-- Status Card -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Current Status -->
          <div class="md:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Estado Actual
            </h2>
            <div
              v-if="activeAttendance"
              class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4"
            >
              <p class="text-sm text-green-700 dark:text-green-300 font-medium mb-2">
                ✓ Sesión activa
              </p>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Entrada: <span class="font-semibold">{{ formatTime(activeAttendance.check_in) }}</span>
              </p>
            </div>
            <div
              v-else
              class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4"
            >
              <p class="text-sm text-gray-700 dark:text-gray-300">
                No hay sesión activa
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col gap-3">
            <button
              v-if="!activeAttendance"
              @click="handleCheckIn"
              :disabled="checkInForm.processing"
              class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
            >
              <span v-if="checkInForm.processing" class="animate-spin">⏳</span>
              <span v-else>✓</span>
              Check In
            </button>

            <button
              v-else
              @click="handleCheckOut"
              :disabled="checkOutForm.processing"
              class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
            >
              <span v-if="checkOutForm.processing" class="animate-spin">⏳</span>
              <span v-else>✗</span>
              Check Out
            </button>
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg flex justify-between items-center">
        <span>{{ successMessage }}</span>
        <button @click="successMessage = ''" class="text-green-700 dark:text-green-300 font-bold">×</button>
      </div>

      <div v-if="errorMessage" class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg flex justify-between items-center">
        <span>{{ errorMessage }}</span>
        <button @click="errorMessage = ''" class="text-red-700 dark:text-red-300 font-bold">×</button>
      </div>

      <!-- Attendance Records -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Historial de Asistencia
          </h2>
        </div>

        <div v-if="attendances.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
          No hay registros de asistencia
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Fecha
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Entrada
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Salida
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Total Horas
                </th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Estado
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="record in attendances"
                :key="record.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150"
              >
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  {{ formatDate(record.check_in) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  {{ formatTime(record.check_in) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  {{ record.check_out ? formatTime(record.check_out) : '—' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  {{ record.total_hours ? `${record.total_hours}h` : '—' }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      statusBadgeClass(record.status),
                    ]"
                  >
                    {{ statusLabel(record.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

// Props from backend
const attendances = computed(() => page.props.attendances || []);
const activeAttendance = computed(() => page.props.activeAttendance || null);

// Forms
const checkInForm = useForm({});
const checkOutForm = useForm({});

// Messages
const successMessage = ref('');
const errorMessage = ref('');

// Methods
const handleCheckIn = () => {
  errorMessage.value = '';
  successMessage.value = '';

  checkInForm.post(route('attendance.checkIn'), {
    onSuccess: () => {
      successMessage.value = '✓ Check-in registrado exitosamente';
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    },
    onError: (errors) => {
      errorMessage.value = errors[Object.keys(errors)[0]] || 'Error al registrar check-in';
    },
  });
};

const handleCheckOut = () => {
  if (!activeAttendance.value) {
    errorMessage.value = 'No hay sesión activa para cerrar';
    return;
  }

  errorMessage.value = '';
  successMessage.value = '';

  checkOutForm.post(route('attendance.checkOut', activeAttendance.value.id), {
    onSuccess: () => {
      successMessage.value = '✓ Check-out registrado exitosamente';
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    },
    onError: (errors) => {
      errorMessage.value = errors[Object.keys(errors)[0]] || 'Error al registrar check-out';
    },
  });
};

// Formatters
const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};

const formatTime = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleTimeString('es-ES', {
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
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
    approved: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
    rejected: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
  };
  return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
};
</script>
