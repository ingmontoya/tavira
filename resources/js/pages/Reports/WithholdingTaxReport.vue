<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ref, computed } from 'vue';
import { FileDown, Filter, X, ChevronRight } from 'lucide-vue-next';

interface Provider {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    total_retained: number;
    expenses_count: number;
}

interface Props {
    providers: Provider[];
    totalRetentions: number;
    filters: {
        start_date: string | null;
        end_date: string | null;
        provider_search: string | null;
    };
    summary: {
        total_expenses_with_retention: number;
        total_providers: number;
        average_retention_rate: number;
    };
}

const props = defineProps<Props>();

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const providerSearch = ref(props.filters.provider_search || '');

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const applyFilters = () => {
    router.get(
        '/retenciones',
        {
            start_date: startDate.value || null,
            end_date: endDate.value || null,
            provider_search: providerSearch.value || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    startDate.value = '';
    endDate.value = '';
    providerSearch.value = '';
    router.get('/retenciones');
};

const hasActiveFilters = computed(() => {
    return startDate.value || endDate.value || providerSearch.value;
});

const viewProviderDetail = (providerId: number) => {
    router.get(`/retenciones/providers/${providerId}`, {
        start_date: startDate.value || null,
        end_date: endDate.value || null,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Retenciones en la Fuente" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Retenciones en la Fuente</h1>
                    <p class="text-muted-foreground">
                        Reporte de retenciones practicadas a proveedores
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="() => router.visit('/retenciones/certificates')">
                        <FileDown class="mr-2 h-4 w-4" />
                        Certificados
                    </Button>
                </div>
            </div>

            <!-- Filters Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filtros</CardTitle>
                    <CardDescription>Filtra las retenciones por fecha o proveedor</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="start_date">Fecha Inicio</Label>
                            <Input id="start_date" v-model="startDate" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">Fecha Fin</Label>
                            <Input id="end_date" v-model="endDate" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="provider_search">Buscar Proveedor</Label>
                            <Input
                                id="provider_search"
                                v-model="providerSearch"
                                placeholder="Nombre del proveedor..."
                            />
                        </div>
                        <div class="flex items-end gap-2">
                            <Button @click="applyFilters" class="flex-1">
                                <Filter class="mr-2 h-4 w-4" />
                                Filtrar
                            </Button>
                            <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Total Retenido</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totalRetentions) }}</div>
                        <p class="text-xs text-muted-foreground">Cuenta 2365</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Gastos con Retención</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.total_expenses_with_retention }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Proveedores</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.total_providers }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Providers List -->
            <Card>
                <CardHeader>
                    <CardTitle>Proveedores con Retenciones</CardTitle>
                    <CardDescription>
                        Haz clic en un proveedor para ver el detalle de sus retenciones y generar el certificado
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="providers.length > 0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Proveedor</TableHead>
                                    <TableHead>Tipo Doc.</TableHead>
                                    <TableHead>Número Doc.</TableHead>
                                    <TableHead class="text-right">Nro. Gastos</TableHead>
                                    <TableHead class="text-right">Total Retenido</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="provider in providers"
                                    :key="provider.id"
                                    class="cursor-pointer hover:bg-muted/50"
                                    @click="viewProviderDetail(provider.id)"
                                >
                                    <TableCell class="font-medium">{{ provider.name }}</TableCell>
                                    <TableCell>{{ provider.document_type || '-' }}</TableCell>
                                    <TableCell>{{ provider.document_number || '-' }}</TableCell>
                                    <TableCell class="text-right">{{ provider.expenses_count }}</TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ formatCurrency(provider.total_retained) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <ChevronRight class="h-4 w-4" />
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="py-12 text-center">
                        <p class="text-muted-foreground">
                            No se encontraron proveedores con retenciones en el período seleccionado.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
