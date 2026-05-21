<script setup>
defineProps({
    badge: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    badgeClass: {
        type: String,
        default: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300',
    },
});
</script>

<template>
    <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
            <div
                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]"
                :class="badgeClass"
            >
                <slot name="icon" />
                {{ badge }}
            </div>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ title }}
            </h1>
            <p v-if="description" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>
