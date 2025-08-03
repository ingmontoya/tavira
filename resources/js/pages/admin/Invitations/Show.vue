<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, CheckCircle, Clock, Mail, QrCode, RefreshCw, Trash2, User, XCircle } from 'lucide-vue-next';

interface Invitation {
    id: number;
    email: string;
    role: string;
    apartment?: {
        id: number;
        number: string;
        tower: string;
        floor: number;
        apartment_type: {
            name: string;
        };
    };
    invited_by: {
        name: string;
        email: string;
    };
    accepted_by?: {
        name: string;
        email: string;
    };
    message?: string;
    created_at: string;
    expires_at: string;
    accepted_at?: string;
    token: string;
}

const props = defineProps<{
    invitation: Invitation;
}>();

const deleteForm = useForm({});
const resendForm = useForm({});

const deleteInvitation = () => {
    if (confirm('¿Estás seguro de que quieres eliminar esta invitación?')) {
        deleteForm.delete(route('invitations.destroy', props.invitation.id), {
            onSuccess: () => {
                router.visit('/invitations');
            },
        });
    }
};

const resendInvitation = () => {
    if (confirm('¿Quieres reenviar esta invitación?')) {
        resendForm.post(route('invitations.resend', props.invitation.id));
    }
};

const getRoleLabel = (role: string) => {
    const roleMap = {
        admin_conjunto: 'Admin Conjunto',
        consejo: 'Consejo',
        propietario: 'Propietario',
        residente: 'Residente',
        porteria: 'Portería',
    };
    return roleMap[role] || role;
};

const getStatusInfo = () => {
    const isExpired = new Date(props.invitation.expires_at) <= new Date();
    const isAccepted = !!props.invitation.accepted_at;

    if (isAccepted) {
        return {
            label: 'Aceptada',
            variant: 'default',
            icon: CheckCircle,
            className: 'bg-green-100 text-green-800',
        };
    } else if (isExpired) {
        return {
            label: 'Expirada',
            variant: 'destructive',
            icon: XCircle,
            className: 'bg-red-100 text-red-800',
        };
    } else {
        return {
            label: 'Pendiente',
            variant: 'secondary',
            icon: Clock,
            className: 'bg-yellow-100 text-yellow-800',
        };
    }
};

const statusInfo = getStatusInfo();

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
        title: 'Detalle',
        href: `/invitations/${props.invitation.id}`,
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const registrationUrl = `${window.location.origin}/register?token=${props.invitation.token}`;
</script>

<template>
    <Head title="Detalle de Invitación" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="router.visit('/invitations')">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">Detalle de Invitación</h1>
                        <p class="text-muted-foreground">{{ invitation.email }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Badge :class="statusInfo.className">
                        <component :is="statusInfo.icon" class="mr-1 h-3 w-3" />
                        {{ statusInfo.label }}
                    </Badge>
                </div>
            </div>

            <div class="grid max-w-4xl gap-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Información Principal -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Mail class="h-5 w-5" />
                                Información Principal
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                                <p class="text-sm">{{ invitation.email }}</p>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Rol</Label>
                                <p class="text-sm">{{ getRoleLabel(invitation.role) }}</p>
                            </div>

                            <div v-if="invitation.apartment">
                                <Label class="text-sm font-medium text-muted-foreground">Apartamento</Label>
                                <p class="text-sm">Torre {{ invitation.apartment.tower }} - Apartamento {{ invitation.apartment.number }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Piso {{ invitation.apartment.floor }} - {{ invitation.apartment.apartment_type.name }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información de Fechas -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Fechas
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Fecha de Invitación</Label>
                                <p class="text-sm">{{ formatDate(invitation.created_at) }}</p>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Fecha de Expiración</Label>
                                <p class="text-sm">{{ formatDate(invitation.expires_at) }}</p>
                            </div>

                            <div v-if="invitation.accepted_at">
                                <Label class="text-sm font-medium text-muted-foreground">Fecha de Aceptación</Label>
                                <p class="text-sm">{{ formatDate(invitation.accepted_at) }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Información de Usuarios -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Usuarios Involucrados
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label class="text-sm font-medium text-muted-foreground">Invitado por</Label>
                            <p class="text-sm">{{ invitation.invited_by.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ invitation.invited_by.email }}</p>
                        </div>

                        <div v-if="invitation.accepted_by">
                            <Label class="text-sm font-medium text-muted-foreground">Aceptado por</Label>
                            <p class="text-sm">{{ invitation.accepted_by.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ invitation.accepted_by.email }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Mensaje Personalizado -->
                <Card v-if="invitation.message">
                    <CardHeader>
                        <CardTitle>Mensaje Personalizado</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm whitespace-pre-wrap">{{ invitation.message }}</p>
                    </CardContent>
                </Card>

                <!-- URL de Registro -->
                <Card v-if="!invitation.accepted_at">
                    <CardHeader>
                        <CardTitle>URL de Registro</CardTitle>
                        <CardDescription> Comparte esta URL con el invitado para que pueda registrarse </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2 rounded-md bg-muted p-3">
                            <code class="flex-1 text-sm break-all">{{ registrationUrl }}</code>
                            <Button size="sm" variant="outline" @click="navigator.clipboard.writeText(registrationUrl)"> Copiar </Button>
                        </div>
                        <div class="flex justify-center">
                            <Button @click="router.visit(route('invitations.url', invitation.id))" variant="outline" class="flex items-center gap-2">
                                <QrCode class="h-4 w-4" />
                                Ver URL y Código QR
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Acciones -->
                <Card>
                    <CardHeader>
                        <CardTitle>Acciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3">
                            <Button
                                v-if="!invitation.accepted_at && new Date(invitation.expires_at) > new Date()"
                                @click="resendInvitation"
                                :disabled="resendForm.processing"
                            >
                                <RefreshCw class="mr-2 h-4 w-4" />
                                {{ resendForm.processing ? 'Reenviando...' : 'Reenviar Invitación' }}
                            </Button>

                            <Button variant="destructive" @click="deleteInvitation" :disabled="deleteForm.processing">
                                <Trash2 class="mr-2 h-4 w-4" />
                                {{ deleteForm.processing ? 'Eliminando...' : 'Eliminar Invitación' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
