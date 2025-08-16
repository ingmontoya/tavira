<script setup lang="ts">
import type { BatchProgressData } from '@/types';
import { computed } from 'vue';

interface Props {
    progress: BatchProgressData;
    showLabels?: boolean;
    showPercentage?: boolean;
    size?: 'sm' | 'default' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    showLabels: true,
    showPercentage: true,
    size: 'default',
});

// Calculate segment widths
const successWidth = computed(() => (props.progress.sent / props.progress.total) * 100);
const failedWidth = computed(() => (props.progress.failed / props.progress.total) * 100);
const pendingWidth = computed(() => (props.progress.pending / props.progress.total) * 100);

// Get height class based on size
const heightClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-1.5';
        case 'lg':
            return 'h-3';
        default:
            return 'h-2';
    }
});

// Text size classes
const textSizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'text-xs';
        case 'lg':
            return 'text-base';
        default:
            return 'text-sm';
    }
});
</script>

<template>
    <div class="space-y-2">
        <!-- Labels and Percentage -->
        <div v-if="showLabels || showPercentage" class="flex items-center justify-between">
            <div v-if="showLabels" :class="['font-medium', textSizeClass]">Progreso de envío</div>
            <div v-if="showPercentage" :class="['font-medium', textSizeClass]">{{ Math.round(progress.percentage) }}%</div>
        </div>

        <!-- Multi-segment Progress Bar -->
        <div :class="['w-full overflow-hidden rounded-full bg-gray-200', heightClass]">
            <div class="flex h-full">
                <!-- Sent (Green) -->
                <div v-if="successWidth > 0" class="bg-green-500 transition-all duration-300" :style="{ width: `${successWidth}%` }"></div>

                <!-- Failed (Red) -->
                <div v-if="failedWidth > 0" class="bg-red-500 transition-all duration-300" :style="{ width: `${failedWidth}%` }"></div>

                <!-- Pending (Orange) -->
                <div v-if="pendingWidth > 0" class="bg-orange-400 transition-all duration-300" :style="{ width: `${pendingWidth}%` }"></div>
            </div>
        </div>

        <!-- Statistics -->
        <div v-if="showLabels" class="grid grid-cols-3 gap-2">
            <div class="text-center">
                <div :class="['font-medium text-green-600', textSizeClass]">
                    {{ progress.sent }}
                </div>
                <div :class="['text-muted-foreground', props.size === 'sm' ? 'text-xs' : 'text-xs']">Enviadas</div>
            </div>

            <div class="text-center">
                <div :class="['font-medium text-red-600', textSizeClass]">
                    {{ progress.failed }}
                </div>
                <div :class="['text-muted-foreground', props.size === 'sm' ? 'text-xs' : 'text-xs']">Fallidas</div>
            </div>

            <div class="text-center">
                <div :class="['font-medium text-orange-600', textSizeClass]">
                    {{ progress.pending }}
                </div>
                <div :class="['text-muted-foreground', props.size === 'sm' ? 'text-xs' : 'text-xs']">Pendientes</div>
            </div>
        </div>

        <!-- Status Text -->
        <div v-if="showLabels" :class="['text-center text-muted-foreground', props.size === 'sm' ? 'text-xs' : 'text-xs']">
            {{ progress.current_status }}
        </div>
    </div>
</template>
