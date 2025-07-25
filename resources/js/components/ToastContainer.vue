<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import ToastNotification from './ToastNotification.vue';

const { toasts, removeToast } = useToast();

const handleToastClose = (id: string) => {
    removeToast(id);
};
</script>

<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <ToastNotification
            v-for="(toast, index) in toasts"
            :key="toast.id"
            :message="toast"
            :visible="true"
            :style="{ 'z-index': 50 + index }"
            @close="handleToastClose"
        />
    </div>
</template>
