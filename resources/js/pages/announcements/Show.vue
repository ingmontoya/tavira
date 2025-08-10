<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    Edit,
    Eye,
    Pin,
    CheckCircle,
    User,
    Copy,
    Users,
    AlertTriangle,
    FileText
} from 'lucide-vue-next';

interface Announcement {
    id: number;
    title: string;
    content: string;
    priority: 'urgent' | 'important' | 'normal';
    type: 'general' | 'administrative' | 'maintenance' | 'emergency';
    status: 'draft' | 'published' | 'archived';
    is_pinned: boolean;
    requires_confirmation: boolean;
    published_at: string | null;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    created_by: any;
    updated_by: any;
    priority_color: string;
    type_color: string;
}

interface ConfirmationStats {
    total_users: number;
    read_count: number;
    confirmed_count: number;
    read_percentage: number;
    confirmed_percentage: number;
}

interface Props {
    announcement: Announcement;
    confirmationStats?: ConfirmationStats;
}

const props = defineProps<Props>();

const priorityLabels = {
    urgent: 'Urgente',
    important: 'Importante',
    normal: 'Normal'
};

const typeLabels = {
    general: 'General',
    administrative: 'Administrativo',
    maintenance: 'Mantenimiento',
    emergency: 'Emergencia'
};

const statusLabels = {
    draft: 'Borrador',
    published: 'Publicado',
    archived: 'Archivado'
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

const formatContent = (content: string) => {
    return content.replace(/\n/g, '<br>');
};

const duplicate = () => {
    router.post(route('announcements.duplicate', props.announcement.id));
};

const breadcrumbs = [
    { title: 'Comunicación', href: '#' },
    { title: 'Anuncios', href: route('announcements.index') },
    { title: props.announcement.title, href: route('announcements.show', props.announcement.id) }
];
</script>

<template>
    <Head :title="announcement.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-semibold text-gray-900">{{ announcement.title }}</h1>
                        <Pin v-if="announcement.is_pinned" class="h-5 w-5 text-yellow-600" />
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <div class="flex items-center gap-1">
                            <User class="h-4 w-4" />
                            {{ announcement.created_by?.name || 'Usuario' }}
                        </div>
                        <div class="flex items-center gap-1">
                            <Calendar class="h-4 w-4" />
                            {{ formatDate(announcement.created_at) }}
                        </div>
                        <div v-if="announcement.published_at" class="flex items-center gap-1">
                            <Eye class="h-4 w-4" />
                            Publicado {{ formatDate(announcement.published_at) }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        @click="duplicate"
                    >
                        <Copy class="mr-2 h-4 w-4" />
                        Duplicar
                    </Button>
                    <Button as-child>
                        <Link :href="route('announcements.edit', announcement.id)">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Status and Metadata -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Estado</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Badge
                            :variant="announcement.status === 'published' ? 'default' : announcement.status === 'draft' ? 'secondary' : 'outline'"
                            class="text-sm"
                        >
                            {{ statusLabels[announcement.status] }}
                        </Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Prioridad</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Badge :variant="announcement.priority_color" class="text-sm">
                            <AlertTriangle v-if="announcement.priority === 'urgent'" class="mr-1 h-3 w-3" />
                            {{ priorityLabels[announcement.priority] }}
                        </Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Tipo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Badge :variant="announcement.type_color" class="text-sm">
                            <FileText class="mr-1 h-3 w-3" />
                            {{ typeLabels[announcement.type] }}
                        </Badge>
                    </CardContent>
                </Card>

                <Card v-if="announcement.expires_at">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Expira</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center text-sm text-gray-600">
                            <Clock class="mr-1 h-3 w-3" />
                            {{ formatDate(announcement.expires_at) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Contenido</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="prose max-w-none" v-html="formatContent(announcement.content)"></div>
                </CardContent>
            </Card>

            <!-- Confirmation Stats -->
            <Card v-if="announcement.requires_confirmation && confirmationStats">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Estadísticas de Confirmación</CardTitle>
                            <CardDescription>
                                Seguimiento de lecturas y confirmaciones de los residentes
                            </CardDescription>
                        </div>
                        <Button as-child variant="outline">
                            <Link :href="route('announcements.confirmations', announcement.id)">
                                <Users class="mr-2 h-4 w-4" />
                                Ver Detalles
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ confirmationStats.total_users }}</div>
                            <div class="text-sm text-gray-600">Total Usuarios</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ confirmationStats.read_count }}</div>
                            <div class="text-sm text-gray-600">Han Leído</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ confirmationStats.confirmed_count }}</div>
                            <div class="text-sm text-gray-600">Han Confirmado</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Porcentaje de Lectura</span>
                                <span>{{ confirmationStats.read_percentage }}%</span>
                            </div>
                            <Progress :value="confirmationStats.read_percentage" class="h-2" />
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Porcentaje de Confirmación</span>
                                <span>{{ confirmationStats.confirmed_percentage }}%</span>
                            </div>
                            <Progress :value="confirmationStats.confirmed_percentage" class="h-2" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Confirmation Required Notice -->
            <Card v-else-if="announcement.requires_confirmation">
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-blue-600">
                        <CheckCircle class="h-5 w-5" />
                        <span class="font-medium">Este anuncio requiere confirmación de los residentes</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle>Información Adicional</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Creado por:</span>
                            <span class="ml-2">{{ announcement.created_by?.name || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Fecha de creación:</span>
                            <span class="ml-2">{{ formatDate(announcement.created_at) }}</span>
                        </div>
                        <div v-if="announcement.updated_by">
                            <span class="font-medium text-gray-700">Última modificación por:</span>
                            <span class="ml-2">{{ announcement.updated_by?.name || 'N/A' }}</span>
                        </div>
                        <div v-if="announcement.updated_at !== announcement.created_at">
                            <span class="font-medium text-gray-700">Última modificación:</span>
                            <span class="ml-2">{{ formatDate(announcement.updated_at) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
