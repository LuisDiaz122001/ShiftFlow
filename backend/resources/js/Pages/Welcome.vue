<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
    },
    phpVersion: {
        type: String,
    },
});

const features = [
    {
        title: 'Gestión de empleados',
        description: 'Administra perfiles, roles y documentación de todo tu personal en un solo lugar.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>`
    },
    {
        title: 'Control de turnos',
        description: 'Planifica y asigna turnos de manera eficiente con nuestro calendario inteligente.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`
    },
    {
        title: 'Cálculo automático',
        description: 'Olvídate de las hojas de cálculo. Calculamos automáticamente las horas trabajadas.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-3-3V18m-3-3V18M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`
    },
    {
        title: 'Pagos y recargos',
        description: 'Cálculo preciso de recargos nocturnos, festivos y prestaciones de ley.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 1.5v.75m0 1.5v.75m0 1.5V15m1.5-10.5h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m-9 3h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m-9 3h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75m1.5 0h.75" /></svg>`
    }
];
</script>

<template>
    <Head title="Bienvenido a ShiftFlow" />

    <div class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-indigo-500 selection:text-white font-sans">
        <!-- Background Decorative Gradients -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] rounded-full bg-purple-500/10 blur-[120px]"></div>
            <div class="absolute -bottom-[10%] left-[20%] w-[30%] h-[30%] rounded-full bg-blue-500/10 blur-[120px]"></div>
        </div>

        <!-- Navigation -->
        <nav class="sticky top-0 z-50 w-full border-b border-zinc-200/50 dark:border-zinc-800/50 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/20">
                            <span class="text-white font-bold text-xl">S</span>
                        </div>
                        <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">
                            ShiftFlow
                        </span>
                    </div>

                    <div v-if="canLogin" class="flex items-center gap-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium px-4 py-2 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:opacity-90 transition-all shadow-sm"
                        >
                            Ir al Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                            >
                                Iniciar sesión
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="text-sm font-medium px-4 py-2 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20"
                            >
                                Registrarse
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main>
            <section class="relative pt-20 pb-16 sm:pt-32 sm:pb-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight mb-6">
                        Domina tus <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600">Turnos</span>,<br />
                        Simplifica tu <span class="bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-pink-600">Nómina</span>.
                    </h1>
                    <p class="text-lg sm:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                        Sistema de gestión de turnos y nómina diseñado para empresas modernas. Optimiza el tiempo, reduce errores y mantén a tu equipo feliz.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="w-full sm:w-auto px-8 py-4 rounded-full bg-indigo-600 text-white font-semibold text-lg hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/25 flex items-center justify-center gap-2 group"
                        >
                            Comenzar gratis
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:translate-x-1 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="w-full sm:w-auto px-8 py-4 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 font-semibold text-lg hover:opacity-90 transition-all shadow-xl"
                        >
                            Ir al Dashboard
                        </Link>
                        <a href="#features" class="w-full sm:w-auto px-8 py-4 rounded-full border border-zinc-200 dark:border-zinc-800 font-semibold text-lg hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-all">
                            Saber más
                        </a>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="py-20 bg-zinc-50/50 dark:bg-zinc-900/30">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl sm:text-4xl font-bold mb-4">Todo lo que necesitas en un solo lugar</h2>
                        <p class="text-zinc-600 dark:text-zinc-400">Potentes herramientas diseñadas para escalar tu operación.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div
                            v-for="(feature, index) in features"
                            :key="index"
                            class="group p-8 rounded-3xl bg-white dark:bg-zinc-900 border border-zinc-200/50 dark:border-zinc-800/50 hover:border-indigo-500/50 dark:hover:border-indigo-400/50 transition-all hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1"
                        >
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform" v-html="feature.icon"></div>
                            <h3 class="text-xl font-bold mb-3">{{ feature.title }}</h3>
                            <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed text-sm">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Trust Section -->
            <section class="py-20 border-t border-zinc-200/50 dark:border-zinc-800/50">
                <div class="max-w-4xl mx-auto px-4 text-center">
                    <h2 class="text-2xl font-bold mb-8 opacity-50 uppercase tracking-widest text-sm">Desarrollado con</h2>
                    <div class="flex flex-wrap justify-center items-center gap-12 grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition-all">
                        <span class="text-2xl font-bold">Laravel</span>
                        <span class="text-2xl font-bold">Vue.js</span>
                        <span class="text-2xl font-bold">Inertia</span>
                        <span class="text-2xl font-bold">TailwindCSS</span>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="py-12 border-t border-zinc-200/50 dark:border-zinc-800/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-indigo-600 rounded flex items-center justify-center">
                        <span class="text-white font-bold text-xs">S</span>
                    </div>
                    <span class="font-bold text-zinc-500">ShiftFlow &copy; {{ new Date().getFullYear() }}</span>
                </div>
                <div class="flex gap-8 text-sm text-zinc-500">
                    <Link :href="route('privacy')" class="hover:text-indigo-600 transition-colors">Privacidad</Link>
                    <Link :href="route('terms')" class="hover:text-indigo-600 transition-colors">Términos</Link>
                    <Link :href="route('contact')" class="hover:text-indigo-600 transition-colors font-medium">Contacto</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Optional: smooth scroll for the "Saber más" button */
html {
    scroll-behavior: smooth;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

