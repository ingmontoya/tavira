<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import type { InvoiceEmailBatch } from '@/types';
import { router } from '@inertiajs/vue3';
import { Clock, Mail, MailCheck, MailX, Send, Trash2, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    batch: InvoiceEmailBatch;
}

const props = defineProps<Props>();

// Emit events for parent actions
const emit = defineEmits<{
    send: [batch: InvoiceEmailBatch];
    delete: [batch: InvoiceEmailBatch];
}>();

// Get status icon
const getStatusIcon = (status: string) => {
    switch (status) {
        case 'borrador':
            return Clock;
        case 'listo':
            return Mail;
        case 'procesando':
            return Send;
        case 'completado':
            return MailCheck;
        case 'con_errores':
            return MailX;
        default:
            return Clock;
    }
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Calculate progress percentage
const getProgressPercentage = computed(() => {
    if (props.batch.total_invoices === 0) return 0;
    return Math.round((props.batch.sent_count / props.batch.total_invoices) * 100);
});

// Handle actions
const handleSend = () => {
    emit('send', props.batch);
};

const handleDelete = () => {
    emit('delete', props.batch);
};

const viewDetails = () => {
    router.visit(`/invoices/email/${props.batch.id}`);
};
</script>

<template>
    <Card class="transition-all hover:shadow-md">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <component :is="getStatusIcon(batch.status)" class="h-5 w-5 text-muted-foreground" />
                    <CardTitle class="text-lg">{{ batch.name }}</CardTitle>
                </div>
                <Badge :class="batch.status_badge.class">
                    {{ batch.status_badge.text }}
                </Badge>
            </div>
            <CardDescription v-if="batch.description">
                {{ batch.description }}
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Statistics Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ batch.total_invoices }}</div>
                    <div class="text-sm text-muted-foreground">Total facturas</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ batch.sent_count }}</div>
                    <div class="text-sm text-muted-foreground">Enviadas</div>
                </div>
            </div>

            <!-- Progress Bar (only show if not in draft) -->
            <div v-if="batch.status !== 'borrador'" class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span>Progreso</span>
                    <span>{{ getProgressPercentage }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200">
                    <div class="h-2 rounded-full bg-blue-600 transition-all duration-300" :style="{ width: `${getProgressPercentage}%` }"></div>
                </div>
            </div>

            <!-- Error Count (if any) -->
            <div v-if="batch.failed_count > 0" class="flex items-center space-x-2 text-red-600">
                <MailX class="h-4 w-4" />
                <span class="text-sm">{{ batch.failed_count }} errores</span>
            </div>

            <!-- Key Dates -->
            <div class="space-y-1 text-sm text-muted-foreground">
                <div class="flex items-center space-x-2">
                    <User class="h-3 w-3" />
                    <span>{{ batch.created_by.name }}</span>
                </div>
                <div>Creado: {{ formatDate(batch.created_at) }}</div>
                <div v-if="batch.sent_at">Enviado: {{ formatDate(batch.sent_at) }}</div>
                <div v-if="batch.completed_at">Completado: {{ formatDate(batch.completed_at) }}</div>
            </div>
        </CardContent>

        <CardFooter class="pt-0">
            <div class="flex w-full gap-2">
                <Button variant="outline" size="sm" class="flex-1" @click="viewDetails"> Ver Detalles </Button>

                <Button v-if="batch.can_send && batch.status === 'listo'" size="sm" @click="handleSend" class="bg-green-600 hover:bg-green-700">
                    <Send class="mr-2 h-3 w-3" />
                    Enviar
                </Button>

                <Button v-if="batch.can_delete" variant="outline" size="sm" @click="handleDelete" class="text-red-600 hover:text-red-700">
                    <Trash2 class="h-3 w-3" />
                </Button>
            </div>
        </CardFooter>
    </Card>
</template>
