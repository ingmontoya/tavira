<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Clock, Mail, QrCode, Send, Users } from 'lucide-vue-next';

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    apartment_type: {
        name: string;
    };
}

interface ActiveMassInvitation {
    id: number;
    mass_invitation_title: string;
    mass_invitation_description?: string;
    expires_at: string;
    token: string;
}

const props = defineProps<{
    apartments: Apartment[];
    roles: Record<string, string>;
    activeMassInvitation?: ActiveMassInvitation;
}>();

const form = useForm({
    email: '',
    role: '',
    apartment_id: null as number | null,
    message: '',
});

const massForm = useForm({
    mass_invitation_title: '',
    mass_invitation_description: '',
    expires_at: '',
});

const submit = () => {
    form.post(route('invitations.store'), {
        onSuccess: () => {
            router.visit('/invitations');
        },
    });
};

const submitMass = () => {
    massForm.post(route('invitations.mass.store'), {
        onSuccess: () => {
            router.visit('/invitations');
        },
    });
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Invitaciones',
        href: '/invitations',
    },
    {
        title: 'Nueva Invitación',
        href: '/invitations/create',
    },
];
</script>

<template>
    <Head title="Nueva Invitación" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mb-4 flex items-center gap-4">
                <Button variant="outline" size="sm" @click="router.visit('/invitations')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Nueva Invitación</h1>
                    <p class="text-muted-foreground">Invita a un nuevo usuario a unirse al sistema</p>
                </div>
            </div>

            <div class="grid max-w-4xl gap-6">
                <!-- Mass Invitation Section -->
                <Card v-if="!activeMassInvitation" class="border-blue-200 bg-blue-50/50">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-blue-700">
                            <Users class="h-5 w-5" />
                            Invitación Masiva para Residentes
                        </CardTitle>
                        <CardDescription class="text-blue-600">
                            Crea una URL única que permite a todos los residentes registrarse y elegir su apartamento
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitMass" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="mass_invitation_title">Título de la Invitación *</Label>
                                <Input
                                    id="mass_invitation_title"
                                    v-model="massForm.mass_invitation_title"
                                    placeholder="Registro de Residentes - Conjunto Torres de Villa"
                                    :class="{ 'border-red-500': massForm.errors.mass_invitation_title }"
                                    required
                                />
                                <p v-if="massForm.errors.mass_invitation_title" class="text-sm text-red-600">
                                    {{ massForm.errors.mass_invitation_title }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="mass_invitation_description">Descripción (Opcional)</Label>
                                <Textarea
                                    id="mass_invitation_description"
                                    v-model="massForm.mass_invitation_description"
                                    placeholder="Bienvenidos al sistema de gestión del conjunto. Completa tu registro seleccionando tu apartamento..."
                                    rows="3"
                                    :class="{ 'border-red-500': massForm.errors.mass_invitation_description }"
                                />
                                <p v-if="massForm.errors.mass_invitation_description" class="text-sm text-red-600">
                                    {{ massForm.errors.mass_invitation_description }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="expires_at">Fecha de Expiración (Opcional)</Label>
                                <Input
                                    id="expires_at"
                                    v-model="massForm.expires_at"
                                    type="date"
                                    :class="{ 'border-red-500': massForm.errors.expires_at }"
                                />
                                <p v-if="massForm.errors.expires_at" class="text-sm text-red-600">
                                    {{ massForm.errors.expires_at }}
                                </p>
                                <p class="text-sm text-muted-foreground">Si no se especifica, expirará en 30 días</p>
                            </div>

                            <div class="flex items-center gap-3 pt-4">
                                <Button type="submit" :disabled="massForm.processing" class="bg-blue-600 hover:bg-blue-700">
                                    <Users class="mr-2 h-4 w-4" />
                                    {{ massForm.processing ? 'Creando...' : 'Crear Invitación Masiva' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Active Mass Invitation Alert -->
                <Card v-if="activeMassInvitation" class="border-green-200 bg-green-50/50">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-green-700">
                            <AlertCircle class="h-5 w-5" />
                            Invitación Masiva Activa
                        </CardTitle>
                        <CardDescription class="text-green-600">
                            Ya tienes una invitación masiva activa para el registro de residentes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div>
                                <p class="font-semibold">{{ activeMassInvitation.mass_invitation_title }}</p>
                                <p v-if="activeMassInvitation.mass_invitation_description" class="mt-1 text-sm text-muted-foreground">
                                    {{ activeMassInvitation.mass_invitation_description }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <Clock class="h-4 w-4" />
                                Expira: {{ new Date(activeMassInvitation.expires_at).toLocaleDateString('es-CO') }}
                            </div>
                            <div class="flex items-center gap-3 pt-2">
                                <Button variant="outline" size="sm" @click="router.visit(`/invitations/${activeMassInvitation.id}/url`)">
                                    <QrCode class="mr-2 h-4 w-4" />
                                    Ver URL y QR
                                </Button>
                                <Button variant="outline" size="sm" @click="router.visit(`/invitations/${activeMassInvitation.id}`)">
                                    Ver Detalles
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Individual Invitation Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Mail class="h-5 w-5" />
                            Invitación Individual
                        </CardTitle>
                        <CardDescription> Para invitar miembros específicos del consejo, administradores o proveedores </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="email">Email *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="usuario@ejemplo.com"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    required
                                />
                                <p v-if="form.errors.email" class="text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="role">Rol *</Label>
                                <Select v-model="form.role" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.role }">
                                        <SelectValue placeholder="Selecciona un rol" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="(label, value) in roles" :key="value" :value="value">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role" class="text-sm text-red-600">
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="apartment_id">Apartamento (Opcional)</Label>
                                <Select v-model="form.apartment_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.apartment_id }">
                                        <SelectValue placeholder="Selecciona un apartamento (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null">Sin apartamento</SelectItem>
                                        <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id">
                                            Torre {{ apartment.tower }} - Apt {{ apartment.number }} (Piso {{ apartment.floor }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.apartment_id" class="text-sm text-red-600">
                                    {{ form.errors.apartment_id }}
                                </p>
                                <p class="text-sm text-muted-foreground">Solo requerido para roles de propietario y residente</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="message">Mensaje Personalizado (Opcional)</Label>
                                <Textarea
                                    id="message"
                                    v-model="form.message"
                                    placeholder="Agrega un mensaje personalizado para el invitado..."
                                    rows="4"
                                    :class="{ 'border-red-500': form.errors.message }"
                                />
                                <p v-if="form.errors.message" class="text-sm text-red-600">
                                    {{ form.errors.message }}
                                </p>
                                <p class="text-sm text-muted-foreground">Este mensaje se incluirá en el email de invitación</p>
                            </div>

                            <div class="flex items-center gap-3 pt-4">
                                <Button type="submit" :disabled="form.processing">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Enviando...' : 'Enviar Invitación' }}
                                </Button>
                                <Button type="button" variant="outline" @click="router.visit('/invitations')"> Cancelar </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Información sobre Roles</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div><strong>Admin Conjunto:</strong> Acceso completo al sistema</div>
                            <div><strong>Consejo:</strong> Acceso a finanzas y administración</div>
                            <div><strong>Propietario:</strong> Acceso a información de su apartamento</div>
                            <div><strong>Residente:</strong> Acceso básico para residentes</div>
                            <div><strong>Portería:</strong> Acceso a seguridad y visitas</div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
