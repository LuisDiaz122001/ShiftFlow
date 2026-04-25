<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Política de Privacidad - ShiftFlow" />

    <div class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-indigo-500 selection:text-white font-sans relative overflow-hidden flex flex-col">
        <!-- Background Decorative Gradients -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute top-[10%] -left-[10%] w-[35%] h-[35%] rounded-full bg-purple-500/10 blur-[120px]"></div>
        </div>

        <!-- Navigation -->
        <nav class="sticky top-0 z-50 w-full border-b border-zinc-200/50 dark:border-zinc-800/50 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <Link href="/" class="flex items-center gap-2 group">
                        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                            <span class="text-white font-bold text-xl">S</span>
                        </div>
                        <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">
                            ShiftFlow
                        </span>
                    </Link>
                    <Link href="/" class="text-sm font-medium text-zinc-500 hover:text-indigo-600 transition-colors">Volver al inicio</Link>
                </div>
            </div>
        </nav>

        <main class="flex-grow max-w-4xl mx-auto px-4 py-16 sm:py-24">
            <div class="animate-slide-up">
                <h1 class="text-4xl sm:text-5xl font-extrabold mb-8">Política de Privacidad</h1>
                <p class="text-lg text-zinc-500 dark:text-zinc-400 mb-12">Última actualización: 24 de abril de 2026</p>

                <div class="prose prose-zinc dark:prose-invert max-w-none space-y-12">
                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </span>
                            Manejo de Datos de Empleados
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            En ShiftFlow, recolectamos y procesamos datos laborales como nombres, correos electrónicos, cargos y registros de asistencia. Estos datos son utilizados exclusivamente para la gestión operativa de turnos y el cálculo de nómina dentro de la organización.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75m-16.5-3.75v3.75" />
                                </svg>
                            </span>
                            Almacenamiento de Información
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Toda la información se almacena en servidores seguros con protocolos de cifrado modernos. Mantenemos los registros históricos de turnos y pagos según los requisitos legales de conservación de documentos laborales vigentes.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.744c0 5.058 3.47 9.357 8.185 10.573a11.95 11.95 0 0 0 8.185-10.573c0-1.31-.21-2.57-.605-3.75A11.959 11.959 0 0 1 12 2.714Z" />
                                </svg>
                            </span>
                            Seguridad del Sistema
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Implementamos controles de acceso basados en roles (RBAC) para asegurar que cada usuario solo pueda ver la información necesaria para su función. Realizamos auditorías periódicas para detectar y prevenir accesos no autorizados.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </span>
                            Uso Interno de Datos
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Los datos laborales no se comparten con terceros con fines comerciales. Solo podrán ser revelados ante autoridades competentes en cumplimiento de la ley o para procesos de auditoría interna de la empresa.
                        </p>
                    </section>
                </div>
            </div>
        </main>

        <footer class="py-12 border-t border-zinc-200/50 dark:border-zinc-800/50 text-center text-sm text-zinc-500">
            ShiftFlow &copy; {{ new Date().getFullYear() }} - Privacidad Protegida
        </footer>
    </div>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
