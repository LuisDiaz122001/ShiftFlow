<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: Object,
        required: true,
    },
});

const classes = computed(() => {
    return props.active
        ? 'flex items-center px-4 py-3 text-sm font-medium transition-colors rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 group'
        : 'flex items-center px-4 py-3 text-sm font-medium transition-colors rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-slate-200 group';
});

const iconClasses = computed(() => {
    return props.active
        ? 'w-5 h-5 mr-3 transition-colors text-indigo-600 dark:text-indigo-400'
        : 'w-5 h-5 mr-3 transition-colors text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300';
});
</script>

<template>
    <Link :href="href" :class="classes">
        <component :is="icon" :class="iconClasses" />
        <slot />
    </Link>
</template>
