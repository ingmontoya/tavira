<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Building2, FileSpreadsheet, FileText, Users } from 'lucide-vue-next';

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    position_on_floor: number;
    monthly_fee: number;
    payment_status: 'overdue_30' | 'overdue_60' | 'overdue_90' | 'overdue_90_plus';
    payment_status_badge: {
        text: string;
        class: string;
    };
    outstanding_balance: number;
    last_payment_date: string | null;
    apartment_type: {
        id: number;
        name: string;
        area_sqm: number;
        bedrooms: number;
        bathrooms: number;
    };
    residents: Array<{
        id: number;
        first_name: string;
        last_name: string;
        resident_type: string;
        status: string;
    }>;
    full_address: string;
}

defineProps<{
    delinquentApartments: Record<string, Apartment[]>;
    stats: {
        total_delinquent: number;
        overdue_30: number;
        overdue_60: number;
        overdue_90: number;
        overdue_90_plus: number;
    };
    cutoffDate: string;
}>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Nunca';
    return new Date(dateString).toLocaleDateString('es-CO');
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Apartamentos',
        href: '/apartments',
    },
    {
        title: 'Morosos',
        href: '/apartments-delinquent',
    },
];

const exportToExcel = () => {
    window.open('/apartments-delinquent/export-excel', '_blank');
};

const exportToPdf = () => {
    window.open('/apartments-delinquent/export-pdf', '_blank');
};
</script>

<template>
    <Head title="Listado de Morosos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Button @click="router.visit('/apartments')" variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver a Apartamentos
                        </Button>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight">Listado de Morosos</h1>
                    <p class="text-muted-foreground">Apartamentos con pagos pendientes (corte {{ formatDate(cutoffDate) }})</p>
                </div>

                <!-- Export Actions -->
                <div class="flex items-center gap-2">
                    <Button @click="exportToExcel" variant="outline" size="sm">
                        <FileSpreadsheet class="mr-2 h-4 w-4" />
                        Exportar Excel
                    </Button>
                    <Button @click="exportToPdf" variant="outline" size="sm">
                        <FileText class="mr-2 h-4 w-4" />
                        Exportar PDF
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card class="p-4">
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">Total Morosos</div>
                        <div class="text-2xl font-bold text-red-600">{{ stats.total_delinquent }}</div>
                    </div>
                </Card>

                <Card class="p-4">
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">30 días</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.overdue_30 }}</div>
                    </div>
                </Card>

                <Card class="p-4">
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">60 días</div>
                        <div class="text-2xl font-bold text-orange-600">{{ stats.overdue_60 }}</div>
                    </div>
                </Card>

                <Card class="p-4">
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">90 días</div>
                        <div class="text-2xl font-bold text-red-600">{{ stats.overdue_90 }}</div>
                    </div>
                </Card>

                <Card class="p-4">
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">+90 días</div>
                        <div class="text-2xl font-bold text-red-800">{{ stats.overdue_90_plus }}</div>
                    </div>
                </Card>
            </div>

            <!-- Delinquent Apartments by Tower -->
            <div class="space-y-6">
                <div v-for="(apartments, tower) in delinquentApartments" :key="tower">
                    <Card class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Building2 class="h-5 w-5 text-muted-foreground" />
                                <h2 class="text-xl font-semibold">Torre {{ tower }}</h2>
                                <Badge variant="secondary">{{ apartments.length }} morosos</Badge>
                            </div>

                            <div class="grid gap-4">
                                <div
                                    v-for="apartment in apartments"
                                    :key="apartment.id"
                                    class="cursor-pointer rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                    @click="router.visit(`/apartments/${apartment.id}`)"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 space-y-2">
                                            <div class="flex items-center gap-3">
                                                <h3 class="font-medium">{{ apartment.full_address }}</h3>
                                                <Badge :class="apartment.payment_status_badge.class">
                                                    {{ apartment.payment_status_badge.text }}
                                                </Badge>
                                            </div>

                                            <div class="space-y-1 text-sm text-muted-foreground">
                                                <div>
                                                    {{ apartment.apartment_type?.name || 'N/A' }} - {{ formatCurrency(apartment.monthly_fee) }}/mes
                                                </div>
                                                <div v-if="apartment.outstanding_balance > 0">
                                                    Saldo pendiente: {{ formatCurrency(apartment.outstanding_balance) }}
                                                </div>
                                                <div>Último pago: {{ formatDate(apartment.last_payment_date) }}</div>
                                            </div>

                                            <div v-if="apartment.residents.length > 0" class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <Users class="h-4 w-4" />
                                                <span>
                                                    {{ apartment.residents.map((r) => `${r.first_name} ${r.last_name}`).join(', ') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <div v-if="Object.keys(delinquentApartments).length === 0" class="py-12 text-center">
                    <Building2 class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                    <h3 class="text-lg font-medium">No hay apartamentos morosos</h3>
                    <p class="text-muted-foreground">Todos los apartamentos están al día con sus pagos.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
