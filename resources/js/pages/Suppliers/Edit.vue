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
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Save, ArrowLeft, Building2, User, Phone, MapPin, FileText, AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Proveedores',
        href: '/suppliers',
    },
    {
        title: 'Editar Proveedor',
    },
];

interface Supplier {
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
    expenses_count: number;
    created_at: string;
}

const props = defineProps<{
    supplier: Supplier;
}>();

// Get page data for errors
const page = usePage();
const errors = computed(() => page.props.errors || {});

// Form setup
const form = useForm({
    name: props.supplier.name,
    document_type: props.supplier.document_type,
    document_number: props.supplier.document_number,
    email: props.supplier.email || '',
    phone: props.supplier.phone || '',
    address: props.supplier.address || '',
    city: props.supplier.city || '',
    country: props.supplier.country || 'Colombia',
    contact_name: props.supplier.contact_name || '',
    contact_phone: props.supplier.contact_phone || '',
    contact_email: props.supplier.contact_email || '',
    notes: props.supplier.notes || '',
    tax_regime: props.supplier.tax_regime || '',
    is_active: props.supplier.is_active,
});

// Submit form
const submit = () => {
    form.put(`/suppliers/${props.supplier.id}`, {
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

// Document types for Colombian context
const documentTypes = [
    { value: 'NIT', label: 'NIT - Número de Identificación Tributaria' },
    { value: 'CC', label: 'CC - Cédula de Ciudadanía' },
    { value: 'CE', label: 'CE - Cédula de Extranjería' },
    { value: 'PA', label: 'PA - Pasaporte' },
    { value: 'RUT', label: 'RUT - Registro Único Tributario' },
];

// Tax regimes for Colombian context
const taxRegimes = [
    { value: '', label: 'Seleccionar régimen' },
    { value: 'Responsable de IVA', label: 'Responsable de IVA' },
    { value: 'No responsable de IVA', label: 'No responsable de IVA' },
    { value: 'Gran Contribuyente', label: 'Gran Contribuyente' },
    { value: 'Autorretenedor', label: 'Autorretenedor' },
    { value: 'Régimen Simplificado', label: 'Régimen Simplificado' },
    { value: 'No Residente', label: 'No Residente' },
];

// Major Colombian cities
const colombianCities = [
    'Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena', 'Cúcuta', 'Soledad', 'Ibagué',
    'Bucaramanga', 'Soacha', 'Santa Marta', 'Villavicencio', 'Valledupar', 'Pereira',
    'Montería', 'Manizales', 'Pasto', 'Neiva', 'Palmira', 'Armenia'
];

// Warning for suppliers with expenses
const hasExpenses = computed(() => props.supplier.expenses_count > 0);
</script>

<template>
    <Head :title="`Editar ${supplier.name}`" />

    <AppLayout :title="`Editar ${supplier.name}`" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <ValidationErrors :errors="errors" />

            <!-- Warning for suppliers with expenses -->
            <div v-if="hasExpenses" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-start gap-2">
                    <AlertTriangle class="h-4 w-4 text-amber-600 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-amber-800 text-sm font-medium">
                            Precaución al editar
                        </p>
                        <p class="text-amber-700 text-xs">
                            Este proveedor tiene {{ supplier.expenses_count }} gasto{{ supplier.expenses_count !== 1 ? 's' : '' }} registrado{{ supplier.expenses_count !== 1 ? 's' : '' }}.
                            Los cambios en el documento o nombre pueden afectar los reportes históricos.
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h2 class="text-2xl font-semibold tracking-tight">Editar {{ supplier.name }}</h2>
                                <Badge :class="supplier.status_badge.class">{{ supplier.status_badge.text }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Modifique la información del proveedor
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
                                        <Building2 class="h-5 w-5" />
                                        Información Básica
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="name" class="required">Nombre o Razón Social</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            placeholder="Nombre del proveedor"
                                            required
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="document_type" class="required">Tipo de Documento</Label>
                                            <Select v-model="form.document_type" required>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar tipo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="docType in documentTypes" :key="docType.value" :value="docType.value">
                                                        {{ docType.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="document_number" class="required">Número de Documento</Label>
                                            <Input
                                                id="document_number"
                                                v-model="form.document_number"
                                                placeholder="Número sin puntos ni guiones"
                                                required
                                            />
                                            <p v-if="hasExpenses" class="text-xs text-amber-600">
                                                Cambiar el documento puede afectar reportes históricos
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="tax_regime">Régimen Tributario</Label>
                                        <Select v-model="form.tax_regime">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar régimen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="regime in taxRegimes" :key="regime.value" :value="regime.value">
                                                    {{ regime.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Contact Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Phone class="h-5 w-5" />
                                        Información de Contacto
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="email">Email Principal</Label>
                                            <Input
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                placeholder="email@proveedor.com"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="phone">Teléfono Principal</Label>
                                            <Input
                                                id="phone"
                                                v-model="form.phone"
                                                placeholder="Número de teléfono"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="contact_name">Persona de Contacto</Label>
                                        <Input
                                            id="contact_name"
                                            v-model="form.contact_name"
                                            placeholder="Nombre del contacto principal"
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="contact_email">Email de Contacto</Label>
                                            <Input
                                                id="contact_email"
                                                v-model="form.contact_email"
                                                type="email"
                                                placeholder="contacto@proveedor.com"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="contact_phone">Teléfono de Contacto</Label>
                                            <Input
                                                id="contact_phone"
                                                v-model="form.contact_phone"
                                                placeholder="Número de contacto directo"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Location Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <MapPin class="h-5 w-5" />
                                        Información de Ubicación
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="address">Dirección</Label>
                                        <Input
                                            id="address"
                                            v-model="form.address"
                                            placeholder="Dirección completa"
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="city">Ciudad</Label>
                                            <Input
                                                id="city"
                                                v-model="form.city"
                                                placeholder="Ciudad"
                                                list="cities"
                                            />
                                            <datalist id="cities">
                                                <option v-for="city in colombianCities" :key="city" :value="city" />
                                            </datalist>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="country">País</Label>
                                            <Input
                                                id="country"
                                                v-model="form.country"
                                                placeholder="País"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Additional Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <FileText class="h-5 w-5" />
                                        Información Adicional
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="notes">Notas y Observaciones</Label>
                                        <Textarea
                                            id="notes"
                                            v-model="form.notes"
                                            placeholder="Información adicional, términos especiales, etc."
                                            rows="4"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">
                            <!-- Status Configuration -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <User class="h-5 w-5" />
                                        Configuración de Estado
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_active"
                                            v-model:checked="form.is_active"
                                        />
                                        <Label for="is_active">
                                            Proveedor activo
                                        </Label>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Los proveedores inactivos no aparecerán en las listas de selección para nuevos gastos.
                                    </p>
                                    <p v-if="hasExpenses && !form.is_active" class="text-xs text-amber-600">
                                        Desactivar el proveedor no afecta los gastos existentes.
                                    </p>
                                </CardContent>
                            </Card>

                            <!-- Supplier Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-sm">Información del Proveedor</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Gastos registrados:</span>
                                        <span class="font-medium">{{ supplier.expenses_count }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Estado actual:</span>
                                        <Badge :class="supplier.status_badge.class">{{ supplier.status_badge.text }}</Badge>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Registrado:</span>
                                        <span>{{ new Date(supplier.created_at).toLocaleDateString('es-CO') }}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Help Information -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-sm">Información de Ayuda</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3 text-sm">
                                    <div>
                                        <h4 class="font-medium mb-1">Campos Requeridos</h4>
                                        <p class="text-muted-foreground text-xs">
                                            Solo el nombre y documento son obligatorios. La información de contacto es opcional pero recomendada.
                                        </p>
                                    </div>
                                    <div v-if="hasExpenses">
                                        <h4 class="font-medium mb-1">Proveedor con Historial</h4>
                                        <p class="text-muted-foreground text-xs">
                                            Este proveedor tiene gastos registrados. Los cambios en información básica pueden afectar reportes.
                                        </p>
                                    </div>
                                    <div>
                                        <h4 class="font-medium mb-1">Estado Activo/Inactivo</h4>
                                        <p class="text-muted-foreground text-xs">
                                            Los proveedores inactivos no se pueden seleccionar en nuevos gastos, pero mantienen su historial.
                                        </p>
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
    content: " *";
    color: red;
}
</style>
