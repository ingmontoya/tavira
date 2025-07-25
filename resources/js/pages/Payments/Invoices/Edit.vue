<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

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
        title: 'Editar Factura',
        href: '',
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
    payment_concept_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    notes: string;
}

interface Invoice {
    id: number;
    invoice_number: string;
    apartment_id: number;
    type: string;
    billing_date: string;
    due_date: string;
    billing_period_year: number;
    billing_period_month: number;
    notes?: string;
    status: string;
    items: Array<{
        id: number;
        payment_concept_id: number;
        description: string;
        quantity: number;
        unit_price: number;
        notes?: string;
        payment_concept: PaymentConcept;
    }>;
}

const props = defineProps<{
    invoice: Invoice;
    apartments: Apartment[];
    paymentConcepts: PaymentConcept[];
}>();

const form = useForm({
    apartment_id: props.invoice.apartment_id.toString(),
    type: props.invoice.type,
    billing_date: props.invoice.billing_date,
    due_date: props.invoice.due_date,
    billing_period_year: props.invoice.billing_period_year,
    billing_period_month: props.invoice.billing_period_month,
    notes: props.invoice.notes || '',
    items: props.invoice.items.map((item) => ({
        payment_concept_id: item.payment_concept_id.toString(),
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        notes: item.notes || '',
    })) as InvoiceItem[],
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
        form.items[index].description = concept.name;
        form.items[index].unit_price = concept.default_amount;
    }
};

const calculateItemTotal = (item: InvoiceItem) => {
    return item.quantity * item.unit_price;
};

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + calculateItemTotal(item), 0);
});

const selectedApartment = computed(() => {
    return props.apartments.find((apt) => apt.id.toString() === form.apartment_id);
});

const submit = () => {
    form.put(`/invoices/${props.invoice.id}`, {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const canEdit = !['paid', 'cancelled'].includes(props.invoice.status);
</script>

<template>
    <Head :title="`Editar Factura ${invoice.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Warning if can't edit -->
            <div v-if="!canEdit" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-center space-x-2">
                    <div class="text-amber-800">
                        <h3 class="font-medium">Factura no editable</h3>
                        <p class="text-sm">Esta factura no se puede editar porque está {{ invoice.status === 'paid' ? 'pagada' : 'cancelada' }}.</p>
                    </div>
                </div>
            </div>

            <form v-if="canEdit" @submit.prevent="submit" class="space-y-6">
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
                                <!-- Invoice Number (read-only) -->
                                <div>
                                    <Label>Número de Factura</Label>
                                    <Input :value="invoice.invoice_number" disabled />
                                </div>

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
                                        <CardDescription> Modifica los conceptos incluidos en esta factura </CardDescription>
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
                                                        {{ concept.name }} - {{ concept.type_label }}
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
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>

            <!-- Read-only view for non-editable invoices -->
            <div v-else class="py-12 text-center">
                <p class="text-muted-foreground">Esta factura no se puede editar.</p>
                <Button asChild class="mt-4">
                    <Link :href="`/invoices/${invoice.id}`"> Ver Detalles de la Factura </Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
