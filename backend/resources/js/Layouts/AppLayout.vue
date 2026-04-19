<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import Topbar from '@/Components/Layout/Topbar.vue';

defineProps({
    title: String,
});

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-slate-950 font-sans antialiased text-slate-900 dark:text-slate-100 transition-colors duration-300">
        <Head :title="title" />

        <!-- Sidebar (Navigation) -->
        <Sidebar 
            :is-open="isSidebarOpen" 
            @close="isSidebarOpen = false" 
        />

        <div class="flex flex-col flex-1 w-0 overflow-hidden">
            <!-- Topbar (Header) -->
            <Topbar @toggle-sidebar="toggleSidebar" />

            <!-- Main Content Area -->
            <main class="relative flex-1 overflow-y-auto focus:outline-none">
                <div class="py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <!-- Dinamic Slot -->
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style>
/* Global resets or simple dashboard animations if needed */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
