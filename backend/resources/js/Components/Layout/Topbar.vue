<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { 
    Menu, 
    Moon, 
    Sun, 
    LogOut, 
    ChevronDown,
    User
} from 'lucide-vue-next';

const emit = defineEmits(['toggleSidebar']);

const isDark = ref(true);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    isDark.value = savedTheme === 'dark';
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const showUserMenu = ref(false);
</script>

<template>
    <header class="sticky top-0 z-30 flex items-center h-16 px-4 bg-white/80 backdrop-blur-md border-b border-slate-200 dark:bg-slate-900/80 dark:border-slate-800">
        <!-- Sidebar Toggle (Mobile) -->
        <button 
            @click="$emit('toggleSidebar')"
            class="p-2 mr-4 rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800"
        >
            <Menu class="w-6 h-6" />
        </button>

        <!-- Current Breadcrumb / Title (Placeholder) -->
        <div class="hidden sm:block">
            <h2 class="text-sm font-medium text-slate-500 dark:text-slate-400">ShiftFlow / {{ route().current()?.split('.')[0] }}</h2>
        </div>

        <div class="flex items-center ml-auto space-x-2">
            <!-- Theme Toggle -->
            <button 
                @click="toggleTheme"
                class="p-2 transition-colors rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                :title="isDark ? 'Activar Modo Claro' : 'Activar Modo Oscuro'"
            >
                <Sun v-if="isDark" class="w-5 h-5" />
                <Moon v-else class="w-5 h-5" />
            </button>

            <!-- User Menu Group -->
            <div class="relative">
                <button 
                    @click="showUserMenu = !showUserMenu"
                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <div class="hidden text-right md:block">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ user?.name }}</p>
                    </div>
                    <ChevronDown class="w-4 h-4 text-slate-400" />
                </button>

                <!-- Dropdown -->
                <div 
                    v-if="showUserMenu" 
                    @click="showUserMenu = false"
                    class="absolute right-0 w-48 mt-2 origin-top-right bg-white border rounded-xl shadow-xl dark:bg-slate-900 dark:border-slate-800 border-slate-200 overflow-hidden ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800/50">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Cuenta</p>
                    </div>
                    <Link 
                        :href="route('profile.edit')" 
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/50"
                    >
                        <User class="w-4 h-4" /> Perfil
                    </Link>
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        class="flex items-center w-full gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10"
                    >
                        <LogOut class="w-4 h-4" /> Cerrar Sesión
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>
