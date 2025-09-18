<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Plus, Save, Settings, Trash2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Facturas',
        href: '/invoices',
    },
    {
        title: 'Nueva Factura',
        href: '/invoices/create',
    },
];

interface Apartment {
    id: number;
    number: string;
    tower: string;
    full_address: string;
    apartment_type: {
        id: number;
        name: string;
    };
}

interface PaymentConcept {
    id: number;
    name: string;
    description?: string;
    type: string;
    type_label: string;
    default_amount: number;
    billing_cycle: string;
}

interface InvoiceItem {
    payment_concept_id: string;
    description: string;
    quantity: number;
    unit_price: number;
    notes: string;
}

interface SystemReadiness {
    has_apartments: boolean;
    has_payment_concepts: boolean;
    has_accounting_mappings: boolean;
    has_chart_of_accounts: boolean;
    is_ready: boolean;
}

const props = defineProps<{
    apartments: Apartment[];
    paymentConcepts: PaymentConcept[];
    apartmentId?: number;
    system_readiness: SystemReadiness;
}>();

const form = useForm({
    apartment_id: props.apartmentId?.toString() || '',
    type: 'individual',
    billing_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 15 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // +15 days
    billing_period_year: new Date().getFullYear(),
    billing_period_month: new Date().getMonth() + 1,
    notes: '',
    dian_observation: '',
    dian_payment_method: null,
    dian_currency: '',
    items: [
        {
            payment_concept_id: '',
            description: '',
            quantity: 1,
            unit_price: 0,
            notes: '',
        },
    ] as InvoiceItem[],
});

const addItem = () => {
    form.items.push({
        payment_concept_id: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        notes: '',
    });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const updateItemFromConcept = (index: number, conceptId: string) => {
    const concept = props.paymentConcepts.find((c) => c.id.toString() === conceptId);
    if (concept) {
        form.items[index].description = concept.name || '';
        form.items[index].unit_price = concept.default_amount || 0;
    }
};

const calculateItemTotal = (item: InvoiceItem) => {
    return item.quantity * item.unit_price;
};

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + calculateItemTotal(item), 0);
});

const selectedApartment = computed(() => {
    return props.apartments.find((apt) => apt.id.toString() === form.apartment_id) || null;
});

const submit = () => {
    form.post('/invoices', {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

// Watch for type changes to set appropriate defaults
watch(
    () => form.type,
    (newType) => {
        if (newType === 'monthly') {
            // For monthly invoices, set period to current month
            const now = new Date();
            form.billing_period_year = now.getFullYear();
            form.billing_period_month = now.getMonth() + 1;
        }
    },
);
</script>

<template>
    <Head title="Nueva Factura" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- System Readiness Alerts -->
            <div v-if="!system_readiness.is_ready" class="space-y-4">
                <!-- No Apartments Alert -->
                <Alert v-if="!system_readiness.has_apartments" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-medium">No hay apartamentos configurados</p>
                            <p>Antes de crear facturas, debe configurar los apartamentos del conjunto residencial.</p>
                            <Button asChild variant="outline" size="sm" class="mt-2">
                                <Link href="/apartments">
                                    <Settings class="mr-2 h-4 w-4" />
                                    Configurar Apartamentos
                                </Link>
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- No Chart of Accounts Alert -->
                <Alert v-else-if="!system_readiness.has_chart_of_accounts" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-medium">Plan de cuentas contable no configurado</p>
                            <p>El sistema contable requiere un plan de cuentas antes de generar facturas.</p>
                            <Button asChild variant="outline" size="sm" class="mt-2">
                                <Link href="/accounting/chart-of-accounts">
                                    <Settings class="mr-2 h-4 w-4" />
                                    Configurar Plan de Cuentas
                                </Link>
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- No Payment Concepts Alert -->
                <Alert v-else-if="!system_readiness.has_payment_concepts" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-medium">Conceptos de pago no configurados</p>
                            <p>Debe crear los conceptos de pago (administración, multas, etc.) antes de facturar.</p>
                            <Button asChild variant="outline" size="sm" class="mt-2">
                                <Link href="/settings/payment-concepts">
                                    <Settings class="mr-2 h-4 w-4" />
                                    Configurar Conceptos de Pago
                                </Link>
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- No Accounting Mappings Alert -->
                <Alert v-else-if="!system_readiness.has_accounting_mappings" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-medium">Mapeo contable no configurado</p>
                            <p>Los conceptos de pago deben estar mapeados a cuentas contables para generar transacciones automáticas.</p>
                            <div class="flex gap-2 mt-2">
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/setup/accounting-wizard">
                                        <Settings class="mr-2 h-4 w-4" />
                                        Configuración Guiada
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/settings/payment-concept-mapping">
                                        Mapeo Manual
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </AlertDescription>
                </Alert>
            </div>

            <form @submit.prevent="submit" class="space-y-6" v-if="system_readiness.is_ready">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Form Fields -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Basic Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Básica</CardTitle>
                                <CardDescription> Información general de la factura </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Apartment Selection -->
                                <div>
                                    <Label for="apartment_id">Apartamento *</Label>
                                    <Select v-model="form.apartment_id" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un apartamento" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                                {{ apartment.full_address }} - {{ apartment.apartment_type.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.apartment_id" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.apartment_id }}
                                    </div>
                                </div>

                                <!-- Type -->
                                <div>
                                    <Label for="type">Tipo de Factura *</Label>
                                    <Select v-model="form.type" required>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="monthly">Administración Mensual</SelectItem>
                                            <SelectItem value="individual">Factura Individual</SelectItem>
                                            <SelectItem value="late_fee">Intereses de Mora</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.type }}
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label for="billing_date">Fecha de Facturación *</Label>
                                        <Input id="billing_date" type="date" v-model="form.billing_date" required />
                                        <div v-if="form.errors.billing_date" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.billing_date }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="due_date">Fecha de Vencimiento *</Label>
                                        <Input id="due_date" type="date" v-model="form.due_date" required />
                                        <div v-if="form.errors.due_date" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.due_date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Billing Period (for monthly invoices) -->
                                <div v-if="form.type === 'monthly'" class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label for="billing_period_year">Año del Período</Label>
                                        <Input
                                            id="billing_period_year"
                                            type="number"
                                            min="2020"
                                            max="2030"
                                            v-model.number="form.billing_period_year"
                                        />
                                        <div v-if="form.errors.billing_period_year" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.billing_period_year }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="billing_period_month">Mes del Período</Label>
                                        <Select v-model="form.billing_period_month">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="month in 12" :key="month" :value="month">
                                                    {{ new Date(2024, month - 1).toLocaleDateString('es-CO', { month: 'long' }) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.billing_period_month" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.billing_period_month }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <Label for="notes">Notas (Opcional)</Label>
                                    <Textarea id="notes" placeholder="Notas adicionales sobre la factura" v-model="form.notes" />
                                    <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.notes }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Invoice Items -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Conceptos a Facturar</CardTitle>
                                        <CardDescription> Agrega los conceptos que se incluirán en esta factura </CardDescription>
                                    </div>
                                    <Button type="button" @click="addItem" variant="outline" size="sm">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Agregar Concepto
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-for="(item, index) in form.items" :key="index" class="space-y-4 rounded-lg border p-4">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium">Concepto {{ index + 1 }}</h4>
                                        <Button v-if="form.items.length > 1" type="button" @click="removeItem(index)" variant="ghost" size="sm">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <!-- Payment Concept -->
                                        <div>
                                            <Label>Concepto de Pago</Label>
                                            <Select v-model="item.payment_concept_id" @update:modelValue="updateItemFromConcept(index, $event)">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Selecciona un concepto" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="concept in paymentConcepts" :key="concept.id" :value="concept.id.toString()">
                                                        {{ concept.name }} - {{ concept.type_label || 'Sin tipo' }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <div v-if="form.errors[`items.${index}.payment_concept_id`]" class="mt-1 text-sm text-red-600">
                                                {{ form.errors[`items.${index}.payment_concept_id`] }}
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div>
                                            <Label>Descripción *</Label>
                                            <Input v-model="item.description" placeholder="Descripción del concepto" required />
                                            <div v-if="form.errors[`items.${index}.description`]" class="mt-1 text-sm text-red-600">
                                                {{ form.errors[`items.${index}.description`] }}
                                            </div>
                                        </div>

                                        <!-- Quantity -->
                                        <div>
                                            <Label>Cantidad *</Label>
                                            <Input type="number" min="1" v-model.number="item.quantity" required />
                                            <div v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-sm text-red-600">
                                                {{ form.errors[`items.${index}.quantity`] }}
                                            </div>
                                        </div>

                                        <!-- Unit Price -->
                                        <div>
                                            <Label>Precio Unitario *</Label>
                                            <Input type="number" min="0" step="0.01" v-model.number="item.unit_price" required />
                                            <div v-if="form.errors[`items.${index}.unit_price`]" class="mt-1 text-sm text-red-600">
                                                {{ form.errors[`items.${index}.unit_price`] }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <Label>Notas del Concepto (Opcional)</Label>
                                        <Textarea v-model="item.notes" placeholder="Notas específicas de este concepto" />
                                    </div>

                                    <!-- Item Total -->
                                    <div class="text-right">
                                        <span class="text-sm text-muted-foreground">Total: </span>
                                        <span class="font-medium">{{ formatCurrency(calculateItemTotal(item)) }}</span>
                                    </div>
                                </div>

                                <div v-if="form.errors.items" class="text-sm text-red-600">
                                    {{ form.errors.items }}
                                </div>
                            </CardContent>
                        </Card>

                        <!-- DIAN Configuration -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración DIAN (Opcional)</CardTitle>
                                <CardDescription> Configuración específica para facturación electrónica </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Observation -->
                                <div>
                                    <Label for="dian_observation">Observación DIAN</Label>
                                    <Textarea 
                                        id="dian_observation" 
                                        v-model="form.dian_observation" 
                                        placeholder="Observación personalizada para la factura electrónica (opcional)"
                                        rows="3"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Si no se especifica, se usará la observación por defecto
                                    </p>
                                    <div v-if="form.errors.dian_observation" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.dian_observation }}
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div>
                                    <Label for="dian_payment_method">Método de Pago DIAN</Label>
                                    <Select v-model="form.dian_payment_method">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar método de pago (por defecto: Efectivo)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="1">Efectivo</SelectItem>
                                            <SelectItem :value="2">Transferencia Bancaria</SelectItem>
                                            <SelectItem :value="10">Consignación Bancaria</SelectItem>
                                            <SelectItem :value="42">Tarjeta de Crédito</SelectItem>
                                            <SelectItem :value="47">Tarjeta Débito</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">
                                        Por defecto: Efectivo (código 1)
                                    </p>
                                    <div v-if="form.errors.dian_payment_method" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.dian_payment_method }}
                                    </div>
                                </div>

                                <!-- Currency -->
                                <div>
                                    <Label for="dian_currency">Moneda</Label>
                                    <Select v-model="form.dian_currency">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar moneda (por defecto: COP)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="COP">Peso Colombiano (COP)</SelectItem>
                                            <SelectItem value="USD">Dólar Estadounidense (USD)</SelectItem>
                                            <SelectItem value="EUR">Euro (EUR)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">
                                        Por defecto: Peso Colombiano (COP)
                                    </p>
                                    <div v-if="form.errors.dian_currency" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.dian_currency }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Summary -->
                    <div class="space-y-6">
                        <!-- Selected Apartment -->
                        <Card v-if="selectedApartment">
                            <CardHeader>
                                <CardTitle>Apartamento Seleccionado</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2">
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Apartamento</Label>
                                        <p class="font-medium">{{ selectedApartment.full_address }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Tipo</Label>
                                        <p>{{ selectedApartment.apartment_type.name }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Total Summary -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Resumen</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Conceptos:</span>
                                        <span>{{ form.items.length }}</span>
                                    </div>

                                    <Separator />

                                    <div class="flex justify-between text-lg font-medium">
                                        <span>Total:</span>
                                        <span>{{ formatCurrency(subtotal) }}</span>
                                    </div>
                                </div>

                                <Button type="submit" class="w-full" :disabled="form.processing || !form.apartment_id || form.items.length === 0">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Creando...' : 'Crear Factura' }}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
