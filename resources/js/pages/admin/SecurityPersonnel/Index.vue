<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, Eye, RefreshCw, Search, Shield, X, XCircle, AlertTriangle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
}

interface Stats {
    pending_email: number;
    pending_approval: number;
    active: number;
    rejected: number;
    suspended: number;
    total: number;
}

interface OrganizationTypes {
    [key: string]: string;
}

const props = defineProps<{
    personnel: {
        data: SecurityPersonnel[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        status?: string;
        search?: string;
        organization_type?: string;
    };
    stats: Stats;
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
];

// Filter state
const filterStatus = ref(props.filters?.status || 'all');
const filterOrgType = ref(props.filters?.organization_type || 'all');
const searchQuery = ref(props.filters?.search || '');

// Dialog state
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const showSuspendDialog = ref(false);
const showReactivateDialog = ref(false);
const selectedPersonnel = ref<SecurityPersonnel | null>(null);

// Forms
const approveForm = useForm({});

const rejectForm = useForm({
    reason: '',
});

const suspendForm = useForm({
    reason: '',
});

const reactivateForm = useForm({});

// Methods
const applyFilters = () => {
    router.get(
        '/admin/security-personnel',
        {
            status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
            organization_type: filterOrgType.value !== 'all' ? filterOrgType.value : undefined,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    filterStatus.value = 'all';
    filterOrgType.value = 'all';
    searchQuery.value = '';
    router.get('/admin/security-personnel');
};

const hasActiveFilters = computed(() => {
    return filterStatus.value !== 'all' || filterOrgType.value !== 'all' || searchQuery.value !== '';
});

const openApproveDialog = (personnel: SecurityPersonnel) => {
    selectedPersonnel.value = personnel;
    showApproveDialog.value = true;
};

const openRejectDialog = (personnel: SecurityPersonnel) => {
    selectedPersonnel.value = personnel;
    rejectForm.reset();
    showRejectDialog.value = true;
};

const openSuspendDialog = (personnel: SecurityPersonnel) => {
    selectedPersonnel.value = personnel;
    suspendForm.reset();
    showSuspendDialog.value = true;
};

const openReactivateDialog = (personnel: SecurityPersonnel) => {
    selectedPersonnel.value = personnel;
    showReactivateDialog.value = true;
};

const approvePersonnel = () => {
    if (!selectedPersonnel.value) return;

    approveForm.post(`/admin/security-personnel/${selectedPersonnel.value.id}/approve`, {
        onSuccess: () => {
            showApproveDialog.value = false;
            selectedPersonnel.value = null;
        },
    });
};

const rejectPersonnel = () => {
    if (!selectedPersonnel.value) return;

    rejectForm.post(`/admin/security-personnel/${selectedPersonnel.value.id}/reject`, {
        onSuccess: () => {
            showRejectDialog.value = false;
            selectedPersonnel.value = null;
        },
    });
};

const suspendPersonnel = () => {
    if (!selectedPersonnel.value) return;

    suspendForm.post(`/admin/security-personnel/${selectedPersonnel.value.id}/suspend`, {
        onSuccess: () => {
            showSuspendDialog.value = false;
            selectedPersonnel.value = null;
        },
    });
};

const reactivatePersonnel = () => {
    if (!selectedPersonnel.value) return;

    reactivateForm.post(`/admin/security-personnel/${selectedPersonnel.value.id}/reactivate`, {
        onSuccess: () => {
            showReactivateDialog.value = false;
            selectedPersonnel.value = null;
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Personal de Seguridad" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Verificando Email</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.pending_email }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_approval }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Activos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Rechazados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Suspendidos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ stats.suspended }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                    <CardDescription>Busca y filtra el personal de seguridad</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label>Busqueda</Label>
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por nombre, email, cedula..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <Select v-model="filterStatus" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="pending_email_verification">Verificando Email</SelectItem>
                                    <SelectItem value="pending_admin_approval">Pendiente Aprobacion</SelectItem>
                                    <SelectItem value="active">Activos</SelectItem>
                                    <SelectItem value="rejected">Rechazados</SelectItem>
                                    <SelectItem value="suspended">Suspendidos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label>Tipo de Organizacion</Label>
                            <Select v-model="filterOrgType" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem v-for="(label, key) in organizationTypes" :key="key" :value="key">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <Button @click="applyFilters" class="flex-1">Aplicar Filtros</Button>
                            <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table -->
            <Card>
                <CardContent class="p-0">
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Organizacion</TableHead>
                                    <TableHead>Cedula</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="personnel.data.length > 0">
                                    <TableRow v-for="person in personnel.data" :key="person.id">
                                        <TableCell>
                                            <div>
                                                <div class="font-medium">{{ person.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ person.email }}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                <div class="font-medium">{{ organizationTypes[person.organization_type] }}</div>
                                                <div v-if="person.organization_name" class="text-sm text-muted-foreground">
                                                    {{ person.organization_name }}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ person.id_number }}</TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusBadge(person.status).class">
                                                {{ getStatusBadge(person.status).text }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ formatDate(person.created_at) }}</TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex justify-end space-x-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    @click="router.visit(`/admin/security-personnel/${person.id}`)"
                                                    title="Ver detalles"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="person.status === 'pending_admin_approval'"
                                                    size="sm"
                                                    variant="default"
                                                    @click="openApproveDialog(person)"
                                                    title="Aprobar"
                                                >
                                                    <CheckCircle class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="person.status === 'pending_admin_approval'"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="openRejectDialog(person)"
                                                    title="Rechazar"
                                                >
                                                    <XCircle class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="person.status === 'active'"
                                                    size="sm"
                                                    variant="outline"
                                                    class="border-orange-500 text-orange-500 hover:bg-orange-50"
                                                    @click="openSuspendDialog(person)"
                                                    title="Suspender"
                                                >
                                                    <AlertTriangle class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="person.status === 'suspended' || person.status === 'rejected'"
                                                    size="sm"
                                                    variant="outline"
                                                    class="border-green-500 text-green-500 hover:bg-green-50"
                                                    @click="openReactivateDialog(person)"
                                                    title="Reactivar"
                                                >
                                                    <RefreshCw class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center gap-2">
                                            <Shield class="h-8 w-8" />
                                            <span>No se encontro personal de seguridad</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="personnel.total > 0" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Mostrando {{ personnel.from }} a {{ personnel.to }} de {{ personnel.total }} resultados
                </div>
                <div class="space-x-2">
                    <Button variant="outline" size="sm" :disabled="!personnel.prev_page_url" @click="router.visit(personnel.prev_page_url)">
                        Anterior
                    </Button>
                    <Button variant="outline" size="sm" :disabled="!personnel.next_page_url" @click="router.visit(personnel.next_page_url)">
                        Siguiente
                    </Button>
                </div>
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

                <div v-if="selectedPersonnel" class="space-y-4">
                    <div class="rounded-lg bg-muted p-4">
                        <p class="font-semibold">{{ selectedPersonnel.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedPersonnel.email }}</p>
                        <p class="text-sm text-muted-foreground">{{ organizationTypes[selectedPersonnel.organization_type] }}</p>
                        <p class="text-sm text-muted-foreground">Cedula: {{ selectedPersonnel.id_number }}</p>
                    </div>
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
                        ¿Estas seguro de que deseas rechazar esta solicitud? El usuario recibira una notificacion.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedPersonnel" class="space-y-4">
                    <div class="rounded-lg bg-muted p-4">
                        <p class="font-semibold">{{ selectedPersonnel.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedPersonnel.email }}</p>
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
                        ¿Estas seguro de que deseas suspender esta cuenta? El usuario no podra acceder a la aplicacion.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedPersonnel" class="space-y-4">
                    <div class="rounded-lg bg-muted p-4">
                        <p class="font-semibold">{{ selectedPersonnel.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedPersonnel.email }}</p>
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
                        ¿Estas seguro de que deseas reactivar esta cuenta? El usuario podra acceder a la aplicacion.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedPersonnel" class="space-y-4">
                    <div class="rounded-lg bg-muted p-4">
                        <p class="font-semibold">{{ selectedPersonnel.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedPersonnel.email }}</p>
                        <p class="text-sm text-muted-foreground">Estado actual: {{ getStatusBadge(selectedPersonnel.status).text }}</p>
                        <p v-if="selectedPersonnel.rejection_reason" class="mt-2 text-sm">
                            <span class="font-medium">Razon anterior:</span> {{ selectedPersonnel.rejection_reason }}
                        </p>
                    </div>
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
