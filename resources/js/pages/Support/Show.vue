<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { MessageSquare, ArrowLeft, Send, RefreshCw, Clock, User, Calendar, Tag, AlertCircle } from 'lucide-vue-next';
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
    'technical': 'Técnico',
    'billing': 'Facturación',
    'general': 'General',
    'feature_request': 'Solicitud de función',
    'bug_report': 'Reporte de error',
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'open': return 'default';
        case 'in_progress': return 'secondary';
        case 'resolved': return 'outline';
        case 'closed': return 'destructive';
        default: return 'default';
    }
};

const getPriorityBadgeVariant = (priority: string) => {
    switch (priority) {
        case 'low': return 'secondary';
        case 'medium': return 'default';
        case 'high': return 'outline';
        case 'urgent': return 'destructive';
        default: return 'default';
    }
};

const canReopen = computed(() => 
    (props.ticket.status === 'resolved' || props.ticket.status === 'closed') &&
    (!props.canManage || props.ticket.user.id === 1) // Assuming current user ID is available
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
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Ticket #${ticket.id} - ${ticket.title}`" />
    
    <AppLayout>
        <div class="container mx-auto px-4 py-8 max-w-5xl">
            <div class="flex items-center gap-4 mb-6">
                <Button variant="ghost" size="sm" @click="router.visit('/support')" class="p-2">
                    <ArrowLeft class="w-4 h-4" />
                </Button>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-semibold text-gray-900">
                            Ticket #{{ ticket.id }}
                        </h1>
                        <Badge :variant="getStatusBadgeVariant(ticket.status)">
                            {{ statusOptions.find(s => s.value === ticket.status)?.label }}
                        </Badge>
                        <Badge :variant="getPriorityBadgeVariant(ticket.priority)">
                            {{ priorityOptions.find(p => p.value === ticket.priority)?.label }}
                        </Badge>
                    </div>
                    <h2 class="text-lg text-gray-700">{{ ticket.title }}</h2>
                </div>
                <div v-if="canReopen" class="flex gap-2">
                    <Button @click="reopenTicket" variant="outline" size="sm">
                        <RefreshCw class="w-4 h-4 mr-2" />
                        Reabrir
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información del ticket -->
                <div class="lg:col-span-1 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Tag class="w-4 h-4" />
                                Detalles
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <User class="w-4 h-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Creado por</p>
                                    <p class="text-sm text-gray-600">{{ ticket.user.name }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <Calendar class="w-4 h-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Fecha de creación</p>
                                    <p class="text-sm text-gray-600">{{ formatDate(ticket.created_at) }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <Tag class="w-4 h-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Categoría</p>
                                    <p class="text-sm text-gray-600">{{ categoryLabels[ticket.category] }}</p>
                                </div>
                            </div>

                            <div v-if="ticket.assigned_to" class="flex items-center gap-3">
                                <User class="w-4 h-4 text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium">Asignado a</p>
                                    <p class="text-sm text-gray-600">{{ ticket.assigned_to.name }}</p>
                                </div>
                            </div>

                            <div v-if="ticket.resolved_at" class="flex items-center gap-3">
                                <Clock class="w-4 h-4 text-gray-500" />
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
                                <AlertCircle class="w-4 h-4" />
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
                                            <SelectItem 
                                                v-for="option in statusOptions" 
                                                :key="option.value" 
                                                :value="option.value"
                                            >
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
                                            <SelectItem 
                                                v-for="option in priorityOptions" 
                                                :key="option.value" 
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Button 
                                    type="submit" 
                                    :disabled="statusForm.processing"
                                    size="sm"
                                    class="w-full"
                                >
                                    {{ statusForm.processing ? 'Actualizando...' : 'Actualizar' }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Conversación -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="w-5 h-5" />
                                Conversación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Mensaje inicial -->
                                <div class="flex gap-3 p-4 bg-gray-50 rounded-lg">
                                    <Avatar class="w-8 h-8">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(ticket.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-sm">{{ ticket.user.name }}</span>
                                            <span class="text-xs text-gray-500">{{ formatDate(ticket.created_at) }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ ticket.description }}</p>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Mensajes adicionales -->
                                <div v-for="message in ticket.messages.slice(1)" :key="message.id" class="flex gap-3">
                                    <Avatar class="w-8 h-8">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(message.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-sm">{{ message.user.name }}</span>
                                            <Badge v-if="message.is_admin_reply" variant="outline" class="text-xs px-1 py-0">
                                                Soporte
                                            </Badge>
                                            <span class="text-xs text-gray-500">{{ formatDate(message.created_at) }}</span>
                                        </div>
                                        <div 
                                            class="p-3 rounded-lg text-sm"
                                            :class="message.is_admin_reply 
                                                ? 'bg-blue-50 border border-blue-200' 
                                                : 'bg-gray-50 border border-gray-200'
                                            "
                                        >
                                            <p class="whitespace-pre-wrap">{{ message.message }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulario para nuevo mensaje -->
                                <div v-if="ticket.status !== 'closed'" class="border-t pt-4 mt-6">
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
                                                <Send class="w-4 h-4" />
                                                {{ messageForm.processing ? 'Enviando...' : 'Enviar respuesta' }}
                                            </Button>
                                        </div>
                                    </form>
                                </div>

                                <div v-else class="border-t pt-4 mt-6">
                                    <div class="text-center py-4 text-gray-500">
                                        <p class="text-sm">Este ticket está cerrado. No se pueden agregar más mensajes.</p>
                                        <p v-if="canReopen" class="text-xs mt-1">
                                            Puedes reabrirlo si necesitas continuar la conversación.
                                        </p>
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