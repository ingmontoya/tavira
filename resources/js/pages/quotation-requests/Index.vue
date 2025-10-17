<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Eye, FileText, Pencil, Plus, Search, Send, X, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    deadline: string | null;
    requirements: string | null;
    status: 'draft' | 'published' | 'closed';
    created_by: number;
    published_at: string | null;
    closed_at: string | null;
    created_at: string;
    updated_at: string;
    createdBy: { id: number; name: string };
    categories: Array<{ id: number; name: string }>;
    responses: Array<any>;
}

interface Stats {
    draft: number;
    published: number;
    closed: number;
    total: number;
}

const props = defineProps<{
    quotationRequests: {
        data: QuotationRequest[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        status?: string;
        search?: string;
    };
    stats: Stats;
}>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Solicitudes de Cotización',
        href: '/quotation-requests',
    },
];

// Filter state
const filterStatus = ref(props.filters?.status || 'all');
const searchQuery = ref(props.filters?.search || '');

// Methods
const applyFilters = () => {
    router.get(
        '/quotation-requests',
        {
            status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    filterStatus.value = 'all';
    searchQuery.value = '';
    router.get('/quotation-requests');
};

const hasActiveFilters = computed(() => {
    return filterStatus.value !== 'all' || searchQuery.value !== '';
});

const publishRequest = (id: number) => {
    if (!confirm('¿Está seguro de publicar esta solicitud? Se notificará a los proveedores.')) return;

    router.post(`/quotation-requests/${id}/publish`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const closeRequest = (id: number) => {
    if (!confirm('¿Está seguro de cerrar esta solicitud? No se aceptarán más cotizaciones.')) return;

    router.post(`/quotation-requests/${id}/close`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteRequest = (id: number) => {
    if (!confirm('¿Está seguro de eliminar esta solicitud? Esta acción no se puede deshacer.')) return;

    router.delete(`/quotation-requests/${id}`, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusBadge = (status: string) => {
    const badges = {
        draft: { text: 'Borrador', class: 'bg-gray-100 text-gray-800' },
        published: { text: 'Publicado', class: 'bg-blue-100 text-blue-800' },
        closed: { text: 'Cerrado', class: 'bg-green-100 text-green-800' },
    };
    return badges[status] || badges.draft;
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Solicitudes de Cotización" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Solicitudes de Cotización</h1>
                    <p class="text-sm text-muted-foreground">Gestiona las solicitudes de cotización a proveedores</p>
                </div>
                <Button @click="router.visit('/quotation-requests/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Solicitud
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Borradores</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-gray-600">{{ stats.draft }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Publicados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.published }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Cerrados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.closed }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                    <CardDescription>Busca y filtra las solicitudes de cotización</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Búsqueda</Label>
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por título o descripción..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <Select v-model="filterStatus" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="draft">Borradores</SelectItem>
                                    <SelectItem value="published">Publicados</SelectItem>
                                    <SelectItem value="closed">Cerrados</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <Button @click="applyFilters" class="flex-1">Aplicar Filtros</Button>
                            <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table -->
            <Card>
                <CardContent class="p-0">
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Título</TableHead>
                                    <TableHead>Categorías</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha Límite</TableHead>
                                    <TableHead>Respuestas</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="quotationRequests.data.length > 0">
                                    <TableRow v-for="request in quotationRequests.data" :key="request.id">
                                        <TableCell class="font-medium">{{ request.title }}</TableCell>
                                        <TableCell>
                                            <div class="flex flex-wrap gap-1">
                                                <Badge
                                                    v-for="category in request.categories"
                                                    :key="category.id"
                                                    variant="outline"
                                                    class="text-xs"
                                                >
                                                    {{ category.name }}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusBadge(request.status).class">
                                                {{ getStatusBadge(request.status).text }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ formatDate(request.deadline) }}</TableCell>
                                        <TableCell>{{ request.responses.length }}</TableCell>
                                        <TableCell>{{ formatDate(request.created_at) }}</TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex justify-end space-x-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    @click="router.visit(`/quotation-requests/${request.id}`)"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="request.status === 'draft'"
                                                    size="sm"
                                                    variant="outline"
                                                    @click="router.visit(`/quotation-requests/${request.id}/edit`)"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="request.status === 'draft'"
                                                    size="sm"
                                                    variant="default"
                                                    @click="publishRequest(request.id)"
                                                >
                                                    <Send class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="request.status === 'published'"
                                                    size="sm"
                                                    variant="default"
                                                    @click="closeRequest(request.id)"
                                                >
                                                    <FileText class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="request.status === 'draft'"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="deleteRequest(request.id)"
                                                >
                                                    <XCircle class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                        No se encontraron solicitudes
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="quotationRequests.total > 0" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Mostrando {{ quotationRequests.from }} a {{ quotationRequests.to }} de {{ quotationRequests.total }} resultados
                </div>
                <div class="space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!quotationRequests.prev_page_url"
                        @click="router.visit(quotationRequests.prev_page_url)"
                    >
                        Anterior
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!quotationRequests.next_page_url"
                        @click="router.visit(quotationRequests.next_page_url)"
                    >
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
