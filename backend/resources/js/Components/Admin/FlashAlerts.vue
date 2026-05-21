<script setup>
defineProps({
    success: { type: String, default: '' },
    error: { type: String, default: '' },
});
</script>

<template>
    <div v-if="success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
        {{ success }}
    </div>
    <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
        {{ error }}
    </div>
</template>
