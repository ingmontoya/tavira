<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { ConjuntoConfig, ApartmentType, Apartment } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3, Building, DollarSign, Edit, FileText, Home, RefreshCw, Settings, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Statistics {
    total_apartments: number;
    occupied_apartments: number;
    available_apartments: number;
    maintenance_apartments: number;
    estimated_monthly_income: number;
    real_monthly_income: number;
}

const props = defineProps<{
    conjunto: ConjuntoConfig;
    apartmentsByTower: Record<string, Record<number, Apartment[]>>;
    statistics: Statistics;
}>();

// Computed properties
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// UI State
const isGeneratingApartments = ref(false);

const generateApartments = () => {
    isGeneratingApartments.value = true;

    router.post(
        '/conjunto-config/generate-apartments',
        {},
        {
            onFinish: () => {
                isGeneratingApartments.value = false;
            },
        },
    );
};

const canGenerateApartments = computed(() => {
    return (
        props.conjunto.apartment_types.length > 0 &&
        props.conjunto.number_of_towers > 0 &&
        props.conjunto.floors_per_tower > 0 &&
        props.conjunto.apartments_per_floor > 0
    );
});

const estimatedApartments = computed(() => {
    let totalPerTower = 0;
    
    // Calculate apartments per tower considering special configurations
    for (let floor = 1; floor <= props.conjunto.floors_per_tower; floor++) {
        const floorConfig = props.conjunto.configuration_metadata?.floor_configuration?.[floor];
        if (floorConfig?.apartments_count !== undefined) {
            totalPerTower += floorConfig.apartments_count;
        } else if (floor === props.conjunto.floors_per_tower && props.conjunto.configuration_metadata?.penthouse_configuration) {
            totalPerTower += props.conjunto.configuration_metadata.penthouse_configuration.apartments_count;
        } else {
            totalPerTower += props.conjunto.apartments_per_floor;
        }
    }
    
    return props.conjunto.number_of_towers * totalPerTower;
});

const getStatusColor = (status: string) => {
    const colors = {
        Available: 'bg-green-100 text-green-800',
        Occupied: 'bg-blue-100 text-blue-800',
        Maintenance: 'bg-yellow-100 text-yellow-800',
        Reserved: 'bg-purple-100 text-purple-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
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

const getMetadataKeyLabel = (key: string) => {
    const labels = {
        architect: 'Arquitecto',
        construction_year: 'Año de Construcción',
        common_areas: 'Áreas Comunes',
        security_features: 'Características de Seguridad',
        address: 'Dirección',
        phone: 'Teléfono',
        email: 'Correo Electrónico',
    };
    return labels[key] || key;
};

const occupancyRate = computed(() => {
    if (props.statistics.total_apartments === 0) return 0;
    return Math.round((props.statistics.occupied_apartments / props.statistics.total_apartments) * 100);
});

const estimatedMonthlyIncome = computed(() => {
    return props.statistics.estimated_monthly_income || 0;
});

const realMonthlyIncome = computed(() => {
    return props.statistics.real_monthly_income || 0;
});

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Configuración de Conjuntos', href: '/conjunto-config' },
    { title: props.conjunto.name, href: `/conjunto-config/${props.conjunto.id}` },
];
</script>

<template>
    <Head :title="conjunto.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ conjunto.name }}</h1>
                        <Badge :variant="conjunto.is_active ? 'default' : 'secondary'">
                            {{ conjunto.is_active ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">
                        {{ conjunto.description }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/conjunto-config">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link :href="`/conjunto-config/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button :disabled="!canGenerateApartments || isGeneratingApartments" variant="outline" class="gap-2">
                                <RefreshCw :class="['h-4 w-4', { 'animate-spin': isGeneratingApartments }]" />
                                {{ isGeneratingApartments ? 'Generando...' : 'Generar Apartamentos' }}
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>¿Generar apartamentos?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Se generarán <strong>{{ estimatedApartments }} apartamentos</strong> basados en la configuración actual ({{
                                        conjunto.number_of_towers
                                    }}
                                    torres × {{ conjunto.floors_per_tower }} pisos × {{ conjunto.apartments_per_floor }} apartamentos). <br /><br />
                                    <span class="font-medium text-destructive">⚠️ Esta acción eliminará todos los apartamentos existentes.</span>
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                <AlertDialogAction
                                    @click="generateApartments"
                                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                >
                                    Generar Apartamentos
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-blue-100 p-2">
                                <Home class="h-4 w-4 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold">
                                    {{ statistics.total_apartments }}
                                    <span v-if="statistics.total_apartments === 0 && canGenerateApartments" class="text-sm text-muted-foreground">
                                        / {{ estimatedApartments }}
                                    </span>
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ statistics.total_apartments === 0 ? 'Apartamentos (Estimados)' : 'Total Apartamentos' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-green-100 p-2">
                                <Users class="h-4 w-4 text-green-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold">{{ occupancyRate }}%</p>
                                <p class="text-sm text-muted-foreground">Ocupación</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-yellow-100 p-2">
                                <DollarSign class="h-4 w-4 text-yellow-600" />
                            </div>
                            <div>
                                <p class="text-lg font-bold">{{ formatCurrency(estimatedMonthlyIncome) }}</p>
                                <p class="text-sm text-muted-foreground">Ingresos Estimados</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-emerald-100 p-2">
                                <BarChart3 class="h-4 w-4 text-emerald-600" />
                            </div>
                            <div>
                                <p class="text-lg font-bold">{{ formatCurrency(realMonthlyIncome) }}</p>
                                <p class="text-sm text-muted-foreground">Ingresos Reales</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content -->
            <Tabs default-value="overview" class="space-y-6">
                <TabsList class="grid w-full grid-cols-4">
                    <TabsTrigger value="overview">Resumen</TabsTrigger>
                    <TabsTrigger value="apartments">Apartamentos</TabsTrigger>
                    <TabsTrigger value="types">Tipos</TabsTrigger>
                    <TabsTrigger value="details">Detalles</TabsTrigger>
                </TabsList>

                <!-- Overview Tab -->
                <TabsContent value="overview" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Configuration Summary -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Building class="h-5 w-5" />
                                    Configuración General
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-muted-foreground">Torres</p>
                                        <p class="text-lg font-semibold">{{ conjunto.number_of_towers }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Pisos por Torre</p>
                                        <p class="text-lg font-semibold">{{ conjunto.floors_per_tower }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Aptos por Piso</p>
                                        <p class="text-lg font-semibold">{{ conjunto.apartments_per_floor }}</p>
                                    </div>
                                </div>

                                <Separator />

                                <div>
                                    <p class="mb-2 text-sm text-muted-foreground">Torres Configuradas</p>
                                    <div class="flex flex-wrap gap-2">
                                        <Badge v-for="tower in conjunto.tower_names" :key="tower" variant="outline"> Torre {{ tower }} </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Occupancy Status -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <BarChart3 class="h-5 w-5" />
                                    Estado de Ocupación
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                                            <span class="text-sm">Ocupados</span>
                                        </div>
                                        <span class="font-semibold">{{ statistics.occupied_apartments }}</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                            <span class="text-sm">Disponibles</span>
                                        </div>
                                        <span class="font-semibold">{{ statistics.available_apartments }}</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                                            <span class="text-sm">Mantenimiento</span>
                                        </div>
                                        <span class="font-semibold">{{ statistics.maintenance_apartments }}</span>
                                    </div>
                                </div>

                                <Separator />

                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Ingresos Estimados</span>
                                        <span class="font-semibold">{{ formatCurrency(estimatedMonthlyIncome) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Ingresos Reales</span>
                                        <span class="font-semibold">{{ formatCurrency(realMonthlyIncome) }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Apartments Tab -->
                <TabsContent value="apartments" class="space-y-6">
                    <div v-for="(floors, tower) in apartmentsByTower" :key="tower" class="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Building class="h-5 w-5" />
                                    Torre {{ tower }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-for="(apartments, floor) in floors" :key="floor" class="space-y-2">
                                        <h4 class="text-sm font-medium text-muted-foreground">Piso {{ floor }}</h4>
                                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                                            <div
                                                v-for="apartment in apartments"
                                                :key="apartment.id"
                                                class="rounded-lg border p-3 transition-shadow hover:shadow-md"
                                            >
                                                <div class="mb-2 flex items-center justify-between">
                                                    <span class="font-medium">{{ apartment.number }}</span>
                                                    <Badge :class="getStatusColor(apartment.status)" class="text-xs">
                                                        {{ getStatusLabel(apartment.status) }}
                                                    </Badge>
                                                </div>
                                                <div class="space-y-1 text-xs text-muted-foreground">
                                                    <p>{{ apartment.apartment_type.name }}</p>
                                                    <p>{{ apartment.apartment_type.area_sqm }}m²</p>
                                                    <p>{{ formatCurrency(apartment.monthly_fee) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Types Tab -->
                <TabsContent value="types" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <Card v-for="type in conjunto.apartment_types" :key="type.id">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Home class="h-5 w-5" />
                                    {{ type.name }}
                                </CardTitle>
                                <CardDescription>
                                    {{ type.description }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-muted-foreground">Área</p>
                                        <p class="font-semibold">{{ type.area_sqm }}m²</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Habitaciones</p>
                                        <p class="font-semibold">{{ type.bedrooms }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Baños</p>
                                        <p class="font-semibold">{{ type.bathrooms }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Apartamentos</p>
                                        <p class="font-semibold">{{ type.apartments_count || 0 }}</p>
                                    </div>
                                </div>

                                <Separator />

                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Coeficiente</span>
                                        <span class="font-semibold">{{ (type.coefficient * 100).toFixed(3) }}%</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Tarifa</span>
                                        <span class="font-semibold">{{ formatCurrency(type.administration_fee) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm text-muted-foreground">Características</p>
                                    <div class="flex flex-wrap gap-2">
                                        <Badge v-if="type.has_balcony" variant="outline">Balcón</Badge>
                                        <Badge v-if="type.has_laundry_room" variant="outline">Lavandería</Badge>
                                        <Badge v-if="type.has_maid_room" variant="outline">Cuarto Servicio</Badge>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm text-muted-foreground">Posiciones en Piso</p>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-for="position in type.floor_positions" :key="position" variant="secondary" class="text-xs">
                                            {{ position }}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Details Tab -->
                <TabsContent value="details" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Metadata -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Información del Sistema
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">ID del Conjunto</span>
                                        <span class="font-mono text-sm">{{ conjunto.id }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">Fecha de Creación</span>
                                        <span class="text-sm">{{ formatDate(conjunto.created_at) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">Última Actualización</span>
                                        <span class="text-sm">{{ formatDate(conjunto.updated_at) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">Estado</span>
                                        <Badge :variant="conjunto.is_active ? 'default' : 'secondary'">
                                            {{ conjunto.is_active ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Configuration Metadata -->
                        <Card v-if="conjunto.configuration_metadata">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    Configuration Metadata
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2">
                                    <div v-for="(value, key) in conjunto.configuration_metadata" :key="key" class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">{{ getMetadataKeyLabel(key) }}</span>
                                        <span class="font-medium">{{ Array.isArray(value) ? `[${value.join(', ')}]` : value }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
