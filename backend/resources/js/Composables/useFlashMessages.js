import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useFlashMessages() {
    const page = usePage();
    const successMessage = ref(page.props.flash?.success ?? '');
    const errorMessage = ref('');

    watch(
        () => page.props.flash?.success,
        (value) => {
            if (value) {
                successMessage.value = value;
            }
        },
    );

    const clearMessages = () => {
        successMessage.value = '';
        errorMessage.value = '';
    };

    const setFormErrors = (form, fallback = 'No fue posible completar la operación.') => {
        errorMessage.value = form.errors.error || Object.values(form.errors)[0] || fallback;
    };

    return {
        page,
        successMessage,
        errorMessage,
        clearMessages,
        setFormErrors,
    };
}
