<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, DollarSign, Edit, FileText, Hash, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    account_type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
    parent_account?: {
        id: number;
        code: string;
        name: string;
    };
    child_accounts?: ChartOfAccount[];
    description?: string;
    is_active: boolean;
    balance: number;
    created_at: string;
    updated_at: string;
}

interface Transaction {
    id: number;
    reference: string;
    description: string;
    amount: number;
    type: 'debit' | 'credit';
    transaction_date: string;
    created_at: string;
}

const props = defineProps<{
    account: ChartOfAccount;
    recentTransactions: Transaction[];
    monthlyBalance: {
        month: string;
        balance: number;
        change: number;
    }[];
}>();

// Computed properties
const accountTypeInfo = computed(() => {
    const typeMap = {
        asset: {
            label: 'Activo',
            color: 'bg-blue-100 text-blue-800',
            description: 'Recursos económicos controlados por la entidad',
        },
        liability: {
            label: 'Pasivo',
            color: 'bg-red-100 text-red-800',
            description: 'Obligaciones presentes de la entidad',
        },
        equity: {
            label: 'Patrimonio',
            color: 'bg-purple-100 text-purple-800',
            description: 'Participación residual en los activos',
        },
        income: {
            label: 'Ingreso',
            color: 'bg-green-100 text-green-800',
            description: 'Incrementos en los beneficios económicos',
        },
        expense: {
            label: 'Gasto',
            color: 'bg-orange-100 text-orange-800',
            description: 'Decrementos en los beneficios económicos',
        },
    };
    return (
        typeMap[props.account.account_type] || {
            label: 'Desconocido',
            color: 'bg-gray-100 text-gray-800',
            description: 'Tipo de cuenta no reconocido',
        }
    );
});

const balanceColor = computed(() => {
    if (props.account.balance === 0) return 'text-muted-foreground';
    return props.account.balance > 0 ? 'text-green-600' : 'text-red-600';
});

const balanceIcon = computed(() => {
    if (props.account.balance === 0) return Hash;
    return props.account.balance > 0 ? TrendingUp : TrendingDown;
});

const formatCurrencyAbs = (amount: number) => {
    if (amount == null || isNaN(amount)) {
        return formatCurrency(0);
    }
    return formatCurrency(Math.abs(amount));
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Plan de Cuentas', href: '/accounting/chart-of-accounts' },
    { title: props.account.name, href: `/accounting/chart-of-accounts/${props.account.id}` },
];
</script>

<template>
    <Head :title="account.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ account.name }}</h1>
                        <Badge :class="accountTypeInfo.color">
                            {{ accountTypeInfo.label }}
                        </Badge>
                        <Badge variant="outline" v-if="!account.is_active"> Inactiva </Badge>
                    </div>
                    <p class="text-muted-foreground">Código: {{ account.code }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/accounting/chart-of-accounts">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link :href="`/accounting/chart-of-accounts/${account.id}/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Information -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Account Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de la Cuenta</CardTitle>
                            <CardDescription>Información básica y configuración</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Código</Label>
                                    <p class="font-mono text-lg">{{ account.code }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Tipo de Cuenta</Label>
                                    <div class="flex items-center gap-2">
                                        <Badge :class="accountTypeInfo.color">
                                            {{ accountTypeInfo.label }}
                                        </Badge>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ accountTypeInfo.description }}</p>
                                </div>
                            </div>

                            <div v-if="account.parent_account">
                                <Label class="text-sm font-medium text-muted-foreground">Cuenta Principal</Label>
                                <Button
                                    variant="link"
                                    class="h-auto p-0 text-base font-normal"
                                    @click="router.visit(`/accounting/chart-of-accounts/${account.parent_account.id}`)"
                                >
                                    {{ account.parent_account.code }} - {{ account.parent_account.name }}
                                </Button>
                            </div>

                            <div v-if="account.description">
                                <Label class="text-sm font-medium text-muted-foreground">Descripción</Label>
                                <p class="text-sm leading-relaxed">{{ account.description }}</p>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <Label class="text-muted-foreground">Creada</Label>
                                    <p>{{ formatDate(account.created_at) }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Última modificación</Label>
                                    <p>{{ formatDate(account.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Transactions -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Transacciones Recientes</CardTitle>
                                    <CardDescription>Últimos movimientos en esta cuenta</CardDescription>
                                </div>
                                <Button variant="outline" size="sm" @click="router.visit(`/accounting/transactions?account=${account.id}`)">
                                    Ver todas
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!recentTransactions || recentTransactions.length === 0" class="py-8 text-center">
                                <FileText class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p class="text-muted-foreground">No hay transacciones registradas</p>
                            </div>
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead>Referencia</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead class="text-right">Monto</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="transaction in recentTransactions || []" :key="transaction.id">
                                        <TableCell class="text-sm">
                                            {{ formatDate(transaction.transaction_date) }}
                                        </TableCell>
                                        <TableCell class="font-mono text-sm">
                                            {{ transaction.reference }}
                                        </TableCell>
                                        <TableCell class="text-sm">
                                            {{ transaction.description }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            <span :class="transaction.type === 'debit' ? 'text-red-600' : 'text-green-600'">
                                                {{ transaction.type === 'debit' ? '-' : '+' }}{{ formatCurrency(transaction.amount) }}
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Child Accounts -->
                    <Card v-if="account.child_accounts && account.child_accounts.length > 0">
                        <CardHeader>
                            <CardTitle>Subcuentas</CardTitle>
                            <CardDescription>Cuentas que dependen de esta cuenta principal</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead class="text-right">Saldo</TableHead>
                                        <TableHead>Estado</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="childAccount in account.child_accounts"
                                        :key="childAccount.id"
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="router.visit(`/accounting/chart-of-accounts/${childAccount.id}`)"
                                    >
                                        <TableCell class="font-mono">{{ childAccount.code }}</TableCell>
                                        <TableCell>{{ childAccount.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            <span :class="childAccount.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ formatCurrencyAbs(childAccount.balance) }}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="childAccount.is_active ? 'default' : 'secondary'">
                                                {{ childAccount.is_active ? 'Activa' : 'Inactiva' }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Balance Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5" />
                                Saldo Actual
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-center">
                                <component :is="balanceIcon" :class="['mx-auto h-8 w-8', balanceColor]" />
                                <p :class="['text-3xl font-bold', balanceColor]">
                                    {{ formatCurrencyAbs(account.balance) }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ account.balance === 0 ? 'Cuenta en equilibrio' : account.balance > 0 ? 'Saldo positivo' : 'Saldo negativo' }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Monthly Balance Trend -->
                    <Card v-if="monthlyBalance.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Tendencia Mensual
                            </CardTitle>
                            <CardDescription>Evolución del saldo en los últimos meses</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="month in monthlyBalance" :key="month.month" class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium">{{ month.month }}</p>
                                        <p :class="['text-xs', month.change >= 0 ? 'text-green-600' : 'text-red-600']">
                                            {{ month.change >= 0 ? '+' : '' }}{{ month.change.toFixed(1) }}%
                                        </p>
                                    </div>
                                    <p class="font-mono text-sm">
                                        {{ formatCurrencyAbs(month.balance) }}
                                    </p>
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
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/accounting/transactions/create?account=${account.id}`)"
                            >
                                <FileText class="h-4 w-4" />
                                Nueva Transacción
                            </Button>
                            <Button
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/accounting/reports/account-ledger?account=${account.id}`)"
                            >
                                <Calendar class="h-4 w-4" />
                                Ver Libro Mayor
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
