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
import { Building2, CalendarDays, Clock, DollarSign, Edit, Eye, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface ReservableAsset {
    id: number;
    name: string;
    description?: string;
    type: string;
    image_path?: string;
    reservation_duration_minutes: number;
    reservation_cost: number;
    advance_booking_days: number;
    max_reservations_per_user: number;
    requires_approval: boolean;
    is_active: boolean;
    reservations_count: number;
    active_reservations_count: number;
    created_at: string;
}

const props = defineProps<{
    assets: {
        data: ReservableAsset[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    assetTypes: Record<string, string>;
    filters?: {
        search?: string;
        type?: string;
        is_active?: boolean;
    };
}>();

const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || 'all');
const activeFilter = ref(props.filters?.is_active?.toString() || 'all');

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (selectedType.value && selectedType.value !== 'all') params.type = selectedType.value;
    if (activeFilter.value !== 'all') params.is_active = activeFilter.value;

    router.get(route('reservable-assets.index'), params, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedType.value = 'all';
    activeFilter.value = 'all';
    applyFilters();
};

const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0 && mins > 0) {
        return `${hours}h ${mins}min`;
    } else if (hours > 0) {
        return `${hours}h`;
    } else {
        return `${mins}min`;
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
    }).format(amount);
};

const deleteAsset = (assetId: number, assetName: string) => {
    if (confirm(`¿Estás seguro de que deseas eliminar el activo "${assetName}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('reservable-assets.destroy', assetId), {
            preserveScroll: true,
            onSuccess: () => {
                // Handle success
            },
        });
    }
};
</script>

<template>

    <Head title="Activos Reservables" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Activos Reservables</h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Gestiona los activos que los residentes
                            pueden reservar</p>
                    </div>
                    <div class="flex space-x-3">
                        <Button @click="() => router.visit(route('reservable-assets.create'))"
                            class="inline-flex items-center">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Activo
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <Card class="mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Buscar
                            </label>
                            <div class="relative">
                                <Search
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                <Input v-model="search" placeholder="Buscar activos..." class="pl-10"
                                    @keydown.enter="applyFilters" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Tipo
                            </label>
                            <Select v-model="selectedType">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem v-for="(label, value) in assetTypes" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Estado
                            </label>
                            <Select v-model="activeFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="1">Activos</SelectItem>
                                    <SelectItem value="0">Inactivos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between">
                        <Button @click="applyFilters" class="inline-flex items-center">
                            <Search class="mr-2 h-4 w-4" />
                            Filtrar
                        </Button>
                        <Button variant="outline" @click="clearFilters"> Limpiar filtros </Button>
                    </div>
                </div>
            </Card>

            <!-- Assets Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Activo</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Duración</TableHead>
                                <TableHead>Costo</TableHead>
                                <TableHead>Reservas</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="assets.data.length === 0">
                                <TableCell colspan="7" class="py-8 text-center text-gray-500">
                                    <Building2 class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                    <p>No hay activos que coincidan con los filtros.</p>
                                    <Link :href="route('reservable-assets.create')"
                                        class="mt-2 inline-block text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    Crear el primer activo reservable
                                    </Link>
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="asset in assets.data" :key="asset.id">
                                <TableCell>
                                    <div class="flex items-center space-x-3">
                                        <div v-if="asset.image_path" class="flex-shrink-0">
                                            <img :src="`/storage/${asset.image_path}`" :alt="asset.name"
                                                class="h-10 w-10 rounded-lg object-cover" />
                                        </div>
                                        <div v-else class="flex-shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                                                <Building2 class="h-5 w-5 text-gray-500" />
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ asset.name }}</p>
                                            <p v-if="asset.description"
                                                class="max-w-xs truncate text-sm text-gray-500 dark:text-gray-400">
                                                {{ asset.description }}
                                            </p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="capitalize">
                                        {{ assetTypes[asset.type] || asset.type.replace('_', ' ') }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center text-sm">
                                        <Clock class="mr-1 h-4 w-4 text-gray-500" />
                                        {{ formatDuration(asset.reservation_duration_minutes) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center text-sm">
                                        <DollarSign class="mr-1 h-4 w-4 text-gray-500" />
                                        {{ asset.reservation_cost > 0 ? formatCurrency(asset.reservation_cost) :
                                        'Gratis' }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p class="font-medium">{{ asset.active_reservations_count }} activas</p>
                                        <p class="text-gray-500">{{ asset.reservations_count }} total</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center space-x-2">
                                        <Badge :variant="asset.is_active ? 'default' : 'secondary'"
                                            :class="asset.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ''">
                                            {{ asset.is_active ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                        <Badge v-if="asset.requires_approval" variant="outline" class="text-xs">
                                            Requiere aprobación </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <DropdownAction>
                                        <Link :href="route('reservable-assets.show', asset.id)"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                        <Eye class="mr-2 h-4 w-4" />
                                        Ver detalles
                                        </Link>

                                        <Link :href="route('reservations.create', { asset_id: asset.id })"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                        <CalendarDays class="mr-2 h-4 w-4" />
                                        Crear reserva
                                        </Link>

                                        <Link :href="route('reservable-assets.edit', asset.id)"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Editar
                                        </Link>

                                        <button @click="deleteAsset(asset.id, asset.name)"
                                            class="flex w-full items-center px-4 py-2 text-left text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-600">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Eliminar
                                        </button>
                                    </DropdownAction>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="assets.total > 0" class="border-t px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Mostrando {{ assets.from }} a {{ assets.to }} de {{ assets.total }} activos
                        </div>
                        <div class="flex space-x-2">
                            <Button v-if="assets.prev_page_url" variant="outline" size="sm"
                                @click="router.visit(assets.prev_page_url)">
                                Anterior
                            </Button>
                            <Button v-if="assets.next_page_url" variant="outline" size="sm"
                                @click="router.visit(assets.next_page_url)">
                                Siguiente
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
