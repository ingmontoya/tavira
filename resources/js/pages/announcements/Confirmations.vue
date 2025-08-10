<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import {
    CheckCircle2,
    Clock,
    Eye,
    User,
    Users,
    ArrowLeft,
    Calendar,
    XCircle
} from 'lucide-vue-next';

interface Announcement {
    id: number;
    title: string;
    content: string;
    priority: 'urgent' | 'important' | 'normal';
    type: 'general' | 'administrative' | 'maintenance' | 'emergency';
    status: 'draft' | 'published' | 'archived';
    requires_confirmation: boolean;
    published_at: string | null;
    created_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Confirmation {
    id: number;
    user: User;
    read_at: string | null;
    confirmed_at: string | null;
    status: 'pending' | 'read' | 'confirmed';
}

interface PaginatedConfirmations {
    data: Confirmation[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    announcement: Announcement;
    confirmations: PaginatedConfirmations;
}

const props = defineProps<Props>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusBadge = (confirmation: Confirmation) => {
    if (confirmation.confirmed_at) {
        return { variant: 'default', text: 'Confirmado', icon: CheckCircle2 };
    } else if (confirmation.read_at) {
        return { variant: 'secondary', text: 'Leído', icon: Eye };
    } else {
        return { variant: 'outline', text: 'Pendiente', icon: Clock };
    }
};

const calculateStats = () => {
    const total = props.confirmations.total;
    const confirmed = props.confirmations.data.filter(c => c.confirmed_at).length;
    const read = props.confirmations.data.filter(c => c.read_at).length;
    
    return {
        total,
        confirmed,
        read,
        pending: total - read,
        confirmedPercentage: total > 0 ? Math.round((confirmed / total) * 100) : 0,
        readPercentage: total > 0 ? Math.round((read / total) * 100) : 0
    };
};

const stats = calculateStats();

const breadcrumbs = [
    { title: 'Comunicación', href: '#' },
    { title: 'Anuncios', href: route('announcements.index') },
    { title: props.announcement.title, href: route('announcements.show', props.announcement.id) },
    { title: 'Confirmaciones', href: route('announcements.confirmations', props.announcement.id) }
];
</script>

<template>
    <Head :title="`Confirmaciones - ${announcement.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('announcements.show', announcement.id)">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Volver al anuncio
                            </Link>
                        </Button>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900">Confirmaciones de Lectura</h1>
                    <p class="text-gray-600 mt-1">{{ announcement.title }}</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            Total Usuarios
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <Eye class="h-4 w-4 text-blue-600" />
                            Han Leído
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.read }}</div>
                        <div class="text-sm text-gray-600">{{ stats.readPercentage }}%</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <CheckCircle2 class="h-4 w-4 text-green-600" />
                            Han Confirmado
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.confirmed }}</div>
                        <div class="text-sm text-gray-600">{{ stats.confirmedPercentage }}%</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <Clock class="h-4 w-4 text-orange-600" />
                            Pendientes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ stats.pending }}</div>
                        <div class="text-sm text-gray-600">
                            {{ stats.total > 0 ? Math.round((stats.pending / stats.total) * 100) : 0 }}%
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Progress Bars -->
            <Card>
                <CardHeader>
                    <CardTitle>Progreso General</CardTitle>
                    <CardDescription>Estado de lectura y confirmación del anuncio</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-medium">Porcentaje de Lectura</span>
                            <span class="text-blue-600 font-semibold">{{ stats.readPercentage }}%</span>
                        </div>
                        <Progress :value="stats.readPercentage" class="h-2" />
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-medium">Porcentaje de Confirmación</span>
                            <span class="text-green-600 font-semibold">{{ stats.confirmedPercentage }}%</span>
                        </div>
                        <Progress :value="stats.confirmedPercentage" class="h-2" />
                    </div>
                </CardContent>
            </Card>

            <!-- Confirmations List -->
            <Card>
                <CardHeader>
                    <CardTitle>Detalle por Usuario</CardTitle>
                    <CardDescription>
                        Mostrando {{ confirmations.from }}-{{ confirmations.to }} de {{ confirmations.total }} usuarios
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div 
                            v-for="confirmation in confirmations.data" 
                            :key="confirmation.id"
                            class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                        <User class="h-5 w-5 text-gray-600" />
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ confirmation.user.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ confirmation.user.email }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <div v-if="confirmation.confirmed_at" class="text-sm">
                                        <div class="text-green-600 font-medium">Confirmado</div>
                                        <div class="text-gray-500 flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(confirmation.confirmed_at) }}
                                        </div>
                                    </div>
                                    <div v-else-if="confirmation.read_at" class="text-sm">
                                        <div class="text-blue-600 font-medium">Leído</div>
                                        <div class="text-gray-500 flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(confirmation.read_at) }}
                                        </div>
                                    </div>
                                    <div v-else class="text-sm">
                                        <div class="text-orange-600 font-medium">Pendiente</div>
                                        <div class="text-gray-500">Sin lectura</div>
                                    </div>
                                </div>
                                <Badge 
                                    :variant="getStatusBadge(confirmation).variant"
                                    class="flex items-center gap-1"
                                >
                                    <component :is="getStatusBadge(confirmation).icon" class="h-3 w-3" />
                                    {{ getStatusBadge(confirmation).text }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div 
                            v-if="confirmations.data.length === 0"
                            class="text-center py-12"
                        >
                            <XCircle class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay confirmaciones</h3>
                            <p class="text-gray-600">Aún no hay usuarios que hayan interactuado con este anuncio.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="confirmations.last_page > 1" class="flex justify-center">
                <div class="flex items-center gap-2">
                    <Button 
                        variant="outline" 
                        size="sm"
                        :disabled="confirmations.current_page === 1"
                        as-child
                    >
                        <Link 
                            :href="route('announcements.confirmations', { 
                                announcement: announcement.id, 
                                page: confirmations.current_page - 1 
                            })"
                        >
                            Anterior
                        </Link>
                    </Button>
                    
                    <span class="text-sm text-gray-600 px-4">
                        Página {{ confirmations.current_page }} de {{ confirmations.last_page }}
                    </span>
                    
                    <Button 
                        variant="outline" 
                        size="sm"
                        :disabled="confirmations.current_page === confirmations.last_page"
                        as-child
                    >
                        <Link 
                            :href="route('announcements.confirmations', { 
                                announcement: announcement.id, 
                                page: confirmations.current_page + 1 
                            })"
                        >
                            Siguiente
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>