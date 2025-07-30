<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, LoaderCircle, Shield, Users } from 'lucide-vue-next';

interface Props {
    invitation?: {
        email?: string;
        role: string;
        token: string;
        is_mass_invitation?: boolean;
        mass_invitation_title?: string;
        mass_invitation_description?: string;
    };
    apartment?: {
        number: string;
        tower: string;
        floor: number;
    };
    apartments?: Array<{
        id: number;
        number: string;
        tower: string;
        floor: number;
        apartment_type?: string;
    }>;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    email: props.invitation?.is_mass_invitation ? '' : (props.invitation ? '' : ''),
    password: '',
    password_confirmation: '',
    token: props.invitation?.token || '',
    apartment_id: null as number | null,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const getRoleLabel = (role: string) => {
    const roles = {
        admin_conjunto: 'Administrador del Conjunto',
        consejo: 'Miembro del Consejo',
        propietario: 'Propietario',
        residente: 'Residente',
        porteria: 'Portería',
    };
    return roles[role as keyof typeof roles] || role;
};

const getPasswordTabIndex = () => {
    if (!props.invitation) return 3;
    if (props.invitation.is_mass_invitation) return 4; // name(1), email(2), apartment(3), password(4)
    return 2; // name(1), password(2)
};

const getPasswordConfirmTabIndex = () => {
    if (!props.invitation) return 4;
    if (props.invitation.is_mass_invitation) return 5;
    return 3;
};

const getSubmitTabIndex = () => {
    if (!props.invitation) return 5;
    if (props.invitation.is_mass_invitation) return 6;
    return 4;
};
</script>

<template>
    <AuthBase 
        :title="invitation ? 'Completar registro' : 'Crear cuenta'"
        :description="invitation ? 'Has sido invitado para unirte a Habitta' : 'Únete a Habitta'"
    >
        <Head :title="invitation ? 'Completar registro' : 'Crear cuenta'" />

        <div class="space-y-6">
            <!-- Mass Invitation Info Card -->
            <Card v-if="invitation?.is_mass_invitation" class="border-blue-200 bg-blue-50">
                <CardHeader class="pb-4">
                    <CardTitle class="flex items-center gap-2 text-blue-800">
                        <Users class="h-5 w-5" />
                        {{ invitation.mass_invitation_title }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p v-if="invitation.mass_invitation_description" class="text-sm text-blue-700">
                        {{ invitation.mass_invitation_description }}
                    </p>
                    <p v-else class="text-sm text-blue-700">
                        Completa tu registro seleccionando tu apartamento y creando tu cuenta.
                    </p>
                </CardContent>
            </Card>

            <!-- Individual Invitation Info Card -->
            <Card v-if="invitation && !invitation.is_mass_invitation" class="border-green-200 bg-green-50">
                <CardHeader class="pb-4">
                    <CardTitle class="flex items-center gap-2 text-green-800">
                        <Shield class="h-5 w-5" />
                        Invitación válida
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="grid gap-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Correo:</span>
                            <span class="font-medium">{{ invitation.email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Rol:</span>
                            <span class="font-medium">{{ getRoleLabel(invitation.role) }}</span>
                        </div>
                        <div v-if="apartment" class="flex justify-between">
                            <span class="flex items-center gap-1 text-muted-foreground">
                                <Building2 class="h-4 w-4" />
                                Apartamento:
                            </span>
                            <span class="font-medium">{{ apartment.tower }}{{ apartment.number }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">Nombre completo</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            v-model="form.name"
                            placeholder="Tu nombre completo"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email field for free registration or mass invitations -->
                    <div v-if="!invitation || invitation.is_mass_invitation" class="grid gap-2">
                        <Label for="email">Correo electrónico</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="tu@correo.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Apartment selection for mass invitations -->
                    <div v-if="invitation?.is_mass_invitation && apartments" class="grid gap-2">
                        <Label for="apartment_id">Selecciona tu apartamento *</Label>
                        <Select v-model="form.apartment_id" required>
                            <SelectTrigger :class="{ 'border-red-500': form.errors.apartment_id }">
                                <SelectValue placeholder="Selecciona tu apartamento" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id">
                                    Torre {{ apartment.tower }} - Apt {{ apartment.number }} (Piso {{ apartment.floor }})
                                    <span v-if="apartment.apartment_type" class="text-muted-foreground ml-2">
                                        - {{ apartment.apartment_type }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.apartment_id" />
                        <p class="text-sm text-muted-foreground">
                            Selecciona el apartamento donde resides
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Contraseña</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="getPasswordTabIndex()"
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Crea una contraseña segura"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirmar contraseña</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="getPasswordConfirmTabIndex()"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirma tu contraseña"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 w-full bg-gradient-to-r from-[#3887FE] to-[#8338EA] font-extrabold"
                        :tabindex="getSubmitTabIndex()"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ invitation ? 'Completar registro' : 'Crear cuenta' }}
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    ¿Ya tienes una cuenta?
                    <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="getSubmitTabIndex() + 1"> Iniciar sesión </TextLink>
                </div>
            </form>
        </div>
    </AuthBase>
</template>
