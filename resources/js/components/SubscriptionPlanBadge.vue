<script setup lang="ts">
import { useFeatures } from '@/composables/useFeatures';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const { subscriptionPlan } = useFeatures();

const planConfig = computed(() => {
    const plans: Record<
        string,
        { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }
    > = {
        basic: {
            label: 'Básico',
            variant: 'outline',
        },
        standard: {
            label: 'Estándar',
            variant: 'secondary',
        },
        premium: {
            label: 'Premium',
            variant: 'default',
        },
        enterprise: {
            label: 'Enterprise',
            variant: 'default',
        },
    };

    return plans[subscriptionPlan.value] || plans.basic;
});
</script>

<template>
    <Badge :variant="planConfig.variant">
        {{ planConfig.label }}
    </Badge>
</template>
