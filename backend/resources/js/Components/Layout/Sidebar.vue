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

const navigationGroups = computed(() => {
    const common = [
        { label: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, active: route().current('dashboard') },
    ];

    const employee = [
        { label: 'Mis Turnos', href: route('shifts.index'), icon: Clock, active: route().current('shifts.*') },
        { label: 'Mi Nómina', href: '#', icon: Wallet, active: false },
        { label: 'Historial', href: '#', icon: History, active: false },
    ];

    const admin = [
        { label: 'Empleados', href: route('employees.manage'), icon: Users, active: route().current('employees.manage') },
        { label: 'Gestión de Turnos', href: '#', icon: ShieldCheck, active: false },
        { label: 'Nómina Global', href: '#', icon: Wallet, active: false },
    ];

    return {
        common,
        employee: role.value === 'employee' ? employee : [],
        admin: ['admin', 'supervisor'].includes(role.value) ? admin : [],
    };
});
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        @click="$emit('close')"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 w-64 transform border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-900 lg:static lg:inset-0 lg:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-16 items-center justify-between border-b border-slate-100 px-6 dark:border-slate-800/50">
            <Link :href="route('dashboard')" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white">SF</div>
                <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">ShiftFlow</span>
            </Link>
            <button
                @click="$emit('close')"
                class="rounded-md p-1 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-8 overflow-y-auto px-4 py-6">
            <div>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.common" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>

            <div v-if="navigationGroups.employee.length > 0">
                <h3 class="mb-2 px-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Empleado
                </h3>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.employee" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>

            <div v-if="navigationGroups.admin.length > 0">
                <h3 class="mb-2 px-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Administración
                </h3>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.admin" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="mt-auto border-t border-slate-100 p-4 dark:border-slate-800/50">
            <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-2 dark:bg-slate-800/40">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                    {{ user?.name.substring(0, 1) }}
                </div>
                <div class="overflow-hidden">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ user?.name }}</p>
                    <p class="truncate text-xs capitalize text-slate-500">{{ user?.role }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>
