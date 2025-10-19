<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { AlertTriangle, CheckCircle, Info, X, XCircle } from 'lucide-vue-next';

const { toasts, removeToast } = useToast();

const getToastIcon = (type: string) => {
    switch (type) {
        case 'success':
            return CheckCircle;
        case 'error':
            return XCircle;
        case 'warning':
            return AlertTriangle;
        case 'info':
            return Info;
        default:
            return Info;
    }
};

const getToastClasses = (type: string) => {
    switch (type) {
        case 'success':
            return 'border-green-200 bg-green-50 text-green-800';
        case 'error':
            return 'border-red-200 bg-red-50 text-red-800';
        case 'warning':
            return 'border-yellow-200 bg-yellow-50 text-yellow-800';
        case 'info':
            return 'border-blue-200 bg-blue-50 text-blue-800';
        default:
            return 'border-gray-200 bg-gray-50 text-gray-800';
    }
};

const getIconClasses = (type: string) => {
    switch (type) {
        case 'success':
            return 'text-green-600';
        case 'error':
            return 'text-red-600';
        case 'warning':
            return 'text-yellow-600';
        case 'info':
            return 'text-blue-600';
        default:
            return 'text-gray-600';
    }
};
</script>

<template>
    <div class="fixed top-4 right-4 z-50 w-full max-w-sm space-y-2">
        <TransitionGroup name="toast" tag="div">
            <Alert v-for="toast in toasts" :key="toast.id" :class="getToastClasses(toast.type)" class="relative border shadow-lg">
                <component :is="getToastIcon(toast.type)" class="h-4 w-4" :class="getIconClasses(toast.type)" />
                <AlertDescription class="pr-8">
                    <div class="font-medium" v-if="toast.title">{{ toast.title }}</div>
                    <div class="text-sm" :class="toast.title ? 'mt-1' : ''">{{ toast.message }}</div>
                </AlertDescription>

                <!-- Close button -->
                <Button @click="removeToast(toast.id)" variant="ghost" size="sm" class="absolute top-2 right-2 h-6 w-6 p-0 hover:bg-black/10">
                    <X class="h-3 w-3" />
                </Button>
            </Alert>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.toast-move {
    transition: transform 0.3s ease;
}
</style>
