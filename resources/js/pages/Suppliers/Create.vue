<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Save, ArrowLeft, Building2, User, Phone, MapPin, FileText } from 'lucide-vue-next';
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
        title: 'Crear Proveedor',
        href: '/suppliers/create',
    },
];

// Get page data for errors
const page = usePage();
const errors = computed(() => page.props.errors || {});

// Form setup
const form = useForm({
    name: '',
    document_type: 'NIT',
    document_number: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: 'Colombia',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    notes: '',
    tax_regime: '',
    is_active: true,
});

// Submit form
const submit = () => {
    form.post('/suppliers', {
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
</script>

<template>
    <Head title="Crear Proveedor" />

    <AppLayout title="Crear Proveedor" :breadcrumbs="breadcrumbs">
        <ValidationErrors :errors="errors" />

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h2 class="text-2xl font-semibold tracking-tight">Crear Nuevo Proveedor</h2>
                        <p class="text-sm text-muted-foreground">
                            Complete la información del proveedor para registrarlo en el sistema
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button type="button" variant="outline" @click="cancel">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Crear Proveedor' }}
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
                                <div>
                                    <h4 class="font-medium mb-1">Documentos</h4>
                                    <p class="text-muted-foreground text-xs">
                                        Para personas jurídicas use NIT. Para personas naturales use CC (Cédula de Ciudadanía).
                                    </p>
                                </div>
                                <div>
                                    <h4 class="font-medium mb-1">Régimen Tributario</h4>
                                    <p class="text-muted-foreground text-xs">
                                        Esta información ayuda con el manejo de impuestos y retenciones.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>

<style scoped>
.required::after {
    content: " *";
    color: red;
}
</style>