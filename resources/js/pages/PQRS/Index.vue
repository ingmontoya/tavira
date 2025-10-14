<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Search, Eye, ExternalLink } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Pqrs {
    id: number;
    ticket_number: string;
    type: string;
    subject: string;
    status: string;
    priority: string;
    submitter_name: string;
    submitter_email: string;
    apartment: {
        number: string;
    } | null;
    created_at: string;
    responded_at: string | null;
}

interface Stats {
    total: number;
    pendiente: number;
    en_proceso: number;
    resuelta: number;
}

interface Props {
    pqrs: {
        data: Pqrs[];
        links: any;
        meta: any;
    };
    stats: Stats;
    filters: {
        status?: string;
        type?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [{ title: 'PQRS', href: route('pqrs.index') }];

const filters = ref({
    status: props.filters.status || 'all',
    type: props.filters.type || 'all',
    search: props.filters.search || '',
});

const typeLabels: Record<string, string> = {
    peticion: 'Petición',
    queja: 'Queja',
    reclamo: 'Reclamo',
    sugerencia: 'Sugerencia',
};

const statusLabels: Record<string, string> = {
    pendiente: 'Pendiente',
    en_revision: 'En Revisión',
    en_proceso: 'En Proceso',
    resuelta: 'Resuelta',
    cerrada: 'Cerrada',
};

const statusColors: Record<string, string> = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    en_revision: 'bg-blue-100 text-blue-800',
    en_proceso: 'bg-purple-100 text-purple-800',
    resuelta: 'bg-green-100 text-green-800',
    cerrada: 'bg-gray-100 text-gray-800',
};

const priorityColors: Record<string, string> = {
    baja: 'bg-gray-100 text-gray-800',
    media: 'bg-blue-100 text-blue-800',
    alta: 'bg-orange-100 text-orange-800',
    urgente: 'bg-red-100 text-red-800',
};

watch(
    filters,
    () => {
        router.get(
            route('pqrs.index'),
            {
                status: filters.value.status !== 'all' ? filters.value.status : undefined,
                type: filters.value.type !== 'all' ? filters.value.type : undefined,
                search: filters.value.search || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    },
    { deep: true },
);
</script>

<template>

    <Head title="Gestión de PQRS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de PQRS</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Administre las peticiones, quejas, reclamos y sugerencias del conjunto
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <a :href="route('pqrs.track')" target="_blank">
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Página de Rastreo
                        </a>
                    </Button>
                    <Button as-child>
                        <a :href="route('pqrs.public.create')" target="_blank">
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Formulario Público
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total PQRS</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Pendientes</CardDescription>
                        <CardTitle class="text-3xl text-yellow-600">
                            {{ stats.pendiente }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>En Proceso</CardDescription>
                        <CardTitle class="text-3xl text-purple-600">
                            {{ stats.en_proceso }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Resueltas</CardDescription>
                        <CardTitle class="text-3xl text-green-600">{{ stats.resuelta }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input v-model="filters.search" type="text"
                                    placeholder="Buscar por ticket, asunto, nombre..." class="pl-10" />
                            </div>
                        </div>
                        <div class="w-full sm:w-48">
                            <Select v-model="filters.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="pendiente">Pendiente</SelectItem>
                                    <SelectItem value="en_revision">En Revisión</SelectItem>
                                    <SelectItem value="en_proceso">En Proceso</SelectItem>
                                    <SelectItem value="resuelta">Resuelta</SelectItem>
                                    <SelectItem value="cerrada">Cerrada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-full sm:w-48">
                            <Select v-model="filters.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem value="peticion">Petición</SelectItem>
                                    <SelectItem value="queja">Queja</SelectItem>
                                    <SelectItem value="reclamo">Reclamo</SelectItem>
                                    <SelectItem value="sugerencia">Sugerencia</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- PQRS Table -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Ticket</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Asunto</TableHead>
                                <TableHead>Solicitante</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Prioridad</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="pqrs.data.length === 0">
                                <TableCell colspan="8" class="h-24 text-center">
                                    No se encontraron PQRS
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="item in pqrs.data" :key="item.id">
                                <TableCell class="font-mono text-sm">
                                    {{ item.ticket_number }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ typeLabels[item.type] }}</Badge>
                                </TableCell>
                                <TableCell class="max-w-xs truncate">
                                    {{ item.subject }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p class="font-medium">{{ item.submitter_name }}</p>
                                        <p v-if="item.apartment" class="text-gray-500">
                                            {{ item.apartment.number }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="statusColors[item.status]">
                                        {{ statusLabels[item.status] }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="priorityColors[item.priority]" variant="outline">
                                        {{ item.priority }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm text-gray-600">
                                    {{ new Date(item.created_at).toLocaleDateString('es-CO') }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="route('pqrs.show', item.id)">
                                        <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
