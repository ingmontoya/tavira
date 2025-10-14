<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BookText, Calendar, CheckCircle, Download, Edit, Filter, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Account {
    id: number;
    code: string;
    name: string;
    full_name: string;
}

interface TransactionEntry {
    id: number;
    account: Account;
    description: string;
    debit_amount: number;
    credit_amount: number;
}

interface Transaction {
    id: number;
    transaction_number: string;
    transaction_date: string;
    description: string;
    reference_type: string;
    status: string;
    status_label: string;
    total_debit: number;
    total_credit: number;
    is_balanced: boolean;
    created_by: { id: number; name: string } | null;
    posted_by: { id: number; name: string } | null;
    posted_at: string | null;
    entries: TransactionEntry[];
}

interface Period {
    start_date: string;
    end_date: string;
    description: string;
}

interface Report {
    period: Period;
    transactions: Transaction[];
    total_debits: number;
    total_credits: number;
    is_balanced: boolean;
    transaction_count: number;
}

interface Filters {
    start_date: string;
    end_date: string;
    status: string;
}

const props = defineProps<{
    report: Report;
    filters: Filters;
    statuses: Record<string, string>;
}>();

const showFilters = ref(false);

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    status: props.filters.status,
});

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Reportes', href: '/accounting/reports' },
    { title: 'Libro Diario', href: '/accounting/reports/journal-book' },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const applyFilters = () => {
    form.get('/accounting/reports/journal-book', {
        preserveState: true,
    });
};

const exportReport = () => {
    const params = new URLSearchParams({
        start_date: props.filters.start_date,
        end_date: props.filters.end_date,
        status: props.filters.status,
    });
    window.location.href = `/accounting/reports/journal-book/export?${params.toString()}`;
};

const getStatusBadge = (status: string) => {
    const badges = {
        borrador: { text: 'Borrador', class: 'bg-gray-100 text-gray-800', icon: Edit },
        contabilizado: { text: 'Contabilizado', class: 'bg-green-100 text-green-800', icon: CheckCircle },
        cancelado: { text: 'Cancelado', class: 'bg-red-100 text-red-800', icon: XCircle },
    };
    return badges[status] || badges.borrador;
};

const viewTransaction = (transactionId: number) => {
    router.visit(`/accounting/transactions/${transactionId}`);
};

const transactionSummary = computed(() => {
    const byStatus = {
        borrador: 0,
        contabilizado: 0,
        cancelado: 0,
    };

    props.report.transactions.forEach((transaction) => {
        byStatus[transaction.status]++;
    });

    return {
        total: props.report.transaction_count,
        byStatus,
    };
});
</script>

<template>
    <Head title="Libro Diario" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Libro Diario</h1>
                    <p class="text-muted-foreground">{{ report.period.description }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="showFilters = !showFilters">
                        <Filter class="mr-2 h-4 w-4" />
                        Filtros
                    </Button>
                    <Button variant="outline" @click="exportReport">
                        <Download class="mr-2 h-4 w-4" />
                        Exportar
                    </Button>
                </div>
            </div>

            <!-- Filters Panel -->
            <Card v-if="showFilters" class="border-dashed">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <Calendar class="h-5 w-5" />
                        Filtros del Libro Diario
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="start_date">Fecha Inicial</Label>
                            <Input id="start_date" type="date" v-model="form.start_date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">Fecha Final</Label>
                            <Input id="end_date" type="date" v-model="form.end_date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Estado</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos</SelectItem>
                                    <SelectItem v-for="(label, value) in statuses" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showFilters = false"> Cancelar </Button>
                        <Button @click="applyFilters"> Aplicar Filtros </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total Transacciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ transactionSummary.total }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">Registros en el período</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total Débitos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(report.total_debits) }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">Suma de débitos</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total Créditos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(report.total_credits) }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">Suma de créditos</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Balance</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <component
                                :is="report.is_balanced ? CheckCircle : XCircle"
                                :class="['h-6 w-6', report.is_balanced ? 'text-green-600' : 'text-red-600']"
                            />
                            <span :class="['text-2xl font-bold', report.is_balanced ? 'text-green-600' : 'text-red-600']">
                                {{ report.is_balanced ? 'Balanceado' : 'No Balanceado' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Estado de la partida doble</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Transactions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <BookText class="h-5 w-5" />
                        Transacciones Contables
                        <span class="ml-2 text-sm font-normal text-muted-foreground">
                            ({{ report.transactions.length }} registros)
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="report.transactions.length === 0" class="py-12 text-center text-muted-foreground">
                        <BookText class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p class="text-lg font-medium">No hay transacciones en el período seleccionado</p>
                        <p class="text-sm">Ajusta los filtros o verifica que existan transacciones en este rango de fechas</p>
                    </div>

                    <div v-else class="space-y-6">
                        <div
                            v-for="transaction in report.transactions"
                            :key="transaction.id"
                            class="rounded-lg border bg-card p-4 hover:shadow-md transition-shadow cursor-pointer"
                            @click="viewTransaction(transaction.id)"
                        >
                            <!-- Transaction Header -->
                            <div class="mb-3 flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-mono text-lg font-semibold">{{ transaction.transaction_number }}</h3>
                                        <Badge :class="getStatusBadge(transaction.status).class">
                                            <component :is="getStatusBadge(transaction.status).icon" class="mr-1 h-3 w-3" />
                                            {{ transaction.status_label }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(transaction.transaction_date) }}</p>
                                    <p class="text-sm">{{ transaction.description }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-muted-foreground">Total</div>
                                    <div class="text-lg font-bold">{{ formatCurrency(transaction.total_debit) }}</div>
                                </div>
                            </div>

                            <Separator class="my-3" />

                            <!-- Transaction Entries -->
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cuenta</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead class="text-right">Débito</TableHead>
                                        <TableHead class="text-right">Crédito</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="entry in transaction.entries" :key="entry.id" class="hover:bg-muted/30">
                                        <TableCell>
                                            <div class="space-y-1">
                                                <div class="font-mono text-sm font-semibold">{{ entry.account.code }}</div>
                                                <div class="text-xs text-muted-foreground">{{ entry.account.name }}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm">{{ entry.description }}</div>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <span v-if="entry.debit_amount > 0" class="font-mono text-red-600">
                                                {{ formatCurrency(entry.debit_amount) }}
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <span v-if="entry.credit_amount > 0" class="font-mono text-green-600">
                                                {{ formatCurrency(entry.credit_amount) }}
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <!-- Transaction Totals -->
                            <div class="mt-3 flex justify-end gap-8 border-t pt-3">
                                <div class="text-right">
                                    <div class="text-xs text-muted-foreground">Total Débitos</div>
                                    <div class="font-mono font-semibold text-red-600">{{ formatCurrency(transaction.total_debit) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-muted-foreground">Total Créditos</div>
                                    <div class="font-mono font-semibold text-green-600">{{ formatCurrency(transaction.total_credit) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Period Summary -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Resumen del Período</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Período:</span>
                                <span class="font-medium">{{ report.period.description }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total transacciones:</span>
                                <span class="font-medium">{{ report.transaction_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total débitos:</span>
                                <span class="font-mono font-medium text-red-600">{{ formatCurrency(report.total_debits) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total créditos:</span>
                                <span class="font-mono font-medium text-green-600">{{ formatCurrency(report.total_credits) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Distribución por Estado</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Borrador:</span>
                                <Badge class="bg-gray-100 text-gray-800">{{ transactionSummary.byStatus.borrador }}</Badge>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Contabilizado:</span>
                                <Badge class="bg-green-100 text-green-800">{{ transactionSummary.byStatus.contabilizado }}</Badge>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Cancelado:</span>
                                <Badge class="bg-red-100 text-red-800">{{ transactionSummary.byStatus.cancelado }}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
