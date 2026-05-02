<script setup>
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});

const pageLinks = computed(() => props.links || []);

const currentPage = computed(() => {
    const active = pageLinks.value.find((link) => link.active);
    if (active) {
        return active.label.replace(/<[^>]*>/g, '');
    }

    const firstNumber = pageLinks.value.find((link) => link.url && !Number.isNaN(Number(link.label.replace(/<[^>]*>/g, ''))));
    return firstNumber ? firstNumber.label.replace(/<[^>]*>/g, '') : 1;
});

const lastPage = computed(() => {
    const pageNumbers = pageLinks.value
        .map((link) => link.label.replace(/<[^>]*>/g, ''))
        .filter((label) => !Number.isNaN(Number(label)))
        .map(Number);
    return pageNumbers.length ? Math.max(...pageNumbers) : 1;
});
</script>

<template>
  <div v-if="pageLinks.length" class="px-4 py-3 sm:px-6">
    <nav class="flex items-center justify-between" aria-label="Pagination">
      <div class="hidden sm:block">
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Página
          <span class="font-medium">{{ currentPage }}</span>
          de
          <span class="font-medium">{{ lastPage }}</span>
        </p>
      </div>

      <div class="flex-1 flex justify-between sm:justify-end">
        <div class="relative z-0 inline-flex rounded-md shadow-sm">
          <template v-for="(link, index) in pageLinks" :key="index">
            <span
              v-if="!link.url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 cursor-default"
              v-html="link.label"
            />
            <a
              v-else
              :href="link.url"
              class="relative inline-flex items-center px-4 py-2 border text-sm font-medium focus:z-20 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
              :class="link.active ? 'z-10 bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
              :aria-current="link.active ? 'page' : undefined"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </nav>
  </div>
</template>
