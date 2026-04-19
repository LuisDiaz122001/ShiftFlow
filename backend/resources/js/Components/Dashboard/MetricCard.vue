<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: Object,
        required: true,
    },
    // Prop explícito para formateo: 'currency', 'hours', 'number'
    formatType: {
        type: String,
        default: 'number',
    },
    // Estilo visual: 'success', 'warning', 'info', 'danger', 'default'
    variant: {
        type: String,
        default: 'default',
    },
});

const formattedValue = computed(() => {
    const val = props.value ?? 0;
    
    if (props.formatType === 'currency') {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
        }).format(val);
    }
    
    if (props.formatType === 'hours') {
        return `${val}h`;
    }
    
    return val;
});

const variantClasses = computed(() => {
    const map = {
        default: 'text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-400',
        success: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 dark:text-emerald-400',
        warning: 'text-amber-600 bg-amber-50 dark:bg-amber-900/20 dark:text-amber-400',
        info: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400',
        danger: 'text-rose-600 bg-rose-50 dark:bg-rose-900/20 dark:text-rose-400',
    };
    return map[props.variant] || map.default;
});
</script>

<template>
    <div class="relative overflow-hidden transition-all duration-300 bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg dark:bg-slate-900 dark:border-slate-800/50 group">
        <div class="flex items-center justify-between mb-4">
            <div :class="['flex items-center justify-center w-12 h-12 rounded-xl transition-transform duration-300 group-hover:scale-110', variantClasses]">
                <component :is="icon" class="w-6 h-6" />
            </div>
            <span class="text-xs font-semibold tracking-wider uppercase text-slate-400 dark:text-slate-500">
                {{ title }}
            </span>
        </div>
        
        <div class="flex flex-col">
            <span v-if="value !== 0" class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ formattedValue }}
            </span>
            <span v-else class="text-lg font-medium text-slate-400 italic">
                No hay datos aún
            </span>
        </div>

        <!-- Decorator background -->
        <div class="absolute -right-4 -bottom-4 w-24 h-24 opacity-[0.03] dark:opacity-[0.05] pointer-events-none">
            <component :is="icon" class="w-full h-full" />
        </div>
    </div>
</template>
