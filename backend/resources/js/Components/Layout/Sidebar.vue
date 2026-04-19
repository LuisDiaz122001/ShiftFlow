<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    LayoutDashboard, 
    Clock, 
    Wallet, 
    ShieldCheck, 
    History,
    X
} from 'lucide-vue-next';
import NavItem from '@/Components/Layout/NavItem.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role);

const navigationGroups = computed(() => {
    const common = [
        { label: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, active: route().current('dashboard') },
    ];

    const employee = [
        { label: 'Mis Turnos', href: '#', icon: Clock, active: false },
        { label: 'Mi Nómina', href: '#', icon: Wallet, active: false },
        { label: 'Historial', href: '#', icon: History, active: false },
    ];

    const admin = [
        { label: 'Gestión de Turnos', href: '#', icon: ShieldCheck, active: false },
        { label: 'Nómina Global', href: '#', icon: Wallet, active: false },
    ];

    return {
        common,
        employee: role.value === 'employee' ? employee : [],
        admin: role.value === 'admin' ? admin : [],
    };
});
</script>

<template>
    <!-- Overlay for mobile -->
    <div 
        v-if="isOpen" 
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        @click="$emit('close')"
    ></div>

    <!-- Sidebar container -->
    <aside 
        class="fixed inset-y-0 left-0 z-50 w-64 transition-transform duration-300 transform bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-800 lg:translate-x-0 lg:static lg:inset-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Logo section -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-slate-100 dark:border-slate-800/50">
            <Link :href="route('dashboard')" class="flex items-center gap-2">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold">SF</div>
                <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">ShiftFlow</span>
            </Link>
            <button @click="$emit('close')" class="p-1 rounded-md lg:hidden text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                <X class="w-5 h-5" />
            </button>
        </div>

        <!-- Navigation list -->
        <nav class="flex-1 px-4 py-6 space-y-8 overflow-y-auto">
            <!-- Common -->
            <div>
                <ul class="space-y-1">
                    <li v-for="item in navigationGroups.common" :key="item.label">
                        <NavItem :href="item.href" :icon="item.icon" :active="item.active">
                            {{ item.label }}
                        </NavItem>
                    </li>
                </ul>
            </div>

            <!-- Employee Section -->
            <div v-if="navigationGroups.employee.length > 0">
                <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider uppercase text-slate-400 dark:text-slate-500">
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

            <!-- Admin Section -->
            <div v-if="navigationGroups.admin.length > 0">
                <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider uppercase text-slate-400 dark:text-slate-500">
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

        <!-- Footer / User display simple -->
        <div class="p-4 mt-auto border-t border-slate-100 dark:border-slate-800/50">
            <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/40">
                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium">
                    {{ user?.name.substring(0, 1) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold truncate text-slate-900 dark:text-slate-100">{{ user?.name }}</p>
                    <p class="text-xs truncate text-slate-500 capitalize">{{ user?.role }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>
