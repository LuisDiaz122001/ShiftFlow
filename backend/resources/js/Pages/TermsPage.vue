<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Términos de Uso - ShiftFlow" />

    <div class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-indigo-500 selection:text-white font-sans relative overflow-hidden flex flex-col">
        <!-- Background Decorative Gradients -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] rounded-full bg-purple-500/10 blur-[120px]"></div>
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
                <h1 class="text-4xl sm:text-5xl font-extrabold mb-8">Términos de Uso</h1>
                <p class="text-lg text-zinc-500 dark:text-zinc-400 mb-12">Última actualización: 24 de abril de 2026</p>

                <div class="prose prose-zinc dark:prose-invert max-w-none space-y-12">
                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">1</span>
                            Uso del Sistema
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            ShiftFlow es una plataforma diseñada para la gestión eficiente de turnos laborales y nómina. Al acceder a este sistema, el usuario se compromete a utilizar las herramientas proporcionadas únicamente para fines laborales y administrativos legítimos dentro de su organización.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">2</span>
                            Responsabilidad del Usuario
                        </h2>
                        <ul class="list-disc pl-6 space-y-3 text-zinc-600 dark:text-zinc-400">
                            <li>Mantener la confidencialidad de sus credenciales de acceso.</li>
                            <li>Registrar información veraz y precisa sobre los turnos y horas trabajadas.</li>
                            <li>No intentar vulnerar la seguridad del sistema o acceder a datos de otros usuarios sin autorización.</li>
                            <li>Notificar cualquier irregularidad técnica o de seguridad de manera inmediata.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">3</span>
                            Protección de Datos
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            El sistema procesa información personal y laboral sensible. ShiftFlow implementa medidas de seguridad técnicas y organizativas para proteger estos datos, pero el usuario también es responsable del manejo prudente de la información que visualiza en la plataforma.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">4</span>
                            Uso del Sistema de Turnos
                        </h2>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">
                            La planificación y asignación de turnos es responsabilidad de los administradores y supervisores designados. Los cálculos de nómina generados por el sistema son herramientas de apoyo y deben ser validados por el área contable correspondiente antes de cualquier pago.
                        </p>
                    </section>
                </div>
            </div>
        </main>

        <footer class="py-12 border-t border-zinc-200/50 dark:border-zinc-800/50 text-center text-sm text-zinc-500">
            ShiftFlow &copy; {{ new Date().getFullYear() }} - Todos los derechos reservados.
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
