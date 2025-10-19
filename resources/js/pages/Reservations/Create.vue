<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, CalendarDays, CheckCircle, Clock, DollarSign, MapPin } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ReservableAsset {
    id: number;
    name: string;
    description?: string;
    type: string;
    image_url?: string;
    reservation_duration_minutes: number;
    reservation_cost: number;
    advance_booking_days: number;
    max_reservations_per_user: number;
    requires_approval: boolean;
    availability_rules?: any;
    can_user_reserve: boolean;
}

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
}

interface TimeSlot {
    start_time: string;
    end_time: string;
    start_datetime: string;
    end_datetime: string;
    available: boolean;
}

const props = defineProps<{
    assets: ReservableAsset[];
    selectedAsset?: ReservableAsset;
    userApartment?: Apartment;
    apartments: Apartment[];
    isAdmin: boolean;
}>();

const form = useForm({
    reservable_asset_id: props.selectedAsset?.id || null,
    apartment_id: props.userApartment?.id || null,
    start_time: '',
    end_time: '',
    notes: '',
});

const selectedDate = ref('');
const availableSlots = ref<TimeSlot[]>([]);
const selectedSlot = ref<TimeSlot | null>(null);
const isLoadingSlots = ref(false);
const selectedAssetData = ref<ReservableAsset | null>(props.selectedAsset || null);

const selectedAsset = computed(() => {
    if (!form.reservable_asset_id) return null;
    return props.assets.find((asset) => asset.id === Number(form.reservable_asset_id)) || null;
});

const minDate = computed(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
});

const maxDate = computed(() => {
    if (!selectedAsset.value) return '';
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + selectedAsset.value.advance_booking_days);
    return maxDate.toISOString().split('T')[0];
});

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

watch(
    () => form.reservable_asset_id,
    async (newValue) => {
        if (newValue) {
            selectedAssetData.value = props.assets.find((asset) => asset.id === Number(newValue)) || null;
            selectedDate.value = '';
            availableSlots.value = [];
            selectedSlot.value = null;
            form.start_time = '';
            form.end_time = '';
        }
    },
);

watch(selectedDate, async (newDate) => {
    if (newDate && selectedAsset.value) {
        await loadAvailableSlots(newDate);
    }
});

const loadAvailableSlots = async (date: string) => {
    if (!selectedAsset.value) return;

    isLoadingSlots.value = true;
    try {
        const response = await fetch(route('reservations.availability') + `?asset_id=${selectedAsset.value.id}&date=${date}`);
        const data = await response.json();
        availableSlots.value = data.slots || [];
        selectedSlot.value = null;
        form.start_time = '';
        form.end_time = '';
    } catch (error) {
        console.error('Error loading slots:', error);
        availableSlots.value = [];
    } finally {
        isLoadingSlots.value = false;
    }
};

const selectSlot = (slot: TimeSlot) => {
    if (!slot.available) return;

    selectedSlot.value = slot;
    form.start_time = slot.start_datetime;
    form.end_time = slot.end_datetime;
};

const submit = () => {
    form.post(route('reservations.store'), {
        onSuccess: () => {
            router.visit(route('reservations.index'));
        },
    });
};

const goBack = () => {
    router.visit(route('reservations.index'));
};
</script>

<template>
    <Head title="Nueva Reserva" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Nueva Reserva</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Selecciona un activo y horario para crear tu reserva</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Form -->
                    <div class="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información de la Reserva</CardTitle>
                                <CardDescription> Completa los datos necesarios para tu reserva </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <form @submit.prevent="submit">
                                    <!-- Asset Selection -->
                                    <div class="space-y-2">
                                        <Label for="asset">Activo a Reservar *</Label>
                                        <Select v-model="form.reservable_asset_id" required>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un activo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="asset in assets"
                                                    :key="asset.id"
                                                    :value="asset.id.toString()"
                                                    :disabled="!asset.can_user_reserve"
                                                >
                                                    <div class="flex w-full items-center justify-between">
                                                        <span>{{ asset.name }}</span>
                                                        <span v-if="!asset.can_user_reserve" class="text-xs text-red-500"> Límite alcanzado </span>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.reservable_asset_id" class="text-sm text-red-600">
                                            {{ form.errors.reservable_asset_id }}
                                        </div>
                                    </div>

                                    <!-- Apartment Selection (for admins) -->
                                    <div v-if="isAdmin && apartments.length > 0" class="space-y-2">
                                        <Label for="apartment">Apartamento</Label>
                                        <Select v-model="form.apartment_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un apartamento" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                                    {{ apartment.number }} - Torre {{ apartment.tower }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Date Selection -->
                                    <div v-if="selectedAsset" class="space-y-2">
                                        <Label for="date">Fecha de la Reserva *</Label>
                                        <Input v-model="selectedDate" type="date" :min="minDate" :max="maxDate" required />
                                        <p class="text-sm text-gray-500">
                                            Puedes reservar hasta {{ selectedAsset.advance_booking_days }} días de anticipación
                                        </p>
                                    </div>

                                    <!-- Time Slots -->
                                    <div v-if="selectedDate && selectedAsset" class="space-y-2">
                                        <Label>Horarios Disponibles *</Label>
                                        <div v-if="isLoadingSlots" class="flex items-center justify-center p-8">
                                            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                                        </div>
                                        <div v-else-if="availableSlots.length === 0" class="p-8 text-center text-gray-500">
                                            <Clock class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                            <p>No hay horarios disponibles para esta fecha</p>
                                        </div>
                                        <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            <button
                                                v-for="slot in availableSlots"
                                                :key="slot.start_time"
                                                type="button"
                                                @click="selectSlot(slot)"
                                                :disabled="!slot.available"
                                                :class="[
                                                    'rounded-lg border-2 p-3 text-sm font-medium transition-colors',
                                                    slot.available
                                                        ? selectedSlot?.start_time === slot.start_time
                                                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-900 dark:text-blue-300'
                                                            : 'border-gray-200 bg-white text-gray-900 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700'
                                                        : 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500',
                                                ]"
                                            >
                                                <div class="text-center">
                                                    <div>{{ slot.start_time }} - {{ slot.end_time }}</div>
                                                    <div v-if="!slot.available" class="mt-1 text-xs">No disponible</div>
                                                </div>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.start_time" class="text-sm text-red-600">
                                            {{ form.errors.start_time }}
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="space-y-2">
                                        <Label for="notes">Notas (Opcional)</Label>
                                        <Textarea v-model="form.notes" placeholder="Información adicional sobre tu reserva..." rows="3" />
                                        <div v-if="form.errors.notes" class="text-sm text-red-600">
                                            {{ form.errors.notes }}
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="flex justify-end space-x-3 pt-6">
                                        <Button type="button" variant="outline" @click="goBack"> Cancelar </Button>
                                        <Button type="submit" :disabled="form.processing || !selectedSlot" class="inline-flex items-center">
                                            <CalendarDays class="mr-2 h-4 w-4" />
                                            {{ form.processing ? 'Creando...' : 'Crear Reserva' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar with Asset Info -->
                    <div v-if="selectedAsset" class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ selectedAsset.name }}</CardTitle>
                                <CardDescription class="capitalize">
                                    {{ selectedAsset.type.replace('_', ' ') }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="selectedAsset.image_url" class="aspect-video overflow-hidden rounded-lg bg-gray-100">
                                    <img :src="selectedAsset.image_url" :alt="selectedAsset.name" class="h-full w-full object-cover" />
                                </div>

                                <div v-if="selectedAsset.description" class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ selectedAsset.description }}
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center text-sm">
                                        <Clock class="mr-2 h-4 w-4 text-gray-500" />
                                        <span>Duración: {{ formatDuration(selectedAsset.reservation_duration_minutes) }}</span>
                                    </div>

                                    <div class="flex items-center text-sm">
                                        <DollarSign class="mr-2 h-4 w-4 text-gray-500" />
                                        <span>
                                            Costo:
                                            <span class="font-medium">
                                                {{ selectedAsset.reservation_cost > 0 ? formatCurrency(selectedAsset.reservation_cost) : 'Gratis' }}
                                            </span>
                                        </span>
                                    </div>

                                    <div class="flex items-center text-sm">
                                        <CalendarDays class="mr-2 h-4 w-4 text-gray-500" />
                                        <span>Máximo {{ selectedAsset.advance_booking_days }} días anticipación</span>
                                    </div>

                                    <div class="flex items-center text-sm">
                                        <MapPin class="mr-2 h-4 w-4 text-gray-500" />
                                        <span>Máximo {{ selectedAsset.max_reservations_per_user }} reserva(s) activa(s)</span>
                                    </div>
                                </div>

                                <div v-if="selectedAsset.requires_approval" class="mt-4">
                                    <Alert>
                                        <AlertTriangle class="h-4 w-4" />
                                        <AlertDescription>
                                            Este activo requiere aprobación administrativa. Tu reserva quedará pendiente hasta ser aprobada.
                                        </AlertDescription>
                                    </Alert>
                                </div>

                                <div v-else class="mt-4">
                                    <Alert class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/10">
                                        <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                                        <AlertDescription class="text-green-700 dark:text-green-300">
                                            Las reservas para este activo son aprobadas automáticamente.
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Reservation Summary -->
                        <Card v-if="selectedSlot">
                            <CardHeader>
                                <CardTitle class="text-lg">Resumen de Reserva</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Fecha:</span>
                                    <span class="ml-2 font-medium">
                                        {{
                                            new Date(selectedDate).toLocaleDateString('es-CO', {
                                                weekday: 'long',
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })
                                        }}
                                    </span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Horario:</span>
                                    <span class="ml-2 font-medium">{{ selectedSlot.start_time }} - {{ selectedSlot.end_time }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Duración:</span>
                                    <span class="ml-2 font-medium">{{ formatDuration(selectedAsset.reservation_duration_minutes) }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Costo:</span>
                                    <span class="ml-2 font-medium">
                                        {{ selectedAsset.reservation_cost > 0 ? formatCurrency(selectedAsset.reservation_cost) : 'Gratis' }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
