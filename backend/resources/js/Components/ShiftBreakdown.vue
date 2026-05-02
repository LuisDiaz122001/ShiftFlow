<script setup>
import { computed } from 'vue';

const props = defineProps({
    shift: {
        type: Object,
        required: true,
    },
});

const calculation = computed(() => props.shift.calculation ?? null);

const formatCurrency = (value) => {
    if (value === undefined || value === null) return '$0';

    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatNumber = (value) => {
    if (value === undefined || value === null) return '0.00';

    return Number(value).toFixed(2);
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-700">
            Resumen de calculo (ID: {{ shift.id || 'Nuevo' }})
        </h4>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="space-y-2">
                <p class="text-xs uppercase text-gray-500">Tiempo trabajado</p>
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

            <div class="space-y-2">
                <p class="text-xs uppercase text-gray-500">Calculo derivado</p>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total calculado:</span>
                    <span class="font-medium">{{ formatNumber(calculation?.total_hours ?? shift.total_hours) }} h</span>
                </div>
                <div
                    v-for="item in calculation?.breakdown ?? []"
                    :key="item.label"
                    class="flex justify-between text-sm"
                >
                    <span class="capitalize text-gray-600">{{ item.label }}:</span>
                    <span class="font-medium">{{ formatNumber(item.hours) }} h</span>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-xs uppercase text-gray-500">Resumen final</p>
                <div class="pt-2">
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total pago:</span>
                        <span class="text-green-600">{{ formatCurrency(calculation?.total_pay ?? shift.total_pago) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
