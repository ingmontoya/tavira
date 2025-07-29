<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, LoaderCircle, Shield } from 'lucide-vue-next';

interface Props {
    invitation?: {
        email: string;
        role: string;
        token: string;
    };
    apartment?: {
        number: string;
        tower: string;
        floor: number;
    };
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
    token: props.invitation?.token || '',
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
</script>

<template>
    <AuthBase title="Completar registro" description="Has sido invitado para unirte a Habitta">
        <Head title="Completar registro" />

        <div class="space-y-6">
            <!-- Invitation Info Card -->
            <Card v-if="invitation" class="border-green-200 bg-green-50">
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

                    <div class="grid gap-2">
                        <Label for="password">Contraseña</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="2"
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
                            :tabindex="3"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirma tu contraseña"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 w-full bg-gradient-to-r from-[#3887FE] to-[#8338EA] font-extrabold"
                        tabindex="4"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Completar registro
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    ¿Ya tienes una cuenta?
                    <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="5"> Iniciar sesión </TextLink>
                </div>
            </form>
        </div>
    </AuthBase>
</template>
