<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, Calendar, CheckCircle, Clock, Edit, Eye, FileText, Filter, MessageSquare, MoreHorizontal, Plus, Search, Star, Trash2, User } from 'lucide-vue-next';
import { ref } from 'vue';

export interface Pqrs {
    id: number;
    ticket_number: string;
    type: 'peticion' | 'queja' | 'reclamo' | 'sugerencia';
    subject: string;
    description: string;
    priority: 'baja' | 'media' | 'alta' | 'urgente';
    status: 'abierto' | 'en_proceso' | 'resuelto' | 'cerrado';
    contact_name: string;
    contact_email: string;
    contact_phone?: string;
    created_at: string;
    assigned_at?: string;
    resolved_at?: string;
    satisfaction_rating?: number;
    apartment?: {
        id: number;
        number: string;
        tower: string;
        floor: number;
    };
    submitted_by: {
        id: number;
        name: string;
    };
    assigned_to?: {
        id: number;
        name: string;
    };
    attachments: Array<{
        id: number;
        type: 'evidence' | 'document' | 'photo';
        filename: string;
        original_filename: string;
    }>;
    type_display: string;
    priority_display: string;
    status_display: string;
    status_color: string;
    priority_color: string;
}

const props = defineProps<{
    pqrs: {
        data: Pqrs[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    apartments: Array<{
        id: number;
        number: string;
        tower: string;
        floor: number;
    }>;
    administrators: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        search?: string;
        type?: string;
        status?: string;
        priority?: string;
        apartment_id?: string;
        assigned_to?: string;
    };
    canCreate: boolean;
    canManage: boolean;
}>();

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || 'all');
const statusFilter = ref(props.filters.status || 'all');
const priorityFilter = ref(props.filters.priority || 'all');
const apartmentFilter = ref(props.filters.apartment_id || 'all');
const assignedToFilter = ref(props.filters.assigned_to || 'all');

const applyFilters = () => {
    router.get(
        route('pqrs.index'),
        {
            search: search.value || undefined,
            type: typeFilter.value === 'all' ? undefined : typeFilter.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value || undefined,
            priority: priorityFilter.value === 'all' ? undefined : priorityFilter.value || undefined,
            apartment_id: apartmentFilter.value === 'all' ? undefined : apartmentFilter.value || undefined,
            assigned_to: assignedToFilter.value === 'all' ? undefined : assignedToFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    typeFilter.value = 'all';
    statusFilter.value = 'all';
    priorityFilter.value = 'all';
    apartmentFilter.value = 'all';
    assignedToFilter.value = 'all';
    applyFilters();
};

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'peticion':
            return FileText;
        case 'queja':
            return AlertCircle;
        case 'reclamo':
            return MessageSquare;
        case 'sugerencia':
            return MessageSquare;
        default:
            return FileText;
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'abierto':
            return Clock;
        case 'en_proceso':
            return Clock;
        case 'resuelto':
            return CheckCircle;
        case 'cerrado':
            return CheckCircle;
        default:
            return Clock;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getApartmentDisplay = (apartment: any) => {
    if (!apartment) return 'Sin asignar';
    return `${apartment.tower}${apartment.number}`;
};

const breadcrumbs = [{ label: 'PQRS', href: '#', current: true }];
</script>

<template>
    <Head title="PQRS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">PQRS</h1>
                    <p class="text-gray-600">Gestión de Peticiones, Quejas, Reclamos y Sugerencias</p>
                </div>
                <Button v-if="canCreate" :as="Link" :href="route('pqrs.create')" class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Nuevo PQRS
                </Button>
            </div>

            <Card class="p-6">
                <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-6">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            placeholder="Buscar por ticket, asunto..."
                            class="pl-9"
                            @input="applyFilters"
                        />
                    </div>

                    <Select v-model="typeFilter" @update:model-value="applyFilters">
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

                    <Select v-model="statusFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estados</SelectItem>
                            <SelectItem value="abierto">Abierto</SelectItem>
                            <SelectItem value="en_proceso">En Proceso</SelectItem>
                            <SelectItem value="resuelto">Resuelto</SelectItem>
                            <SelectItem value="cerrado">Cerrado</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="priorityFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Prioridad" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las prioridades</SelectItem>
                            <SelectItem value="baja">Baja</SelectItem>
                            <SelectItem value="media">Media</SelectItem>
                            <SelectItem value="alta">Alta</SelectItem>
                            <SelectItem value="urgente">Urgente</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-if="canManage && apartments.length > 0" v-model="apartmentFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Apartamento" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los apartamentos</SelectItem>
                            <SelectItem
                                v-for="apartment in apartments"
                                :key="apartment.id"
                                :value="apartment.id.toString()"
                            >
                                {{ apartment.tower }}{{ apartment.number }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-if="canManage && administrators.length > 0" v-model="assignedToFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Asignado a" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="unassigned">Sin asignar</SelectItem>
                            <SelectItem
                                v-for="admin in administrators"
                                :key="admin.id"
                                :value="admin.id.toString()"
                            >
                                {{ admin.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Filter class="h-4 w-4 text-gray-400" />
                        <span class="text-sm text-gray-600">
                            {{ pqrs.from }}-{{ pqrs.to }} de {{ pqrs.total }} registros
                        </span>
                    </div>
                    <Button variant="outline" size="sm" @click="clearFilters">
                        Limpiar filtros
                    </Button>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-gray-50">
                                <TableHead class="w-32">Ticket</TableHead>
                                <TableHead class="w-24">Tipo</TableHead>
                                <TableHead>Asunto</TableHead>
                                <TableHead class="w-20">Prioridad</TableHead>
                                <TableHead class="w-24">Estado</TableHead>
                                <TableHead v-if="canManage" class="w-32">Apartamento</TableHead>
                                <TableHead class="w-32">Enviado por</TableHead>
                                <TableHead v-if="canManage" class="w-32">Asignado a</TableHead>
                                <TableHead class="w-28">Fecha</TableHead>
                                <TableHead class="w-16">Archivos</TableHead>
                                <TableHead class="w-16">Calificación</TableHead>
                                <TableHead class="w-16"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="pqrs.data.length === 0">
                                <TableCell :colspan="canManage ? 12 : 10" class="text-center text-gray-500 py-8">
                                    No se encontraron registros PQRS
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-for="item in pqrs.data"
                                :key="item.id"
                                class="group hover:bg-gray-50"
                            >
                                <TableCell class="font-mono text-sm">
                                    {{ item.ticket_number }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <component :is="getTypeIcon(item.type)" class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm">{{ item.type_display }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="max-w-xs">
                                    <div class="truncate font-medium">{{ item.subject }}</div>
                                    <div class="text-sm text-gray-500 truncate">{{ item.description }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="item.priority_color" class="text-xs">
                                        {{ item.priority_display }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <component :is="getStatusIcon(item.status)" class="h-4 w-4 text-gray-400" />
                                        <Badge :class="item.status_color" class="text-xs">
                                            {{ item.status_display }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell v-if="canManage">
                                    {{ getApartmentDisplay(item.apartment) }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <User class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm">{{ item.submitted_by.name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell v-if="canManage">
                                    <span v-if="item.assigned_to" class="text-sm">{{ item.assigned_to.name }}</span>
                                    <span v-else class="text-sm text-gray-400">Sin asignar</span>
                                </TableCell>
                                <TableCell class="text-sm text-gray-500">
                                    {{ formatDate(item.created_at) }}
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="item.attachments.length > 0" variant="outline" class="text-xs">
                                        {{ item.attachments.length }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div v-if="item.satisfaction_rating" class="flex items-center gap-1">
                                        <Star class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                                        <span class="text-sm">{{ item.satisfaction_rating }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <DropdownAction>
                                        <template #trigger>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </template>
                                        <template #content>
                                            <div class="py-1">
                                                <Link
                                                    :href="route('pqrs.show', item.id)"
                                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                    Ver detalles
                                                </Link>
                                                <Link
                                                    v-if="item.status === 'abierto' || item.status === 'en_proceso'"
                                                    :href="route('pqrs.edit', item.id)"
                                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                    Editar
                                                </Link>
                                                <Link
                                                    v-if="canManage || (item.status === 'abierto' || item.status === 'en_proceso')"
                                                    :href="route('pqrs.destroy', item.id)"
                                                    method="delete"
                                                    as="button"
                                                    class="flex items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50 w-full text-left"
                                                    preserve-scroll
                                                    :data="{
                                                        confirm: '¿Estás seguro de que deseas eliminar este PQRS?'
                                                    }"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    Eliminar
                                                </Link>
                                            </div>
                                        </template>
                                    </DropdownAction>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="pqrs.prev_page_url || pqrs.next_page_url" class="mt-6 flex items-center justify-center gap-2">
                    <Button
                        v-if="pqrs.prev_page_url"
                        :as="Link"
                        :href="pqrs.prev_page_url"
                        variant="outline"
                        size="sm"
                    >
                        Anterior
                    </Button>
                    <Button
                        v-if="pqrs.next_page_url"
                        :as="Link"
                        :href="pqrs.next_page_url"
                        variant="outline"
                        size="sm"
                    >
                        Siguiente
                    </Button>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
