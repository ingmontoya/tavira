import { usePage } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';

interface Toast {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title?: string;
    message: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);

let toastIdCounter = 0;

export function useToast() {
    const page = usePage();

    // Watch for flash messages and convert them to toasts
    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) {
                addToast({
                    type: 'success',
                    title: 'Éxito',
                    message: flash.success,
                });
            }
            if (flash?.error) {
                addToast({
                    type: 'error',
                    title: 'Error',
                    message: flash.error,
                });
            }
            if (flash?.warning) {
                addToast({
                    type: 'warning',
                    title: 'Advertencia',
                    message: flash.warning,
                });
            }
            if (flash?.info) {
                addToast({
                    type: 'info',
                    title: 'Información',
                    message: flash.info,
                });
            }
        },
        { immediate: true, deep: true }
    );

    function addToast(toast: Omit<Toast, 'id'>) {
        const id = `toast-${++toastIdCounter}`;
        const newToast: Toast = {
            id,
            duration: 5000, // 5 seconds default
            ...toast,
        };

        toasts.value.push(newToast);

        // Auto remove after duration
        if (newToast.duration && newToast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, newToast.duration);
        }

        return id;
    }

    function removeToast(id: string) {
        const index = toasts.value.findIndex(toast => toast.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    }

    function toast(message: string, type: Toast['type'] = 'info', options?: Partial<Toast>) {
        return addToast({
            type,
            message,
            ...options,
        });
    }

    function success(message: string, options?: Partial<Toast>) {
        return toast(message, 'success', options);
    }

    function error(message: string, options?: Partial<Toast>) {
        return toast(message, 'error', options);
    }

    function warning(message: string, options?: Partial<Toast>) {
        return toast(message, 'warning', options);
    }

    function info(message: string, options?: Partial<Toast>) {
        return toast(message, 'info', options);
    }

    return {
        toasts: computed(() => toasts.value),
        addToast,
        removeToast,
        toast,
        success,
        error,
        warning,
        info,
    };
}

// Global instance for convenience
export const globalToast = {
    success: (message: string, options?: Partial<Omit<Toast, 'id' | 'type'>>) => {
        const instance = useToast();
        return instance.success(message, options);
    },
    error: (message: string, options?: Partial<Omit<Toast, 'id' | 'type'>>) => {
        const instance = useToast();
        return instance.error(message, options);
    },
    warning: (message: string, options?: Partial<Omit<Toast, 'id' | 'type'>>) => {
        const instance = useToast();
        return instance.warning(message, options);
    },
    info: (message: string, options?: Partial<Omit<Toast, 'id' | 'type'>>) => {
        const instance = useToast();
        return instance.info(message, options);
    },
};