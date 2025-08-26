<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    CalendarDays, 
    Clock, 
    DollarSign, 
    MapPin, 
    User, 
    Building, 
    Edit, 
    Trash2, 
    Check, 
    X, 
    AlertTriangle, 
    ArrowLeft 
} from 'lucide-vue-next';

interface ReservableAsset {
    id: number;
    name: string;
    description?: string;
    type: string;
    image_url?: string;
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
    approved_at?: string;
    approved_by?: User;
    cancelled_at?: string;
    cancelled_by?: User;
    can_be_cancelled: boolean;
    metadata?: any;
    created_at: string;
}

const props = defineProps<{
    reservation: Reservation;
    isAdmin: boolean;
}>();

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
        weekday: 'long',
        year: 'numeric',
        month: 'long',
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

const getDurationFormatted = () => {
    const start = new Date(props.reservation.start_time);
    const end = new Date(props.reservation.end_time);
    const durationMinutes = (end.getTime() - start.getTime()) / (1000 * 60);
    
    const hours = Math.floor(durationMinutes / 60);
    const minutes = durationMinutes % 60;
    
    if (hours > 0 && minutes > 0) {
        return `${hours}h ${minutes}min`;
    } else if (hours > 0) {
        return `${hours}h`;
    } else {
        return `${minutes}min`;
    }
};

const approveReservation = () => {
    router.post(route('reservations.approve', props.reservation.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Handle success
        }
    });
};

const rejectReservation = () => {
    router.post(route('reservations.reject', props.reservation.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Handle success
        }
    });
};

const cancelReservation = () => {
    if (confirm('¿Estás seguro de que deseas cancelar esta reserva?')) {
        router.delete(route('reservations.destroy', props.reservation.id), {
            onSuccess: () => {
                router.visit(route('reservations.index'));
            }
        });
    }
};
</script>

<template>
    <Head :title="`Reserva #${reservation.id}`" />

    <AppLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center space-x-4 mb-4">
                        <Button variant="outline" size="sm" @click="router.visit(route('reservations.index'))">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver a Reservas
                        </Button>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                Reserva #{{ reservation.id }}
                            </h1>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ reservation.reservable_asset.name }}
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <Badge :class="getStatusBadgeClass(reservation.status_color)" class="text-sm px-3 py-1">
                                {{ reservation.status_label }}
                            </Badge>
                            
                            <!-- Actions for admins -->
                            <div v-if="isAdmin && reservation.status === 'pending'" class="flex space-x-2">
                                <Button size="sm" @click="approveReservation" class="bg-green-600 hover:bg-green-700">
                                    <Check class="h-4 w-4 mr-1" />
                                    Aprobar
                                </Button>
                                <Button size="sm" variant="outline" @click="rejectReservation" class="text-red-600 hover:bg-red-50">
                                    <X class="h-4 w-4 mr-1" />
                                    Rechazar
                                </Button>
                            </div>
                            
                            <!-- Actions for users -->
                            <div v-if="!isAdmin && reservation.can_be_cancelled" class="flex space-x-2">
                                <Button 
                                    size="sm" 
                                    variant="outline"
                                    :href="route('reservations.edit', reservation.id)"
                                    as="link"
                                >
                                    <Edit class="h-4 w-4 mr-1" />
                                    Editar
                                </Button>
                                <Button 
                                    size="sm" 
                                    variant="outline" 
                                    @click="cancelReservation"
                                    class="text-red-600 hover:bg-red-50"
                                >
                                    <Trash2 class="h-4 w-4 mr-1" />
                                    Cancelar
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Reservation Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles de la Reserva</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Date and Time -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                            <CalendarDays class="h-4 w-4 mr-2 text-gray-500" />
                                            Fecha y Hora de Inicio
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ formatDateTime(reservation.start_time) }}
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                            <Clock class="h-4 w-4 mr-2 text-gray-500" />
                                            Fecha y Hora de Fin
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ formatDateTime(reservation.end_time) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Duration and Cost -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                            Duración
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ getDurationFormatted() }}
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                            <DollarSign class="h-4 w-4 mr-2 text-gray-500" />
                                            Costo
                                        </h4>
                                        <div class="space-y-1">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ reservation.cost > 0 ? formatCurrency(reservation.cost) : 'Gratis' }}
                                            </p>
                                            <p v-if="reservation.payment_required" class="text-xs text-gray-500 capitalize">
                                                Estado de pago: {{ reservation.payment_status }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Info (for admins) -->
                                <div v-if="isAdmin" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                            <User class="h-4 w-4 mr-2 text-gray-500" />
                                            Usuario
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ reservation.user.name }}
                                        </p>
                                    </div>
                                    
                                    <div v-if="reservation.apartment" class="space-y-3">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                            <Building class="h-4 w-4 mr-2 text-gray-500" />
                                            Apartamento
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ reservation.apartment.number }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div v-if="reservation.notes" class="space-y-3">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                        Notas del Usuario
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                                        {{ reservation.notes }}
                                    </p>
                                </div>

                                <!-- Admin Notes -->
                                <div v-if="reservation.admin_notes" class="space-y-3">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                        Notas Administrativas
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/10 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                                        {{ reservation.admin_notes }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Status History -->
                        <Card v-if="reservation.approved_at || reservation.cancelled_at">
                            <CardHeader>
                                <CardTitle>Historial de Estados</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Creation -->
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <div>
                                        <p class="text-sm font-medium">Reserva creada</p>
                                        <p class="text-xs text-gray-500">{{ formatDateTime(reservation.created_at) }}</p>
                                    </div>
                                </div>

                                <!-- Approval -->
                                <div v-if="reservation.approved_at" class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div>
                                        <p class="text-sm font-medium">Reserva aprobada</p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatDateTime(reservation.approved_at) }}
                                            <span v-if="reservation.approved_by">
                                                por {{ reservation.approved_by.name }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Cancellation -->
                                <div v-if="reservation.cancelled_at" class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                    <div>
                                        <p class="text-sm font-medium">Reserva cancelada</p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatDateTime(reservation.cancelled_at) }}
                                            <span v-if="reservation.cancelled_by">
                                                por {{ reservation.cancelled_by.name }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Asset Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ reservation.reservable_asset.name }}</CardTitle>
                                <CardDescription class="capitalize">
                                    {{ reservation.reservable_asset.type.replace('_', ' ') }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="reservation.reservable_asset.image_url" class="mb-4">
                                    <img 
                                        :src="reservation.reservable_asset.image_url" 
                                        :alt="reservation.reservable_asset.name"
                                        class="w-full h-32 object-cover rounded-lg"
                                    />
                                </div>
                                
                                <div v-if="reservation.reservable_asset.description" class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ reservation.reservable_asset.description }}
                                </div>
                                
                                <div class="mt-4">
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        class="w-full"
                                        :href="route('reservations.create', { asset_id: reservation.reservable_asset.id })"
                                        as="link"
                                    >
                                        <CalendarDays class="h-4 w-4 mr-2" />
                                        Hacer nueva reserva
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Status Alert -->
                        <Alert v-if="reservation.status === 'pending'" class="border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/10">
                            <AlertTriangle class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                            <AlertDescription class="text-yellow-700 dark:text-yellow-300">
                                Esta reserva está pendiente de aprobación administrativa.
                            </AlertDescription>
                        </Alert>

                        <Alert v-else-if="reservation.status === 'approved'" class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/10">
                            <Check class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <AlertDescription class="text-green-700 dark:text-green-300">
                                Tu reserva ha sido aprobada. ¡Disfruta del activo reservado!
                            </AlertDescription>
                        </Alert>

                        <Alert v-else-if="reservation.status === 'rejected'" class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/10">
                            <X class="h-4 w-4 text-red-600 dark:text-red-400" />
                            <AlertDescription class="text-red-700 dark:text-red-300">
                                Esta reserva ha sido rechazada por la administración.
                            </AlertDescription>
                        </Alert>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>