<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <AppLayout title="Perfil">
        <div class="space-y-6">
            <div class="max-w-3xl">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Perfil</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Administra la informacion de la cuenta, la contrasena y el acceso.
                </p>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
