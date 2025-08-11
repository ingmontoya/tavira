<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, Eye, QrCode, Calendar, Clock, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Visit {
    id: number;
    visitor_name: string;
    visitor_document_number: string;
    visitor_phone: string;
    visit_reason: string;
    valid_from: string;
    valid_until: string;
    qr_code: string;
    status: 'pending' | 'active' | 'used' | 'expired' | 'cancelled';
    entry_time: string | null;
    apartment: {
        id: number;
        number: string;
        tower: string;
    };
    creator: {
        id: number;
        name: string;
    };
}

interface Props {
    visits: {
        data: Visit[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();
const searchForm = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');

const statusConfig = {
    pending: { label: 'Pendiente', variant: 'default' as const, color: 'bg-gray-100 text-gray-800' },
    active: { label: 'Activa', variant: 'default' as const, color: 'bg-blue-100 text-blue-800' },
    used: { label: 'Utilizada', variant: 'default' as const, color: 'bg-green-100 text-green-800' },
    expired: { label: 'Expirada', variant: 'destructive' as const, color: 'bg-red-100 text-red-800' },
    cancelled: { label: 'Cancelada', variant: 'destructive' as const, color: 'bg-red-100 text-red-800' },
};

const search = () => {
    router.get(route('visits.index'), {
        search: searchForm.value,
        status: statusFilter.value === 'all' ? '' : statusFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    searchForm.value = '';
    statusFilter.value = 'all';
    router.get(route('visits.index'));
};

const deleteVisit = (visitId: number) => {
    if (confirm('¿Estás seguro de que deseas cancelar esta visita?')) {
        router.delete(route('visits.destroy', visitId), {
            preserveScroll: true,
        });
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Gestión de Visitas" />
    
    <AppLayout>
        <div class="container mx-auto px-6 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Gestión de Visitas</h1>
                <p class="text-gray-600 mt-2">Administra las visitas y códigos QR de acceso</p>
            </div>

            <!-- Filters and Actions -->
            <Card class="mb-6">
                <CardContent class="pt-6">
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                Buscar visitante o código
                            </label>
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                <Input
                                    id="search"
                                    v-model="searchForm"
                                    placeholder="Nombre, documento o código QR..."
                                    class="pl-10"
                                    @keyup.enter="search"
                                />
                            </div>
                        </div>
                        
                        <div class="min-w-[200px]">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Estado
                            </label>
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="pending">Pendiente</SelectItem>
                                    <SelectItem value="active">Activa</SelectItem>
                                    <SelectItem value="used">Utilizada</SelectItem>
                                    <SelectItem value="expired">Expirada</SelectItem>
                                    <SelectItem value="cancelled">Cancelada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div class="flex gap-2">
                            <Button @click="search" variant="default">
                                <Search class="h-4 w-4 mr-2" />
                                Buscar
                            </Button>
                            <Button @click="clearFilters" variant="outline">
                                Limpiar
                            </Button>
                            <Button @click="router.get(route('visits.create'))" class="bg-primary">
                                <Plus class="h-4 w-4 mr-2" />
                                Nueva Visita
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Visits Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Visitas Registradas
                    </CardTitle>
                    <CardDescription>
                        Lista de todas las visitas y su estado actual
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="props.visits.data.length === 0" class="text-center py-12">
                        <div class="text-gray-500">
                            <QrCode class="h-12 w-12 mx-auto mb-4 opacity-50" />
                            <p class="text-lg font-medium">No hay visitas registradas</p>
                            <p class="text-sm">Comienza creando una nueva visita</p>
                        </div>
                        <Button @click="router.get(route('visits.create'))" class="mt-4">
                            <Plus class="h-4 w-4 mr-2" />
                            Crear Primera Visita
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Visitante</TableHead>
                                    <TableHead>Apartamento</TableHead>
                                    <TableHead>Validez</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Código QR</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="visit in props.visits.data" :key="visit.id" class="hover:bg-gray-50">
                                    <TableCell>
                                        <div class="font-medium">{{ visit.visitor_name }}</div>
                                        <div class="text-sm text-gray-500">{{ visit.visitor_document_number }}</div>
                                        <div v-if="visit.visit_reason" class="text-xs text-gray-400 mt-1">
                                            {{ visit.visit_reason }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="font-medium">{{ visit.apartment.tower }}-{{ visit.apartment.number }}</div>
                                        <div class="text-sm text-gray-500">{{ visit.creator.name }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            <div class="flex items-center gap-1 text-gray-600">
                                                <Clock class="h-3 w-3" />
                                                Desde: {{ formatDate(visit.valid_from) }}
                                            </div>
                                            <div class="flex items-center gap-1 text-gray-600">
                                                <Clock class="h-3 w-3" />
                                                Hasta: {{ formatDate(visit.valid_until) }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="statusConfig[visit.status].color">
                                            {{ statusConfig[visit.status].label }}
                                        </Badge>
                                        <div v-if="visit.entry_time" class="text-xs text-gray-500 mt-1">
                                            Entrada: {{ formatDate(visit.entry_time) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                            {{ visit.qr_code }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex gap-2 justify-end">
                                            <Button
                                                @click="router.get(route('visits.show', visit.id))"
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="visit.status === 'pending'"
                                                @click="deleteVisit(visit.id)"
                                                variant="outline"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.visits.links.length > 3" class="mt-6 flex justify-center">
                        <nav class="flex items-center gap-1">
                            <template v-for="link in props.visits.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-2 text-sm font-medium rounded-md"
                                    :class="link.active 
                                        ? 'bg-primary text-white' 
                                        : 'text-gray-700 hover:bg-gray-100'"
                                >
                                    {{ link.label }}
                                </Link>
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm font-medium text-gray-400"
                                >
                                    {{ link.label }}
                                </span>
                            </template>
                        </nav>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>