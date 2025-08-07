<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Save, ArrowLeft, Calculator, CreditCard, Receipt, AlertTriangle } from 'lucide-vue-next';
import { computed, watch } from 'vue';
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
        title: 'Editar Gasto',
    },
];

interface ExpenseCategory {
    id: number;
    name: string;
    description: string;
    color: string;
    icon: string;
    requires_approval: boolean;
    default_debit_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
    default_credit_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
}

interface Account {
    id: number;
    code: string;
    name: string;
    full_name: string;
}

interface User {
    id: number;
    name: string;
}

interface Supplier {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    email: string | null;
    phone: string | null;
    contact_name: string | null;
    full_contact_info: string;
}

interface Expense {
    id: number;
    expense_number: string;
    expense_category_id: number;
    supplier_id?: number;
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
    debit_account_id?: number;
    credit_account_id?: number;
    notes?: string;
    created_by: User;
}

const props = defineProps<{
    expense: Expense;
    categories: ExpenseCategory[];
    expenseAccounts: Account[];
    assetAccounts: Account[];
    liabilityAccounts: Account[];
    suppliers: Supplier[];
}>();

// Get page data for errors
const page = usePage();
const errors = computed(() => page.props.errors || {});

// Form setup
const form = useForm({
    expense_category_id: props.expense.expense_category_id.toString(),
    supplier_id: props.expense.supplier_id?.toString() || '',
    vendor_name: props.expense.vendor_name,
    vendor_document: props.expense.vendor_document || '',
    vendor_email: props.expense.vendor_email || '',
    vendor_phone: props.expense.vendor_phone || '',
    description: props.expense.description,
    expense_date: props.expense.expense_date,
    due_date: props.expense.due_date || '',
    subtotal: props.expense.subtotal,
    tax_amount: props.expense.tax_amount,
    total_amount: props.expense.total_amount,
    debit_account_id: props.expense.debit_account_id?.toString() || '',
    credit_account_id: props.expense.credit_account_id?.toString() || '',
    notes: props.expense.notes || '',
    submit_for_approval: false,
});

// Computed properties
const selectedCategory = computed(() => {
    if (!form.expense_category_id) return null;
    return props.categories.find(cat => cat.id === parseInt(form.expense_category_id));
});

const allCreditAccounts = computed(() => {
    return [...props.assetAccounts, ...props.liabilityAccounts];
});

const selectedSupplier = computed(() => {
    if (!form.supplier_id) return null;
    return props.suppliers.find(supplier => supplier.id === parseInt(form.supplier_id));
});

const showVendorFields = computed(() => {
    return !form.supplier_id;
});

const canEdit = computed(() => {
    return ['borrador', 'pendiente', 'rechazado'].includes(props.expense.status);
});

// Watch for category changes to set default accounts (only if current accounts are empty)
watch(() => form.expense_category_id, (newCategoryId) => {
    if (newCategoryId) {
        const category = props.categories.find(cat => cat.id === parseInt(newCategoryId));
        if (category) {
            if (category.default_debit_account && !form.debit_account_id) {
                form.debit_account_id = category.default_debit_account.id.toString();
            }
            if (category.default_credit_account && !form.credit_account_id) {
                form.credit_account_id = category.default_credit_account.id.toString();
            }
        }
    }
});

// Watch for supplier changes to populate vendor fields (only if editing is allowed)
watch(() => form.supplier_id, (newSupplierId) => {
    if (canEdit.value) {
        if (newSupplierId) {
            const supplier = props.suppliers.find(s => s.id === parseInt(newSupplierId));
            if (supplier) {
                form.vendor_name = supplier.name;
                form.vendor_document = supplier.document_number;
                form.vendor_email = supplier.email || '';
                form.vendor_phone = supplier.phone || '';
            }
        } else {
            // Don't clear existing data when deselecting supplier in edit mode
            // User might want to modify existing vendor data
        }
    }
});

// Calculate total when subtotal or tax changes
watch([() => form.subtotal, () => form.tax_amount], () => {
    form.total_amount = (form.subtotal || 0) + (form.tax_amount || 0);
});

// Submit form
const submit = () => {
    form.put(`/expenses/${props.expense.id}`, {
        onSuccess: () => {
            // Will redirect to show page
        }
    });
};

const cancel = () => {
    if (confirm('¿Está seguro de que desea cancelar? Los cambios no guardados se perderán.')) {
        window.history.back();
    }
};
</script>

<template>
    <Head :title="`Editar ${expense.expense_number}`" />

    <AppLayout :title="`Editar ${expense.expense_number}`" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <ValidationErrors :errors="errors" />

            <!-- Alert if expense cannot be edited -->
            <div v-if="!canEdit" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <AlertTriangle class="h-4 w-4 text-yellow-600" />
                    <p class="text-yellow-800 text-sm">
                        Este gasto no puede ser editado en su estado actual: <strong>{{ expense.status_label }}</strong>
                    </p>
                </div>
            </div>

            <form v-if="canEdit" @submit.prevent="submit">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h2 class="text-2xl font-semibold tracking-tight">Editar {{ expense.expense_number }}</h2>
                                <Badge :class="expense.status_badge.class">{{ expense.status_badge.text }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Modifique la información del gasto y su mapeo contable
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button type="button" variant="outline" @click="cancel">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </Button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Main Information -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Basic Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Receipt class="h-5 w-5" />
                                        Información Básica
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="expense_category_id" class="required">Categoría</Label>
                                            <Select v-model="form.expense_category_id" required>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar categoría" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: category.color }"></div>
                                                            {{ category.name }}
                                                        </div>
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="selectedCategory?.description" class="text-xs text-muted-foreground">
                                                {{ selectedCategory.description }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="expense_date" class="required">Fecha del Gasto</Label>
                                            <Input
                                                id="expense_date"
                                                v-model="form.expense_date"
                                                type="date"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="description" class="required">Descripción</Label>
                                        <Textarea
                                            id="description"
                                            v-model="form.description"
                                            placeholder="Describa el concepto del gasto..."
                                            rows="3"
                                            required
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Vendor Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información del Proveedor</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <!-- Supplier Selection (only if can edit) -->
                                    <div v-if="canEdit" class="space-y-2">
                                        <Label for="supplier_id">Seleccionar Proveedor Registrado</Label>
                                        <Select v-model="form.supplier_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar proveedor existente (opcional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Ninguno - Mantener datos actuales</SelectItem>
                                                <SelectItem v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id.toString()">
                                                    <div class="flex flex-col">
                                                        <div class="font-medium">{{ supplier.name }}</div>
                                                        <div class="text-xs text-muted-foreground">
                                                            {{ supplier.document_type }}: {{ supplier.document_number }}
                                                            <span v-if="supplier.full_contact_info"> • {{ supplier.full_contact_info }}</span>
                                                        </div>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Si selecciona un proveedor, reemplazará los datos actuales
                                        </p>
                                    </div>

                                    <!-- Vendor Fields -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="vendor_name" :class="{ 'required': showVendorFields }">Nombre del Proveedor</Label>
                                            <Input
                                                id="vendor_name"
                                                v-model="form.vendor_name"
                                                placeholder="Nombre o razón social"
                                                :disabled="!!selectedSupplier || !canEdit"
                                                :required="showVendorFields"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="vendor_document">Documento</Label>
                                            <Input
                                                id="vendor_document"
                                                v-model="form.vendor_document"
                                                placeholder="NIT, CC, CE..."
                                                :disabled="!!selectedSupplier || !canEdit"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="vendor_email">Email</Label>
                                            <Input
                                                id="vendor_email"
                                                v-model="form.vendor_email"
                                                type="email"
                                                placeholder="email@proveedor.com"
                                                :disabled="!!selectedSupplier || !canEdit"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="vendor_phone">Teléfono</Label>
                                            <Input
                                                id="vendor_phone"
                                                v-model="form.vendor_phone"
                                                placeholder="Número de contacto"
                                                :disabled="!!selectedSupplier || !canEdit"
                                            />
                                        </div>
                                    </div>

                                    <div v-if="selectedSupplier && canEdit" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div class="flex items-center gap-2 text-blue-800">
                                            <AlertTriangle class="h-4 w-4" />
                                            <span class="text-sm font-medium">Proveedor Seleccionado</span>
                                        </div>
                                        <p class="text-sm text-blue-600 mt-1">
                                            Los datos del proveedor "{{ selectedSupplier.name }}" reemplazarán la información actual.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Amount Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Calculator class="h-5 w-5" />
                                        Valores
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-2">
                                            <Label for="subtotal" class="required">Subtotal</Label>
                                            <Input
                                                id="subtotal"
                                                v-model.number="form.subtotal"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                required
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="tax_amount">Impuestos</Label>
                                            <Input
                                                id="tax_amount"
                                                v-model.number="form.tax_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="total_amount" class="required">Total</Label>
                                            <Input
                                                id="total_amount"
                                                :value="formatCurrency(form.total_amount)"
                                                disabled
                                                class="font-bold"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="due_date">Fecha de Vencimiento</Label>
                                        <Input
                                            id="due_date"
                                            v-model="form.due_date"
                                            type="date"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Additional Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información Adicional</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="notes">Notas</Label>
                                        <Textarea
                                            id="notes"
                                            v-model="form.notes"
                                            placeholder="Observaciones adicionales..."
                                            rows="3"
                                        />
                                    </div>

                                    <div v-if="selectedCategory?.requires_approval && expense.status === 'borrador'" class="flex items-center space-x-2">
                                        <Checkbox
                                            id="submit_for_approval"
                                            v-model:checked="form.submit_for_approval"
                                        />
                                        <Label for="submit_for_approval">
                                            Enviar para aprobación inmediatamente
                                        </Label>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Accounting Mapping -->
                        <div class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <CreditCard class="h-5 w-5" />
                                        Mapeo Contable
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="debit_account_id" class="required">Cuenta a Debitar (Gasto)</Label>
                                        <Select v-model="form.debit_account_id" required>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar cuenta de gasto" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="account in expenseAccounts" :key="account.id" :value="account.id.toString()">
                                                    {{ account.full_name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Esta cuenta registrará el gasto incurrido
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="credit_account_id" class="required">Cuenta a Acreditar</Label>
                                        <Select v-model="form.credit_account_id" required>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar cuenta de origen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <!-- Asset Accounts (Pagos Inmediatos) -->
                                                <SelectItem v-if="assetAccounts.length > 0" disabled class="font-semibold text-primary">
                                                    Activos (Pagos Inmediatos)
                                                </SelectItem>
                                                <SelectItem v-for="account in assetAccounts" :key="`asset-${account.id}`" :value="account.id.toString()">
                                                    <span class="ml-4">{{ account.full_name }}</span>
                                                </SelectItem>
                                                <!-- Liability Accounts (Cuentas por Pagar) -->
                                                <SelectItem v-if="liabilityAccounts.length > 0" disabled class="font-semibold text-primary">
                                                    Pasivos (Cuentas por Pagar)
                                                </SelectItem>
                                                <SelectItem v-for="account in liabilityAccounts" :key="`liability-${account.id}`" :value="account.id.toString()">
                                                    <span class="ml-4">{{ account.full_name }}</span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Seleccione Activos (Caja/Bancos) para pagos inmediatos o Pasivos (Cuentas por Pagar) para gastos por pagar
                                        </p>
                                    </div>

                                    <!-- Preview of accounting entry -->
                                    <div v-if="form.debit_account_id && form.credit_account_id && form.total_amount > 0" class="mt-4 p-3 bg-muted rounded-lg">
                                        <h4 class="text-sm font-medium mb-2">Vista Previa del Asiento Contable:</h4>
                                        <div class="space-y-1 text-xs">
                                            <div class="flex justify-between">
                                                <span>Débito: {{ expenseAccounts.find(acc => acc.id === parseInt(form.debit_account_id))?.full_name }}</span>
                                                <span class="font-mono">{{ formatCurrency(form.total_amount) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Crédito: {{ allCreditAccounts.find(acc => acc.id === parseInt(form.credit_account_id))?.full_name }}</span>
                                                <span class="font-mono">{{ formatCurrency(form.total_amount) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Category Info -->
                            <Card v-if="selectedCategory">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: selectedCategory.color }"></div>
                                        {{ selectedCategory.name }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <p class="text-sm text-muted-foreground">
                                        {{ selectedCategory.description }}
                                    </p>
                                    <div v-if="selectedCategory.requires_approval" class="flex items-center gap-2 text-amber-600 text-xs">
                                        <AlertTriangle class="h-3 w-3" />
                                        Requiere aprobación
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Expense Status Info -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-sm">Información del Gasto</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Creado por:</span>
                                        <span class="font-medium">{{ expense.created_by.name }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Estado actual:</span>
                                        <Badge :class="expense.status_badge.class">{{ expense.status_badge.text }}</Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </form>

            <!-- If cannot edit, show view-only message -->
            <div v-else class="text-center py-8">
                <p class="text-muted-foreground mb-4">
                    Este gasto no puede ser editado en su estado actual.
                </p>
                <Button asChild variant="outline">
                    <Link :href="`/expenses/${expense.id}`">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Ver Gasto
                    </Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.required::after {
    content: " *";
    color: red;
}
</style>
