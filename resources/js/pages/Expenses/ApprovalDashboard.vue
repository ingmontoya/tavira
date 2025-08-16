<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Building, Calendar, CheckCircle, Clock, CreditCard, Eye, TrendingUp, Users, XCircle } from 'lucide-vue-next';
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
        title: 'Dashboard de Aprobaciones',
    },
];

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
}

interface Expense {
    id: number;
    expense_number: string;
    vendor_name: string;
    description: string;
    expense_date: string;
    due_date?: string;
    total_amount: number;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    is_overdue: boolean;
    days_overdue: number;
    created_at: string;
    approved_at?: string;
    expense_category: ExpenseCategory;
    created_by: User;
    approved_by?: User;
}

interface Stats {
    pending_regular_count: number;
    pending_council_count: number;
    overdue_count: number;
    pending_total_amount: number;
    council_pending_total_amount: number;
    overdue_total_amount: number;
}

const props = defineProps<{
    pendingApprovals: Expense[];
    councilPendingApprovals: Expense[];
    overdueExpenses: Expense[];
    recentApprovals: Expense[];
    stats: Stats;
}>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Selected expenses for bulk actions
const selectedExpenses = ref<number[]>([]);
const selectAll = ref(false);

// Dialog states
const showBulkApproveDialog = ref(false);
const showBulkRejectDialog = ref(false);
const showApproveDialog = ref(false);
const showCouncilApproveDialog = ref(false);
const showNoSelectionDialog = ref(false);
const noSelectionMessage = ref('');
const pendingExpenseId = ref<number | null>(null);
const rejectReason = ref('');

// Forms
const bulkApproveForm = useForm({
    expense_ids: [] as number[],
});

const bulkRejectForm = useForm({
    expense_ids: [] as number[],
    reason: '',
});

// Computed properties for better reactivity
const hasSelectedExpenses = computed(() => selectedExpenses.value.length > 0);
const selectedCount = computed(() => selectedExpenses.value.length);

// Methods
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedExpenses.value = [];
    } else {
        selectedExpenses.value = props.pendingApprovals.map((expense) => expense.id);
    }
    selectAll.value = !selectAll.value;
};

const toggleExpenseSelection = (expenseId: number) => {
    const index = selectedExpenses.value.indexOf(expenseId);
    if (index > -1) {
        selectedExpenses.value.splice(index, 1);
    } else {
        selectedExpenses.value.push(expenseId);
    }
    selectAll.value = selectedExpenses.value.length === props.pendingApprovals.length;
};

const bulkApprove = () => {
    if (selectedExpenses.value.length === 0) {
        noSelectionMessage.value = 'Seleccione al menos un gasto para aprobar';
        showNoSelectionDialog.value = true;
        return;
    }
    showBulkApproveDialog.value = true;
};

const confirmBulkApprove = () => {
    bulkApproveForm.expense_ids = [...selectedExpenses.value];
    bulkApproveForm.post('/expenses/bulk-approve', {
        onSuccess: () => {
            selectedExpenses.value = [];
            selectAll.value = false;
            showBulkApproveDialog.value = false;
        },
    });
};

const bulkReject = () => {
    if (selectedExpenses.value.length === 0) {
        noSelectionMessage.value = 'Seleccione al menos un gasto para rechazar';
        showNoSelectionDialog.value = true;
        return;
    }
    rejectReason.value = '';
    showBulkRejectDialog.value = true;
};

const confirmBulkReject = () => {
    if (!rejectReason.value.trim()) {
        return;
    }

    bulkRejectForm.expense_ids = [...selectedExpenses.value];
    bulkRejectForm.reason = rejectReason.value;
    bulkRejectForm.post('/expenses/bulk-reject', {
        onSuccess: () => {
            selectedExpenses.value = [];
            selectAll.value = false;
            showBulkRejectDialog.value = false;
            rejectReason.value = '';
        },
    });
};

const approveExpense = (expenseId: number) => {
    pendingExpenseId.value = expenseId;
    showApproveDialog.value = true;
};

const confirmApproveExpense = () => {
    if (pendingExpenseId.value) {
        router.post(`/expenses/${pendingExpenseId.value}/approve`);
        showApproveDialog.value = false;
        pendingExpenseId.value = null;
    }
};

const approveByCouncil = (expenseId: number) => {
    pendingExpenseId.value = expenseId;
    showCouncilApproveDialog.value = true;
};

const confirmApproveByCouncil = () => {
    if (pendingExpenseId.value) {
        router.post(`/expenses/${pendingExpenseId.value}/approve-council`);
        showCouncilApproveDialog.value = false;
        pendingExpenseId.value = null;
    }
};
</script>

<template>
    <Head title="Dashboard de Aprobaciones" />

    <AppLayout title="Dashboard de Aprobaciones" :breadcrumbs="breadcrumbs">
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

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h2 class="text-2xl font-semibold tracking-tight">Dashboard de Aprobaciones</h2>
                    <p class="text-sm text-muted-foreground">Gestiona las aprobaciones pendientes y supervisa el flujo de gastos</p>
                </div>
                <Button asChild variant="outline">
                    <Link href="/expenses"> Ver Todos los Gastos </Link>
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Aprobación Regular</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_regular_count }}</div>
                        <p class="text-xs text-muted-foreground">{{ formatCurrency(stats.pending_total_amount) }} total</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Aprobación Concejo</CardTitle>
                        <Building class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_council_count }}</div>
                        <p class="text-xs text-muted-foreground">{{ formatCurrency(stats.council_pending_total_amount) }} total</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Gastos Vencidos</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.overdue_count }}</div>
                        <p class="text-xs text-muted-foreground">{{ formatCurrency(stats.overdue_total_amount) }} total</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Aprobados (7 días)</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ recentApprovals.length }}</div>
                        <p class="text-xs text-muted-foreground">Últimas aprobaciones</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs for different approval queues -->
            <Tabs default-value="pending" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="pending"> Aprobación Regular ({{ stats.pending_regular_count }}) </TabsTrigger>
                    <TabsTrigger value="council"> Aprobación Concejo ({{ stats.pending_council_count }}) </TabsTrigger>
                    <TabsTrigger value="overdue"> Vencidos ({{ stats.overdue_count }}) </TabsTrigger>
                    <TabsTrigger value="recent"> Recientes </TabsTrigger>
                </TabsList>

                <!-- Pending Regular Approvals -->
                <TabsContent value="pending" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Gastos Pendientes de Aprobación Regular</CardTitle>
                                <div class="flex items-center space-x-2" v-if="pendingApprovals.length > 0">
                                    <Button
                                        @click="bulkApprove"
                                        :disabled="bulkApproveForm.processing || !hasSelectedExpenses"
                                        size="sm"
                                        class="bg-green-600 hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                                    >
                                        <CheckCircle class="mr-2 h-4 w-4" />
                                        Aprobar Seleccionados ({{ selectedCount }})
                                    </Button>
                                    <Button
                                        @click="bulkReject"
                                        :disabled="bulkRejectForm.processing || !hasSelectedExpenses"
                                        size="sm"
                                        variant="destructive"
                                        class="disabled:cursor-not-allowed disabled:bg-gray-400"
                                    >
                                        <XCircle class="mr-2 h-4 w-4" />
                                        Rechazar Seleccionados ({{ selectedCount }})
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="pendingApprovals.length === 0" class="py-8 text-center text-muted-foreground">
                                <CheckCircle class="mx-auto mb-4 h-12 w-12 text-green-500" />
                                <h3 class="mb-2 text-lg font-medium">¡Todo al día!</h3>
                                <p>No hay gastos pendientes de aprobación regular.</p>
                            </div>
                            <div v-else>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-12">
                                                <Checkbox :checked="true" @update:checked="toggleSelectAll" />
                                            </TableHead>
                                            <TableHead>Gasto</TableHead>
                                            <TableHead>Categoría</TableHead>
                                            <TableHead>Proveedor</TableHead>
                                            <TableHead>Monto</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead>Creado por</TableHead>
                                            <TableHead>Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="expense in pendingApprovals"
                                            :key="expense.id"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="router.visit(`/expenses/${expense.id}`)"
                                        >
                                            <TableCell @click.stop>
                                                <Checkbox
                                                    :checked="selectedExpenses.includes(expense.id)"
                                                    @update:checked="toggleExpenseSelection(expense.id)"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <div class="font-medium">{{ expense.expense_number }}</div>
                                                    <div class="text-sm text-muted-foreground">{{ expense.description }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="h-3 w-3 rounded-full"
                                                        :style="{ backgroundColor: expense.expense_category.color }"
                                                    ></div>
                                                    <span>{{ expense.expense_category.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{{ expense.vendor_name }}</TableCell>
                                            <TableCell class="font-mono">{{ formatCurrency(expense.total_amount) }}</TableCell>
                                            <TableCell>{{ new Date(expense.expense_date).toLocaleDateString('es-CO') }}</TableCell>
                                            <TableCell>{{ expense.created_by.name }}</TableCell>
                                            <TableCell @click.stop>
                                                <div class="flex items-center space-x-2">
                                                    <Button @click="approveExpense(expense.id)" size="sm" variant="outline">
                                                        <CheckCircle class="mr-1 h-3 w-3" />
                                                        Aprobar
                                                    </Button>
                                                    <Button asChild variant="ghost" size="sm">
                                                        <Link :href="`/expenses/${expense.id}`">
                                                            <Eye class="h-3 w-3" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Council Pending Approvals -->
                <TabsContent value="council" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Gastos Pendientes de Aprobación del Concejo</CardTitle>
                            <p class="text-sm text-muted-foreground">Gastos que superan los $4,000,000 COP y requieren aprobación del concejo</p>
                        </CardHeader>
                        <CardContent>
                            <div v-if="councilPendingApprovals.length === 0" class="py-8 text-center text-muted-foreground">
                                <Building class="mx-auto mb-4 h-12 w-12 text-blue-500" />
                                <h3 class="mb-2 text-lg font-medium">Sin gastos pendientes</h3>
                                <p>No hay gastos esperando aprobación del concejo.</p>
                            </div>
                            <div v-else>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Gasto</TableHead>
                                            <TableHead>Categoría</TableHead>
                                            <TableHead>Proveedor</TableHead>
                                            <TableHead>Monto</TableHead>
                                            <TableHead>Aprobado por</TableHead>
                                            <TableHead>Fecha Aprobación</TableHead>
                                            <TableHead>Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="expense in councilPendingApprovals"
                                            :key="expense.id"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="router.visit(`/expenses/${expense.id}`)"
                                        >
                                            <TableCell>
                                                <div>
                                                    <div class="font-medium">{{ expense.expense_number }}</div>
                                                    <div class="text-sm text-muted-foreground">{{ expense.description }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="h-3 w-3 rounded-full"
                                                        :style="{ backgroundColor: expense.expense_category.color }"
                                                    ></div>
                                                    <span>{{ expense.expense_category.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{{ expense.vendor_name }}</TableCell>
                                            <TableCell class="font-mono font-bold">{{ formatCurrency(expense.total_amount) }}</TableCell>
                                            <TableCell>{{ expense.approved_by?.name || 'N/A' }}</TableCell>
                                            <TableCell>
                                                {{ expense.approved_at ? new Date(expense.approved_at).toLocaleDateString('es-CO') : 'N/A' }}
                                            </TableCell>
                                            <TableCell @click.stop>
                                                <div class="flex items-center space-x-2">
                                                    <Button @click="approveByCouncil(expense.id)" size="sm">
                                                        <Building class="mr-1 h-3 w-3" />
                                                        Aprobar por Concejo
                                                    </Button>
                                                    <Button asChild variant="ghost" size="sm">
                                                        <Link :href="`/expenses/${expense.id}`">
                                                            <Eye class="h-3 w-3" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Overdue Expenses -->
                <TabsContent value="overdue" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-red-600">
                                <AlertTriangle class="h-5 w-5" />
                                Gastos Vencidos
                            </CardTitle>
                            <p class="text-sm text-muted-foreground">Gastos aprobados pero con fecha de pago vencida</p>
                        </CardHeader>
                        <CardContent>
                            <div v-if="overdueExpenses.length === 0" class="py-8 text-center text-muted-foreground">
                                <Calendar class="mx-auto mb-4 h-12 w-12 text-green-500" />
                                <h3 class="mb-2 text-lg font-medium">¡Sin gastos vencidos!</h3>
                                <p>Todos los gastos están al día con sus fechas de vencimiento.</p>
                            </div>
                            <div v-else>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Gasto</TableHead>
                                            <TableHead>Categoría</TableHead>
                                            <TableHead>Proveedor</TableHead>
                                            <TableHead>Monto</TableHead>
                                            <TableHead>Vencimiento</TableHead>
                                            <TableHead>Días Vencido</TableHead>
                                            <TableHead>Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="expense in overdueExpenses"
                                            :key="expense.id"
                                            class="cursor-pointer bg-red-50 hover:bg-red-100/75"
                                            @click="router.visit(`/expenses/${expense.id}`)"
                                        >
                                            <TableCell>
                                                <div>
                                                    <div class="font-medium">{{ expense.expense_number }}</div>
                                                    <div class="text-sm text-muted-foreground">{{ expense.description }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="h-3 w-3 rounded-full"
                                                        :style="{ backgroundColor: expense.expense_category.color }"
                                                    ></div>
                                                    <span>{{ expense.expense_category.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{{ expense.vendor_name }}</TableCell>
                                            <TableCell class="font-mono">{{ formatCurrency(expense.total_amount) }}</TableCell>
                                            <TableCell>{{
                                                expense.due_date ? new Date(expense.due_date).toLocaleDateString('es-CO') : 'N/A'
                                            }}</TableCell>
                                            <TableCell>
                                                <Badge variant="destructive">{{ expense.days_overdue }} días</Badge>
                                            </TableCell>
                                            <TableCell @click.stop>
                                                <div class="flex items-center space-x-2">
                                                    <Button asChild size="sm">
                                                        <Link :href="`/expenses/${expense.id}`">
                                                            <CreditCard class="mr-1 h-3 w-3" />
                                                            Marcar como Pagado
                                                        </Link>
                                                    </Button>
                                                    <Button asChild variant="ghost" size="sm">
                                                        <Link :href="`/expenses/${expense.id}`">
                                                            <Eye class="h-3 w-3" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Recent Approvals -->
                <TabsContent value="recent" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-green-600">
                                <CheckCircle class="h-5 w-5" />
                                Aprobaciones Recientes
                            </CardTitle>
                            <p class="text-sm text-muted-foreground">Gastos aprobados en los últimos 7 días</p>
                        </CardHeader>
                        <CardContent>
                            <div v-if="recentApprovals.length === 0" class="py-8 text-center text-muted-foreground">
                                <Users class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="mb-2 text-lg font-medium">Sin aprobaciones recientes</h3>
                                <p>No se han aprobado gastos en los últimos 7 días.</p>
                            </div>
                            <div v-else>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Gasto</TableHead>
                                            <TableHead>Categoría</TableHead>
                                            <TableHead>Proveedor</TableHead>
                                            <TableHead>Monto</TableHead>
                                            <TableHead>Aprobado por</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead>Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="expense in recentApprovals"
                                            :key="expense.id"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="router.visit(`/expenses/${expense.id}`)"
                                        >
                                            <TableCell>
                                                <div>
                                                    <div class="font-medium">{{ expense.expense_number }}</div>
                                                    <div class="text-sm text-muted-foreground">{{ expense.description }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="h-3 w-3 rounded-full"
                                                        :style="{ backgroundColor: expense.expense_category.color }"
                                                    ></div>
                                                    <span>{{ expense.expense_category.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{{ expense.vendor_name }}</TableCell>
                                            <TableCell class="font-mono">{{ formatCurrency(expense.total_amount) }}</TableCell>
                                            <TableCell>{{ expense.approved_by?.name || 'Auto' }}</TableCell>
                                            <TableCell>{{
                                                expense.approved_at ? new Date(expense.approved_at).toLocaleDateString('es-CO') : 'N/A'
                                            }}</TableCell>
                                            <TableCell @click.stop>
                                                <Button asChild variant="ghost" size="sm">
                                                    <Link :href="`/expenses/${expense.id}`">
                                                        <Eye class="h-3 w-3" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Bulk Approve Dialog -->
        <AlertDialog v-model:open="showBulkApproveDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirmar Aprobación</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Está seguro de aprobar {{ selectedExpenses.length }} gastos seleccionados? Esta acción no se puede deshacer.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmBulkApprove" :disabled="bulkApproveForm.processing">
                        {{ bulkApproveForm.processing ? 'Aprobando...' : 'Aprobar' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Bulk Reject Dialog -->
        <Dialog v-model:open="showBulkRejectDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rechazar Gastos Seleccionados</DialogTitle>
                    <DialogDescription> Ingrese el motivo del rechazo para {{ selectedExpenses.length }} gastos seleccionados. </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    <div>
                        <Label for="reject-reason">Motivo del rechazo *</Label>
                        <Input id="reject-reason" v-model="rejectReason" placeholder="Ingrese el motivo del rechazo..." class="mt-1" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showBulkRejectDialog = false">Cancelar</Button>
                    <Button variant="destructive" @click="confirmBulkReject" :disabled="bulkRejectForm.processing || !rejectReason.trim()">
                        {{ bulkRejectForm.processing ? 'Rechazando...' : 'Rechazar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Single Approve Dialog -->
        <AlertDialog v-model:open="showApproveDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirmar Aprobación</AlertDialogTitle>
                    <AlertDialogDescription> ¿Está seguro de aprobar este gasto? Esta acción no se puede deshacer. </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmApproveExpense"> Aprobar </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Council Approve Dialog -->
        <AlertDialog v-model:open="showCouncilApproveDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirmar Aprobación por Concejo</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Está seguro de aprobar este gasto por el concejo? Esta acción marcará el gasto como definitivamente aprobado.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmApproveByCouncil"> Aprobar por Concejo </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- No Selection Dialog -->
        <AlertDialog v-model:open="showNoSelectionDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Selección Requerida</AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ noSelectionMessage }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogAction @click="showNoSelectionDialog = false"> Entendido </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
