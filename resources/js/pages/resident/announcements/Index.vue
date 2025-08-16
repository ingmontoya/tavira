<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, Bell, Calendar, CheckCircle, Clock, Eye, MessageSquare, Pin, Search, User } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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
    announcements: {
        data: Announcement[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        priority?: string;
        type?: string;
        unread?: boolean;
        unconfirmed?: boolean;
    };
    stats: {
        total: number;
        unread: number;
        requiring_confirmation: number;
    };
}

const props = defineProps<Props>();

const searchTerm = ref(props.filters.search || '');
const selectedPriority = ref(props.filters.priority || '');
const selectedType = ref(props.filters.type || '');
const showUnread = ref(props.filters.unread || false);
const showUnconfirmed = ref(props.filters.unconfirmed || false);

watch(
    [searchTerm, selectedPriority, selectedType, showUnread, showUnconfirmed],
    () => {
        router.get(
            route('resident.announcements.index'),
            {
                search: searchTerm.value,
                priority: selectedPriority.value,
                type: selectedType.value,
                unread: showUnread.value || undefined,
                unconfirmed: showUnconfirmed.value || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    },
    { debounce: 300 },
);

const priorityLabels = {
    urgent: 'Urgente',
    important: 'Importante',
    normal: 'Normal',
};

const typeLabels = {
    general: 'General',
    administrative: 'Administrativo',
    maintenance: 'Mantenimiento',
    emergency: 'Emergencia',
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDateShort = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        month: 'short',
        day: 'numeric',
    });
};

const truncateContent = (content: string, maxLength: number = 120) => {
    if (content.length <= maxLength) return content;
    return content.substring(0, maxLength).trim() + '...';
};

const breadcrumbs = [{ title: 'Anuncios', href: route('resident.announcements.index') }];
</script>

<template>
    <Head title="Announcements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Anuncios</h1>
                    <p class="mt-1 text-sm text-gray-600">Consulta los anuncios de la administración</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Anuncios</CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">No Leídos</CardTitle>
                        <Bell class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.unread }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pendientes Confirmación</CardTitle>
                        <AlertCircle class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ stats.requiring_confirmation }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                            <Input v-model="searchTerm" placeholder="Buscar anuncios..." class="pl-10" />
                        </div>

                        <Select v-model="selectedPriority">
                            <SelectTrigger>
                                <SelectValue placeholder="Prioridad" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="urgent">Urgente</SelectItem>
                                <SelectItem value="important">Importante</SelectItem>
                                <SelectItem value="normal">Normal</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="selectedType">
                            <SelectTrigger>
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="general">General</SelectItem>
                                <SelectItem value="administrative">Administrativo</SelectItem>
                                <SelectItem value="maintenance">Mantenimiento</SelectItem>
                                <SelectItem value="emergency">Emergencia</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button :variant="showUnread ? 'default' : 'outline'" @click="showUnread = !showUnread"> Solo No Leídos </Button>

                        <Button :variant="showUnconfirmed ? 'default' : 'outline'" @click="showUnconfirmed = !showUnconfirmed"> Pendientes </Button>

                        <Button
                            variant="outline"
                            @click="
                                searchTerm = '';
                                selectedPriority = '';
                                selectedType = '';
                                showUnread = false;
                                showUnconfirmed = false;
                            "
                        >
                            Limpiar
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Announcements List -->
            <div class="space-y-4">
                <Card
                    v-for="announcement in announcements.data"
                    :key="announcement.id"
                    :class="{
                        'border-blue-200 bg-blue-50': !announcement.is_read_by_user,
                        'border-orange-200 bg-orange-50': announcement.requires_confirmation && !announcement.is_confirmed_by_user,
                        'border-red-200 bg-red-50': announcement.priority === 'urgent',
                    }"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="mb-2 flex items-center gap-2">
                                    <Pin v-if="announcement.is_pinned" class="h-4 w-4 flex-shrink-0 text-yellow-600" />
                                    <h3 class="truncate text-lg font-semibold text-gray-900">{{ announcement.title }}</h3>
                                    <div v-if="!announcement.is_read_by_user" class="h-2 w-2 flex-shrink-0 rounded-full bg-blue-500"></div>
                                </div>

                                <div class="mb-2 flex items-center gap-4 text-sm text-gray-600">
                                    <Badge :variant="announcement.priority_color" class="text-xs">
                                        <AlertTriangle v-if="announcement.priority === 'urgent'" class="mr-1 h-3 w-3" />
                                        {{ priorityLabels[announcement.priority] }}
                                    </Badge>
                                    <Badge :variant="announcement.type_color" class="text-xs">
                                        {{ typeLabels[announcement.type] }}
                                    </Badge>
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        {{ formatDateShort(announcement.published_at) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <User class="h-3 w-3" />
                                        {{ announcement.created_by?.name || 'Administración' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <div class="flex items-center gap-2">
                                    <CheckCircle
                                        v-if="announcement.requires_confirmation && announcement.is_confirmed_by_user"
                                        class="h-5 w-5 text-green-600"
                                    />
                                    <AlertCircle
                                        v-else-if="announcement.requires_confirmation && !announcement.is_confirmed_by_user"
                                        class="h-5 w-5 text-orange-600"
                                    />
                                    <Eye v-if="announcement.is_read_by_user && !announcement.requires_confirmation" class="h-5 w-5 text-gray-400" />
                                </div>
                                <Button as-child size="sm">
                                    <Link :href="route('resident.announcements.show', announcement.id)"> Ver </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="pt-0">
                        <p class="leading-relaxed text-gray-700">
                            {{ truncateContent(announcement.content) }}
                        </p>

                        <div v-if="announcement.expires_at" class="mt-3 flex items-center gap-1 text-xs text-gray-500">
                            <Clock class="h-3 w-3" />
                            Expira: {{ formatDate(announcement.expires_at) }}
                        </div>

                        <div v-if="announcement.requires_confirmation && !announcement.is_confirmed_by_user" class="mt-3">
                            <Badge variant="outline" class="text-xs">
                                <AlertCircle class="mr-1 h-3 w-3" />
                                Requiere Confirmación
                            </Badge>
                        </div>

                        <div v-if="announcement.requires_confirmation && announcement.is_confirmed_by_user" class="mt-3">
                            <Badge variant="default" class="bg-green-100 text-xs text-green-800">
                                <CheckCircle class="mr-1 h-3 w-3" />
                                Confirmado
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="announcements.data.length === 0">
                    <CardContent class="py-8 text-center text-gray-500">
                        <MessageSquare class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                        <h3 class="mb-2 text-lg font-medium text-gray-900">No hay anuncios</h3>
                        <p>No se encontraron anuncios que coincidan con los filtros seleccionados.</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination would go here if needed -->
            <div v-if="announcements.links" class="flex justify-center">
                <!-- Add pagination component if available -->
            </div>
        </div>
    </AppLayout>
</template>
