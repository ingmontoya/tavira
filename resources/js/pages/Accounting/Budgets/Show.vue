<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Calendar, CheckCircle, Clock, DollarSign, Edit, Hash, Play, TrendingDown, TrendingUp, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface BudgetItem {
    id: number;
    account: {
        id: number;
        code: string;
        name: string;
        account_type: string;
    };
    budgeted_amount: number;
    executed_amount: number;
    variance_amount: number;
    variance_percentage: number;
    description?: string;
}

interface Budget {
    id: number;
    name: string;
    description?: string;
    year: number;
    start_date: string;
    end_date: string;
    total_budget: number;
    total_executed: number;
    status: 'Draft' | 'Active' | 'Closed';
    approval_date?: string;
    execution_percentage: number;
    items: BudgetItem[];
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    budget: Budget;
}>();

const deleteDialogOpen = ref(false);

// Computed properties
const statusInfo = computed(() => {
    const statusMap = {
        Draft: {
            label: 'Borrador',
            color: 'bg-gray-100 text-gray-800',
            icon: Edit,
            description: 'Presupuesto en proceso de creación',
        },
        Active: {
            label: 'Activo',
            color: 'bg-green-100 text-green-800',
            icon: CheckCircle,
            description: 'Presupuesto aprobado y en ejecución',
        },
        Closed: {
            label: 'Cerrado',
            color: 'bg-blue-100 text-blue-800',
            icon: Clock,
            description: 'Presupuesto finalizado',
        },
    };
    return (
        statusMap[props.budget.status] || {
            label: 'Desconocido',
            color: 'bg-gray-100 text-gray-800',
            icon: AlertCircle,
            description: 'Estado no reconocido',
        }
    );
});

const totalVariance = computed(() => {
    return props.budget.total_executed - props.budget.total_budget;
});

const isOverBudget = computed(() => {
    return totalVariance.value > 0;
});

const canEdit = computed(() => {
    return props.budget.status === 'Draft'; // Only allow editing for draft budgets
});

const canApprove = computed(() => {
    return props.budget.status === 'Draft';
});

const canActivate = computed(() => {
    return props.budget.status === 'Approved';
});

const canClose = computed(() => {
    return props.budget.status === 'Active';
});

const canDelete = computed(() => {
    return props.budget.status !== 'Active';
});

// Budget actions
const approveBudget = () => {
    if (confirm('¿Está seguro que desea aprobar este presupuesto?')) {
        router.post(`/accounting/budgets/${props.budget.id}/approve`);
    }
};

const activateBudget = () => {
    if (confirm('¿Está seguro que desea activar este presupuesto? Esto desactivará otros presupuestos activos del mismo año.')) {
        router.post(`/accounting/budgets/${props.budget.id}/activate`);
    }
};

const closeBudget = () => {
    if (confirm('¿Está seguro que desea cerrar este presupuesto?')) {
        router.post(`/accounting/budgets/${props.budget.id}/close`);
    }
};

const confirmDeleteBudget = () => {
    deleteDialogOpen.value = false;
    router.delete(`/accounting/budgets/${props.budget.id}`, {
        onSuccess: () => {
            router.visit('/accounting/budgets');
        }
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Presupuestos', href: '/accounting/budgets' },
    { title: props.budget.name, href: `/accounting/budgets/${props.budget.id}` },
];
</script>

<template>
    <Head :title="budget.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ budget.name }}</h1>
                        <Badge :class="statusInfo.color">
                            <component :is="statusInfo.icon" class="mr-1 h-3 w-3" />
                            {{ statusInfo.label }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">{{ budget.description || `Presupuesto para el año ${budget.year}` }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/accounting/budgets">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    
                    <!-- Botones de estado -->
                    <Button v-if="canApprove" @click="approveBudget" variant="outline" class="gap-2 text-blue-600 border-blue-600 hover:bg-blue-50">
                        <CheckCircle class="h-4 w-4" />
                        Aprobar
                    </Button>
                    
                    <Button v-if="canActivate" @click="activateBudget" class="gap-2 bg-green-600 hover:bg-green-700">
                        <Play class="h-4 w-4" />
                        Activar
                    </Button>
                    
                    <Button v-if="canClose" @click="closeBudget" variant="outline" class="gap-2 text-orange-600 border-orange-600 hover:bg-orange-50">
                        <Clock class="h-4 w-4" />
                        Cerrar
                    </Button>
                    
                    <Link v-if="canEdit" :href="`/accounting/budgets/${budget.id}/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                    
                    <AlertDialog v-model:open="deleteDialogOpen">
                        <AlertDialogTrigger as-child>
                            <Button v-if="canDelete" variant="outline" class="gap-2 text-red-600 border-red-600 hover:bg-red-50">
                                <Trash2 class="h-4 w-4" />
                                Eliminar
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Eliminar Presupuesto</AlertDialogTitle>
                                <AlertDialogDescription>
                                    ¿Está seguro que desea eliminar el presupuesto "{{ budget.name }}" del año {{ budget.year }}? <br><br>
                                    <strong class="text-red-600">Esta acción no se puede deshacer</strong> y se eliminarán todas las partidas presupuestales asociadas.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                <AlertDialogAction @click="confirmDeleteBudget" class="bg-red-600 hover:bg-red-700">Eliminar Presupuesto</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Budget Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles del Presupuesto</CardTitle>
                            <CardDescription>Información general y período de vigencia</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Año</Label>
                                    <p class="text-lg font-semibold">{{ budget.year }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Estado</Label>
                                    <div class="flex items-center gap-2">
                                        <Badge :class="statusInfo.color">
                                            <component :is="statusInfo.icon" class="mr-1 h-3 w-3" />
                                            {{ statusInfo.label }}
                                        </Badge>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ statusInfo.description }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Período</Label>
                                    <p class="text-sm">{{ formatDate(budget.start_date) }} - {{ formatDate(budget.end_date) }}</p>
                                </div>
                                <div v-if="budget.approval_date">
                                    <Label class="text-sm font-medium text-muted-foreground">Fecha de Aprobación</Label>
                                    <p class="text-sm">{{ formatDate(budget.approval_date) }}</p>
                                </div>
                            </div>

                            <!-- Execution Progress -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label class="text-sm font-medium text-muted-foreground">Progreso de Ejecución</Label>
                                    <span class="text-sm font-medium">{{ budget.execution_percentage }}%</span>
                                </div>
                                <Progress :value="Math.min(budget.execution_percentage, 100)" :class="isOverBudget ? 'bg-red-100' : ''" />
                                <div v-if="isOverBudget" class="flex items-center gap-1 text-xs text-red-600">
                                    <TrendingUp class="h-3 w-3" />
                                    Sobrepresupuesto por {{ formatCurrency(totalVariance) }}
                                </div>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <Label class="text-muted-foreground">Creado</Label>
                                    <p>{{ formatDateTime(budget.created_at) }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Última modificación</Label>
                                    <p>{{ formatDateTime(budget.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Budget Items -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Partidas Presupuestales</CardTitle>
                                    <CardDescription>Detalle por cuenta contable</CardDescription>
                                </div>
                                <Button
                                    v-if="canEdit"
                                    variant="outline"
                                    size="sm"
                                    @click="router.visit(`/accounting/budgets/${budget.id}/items/create`)"
                                >
                                    Agregar Partida
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="budget.items.length === 0" class="py-8 text-center">
                                <Hash class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p class="text-muted-foreground">No hay partidas presupuestales definidas</p>
                            </div>
                            <div v-else>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Cuenta</TableHead>
                                            <TableHead class="text-right">Presupuestado</TableHead>
                                            <TableHead class="text-right">Ejecutado</TableHead>
                                            <TableHead class="text-right">Variación</TableHead>
                                            <TableHead class="text-center">%</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="item in budget.items"
                                            :key="item.id"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="router.visit(`/accounting/chart-of-accounts/${item.account.id}`)"
                                        >
                                            <TableCell>
                                                <div class="space-y-1">
                                                    <div class="font-mono text-sm">{{ item.account.code }}</div>
                                                    <div class="text-sm font-medium">{{ item.account.name }}</div>
                                                    <div v-if="item.description" class="text-xs text-muted-foreground">{{ item.description }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                {{ formatCurrency(item.budgeted_amount) }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                {{ formatCurrency(item.executed_amount) }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                <span :class="item.variance_amount >= 0 ? 'text-red-600' : 'text-green-600'">
                                                    {{ item.variance_amount >= 0 ? '+' : '' }}{{ formatCurrency(item.variance_amount) }}
                                                </span>
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <component
                                                        :is="
                                                            item.variance_percentage > 0
                                                                ? TrendingUp
                                                                : item.variance_percentage < 0
                                                                  ? TrendingDown
                                                                  : Hash
                                                        "
                                                        :class="[
                                                            'h-3 w-3',
                                                            item.variance_percentage > 0
                                                                ? 'text-red-500'
                                                                : item.variance_percentage < 0
                                                                  ? 'text-green-500'
                                                                  : 'text-gray-500',
                                                        ]"
                                                    />
                                                    <span
                                                        :class="
                                                            item.variance_percentage > 0
                                                                ? 'text-red-600'
                                                                : item.variance_percentage < 0
                                                                  ? 'text-green-600'
                                                                  : 'text-gray-600'
                                                        "
                                                    >
                                                        {{ Math.abs(item.variance_percentage).toFixed(1) }}%
                                                    </span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Budget Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5" />
                                Resumen Financiero
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <div>
                                    <Label class="text-sm text-muted-foreground">Presupuesto Total</Label>
                                    <p class="text-2xl font-bold">{{ formatCurrency(budget.total_budget) }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm text-muted-foreground">Ejecutado</Label>
                                    <p class="text-xl font-semibold text-blue-600">{{ formatCurrency(budget.total_executed) }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm text-muted-foreground">Variación</Label>
                                    <p :class="['text-lg font-semibold', isOverBudget ? 'text-red-600' : 'text-green-600']">
                                        {{ totalVariance >= 0 ? '+' : '' }}{{ formatCurrency(totalVariance) }}
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Partidas:</span>
                                    <span>{{ budget.items.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Ejecución:</span>
                                    <span>{{ budget.execution_percentage }}%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones Rápidas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button
                                v-if="canEdit"
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/accounting/budgets/${budget.id}/edit`)"
                            >
                                <Edit class="h-4 w-4" />
                                Editar Presupuesto
                            </Button>

                            <Button
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/accounting/budgets/${budget.id}/execution`)"
                            >
                                <TrendingUp class="h-4 w-4" />
                                Ver Ejecución
                            </Button>

                            <Button
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/accounting/reports/budget-execution?budget=${budget.id}`)"
                            >
                                <Calendar class="h-4 w-4" />
                                Reporte de Ejecución
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
