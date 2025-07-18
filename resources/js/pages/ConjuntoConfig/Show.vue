<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    ArrowLeft, 
    Building, 
    Home, 
    Edit, 
    BarChart3,
    MapPin,
    Users,
    DollarSign,
    Calendar,
    FileText,
    Settings,
    RefreshCw,
    Plus
} from 'lucide-vue-next';

interface ApartmentType {
    id: number;
    name: string;
    description: string;
    area_sqm: number;
    bedrooms: number;
    bathrooms: number;
    has_balcony: boolean;
    has_laundry_room: boolean;
    has_maid_room: boolean;
    coefficient: number;
    administration_fee: number;
    floor_positions: number[];
    apartments_count: number;
}

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    position_on_floor: number;
    status: 'Available' | 'Occupied' | 'Maintenance' | 'Reserved';
    monthly_fee: number;
    apartment_type: ApartmentType;
}

interface ConjuntoConfig {
    id: number;
    name: string;
    description: string;
    number_of_towers: number;
    floors_per_tower: number;
    apartments_per_floor: number;
    is_active: boolean;
    tower_names: string[];
    configuration_metadata: any;
    apartment_types: ApartmentType[];
    apartments: Apartment[];
    created_at: string;
    updated_at: string;
}

interface Statistics {
    total_apartments: number;
    occupied_apartments: number;
    available_apartments: number;
    maintenance_apartments: number;
    total_area: number;
    monthly_fees_total: number;
}

const props = defineProps<{
    conjuntoConfig: ConjuntoConfig;
    apartmentsByTower: Record<string, Record<number, Apartment[]>>;
    statistics: Statistics;
}>();

// Computed properties
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// UI State
const isGeneratingApartments = ref(false);

const generateApartments = () => {
    if (confirm(`¿Estás seguro de que deseas generar ${props.conjuntoConfig.number_of_towers * props.conjuntoConfig.floors_per_tower * props.conjuntoConfig.apartments_per_floor} apartamentos? Esta acción eliminará todos los apartamentos existentes.`)) {
        isGeneratingApartments.value = true;
        
        router.post(`/conjunto-config/${props.conjuntoConfig.id}/generate-apartments`, {}, {
            onFinish: () => {
                isGeneratingApartments.value = false;
            }
        });
    }
};

const canGenerateApartments = computed(() => {
    return props.conjuntoConfig.apartment_types.length > 0 && 
           props.conjuntoConfig.number_of_towers > 0 && 
           props.conjuntoConfig.floors_per_tower > 0 && 
           props.conjuntoConfig.apartments_per_floor > 0;
});

const estimatedApartments = computed(() => {
    return props.conjuntoConfig.number_of_towers * 
           props.conjuntoConfig.floors_per_tower * 
           props.conjuntoConfig.apartments_per_floor;
});

const getStatusColor = (status: string) => {
    const colors = {
        'Available': 'bg-green-100 text-green-800',
        'Occupied': 'bg-blue-100 text-blue-800',
        'Maintenance': 'bg-yellow-100 text-yellow-800',
        'Reserved': 'bg-purple-100 text-purple-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status: string) => {
    const labels = {
        'Available': 'Disponible',
        'Occupied': 'Ocupado',
        'Maintenance': 'Mantenimiento',
        'Reserved': 'Reservado'
    };
    return labels[status] || status;
};

const occupancyRate = computed(() => {
    if (props.statistics.total_apartments === 0) return 0;
    return Math.round((props.statistics.occupied_apartments / props.statistics.total_apartments) * 100);
});

const averageMonthlyFee = computed(() => {
    if (props.statistics.total_apartments === 0) return 0;
    return props.statistics.monthly_fees_total / props.statistics.total_apartments;
});

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Configuración de Conjuntos', href: '/conjunto-config' },
    { title: props.conjuntoConfig.name, href: `/conjunto-config/${props.conjuntoConfig.id}` },
];
</script>

<template>
    <Head :title="conjuntoConfig.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ conjuntoConfig.name }}</h1>
                        <Badge :variant="conjuntoConfig.is_active ? 'default' : 'secondary'">
                            {{ conjuntoConfig.is_active ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">
                        {{ conjuntoConfig.description }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/conjunto-config">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link :href="`/conjunto-config/${conjuntoConfig.id}/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                    <Button 
                        @click="generateApartments"
                        :disabled="!canGenerateApartments || isGeneratingApartments"
                        variant="outline"
                        class="gap-2"
                    >
                        <RefreshCw :class="['h-4 w-4', { 'animate-spin': isGeneratingApartments }]" />
                        {{ isGeneratingApartments ? 'Generando...' : 'Generar Apartamentos' }}
                    </Button>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
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
                            <div class="p-2 bg-green-100 rounded-lg">
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
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <BarChart3 class="h-4 w-4 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-lg font-bold">{{ (statistics.total_area || 0).toLocaleString() }}m²</p>
                                <p class="text-sm text-muted-foreground">Área Total</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <DollarSign class="h-4 w-4 text-yellow-600" />
                            </div>
                            <div>
                                <p class="text-lg font-bold">{{ formatCurrency(averageMonthlyFee) }}</p>
                                <p class="text-sm text-muted-foreground">Tarifa Promedio</p>
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
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                                        <p class="text-lg font-semibold">{{ conjuntoConfig.number_of_towers }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Pisos por Torre</p>
                                        <p class="text-lg font-semibold">{{ conjuntoConfig.floors_per_tower }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-muted-foreground">Aptos por Piso</p>
                                        <p class="text-lg font-semibold">{{ conjuntoConfig.apartments_per_floor }}</p>
                                    </div>
                                </div>
                                
                                <Separator />
                                
                                <div>
                                    <p class="text-sm text-muted-foreground mb-2">Torres Configuradas</p>
                                    <div class="flex flex-wrap gap-2">
                                        <Badge
                                            v-for="tower in conjuntoConfig.tower_names"
                                            :key="tower"
                                            variant="outline"
                                        >
                                            Torre {{ tower }}
                                        </Badge>
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
                                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                            <span class="text-sm">Ocupados</span>
                                        </div>
                                        <span class="font-semibold">{{ statistics.occupied_apartments }}</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                            <span class="text-sm">Disponibles</span>
                                        </div>
                                        <span class="font-semibold">{{ statistics.available_apartments }}</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                            <span class="text-sm">Mantenimiento</span>
                                        </div>
                                        <span class="font-semibold">{{ statistics.maintenance_apartments }}</span>
                                    </div>
                                </div>
                                
                                <Separator />
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Ingresos Mensuales</span>
                                        <span class="font-semibold">{{ formatCurrency(statistics.monthly_fees_total) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Tarifa Promedio</span>
                                        <span class="font-semibold">{{ formatCurrency(averageMonthlyFee) }}</span>
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
                                        <h4 class="font-medium text-sm text-muted-foreground">Piso {{ floor }}</h4>
                                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                            <div
                                                v-for="apartment in apartments"
                                                :key="apartment.id"
                                                class="p-3 border rounded-lg hover:shadow-md transition-shadow"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="font-medium">{{ apartment.number }}</span>
                                                    <Badge :class="getStatusColor(apartment.status)" class="text-xs">
                                                        {{ getStatusLabel(apartment.status) }}
                                                    </Badge>
                                                </div>
                                                <div class="text-xs text-muted-foreground space-y-1">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Card v-for="type in conjuntoConfig.apartment_types" :key="type.id">
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
                                        <Badge
                                            v-for="position in type.floor_positions"
                                            :key="position"
                                            variant="secondary"
                                            class="text-xs"
                                        >
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
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                                        <span class="font-mono text-sm">{{ conjuntoConfig.id }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">Fecha de Creación</span>
                                        <span class="text-sm">{{ formatDate(conjuntoConfig.created_at) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">Última Actualización</span>
                                        <span class="text-sm">{{ formatDate(conjuntoConfig.updated_at) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-muted-foreground">Estado</span>
                                        <Badge :variant="conjuntoConfig.is_active ? 'default' : 'secondary'">
                                            {{ conjuntoConfig.is_active ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Configuration Metadata -->
                        <Card v-if="conjuntoConfig.configuration_metadata">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    Metadatos de Configuración
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2">
                                    <div
                                        v-for="(value, key) in conjuntoConfig.configuration_metadata"
                                        :key="key"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-muted-foreground">{{ key }}</span>
                                        <span class="font-medium">{{ value }}</span>
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