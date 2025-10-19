<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Calendar, Clock, MessageSquare, RefreshCw, Send, Tag, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface SupportMessage {
    id: number;
    message: string;
    is_admin_reply: boolean;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
}

interface SupportTicket {
    id: number;
    title: string;
    description: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    category: 'technical' | 'billing' | 'general' | 'feature_request' | 'bug_report';
    created_at: string;
    resolved_at?: string;
    user: {
        id: number;
        name: string;
    };
    assigned_to?: {
        id: number;
        name: string;
    };
    messages: SupportMessage[];
}

const props = defineProps<{
    ticket: SupportTicket;
    canManage: boolean;
}>();

// Navigation helpers

const messageForm = useForm({
    message: '',
});

const statusForm = useForm({
    status: props.ticket.status,
    priority: props.ticket.priority,
    assigned_to: props.ticket.assigned_to?.id || null,
});

const statusOptions = [
    { value: 'open', label: 'Abierto', color: 'bg-blue-500' },
    { value: 'in_progress', label: 'En progreso', color: 'bg-yellow-500' },
    { value: 'resolved', label: 'Resuelto', color: 'bg-green-500' },
    { value: 'closed', label: 'Cerrado', color: 'bg-gray-500' },
];

const priorityOptions = [
    { value: 'low', label: 'Baja', color: 'bg-gray-500' },
    { value: 'medium', label: 'Media', color: 'bg-blue-500' },
    { value: 'high', label: 'Alta', color: 'bg-orange-500' },
    { value: 'urgent', label: 'Urgente', color: 'bg-red-500' },
];

const categoryLabels = {
    technical: 'Técnico',
    billing: 'Facturación',
    general: 'General',
    feature_request: 'Solicitud de función',
    bug_report: 'Reporte de error',
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'open':
            return 'default';
        case 'in_progress':
            return 'secondary';
        case 'resolved':
            return 'outline';
        case 'closed':
            return 'destructive';
        default:
            return 'default';
    }
};

const getPriorityBadgeVariant = (priority: string) => {
    switch (priority) {
        case 'low':
            return 'secondary';
        case 'medium':
            return 'default';
        case 'high':
            return 'outline';
        case 'urgent':
            return 'destructive';
        default:
            return 'default';
    }
};

const canReopen = computed(
    () => (props.ticket.status === 'resolved' || props.ticket.status === 'closed') && (!props.canManage || props.ticket.user.id === 1), // Assuming current user ID is available
);

const submitMessage = () => {
    messageForm.post(route('support.add-message', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset();
        },
    });
};

const updateStatus = () => {
    statusForm.patch(route('support.update', props.ticket.id), {
        preserveScroll: true,
    });
};

const reopenTicket = () => {
    statusForm.post(route('support.reopen', props.ticket.id), {
        preserveScroll: true,
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Ticket #${ticket.id} - ${ticket.title}`" />

    <AppLayout>
        <div class="container mx-auto max-w-5xl px-4 py-8">
            <div class="mb-6 flex items-center gap-4">
                <Button variant="ghost" size="sm" @click="router.visit('/support')" class="p-2">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1">
                    <div class="mb-2 flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-gray-900">Ticket #{{ ticket.id }}</h1>
                        <Badge :variant="getStatusBadgeVariant(ticket.status)">
                            {{ statusOptions.find((s) => s.value === ticket.status)?.label }}
                        </Badge>
                        <Badge :variant="getPriorityBadgeVariant(ticket.priority)">
                            {{ priorityOptions.find((p) => p.value === ticket.priority)?.label }}
                        </Badge>
                    </div>
                    <h2 class="text-lg text-gray-700">{{ ticket.title }}</h2>
                </div>
                <div v-if="canReopen" class="flex gap-2">
                    <Button @click="reopenTicket" variant="outline" size="sm">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Reabrir
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Información del ticket -->
                <div class="space-y-6 lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Tag class="h-4 w-4" />
                                Detalles
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <User class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Creado por</p>
                                    <p class="text-sm text-gray-600">{{ ticket.user.name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Calendar class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Fecha de creación</p>
                                    <p class="text-sm text-gray-600">{{ formatDate(ticket.created_at) }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Tag class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Categoría</p>
                                    <p class="text-sm text-gray-600">{{ categoryLabels[ticket.category] }}</p>
                                </div>
                            </div>

                            <div v-if="ticket.assigned_to" class="flex items-center gap-3">
                                <User class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Asignado a</p>
                                    <p class="text-sm text-gray-600">{{ ticket.assigned_to.name }}</p>
                                </div>
                            </div>

                            <div v-if="ticket.resolved_at" class="flex items-center gap-3">
                                <Clock class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Fecha de resolución</p>
                                    <p class="text-sm text-gray-600">{{ formatDate(ticket.resolved_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Panel de gestión para administradores -->
                    <Card v-if="canManage">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <AlertCircle class="h-4 w-4" />
                                Gestión
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="updateStatus" class="space-y-4">
                                <div>
                                    <Label class="text-sm font-medium">Estado</Label>
                                    <Select v-model="statusForm.status">
                                        <SelectTrigger class="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label class="text-sm font-medium">Prioridad</Label>
                                    <Select v-model="statusForm.priority">
                                        <SelectTrigger class="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Button type="submit" :disabled="statusForm.processing" size="sm" class="w-full">
                                    {{ statusForm.processing ? 'Actualizando...' : 'Actualizar' }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Conversación -->
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                Conversación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Mensaje inicial -->
                                <div class="flex gap-3 rounded-lg bg-gray-50 p-4">
                                    <Avatar class="h-8 w-8">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(ticket.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="mb-1 flex items-center gap-2">
                                            <span class="text-sm font-medium">{{ ticket.user.name }}</span>
                                            <span class="text-xs text-gray-500">{{ formatDate(ticket.created_at) }}</span>
                                        </div>
                                        <p class="text-sm whitespace-pre-wrap text-gray-700">{{ ticket.description }}</p>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Mensajes adicionales -->
                                <div v-for="message in ticket.messages.slice(1)" :key="message.id" class="flex gap-3">
                                    <Avatar class="h-8 w-8">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(message.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="mb-1 flex items-center gap-2">
                                            <span class="text-sm font-medium">{{ message.user.name }}</span>
                                            <Badge v-if="message.is_admin_reply" variant="outline" class="px-1 py-0 text-xs"> Soporte </Badge>
                                            <span class="text-xs text-gray-500">{{ formatDate(message.created_at) }}</span>
                                        </div>
                                        <div
                                            class="rounded-lg p-3 text-sm"
                                            :class="
                                                message.is_admin_reply ? 'border border-blue-200 bg-blue-50' : 'border border-gray-200 bg-gray-50'
                                            "
                                        >
                                            <p class="whitespace-pre-wrap">{{ message.message }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulario para nuevo mensaje -->
                                <div v-if="ticket.status !== 'closed'" class="mt-6 border-t pt-4">
                                    <form @submit.prevent="submitMessage" class="space-y-3">
                                        <Label class="text-sm font-medium">Agregar respuesta</Label>
                                        <Textarea
                                            v-model="messageForm.message"
                                            placeholder="Escribe tu respuesta..."
                                            rows="4"
                                            required
                                            :class="{ 'border-red-500': messageForm.errors.message }"
                                        />
                                        <p v-if="messageForm.errors.message" class="text-sm text-red-600">
                                            {{ messageForm.errors.message }}
                                        </p>
                                        <div class="flex justify-end">
                                            <Button
                                                type="submit"
                                                :disabled="messageForm.processing || !messageForm.message.trim()"
                                                class="flex items-center gap-2"
                                            >
                                                <Send class="h-4 w-4" />
                                                {{ messageForm.processing ? 'Enviando...' : 'Enviar respuesta' }}
                                            </Button>
                                        </div>
                                    </form>
                                </div>

                                <div v-else class="mt-6 border-t pt-4">
                                    <div class="py-4 text-center text-gray-500">
                                        <p class="text-sm">Este ticket está cerrado. No se pueden agregar más mensajes.</p>
                                        <p v-if="canReopen" class="mt-1 text-xs">Puedes reabrirlo si necesitas continuar la conversación.</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
