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
import { CalendarDays, Check, Edit, Eye, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface ReservableAsset {
    id: number;
    name: string;
    type: string;
}

interface User {
    id: number;
    name: string;
}

interface Apartment {
    id: number;
    number: string;
}

interface Reservation {
    id: number;
    reservable_asset: ReservableAsset;
    user: User;
    apartment?: Apartment;
    start_time: string;
    end_time: string;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled' | 'completed';
    status_label: string;
    status_color: string;
    cost: number;
    payment_required: boolean;
    payment_status: string;
    notes?: string;
    admin_notes?: string;
    can_be_cancelled: boolean;
    approved_by?: User;
    cancelled_by?: User;
    created_at: string;
}

const props = defineProps<{
    reservations: {
        data: Reservation[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    assets: Array<{
        id: number;
        name: string;
    }>;
    statuses: Record<string, string>;
    filters?: {
        search?: string;
        status?: string;
        asset_id?: string;
        date_from?: string;
        date_to?: string;
    };
    isAdmin: boolean;
}>();

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || 'all');
const selectedAsset = ref(props.filters?.asset_id || 'all');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (selectedStatus.value && selectedStatus.value !== 'all') params.status = selectedStatus.value;
    if (selectedAsset.value && selectedAsset.value !== 'all') params.asset_id = selectedAsset.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get(route('reservations.index'), params, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = 'all';
    selectedAsset.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const getStatusBadgeClass = (color: string) => {
    const colorMap: Record<string, string> = {
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    };
    return colorMap[color] || colorMap.gray;
};

const formatDateTime = (dateTime: string) => {
    return new Date(dateTime).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
    }).format(amount);
};

const approveReservation = (reservationId: number) => {
    router.post(
        route('reservations.approve', reservationId),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Handle success
            },
        },
    );
};

const rejectReservation = (reservationId: number) => {
    router.post(
        route('reservations.reject', reservationId),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Handle success
            },
        },
    );
};

const cancelReservation = (reservationId: number) => {
    if (confirm('¿Estás seguro de que deseas cancelar esta reserva?')) {
        router.delete(route('reservations.destroy', reservationId), {
            preserveScroll: true,
            onSuccess: () => {
                // Handle success
            },
        });
    }
};
</script>

<template>

    <Head title="Reservas" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ isAdmin ? 'Gestión de Reservas' : 'Mis Reservas' }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ isAdmin ? 'Administra todas las reservas del conjunto' :
                                'Consulta y gestiona tus reservas' }}
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <Button @click="() => router.visit(route('reservations.create'))"
                            class="inline-flex items-center">
                            <Plus class="mr-2 h-4 w-4" />
                            Nueva Reserva
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <Card class="mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Buscar
                            </label>
                            <div class="relative">
                                <Search
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                <Input v-model="search" placeholder="Buscar reservas..." class="pl-10"
                                    @keydown.enter="applyFilters" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Estado
                            </label>
                            <Select v-model="selectedStatus">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem v-for="(label, value) in statuses" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Activo
                            </label>
                            <Select v-model="selectedAsset">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los activos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los activos</SelectItem>
                                    <SelectItem v-for="asset in assets" :key="asset.id" :value="asset.id.toString()">
                                        {{ asset.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Desde
                            </label>
                            <Input v-model="dateFrom" type="date" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Hasta
                            </label>
                            <Input v-model="dateTo" type="date" />
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

            <!-- Reservations Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Activo</TableHead>
                                <TableHead v-if="isAdmin">Usuario</TableHead>
                                <TableHead v-if="isAdmin">Apartamento</TableHead>
                                <TableHead>Fecha y Hora</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Costo</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="reservations.data.length === 0">
                                <TableCell :colspan="isAdmin ? 7 : 5" class="py-8 text-center text-gray-500">
                                    <CalendarDays class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                    <p>No hay reservas que coincidan con los filtros.</p>
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="reservation in reservations.data" :key="reservation.id">
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ reservation.reservable_asset.name }}</p>
                                        <p class="text-sm text-gray-500 capitalize">{{
                                            reservation.reservable_asset.type.replace('_', ' ') }}</p>
                                    </div>
                                </TableCell>
                                <TableCell v-if="isAdmin">
                                    {{ reservation.user.name }}
                                </TableCell>
                                <TableCell v-if="isAdmin">
                                    {{ reservation.apartment?.number || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p class="font-medium">{{ formatDateTime(reservation.start_time) }}</p>
                                        <p class="text-gray-500">{{ formatDateTime(reservation.end_time) }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getStatusBadgeClass(reservation.status_color)">
                                        {{ reservation.status_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p class="font-medium">{{ formatCurrency(reservation.cost) }}</p>
                                        <p v-if="reservation.payment_required" class="text-gray-500 capitalize">
                                            {{ reservation.payment_status }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <DropdownAction>
                                        <Link :href="route('reservations.show', reservation.id)"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                        <Eye class="mr-2 h-4 w-4" />
                                        Ver detalles
                                        </Link>

                                        <Link v-if="reservation.can_be_cancelled && !isAdmin"
                                            :href="route('reservations.edit', reservation.id)"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Editar
                                        </Link>

                                        <button v-if="isAdmin && reservation.status === 'pending'"
                                            @click="approveReservation(reservation.id)"
                                            class="flex w-full items-center px-4 py-2 text-left text-sm text-green-700 hover:bg-green-50 dark:text-green-300 dark:hover:bg-green-600">
                                            <Check class="mr-2 h-4 w-4" />
                                            Aprobar
                                        </button>

                                        <button v-if="isAdmin && reservation.status === 'pending'"
                                            @click="rejectReservation(reservation.id)"
                                            class="flex w-full items-center px-4 py-2 text-left text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-600">
                                            <X class="mr-2 h-4 w-4" />
                                            Rechazar
                                        </button>

                                        <button v-if="reservation.can_be_cancelled"
                                            @click="cancelReservation(reservation.id)"
                                            class="flex w-full items-center px-4 py-2 text-left text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-600">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Cancelar
                                        </button>
                                    </DropdownAction>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="reservations.total > 0" class="border-t px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Mostrando {{ reservations.from }} a {{ reservations.to }} de {{ reservations.total }}
                            reservas
                        </div>
                        <div class="flex space-x-2">
                            <Button v-if="reservations.prev_page_url" variant="outline" size="sm"
                                @click="router.visit(reservations.prev_page_url)">
                                Anterior
                            </Button>
                            <Button v-if="reservations.next_page_url" variant="outline" size="sm"
                                @click="router.visit(reservations.next_page_url)">
                                Siguiente
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
