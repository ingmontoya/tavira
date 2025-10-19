<template>
    <Head title="Crear Tenant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Crear Nuevo Tenant</h1>
                    <p class="text-muted-foreground">Registra un nuevo conjunto residencial en la plataforma</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="router.visit('/tenants')" class="gap-2">
                        <Icon name="arrow-left" class="h-4 w-4" />
                        Volver
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- Main form -->
                <div class="lg:col-span-3">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="building" class="h-5 w-5" />
                                Información del Conjunto
                            </CardTitle>
                            <CardDescription>Completa los datos básicos para crear el nuevo tenant</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <div class="grid gap-4">
                                    <div class="grid gap-2">
                                        <Label for="name">Nombre del Conjunto *</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            placeholder="Ej: Conjunto Villa Hermosa"
                                            :class="{ 'border-red-500': errors.name }"
                                            required
                                        />
                                        <p v-if="errors.name" class="text-sm text-red-600">{{ errors.name }}</p>
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="email">Email del Conjunto *</Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="administracion@villa-hermosa.com"
                                            :class="{ 'border-red-500': errors.email }"
                                            required
                                        />
                                        <p v-if="errors.email" class="text-sm text-red-600">{{ errors.email }}</p>
                                        <p class="text-sm text-muted-foreground">Este email se usará para comunicaciones oficiales del conjunto</p>
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="subdomain">Subdominio *</Label>
                                        <div class="flex items-center">
                                            <Input
                                                id="subdomain"
                                                v-model="form.subdomain"
                                                type="text"
                                                placeholder="villa-hermosa"
                                                :class="{ 'border-red-500': errors.domain }"
                                                class="rounded-r-none"
                                                required
                                            />
                                            <span
                                                class="rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 py-2 text-sm text-muted-foreground"
                                            >
                                                .tavira.com.co
                                            </span>
                                        </div>
                                        <p v-if="errors.domain" class="text-sm text-red-600">{{ errors.domain }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            Solo letras minúsculas, números y guiones. Este será tu dominio único.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 pt-6">
                                    <Button type="submit" :disabled="processing" class="min-w-32 gap-2">
                                        <Icon v-if="processing" name="loader-2" class="h-4 w-4 animate-spin" />
                                        {{ processing ? 'Creando...' : 'Crear Tenant' }}
                                    </Button>
                                    <Button type="button" variant="outline" @click="router.visit('/tenants')"> Cancelar </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <Card class="sticky top-4">
                        <CardHeader>
                            <CardTitle class="text-lg">Resumen</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Icon name="building" class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">{{ form.name || 'Nombre del conjunto' }}</p>
                                        <p class="text-xs text-muted-foreground">Conjunto</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Icon name="mail" class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">{{ form.email || 'email@ejemplo.com' }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Icon name="globe" class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">{{ (form.subdomain || 'subdominio') + '.tavira.com.co' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Information Card -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="info" class="h-5 w-5 text-blue-600" />
                        Información Importante
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-2">
                            <Icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" />
                            <p>El tenant se creará automáticamente en estado "Activo" y estará listo para usar</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <Icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" />
                            <p>Se creará automáticamente una base de datos independiente con configuración inicial</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <Icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" />
                            <p>Tu usuario actual será replicado como administrador del nuevo tenant</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <Icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" />
                            <p>El dominio debe ser único en toda la plataforma</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <Icon name="alert-circle" class="mt-0.5 h-4 w-4 flex-shrink-0 text-orange-600" />
                            <p>La contraseña por defecto será "password123" - cámbiala después del primer acceso</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

interface Props {
    errors?: Record<string, string>;
}

defineProps<Props>();

// Breadcrumbs
const breadcrumbs = [
    { title: 'Panel Central', href: '/dashboard' },
    { title: 'Gestión de Tenants', href: '/tenants' },
    { title: 'Crear Tenant', href: '/tenants/create' },
];

const form = reactive({
    name: '',
    email: '',
    subdomain: '',
});

const processing = ref(false);

const submit = () => {
    processing.value = true;

    // Construir dominio completo
    const formData = {
        name: form.name,
        email: form.email,
        domain: form.subdomain + '.tavira.com.co',
    };

    router.post(route('tenant-management.store'), formData, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>
