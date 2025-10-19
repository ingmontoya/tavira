<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Calendar, Eye, FileText, Search } from 'lucide-vue-next';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    deadline: string | null;
    status: string;
    created_at: string;
    categories: Category[];
    tenant_id: string;
    tenant_name: string;
    has_response: boolean;
    my_response: {
        id: number;
        status: 'pending' | 'accepted' | 'rejected';
        quoted_amount: number;
        created_at: string;
    } | null;
}

interface PaginationData {
    data: QuotationRequest[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

interface Props {
    requests: PaginationData;
    filters: {
        status?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const selectedStatus = ref(props.filters.status || 'all');

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusBadge = (status: string) => {
    const badges = {
        published: { text: 'Publicada', variant: 'default' as const },
        draft: { text: 'Borrador', variant: 'secondary' as const },
        closed: { text: 'Cerrada', variant: 'destructive' as const },
    };
    return badges[status as keyof typeof badges] || badges.published;
};

const getResponseStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', variant: 'secondary' as const, class: 'bg-blue-100 text-blue-800' },
        accepted: { text: 'Aprobada', variant: 'default' as const, class: 'bg-green-100 text-green-800' },
        rejected: { text: 'No seleccionada', variant: 'outline' as const, class: 'bg-gray-100 text-gray-800' },
    };
    return badges[status as keyof typeof badges] || badges.pending;
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const isExpiringSoon = (deadline: string | null): boolean => {
    if (!deadline) return false;
    const daysUntilDeadline = Math.ceil(
        (new Date(deadline).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
    );
    return daysUntilDeadline <= 3 && daysUntilDeadline >= 0;
};

const isExpired = (deadline: string | null): boolean => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
};

const handleStatusChange = (value: string) => {
    selectedStatus.value = value;
    router.get(
        route('provider.quotations.index'),
        { status: value === 'all' ? undefined : value },
        { preserveState: true }
    );
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/provider/dashboard' },
    { title: 'Solicitudes de Cotización', href: '/provider/quotations' },
];

const filteredRequests = props.requests.data.filter((request) => {
    const matchesSearch = request.title
        .toLowerCase()
        .includes(searchQuery.value.toLowerCase());
    return matchesSearch;
});
</script>

<template>
    <AppLayout title="Solicitudes de Cotización" :breadcrumbs="breadcrumbs">
        <Head title="Solicitudes de Cotización" />

        <div class="container mx-auto space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Solicitudes de Cotización</h1>
                    <p class="text-muted-foreground">
                        Revisa y responde a las solicitudes disponibles para tu categoría
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por título..."
                                    class="pl-10"
                                />
                            </div>
                        </div>
                        <Select :model-value="selectedStatus" @update:model-value="handleStatusChange">
                            <SelectTrigger class="w-[200px]">
                                <SelectValue placeholder="Filtrar..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas</SelectItem>
                                <SelectItem value="pending">Sin Responder</SelectItem>
                                <SelectItem value="responded">Ya Respondidas</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Results Summary -->
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Mostrando {{ filteredRequests.length }} de {{ requests.total }} solicitudes
                </p>
            </div>

            <!-- Requests List -->
            <div v-if="filteredRequests.length === 0" class="py-12 text-center">
                <FileText class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">No hay solicitudes disponibles</h3>
                <p class="text-muted-foreground">
                    No se encontraron solicitudes que coincidan con tus criterios de búsqueda.
                </p>
            </div>

            <div v-else class="space-y-4">
                <Card v-for="request in filteredRequests" :key="request.id" class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <CardTitle class="text-xl">{{ request.title }}</CardTitle>
                                    <Badge :variant="getStatusBadge(request.status).variant">
                                        {{ getStatusBadge(request.status).text }}
                                    </Badge>
                                    <Badge v-if="isExpiringSoon(request.deadline)" variant="outline" class="border-yellow-500 text-yellow-700">
                                        ⚠️ Vence pronto
                                    </Badge>
                                    <Badge v-if="isExpired(request.deadline)" variant="destructive">
                                        Expirada
                                    </Badge>
                                    <Badge v-if="request.has_response" :class="getResponseStatusBadge(request.my_response?.status || 'pending').class">
                                        {{ getResponseStatusBadge(request.my_response?.status || 'pending').text }}
                                    </Badge>
                                </div>
                                <CardDescription>
                                    {{ request.tenant_name }}
                                </CardDescription>
                            </div>
                            <Link :href="route('provider.quotations.show', [request.tenant_id, request.id])">
                                <Button variant="outline" size="sm">
                                    <Eye class="mr-2 h-4 w-4" />
                                    Ver detalles
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="mb-4 text-sm text-muted-foreground">
                            {{ request.description }}
                        </p>

                        <!-- Show response info if provider has responded -->
                        <div v-if="request.has_response && request.my_response" class="mb-4 rounded-md bg-blue-50 p-3 border border-blue-200">
                            <p class="text-sm font-medium text-blue-900">Tu propuesta:</p>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-blue-800">
                                <span class="font-semibold">{{ formatCurrency(request.my_response.quoted_amount) }}</span>
                                <span class="text-xs text-blue-600">Enviada el {{ formatDate(request.my_response.created_at) }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                            <div v-if="request.deadline" class="flex items-center gap-1">
                                <Calendar class="h-4 w-4" />
                                <span>Vence: {{ formatDate(request.deadline) }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <FileText class="h-4 w-4" />
                                <span>Categorías: {{ request.categories.map(c => c.name).join(', ') }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div v-if="requests.last_page > 1" class="flex justify-center gap-2">
                <Button
                    v-for="page in requests.last_page"
                    :key="page"
                    :variant="page === requests.current_page ? 'default' : 'outline'"
                    size="sm"
                    @click="router.get(route('provider.quotations.index', { page, status: selectedStatus === 'all' ? undefined : selectedStatus }))"
                >
                    {{ page }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
