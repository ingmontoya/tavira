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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building, Calendar, Edit, Eye, Filter, Home, Info, Plus, RefreshCw, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ApartmentType {
    id: number;
    name: string;
    area_sqm: number;
    bedrooms: number;
    bathrooms: number;
    administration_fee: number;
    has_balcony: boolean;
    has_laundry_room: boolean;
    has_maid_room: boolean;
    coefficient: number;
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
    apartment_types: ApartmentType[];
    apartment_types_count: number;
    apartments_count: number;
    created_at: string;
    updated_at: string;
    total_apartments: number;
}

const props = defineProps<{
    conjunto: ConjuntoConfig | null;
}>();

// Convert single conjunto to array for compatibility with original UI
const conjuntos = computed(() => {
    return props.conjunto ? [props.conjunto] : [];
});

// Reactive state
const searchQuery = ref('');
const statusFilter = ref('all');
const sortBy = ref('name');
const sortOrder = ref('asc');

// Computed properties
const filteredConjuntos = computed(() => {
    let filtered = conjuntos.value;

    // Search filter
    if (searchQuery.value) {
        filtered = filtered.filter(
            (conjunto) =>
                conjunto.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                conjunto.description?.toLowerCase().includes(searchQuery.value.toLowerCase()),
        );
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter((conjunto) => (statusFilter.value === 'active' ? conjunto.is_active : !conjunto.is_active));
    }

    // Sort
    if (filtered.length > 0) {
        filtered.sort((a, b) => {
            let aValue = a[sortBy.value];
            let bValue = b[sortBy.value];

            if (sortBy.value === 'name') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }

            if (sortOrder.value === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
    }

    return filtered;
});

const totalStats = computed(() => {
    return {
        totalConjuntos: conjuntos.value.length,
        activeConjuntos: conjuntos.value.filter((c) => c.is_active).length,
        totalApartments: conjuntos.value.reduce((sum, c) => sum + c.apartments_count, 0),
        totalTowers: conjuntos.value.reduce((sum, c) => sum + c.number_of_towers, 0),
    };
});

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Configuración de Conjuntos',
        href: '/conjunto-config',
    },
];

const generateApartments = (conjunto: ConjuntoConfig) => {
    router.post('/conjunto-config/generate-apartments');
};

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
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Configuración de Conjuntos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header with Statistics -->
            <div class="flex flex-col gap-6">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold tracking-tight">Configuración de Conjuntos</h1>
                        <p class="text-muted-foreground">Gestiona la configuración del conjunto residencial y genera apartamentos automáticamente</p>
                    </div>
                    <Link v-if="!conjunto" href="/conjunto-config/edit">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Crear Conjunto
                        </Button>
                    </Link>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-primary/10 p-2">
                                    <Building class="h-4 w-4 text-primary" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ totalStats.totalConjuntos }}</p>
                                    <p class="text-sm text-muted-foreground">Conjuntos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-green-100 p-2">
                                    <Building class="h-4 w-4 text-green-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ totalStats.activeConjuntos }}</p>
                                    <p class="text-sm text-muted-foreground">Activos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-blue-100 p-2">
                                    <Home class="h-4 w-4 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ totalStats.totalApartments }}</p>
                                    <p class="text-sm text-muted-foreground">Apartamentos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-purple-100 p-2">
                                    <Building class="h-4 w-4 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ totalStats.totalTowers }}</p>
                                    <p class="text-sm text-muted-foreground">Torres</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Filters and Search -->
            <Card v-if="conjunto">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <Filter class="h-5 w-5" />
                        Filtros y Búsqueda
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                <Input id="search" v-model="searchQuery" placeholder="Nombre o descripción..." class="pl-10" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Estado</Label>
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Filtrar por estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="active">Activos</SelectItem>
                                    <SelectItem value="inactive">Inactivos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="sort">Ordenar por</Label>
                            <Select v-model="sortBy">
                                <SelectTrigger>
                                    <SelectValue placeholder="Ordenar por" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name">Nombre</SelectItem>
                                    <SelectItem value="created_at">Fecha de creación</SelectItem>
                                    <SelectItem value="apartments_count">Número de apartamentos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="order">Orden</Label>
                            <Select v-model="sortOrder">
                                <SelectTrigger>
                                    <SelectValue placeholder="Orden" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="asc">Ascendente</SelectItem>
                                    <SelectItem value="desc">Descendente</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Results Summary -->
            <div v-if="conjunto" class="flex items-center justify-between text-sm text-muted-foreground">
                <span> Mostrando {{ filteredConjuntos.length }} de {{ conjuntos.length }} conjuntos </span>
                <span> Última actualización: {{ formatDate(new Date().toISOString()) }} </span>
            </div>

            <!-- Conjuntos Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="conjunto in filteredConjuntos"
                    :key="conjunto.id"
                    class="group relative transition-all duration-200 hover:border-primary/50 hover:shadow-lg"
                >
                    <CardHeader class="pb-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-primary/10 p-2">
                                    <Building class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg transition-colors group-hover:text-primary">
                                        {{ conjunto.name }}
                                    </CardTitle>
                                    <div class="mt-1 flex items-center gap-2">
                                        <Badge :variant="conjunto.is_active ? 'default' : 'secondary'">
                                            {{ conjunto.is_active ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                        <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(conjunto.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger>
                                        <Info class="h-4 w-4 text-muted-foreground" />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>ID: {{ conjunto.id }}</p>
                                        <p>Creado: {{ formatDate(conjunto.created_at) }}</p>
                                        <p>Actualizado: {{ formatDate(conjunto.updated_at) }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                        <CardDescription class="mt-2 line-clamp-2 text-sm">
                            {{ conjunto.description }}
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <!-- Key Statistics -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center gap-2 rounded-lg bg-muted/30 p-3">
                                <Building class="h-4 w-4 text-blue-600" />
                                <div>
                                    <p class="text-lg font-semibold">{{ conjunto.number_of_towers }}</p>
                                    <p class="text-xs text-muted-foreground">Torres</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg bg-muted/30 p-3">
                                <Home class="h-4 w-4 text-green-600" />
                                <div>
                                    <p class="text-lg font-semibold">{{ conjunto.apartments_count }}</p>
                                    <p class="text-xs text-muted-foreground">Apartamentos</p>
                                </div>
                            </div>
                        </div>

                        <!-- Configuration Details -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Pisos por torre:</span>
                                <span class="font-medium">{{ conjunto.floors_per_tower }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Aptos por piso:</span>
                                <span class="font-medium">{{ conjunto.apartments_per_floor }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Tipos de apartamento:</span>
                                <span class="font-medium">{{ conjunto.apartment_types_count }}</span>
                            </div>
                        </div>

                        <Separator />

                        <!-- Apartment Types -->
                        <div v-if="conjunto.apartment_types && conjunto.apartment_types.length > 0" class="space-y-2">
                            <p class="flex items-center gap-2 text-sm font-medium">
                                <Home class="h-4 w-4" />
                                Tipos de Apartamento
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <TooltipProvider v-for="type in conjunto.apartment_types" :key="type.id">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Badge variant="outline" class="text-xs hover:bg-muted/50">
                                                {{ type.name }} ({{ type.area_sqm }}m²)
                                            </Badge>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <div class="space-y-1">
                                                <p>
                                                    <strong>{{ type.name }}</strong>
                                                </p>
                                                <p>Área: {{ type.area_sqm }}m²</p>
                                                <p>Habitaciones: {{ type.bedrooms }}</p>
                                                <p>Baños: {{ type.bathrooms }}</p>
                                                <p>Tarifa: {{ formatCurrency(type.administration_fee) }}</p>
                                                <p>Coeficiente: {{ (type.coefficient * 100).toFixed(3) }}%</p>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-2">
                            <Link href="/conjunto-config/show">
                                <Button variant="outline" size="sm" class="flex-1">
                                    <Eye class="mr-2 h-4 w-4" />
                                    Ver
                                </Button>
                            </Link>
                            <Link href="/conjunto-config/edit">
                                <Button variant="outline" size="sm" class="flex-1">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Editar
                                </Button>
                            </Link>
                        </div>

                        <div class="flex gap-2">
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button variant="outline" size="sm" class="flex-1">
                                        <RefreshCw class="mr-2 h-4 w-4" />
                                        Generar
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>¿Regenerar apartamentos?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Esta acción generará apartamentos basados en la configuración actual. Si ya existen apartamentos, se
                                            mantendrán y solo se crearán los faltantes.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                        <AlertDialogAction @click="generateApartments(conjunto)"> Regenerar </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State - No conjunto configured -->
            <div v-if="conjuntos.length === 0" class="py-20 text-center">
                <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-muted/50">
                    <Building class="h-12 w-12 text-muted-foreground" />
                </div>
                <h3 class="mb-2 text-xl font-semibold">No hay conjunto configurado</h3>
                <p class="mx-auto mb-6 max-w-md text-muted-foreground">
                    Comienza creando la configuración de tu conjunto residencial. Define torres, pisos, tipos de apartamento y genera la estructura
                    automáticamente.
                </p>
                <Link href="/conjunto-config/edit">
                    <Button class="gap-2">
                        <Plus class="h-4 w-4" />
                        Crear Conjunto
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
