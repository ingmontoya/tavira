<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, LoaderCircle, Mail, Shield, Users } from 'lucide-vue-next';

interface Props {
    conjunto: {
        name: string;
        description: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    email: '',
    apartment_number: '',
    tower: '',
    relationship: '',
    phone: '',
    message: '',
});

const submit = () => {
    form.post(route('access-request.store'), {
        onFinish: () => {
            if (!form.hasErrors) {
                form.reset();
            }
        },
    });
};
</script>

<template>
    <AuthBase title="Solicitar acceso" :description="`Solicita acceso a ${conjunto.name}`">
        <Head title="Solicitar acceso" />

        <div class="space-y-6">
            <!-- Conjunto Info Card -->
            <Card class="border-blue-200 bg-blue-50">
                <CardHeader class="pb-4">
                    <CardTitle class="flex items-center gap-2 text-blue-800">
                        <Building2 class="h-5 w-5" />
                        {{ conjunto.name }}
                    </CardTitle>
                    <CardDescription class="text-blue-700">
                        {{ conjunto.description }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 text-sm">
                        <div class="flex items-center gap-2 text-blue-700">
                            <Shield class="h-4 w-4" />
                            <span>Registro por invitación únicamente</span>
                        </div>
                        <div class="flex items-center gap-2 text-blue-700">
                            <Users class="h-4 w-4" />
                            <span>Para residentes, propietarios y administradores</span>
                        </div>
                        <div class="flex items-center gap-2 text-blue-700">
                            <Mail class="h-4 w-4" />
                            <span>Recibirás una respuesta por correo electrónico</span>
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
                        <Label for="email">Correo electrónico</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="correo@ejemplo.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="tower">Torre</Label>
                            <Select v-model="form.tower" required>
                                <SelectTrigger id="tower" :tabindex="3">
                                    <SelectValue placeholder="Selecciona torre" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="A">Torre A</SelectItem>
                                    <SelectItem value="B">Torre B</SelectItem>
                                    <SelectItem value="C">Torre C</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.tower" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="apartment_number">Número de apartamento</Label>
                            <Input
                                id="apartment_number"
                                type="text"
                                required
                                :tabindex="4"
                                v-model="form.apartment_number"
                                placeholder="101, 201, etc."
                            />
                            <InputError :message="form.errors.apartment_number" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="relationship">Relación con el apartamento</Label>
                        <Select v-model="form.relationship" required>
                            <SelectTrigger id="relationship" :tabindex="5">
                                <SelectValue placeholder="Selecciona tu relación" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="propietario">Propietario</SelectItem>
                                <SelectItem value="residente">Residente/Inquilino</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.relationship" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Teléfono (opcional)</Label>
                        <Input id="phone" type="tel" :tabindex="6" v-model="form.phone" placeholder="Número de contacto" />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="message">Mensaje adicional (opcional)</Label>
                        <Textarea
                            id="message"
                            :tabindex="7"
                            v-model="form.message"
                            placeholder="Información adicional que consideres relevante"
                            rows="3"
                        />
                        <InputError :message="form.errors.message" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 w-full bg-gradient-to-r from-[#3887FE] to-[#8338EA] font-extrabold"
                        tabindex="8"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Enviar solicitud
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    ¿Ya tienes una cuenta?
                    <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="9"> Iniciar sesión </TextLink>
                </div>
            </form>
        </div>
    </AuthBase>
</template>
