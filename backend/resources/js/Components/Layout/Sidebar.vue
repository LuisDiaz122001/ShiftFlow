<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Clock,
    History,
    LayoutDashboard,
    ShieldCheck,
    Users,
    Wallet,
    X,
} from 'lucide-vue-next';
import NavItem from '@/Components/Layout/NavItem.vue';

defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
});

defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role);

// Agrupación de navegación con reactividad mejorada
const navigationGroups = computed(() => {
    // Usamos usePage().url para asegurar reactividad en el estado activo
    const currentUrl = page.url;

    const common = [
        { 
            label: 'Dashboard', 
            href: route('dashboard'), 
            icon: LayoutDashboard, 
            active: route().current('dashboard') 
        },
    ];

    const employee = [
        { 
            label: 'Mis Turnos', 
            href: route('shifts.index'), 
            icon: Clock, 
            active: route().current('shifts.*') 
        },
        { 
            label: 'Mi Nómina', 
            href: route('payrolls.index'), 
            icon: Wallet, 
            active: route().current('payrolls.*') && role.value === 'employee' 
        },
    ];

    const admin = [
        { 
            label: 'Empleados', 
            href: route('employees.index'), 
            icon: Users, 
            active: route().current('employees.*') 
        },
        { 
            label: 'Nómina Global', 
            href: route('payrolls.index'), 
            icon: ShieldCheck, 
            active: route().current('payrolls.*') && ['admin', 'supervisor'].includes(role.value)
        },
    ];

    return {
        common,
        employee: role.value === 'employee' ? employee : [],
        admin: ['admin', 'supervisor'].includes(role.value) ? admin : [],
    };
});
</script>

<template>
    <!-- Overlay para móviles -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
            @click="$emit('close')"
        ></div>
    </Transition>

    <!-- Contenedor del Sidebar -->
    <aside
        class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 transform border-r border-slate-200 bg-white transition-transform duration-300 ease-in-out dark:border-slate-800 dark:bg-slate-900 lg:static lg:inset-0 lg:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Logo Area -->
        <div class="flex h-16 items-center justify-between border-b border-slate-100 px-6 dark:border-slate-800/50">
            <Link :href="route('dashboard')" class="flex items-center gap-2 group">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white transition-transform group-hover:scale-110">SF</div>
                <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">ShiftFlow</span>
            </Link>
            <button
                @click="$emit('close')"
                class="rounded-md p-1 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 space-y-8 overflow-y-auto px-4 py-6 scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-800">
            <!-- Common Routes -->
            <div>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.common" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active" @click="$emit('close')">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>

            <!-- Employee Section -->
            <div v-if="navigationGroups.employee.length > 0">
                <h3 class="mb-2 px-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Panel Empleado
                </h3>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.employee" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active" @click="$emit('close')">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>

            <!-- Administration Section -->
            <div v-if="navigationGroups.admin.length > 0">
                <h3 class="mb-2 px-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Administración
                </h3>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.admin" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active" @click="$emit('close')">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- User Footer Area -->
        <div class="mt-auto border-t border-slate-100 p-4 dark:border-slate-800/50">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/40 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                    {{ user?.name.substring(0, 1).toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ user?.name }}</p>
                    <p class="truncate text-xs capitalize text-slate-500 font-medium">{{ user?.role }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
/* Estilización suave de la barra de desplazamiento */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    border-radius: 10px;
}
</style>
