<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, Eye, Pencil, Search, X, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProviderRegistration {
    id: number;
    company_name: string;
    contact_name: string;
    email: string;
    phone: string;
    service_type: string;
    description: string | null;
    status: 'pending' | 'approved' | 'rejected';
    admin_notes: string | null;
    reviewed_at: string | null;
    reviewed_by: { id: number; name: string } | null;
    created_at: string;
}

interface Stats {
    pending: number;
    approved: number;
    rejected: number;
    total: number;
}

const props = defineProps<{
    registrations: {
        data: ProviderRegistration[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        status?: string;
        search?: string;
    };
    stats: Stats;
}>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Solicitudes de Proveedores',
        href: '/admin/provider-registrations',
    },
];

// Filter state
const filterStatus = ref(props.filters?.status || 'all');
const searchQuery = ref(props.filters?.search || '');

// Dialog state
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const selectedRegistration = ref<ProviderRegistration | null>(null);

// Forms
const approveForm = useForm({
    admin_notes: '',
});

const rejectForm = useForm({
    admin_notes: '',
});

// Methods
const applyFilters = () => {
    router.get(
        '/admin/provider-registrations',
        {
            status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    filterStatus.value = 'all';
    searchQuery.value = '';
    router.get('/admin/provider-registrations');
};

const hasActiveFilters = computed(() => {
    return filterStatus.value !== 'all' || searchQuery.value !== '';
});

const openApproveDialog = (registration: ProviderRegistration) => {
    selectedRegistration.value = registration;
    approveForm.reset();
    showApproveDialog.value = true;
};

const openRejectDialog = (registration: ProviderRegistration) => {
    selectedRegistration.value = registration;
    rejectForm.reset();
    showRejectDialog.value = true;
};

const approveRegistration = () => {
    if (!selectedRegistration.value) return;

    approveForm.post(`/admin/provider-registrations/${selectedRegistration.value.id}/approve`, {
        onSuccess: () => {
            showApproveDialog.value = false;
            selectedRegistration.value = null;
        },
    });
};

const rejectRegistration = () => {
    if (!selectedRegistration.value) return;

    rejectForm.post(`/admin/provider-registrations/${selectedRegistration.value.id}/reject`, {
        onSuccess: () => {
            showRejectDialog.value = false;
            selectedRegistration.value = null;
        },
    });
};

const getStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
        approved: { text: 'Aprobado', class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazado', class: 'bg-red-100 text-red-800' },
    };
    return badges[status] || badges.pending;
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
    <Head title="Solicitudes de Proveedores" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
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
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Aprobados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
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
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                    <CardDescription>Busca y filtra las solicitudes de proveedores</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Búsqueda</Label>
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por empresa, contacto, email..."
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
                                    <SelectItem value="pending">Pendientes</SelectItem>
                                    <SelectItem value="approved">Aprobados</SelectItem>
                                    <SelectItem value="rejected">Rechazados</SelectItem>
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
                                    <TableHead>Empresa</TableHead>
                                    <TableHead>Contacto</TableHead>
                                    <TableHead>Tipo de Servicio</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="registrations.data.length > 0">
                                    <TableRow v-for="registration in registrations.data" :key="registration.id">
                                        <TableCell class="font-medium">{{ registration.company_name }}</TableCell>
                                        <TableCell>{{ registration.contact_name }}</TableCell>
                                        <TableCell>{{ registration.service_type }}</TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusBadge(registration.status).class">
                                                {{ getStatusBadge(registration.status).text }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ formatDate(registration.created_at) }}</TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex justify-end space-x-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    @click="router.visit(`/admin/provider-registrations/${registration.id}`)"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="registration.status === 'pending'"
                                                    size="sm"
                                                    variant="outline"
                                                    @click="router.visit(`/admin/provider-registrations/${registration.id}/edit`)"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="registration.status === 'pending'"
                                                    size="sm"
                                                    variant="default"
                                                    @click="openApproveDialog(registration)"
                                                >
                                                    <CheckCircle class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="registration.status === 'pending'"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="openRejectDialog(registration)"
                                                >
                                                    <XCircle class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                        No se encontraron solicitudes
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="registrations.total > 0" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Mostrando {{ registrations.from }} a {{ registrations.to }} de {{ registrations.total }} resultados
                </div>
                <div class="space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!registrations.prev_page_url"
                        @click="router.visit(registrations.prev_page_url)"
                    >
                        Anterior
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!registrations.next_page_url"
                        @click="router.visit(registrations.next_page_url)"
                    >
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>

        <!-- Approve Dialog -->
        <Dialog v-model:open="showApproveDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Aprobar Proveedor</DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que deseas aprobar este proveedor? Se creará un registro de proveedor activo.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedRegistration" class="space-y-4">
                    <div>
                        <p class="font-semibold">{{ selectedRegistration.company_name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedRegistration.contact_name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedRegistration.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="approve_notes">Notas del Administrador (opcional)</Label>
                        <Textarea
                            id="approve_notes"
                            v-model="approveForm.admin_notes"
                            placeholder="Agrega notas sobre esta aprobación..."
                            rows="3"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showApproveDialog = false">Cancelar</Button>
                    <Button @click="approveRegistration" :disabled="approveForm.processing">
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
                        ¿Estás seguro de que deseas rechazar esta solicitud? Esta acción no se puede deshacer.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedRegistration" class="space-y-4">
                    <div>
                        <p class="font-semibold">{{ selectedRegistration.company_name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedRegistration.contact_name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedRegistration.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="reject_notes">Motivo del Rechazo (requerido)*</Label>
                        <Textarea
                            id="reject_notes"
                            v-model="rejectForm.admin_notes"
                            placeholder="Explica por qué se rechaza esta solicitud..."
                            rows="3"
                            required
                        />
                        <p v-if="rejectForm.errors.admin_notes" class="text-sm text-red-600">
                            {{ rejectForm.errors.admin_notes }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showRejectDialog = false">Cancelar</Button>
                    <Button variant="destructive" @click="rejectRegistration" :disabled="rejectForm.processing">
                        {{ rejectForm.processing ? 'Rechazando...' : 'Rechazar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
