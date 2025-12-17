<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Calendar, CheckCircle, CreditCard, Mail, Phone, RefreshCw, Shield, User, XCircle, AlertTriangle } from 'lucide-vue-next';
import { ref } from 'vue';

interface SecurityPersonnel {
    id: number;
    name: string;
    email: string;
    phone: string;
    organization_type: string;
    organization_name: string | null;
    id_number: string;
    status: 'pending_email_verification' | 'pending_admin_approval' | 'active' | 'rejected' | 'suspended';
    rejection_reason: string | null;
    approved_by: number | null;
    approved_at: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

interface OrganizationTypes {
    [key: string]: string;
}

const props = defineProps<{
    personnel: SecurityPersonnel;
    organizationTypes: OrganizationTypes;
}>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Personal de Seguridad',
        href: '/admin/security-personnel',
    },
    {
        title: props.personnel.name,
        href: `/admin/security-personnel/${props.personnel.id}`,
    },
];

// Dialog state
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const showSuspendDialog = ref(false);
const showReactivateDialog = ref(false);

// Forms
const approveForm = useForm({});

const rejectForm = useForm({
    reason: '',
});

const suspendForm = useForm({
    reason: '',
});

const reactivateForm = useForm({});

const approvePersonnel = () => {
    approveForm.post(`/admin/security-personnel/${props.personnel.id}/approve`, {
        onSuccess: () => {
            showApproveDialog.value = false;
        },
    });
};

const rejectPersonnel = () => {
    rejectForm.post(`/admin/security-personnel/${props.personnel.id}/reject`, {
        onSuccess: () => {
            showRejectDialog.value = false;
        },
    });
};

const suspendPersonnel = () => {
    suspendForm.post(`/admin/security-personnel/${props.personnel.id}/suspend`, {
        onSuccess: () => {
            showSuspendDialog.value = false;
        },
    });
};

const reactivatePersonnel = () => {
    reactivateForm.post(`/admin/security-personnel/${props.personnel.id}/reactivate`, {
        onSuccess: () => {
            showReactivateDialog.value = false;
        },
    });
};

const getStatusBadge = (status: string) => {
    const badges: Record<string, { text: string; class: string }> = {
        pending_email_verification: { text: 'Verificando Email', class: 'bg-blue-100 text-blue-800' },
        pending_admin_approval: { text: 'Pendiente Aprobacion', class: 'bg-yellow-100 text-yellow-800' },
        active: { text: 'Activo', class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazado', class: 'bg-red-100 text-red-800' },
        suspended: { text: 'Suspendido', class: 'bg-orange-100 text-orange-800' },
    };
    return badges[status] || { text: status, class: 'bg-gray-100 text-gray-800' };
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Personal de Seguridad - ${personnel.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="router.visit('/admin/security-personnel')">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">{{ personnel.name }}</h1>
                        <p class="text-sm text-muted-foreground">ID: {{ personnel.id }}</p>
                    </div>
                </div>
                <Badge :class="getStatusBadge(personnel.status).class" class="text-base">
                    {{ getStatusBadge(personnel.status).text }}
                </Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- Informacion Personal -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Informacion Personal
                        </CardTitle>
                        <CardDescription>Datos del personal de seguridad</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-start gap-3">
                            <User class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Nombre Completo</p>
                                <p class="text-base">{{ personnel.name }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <Mail class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Email</p>
                                <p class="text-base">{{ personnel.email }}</p>
                                <p v-if="personnel.email_verified_at" class="text-xs text-green-600">
                                    Verificado: {{ formatDate(personnel.email_verified_at) }}
                                </p>
                                <p v-else class="text-xs text-yellow-600">
                                    Pendiente de verificacion
                                </p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <Phone class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Telefono</p>
                                <p class="text-base">{{ personnel.phone }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <CreditCard class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Cedula / ID</p>
                                <p class="text-base">{{ personnel.id_number }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Informacion de Organizacion -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Informacion de Organizacion
                        </CardTitle>
                        <CardDescription>Tipo y nombre de la organizacion</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-start gap-3">
                            <Shield class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Tipo de Organizacion</p>
                                <p class="text-base">{{ organizationTypes[personnel.organization_type] || personnel.organization_type }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <Building2 class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Nombre de la Organizacion</p>
                                <p v-if="personnel.organization_name" class="text-base">{{ personnel.organization_name }}</p>
                                <p v-else class="text-base text-muted-foreground italic">No especificado</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Informacion de Estado -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Informacion de Estado y Fechas
                        </CardTitle>
                        <CardDescription>Historial de la solicitud</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-3">
                        <div class="flex items-start gap-3">
                            <Calendar class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Fecha de Registro</p>
                                <p class="text-base">{{ formatDate(personnel.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="personnel.approved_at" class="flex items-start gap-3">
                            <CheckCircle class="mt-1 h-5 w-5 text-green-500" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Fecha de Aprobacion</p>
                                <p class="text-base">{{ formatDate(personnel.approved_at) }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <Calendar class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Ultima Actualizacion</p>
                                <p class="text-base">{{ formatDate(personnel.updated_at) }}</p>
                            </div>
                        </div>

                        <div v-if="personnel.rejection_reason" class="md:col-span-3">
                            <Separator class="mb-4" />
                            <div class="rounded-lg bg-red-50 p-4">
                                <p class="text-sm font-medium text-red-800">Razon del Rechazo/Suspension</p>
                                <p class="mt-2 text-base text-red-700 whitespace-pre-wrap">{{ personnel.rejection_reason }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button
                    v-if="personnel.status === 'pending_admin_approval'"
                    variant="default"
                    @click="showApproveDialog = true"
                >
                    <CheckCircle class="mr-2 h-4 w-4" />
                    Aprobar
                </Button>
                <Button
                    v-if="personnel.status === 'pending_admin_approval'"
                    variant="destructive"
                    @click="showRejectDialog = true"
                >
                    <XCircle class="mr-2 h-4 w-4" />
                    Rechazar
                </Button>
                <Button
                    v-if="personnel.status === 'active'"
                    variant="outline"
                    class="border-orange-500 text-orange-500 hover:bg-orange-50"
                    @click="showSuspendDialog = true"
                >
                    <AlertTriangle class="mr-2 h-4 w-4" />
                    Suspender
                </Button>
                <Button
                    v-if="personnel.status === 'suspended' || personnel.status === 'rejected'"
                    variant="outline"
                    class="border-green-500 text-green-500 hover:bg-green-50"
                    @click="showReactivateDialog = true"
                >
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Reactivar
                </Button>
            </div>
        </div>

        <!-- Approve Dialog -->
        <Dialog v-model:open="showApproveDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Aprobar Personal de Seguridad</DialogTitle>
                    <DialogDescription>
                        ¿Estas seguro de que deseas aprobar esta solicitud? El usuario podra acceder a la aplicacion.
                    </DialogDescription>
                </DialogHeader>

                <div class="rounded-lg bg-muted p-4">
                    <p class="font-semibold">{{ personnel.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ personnel.email }}</p>
                    <p class="text-sm text-muted-foreground">{{ organizationTypes[personnel.organization_type] }}</p>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showApproveDialog = false">Cancelar</Button>
                    <Button @click="approvePersonnel" :disabled="approveForm.processing">
                        {{ approveForm.processing ? 'Aprobando...' : 'Aprobar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reject Dialog -->
        <Dialog v-model:open="showRejectDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rechazar Solicitud</DialogTitle>
                    <DialogDescription>
                        ¿Estas seguro de que deseas rechazar esta solicitud?
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="rounded-lg bg-muted p-4">
                        <p class="font-semibold">{{ personnel.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ personnel.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="reject_reason">Motivo del Rechazo (requerido)*</Label>
                        <Textarea
                            id="reject_reason"
                            v-model="rejectForm.reason"
                            placeholder="Explica por que se rechaza esta solicitud..."
                            rows="3"
                            required
                        />
                        <p v-if="rejectForm.errors.reason" class="text-sm text-red-600">
                            {{ rejectForm.errors.reason }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showRejectDialog = false">Cancelar</Button>
                    <Button variant="destructive" @click="rejectPersonnel" :disabled="rejectForm.processing || !rejectForm.reason">
                        {{ rejectForm.processing ? 'Rechazando...' : 'Rechazar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Suspend Dialog -->
        <Dialog v-model:open="showSuspendDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Suspender Cuenta</DialogTitle>
                    <DialogDescription>
                        ¿Estas seguro de que deseas suspender esta cuenta?
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="rounded-lg bg-muted p-4">
                        <p class="font-semibold">{{ personnel.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ personnel.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="suspend_reason">Motivo de la Suspension (requerido)*</Label>
                        <Textarea
                            id="suspend_reason"
                            v-model="suspendForm.reason"
                            placeholder="Explica por que se suspende esta cuenta..."
                            rows="3"
                            required
                        />
                        <p v-if="suspendForm.errors.reason" class="text-sm text-red-600">
                            {{ suspendForm.errors.reason }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showSuspendDialog = false">Cancelar</Button>
                    <Button
                        variant="destructive"
                        class="bg-orange-500 hover:bg-orange-600"
                        @click="suspendPersonnel"
                        :disabled="suspendForm.processing || !suspendForm.reason"
                    >
                        {{ suspendForm.processing ? 'Suspendiendo...' : 'Suspender' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reactivate Dialog -->
        <Dialog v-model:open="showReactivateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reactivar Cuenta</DialogTitle>
                    <DialogDescription>
                        ¿Estas seguro de que deseas reactivar esta cuenta?
                    </DialogDescription>
                </DialogHeader>

                <div class="rounded-lg bg-muted p-4">
                    <p class="font-semibold">{{ personnel.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ personnel.email }}</p>
                    <p class="text-sm text-muted-foreground">Estado actual: {{ getStatusBadge(personnel.status).text }}</p>
                    <p v-if="personnel.rejection_reason" class="mt-2 text-sm">
                        <span class="font-medium">Razon anterior:</span> {{ personnel.rejection_reason }}
                    </p>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showReactivateDialog = false">Cancelar</Button>
                    <Button
                        class="bg-green-500 hover:bg-green-600"
                        @click="reactivatePersonnel"
                        :disabled="reactivateForm.processing"
                    >
                        {{ reactivateForm.processing ? 'Reactivando...' : 'Reactivar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
