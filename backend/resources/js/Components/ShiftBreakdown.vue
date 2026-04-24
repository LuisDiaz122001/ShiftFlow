<script setup>
import { computed } from 'vue';

const props = defineProps({
    shift: {
        type: Object,
        required: true
    }
});

const formatCurrency = (value) => {
    if (value === undefined || value === null) return '$0.00';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 2
    }).format(value);
};

const formatNumber = (value) => {
    if (value === undefined || value === null) return '0.00';
    return Number(value).toFixed(2);
};
</script>

<template>
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
            Resumen de Cálculo (ID: {{ shift.id || 'Nuevo' }})
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Horas -->
            <div class="space-y-2">
                <p class="text-xs text-gray-500 uppercase">Tiempo Trabajado</p>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total:</span>
                    <span class="font-medium">{{ formatNumber(shift.total_hours) }} h</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Diurnas:</span>
                    <span class="font-medium">{{ formatNumber(shift.diurnas_hours) }} h</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Nocturnas:</span>
                    <span class="font-medium text-indigo-600">{{ formatNumber(shift.nocturnas_hours) }} h</span>
                </div>
            </div>

            <!-- Pagos Base -->
            <div class="space-y-2">
                <p class="text-xs text-gray-500 uppercase">Pagos Ordinarios</p>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Valor Hora:</span>
                    <span class="font-medium">{{ formatCurrency(shift.valor_hora) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Pago Diurno:</span>
                    <span class="font-medium">{{ formatCurrency(shift.pago_diurno) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Pago Nocturno:</span>
                    <span class="font-medium text-indigo-600">{{ formatCurrency(shift.pago_nocturno) }}</span>
                </div>
            </div>

            <!-- Totales -->
            <div class="space-y-2">
                <p class="text-xs text-gray-500 uppercase">Resumen Final</p>
                <div v-if="shift.pago_extra_diurno > 0 || shift.pago_extra_nocturno > 0" class="space-y-1">
                    <div class="flex justify-between text-xs text-orange-600">
                        <span>Extras Diurnas:</span>
                        <span>{{ formatCurrency(shift.pago_extra_diurno) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-orange-600">
                        <span>Extras Nocturnas:</span>
                        <span>{{ formatCurrency(shift.pago_extra_nocturno) }}</span>
                    </div>
                </div>
                <div class="pt-2 border-t border-gray-200 mt-2">
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total Pago:</span>
                        <span class="text-green-600">{{ formatCurrency(shift.total_pago) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
