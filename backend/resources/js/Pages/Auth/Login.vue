<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-indigo-500 selection:text-white font-sans relative overflow-hidden">
        <!-- Background Decorative Gradients -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] rounded-full bg-purple-500/10 blur-[120px]"></div>
            <div class="absolute -bottom-[10%] left-[20%] w-[30%] h-[30%] rounded-full bg-blue-500/10 blur-[120px]"></div>
        </div>

        <!-- Logo/Header -->
        <div class="mb-8 text-center animate-fade-in">
            <Link href="/" class="inline-flex items-center gap-2">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <span class="text-white font-bold text-2xl">S</span>
                </div>
                <span class="text-2xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">
                    ShiftFlow
                </span>
            </Link>
        </div>

        <!-- Auth Card -->
        <div class="w-full sm:max-w-md px-8 py-10 bg-white dark:bg-zinc-900 border border-zinc-200/50 dark:border-zinc-800/50 shadow-2xl shadow-indigo-500/5 sm:rounded-3xl animate-slide-up">
            <div class="mb-8">
                <h1 class="text-2xl font-bold mb-2">Bienvenido a ShiftFlow</h1>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">Inicia sesión para gestionar turnos y nómina</p>
            </div>

            <div v-if="status" class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium mb-2 text-zinc-700 dark:text-zinc-300">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        class="w-full px-4 py-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="tu@email.com"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-rose-500 font-medium">{{ form.errors.email }}</p>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contraseña</label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>
                    <input
                        id="password"
                        type="password"
                        v-model="form.password"
                        class="w-full px-4 py-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-sm text-rose-500 font-medium">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="remember" v-model="form.remember" class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-zinc-900" />
                        <span class="ms-2 text-sm text-zinc-500 group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">Recordarme</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full py-4 rounded-xl bg-indigo-600 text-white font-semibold text-lg hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/25 flex items-center justify-center gap-2 group disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Iniciar sesión</span>
                    <svg v-if="!form.processing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:translate-x-1 transition-transform">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800 text-center">
                <p class="text-sm text-zinc-500">
                    ¿No tienes una cuenta?
                    <Link :href="route('register')" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Crear cuenta
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}
.animate-slide-up {
    animation: slideUp 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

