<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Package, Mail, FileText, MoreHorizontal, Search, Filter, Plus, Eye, Edit, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

export interface Correspondence {
    id: number;
    tracking_number: string;
    sender_name: string;
    sender_company: string | null;
    type: 'package' | 'letter' | 'document' | 'other';
    description: string;
    status: 'received' | 'delivered' | 'pending_signature' | 'returned';
    received_at: string;
    delivered_at: string | null;
    requires_signature: boolean;
    apartment: {
        id: number;
        number: string;
        tower: string;
        floor: number;
    };
    received_by: {
        id: number;
        name: string;
    };
    delivered_by: {
        id: number;
        name: string;
    } | null;
    attachments: Array<{
        id: number;
        type: 'photo_evidence' | 'signature' | 'document';
        filename: string;
    }>;
}

const props = defineProps<{
    correspondences: {
        data: Correspondence[];
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
    filters: {
        search?: string;
        status?: string;
        type?: string;
        apartment_id?: string;
    };
    canCreate: boolean;
    canManage: boolean;
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const typeFilter = ref(props.filters.type || 'all');
const apartmentFilter = ref(props.filters.apartment_id || 'all');

const applyFilters = () => {
    router.get(route('correspondence.index'), {
        search: search.value || undefined,
        status: statusFilter.value === 'all' ? undefined : statusFilter.value || undefined,
        type: typeFilter.value === 'all' ? undefined : typeFilter.value || undefined,
        apartment_id: apartmentFilter.value === 'all' ? undefined : apartmentFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    typeFilter.value = 'all';
    apartmentFilter.value = 'all';
    applyFilters();
};

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'package': return Package;
        case 'letter': return Mail;
        case 'document': return FileText;
        default: return Mail;
    }
};

const getTypeLabel = (type: string) => {
    switch (type) {
        case 'package': return 'Paquete';
        case 'letter': return 'Carta';
        case 'document': return 'Documento';
        case 'other': return 'Otro';
        default: return type;
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'received':
            return { text: 'Recibida', class: 'bg-blue-100 text-blue-800' };
        case 'delivered':
            return { text: 'Entregada', class: 'bg-green-100 text-green-800' };
        case 'pending_signature':
            return { text: 'Pendiente Firma', class: 'bg-yellow-100 text-yellow-800' };
        case 'returned':
            return { text: 'Devuelta', class: 'bg-red-100 text-red-800' };
        default:
            return { text: status, class: 'bg-gray-100 text-gray-800' };
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const deleteCorrespondence = (id: number) => {
    if (confirm('¿Está seguro de que desea eliminar esta correspondencia?')) {
        router.delete(route('correspondence.destroy', id));
    }
};

const navigateToShow = (id: number) => {
    router.visit(route('correspondence.show', id));
};
</script>

<template>
    <Head title="Correspondencia" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Correspondencia</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Gestión de correspondencia y paquetes del conjunto
                    </p>
                </div>

                <Link
                    v-if="canCreate"
                    :href="route('correspondence.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <Plus class="w-4 h-4 mr-2" />
                    Nueva Correspondencia
                </Link>
            </div>

            <!-- Filters -->
            <Card class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <Input
                            v-model="search"
                            placeholder="Buscar por número, remitente..."
                            @keyup.enter="applyFilters"
                        >
                            <template #prefix>
                                <Search class="w-4 h-4 text-gray-400" />
                            </template>
                        </Input>
                    </div>

                    <Select v-model="statusFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Filtrar por estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estados</SelectItem>
                            <SelectItem value="received">Recibida</SelectItem>
                            <SelectItem value="delivered">Entregada</SelectItem>
                            <SelectItem value="pending_signature">Pendiente Firma</SelectItem>
                            <SelectItem value="returned">Devuelta</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="typeFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Filtrar por tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los tipos</SelectItem>
                            <SelectItem value="package">Paquete</SelectItem>
                            <SelectItem value="letter">Carta</SelectItem>
                            <SelectItem value="document">Documento</SelectItem>
                            <SelectItem value="other">Otro</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        v-if="apartments.length > 0"
                        v-model="apartmentFilter"
                        @update:model-value="applyFilters"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Filtrar por apartamento" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los apartamentos</SelectItem>
                            <SelectItem
                                v-for="apartment in apartments"
                                :key="apartment.id"
                                :value="apartment.id.toString()"
                            >
                                {{ apartment.tower }}-{{ apartment.number }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center space-x-2 mt-4">
                    <Button @click="applyFilters" size="sm">
                        <Filter class="w-4 h-4 mr-2" />
                        Aplicar Filtros
                    </Button>
                    <Button @click="clearFilters" variant="outline" size="sm">
                        Limpiar
                    </Button>
                </div>
            </Card>

            <!-- Results -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow class="border-b-2 border-gray-200">
                                <TableHead class="font-semibold text-gray-900 py-4">Número de Seguimiento</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4">Tipo</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4">Remitente</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4">Apartamento</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4">Estado</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4">Fecha Recepción</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4">Evidencia</TableHead>
                                <TableHead class="font-semibold text-gray-900 py-4 text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="correspondence in correspondences.data"
                                :key="correspondence.id"
                                class="hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors"
                                @click="navigateToShow(correspondence.id)"
                            >
                                <TableCell class="font-medium">
                                    {{ correspondence.tracking_number }}
                                </TableCell>

                                <TableCell>
                                    <div class="flex items-center space-x-2">
                                        <component
                                            :is="getTypeIcon(correspondence.type)"
                                            class="w-4 h-4 text-gray-500"
                                        />
                                        <span>{{ getTypeLabel(correspondence.type) }}</span>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div>
                                        <div class="font-medium">{{ correspondence.sender_name }}</div>
                                        <div
                                            v-if="correspondence.sender_company"
                                            class="text-sm text-gray-500"
                                        >
                                            {{ correspondence.sender_company }}
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="font-medium">
                                        {{ correspondence.apartment.tower }}-{{ correspondence.apartment.number }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Piso {{ correspondence.apartment.floor }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge :class="getStatusBadge(correspondence.status).class">
                                        {{ getStatusBadge(correspondence.status).text }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    {{ formatDate(correspondence.received_at) }}
                                </TableCell>

                                <TableCell>
                                    <div class="text-sm">
                                        {{ correspondence.attachments.length }} archivo(s)
                                    </div>
                                </TableCell>

                                <TableCell class="text-right" @click.stop>
                                    <DropdownAction>
                                        <template #trigger>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="w-4 h-4" />
                                            </Button>
                                        </template>

                                        <template #content>
                                            <Link
                                                :href="route('correspondence.show', correspondence.id)"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <Eye class="w-4 h-4 mr-2" />
                                                Ver Detalles
                                            </Link>

                                            <Link
                                                v-if="canManage || canCreate"
                                                :href="route('correspondence.edit', correspondence.id)"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <Edit class="w-4 h-4 mr-2" />
                                                Editar
                                            </Link>

                                            <button
                                                v-if="canManage"
                                                @click="deleteCorrespondence(correspondence.id)"
                                                class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                            >
                                                <Trash2 class="w-4 h-4 mr-2" />
                                                Eliminar
                                            </button>
                                        </template>
                                    </DropdownAction>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando {{ correspondences.from }} a {{ correspondences.to }}
                            de {{ correspondences.total }} resultados
                        </div>

                        <div class="flex space-x-2">
                            <Link
                                v-if="correspondences.prev_page_url"
                                :href="correspondences.prev_page_url"
                                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Anterior
                            </Link>
                            <Link
                                v-if="correspondences.next_page_url"
                                :href="correspondences.next_page_url"
                                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Siguiente
                            </Link>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
