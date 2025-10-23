<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Calculator, CheckCircle, CreditCard, Receipt, Save } from 'lucide-vue-next';
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
        title: 'Crear Gasto',
        href: '/expenses/create',
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

interface Provider {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    email: string | null;
    phone: string | null;
    contact_name: string | null;
    full_contact_info: string;
}

interface ApprovalSettings {
    approval_threshold_amount: number;
    council_approval_required: boolean;
}

const props = defineProps<{
    categories: ExpenseCategory[];
    expenseAccounts: Account[];
    assetAccounts: Account[];
    liabilityAccounts: Account[];
    providers: Provider[];
    approvalSettings: ApprovalSettings;
}>();

// Get page data for errors
const page = usePage();
const errors = computed(() => page.props.errors || {});

// Form setup
const form = useForm({
    expense_category_id: '',
    provider_id: '',
    vendor_name: '',
    vendor_document: '',
    vendor_email: '',
    vendor_phone: '',
    description: '',
    expense_date: new Date().toISOString().split('T')[0],
    due_date: '',
    subtotal: 0,
    tax_amount: 0,
    total_amount: 0,
    debit_account_id: '',
    credit_account_id: '',
    notes: '',
});

// Computed properties
const selectedCategory = computed(() => {
    if (!form.expense_category_id) return null;
    return props.categories.find((cat) => cat.id === parseInt(form.expense_category_id));
});

const allCreditAccounts = computed(() => {
    return [...props.assetAccounts, ...props.liabilityAccounts];
});

const selectedProvider = computed(() => {
    if (!form.provider_id) return null;
    return props.providers.find((provider) => provider.id === parseInt(form.provider_id));
});

const showVendorFields = computed(() => {
    return !form.provider_id;
});

const requiresApproval = computed(() => {
    if (!props.approvalSettings) return false;

    let needsApproval = false;

    // Check if amount exceeds threshold (4 salarios mínimos)
    if (props.approvalSettings.council_approval_required && form.total_amount >= props.approvalSettings.approval_threshold_amount) {
        needsApproval = true;
    }

    // Also check if category requires approval
    if (selectedCategory.value?.requires_approval) {
        needsApproval = true;
    }

    return needsApproval;
});

// Watch for category changes to set default accounts
watch(
    () => form.expense_category_id,
    (newCategoryId) => {
        if (newCategoryId) {
            const category = props.categories.find((cat) => cat.id === parseInt(newCategoryId));
            if (category) {
                if (category.default_debit_account && !form.debit_account_id) {
                    form.debit_account_id = category.default_debit_account.id.toString();
                }
                if (category.default_credit_account && !form.credit_account_id) {
                    form.credit_account_id = category.default_credit_account.id.toString();
                }
            }
        }
    },
);

// Watch for provider changes to populate vendor fields
watch(
    () => form.provider_id,
    (newProviderId) => {
        if (newProviderId) {
            const provider = props.providers.find((p) => p.id === parseInt(newProviderId));
            if (provider) {
                form.vendor_name = provider.name;
                form.vendor_document = provider.document_number;
                form.vendor_email = provider.email || '';
                form.vendor_phone = provider.phone || '';
            }
        } else {
            // Clear vendor fields when no provider is selected
            form.vendor_name = '';
            form.vendor_document = '';
            form.vendor_email = '';
            form.vendor_phone = '';
        }
    },
);

// Calculate total when subtotal or tax changes
watch([() => form.subtotal, () => form.tax_amount], () => {
    form.total_amount = (form.subtotal || 0) + (form.tax_amount || 0);
});

// Submit form
const submit = () => {
    form.post('/expenses', {
        onSuccess: () => {
            // Will redirect to show page
        },
    });
};

const cancel = () => {
    if (confirm('¿Está seguro de que desea cancelar? Los cambios no guardados se perderán.')) {
        window.history.back();
    }
};
</script>

<template>
    <Head title="Crear Gasto" />

    <AppLayout title="Crear Gasto" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <ValidationErrors :errors="errors" />

            <form @submit.prevent="submit">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h2 class="text-2xl font-semibold tracking-tight">Crear Nuevo Gasto</h2>
                            <p class="text-sm text-muted-foreground">Complete la información del gasto y su mapeo contable</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button type="button" variant="outline" @click="cancel">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Guardando...' : 'Crear Gasto' }}
                            </Button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Main Information -->
                        <div class="space-y-6 lg:col-span-2">
                            <!-- Basic Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Receipt class="h-5 w-5" />
                                        Información Básica
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="expense_category_id" class="required">Categoría</Label>
                                            <Select v-model="form.expense_category_id" required>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar categoría" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                                        <div class="flex items-center gap-2">
                                                            <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: category.color }"></div>
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
                                            <Input id="expense_date" v-model="form.expense_date" type="date" required />
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
                                    <!-- Provider Selection -->
                                    <div class="space-y-2">
                                        <Label for="provider_id">Seleccionar Proveedor Registrado</Label>
                                        <Select v-model="form.provider_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar proveedor existente (opcional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Ninguno - Ingresar manualmente</SelectItem>
                                                <SelectItem v-for="provider in providers" :key="provider.id" :value="provider.id.toString()">
                                                    <div class="flex flex-col">
                                                        <div class="font-medium">{{ provider.name }}</div>
                                                        <div class="text-xs text-muted-foreground">
                                                            {{ provider.document_type }}: {{ provider.document_number }}
                                                            <span v-if="provider.full_contact_info"> • {{ provider.full_contact_info }}</span>
                                                        </div>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">Si selecciona un proveedor, sus datos se cargarán automáticamente</p>
                                    </div>

                                    <!-- Vendor Fields (manual entry) -->
                                    <div v-if="showVendorFields || selectedProvider" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="vendor_name" :class="{ required: showVendorFields }">Nombre del Proveedor</Label>
                                            <Input
                                                id="vendor_name"
                                                v-model="form.vendor_name"
                                                placeholder="Nombre o razón social"
                                                :disabled="!!selectedProvider"
                                                :required="showVendorFields"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="vendor_document">Documento</Label>
                                            <Input
                                                id="vendor_document"
                                                v-model="form.vendor_document"
                                                placeholder="NIT, CC, CE..."
                                                :disabled="!!selectedProvider"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="vendor_email">Email</Label>
                                            <Input
                                                id="vendor_email"
                                                v-model="form.vendor_email"
                                                type="email"
                                                placeholder="email@proveedor.com"
                                                :disabled="!!selectedProvider"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="vendor_phone">Teléfono</Label>
                                            <Input
                                                id="vendor_phone"
                                                v-model="form.vendor_phone"
                                                placeholder="Número de contacto"
                                                :disabled="!!selectedProvider"
                                            />
                                        </div>
                                    </div>

                                    <div v-if="selectedProvider" class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                                        <div class="flex items-center gap-2 text-blue-800">
                                            <AlertTriangle class="h-4 w-4" />
                                            <span class="text-sm font-medium">Proveedor Seleccionado</span>
                                        </div>
                                        <p class="mt-1 text-sm text-blue-600">
                                            Los datos del proveedor "{{ selectedProvider.name }}" se han cargado automáticamente. Para modificar esta
                                            información, desseleccione el proveedor e ingrese los datos manualmente.
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
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="space-y-2">
                                            <Label for="subtotal" class="required">Subtotal</Label>
                                            <Input id="subtotal" v-model.number="form.subtotal" type="number" step="0.01" min="0" required />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="tax_amount">Impuestos</Label>
                                            <Input id="tax_amount" v-model.number="form.tax_amount" type="number" step="0.01" min="0" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="total_amount" class="required">Total</Label>
                                            <Input id="total_amount" :value="formatCurrency(form.total_amount)" disabled class="font-bold" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="due_date">Fecha de Vencimiento</Label>
                                        <Input id="due_date" v-model="form.due_date" type="date" />
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
                                        <Textarea id="notes" v-model="form.notes" placeholder="Observaciones adicionales..." rows="3" />
                                    </div>

                                    <div v-if="requiresApproval" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                        <div class="flex items-center gap-2 text-amber-800">
                                            <AlertTriangle class="h-4 w-4" />
                                            <span class="text-sm font-medium">Aprobación Requerida</span>
                                        </div>
                                        <p class="mt-1 text-sm text-amber-700">
                                            Este gasto requiere aprobación
                                            <template v-if="form.total_amount >= approvalSettings.approval_threshold_amount">
                                                del concejo debido a que el monto ({{ formatCurrency(form.total_amount) }}) supera el límite de
                                                {{ formatCurrency(approvalSettings.approval_threshold_amount) }} (4 salarios mínimos)
                                            </template>
                                            <template v-else-if="selectedCategory?.requires_approval"> debido a la categoría seleccionada </template>.
                                        </p>
                                    </div>
                                    <div v-else class="rounded-lg border border-green-200 bg-green-50 p-3">
                                        <div class="flex items-center gap-2 text-green-800">
                                            <CheckCircle class="h-4 w-4" />
                                            <span class="text-sm font-medium">Aprobación Automática</span>
                                        </div>
                                        <p class="mt-1 text-sm text-green-700">
                                            Este gasto será aprobado automáticamente porque el monto está por debajo del límite de
                                            {{ formatCurrency(approvalSettings.approval_threshold_amount) }} y no requiere aprobación de categoría.
                                        </p>
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
                                        <p class="text-xs text-muted-foreground">Esta cuenta registrará el gasto incurrido</p>
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
                                                <SelectItem
                                                    v-for="account in assetAccounts"
                                                    :key="`asset-${account.id}`"
                                                    :value="account.id.toString()"
                                                >
                                                    <span class="ml-4">{{ account.full_name }}</span>
                                                </SelectItem>
                                                <!-- Liability Accounts (Cuentas por Pagar) -->
                                                <SelectItem v-if="liabilityAccounts.length > 0" disabled class="font-semibold text-primary">
                                                    Pasivos (Cuentas por Pagar)
                                                </SelectItem>
                                                <SelectItem
                                                    v-for="account in liabilityAccounts"
                                                    :key="`liability-${account.id}`"
                                                    :value="account.id.toString()"
                                                >
                                                    <span class="ml-4">{{ account.full_name }}</span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Seleccione Activos (Caja/Bancos) para pagos inmediatos o Pasivos (Cuentas por Pagar) para gastos por pagar
                                        </p>
                                    </div>

                                    <!-- Preview of accounting entry -->
                                    <div
                                        v-if="form.debit_account_id && form.credit_account_id && form.total_amount > 0"
                                        class="mt-4 rounded-lg bg-muted p-3"
                                    >
                                        <h4 class="mb-2 text-sm font-medium">Vista Previa del Asiento Contable:</h4>
                                        <div class="space-y-1 text-xs">
                                            <div class="flex justify-between">
                                                <span
                                                    >Débito:
                                                    {{ expenseAccounts.find((acc) => acc.id === parseInt(form.debit_account_id))?.full_name }}</span
                                                >
                                                <span class="font-mono">{{ formatCurrency(form.total_amount) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span
                                                    >Crédito:
                                                    {{
                                                        allCreditAccounts.find((acc) => acc.id === parseInt(form.credit_account_id))?.full_name
                                                    }}</span
                                                >
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
                                        <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: selectedCategory.color }"></div>
                                        {{ selectedCategory.name }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <p class="text-sm text-muted-foreground">
                                        {{ selectedCategory.description }}
                                    </p>
                                    <div v-if="selectedCategory.requires_approval" class="flex items-center gap-2 text-xs text-amber-600">
                                        <AlertTriangle class="h-3 w-3" />
                                        Requiere aprobación
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.required::after {
    content: ' *';
    color: red;
}
</style>
