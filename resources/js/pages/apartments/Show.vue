<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Building, DollarSign, Edit, Eye, FileText, Home, MapPin, Settings, UserPlus, Users } from 'lucide-vue-next';

interface ApartmentType {
    id: number;
    name: string;
    area_sqm: number;
    bedrooms: number;
    bathrooms: number;
    has_balcony: boolean;
    has_laundry_room: boolean;
    has_maid_room: boolean;
    coefficient: number;
    administration_fee: number;
}

interface ConjuntoConfig {
    id: number;
    name: string;
}

interface Resident {
    id: number;
    first_name: string;
    last_name: string;
    document_type: string;
    document_number: string;
    email: string;
    phone?: string;
    mobile_phone?: string;
    resident_type: 'Owner' | 'Tenant' | 'Family';
    status: 'Active' | 'Inactive';
    start_date: string;
    end_date?: string;
    full_name: string;
}

interface PaymentAgreementInstallment {
    id: number;
    installment_number: number;
    amount: number;
    due_date: string;
    paid_amount: number;
    penalty_amount: number;
    payment_method?: string;
    payment_date?: string;
    status: 'pending' | 'paid' | 'overdue' | 'partial';
}

interface PaymentAgreement {
    id: number;
    status: 'draft' | 'pending_approval' | 'approved' | 'active' | 'breached' | 'completed' | 'cancelled';
    total_debt_amount: number;
    initial_payment: number;
    monthly_payment: number;
    installments: number;
    start_date: string;
    end_date: string;
    penalty_rate: number;
    terms_and_conditions?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
    status_badge: {
        text: string;
        class: string;
    };
    progress_percentage: number;
    remaining_balance: number;
    overdue_installments_count: number;
    next_due_date?: string;
    installments_data?: PaymentAgreementInstallment[];
}

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    position_on_floor: number;
    status: 'Available' | 'Occupied' | 'Maintenance' | 'Reserved';
    monthly_fee: number;
    utilities?: Record<string, boolean>;
    features?: Record<string, boolean>;
    notes?: string;
    apartment_type: ApartmentType;
    conjunto_config: ConjuntoConfig;
    residents: Resident[];
    full_address: string;
    created_at: string;
    updated_at: string;
}

interface Statistics {
    total_residents: number;
    active_residents: number;
    owners: number;
    tenants: number;
}

const props = defineProps<{
    apartment: Apartment;
    statistics: Statistics;
    paymentAgreements: PaymentAgreement[];
}>();

const formatDate = (date: string | null) => {
    if (!date) return null;
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateTime: string | null) => {
    if (!dateTime) return null;
    return new Date(dateTime).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const getStatusLabel = (status: string) => {
    const labels = {
        Available: 'Disponible',
        Occupied: 'Ocupado',
        Maintenance: 'Mantenimiento',
        Reserved: 'Reservado',
    };
    return labels[status] || status;
};

const getStatusColor = (status: string) => {
    const colors = {
        Available: 'bg-green-100 text-green-800',
        Occupied: 'bg-blue-100 text-blue-800',
        Maintenance: 'bg-yellow-100 text-yellow-800',
        Reserved: 'bg-purple-100 text-purple-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getResidentTypeLabel = (type: string) => {
    const labels = {
        Owner: 'Propietario',
        Tenant: 'Arrendatario',
        Family: 'Familiar',
    };
    return labels[type] || type;
};

const getResidentTypeColor = (type: string) => {
    const colors = {
        Owner: 'bg-green-100 text-green-800',
        Tenant: 'bg-blue-100 text-blue-800',
        Family: 'bg-yellow-100 text-yellow-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const getInitials = (first_name: string, last_name: string) => {
    return `${first_name.charAt(0)}${last_name.charAt(0)}`.toUpperCase();
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Apartamentos', href: '/apartments' },
    { title: props.apartment.full_address, href: `/apartments/${props.apartment.id}` },
];
</script>

<template>
    <Head :title="apartment.full_address" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ apartment.full_address }}
                    </h1>
                    <div class="flex items-center gap-3">
                        <Badge :class="getStatusColor(apartment.status)">
                            {{ getStatusLabel(apartment.status) }}
                        </Badge>
                        <Badge variant="outline">
                            {{ apartment.apartment_type.name }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/apartments">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link :href="`/apartments/${apartment.id}/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Información del Apartamento -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Home class="h-5 w-5" />
                            Información del Apartamento
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex items-center gap-3">
                                <Building class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Conjunto</p>
                                    <p class="text-base">{{ apartment.conjunto_config.name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <MapPin class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Ubicación</p>
                                    <p class="text-base">Torre {{ apartment.tower }} - Piso {{ apartment.floor }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Home class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Número</p>
                                    <p class="text-base font-medium">{{ apartment.number }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Settings class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Posición en piso</p>
                                    <p class="text-base">{{ apartment.position_on_floor }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <DollarSign class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Cuota mensual</p>
                                    <p class="text-base font-medium">{{ formatCurrency(apartment.monthly_fee) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="apartment.notes">
                            <Separator class="my-4" />
                            <div class="flex items-start gap-3">
                                <FileText class="mt-1 h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Notas</p>
                                    <p class="text-base whitespace-pre-line">{{ apartment.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Características del Tipo -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building class="h-5 w-5" />
                            Características del Tipo
                        </CardTitle>
                        <CardDescription>
                            {{ apartment.apartment_type.name }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg bg-muted/50 p-4 text-center">
                                <p class="text-2xl font-bold text-primary">{{ apartment.apartment_type.area_sqm }}</p>
                                <p class="text-sm text-muted-foreground">m² área</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 p-4 text-center">
                                <p class="text-2xl font-bold text-primary">{{ apartment.apartment_type.bedrooms }}</p>
                                <p class="text-sm text-muted-foreground">habitaciones</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 p-4 text-center">
                                <p class="text-2xl font-bold text-primary">{{ apartment.apartment_type.bathrooms }}</p>
                                <p class="text-sm text-muted-foreground">baños</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 p-4 text-center">
                                <p class="text-2xl font-bold text-primary">{{ (apartment.apartment_type.coefficient * 100).toFixed(2) }}%</p>
                                <p class="text-sm text-muted-foreground">coeficiente</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-4">
                            <div>
                                <p class="mb-3 text-sm font-medium text-muted-foreground">Características</p>
                                <div class="grid grid-cols-1 gap-2">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.apartment_type.has_balcony ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Balcón</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.apartment_type.has_laundry_room ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Zona de lavado</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.apartment_type.has_maid_room ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Cuarto de servicio</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="apartment.utilities">
                                <p class="mb-3 text-sm font-medium text-muted-foreground">Servicios Públicos</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full" :class="apartment.utilities.water ? 'bg-green-500' : 'bg-gray-300'"></div>
                                        <span class="text-sm">Agua</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.utilities.electricity ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Electricidad</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full" :class="apartment.utilities.gas ? 'bg-green-500' : 'bg-gray-300'"></div>
                                        <span class="text-sm">Gas</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.utilities.internet ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Internet</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="apartment.features">
                                <p class="mb-3 text-sm font-medium text-muted-foreground">Características Adicionales</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full" :class="apartment.features.parking ? 'bg-green-500' : 'bg-gray-300'"></div>
                                        <span class="text-sm">Parqueadero</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full" :class="apartment.features.storage ? 'bg-green-500' : 'bg-gray-300'"></div>
                                        <span class="text-sm">Depósito</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.features.pets_allowed ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Mascotas permitidas</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-2 w-2 rounded-full"
                                            :class="apartment.features.furnished ? 'bg-green-500' : 'bg-gray-300'"
                                        ></div>
                                        <span class="text-sm">Amoblado</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Residentes -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Residentes
                            </CardTitle>
                            <Link :href="`/residents/create?apartment_id=${apartment.id}`">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <UserPlus class="h-4 w-4" />
                                    Agregar Residente
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="apartment.residents && apartment.residents.length > 0" class="space-y-4">
                            <div
                                v-for="resident in apartment.residents"
                                :key="resident.id"
                                class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                        <span class="text-sm font-medium text-primary">{{
                                            getInitials(resident.first_name, resident.last_name)
                                        }}</span>
                                    </div>
                                    <div>
                                        <h4 class="font-medium">{{ resident.full_name }}</h4>
                                        <p class="text-sm text-muted-foreground">{{ resident.email }}</p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <Badge :class="getResidentTypeColor(resident.resident_type)" class="text-xs">
                                                {{ getResidentTypeLabel(resident.resident_type) }}
                                            </Badge>
                                            <Badge
                                                :class="resident.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="text-xs"
                                            >
                                                {{ resident.status === 'Active' ? 'Activo' : 'Inactivo' }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link :href="`/residents/${resident.id}`">
                                        <Button variant="outline" size="sm" class="gap-2">
                                            <Eye class="h-4 w-4" />
                                            Ver
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                <Users class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <h3 class="mb-2 text-lg font-medium">No hay residentes</h3>
                            <p class="mb-4 text-muted-foreground">Este apartamento no tiene residentes asignados</p>
                            <Link :href="`/residents/create?apartment_id=${apartment.id}`">
                                <Button class="gap-2">
                                    <UserPlus class="h-4 w-4" />
                                    Agregar Primer Residente
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Acuerdos de Pago -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5" />
                                Acuerdos de Pago
                            </CardTitle>
                            <Link :href="`/payment-agreements/create?apartment_id=${apartment.id}`" v-if="apartment.status === 'Occupied'">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <FileText class="h-4 w-4" />
                                    Crear Acuerdo
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="paymentAgreements && paymentAgreements.length > 0" class="space-y-4">
                            <div
                                v-for="agreement in paymentAgreements"
                                :key="agreement.id"
                                class="rounded-lg border p-4 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <Badge :class="agreement.status_badge.class" class="text-xs">
                                                {{ agreement.status_badge.text }}
                                            </Badge>
                                            <span class="text-sm text-muted-foreground"> Acuerdo #{{ agreement.id }} </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <p class="font-medium">Deuda Total</p>
                                                <p class="text-muted-foreground">{{ formatCurrency(agreement.total_debt_amount) }}</p>
                                            </div>
                                            <div>
                                                <p class="font-medium">Saldo Restante</p>
                                                <p class="text-muted-foreground">{{ formatCurrency(agreement.remaining_balance) }}</p>
                                            </div>
                                            <div>
                                                <p class="font-medium">Cuotas</p>
                                                <p class="text-muted-foreground">
                                                    {{ agreement.installments }} cuotas de {{ formatCurrency(agreement.monthly_payment) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="font-medium">Progreso</p>
                                                <p class="text-muted-foreground">{{ agreement.progress_percentage }}% completado</p>
                                            </div>
                                        </div>
                                        <div v-if="agreement.next_due_date" class="text-sm">
                                            <p class="font-medium">Próximo Vencimiento</p>
                                            <p class="text-muted-foreground">{{ formatDate(agreement.next_due_date) }}</p>
                                        </div>
                                        <div v-if="agreement.overdue_installments_count > 0" class="text-sm">
                                            <p class="font-medium text-red-600">Cuotas Vencidas</p>
                                            <p class="text-red-600">{{ agreement.overdue_installments_count }} cuotas</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Link :href="`/payment-agreements/${agreement.id}`">
                                            <Button variant="outline" size="sm" class="gap-2">
                                                <Eye class="h-4 w-4" />
                                                Ver
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                <DollarSign class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <h3 class="mb-2 text-lg font-medium">No hay acuerdos de pago</h3>
                            <p class="mb-4 text-muted-foreground">Este apartamento no tiene acuerdos de pago activos</p>
                            <Link :href="`/payment-agreements/create?apartment_id=${apartment.id}`" v-if="apartment.status === 'Occupied'">
                                <Button class="gap-2">
                                    <FileText class="h-4 w-4" />
                                    Crear Primer Acuerdo
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Estadísticas -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Estadísticas y Detalles
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-blue-50 p-4 text-center">
                                <p class="text-2xl font-bold text-blue-600">{{ statistics.total_residents }}</p>
                                <p class="text-sm text-muted-foreground">Total Residentes</p>
                            </div>
                            <div class="rounded-lg bg-green-50 p-4 text-center">
                                <p class="text-2xl font-bold text-green-600">{{ statistics.active_residents }}</p>
                                <p class="text-sm text-muted-foreground">Activos</p>
                            </div>
                            <div class="rounded-lg bg-purple-50 p-4 text-center">
                                <p class="text-2xl font-bold text-purple-600">{{ statistics.owners }}</p>
                                <p class="text-sm text-muted-foreground">Propietarios</p>
                            </div>
                            <div class="rounded-lg bg-orange-50 p-4 text-center">
                                <p class="text-2xl font-bold text-orange-600">{{ statistics.tenants }}</p>
                                <p class="text-sm text-muted-foreground">Arrendatarios</p>
                            </div>
                        </div>

                        <Separator class="my-4" />

                        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Fecha de creación:</span>
                                <span>{{ formatDateTime(apartment.created_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Última actualización:</span>
                                <span>{{ formatDateTime(apartment.updated_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Tarifa de administración:</span>
                                <span class="font-medium">{{ formatCurrency(apartment.apartment_type.administration_fee) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">ID del apartamento:</span>
                                <span class="font-mono">{{ apartment.id }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
