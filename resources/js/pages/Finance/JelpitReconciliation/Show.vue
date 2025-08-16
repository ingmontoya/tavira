<script setup lang="ts">
import ToastContainer from '@/components/ToastContainer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Building, Calendar, Check, Clock, CreditCard, DollarSign, FileText, Hash, MapPin, User, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Apartment {
    id: number;
    number: string;
    full_address: string;
    apartment_type?: {
        name: string;
    };
    residents?: Resident[];
}

interface Resident {
    id: number;
    full_name: string;
    document_number: string;
}

interface PotentialMatch {
    type: 'apartment' | 'nit';
    apartment: Apartment;
    resident?: Resident;
    confidence: 'high' | 'medium' | 'low';
    reason: string;
}

interface JelpitImport {
    id: number;
    payment_type: string;
    reference_number?: string;
    transaction_date: string;
    transaction_time?: string;
    transaction_amount: number;
    posting_date: string;
    approval_number?: string;
    originator_nit?: string;
    cleaned_nit?: string;
    payment_detail?: string;
    reconciliation_status: string;
    status_badge: { text: string; class: string };
    apartment?: Apartment;
    payment?: {
        id: number;
        payment_number: string;
    };
    match_type?: string;
    match_notes?: string;
    can_create_payment: boolean;
    is_reconciled: boolean;
    created_at: string;
    created_by?: {
        name: string;
    };
    processed_by?: {
        name: string;
    };
    processed_at?: string;
}

interface Props {
    importItem: JelpitImport;
    potentialMatches: PotentialMatch[];
    allApartments: Apartment[];
}

const props = defineProps<Props>();

// Get page data for flash messages
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Toast notifications
const { success, error } = useToast();

// Watch for flash messages and convert to toasts
watch(flashSuccess, (message) => {
    if (message) {
        success(message, 'Éxito');
    }
});

watch(flashError, (message) => {
    if (message) {
        error(message, 'Error');
    }
});

const showManualMatchDialog = ref(false);
const showRejectDialog = ref(false);

const manualMatchForm = useForm({
    apartment_id: '',
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

const createPaymentForm = useForm({});

function manualMatch() {
    if (!props.importItem?.id) return;

    manualMatchForm.post(route('finance.jelpit-reconciliation.manual-match', props.importItem.id), {
        onSuccess: () => {
            showManualMatchDialog.value = false;
            manualMatchForm.reset();
        },
        preserveScroll: true,
    });
}

function rejectImport() {
    if (!props.importItem?.id) return;

    rejectForm.post(route('finance.jelpit-reconciliation.reject', props.importItem.id), {
        onSuccess: () => {
            showRejectDialog.value = false;
            rejectForm.reset();
        },
        onError: (errors) => {
            // Handle form validation errors through toast
            const firstErrorKey = Object.keys(errors)[0];
            if (firstErrorKey && errors[firstErrorKey]) {
                error(errors[firstErrorKey], 'Error al rechazar');
            }
        },
        preserveScroll: true,
    });
}

function createPayment() {
    if (!props.importItem?.id) return;

    createPaymentForm.post(route('finance.jelpit-reconciliation.create-payment', props.importItem.id), {
        onSuccess: () => {
            success('Pago creado exitosamente', 'Éxito');
        },
        onError: (errors) => {
            // Handle form validation errors through toast
            const firstErrorKey = Object.keys(errors)[0];
            if (firstErrorKey && errors[firstErrorKey]) {
                error(errors[firstErrorKey], 'Error creando el pago');
            }
        },
        preserveScroll: true,
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
}

function formatDateTime(dateString: string): string {
    return new Date(dateString).toLocaleString('es-CO');
}

function getStatusIcon(status: string) {
    switch (status) {
        case 'matched':
            return Check;
        case 'manual_review':
            return AlertCircle;
        case 'rejected':
            return X;
        default:
            return AlertCircle;
    }
}

function getConfidenceColor(confidence: string): string {
    switch (confidence) {
        case 'high':
            return 'bg-green-100 text-green-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
</script>

<template>
    <AppLayout>
        <ToastContainer />
        <Head title="Detalle Importación Jelpit" />

        <div v-if="!importItem" class="flex min-h-64 items-center justify-center">
            <p class="text-gray-500">Cargando...</p>
        </div>

        <div v-else class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button asChild variant="outline" size="sm">
                        <Link :href="route('finance.jelpit-reconciliation.index')">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Detalle Importación Jelpit</h1>
                        <p class="text-sm text-gray-600" v-if="importItem?.id && importItem?.created_at">
                            Registro #{{ importItem.id }} importado el {{ formatDateTime(importItem.created_at) }}
                        </p>
                    </div>
                </div>

                <Badge :class="importItem?.status_badge?.class" class="flex items-center gap-1" v-if="importItem?.status_badge">
                    <component :is="getStatusIcon(importItem.reconciliation_status)" class="h-3 w-3" />
                    {{ importItem.status_badge.text }}
                </Badge>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Transaction Details -->
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Detalles de la Transacción
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <Calendar class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Fecha de Transacción</p>
                                            <p class="text-sm text-gray-600">
                                                {{ new Date(importItem.transaction_date).toLocaleDateString('es-CO') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <Clock class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Hora</p>
                                            <p class="text-sm text-gray-600">{{ importItem.transaction_time || 'No disponible' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <DollarSign class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Monto</p>
                                            <p class="text-lg font-semibold text-green-600">{{ formatCurrency(importItem.transaction_amount) }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <CreditCard class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Tipo de Pago</p>
                                            <p class="text-sm text-gray-600">{{ importItem.payment_type }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <Hash class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">No. Referencia</p>
                                            <p class="text-sm text-gray-600">{{ importItem.reference_number || 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <User class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">NIT Originador</p>
                                            <p class="font-mono text-sm text-gray-600">{{ importItem.originator_nit || 'N/A' }}</p>
                                            <p
                                                v-if="importItem.cleaned_nit && importItem.cleaned_nit !== importItem.originator_nit"
                                                class="text-xs text-gray-500"
                                            >
                                                Limpio: {{ importItem.cleaned_nit }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <FileText class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Número de Aprobación</p>
                                            <p class="text-sm text-gray-600">{{ importItem.approval_number || 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <Calendar class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Fecha de Abono</p>
                                            <p class="text-sm text-gray-600">{{ new Date(importItem.posting_date).toLocaleDateString('es-CO') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <div v-if="importItem.payment_detail" class="space-y-2">
                                <p class="text-sm font-medium text-gray-900">Detalle del Pago</p>
                                <p class="rounded-md bg-gray-50 p-3 text-sm text-gray-600">{{ importItem.payment_detail }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Reconciliation Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <component :is="getStatusIcon(importItem.reconciliation_status)" class="h-5 w-5" />
                                Estado de Conciliación
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="importItem.apartment" class="flex items-center gap-3 rounded-md bg-green-50 p-3">
                                <Building class="h-5 w-5 text-green-600" />
                                <div>
                                    <p class="text-sm font-medium text-green-900">Apartamento Asignado</p>
                                    <p class="text-sm text-green-700">{{ importItem.apartment.full_address }}</p>
                                    <p v-if="importItem.apartment.apartment_type" class="text-xs text-green-600">
                                        {{ importItem.apartment.apartment_type.name }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="importItem.match_type" class="space-y-2">
                                <p class="text-sm font-medium text-gray-900">Tipo de Coincidencia</p>
                                <Badge variant="outline">{{ importItem.match_type }}</Badge>
                            </div>

                            <div v-if="importItem.match_notes" class="space-y-2">
                                <p class="text-sm font-medium text-gray-900">Notas de Conciliación</p>
                                <p class="rounded-md bg-gray-50 p-3 text-sm text-gray-600">{{ importItem.match_notes }}</p>
                            </div>

                            <div v-if="importItem.payment" class="space-y-2">
                                <p class="text-sm font-medium text-gray-900">Pago Creado</p>
                                <div class="flex items-center gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link :href="route('finance.payments.show', importItem.payment.id)">
                                            Ver Pago {{ importItem.payment.payment_number }}
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Potential Matches -->
                    <Card v-if="potentialMatches.length > 0">
                        <CardHeader>
                            <CardTitle>Coincidencias Potenciales</CardTitle>
                            <CardDescription> Apartamentos y propietarios que podrían coincidir con este pago </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="(match, index) in potentialMatches"
                                    :key="index"
                                    class="flex items-center justify-between rounded-md border p-3 hover:bg-gray-50"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <Building v-if="match.type === 'apartment'" class="h-5 w-5 text-blue-500" />
                                            <User v-else class="h-5 w-5 text-green-500" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ match.apartment.full_address }}</p>
                                            <p class="text-xs text-gray-500">{{ match.reason }}</p>
                                            <p v-if="match.resident" class="text-xs text-gray-600">
                                                {{ match.resident.full_name }} - {{ match.resident.document_number }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge :class="getConfidenceColor(match.confidence)">
                                            {{ match.confidence }}
                                        </Badge>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="
                                                manualMatchForm.apartment_id = match.apartment.id.toString();
                                                showManualMatchDialog = true;
                                            "
                                        >
                                            Seleccionar
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Actions Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones</CardTitle>
                            <CardDescription>Gestiona el estado de este registro importado</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button
                                v-if="importItem?.can_create_payment && importItem?.id"
                                @click="createPayment"
                                :disabled="createPaymentForm.processing"
                                class="w-full bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                            >
                                <Check class="mr-2 h-4 w-4" />
                                {{ createPaymentForm.processing ? 'Creando...' : 'Crear Pago' }}
                            </Button>

                            <Button
                                v-if="importItem?.reconciliation_status === 'manual_review'"
                                @click="showManualMatchDialog = true"
                                class="w-full"
                                variant="outline"
                            >
                                <MapPin class="mr-2 h-4 w-4" />
                                Asignar Manualmente
                            </Button>

                            <Button
                                v-if="importItem?.reconciliation_status !== 'rejected'"
                                @click="showRejectDialog = true"
                                class="w-full"
                                variant="destructive"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Rechazar
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Sistema</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">Importado por</p>
                                <p class="text-gray-600">{{ importItem.created_by?.name || 'Sistema' }}</p>
                                <p class="text-xs text-gray-500">{{ formatDateTime(importItem.created_at) }}</p>
                            </div>

                            <div v-if="importItem.processed_by">
                                <p class="font-medium text-gray-900">Procesado por</p>
                                <p class="text-gray-600">{{ importItem.processed_by.name }}</p>
                                <p class="text-xs text-gray-500">{{ formatDateTime(importItem.processed_at!) }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Manual Match Dialog -->
        <Dialog v-model:open="showManualMatchDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Asignación Manual</DialogTitle>
                    <DialogDescription> Selecciona el apartamento al cual debe asignarse este pago de Jelpit </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="manualMatch" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Apartamento</Label>
                        <Select v-model="manualMatchForm.apartment_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona un apartamento" />
                            </SelectTrigger>
                            <SelectContent>
                                <!-- Show potential matches first if they exist -->
                                <SelectItem
                                    v-for="match in potentialMatches"
                                    :key="`match-${match.apartment.id}`"
                                    :value="match.apartment.id.toString()"
                                >
                                    {{ match.apartment.full_address }}
                                    <span v-if="match.resident" class="ml-2 text-xs text-gray-500"> - {{ match.resident.full_name }} </span>
                                    <span class="ml-2 text-xs text-blue-600">(Sugerido)</span>
                                </SelectItem>

                                <!-- Add separator if potential matches exist -->
                                <div v-if="potentialMatches.length > 0" class="my-1 border-t border-gray-200"></div>

                                <!-- Show all apartments, excluding those already in potential matches -->
                                <SelectItem
                                    v-for="apartment in allApartments.filter(
                                        (apt) => !potentialMatches.some((match) => match.apartment.id === apt.id),
                                    )"
                                    :key="`apt-${apartment.id}`"
                                    :value="apartment.id.toString()"
                                >
                                    {{ apartment.full_address }}
                                    <span v-if="apartment.residents && apartment.residents.length > 0" class="ml-2 text-xs text-gray-500">
                                        - {{ apartment.residents.map((r) => r.full_name).join(', ') }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="manualMatchForm.errors.apartment_id" class="text-sm text-red-600">
                            {{ manualMatchForm.errors.apartment_id }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Notas (Opcional)</Label>
                        <Textarea v-model="manualMatchForm.notes" placeholder="Agrega notas sobre esta asignación manual..." />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button @click="showManualMatchDialog = false" variant="outline" type="button"> Cancelar </Button>
                        <Button type="submit" :disabled="manualMatchForm.processing">
                            {{ manualMatchForm.processing ? 'Asignando...' : 'Asignar' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Reject Dialog -->
        <Dialog v-model:open="showRejectDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rechazar Registro</DialogTitle>
                    <DialogDescription> Especifica la razón por la cual se rechaza este registro importado </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="rejectImport" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Razón del Rechazo</Label>
                        <Textarea v-model="rejectForm.reason" placeholder="Especifica la razón del rechazo..." required />
                        <div v-if="rejectForm.errors.reason" class="text-sm text-red-600">
                            {{ rejectForm.errors.reason }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button @click="showRejectDialog = false" variant="outline" type="button"> Cancelar </Button>
                        <Button type="submit" :disabled="rejectForm.processing" variant="destructive">
                            {{ rejectForm.processing ? 'Rechazando...' : 'Rechazar' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
