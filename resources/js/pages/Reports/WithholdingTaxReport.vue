<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FileDown, Filter, X } from 'lucide-vue-next';

interface Provider {
    id: number;
    name: string;
}

interface RetentionAccount {
    id: number;
    code: string;
    name: string;
}

interface Expense {
    id: number;
    expense_number: string;
    vendor_name: string;
    expense_date: string;
    subtotal: number;
    tax_amount: number;
    category: string;
    provider_id: number | null;
}

interface RetentionByAccount {
    account_id: number;
    account_code: string;
    account_name: string;
    count: number;
    total_retained: number;
    expenses: Expense[];
}

interface Props {
    retentionsByAccount: RetentionByAccount[];
    totalRetentions: number;
    mainAccount: { id: number; code: string; name: string } | null;
    retentionAccounts: RetentionAccount[];
    providers: Provider[];
    filters: {
        start_date: string | null;
        end_date: string | null;
        retention_type: number | null;
        provider_id: number | null;
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
const retentionType = ref(props.filters.retention_type?.toString() || '');
const providerId = ref(props.filters.provider_id?.toString() || '');
const providerSearch = ref(props.filters.provider_search || '');

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO');
};

const applyFilters = () => {
    router.get(
        '/retenciones',
        {
            start_date: startDate.value || null,
            end_date: endDate.value || null,
            retention_type: retentionType.value || null,
            provider_id: providerId.value || null,
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
    retentionType.value = '';
    providerId.value = '';
    providerSearch.value = '';
    router.get('/retenciones');
};

const hasActiveFilters = computed(() => {
    return startDate.value || endDate.value || retentionType.value || providerId.value || providerSearch.value;
});
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
                    <CardDescription>Filtra las retenciones por fecha, tipo o proveedor</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <div class="space-y-2">
                            <Label for="start_date">Fecha Inicio</Label>
                            <Input id="start_date" v-model="startDate" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">Fecha Fin</Label>
                            <Input id="end_date" v-model="endDate" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="retention_type">Tipo de Retención</Label>
                            <Select v-model="retentionType">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos</SelectItem>
                                    <SelectItem
                                        v-for="account in retentionAccounts"
                                        :key="account.id"
                                        :value="account.id.toString()"
                                    >
                                        {{ account.code }} - {{ account.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Total Retenido</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totalRetentions) }}</div>
                        <p v-if="mainAccount" class="text-xs text-muted-foreground">
                            Cuenta {{ mainAccount.code }}
                        </p>
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
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Tasa Promedio</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.average_retention_rate.toFixed(2) }}%</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Retentions by Account -->
            <div v-if="retentionsByAccount.length > 0" class="space-y-4">
                <Card v-for="retention in retentionsByAccount" :key="retention.account_id">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>{{ retention.account_code }} - {{ retention.account_name }}</CardTitle>
                                <CardDescription>
                                    {{ retention.count }} gastos - Total: {{ formatCurrency(retention.total_retained) }}
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nro. Gasto</TableHead>
                                    <TableHead>Proveedor</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Categoría</TableHead>
                                    <TableHead class="text-right">Base</TableHead>
                                    <TableHead class="text-right">Retención</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="expense in retention.expenses" :key="expense.id">
                                    <TableCell>{{ expense.expense_number }}</TableCell>
                                    <TableCell>{{ expense.vendor_name }}</TableCell>
                                    <TableCell>{{ formatDate(expense.expense_date) }}</TableCell>
                                    <TableCell>{{ expense.category || '-' }}</TableCell>
                                    <TableCell class="text-right">{{ formatCurrency(expense.subtotal) }}</TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ formatCurrency(expense.tax_amount) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="py-12 text-center">
                    <p class="text-muted-foreground">
                        No se encontraron gastos con retenciones en el período seleccionado.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
