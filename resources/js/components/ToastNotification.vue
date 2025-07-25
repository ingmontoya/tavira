<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AlertCircle, CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

export interface ToastMessage {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title?: string;
    message: string;
    duration?: number;
    persistent?: boolean;
}

interface Props {
    message: ToastMessage | null;
    visible: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [id: string];
}>();

const isVisible = ref(props.visible);

watch(
    () => props.visible,
    (newValue) => {
        isVisible.value = newValue;
    },
);

const closeNotification = () => {
    if (props.message) {
        isVisible.value = false;
        emit('close', props.message.id);
    }
};

const getIcon = computed(() => {
    if (!props.message) return CheckCircle2;

    switch (props.message.type) {
        case 'success':
            return CheckCircle2;
        case 'error':
            return XCircle;
        case 'warning':
            return AlertCircle;
        case 'info':
            return Info;
        default:
            return CheckCircle2;
    }
});

const getVariant = computed(() => {
    if (!props.message) return 'default';

    switch (props.message.type) {
        case 'error':
            return 'destructive';
        default:
            return 'default';
    }
});

const getAlertClasses = computed(() => {
    if (!props.message) return '';

    switch (props.message.type) {
        case 'success':
            return 'border-green-200 bg-green-50 text-green-800';
        case 'warning':
            return 'border-yellow-200 bg-yellow-50 text-yellow-800';
        case 'info':
            return 'border-blue-200 bg-blue-50 text-blue-800';
        case 'error':
            return ''; // Use default destructive variant
        default:
            return '';
    }
});

// Auto-close non-persistent notifications
watch(
    () => props.message,
    (newMessage) => {
        if (newMessage && !newMessage.persistent) {
            const duration = newMessage.duration || (newMessage.type === 'error' ? 7000 : 5000);
            setTimeout(() => {
                closeNotification();
            }, duration);
        }
    },
);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform opacity-0 scale-95 translate-x-full"
        enter-to-class="transform opacity-100 scale-100 translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform opacity-100 scale-100 translate-x-0"
        leave-to-class="transform opacity-0 scale-95 translate-x-full"
    >
        <div v-if="isVisible && message" class="fixed top-4 right-4 z-50 w-96 max-w-sm">
            <Alert :variant="getVariant" :class="['relative', getAlertClasses]">
                <component :is="getIcon" class="h-4 w-4" />

                <Button variant="ghost" size="sm" class="absolute top-2 right-2 h-auto w-auto p-1 hover:bg-black/10" @click="closeNotification">
                    <X class="h-3 w-3" />
                </Button>

                <AlertDescription class="pr-6">
                    <div class="space-y-1">
                        <div v-if="message.title" class="font-medium">
                            {{ message.title }}
                        </div>

                        <div class="text-sm">
                            {{ message.message }}
                        </div>
                    </div>
                </AlertDescription>
            </Alert>
        </div>
    </Transition>
</template>
