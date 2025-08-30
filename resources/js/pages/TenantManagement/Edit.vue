<template>
    <Head title="Editar Tenant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar Tenant</h1>
                    <p class="text-muted-foreground">{{ tenant.name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="router.visit(`/tenants/${tenant.id}`)" class="gap-2">
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
                            <CardDescription>Actualiza los datos del tenant</CardDescription>
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
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="status">Estado del Tenant *</Label>
                                        <Select v-model="form.status" required>
                                            <SelectTrigger :class="{ 'border-red-500': errors.status }">
                                                <SelectValue placeholder="Selecciona un estado" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="pending">Pendiente</SelectItem>
                                                <SelectItem value="active">Activo</SelectItem>
                                                <SelectItem value="suspended">Suspendido</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="errors.status" class="text-sm text-red-600">{{ errors.status }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            Solo tenants activos pueden ser accedidos por usuarios
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 pt-6">
                                    <Button 
                                        type="submit" 
                                        :disabled="processing"
                                        class="min-w-32 gap-2"
                                    >
                                        <Icon v-if="processing" name="loader-2" class="h-4 w-4 animate-spin" />
                                        {{ processing ? 'Guardando...' : 'Guardar Cambios' }}
                                    </Button>
                                    <Button 
                                        type="button" 
                                        variant="outline"
                                        @click="router.visit(`/tenants/${tenant.id}`)"
                                    >
                                        Cancelar
                                    </Button>
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
                                    <Icon name="check-circle" class="h-4 w-4 text-muted-foreground" />
                                    <Badge :variant="form.status ? 'default' : 'secondary'">
                                        {{ form.status === 'active' ? 'Activo' : form.status === 'pending' ? 'Pendiente' : form.status === 'suspended' ? 'Suspendido' : 'Estado' }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Current Domains -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="globe" class="h-5 w-5" />
                        Dominios Configurados
                    </CardTitle>
                    <CardDescription>Dominios asociados a este tenant</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="tenant.domains.length === 0" class="py-8 text-center text-muted-foreground">
                        <Icon name="globe" class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p>No hay dominios configurados</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="domain in tenant.domains"
                            :key="domain.id"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <div class="flex items-center gap-3">
                                <Icon name="globe" class="h-4 w-4 text-blue-600" />
                                <div>
                                    <p class="font-medium">{{ domain.domain }}</p>
                                    <p v-if="domain.is_primary" class="text-xs text-green-600">Dominio principal</p>
                                </div>
                            </div>
                            <Badge v-if="domain.is_primary" variant="default" class="text-xs">
                                Principal
                            </Badge>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground mt-4">
                        💡 La gestión de dominios se implementará en una versión futura
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import Icon from '@/components/Icon.vue'

interface Domain {
    id: string
    domain: string
    is_primary: boolean
}

interface Tenant {
    id: string
    name: string
    email: string | null
    status: string
    domains: Domain[]
}

interface Props {
    tenant: Tenant
    errors: Record<string, string>
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
    { title: 'Panel Central', href: '/dashboard' },
    { title: 'Gestión de Tenants', href: '/tenants' },
    { title: props.tenant.name, href: `/tenants/${props.tenant.id}` },
    { title: 'Editar', href: `/tenants/${props.tenant.id}/edit` },
]

const form = reactive({
    name: props.tenant.name,
    email: props.tenant.email || '',
    status: props.tenant.status,
})

const processing = ref(false)

const submit = () => {
    processing.value = true
    
    router.put(route('tenant-management.update', props.tenant.id), form, {
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>