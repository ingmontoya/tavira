<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Building2, CalendarDays, CheckCircle, Clock, DollarSign, Edit, Eye, MapPin, User } from 'lucide-vue-next';

interface ConjuntoConfig {
    id: number;
    name: string;
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
    user: User;
    apartment?: Apartment;
    start_time: string;
    end_time: string;
    status: string;
    status_label: string;
    status_color: string;
    created_at: string;
}

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
    availability_rules?: {
        allowed_days?: number[];
        time_slots?: Array<{ start: string; end: string }>;
    };
    metadata?: any;
    conjunto_config: ConjuntoConfig;
    reservations: Reservation[];
}

interface Stats {
    total_reservations: number;
    active_reservations: number;
    pending_approval: number;
    completed_reservations: number;
}

const props = defineProps<{
    asset: ReservableAsset;
    stats: Stats;
}>();

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

const formatDateTime = (dateTime: string) => {
    return new Date(dateTime).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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

const getDayName = (dayNumber: number) => {
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return days[dayNumber];
};

const assetTypeLabels: Record<string, string> = {
    common_area: 'Área Común',
    amenity: 'Amenidad',
    facility: 'Instalación',
    sports: 'Deportivo',
    recreation: 'Recreativo',
    meeting_room: 'Sala de Reuniones',
    event_space: 'Espacio para Eventos',
};
</script>

<template>
    <Head :title="asset.name" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="mb-4 flex items-center space-x-4">
                        <Button variant="outline" size="sm" @click="router.visit(route('reservable-assets.index'))">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver a Activos
                        </Button>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ asset.name }}
                            </h1>
                            <p class="mt-2 text-sm text-gray-600 capitalize dark:text-gray-400">
                                {{ assetTypeLabels[asset.type] || asset.type.replace('_', ' ') }}
                            </p>
                        </div>

                        <div class="flex items-center space-x-3">
                            <Badge
                                :variant="asset.is_active ? 'default' : 'secondary'"
                                :class="asset.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ''"
                            >
                                {{ asset.is_active ? 'Activo' : 'Inactivo' }}
                            </Badge>

                            <div class="flex space-x-2">
                                <Button size="sm" :href="route('reservations.create', { asset_id: asset.id })" as="link">
                                    <CalendarDays class="mr-1 h-4 w-4" />
                                    Crear Reserva
                                </Button>
                                <Button size="sm" variant="outline" :href="route('reservable-assets.edit', asset.id)" as="link">
                                    <Edit class="mr-1 h-4 w-4" />
                                    Editar
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Asset Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles del Activo</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div v-if="asset.image_path" class="mb-6">
                                    <img :src="`/storage/${asset.image_path}`" :alt="asset.name" class="h-64 w-full rounded-lg object-cover" />
                                </div>

                                <div v-if="asset.description" class="space-y-2">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Descripción</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ asset.description }}
                                    </p>
                                </div>

                                <!-- Configuration -->
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div class="space-y-4">
                                        <div class="flex items-center space-x-3">
                                            <Clock class="h-5 w-5 text-gray-500" />
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Duración</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ formatDuration(asset.reservation_duration_minutes) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <DollarSign class="h-5 w-5 text-gray-500" />
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Costo</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ asset.reservation_cost > 0 ? formatCurrency(asset.reservation_cost) : 'Gratis' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex items-center space-x-3">
                                            <CalendarDays class="h-5 w-5 text-gray-500" />
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Anticipación máxima</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ asset.advance_booking_days }} días</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <MapPin class="h-5 w-5 text-gray-500" />
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Máximo por usuario</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ asset.max_reservations_per_user }} reserva(s) activa(s)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approval Status -->
                                <div
                                    class="flex items-center space-x-3 rounded-lg p-4"
                                    :class="asset.requires_approval ? 'bg-yellow-50 dark:bg-yellow-900/10' : 'bg-green-50 dark:bg-green-900/10'"
                                >
                                    <component
                                        :is="asset.requires_approval ? AlertTriangle : CheckCircle"
                                        :class="
                                            asset.requires_approval ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'
                                        "
                                        class="h-5 w-5"
                                    />
                                    <div>
                                        <p
                                            class="text-sm font-medium"
                                            :class="
                                                asset.requires_approval
                                                    ? 'text-yellow-800 dark:text-yellow-200'
                                                    : 'text-green-800 dark:text-green-200'
                                            "
                                        >
                                            {{ asset.requires_approval ? 'Requiere Aprobación' : 'Aprobación Automática' }}
                                        </p>
                                        <p
                                            class="text-xs"
                                            :class="
                                                asset.requires_approval
                                                    ? 'text-yellow-700 dark:text-yellow-300'
                                                    : 'text-green-700 dark:text-green-300'
                                            "
                                        >
                                            {{
                                                asset.requires_approval
                                                    ? 'Las reservas quedarán pendientes hasta ser aprobadas'
                                                    : 'Las reservas son aprobadas automáticamente'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Availability Rules -->
                        <Card
                            v-if="
                                asset.availability_rules &&
                                (asset.availability_rules.allowed_days?.length || asset.availability_rules.time_slots?.length)
                            "
                        >
                            <CardHeader>
                                <CardTitle>Reglas de Disponibilidad</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Allowed Days -->
                                <div v-if="asset.availability_rules.allowed_days?.length" class="space-y-2">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Días Permitidos</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <Badge v-for="day in asset.availability_rules.allowed_days" :key="day" variant="secondary">
                                            {{ getDayName(day) }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Time Slots -->
                                <div v-if="asset.availability_rules.time_slots?.length" class="space-y-2">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Horarios Permitidos</h4>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div
                                            v-for="(slot, index) in asset.availability_rules.time_slots"
                                            :key="index"
                                            class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            <Clock class="h-4 w-4 text-gray-500" />
                                            <span>{{ slot.start }} - {{ slot.end }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Recent Reservations -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Reservas Recientes</CardTitle>
                                <CardDescription> Últimas reservas realizadas para este activo </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="asset.reservations.length === 0" class="py-8 text-center text-gray-500">
                                    <CalendarDays class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                    <p>No hay reservas registradas para este activo.</p>
                                </div>
                                <div v-else class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Usuario</TableHead>
                                                <TableHead>Apartamento</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="reservation in asset.reservations" :key="reservation.id">
                                                <TableCell>
                                                    <div class="flex items-center space-x-2">
                                                        <User class="h-4 w-4 text-gray-500" />
                                                        <span class="text-sm">{{ reservation.user.name }}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell class="text-sm">
                                                    {{ reservation.apartment?.number || 'N/A' }}
                                                </TableCell>
                                                <TableCell class="text-sm">
                                                    {{ formatDateTime(reservation.start_time) }}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge :class="getStatusBadgeClass(reservation.status_color)" class="text-xs">
                                                        {{ reservation.status_label }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Button variant="ghost" size="sm" :href="route('reservations.show', reservation.id)" as="link">
                                                        <Eye class="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Statistics -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Estadísticas</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-800">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {{ stats.total_reservations }}
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Total Reservas</p>
                                    </div>

                                    <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/10">
                                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                            {{ stats.active_reservations }}
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400">Activas</p>
                                    </div>

                                    <div class="rounded-lg bg-yellow-50 p-3 text-center dark:bg-yellow-900/10">
                                        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                                            {{ stats.pending_approval }}
                                        </p>
                                        <p class="text-xs text-yellow-600 dark:text-yellow-400">Pendientes</p>
                                    </div>

                                    <div class="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-900/10">
                                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                            {{ stats.completed_reservations }}
                                        </p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400">Completadas</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Quick Actions -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Acciones Rápidas</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <Button size="sm" class="w-full justify-start" :href="route('reservations.create', { asset_id: asset.id })" as="link">
                                    <CalendarDays class="mr-2 h-4 w-4" />
                                    Crear Nueva Reserva
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full justify-start"
                                    :href="route('reservable-assets.edit', asset.id)"
                                    as="link"
                                >
                                    <Edit class="mr-2 h-4 w-4" />
                                    Editar Configuración
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full justify-start"
                                    :href="route('reservations.index', { asset_id: asset.id })"
                                    as="link"
                                >
                                    <Eye class="mr-2 h-4 w-4" />
                                    Ver Todas las Reservas
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Asset Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Información del Conjunto</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex items-center space-x-3">
                                    <Building2 class="h-5 w-5 text-gray-500" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ asset.conjunto_config.name }}
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Conjunto residencial</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
