<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import ShiftBreakdown from '@/Components/ShiftBreakdown.vue';

const props = defineProps({
    auth: Object,
    shifts: Object // Inertia will pass this if provided by controller
});

// State
const shifts = ref(props.shifts?.data || []);
const loading = ref(false);
const submitting = ref(false);
const lastCreatedShift = ref(null);
const errorMessage = ref('');
const successMessage = ref('');

// Form State
const form = reactive({
    fecha_inicio: '',
    fecha_fin: '',
    notas: '',
});

const errors = ref({});
const processingRow = ref(null);

// Functions
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const fetchShifts = async () => {
    // If we already have data from props and it's the first load, we might skip
    // but the user asked for Axios GET /api/v1/shifts
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
        
        // Success handling
        lastCreatedShift.value = response.data.data;
        successMessage.value = response.data.meta?.message || 'Turno registrado correctamente.';
        
        // Reset form
        form.fecha_inicio = '';
        form.fecha_fin = '';
        form.notas = '';
        
        // Refresh list
        fetchShifts();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            // If the backend returns a flat message instead of errors object
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
        'approve': 'aprobar',
        'reject': 'rechazar',
        'void': 'anular'
    };

    if (!confirm(`¿Estás seguro de que deseas ${actionMap[action]} este turno?`)) return;

    processingRow.value = id;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await axios.post(`/api/v1/shifts/${id}/${action}`);
        successMessage.value = response.data.message || `Turno ${actionMap[action]} exitosamente.`;
        
        // Actualizar estado local
        const index = shifts.value.findIndex(s => s.id === id);
        if (index !== -1) {
            if (response.data.data) {
                shifts.value[index] = response.data.data;
            } else {
                // Fallback por si la API no devuelve el objeto actualizado
                shifts.value[index].status = action === 'approve' ? 'approved' : (action === 'reject' ? 'rejected' : 'voided');
            }
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Error al procesar la acción.';
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
    <Head title="Gestión de Turnos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Turnos
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Feedback Messages -->
                <div v-if="errorMessage" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ errorMessage }}</p>
                </div>
                <div v-if="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ successMessage }}</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Formulario de Creación -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Nuevo Turno</h3>
                            
                            <form @submit.prevent="submitForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fecha y Hora de Inicio</label>
                                    <input 
                                        v-model="form.fecha_inicio" 
                                        type="datetime-local" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    />
                                    <p v-if="errors.fecha_inicio" class="mt-1 text-xs text-red-600">{{ errors.fecha_inicio[0] }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fecha y Hora de Fin</label>
                                    <input 
                                        v-model="form.fecha_fin" 
                                        type="datetime-local" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    />
                                    <p v-if="errors.fecha_fin" class="mt-1 text-xs text-red-600">{{ errors.fecha_fin[0] }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notas</label>
                                    <textarea 
                                        v-model="form.notas" 
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Opcional..."
                                    ></textarea>
                                    <p v-if="errors.notas" class="mt-1 text-xs text-red-600">{{ errors.notas[0] }}</p>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="submitting"
                                    class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    <span v-if="submitting">Procesando...</span>
                                    <span v-else>Registrar Turno</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Listado y Resultados -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Visualización de último cálculo -->
                        <div v-if="lastCreatedShift" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500 animate-fade-in">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Resultado del Registro</h3>
                                <button @click="lastCreatedShift = null" class="text-gray-400 hover:text-gray-600 text-sm">Cerrar</button>
                            </div>
                            <ShiftBreakdown :shift="lastCreatedShift" />
                        </div>

                        <!-- Tabla de Turnos -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Historial de Turnos</h3>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th v-if="auth.user.role === 'admin'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicio</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fin</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Horas</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pago</th>
                                                <th v-if="['admin', 'supervisor'].includes(auth.user.role)" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-if="loading">
                                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 italic">Cargando datos...</td>
                                            </tr>
                                            <tr v-else-if="shifts.length === 0">
                                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 italic">No se encontraron turnos.</td>
                                            </tr>
                                            <tr v-for="shift in shifts" :key="shift.id" class="hover:bg-gray-50 transition-colors">
                                                <td v-if="auth.user.role === 'admin'" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ shift.employee?.name || 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ formatDate(shift.fecha_inicio) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ formatDate(shift.fecha_fin) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="{
                                                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                                        'bg-yellow-100 text-yellow-800': shift.status === 'pending',
                                                        'bg-green-100 text-green-800': shift.status === 'approved',
                                                        'bg-red-100 text-red-800': shift.status === 'rejected' || shift.is_voided
                                                    }">
                                                        {{ shift.status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                    <div class="font-bold">{{ shift.total_hours }}h</div>
                                                    <div class="text-xs text-gray-400">D: {{ shift.diurnas_hours }} | N: {{ shift.nocturnas_hours }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-green-600">
                                                    {{ formatCurrency(shift.total_pago) }}
                                                </td>
                                                <td v-if="['admin', 'supervisor'].includes(auth.user.role)" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div class="flex justify-end space-x-2">
                                                        <template v-if="shift.status === 'pending'">
                                                            <button 
                                                                @click="updateShiftStatus(shift.id, 'approve')"
                                                                :disabled="processingRow === shift.id"
                                                                class="inline-flex items-center px-2 py-1 border border-green-600 text-green-600 text-xs font-medium rounded hover:bg-green-600 hover:text-white disabled:opacity-50 transition-colors"
                                                            >
                                                                Aprobar
                                                            </button>
                                                            <button 
                                                                @click="updateShiftStatus(shift.id, 'reject')"
                                                                :disabled="processingRow === shift.id"
                                                                class="inline-flex items-center px-2 py-1 border border-red-600 text-red-600 text-xs font-medium rounded hover:bg-red-600 hover:text-white disabled:opacity-50 transition-colors"
                                                            >
                                                                Rechazar
                                                            </button>
                                                        </template>
                                                        <template v-else-if="shift.status === 'approved' && !shift.is_voided">
                                                            <button 
                                                                @click="updateShiftStatus(shift.id, 'void')"
                                                                :disabled="processingRow === shift.id"
                                                                class="inline-flex items-center px-2 py-1 border border-gray-400 text-gray-500 text-xs font-medium rounded hover:bg-gray-400 hover:text-white disabled:opacity-50 transition-colors"
                                                            >
                                                                Anular
                                                            </button>
                                                        </template>
                                                        <div v-if="processingRow === shift.id" class="flex items-center">
                                                            <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                        </div>
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
        </div>
    </AuthenticatedLayout>
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
