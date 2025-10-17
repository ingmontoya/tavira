<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface ProviderCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
}

interface Provider {
    id: number;
    name: string;
    document_type: string | null;
    document_number: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    tax_regime: string | null;
    notes: string | null;
    is_active: boolean;
    categories: ProviderCategory[];
}

const props = defineProps<{
    provider: Provider;
    categories: ProviderCategory[];
}>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Proveedores',
        href: '/admin/providers',
    },
    {
        title: props.provider.name,
        href: `/admin/providers/${props.provider.id}`,
    },
    {
        title: 'Editar',
        href: `/admin/providers/${props.provider.id}/edit`,
    },
];

const form = useForm({
    name: props.provider.name,
    document_type: props.provider.document_type ?? null,
    document_number: props.provider.document_number ?? null,
    email: props.provider.email ?? null,
    phone: props.provider.phone ?? null,
    address: props.provider.address ?? null,
    city: props.provider.city ?? null,
    country: props.provider.country ?? 'Colombia',
    contact_name: props.provider.contact_name ?? null,
    contact_phone: props.provider.contact_phone ?? null,
    contact_email: props.provider.contact_email ?? null,
    tax_regime: props.provider.tax_regime ?? null,
    notes: props.provider.notes ?? null,
    is_active: props.provider.is_active,
    category_ids: props.provider.categories?.map(c => c.id) ?? [],
});

const toggleCategory = (categoryId: number) => {
    const index = form.category_ids.indexOf(categoryId);
    if (index > -1) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(categoryId);
    }
};

const isCategorySelected = (categoryId: number) => {
    return form.category_ids.includes(categoryId);
};

const submit = () => {
    form.put(`/admin/providers/${props.provider.id}`, {
        onSuccess: () => {
            router.visit(`/admin/providers/${props.provider.id}`);
        },
    });
};
</script>

<template>
    <Head :title="`Editar ${provider.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="sm" @click="router.visit(`/admin/providers/${provider.id}`)">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Editar Proveedor</h1>
                    <p class="text-sm text-muted-foreground">{{ provider.name }}</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-4">
                    <!-- Información Básica -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription>Datos principales del proveedor</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2 md:col-span-2">
                                <Label for="name">Nombre de la Empresa *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Ej: Constructora ABC"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="document_type">Tipo de Documento</Label>
                                <Select v-model="form.document_type">
                                    <SelectTrigger id="document_type">
                                        <SelectValue placeholder="Seleccionar tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null">Sin especificar</SelectItem>
                                        <SelectItem value="NIT">NIT</SelectItem>
                                        <SelectItem value="CC">Cédula de Ciudadanía</SelectItem>
                                        <SelectItem value="CE">Cédula de Extranjería</SelectItem>
                                        <SelectItem value="TI">Tarjeta de Identidad</SelectItem>
                                        <SelectItem value="PA">Pasaporte</SelectItem>
                                        <SelectItem value="RUT">RUT</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.document_type" class="text-sm text-red-600">
                                    {{ form.errors.document_type }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="document_number">Número de Documento</Label>
                                <Input
                                    id="document_number"
                                    v-model="form.document_number"
                                    type="text"
                                    placeholder="123456789"
                                />
                                <p v-if="form.errors.document_number" class="text-sm text-red-600">
                                    {{ form.errors.document_number }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="contacto@empresa.com"
                                />
                                <p v-if="form.errors.email" class="text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="phone">Teléfono</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="3001234567"
                                />
                                <p v-if="form.errors.phone" class="text-sm text-red-600">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="tax_regime">Régimen Tributario</Label>
                                <Input
                                    id="tax_regime"
                                    v-model="form.tax_regime"
                                    type="text"
                                    placeholder="Ej: Régimen Común"
                                />
                                <p v-if="form.errors.tax_regime" class="text-sm text-red-600">
                                    {{ form.errors.tax_regime }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información de Ubicación -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de Ubicación</CardTitle>
                            <CardDescription>Dirección y ubicación del proveedor</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2 md:col-span-2">
                                <Label for="address">Dirección</Label>
                                <Input
                                    id="address"
                                    v-model="form.address"
                                    type="text"
                                    placeholder="Calle 123 # 45-67"
                                />
                                <p v-if="form.errors.address" class="text-sm text-red-600">
                                    {{ form.errors.address }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="city">Ciudad</Label>
                                <Input
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    placeholder="Bogotá"
                                />
                                <p v-if="form.errors.city" class="text-sm text-red-600">
                                    {{ form.errors.city }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="country">País</Label>
                                <Input
                                    id="country"
                                    v-model="form.country"
                                    type="text"
                                    placeholder="Colombia"
                                />
                                <p v-if="form.errors.country" class="text-sm text-red-600">
                                    {{ form.errors.country }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información de Contacto -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de Contacto</CardTitle>
                            <CardDescription>Persona de contacto del proveedor</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2 md:col-span-2">
                                <Label for="contact_name">Nombre de Contacto</Label>
                                <Input
                                    id="contact_name"
                                    v-model="form.contact_name"
                                    type="text"
                                    placeholder="Juan Pérez"
                                />
                                <p v-if="form.errors.contact_name" class="text-sm text-red-600">
                                    {{ form.errors.contact_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="contact_email">Email de Contacto</Label>
                                <Input
                                    id="contact_email"
                                    v-model="form.contact_email"
                                    type="email"
                                    placeholder="juan.perez@empresa.com"
                                />
                                <p v-if="form.errors.contact_email" class="text-sm text-red-600">
                                    {{ form.errors.contact_email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="contact_phone">Teléfono de Contacto</Label>
                                <Input
                                    id="contact_phone"
                                    v-model="form.contact_phone"
                                    type="tel"
                                    placeholder="3001234567"
                                />
                                <p v-if="form.errors.contact_phone" class="text-sm text-red-600">
                                    {{ form.errors.contact_phone }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Categorías y Servicios -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Categorías de Servicio</CardTitle>
                            <CardDescription>Selecciona las categorías de servicio que ofrece</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 gap-3 max-h-64 overflow-y-auto p-4 border rounded-lg bg-muted/30">
                                    <div
                                        v-for="category in categories"
                                        :key="category.id"
                                        class="flex items-start space-x-3 p-2 rounded hover:bg-muted/50 transition-colors"
                                    >
                                        <input
                                            :id="`category-${category.id}`"
                                            type="checkbox"
                                            :value="category.id"
                                            :checked="isCategorySelected(category.id)"
                                            class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer"
                                            @change="toggleCategory(category.id)"
                                        />
                                        <div class="flex-1">
                                            <label
                                                :for="`category-${category.id}`"
                                                class="font-medium cursor-pointer"
                                            >
                                                {{ category.name }}
                                            </label>
                                            <p v-if="category.description" class="text-xs text-muted-foreground mt-0.5">
                                                {{ category.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.category_ids" class="text-sm text-red-600">
                                    {{ form.errors.category_ids }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notas -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Notas</CardTitle>
                            <CardDescription>Información adicional sobre el proveedor</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Información adicional, condiciones especiales, etc."
                                    rows="4"
                                />
                                <p v-if="form.errors.notes" class="text-sm text-red-600">
                                    {{ form.errors.notes }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Estado -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado</CardTitle>
                            <CardDescription>Activar o desactivar el proveedor</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center space-x-2">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer"
                                />
                                <label for="is_active" class="cursor-pointer font-medium">
                                    Proveedor activo
                                </label>
                            </div>
                            <p class="text-xs text-muted-foreground mt-2">
                                Los proveedores inactivos no aparecerán en las listas de selección
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Actions -->
                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(`/admin/providers/${provider.id}`)"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
