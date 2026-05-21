<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    showCancel: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel']);
</script>

<template>
    <aside class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-950/40">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ description }}</p>
            </div>
            <button
                v-if="showCancel"
                type="button"
                class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white"
                @click="$emit('cancel')"
            >
                Cancelar
            </button>
        </div>
        <form class="mt-6 space-y-4" @submit.prevent="$emit('submit')">
            <slot />
        </form>
    </aside>
</template>
