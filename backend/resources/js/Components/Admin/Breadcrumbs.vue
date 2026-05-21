<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
        <template v-for="(item, index) in items" :key="index">
            <span v-if="index > 0" class="text-slate-300 dark:text-slate-600">/</span>
            <Link
                v-if="item.href && index < items.length - 1"
                :href="item.href"
                class="font-medium hover:text-indigo-600 dark:hover:text-indigo-400"
            >
                {{ item.label }}
            </Link>
            <span v-else class="font-semibold text-slate-800 dark:text-slate-200">{{ item.label }}</span>
        </template>
    </nav>
</template>
