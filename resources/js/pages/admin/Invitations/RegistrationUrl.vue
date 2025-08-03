<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Copy, Download, QrCode, Share, User } from 'lucide-vue-next';

interface Props {
    invitation: {
        id: number;
        email: string;
        role: string;
        created_at: string;
        expires_at: string;
        apartment?: {
            number: string;
            tower: string;
            floor: number;
            apartment_type?: {
                name: string;
            };
        };
    };
    registrationUrl: string;
    qrCode: string;
}

const props = defineProps<Props>();
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Invitaciones',
        href: '/invitaciones',
    },
];

// Debug logging
console.log('RegistrationUrl props:', props);
console.log('Registration URL:', props.registrationUrl);
console.log('QR Code type:', typeof props.qrCode);
console.log('QR Code value:', props.qrCode);
console.log('QR Code length:', props.qrCode?.length || 'undefined');

const copyUrl = async () => {
    try {
        await navigator.clipboard.writeText(props.registrationUrl);
        // You could add a toast notification here
        alert('URL copiada al portapapeles');
    } catch (err) {
        console.error('Error copying URL:', err);
    }
};

const downloadQR = () => {
    const blob = new Blob([props.qrCode], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `qr-invitation-${props.invitation.email}.svg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
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

const shareUrl = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Invitación a Habitta',
                text: `Has sido invitado a unirte a Habitta como ${getRoleLabel(props.invitation.role)}`,
                url: props.registrationUrl,
            });
        } catch (err) {
            console.error('Error sharing:', err);
        }
    } else {
        copyUrl();
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`URL de Registro - ${invitation.email}`" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('invitations.index')" class="flex items-center gap-2 text-muted-foreground hover:text-foreground">
                    <ArrowLeft class="h-4 w-4" />
                    Volver a invitaciones
                </Link>
            </div>

            <!-- Invitation Info -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Detalles de la Invitación
                    </CardTitle>
                    <CardDescription> Información del usuario invitado </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label class="text-muted-foreground">Correo electrónico</Label>
                            <p class="font-medium">{{ invitation.email }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Rol</Label>
                            <p class="font-medium">{{ getRoleLabel(invitation.role) }}</p>
                        </div>
                        <div v-if="invitation.apartment">
                            <Label class="text-muted-foreground">Apartamento</Label>
                            <p class="font-medium">
                                {{ invitation.apartment.tower }}{{ invitation.apartment.number }} - Piso {{ invitation.apartment.floor }}
                            </p>
                        </div>
                        <div v-if="invitation.apartment?.apartment_type">
                            <Label class="text-muted-foreground">Tipo</Label>
                            <p class="font-medium">{{ invitation.apartment.apartment_type.name }}</p>
                        </div>
                    </div>

                    <Separator />

                    <div class="grid gap-2 md:grid-cols-2">
                        <div>
                            <Label class="text-muted-foreground">Fecha de invitación</Label>
                            <p class="font-medium">{{ new Date(invitation.created_at).toLocaleDateString('es-ES') }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Expira el</Label>
                            <p class="font-medium">{{ new Date(invitation.expires_at).toLocaleDateString('es-ES') }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Registration URL -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Share class="h-5 w-5" />
                        URL de Registro
                    </CardTitle>
                    <CardDescription> Comparte esta URL con el usuario para que pueda completar su registro </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex gap-2">
                        <!-- Debug: registrationUrl = {{ registrationUrl }} -->
                        <input
                            :value="registrationUrl"
                            readonly
                            class="flex-1 rounded-md border border-input bg-background px-3 py-2 font-mono text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <Button @click="copyUrl" variant="outline" size="icon">
                            <Copy class="h-4 w-4" />
                        </Button>
                        <Button @click="shareUrl" variant="outline" size="icon">
                            <Share class="h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- QR Code -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <QrCode class="h-5 w-5" />
                        Código QR
                    </CardTitle>
                    <CardDescription> El usuario puede escanear este código QR para acceder directamente al formulario de registro </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="flex justify-center rounded-lg border bg-white p-4">
                            <div v-html="qrCode" class="max-w-[200px]"></div>
                        </div>
                        <Button @click="downloadQR" variant="outline" class="flex items-center gap-2">
                            <Download class="h-4 w-4" />
                            Descargar QR como SVG
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
