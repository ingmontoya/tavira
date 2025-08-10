<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    Pin,
    CheckCircle,
    User,
    AlertTriangle,
    FileText,
    ArrowLeft,
    Check
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Announcement {
    id: number;
    title: string;
    content: string;
    priority: 'urgent' | 'important' | 'normal';
    type: 'general' | 'administrative' | 'maintenance' | 'emergency';
    status: 'published';
    is_pinned: boolean;
    requires_confirmation: boolean;
    published_at: string;
    expires_at: string | null;
    created_at: string;
    created_by: any;
    priority_color: string;
    type_color: string;
    is_read_by_user: boolean;
    is_confirmed_by_user: boolean;
}

interface Props {
    announcement: Announcement;
}

const props = defineProps<Props>();

const confirmForm = useForm({});

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

const confirmAnnouncement = () => {
    confirmForm.post(route('resident.announcements.confirm', props.announcement.id));
};

const goBack = () => {
    router.visit(route('resident.announcements.index'));
};

const breadcrumbs = [
    { title: 'Anuncios', href: route('resident.announcements.index') },
    { title: props.announcement.title, href: route('resident.announcements.show', props.announcement.id) }
];
</script>

<template>
    <Head :title="announcement.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <Button variant="outline" @click="goBack" class="mb-4">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Anuncios
                </Button>

                <div v-if="announcement.requires_confirmation && !announcement.is_confirmed_by_user">
                    <Button @click="confirmAnnouncement" :disabled="confirmForm.processing">
                        <Check class="mr-2 h-4 w-4" />
                        Confirmar Lectura
                    </Button>
                </div>
            </div>

            <!-- Title and Metadata -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <h1 class="text-2xl font-bold text-gray-900">{{ announcement.title }}</h1>
                                <Pin v-if="announcement.is_pinned" class="h-6 w-6 text-yellow-600" />
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                <div class="flex items-center gap-1">
                                    <User class="h-4 w-4" />
                                    {{ announcement.created_by?.name || 'Administración' }}
                                </div>
                                <div class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    {{ formatDate(announcement.published_at) }}
                                </div>
                                <div v-if="announcement.expires_at" class="flex items-center gap-1 text-orange-600">
                                    <Clock class="h-4 w-4" />
                                    Expira: {{ formatDate(announcement.expires_at) }}
                                </div>
                            </div>
                        </div>

                        <!-- Status Indicators -->
                        <div class="flex flex-col items-end gap-2">
                            <div v-if="announcement.requires_confirmation && announcement.is_confirmed_by_user" class="flex items-center gap-1 text-green-600 text-sm">
                                <CheckCircle class="h-4 w-4" />
                                Confirmado
                            </div>
                            <div v-else-if="announcement.requires_confirmation && !announcement.is_confirmed_by_user" class="flex items-center gap-1 text-orange-600 text-sm">
                                <AlertTriangle class="h-4 w-4" />
                                Pendiente Confirmación
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Priority and Type Badges -->
            <div class="flex items-center gap-3">
                <Badge
                    :variant="announcement.priority_color"
                    class="text-sm px-3 py-1"
                >
                    <AlertTriangle v-if="announcement.priority === 'urgent'" class="mr-1 h-4 w-4" />
                    {{ priorityLabels[announcement.priority] }}
                </Badge>

                <Badge
                    :variant="announcement.type_color"
                    class="text-sm px-3 py-1"
                >
                    <FileText class="mr-1 h-4 w-4" />
                    {{ typeLabels[announcement.type] }}
                </Badge>
            </div>

            <!-- Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Contenido del Anuncio</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="prose max-w-none text-gray-700 leading-relaxed"
                        v-html="formatContent(announcement.content)"
                    ></div>
                </CardContent>
            </Card>

            <!-- Confirmation Section -->
            <Card v-if="announcement.requires_confirmation">
                <CardHeader>
                    <CardTitle>Confirmación de Lectura</CardTitle>
                    <CardDescription>
                        Este anuncio requiere que confirmes que lo has leído y comprendido.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="announcement.is_confirmed_by_user" class="flex items-center gap-2 text-green-600">
                        <CheckCircle class="h-5 w-5" />
                        <span class="font-medium">Ya has confirmado la lectura de este anuncio</span>
                    </div>

                    <div v-else class="space-y-3">
                        <div class="flex items-center gap-2 text-orange-600">
                            <AlertTriangle class="h-5 w-5" />
                            <span class="font-medium">Tu confirmación está pendiente</span>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <p class="text-sm text-amber-800">
                                Al hacer clic en "Confirmar Lectura", estarás indicando que has leído y comprendido el contenido de este anuncio.
                            </p>
                        </div>

                        <Button
                            @click="confirmAnnouncement"
                            :disabled="confirmForm.processing"
                            class="w-full sm:w-auto"
                        >
                            <Check class="mr-2 h-4 w-4" />
                            {{ confirmForm.processing ? 'Confirmando...' : 'Confirmar Lectura' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Important Notice for Urgent Messages -->
            <Card v-if="announcement.priority === 'urgent'" class="border-red-200 bg-red-50">
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-red-800">
                        <AlertTriangle class="h-5 w-5" />
                        <span class="font-medium">Este es un anuncio urgente que requiere tu atención inmediata.</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Action Buttons (Mobile) -->
            <div class="flex flex-col sm:flex-row gap-3 sm:justify-between">
                <Button variant="outline" @click="goBack" class="w-full sm:w-auto">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Anuncios
                </Button>

                <Button
                    v-if="announcement.requires_confirmation && !announcement.is_confirmed_by_user"
                    @click="confirmAnnouncement"
                    :disabled="confirmForm.processing"
                    class="w-full sm:w-auto"
                >
                    <Check class="mr-2 h-4 w-4" />
                    {{ confirmForm.processing ? 'Confirmando...' : 'Confirmar Lectura' }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
