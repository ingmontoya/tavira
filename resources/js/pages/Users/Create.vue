<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';

export interface Role {
    id: number;
    name: string;
}

defineProps<{
    roles: Role[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    position: '',
    department: '',
    is_active: true,
    role: '',
});

const submit = () => {
    form.post('/users', {
        onSuccess: () => {
            router.visit('/users');
        },
    });
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Crear Usuario',
        href: '/users/create',
    },
];

const roleLabels: Record<string, string> = {
    superadmin: 'Super Administrador',
    admin_conjunto: 'Administrador del Conjunto',
    consejo: 'Miembro del Consejo',
};
</script>

<template>
    <Head title="Crear Usuario" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-auto w-full max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserPlus class="h-5 w-5" />
                        Crear Nuevo Usuario
                    </CardTitle>
                    <CardDescription> Crea un nuevo usuario administrativo del sistema </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Información Personal -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Información Personal</h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="name">Nombre Completo <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Ej: Juan Carlos Pérez"
                                        :class="{ 'border-red-500': form.errors.name }"
                                    />
                                    <div v-if="form.errors.name" class="text-sm text-red-500">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="email">Email <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        placeholder="ejemplo@correo.com"
                                        :class="{ 'border-red-500': form.errors.email }"
                                    />
                                    <div v-if="form.errors.email" class="text-sm text-red-500">
                                        {{ form.errors.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="phone">Teléfono</Label>
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="Ej: + +44 7447 313219"
                                        :class="{ 'border-red-500': form.errors.phone }"
                                    />
                                    <div v-if="form.errors.phone" class="text-sm text-red-500">
                                        {{ form.errors.phone }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="position">Cargo</Label>
                                    <Input
                                        id="position"
                                        v-model="form.position"
                                        type="text"
                                        placeholder="Ej: Contador, Administrador, Asistente"
                                        :class="{ 'border-red-500': form.errors.position }"
                                    />
                                    <div v-if="form.errors.position" class="text-sm text-red-500">
                                        {{ form.errors.position }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="department">Departamento</Label>
                                <Input
                                    id="department"
                                    v-model="form.department"
                                    type="text"
                                    placeholder="Ej: Contabilidad, Administración, Servicios Generales"
                                    :class="{ 'border-red-500': form.errors.department }"
                                />
                                <div v-if="form.errors.department" class="text-sm text-red-500">
                                    {{ form.errors.department }}
                                </div>
                            </div>
                        </div>

                        <!-- Credenciales de Acceso -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Credenciales de Acceso</h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="password">Contraseña <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        required
                                        placeholder="Mínimo 8 caracteres"
                                        :class="{ 'border-red-500': form.errors.password }"
                                    />
                                    <div v-if="form.errors.password" class="text-sm text-red-500">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="password_confirmation">Confirmar Contraseña <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        required
                                        placeholder="Repite la contraseña"
                                        :class="{ 'border-red-500': form.errors.password_confirmation }"
                                    />
                                    <div v-if="form.errors.password_confirmation" class="text-sm text-red-500">
                                        {{ form.errors.password_confirmation }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permisos y Estado -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Permisos y Estado</h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="role">Rol del Sistema <span class="text-red-500">*</span></Label>
                                    <Select v-model="form.role" required>
                                        <SelectTrigger :class="{ 'border-red-500': form.errors.role }">
                                            <SelectValue placeholder="Selecciona un rol" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                                {{ roleLabels[role.name] || role.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.role" class="text-sm text-red-500">
                                        {{ form.errors.role }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label>Estado del Usuario</Label>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="is_active" v-model="form.is_active" :class="{ 'border-red-500': form.errors.is_active }" />
                                        <Label for="is_active" class="text-sm font-normal"> Usuario activo (puede acceder al sistema) </Label>
                                    </div>
                                    <div v-if="form.errors.is_active" class="text-sm text-red-500">
                                        {{ form.errors.is_active }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-6">
                            <Button type="button" variant="outline" @click="router.visit('/users')"> Cancelar </Button>
                            <Button type="submit" :disabled="form.processing">
                                <UserPlus class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Creando...' : 'Crear Usuario' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
