<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Clock, MailCheck, MailX } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    status: 'pendiente' | 'enviado' | 'fallido' | 'rebotado';
    statusLabel: string;
    showIcon?: boolean;
    size?: 'sm' | 'default' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    showIcon: true,
    size: 'default',
});

// Get status configuration
const statusConfig = computed(() => {
    switch (props.status) {
        case 'pendiente':
            return {
                icon: Clock,
                class: 'bg-orange-100 text-orange-800 border-orange-200',
                textClass: 'text-orange-800',
            };
        case 'enviado':
            return {
                icon: MailCheck,
                class: 'bg-green-100 text-green-800 border-green-200',
                textClass: 'text-green-800',
            };
        case 'fallido':
            return {
                icon: MailX,
                class: 'bg-red-100 text-red-800 border-red-200',
                textClass: 'text-red-800',
            };
        case 'rebotado':
            return {
                icon: MailX,
                class: 'bg-red-100 text-red-800 border-red-200',
                textClass: 'text-red-800',
            };
        default:
            return {
                icon: Clock,
                class: 'bg-gray-100 text-gray-800 border-gray-200',
                textClass: 'text-gray-800',
            };
    }
});

// Get icon size based on badge size
const iconSize = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-3 w-3';
        case 'lg':
            return 'h-5 w-5';
        default:
            return 'h-4 w-4';
    }
});
</script>

<template>
    <Badge 
        :class="[statusConfig.class, size === 'sm' ? 'text-xs' : size === 'lg' ? 'text-base' : 'text-sm']"
        variant="outline"
    >
        <component 
            v-if="showIcon"
            :is="statusConfig.icon" 
            :class="['mr-1', iconSize]"
        />
        {{ statusLabel }}
    </Badge>
</template>