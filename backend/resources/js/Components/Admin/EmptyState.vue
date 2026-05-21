<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-950/40">
        <div v-if="$slots.icon" class="mb-4 text-slate-400">
            <slot name="icon" />
        </div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ title }}</h3>
        <p v-if="description" class="mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
