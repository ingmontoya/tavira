<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, Eye, Plus, TrendingDown, TrendingUp, Undo2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Closure {
    id: number;
    fiscal_year: number;
    period_label: string;
    closure_date: string;
    status: string;
    status_label: string;
    total_income: number;
    total_expenses: number;
    net_result: number;
    is_profit: boolean;
    closed_by: string;
    closing_transaction_number: string;
    notes?: string;
}

const props = defineProps<{
    closures: Closure[];
    conjunto: any;
}>();

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Cierres Contables', href: route('accounting.closures.index') },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'reversed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const reverseClosure = (closureId: number) => {
    if (confirm('¿Está seguro de que desea reversar este cierre contable? Esta acción cancelará todas las transacciones asociadas.')) {
        router.post(route('accounting.closures.reverse', closureId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};
</script>

<template>
    <Head title="Cierres Contables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Cierres Contables</h1>
                    <p class="text-muted-foreground">Gestión de cierres contables anuales</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('accounting.closures.create')">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Nuevo Cierre
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Closures List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Historial de Cierres
                    </CardTitle>
                    <CardDescription>
                        Listado de todos los cierres contables ejecutados
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="closures.length === 0" class="py-12 text-center">
                        <p class="text-muted-foreground">No hay cierres contables registrados</p>
                        <Link :href="route('accounting.closures.create')">
                            <Button variant="outline" class="mt-4 gap-2">
                                <Plus class="h-4 w-4" />
                                Crear Primer Cierre
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Período</TableHead>
                                    <TableHead>Fecha de Cierre</TableHead>
                                    <TableHead class="text-right">Ingresos</TableHead>
                                    <TableHead class="text-right">Gastos</TableHead>
                                    <TableHead class="text-right">Resultado Neto</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Cerrado Por</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="closure in closures" :key="closure.id">
                                    <TableCell class="font-medium">
                                        {{ closure.period_label }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(closure.closure_date) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono text-green-600">
                                        {{ formatCurrency(closure.total_income) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono text-red-600">
                                        {{ formatCurrency(closure.total_expenses) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        <div class="flex items-center justify-end gap-1">
                                            <component
                                                :is="closure.is_profit ? TrendingUp : TrendingDown"
                                                class="h-4 w-4"
                                                :class="closure.is_profit ? 'text-green-600' : 'text-red-600'"
                                            />
                                            <span :class="closure.is_profit ? 'text-green-600' : 'text-red-600'">
                                                {{ formatCurrency(Math.abs(closure.net_result)) }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(closure.status)">
                                            {{ closure.status_label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{ closure.closed_by }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('accounting.closures.show', closure.id)">
                                                <Button variant="ghost" size="sm" class="gap-1">
                                                    <Eye class="h-4 w-4" />
                                                    Ver
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="closure.status === 'completed'"
                                                variant="ghost"
                                                size="sm"
                                                class="gap-1 text-orange-600 hover:text-orange-700"
                                                @click="reverseClosure(closure.id)"
                                            >
                                                <Undo2 class="h-4 w-4" />
                                                Reversar
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
