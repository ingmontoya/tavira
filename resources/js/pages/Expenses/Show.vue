<script setup lang="ts">
import ExpenseApprovalFlow from '@/components/ExpenseApprovalFlow.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Edit,
    CheckCircle,
    XCircle,
    CreditCard,
    Copy,
    Trash2,
    AlertTriangle,
    Receipt,
    Building,
    User,
    Calendar,
    AccountingTransaction
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { formatCurrency } from '../../utils';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Egresos',
        href: '/expenses',
    },
    {
        title: 'Detalle de Gasto',
    },
];

interface Account {
    id: number;
    code: string;
    name: string;
    full_name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface ExpenseCategory {
    id: number;
    name: string;
    description: string;
    color: string;
    icon: string;
    requires_approval: boolean;
}

interface AccountingTransactionEntry {
    id: number;
    description: string;
    debit_amount: number;
    credit_amount: number;
    account: Account;
}

interface AccountingTransaction {
    id: number;
    transaction_number: string;
    transaction_date: string;
    description: string;
    total_debit: number;
    total_credit: number;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    entries: AccountingTransactionEntry[];
    created_by: User;
    posted_by?: User;
    posted_at?: string;
}

interface Expense {
    id: number;
    expense_number: string;
    vendor_name: string;
    vendor_document?: string;
    vendor_email?: string;
    vendor_phone?: string;
    description: string;
    expense_date: string;
    due_date?: string;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    is_overdue: boolean;
    days_overdue: number;
    payment_method?: string;
    payment_reference?: string;
    paid_at?: string;
    notes?: string;
    created_at: string;
    approved_at?: string;
    council_approved_at?: string;
    can_be_approved: boolean;
    can_be_paid: boolean;
    can_be_cancelled: boolean;
    expense_category: ExpenseCategory;
    debit_account?: Account;
    credit_account?: Account;
    created_by: User;
    approved_by?: User;
    council_approved_by?: User;
    accounting_transactions: AccountingTransaction[];
}

const props = defineProps<{
    expense: Expense;
    paymentMethods: Record<string, string>;
    approvalSettings?: {
        approval_threshold_amount: number;
        council_approval_required: boolean;
    };
}>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Forms for actions
const approveForm = useForm({});
const rejectForm = useForm({
    reason: '',
});
const markAsPaidForm = useForm({
    payment_method: '',
    payment_reference: '',
});

// Dialog state
const isMarkAsPaidDialogOpen = ref(false);
const cancelForm = useForm({
    reason: '',
});

// Action methods
const approveExpense = () => {
    if (confirm('¿Está seguro de que desea aprobar este gasto?')) {
        approveForm.post(`/expenses/${props.expense.id}/approve`);
    }
};

const rejectExpense = () => {
    const reason = prompt('Motivo del rechazo:');
    if (reason) {
        rejectForm.reason = reason;
        rejectForm.post(`/expenses/${props.expense.id}/reject`);
    }
};

const openMarkAsPaidDialog = () => {
    // Reset form
    markAsPaidForm.reset();
    isMarkAsPaidDialogOpen.value = true;
};

const markAsPaid = () => {
    markAsPaidForm.post(`/expenses/${props.expense.id}/mark-as-paid`, {
        onSuccess: () => {
            isMarkAsPaidDialogOpen.value = false;
        }
    });
};

const cancelExpense = () => {
    const reason = prompt('Motivo de la cancelación:');
    if (reason !== null) { // Allow empty string but not null (user cancelled)
        cancelForm.reason = reason;
        if (confirm('¿Está seguro de que desea cancelar este gasto?')) {
            cancelForm.post(`/expenses/${props.expense.id}/cancel`);
        }
    }
};

const duplicateExpense = () => {
    router.post(`/expenses/${props.expense.id}/duplicate`);
};

const deleteExpense = () => {
    if (confirm('¿Está seguro de que desea eliminar este gasto? Esta acción no se puede deshacer.')) {
        router.delete(`/expenses/${props.expense.id}`);
    }
};
</script>

<template>
    <Head :title="`Gasto ${expense.expense_number}`" />

    <AppLayout :title="`Gasto ${expense.expense_number}`" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <ValidationErrors :errors="errors" />

        <!-- Success Alert -->
        <Alert v-if="flashSuccess" class="mb-6 border-green-200 bg-green-50">
            <CheckCircle class="h-4 w-4 text-green-600" />
            <AlertDescription class="text-green-800">
                {{ flashSuccess }}
            </AlertDescription>
        </Alert>

        <!-- Error Alert -->
        <Alert v-if="flashError" class="mb-6 border-red-200 bg-red-50">
            <XCircle class="h-4 w-4 text-red-600" />
            <AlertDescription class="text-red-800">
                {{ flashError }}
            </AlertDescription>
        </Alert>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-semibold tracking-tight">{{ expense.expense_number }}</h2>
                        <Badge :class="expense.status_badge.class">{{ expense.status_badge.text }}</Badge>
                        <AlertTriangle v-if="expense.is_overdue" class="w-5 h-5 text-red-500" :title="`Vencido ${expense.days_overdue} días`" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ expense.description }}
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <Button asChild variant="outline">
                        <Link href="/expenses">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>

                    <!-- Action buttons based on status -->
                    <template v-if="['borrador', 'pendiente', 'rechazado'].includes(expense.status)">
                        <Button asChild variant="outline" size="sm">
                            <Link :href="`/expenses/${expense.id}/edit`">
                                <Edit class="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                    </template>

                    <template v-if="expense.status === 'pendiente' && page.props.auth?.user?.role === 'administrator'">
                        <Button @click="approveExpense" variant="outline" size="sm">
                            <CheckCircle class="mr-2 h-4 w-4" />
                            Aprobar
                        </Button>
                        <Button @click="rejectExpense" variant="destructive" size="sm">
                            <XCircle class="mr-2 h-4 w-4" />
                            Rechazar
                        </Button>
                    </template>

                    <template v-if="expense.status === 'aprobado'">
                        <Button @click="openMarkAsPaidDialog" size="sm">
                            <CreditCard class="mr-2 h-4 w-4" />
                            Marcar como Pagado
                        </Button>
                    </template>

                    <Button @click="duplicateExpense" variant="outline" size="sm">
                        <Copy class="mr-2 h-4 w-4" />
                        Duplicar
                    </Button>

                    <template v-if="expense.can_be_cancelled">
                        <Button @click="cancelExpense" variant="outline" size="sm">
                            <XCircle class="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                    </template>

                    <template v-if="['borrador', 'rechazado'].includes(expense.status)">
                        <Button @click="deleteExpense" variant="destructive" size="sm">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                        </Button>
                    </template>
                </div>
            </div>

            <Tabs default-value="details" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="details">Detalles</TabsTrigger>
                    <TabsTrigger value="accounting">Contabilidad</TabsTrigger>
                    <TabsTrigger value="history">Historial</TabsTrigger>
                </TabsList>

                <TabsContent value="details" class="space-y-4">
                    <!-- Approval Flow Card - First Card -->
                    <Card class="mb-6">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12l2 2 4-4"/>
                                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                                    <path d="M21 12v6c0 .552-.448 1-1 1H3c-.552 0-1-.448-1-1v-6"/>
                                </svg>
                                Flujo de Aprobación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ExpenseApprovalFlow 
                                :expense="expense"
                                :approval-threshold="approvalSettings?.approval_threshold_amount || 4000000"
                                :council-approval-required="approvalSettings?.council_approval_required || true"
                            />
                        </CardContent>
                    </Card>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Main Details -->
                        <div class="lg:col-span-2 space-y-4">
                            <!-- Basic Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Receipt class="h-5 w-5" />
                                        Información del Gasto
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Número</h4>
                                            <p class="font-medium">{{ expense.expense_number }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Categoría</h4>
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: expense.expense_category.color }"></div>
                                                <span>{{ expense.expense_category.name }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground mb-1">Descripción</h4>
                                        <p>{{ expense.description }}</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha del Gasto</h4>
                                            <p>{{ new Date(expense.expense_date).toLocaleDateString('es-CO') }}</p>
                                        </div>
                                        <div v-if="expense.due_date">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Vencimiento</h4>
                                            <p class="flex items-center gap-2">
                                                {{ new Date(expense.due_date).toLocaleDateString('es-CO') }}
                                                <AlertTriangle v-if="expense.is_overdue" class="w-4 h-4 text-red-500" />
                                            </p>
                                        </div>
                                    </div>

                                    <Separator />

                                    <!-- Amount breakdown -->
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Subtotal:</span>
                                            <span class="font-medium">{{ formatCurrency(expense.subtotal) }}</span>
                                        </div>
                                        <div v-if="expense.tax_amount > 0" class="flex justify-between">
                                            <span class="text-muted-foreground">Impuestos:</span>
                                            <span class="font-medium">{{ formatCurrency(expense.tax_amount) }}</span>
                                        </div>
                                        <Separator />
                                        <div class="flex justify-between text-lg font-bold">
                                            <span>Total:</span>
                                            <span>{{ formatCurrency(expense.total_amount) }}</span>
                                        </div>
                                    </div>

                                    <div v-if="expense.notes" class="mt-4">
                                        <h4 class="text-sm font-medium text-muted-foreground mb-1">Notas</h4>
                                        <p class="text-sm bg-muted p-3 rounded-md whitespace-pre-wrap">{{ expense.notes }}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Vendor Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Building class="h-5 w-5" />
                                        Información del Proveedor
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Nombre</h4>
                                            <p class="font-medium">{{ expense.vendor_name }}</p>
                                        </div>
                                        <div v-if="expense.vendor_document">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Documento</h4>
                                            <p>{{ expense.vendor_document }}</p>
                                        </div>
                                        <div v-if="expense.vendor_email">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Email</h4>
                                            <p>
                                                <a :href="`mailto:${expense.vendor_email}`" class="text-blue-600 hover:underline">
                                                    {{ expense.vendor_email }}
                                                </a>
                                            </p>
                                        </div>
                                        <div v-if="expense.vendor_phone">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Teléfono</h4>
                                            <p>
                                                <a :href="`tel:${expense.vendor_phone}`" class="text-blue-600 hover:underline">
                                                    {{ expense.vendor_phone }}
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Payment Information (if paid) -->
                            <Card v-if="expense.status === 'pagado'">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <CreditCard class="h-5 w-5" />
                                        Información de Pago
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Pago</h4>
                                            <p class="font-medium">
                                                {{ expense.paid_at ? new Date(expense.paid_at).toLocaleDateString('es-CO') : 'N/A' }}
                                            </p>
                                        </div>
                                        <div v-if="expense.payment_method">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Método de Pago</h4>
                                            <p>{{ expense.payment_method }}</p>
                                        </div>
                                        <div v-if="expense.payment_reference" class="col-span-2">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Referencia</h4>
                                            <p class="font-mono text-sm">{{ expense.payment_reference }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-4">
                            <!-- Status and Workflow -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-sm">Estado y Flujo</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-muted-foreground">Estado:</span>
                                        <Badge :class="expense.status_badge.class">{{ expense.status_badge.text }}</Badge>
                                    </div>

                                    <Separator />

                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center gap-2">
                                            <User class="h-3 w-3" />
                                            <span class="text-muted-foreground">Creado por:</span>
                                            <span class="font-medium">{{ expense.created_by.name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-3 w-3" />
                                            <span class="text-muted-foreground">Creado:</span>
                                            <span>{{ new Date(expense.created_at).toLocaleDateString('es-CO') }}</span>
                                        </div>
                                        <div v-if="expense.approved_by && expense.approved_at" class="flex items-center gap-2">
                                            <CheckCircle class="h-3 w-3 text-green-600" />
                                            <span class="text-muted-foreground">Aprobado por:</span>
                                            <span class="font-medium">{{ expense.approved_by.name }}</span>
                                        </div>
                                        <div v-if="expense.approved_at" class="flex items-center gap-2 ml-5">
                                            <span class="text-muted-foreground">Fecha:</span>
                                            <span>{{ new Date(expense.approved_at).toLocaleDateString('es-CO') }}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Accounting Information -->
                            <Card v-if="expense.debit_account && expense.credit_account">
                                <CardHeader>
                                    <CardTitle class="text-sm">Mapeo Contable</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div>
                                        <h5 class="text-xs font-medium text-muted-foreground mb-1">Cuenta a Debitar</h5>
                                        <p class="text-sm">{{ expense.debit_account.full_name }}</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-medium text-muted-foreground mb-1">Cuenta a Acreditar</h5>
                                        <p class="text-sm">{{ expense.credit_account.full_name }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent value="accounting" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Transacciones Contables</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="expense.accounting_transactions.length === 0" class="text-center py-8 text-muted-foreground">
                                No hay transacciones contables asociadas
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="transaction in expense.accounting_transactions" :key="transaction.id" class="border rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-medium">{{ transaction.transaction_number }}</h4>
                                            <Badge :class="transaction.status_badge.class">{{ transaction.status_badge.text }}</Badge>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ new Date(transaction.transaction_date).toLocaleDateString('es-CO') }}
                                        </div>
                                    </div>

                                    <p class="text-sm text-muted-foreground mb-3">{{ transaction.description }}</p>

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
                                            <TableRow v-for="entry in transaction.entries" :key="entry.id">
                                                <TableCell class="font-medium">{{ entry.account.full_name }}</TableCell>
                                                <TableCell>{{ entry.description }}</TableCell>
                                                <TableCell class="text-right">
                                                    {{ entry.debit_amount > 0 ? formatCurrency(entry.debit_amount) : '-' }}
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    {{ entry.credit_amount > 0 ? formatCurrency(entry.credit_amount) : '-' }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>

                                    <div class="flex justify-between items-center mt-3 pt-3 border-t text-sm font-medium">
                                        <span>Totales:</span>
                                        <div class="flex gap-8">
                                            <span>{{ formatCurrency(transaction.total_debit) }}</span>
                                            <span>{{ formatCurrency(transaction.total_credit) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="history" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de Cambios</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                    <div>
                                        <p class="text-sm font-medium">Gasto creado</p>
                                        <p class="text-xs text-muted-foreground">
                                            Por {{ expense.created_by.name }} el {{ new Date(expense.created_at).toLocaleString('es-CO') }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="expense.approved_by && expense.approved_at" class="flex items-start gap-3">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                    <div>
                                        <p class="text-sm font-medium">Gasto aprobado</p>
                                        <p class="text-xs text-muted-foreground">
                                            Por {{ expense.approved_by.name }} el {{ new Date(expense.approved_at).toLocaleString('es-CO') }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="expense.paid_at" class="flex items-start gap-3">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                                    <div>
                                        <p class="text-sm font-medium">Gasto marcado como pagado</p>
                                        <p class="text-xs text-muted-foreground">
                                            El {{ new Date(expense.paid_at).toLocaleString('es-CO') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Mark as Paid Dialog -->
        <Dialog v-model:open="isMarkAsPaidDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <form @submit.prevent="markAsPaid">
                    <DialogHeader>
                        <DialogTitle>Marcar como Pagado</DialogTitle>
                        <DialogDescription>
                            Registre la información del pago para el gasto {{ expense.expense_number }}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="payment_method">Método de Pago (opcional)</Label>
                            <Select v-model="markAsPaidForm.payment_method">
                                <SelectTrigger id="payment_method">
                                    <SelectValue placeholder="Seleccionar método de pago" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Sin especificar</SelectItem>
                                    <SelectItem v-for="(label, value) in paymentMethods" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="payment_reference">Referencia de Pago (opcional)</Label>
                            <Input
                                id="payment_reference"
                                v-model="markAsPaidForm.payment_reference"
                                placeholder="ej: Comprobante, Número de cheque, Referencia"
                                maxlength="255"
                            />
                        </div>
                        <div class="rounded-md bg-muted p-3">
                            <div class="text-sm font-medium text-muted-foreground mb-1">Monto a Pagar</div>
                            <div class="text-lg font-bold">{{ formatCurrency(expense.total_amount) }}</div>
                        </div>
                    </div>
                    
                    <DialogFooter>
                        <Button 
                            type="button" 
                            variant="outline" 
                            @click="isMarkAsPaidDialogOpen = false"
                            :disabled="markAsPaidForm.processing"
                        >
                            Cancelar
                        </Button>
                        <Button 
                            type="submit" 
                            :disabled="markAsPaidForm.processing"
                        >
                            <CreditCard class="mr-2 h-4 w-4" />
                            {{ markAsPaidForm.processing ? 'Procesando...' : 'Marcar como Pagado' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
    </AppLayout>
</template>
