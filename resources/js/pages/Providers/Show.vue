<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Building2,
    Calendar,
    CheckCircle,
    Copy,
    Edit,
    Eye,
    FileText,
    Mail,
    MapPin,
    Phone,
    Receipt,
    Trash2,
    User,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { formatCurrency } from '../../utils';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Proveedores',
        href: '/providers',
    },
    {
        title: 'Detalle de Proveedor',
    },
];

interface Provider {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    country?: string;
    contact_name?: string;
    contact_phone?: string;
    contact_email?: string;
    notes?: string;
    tax_regime?: string;
    is_active: boolean;
    status_badge: {
        text: string;
        class: string;
    };
    full_contact_info: string;
    expenses_count: number;
    total_expenses: number;
    last_expense_date?: string;
    created_at: string;
    updated_at: string;
}

interface Expense {
    id: number;
    expense_number: string;
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
    expense_category: {
        id: number;
        name: string;
        color: string;
        icon: string;
    };
}

interface Stats {
    total_expenses: number;
    pending_expenses: number;
    approved_expenses: number;
    paid_expenses: number;
    overdue_expenses: number;
    total_amount: number;
    average_amount: number;
}

const props = defineProps<{
    provider: Provider;
    expenses?: {
        data: Expense[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats?: Stats;
}>();

// Get page data for errors and flash messages
const page = usePage();
const { error } = useToast();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Forms for actions
const toggleStatusForm = useForm({
    is_active: props.provider.is_active,
});

// Action methods
const toggleProviderStatus = () => {
    const action = props.provider.is_active ? 'desactivar' : 'activar';
    if (confirm(`¿Está seguro de que desea ${action} este proveedor?`)) {
        toggleStatusForm.is_active = !props.provider.is_active;
        toggleStatusForm.post(`/providers/${props.provider.id}/toggle-status`);
    }
};

const duplicateProvider = () => {
    router.post(`/providers/${props.provider.id}/duplicate`);
};

const deleteProvider = () => {
    if (props.provider.expenses_count > 0) {
        error('No se puede eliminar un proveedor con gastos registrados.');
        return;
    }

    if (confirm('¿Está seguro de que desea eliminar este proveedor? Esta acción no se puede deshacer.')) {
        router.delete(`/providers/${props.provider.id}`);
    }
};

const viewExpense = (expenseId: number) => {
    router.visit(`/expenses/${expenseId}`);
};

// Computed properties
const hasContactInfo = computed(() => {
    return props.provider.email || props.provider.phone || props.provider.contact_email || props.provider.contact_phone;
});

const hasLocationInfo = computed(() => {
    return props.provider.address || props.provider.city || props.provider.country;
});
</script>

<template>
    <Head :title="`Proveedor ${provider.name}`" />

    <AppLayout :title="`Proveedor ${provider.name}`" :breadcrumbs="breadcrumbs">
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
                            <h2 class="text-2xl font-semibold tracking-tight">{{ provider.name }}</h2>
                            <Badge :class="provider.status_badge.class">{{ provider.status_badge.text }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">{{ provider.document_type }}: {{ provider.document_number }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button asChild variant="outline">
                            <Link href="/providers">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>

                        <Button asChild variant="outline" size="sm">
                            <Link :href="`/providers/${provider.id}/edit`">
                                <Edit class="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>

                        <Button @click="toggleProviderStatus" variant="outline" size="sm">
                            <component :is="provider.is_active ? XCircle : CheckCircle" class="mr-2 h-4 w-4" />
                            {{ provider.is_active ? 'Desactivar' : 'Activar' }}
                        </Button>

                        <Button @click="duplicateProvider" variant="outline" size="sm">
                            <Copy class="mr-2 h-4 w-4" />
                            Duplicar
                        </Button>

                        <template v-if="provider.expenses_count === 0">
                            <Button @click="deleteProvider" variant="destructive" size="sm">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Eliminar
                            </Button>
                        </template>
                    </div>
                </div>

                <Tabs default-value="details" class="space-y-4">
                    <TabsList>
                        <TabsTrigger value="details">Información</TabsTrigger>
                        <TabsTrigger value="expenses">Gastos ({{ provider.expenses_count || 0 }})</TabsTrigger>
                        <TabsTrigger value="stats">Estadísticas</TabsTrigger>
                    </TabsList>

                    <TabsContent value="details" class="space-y-4">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <!-- Main Details -->
                            <div class="space-y-4 lg:col-span-2">
                                <!-- Basic Information -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Building2 class="h-5 w-5" />
                                            Información Básica
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Nombre/Razón Social</h4>
                                                <p class="font-medium">{{ provider.name }}</p>
                                            </div>
                                            <div>
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Tipo de Documento</h4>
                                                <p>{{ provider.document_type }}</p>
                                            </div>
                                            <div>
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Número de Documento</h4>
                                                <p class="font-mono">{{ provider.document_number }}</p>
                                            </div>
                                            <div v-if="provider.tax_regime">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Régimen Tributario</h4>
                                                <p>{{ provider.tax_regime }}</p>
                                            </div>
                                        </div>

                                        <div v-if="provider.notes">
                                            <Separator class="my-4" />
                                            <h4 class="mb-1 text-sm font-medium text-muted-foreground">Notas</h4>
                                            <p class="rounded-md bg-muted p-3 text-sm whitespace-pre-wrap">{{ provider.notes }}</p>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Contact Information -->
                                <Card v-if="hasContactInfo">
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Phone class="h-5 w-5" />
                                            Información de Contacto
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-3">
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div v-if="provider.email">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Email Principal</h4>
                                                <p>
                                                    <a
                                                        :href="`mailto:${provider.email}`"
                                                        class="flex items-center gap-1 text-blue-600 hover:underline"
                                                    >
                                                        <Mail class="h-3 w-3" />
                                                        {{ provider.email }}
                                                    </a>
                                                </p>
                                            </div>
                                            <div v-if="provider.phone">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Teléfono Principal</h4>
                                                <p>
                                                    <a :href="`tel:${provider.phone}`" class="flex items-center gap-1 text-blue-600 hover:underline">
                                                        <Phone class="h-3 w-3" />
                                                        {{ provider.phone }}
                                                    </a>
                                                </p>
                                            </div>
                                            <div v-if="provider.contact_name">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Persona de Contacto</h4>
                                                <p class="flex items-center gap-1">
                                                    <User class="h-3 w-3" />
                                                    {{ provider.contact_name }}
                                                </p>
                                            </div>
                                            <div v-if="provider.contact_phone">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Teléfono de Contacto</h4>
                                                <p>
                                                    <a
                                                        :href="`tel:${provider.contact_phone}`"
                                                        class="flex items-center gap-1 text-blue-600 hover:underline"
                                                    >
                                                        <Phone class="h-3 w-3" />
                                                        {{ provider.contact_phone }}
                                                    </a>
                                                </p>
                                            </div>
                                            <div v-if="provider.contact_email" class="md:col-span-2">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Email de Contacto</h4>
                                                <p>
                                                    <a
                                                        :href="`mailto:${provider.contact_email}`"
                                                        class="flex items-center gap-1 text-blue-600 hover:underline"
                                                    >
                                                        <Mail class="h-3 w-3" />
                                                        {{ provider.contact_email }}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Location Information -->
                                <Card v-if="hasLocationInfo">
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <MapPin class="h-5 w-5" />
                                            Información de Ubicación
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-3">
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div v-if="provider.address" class="md:col-span-2">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Dirección</h4>
                                                <p>{{ provider.address }}</p>
                                            </div>
                                            <div v-if="provider.city">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">Ciudad</h4>
                                                <p>{{ provider.city }}</p>
                                            </div>
                                            <div v-if="provider.country">
                                                <h4 class="mb-1 text-sm font-medium text-muted-foreground">País</h4>
                                                <p>{{ provider.country }}</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Sidebar -->
                            <div class="space-y-4">
                                <!-- Status and Summary -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="text-sm">Estado y Resumen</CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-muted-foreground">Estado:</span>
                                            <Badge :class="provider.status_badge.class">{{ provider.status_badge.text }}</Badge>
                                        </div>

                                        <Separator />

                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-muted-foreground">Gastos registrados:</span>
                                                <span class="font-medium">{{ provider.expenses_count }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-muted-foreground">Total facturado:</span>
                                                <span class="font-medium">{{ formatCurrency(provider.total_expenses) }}</span>
                                            </div>
                                            <div v-if="provider.last_expense_date">
                                                <span class="text-muted-foreground">Último gasto:</span>
                                                <span class="block font-medium">{{
                                                    new Date(provider.last_expense_date).toLocaleDateString('es-CO')
                                                }}</span>
                                            </div>
                                        </div>

                                        <Separator />

                                        <div class="space-y-2 text-sm">
                                            <div class="flex items-center gap-2">
                                                <Calendar class="h-3 w-3" />
                                                <span class="text-muted-foreground">Registrado:</span>
                                                <span>{{ new Date(provider.created_at).toLocaleDateString('es-CO') }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <FileText class="h-3 w-3" />
                                                <span class="text-muted-foreground">Actualizado:</span>
                                                <span>{{ new Date(provider.updated_at).toLocaleDateString('es-CO') }}</span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Quick Actions -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="text-sm">Acciones Rápidas</CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-2">
                                        <Button asChild variant="outline" size="sm" class="w-full justify-start">
                                            <Link
                                                href="/expenses/create"
                                                :data="{
                                                    vendor_name: provider.name,
                                                    vendor_document: provider.document_number,
                                                    vendor_email: provider.email,
                                                    vendor_phone: provider.phone,
                                                }"
                                            >
                                                <Receipt class="mr-2 h-4 w-4" />
                                                Crear Gasto
                                            </Link>
                                        </Button>
                                        <Button @click="duplicateProvider" variant="outline" size="sm" class="w-full justify-start">
                                            <Copy class="mr-2 h-4 w-4" />
                                            Duplicar Proveedor
                                        </Button>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="expenses" class="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Historial de Gastos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="!expenses || expenses.data.length === 0" class="py-8 text-center text-muted-foreground">
                                    No hay gastos registrados para este proveedor
                                </div>
                                <div v-else>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Número</TableHead>
                                                <TableHead>Descripción</TableHead>
                                                <TableHead>Categoría</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead class="text-right">Valor</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="expense in expenses?.data || []" :key="expense.id">
                                                <TableCell class="font-medium">{{ expense.expense_number }}</TableCell>
                                                <TableCell>
                                                    <div class="max-w-[200px] truncate" :title="expense.description">
                                                        {{ expense.description }}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="h-3 w-3 rounded-full"
                                                            :style="{ backgroundColor: expense.expense_category.color }"
                                                        ></div>
                                                        <span class="text-sm">{{ expense.expense_category.name }}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>{{ new Date(expense.expense_date).toLocaleDateString('es-CO') }}</TableCell>
                                                <TableCell class="text-right font-mono">{{ formatCurrency(expense.total_amount) }}</TableCell>
                                                <TableCell>
                                                    <div class="flex items-center gap-2">
                                                        <Badge :class="expense.status_badge.class">{{ expense.status_badge.text }}</Badge>
                                                        <AlertTriangle
                                                            v-if="expense.is_overdue"
                                                            class="h-4 w-4 text-red-500"
                                                            :title="`Vencido ${expense.days_overdue} días`"
                                                        />
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Button @click="viewExpense(expense.id)" variant="ghost" size="sm">
                                                        <Eye class="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>

                                    <!-- Pagination for expenses -->
                                    <div v-if="expenses && expenses.last_page > 1" class="mt-4 flex items-center justify-between">
                                        <div class="flex-1 text-sm text-muted-foreground">
                                            Mostrando {{ expenses.from }} a {{ expenses.to }} de {{ expenses.total }} gastos.
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                :disabled="expenses.current_page === 1"
                                                @click="
                                                    router.get(window.location.pathname, { page: expenses.current_page - 1 }, { preserveState: true })
                                                "
                                            >
                                                Anterior
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                :disabled="expenses.current_page === expenses.last_page"
                                                @click="
                                                    router.get(window.location.pathname, { page: expenses.current_page + 1 }, { preserveState: true })
                                                "
                                            >
                                                Siguiente
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="stats" class="space-y-4">
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Gastos</CardTitle>
                                    <Receipt class="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ stats?.total_expenses || 0 }}</div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-amber-600">{{ stats?.pending_expenses || 0 }}</div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Aprobados</CardTitle>
                                    <CheckCircle class="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-blue-600">{{ stats?.approved_expenses || 0 }}</div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Pagados</CardTitle>
                                    <CheckCircle class="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-green-600">{{ stats?.paid_expenses || 0 }}</div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Summary Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Resumen Financiero</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-muted-foreground">Monto Total:</span>
                                            <span class="text-lg font-bold">{{ formatCurrency(stats?.total_amount || 0) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-muted-foreground">Promedio por Gasto:</span>
                                            <span class="font-medium">{{ formatCurrency(stats?.average_amount || 0) }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div v-if="stats?.overdue_expenses > 0" class="flex items-center justify-between">
                                            <span class="text-muted-foreground">Gastos Vencidos:</span>
                                            <span class="font-medium text-red-600">{{ stats?.overdue_expenses || 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-muted-foreground">Último Registro:</span>
                                            <span class="font-medium">{{
                                                provider.last_expense_date ? new Date(provider.last_expense_date).toLocaleDateString('es-CO') : 'N/A'
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
