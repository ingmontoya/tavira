<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Crown, LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register.admin.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Registro de Administrador" description="Configura tu conjunto residencial con Tavira">
        <Head title="Registro de Administrador" />

        <div class="space-y-6">
            <!-- Admin Info Card -->
            <Card class="border-amber-200 bg-amber-50">
                <CardHeader class="pb-4">
                    <CardTitle class="flex items-center gap-2 text-amber-800">
                        <Crown class="h-5 w-5" />
                        Registro de Administrador
                    </CardTitle>
                    <CardDescription class="text-amber-700">
                        Como primer administrador, tendrás acceso completo para gestionar el conjunto residencial y crear invitaciones para otros
                        usuarios.
                    </CardDescription>
                </CardHeader>
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
                        <Label for="email">Correo electrónico</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="admin@conjunto.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Contraseña</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
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
                            :tabindex="4"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirma tu contraseña"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 w-full bg-gradient-to-r from-[#1D3557] to-[#06D6A0] font-extrabold"
                        tabindex="5"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Crear cuenta de administrador
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    ¿Ya tienes una cuenta?
                    <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6"> Iniciar sesión </TextLink>
                </div>
            </form>
        </div>
    </AuthBase>
</template>
