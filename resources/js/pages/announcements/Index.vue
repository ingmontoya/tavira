<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MessageSquare, Plus, Search, Eye, Edit, Trash2, Copy, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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
    created_by: number;
    created_at: string;
    updated_at: string;
    created_by_name?: string;
    priority_color: string;
    type_color: string;
}

interface Props {
    announcements: {
        data: Announcement[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        priority?: string;
        type?: string;
        pinned?: boolean;
    };
    stats: {
        total: number;
        published: number;
        draft: number;
        urgent: number;
    };
}

const props = defineProps<Props>();

const searchTerm = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPriority = ref(props.filters.priority || '');
const selectedType = ref(props.filters.type || '');
const showPinned = ref(props.filters.pinned || false);

watch([searchTerm, selectedStatus, selectedPriority, selectedType, showPinned], () => {
    router.get(route('announcements.index'), {
        search: searchTerm.value,
        status: selectedStatus.value,
        priority: selectedPriority.value,
        type: selectedType.value,
        pinned: showPinned.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

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
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const handleDelete = (announcement: Announcement) => {
    if (confirm('¿Estás seguro de que deseas eliminar este anuncio?')) {
        router.delete(route('announcements.destroy', announcement.id));
    }
};

const duplicate = (announcement: Announcement) => {
    router.post(route('announcements.duplicate', announcement.id));
};

const getDropdownActions = (announcement: Announcement) => [
    {
        label: 'Ver',
        icon: Eye,
        href: route('announcements.show', announcement.id),
    },
    {
        label: 'Editar',
        icon: Edit,
        href: route('announcements.edit', announcement.id),
    },
    {
        label: 'Duplicar',
        icon: Copy,
        action: () => duplicate(announcement),
    },
    ...(announcement.requires_confirmation ? [{
        label: 'Ver Confirmaciones',
        icon: Users,
        href: route('announcements.confirmations', announcement.id),
    }] : []),
    {
        label: 'Eliminar',
        icon: Trash2,
        action: () => handleDelete(announcement),
        destructive: true,
    },
];

const breadcrumbs = [
    { title: 'Comunicación', href: '#' },
    { title: 'Anuncios', href: route('announcements.index') }
];
</script>

<template>
    <Head title="Anuncios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Anuncios</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Gestiona los anuncios para los residentes
                    </p>
                </div>
                <Button as-child>
                    <Link :href="route('announcements.create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Anuncio
                    </Link>
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total</CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Publicados</CardTitle>
                        <Eye class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.published }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Borradores</CardTitle>
                        <Edit class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.draft }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Urgentes</CardTitle>
                        <MessageSquare class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.urgent }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <Input
                                v-model="searchTerm"
                                placeholder="Buscar anuncios..."
                                class="pl-10"
                            />
                        </div>

                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Borrador</SelectItem>
                                <SelectItem value="published">Publicado</SelectItem>
                                <SelectItem value="archived">Archivado</SelectItem>
                            </SelectContent>
                        </Select>

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

                        <Button
                            :variant="showPinned ? 'default' : 'outline'"
                            @click="showPinned = !showPinned"
                        >
                            Solo Fijados
                        </Button>

                        <Button
                            variant="outline"
                            @click="searchTerm = ''; selectedStatus = ''; selectedPriority = ''; selectedType = ''; showPinned = false"
                        >
                            Limpiar
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Table -->
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Título</TableHead>
                            <TableHead>Prioridad</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Publicado</TableHead>
                            <TableHead>Creado por</TableHead>
                            <TableHead class="w-[100px]">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="announcement in announcements.data"
                            :key="announcement.id"
                            class="cursor-pointer hover:bg-gray-50"
                            @click="router.visit(route('announcements.show', announcement.id))"
                        >
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <div v-if="announcement.is_pinned" class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                    <div>
                                        <div class="font-medium">{{ announcement.title }}</div>
                                        <div class="text-sm text-gray-500 line-clamp-1">
                                            {{ announcement.content.substring(0, 100) }}...
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="announcement.priority_color">
                                    {{ priorityLabels[announcement.priority] }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="announcement.type_color">
                                    {{ typeLabels[announcement.type] }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="announcement.status === 'published' ? 'default' : announcement.status === 'draft' ? 'secondary' : 'outline'">
                                    {{ statusLabels[announcement.status] }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {{ announcement.published_at ? formatDate(announcement.published_at) : '-' }}
                            </TableCell>
                            <TableCell>
                                {{ announcement.created_by_name || 'N/A' }}
                            </TableCell>
                            <TableCell @click.stop>
                                <DropdownAction :actions="getDropdownActions(announcement)" />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="announcements.data.length === 0">
                            <TableCell :colspan="7" class="text-center py-8 text-gray-500">
                                No se encontraron anuncios
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination would go here if needed -->
                <div v-if="announcements.links" class="p-4 border-t">
                    <!-- Add pagination component if available -->
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
