<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Search, Users, UserX } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ProviderCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

interface Provider {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    contact_name: string | null;
    document_number: string | null;
    is_active: boolean;
    categories: ProviderCategory[];
    created_at: string;
}

interface PaginatedProviders {
    data: Provider[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    providers: PaginatedProviders;
    categories: ProviderCategory[];
    filters: {
        status?: string;
        search?: string;
        category_id?: string;
    };
    stats: {
        active: number;
        inactive: number;
        total: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Proveedores',
        href: '/admin/providers',
    },
];

// Local filter state
const statusFilter = ref(props.filters.status || 'all');
const searchQuery = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || 'all');

// Debounced search
let searchTimeout: number | undefined;
watch(searchQuery, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        updateFilters();
    }, 300);
});

watch([statusFilter, categoryFilter], () => {
    updateFilters();
});

const updateFilters = () => {
    router.get(
        '/admin/providers',
        {
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            search: searchQuery.value || undefined,
            category_id: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    statusFilter.value = 'all';
    searchQuery.value = '';
    categoryFilter.value = 'all';
    router.get('/admin/providers');
};

const getStatusBadge = (provider: Provider) => {
    if (provider.is_active) {
        return { variant: 'success' as const, label: 'Activo' };
    }
    return { variant: 'secondary' as const, label: 'Inactivo' };
};

const hasActiveFilters = computed(() => {
    return statusFilter.value !== 'all' || searchQuery.value !== '' || categoryFilter.value !== 'all';
});
</script>

<template>
    <Head title="Proveedores - Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Proveedores</h1>
                    <p class="text-sm text-muted-foreground">Administra los proveedores aprobados del sistema</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Proveedores</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                        <p class="text-xs text-muted-foreground">Proveedores registrados</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Activos</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.active }}</div>
                        <p class="text-xs text-muted-foreground">Proveedores activos</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Inactivos</CardTitle>
                        <UserX class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.inactive }}</div>
                        <p class="text-xs text-muted-foreground">Proveedores inactivos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                    <CardDescription>Busca y filtra proveedores</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4 md:flex-row md:items-end">
                        <div class="flex-1 space-y-2">
                            <label class="text-sm font-medium">Buscar</label>
                            <div class="relative">
                                <Search class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input v-model="searchQuery" placeholder="Buscar por nombre, email, contacto..." class="pl-10" />
                            </div>
                        </div>

                        <div class="space-y-2 md:w-48">
                            <label class="text-sm font-medium">Estado</label>
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="active">Activos</SelectItem>
                                    <SelectItem value="inactive">Inactivos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2 md:w-64">
                            <label class="text-sm font-medium">Categoría</label>
                            <Select v-model="categoryFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las categorías</SelectItem>
                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters"> Limpiar Filtros </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Providers Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Lista de Proveedores</CardTitle>
                    <CardDescription> Mostrando {{ providers.data.length }} de {{ providers.total }} proveedores </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Contacto</TableHead>
                                    <TableHead>Categorías</TableHead>
                                    <TableHead>Documento</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="providers.data.length === 0">
                                    <TableCell colspan="6" class="text-center text-muted-foreground"> No se encontraron proveedores </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="provider in providers.data"
                                    :key="provider.id"
                                    class="cursor-pointer hover:bg-muted/50"
                                    @click="router.visit(`/admin/providers/${provider.id}`)"
                                >
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ provider.name }}</div>
                                            <div v-if="provider.email" class="text-sm text-muted-foreground">
                                                {{ provider.email }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="provider.contact_name || provider.phone">
                                            <div v-if="provider.contact_name" class="text-sm">
                                                {{ provider.contact_name }}
                                            </div>
                                            <div v-if="provider.phone" class="text-sm text-muted-foreground">
                                                {{ provider.phone }}
                                            </div>
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="category in provider.categories" :key="category.id" variant="outline" class="text-xs">
                                                {{ category.name }}
                                            </Badge>
                                            <span v-if="provider.categories.length === 0" class="text-sm text-muted-foreground">
                                                Sin categorías
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="provider.document_number" class="text-sm">
                                            {{ provider.document_number }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(provider).variant">
                                            {{ getStatusBadge(provider).label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button variant="ghost" size="sm" as-child @click.stop>
                                            <Link :href="`/admin/providers/${provider.id}`"> Ver Detalles </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="providers.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">Página {{ providers.current_page }} de {{ providers.last_page }}</div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in providers.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                size="sm"
                                @click="link.url && router.visit(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
