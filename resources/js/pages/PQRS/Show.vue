<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    AlertCircle, ArrowLeft, Calendar, CheckCircle, Clock, Download, Edit, 
    FileText, Mail, MessageSquare, Paperclip, Phone, Star, Trash2, User, Users 
} from 'lucide-vue-next';
import { ref } from 'vue';

interface PqrsAttachment {
    id: number;
    filename: string;
    original_filename: string;
    mime_type: string;
    file_size: number;
    file_path: string;
    type: 'evidence' | 'document' | 'photo';
    uploaded_by: {
        id: number;
        name: string;
    };
    created_at: string;
    type_display: string;
    file_size_formatted: string;
}

interface Pqrs {
    id: number;
    ticket_number: string;
    type: 'peticion' | 'queja' | 'reclamo' | 'sugerencia';
    subject: string;
    description: string;
    priority: 'baja' | 'media' | 'alta' | 'urgente';
    status: 'abierto' | 'en_proceso' | 'resuelto' | 'cerrado';
    contact_name: string;
    contact_email: string;
    contact_phone?: string;
    created_at: string;
    assigned_at?: string;
    resolved_at?: string;
    satisfaction_rating?: number;
    satisfaction_comments?: string;
    admin_notes?: string;
    resolution?: string;
    requires_follow_up: boolean;
    follow_up_date?: string;
    follow_up_notes?: string;
    apartment?: {
        id: number;
        number: string;
        tower: string;
        floor: number;
        apartment_type: {
            id: number;
            name: string;
        };
    };
    submitted_by: {
        id: number;
        name: string;
    };
    assigned_to?: {
        id: number;
        name: string;
    };
    resolved_by?: {
        id: number;
        name: string;
    };
    attachments: PqrsAttachment[];
    type_display: string;
    priority_display: string;
    status_display: string;
    status_color: string;
    priority_color: string;
}

const props = defineProps<{
    pqrs: Pqrs;
    administrators: Array<{
        id: number;
        name: string;
    }>;
    canEdit: boolean;
    canManage: boolean;
    canRate: boolean;
}>();

const showAssignForm = ref(false);
const showResolveForm = ref(false);
const showRatingForm = ref(false);

const assignForm = useForm({
    assigned_to: props.pqrs.assigned_to?.id || null,
});

const resolveForm = useForm({
    status: props.pqrs.status,
    resolution: props.pqrs.resolution || '',
    admin_notes: props.pqrs.admin_notes || '',
    requires_follow_up: props.pqrs.requires_follow_up,
    follow_up_date: props.pqrs.follow_up_date || '',
    follow_up_notes: props.pqrs.follow_up_notes || '',
});

const ratingForm = useForm({
    satisfaction_rating: 5,
    satisfaction_comments: '',
});

const submitAssign = () => {
    assignForm.post(route('pqrs.assign', props.pqrs.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAssignForm.value = false;
        },
    });
};

const submitResolve = () => {
    resolveForm.post(route('pqrs.resolve', props.pqrs.id), {
        preserveScroll: true,
        onSuccess: () => {
            showResolveForm.value = false;
        },
    });
};

const submitRating = () => {
    ratingForm.post(route('pqrs.rate', props.pqrs.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRatingForm.value = false;
        },
    });
};

const deleteAttachment = (attachmentId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este archivo?')) {
        router.delete(route('pqrs.attachments.destroy', attachmentId), {
            preserveScroll: true,
        });
    }
};

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'peticion':
            return FileText;
        case 'queja':
            return AlertCircle;
        case 'reclamo':
            return MessageSquare;
        case 'sugerencia':
            return MessageSquare;
        default:
            return FileText;
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'abierto':
            return Clock;
        case 'en_proceso':
            return Clock;
        case 'resuelto':
            return CheckCircle;
        case 'cerrado':
            return CheckCircle;
        default:
            return Clock;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getApartmentDisplay = (apartment: any) => {
    if (!apartment) return 'Sin apartamento específico';
    return `${apartment.tower}${apartment.number} - ${apartment.apartment_type.name}`;
};

const renderStars = (rating: number) => {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
        stars.push(i <= rating);
    }
    return stars;
};

const breadcrumbs = [
    { label: 'PQRS', href: route('pqrs.index'), current: false },
    { label: props.pqrs.ticket_number, href: '#', current: true }
];
</script>

<template>
    <Head :title="`PQRS ${pqrs.ticket_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <component :is="getTypeIcon(pqrs.type)" class="h-6 w-6 text-gray-400" />
                        <h1 class="text-2xl font-semibold text-gray-900">{{ pqrs.ticket_number }}</h1>
                        <Badge :class="pqrs.status_color">{{ pqrs.status_display }}</Badge>
                        <Badge :class="pqrs.priority_color">{{ pqrs.priority_display }}</Badge>
                    </div>
                    <p class="text-lg text-gray-600">{{ pqrs.subject }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="canEdit" :as="Link" :href="route('pqrs.edit', pqrs.id)" variant="outline" class="flex items-center gap-2">
                        <Edit class="h-4 w-4" />
                        Editar
                    </Button>
                    <Button :as="Link" :href="route('pqrs.index')" variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Descripción</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ pqrs.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Attachments -->
                    <Card v-if="pqrs.attachments.length > 0">
                        <CardHeader>
                            <CardTitle>Archivos adjuntos</CardTitle>
                            <CardDescription>{{ pqrs.attachments.length }} archivo(s) adjunto(s)</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="attachment in pqrs.attachments"
                                    :key="attachment.id"
                                    class="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <Paperclip class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ attachment.original_filename }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ attachment.file_size_formatted }} • {{ attachment.type_display }}
                                                • Subido por {{ attachment.uploaded_by.name }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            :as="Link"
                                            :href="`/storage/${attachment.file_path}`"
                                            target="_blank"
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Download class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            v-if="canEdit || canManage"
                                            variant="outline"
                                            size="sm"
                                            @click="deleteAttachment(attachment.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admin Resolution -->
                    <Card v-if="pqrs.resolution">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CheckCircle class="h-5 w-5 text-green-600" />
                                Resolución
                            </CardTitle>
                            <CardDescription>
                                Resuelto por {{ pqrs.resolved_by?.name }} el {{ formatDateTime(pqrs.resolved_at!) }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ pqrs.resolution }}</p>
                        </CardContent>
                    </Card>

                    <!-- Rating -->
                    <Card v-if="pqrs.satisfaction_rating || canRate">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Star class="h-5 w-5 text-yellow-500" />
                                Calificación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="pqrs.satisfaction_rating" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <Star
                                            v-for="(filled, index) in renderStars(pqrs.satisfaction_rating)"
                                            :key="index"
                                            :class="filled ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'"
                                            class="h-5 w-5"
                                        />
                                    </div>
                                    <span class="text-sm text-gray-600">{{ pqrs.satisfaction_rating }} de 5 estrellas</span>
                                </div>
                                <p v-if="pqrs.satisfaction_comments" class="text-gray-700">{{ pqrs.satisfaction_comments }}</p>
                            </div>
                            <div v-else-if="canRate">
                                <div v-if="!showRatingForm">
                                    <Button @click="showRatingForm = true" class="flex items-center gap-2">
                                        <Star class="h-4 w-4" />
                                        Calificar servicio
                                    </Button>
                                </div>
                                <form v-else @submit.prevent="submitRating" class="space-y-4">
                                    <div>
                                        <Label>Calificación</Label>
                                        <div class="flex items-center gap-1 mt-2">
                                            <button
                                                v-for="star in 5"
                                                :key="star"
                                                type="button"
                                                @click="ratingForm.satisfaction_rating = star"
                                                class="transition-colors"
                                            >
                                                <Star
                                                    :class="star <= ratingForm.satisfaction_rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300 hover:text-yellow-300'"
                                                    class="h-6 w-6"
                                                />
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <Label for="satisfaction_comments">Comentarios (opcional)</Label>
                                        <Textarea
                                            id="satisfaction_comments"
                                            v-model="ratingForm.satisfaction_comments"
                                            placeholder="Comparte tu experiencia..."
                                            rows="3"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="submit"
                                            :disabled="ratingForm.processing"
                                            class="flex items-center gap-2"
                                        >
                                            <Star class="h-4 w-4" />
                                            Enviar calificación
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            @click="showRatingForm = false"
                                        >
                                            Cancelar
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Contact Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de contacto</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <User class="h-4 w-4 text-gray-400" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ pqrs.contact_name }}</p>
                                    <p class="text-xs text-gray-500">Nombre de contacto</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Mail class="h-4 w-4 text-gray-400" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ pqrs.contact_email }}</p>
                                    <p class="text-xs text-gray-500">Correo electrónico</p>
                                </div>
                            </div>
                            <div v-if="pqrs.contact_phone" class="flex items-center gap-3">
                                <Phone class="h-4 w-4 text-gray-400" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ pqrs.contact_phone }}</p>
                                    <p class="text-xs text-gray-500">Teléfono</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500">Tipo</p>
                                <p class="text-sm font-medium text-gray-900">{{ pqrs.type_display }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Apartamento</p>
                                <p class="text-sm font-medium text-gray-900">{{ getApartmentDisplay(pqrs.apartment) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Enviado por</p>
                                <p class="text-sm font-medium text-gray-900">{{ pqrs.submitted_by.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Fecha de creación</p>
                                <p class="text-sm font-medium text-gray-900">{{ formatDateTime(pqrs.created_at) }}</p>
                            </div>
                            <div v-if="pqrs.assigned_to">
                                <p class="text-xs text-gray-500">Asignado a</p>
                                <p class="text-sm font-medium text-gray-900">{{ pqrs.assigned_to.name }}</p>
                                <p class="text-xs text-gray-500">{{ formatDateTime(pqrs.assigned_at!) }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admin Actions -->
                    <Card v-if="canManage">
                        <CardHeader>
                            <CardTitle>Acciones administrativas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Assign -->
                            <div v-if="!showAssignForm">
                                <Button
                                    @click="showAssignForm = true"
                                    variant="outline"
                                    class="w-full flex items-center gap-2"
                                >
                                    <Users class="h-4 w-4" />
                                    {{ pqrs.assigned_to ? 'Reasignar' : 'Asignar' }}
                                </Button>
                            </div>
                            <div v-else class="space-y-3">
                                <Select v-model="assignForm.assigned_to">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar administrador" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Sin asignar</SelectItem>
                                        <SelectItem
                                            v-for="admin in administrators"
                                            :key="admin.id"
                                            :value="admin.id"
                                        >
                                            {{ admin.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div class="flex gap-2">
                                    <Button
                                        @click="submitAssign"
                                        :disabled="assignForm.processing"
                                        size="sm"
                                    >
                                        Asignar
                                    </Button>
                                    <Button
                                        @click="showAssignForm = false"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Cancelar
                                    </Button>
                                </div>
                            </div>

                            <Separator />

                            <!-- Resolve -->
                            <div v-if="!showResolveForm">
                                <Button
                                    @click="showResolveForm = true"
                                    variant="outline"
                                    class="w-full flex items-center gap-2"
                                >
                                    <component :is="getStatusIcon(pqrs.status)" class="h-4 w-4" />
                                    Actualizar estado
                                </Button>
                            </div>
                            <div v-else class="space-y-3">
                                <form @submit.prevent="submitResolve" class="space-y-3">
                                    <div>
                                        <Label>Estado</Label>
                                        <Select v-model="resolveForm.status">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="en_proceso">En Proceso</SelectItem>
                                                <SelectItem value="resuelto">Resuelto</SelectItem>
                                                <SelectItem value="cerrado">Cerrado</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div v-if="resolveForm.status === 'resuelto'">
                                        <Label>Resolución</Label>
                                        <Textarea
                                            v-model="resolveForm.resolution"
                                            placeholder="Describe la solución proporcionada..."
                                            rows="3"
                                        />
                                    </div>
                                    <div>
                                        <Label>Notas administrativas</Label>
                                        <Textarea
                                            v-model="resolveForm.admin_notes"
                                            placeholder="Notas internas..."
                                            rows="2"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            type="submit"
                                            :disabled="resolveForm.processing"
                                            size="sm"
                                        >
                                            Actualizar
                                        </Button>
                                        <Button
                                            type="button"
                                            @click="showResolveForm = false"
                                            variant="outline"
                                            size="sm"
                                        >
                                            Cancelar
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admin Notes -->
                    <Card v-if="pqrs.admin_notes && canManage">
                        <CardHeader>
                            <CardTitle>Notas administrativas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ pqrs.admin_notes }}</p>
                        </CardContent>
                    </Card>

                    <!-- Follow-up -->
                    <Card v-if="pqrs.requires_follow_up && canManage">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-orange-500" />
                                Seguimiento requerido
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div v-if="pqrs.follow_up_date">
                                <p class="text-xs text-gray-500">Fecha de seguimiento</p>
                                <p class="text-sm font-medium text-gray-900">{{ formatDate(pqrs.follow_up_date) }}</p>
                            </div>
                            <div v-if="pqrs.follow_up_notes">
                                <p class="text-xs text-gray-500">Notas de seguimiento</p>
                                <p class="text-sm text-gray-700">{{ pqrs.follow_up_notes }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>