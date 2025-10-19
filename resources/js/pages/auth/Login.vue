<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// Check if we're on a central domain (not a subdomain)
const isCentralDomain = computed(() => {
    const hostname = window.location.hostname;
    // Central domains: localhost/127.0.0.1 for dev, tavira.com.co for production
    return hostname === 'localhost' || hostname === '127.0.0.1' || hostname === 'tavira.com.co';
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Iniciar sesión en tu cuenta" description="Ingresa tu correo electrónico y contraseña para iniciar sesión">
        <Head title="Iniciar Sesión" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="correo@ejemplo.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Contraseña</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            ¿Olvidaste tu contraseña?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Contraseña"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span>Recordarme</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full bg-gradient-to-r from-[#1D3557] to-[#06D6A0]" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Iniciar Sesión
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                <!-- Central app domains: show registration option -->
                <template v-if="isCentralDomain">
                    <div>
                        ¿No tienes una cuenta?
                        <TextLink :href="route('register')" class="text-primary"> Regístrate aquí </TextLink>
                    </div>
                </template>

                <!-- Tenant domains: no registration allowed -->
                <template v-else>
                    <div>¿No tienes una cuenta? Contacta al administrador para obtener una invitación.</div>
                </template>
            </div>
        </form>
    </AuthBase>
</template>
