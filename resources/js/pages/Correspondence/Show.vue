<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Camera, CheckCircle, Edit, FileText, Mail, MapPin, Package, PenTool, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Correspondence {
    id: number;
    tracking_number: string;
    sender_name: string;
    sender_company: string | null;
    type: 'package' | 'letter' | 'document' | 'other';
    description: string;
    status: 'received' | 'delivered' | 'pending_signature' | 'returned';
    received_at: string;
    delivered_at: string | null;
    requires_signature: boolean;
    delivery_notes: string | null;
    recipient_name: string | null;
    recipient_document: string | null;
    apartment: {
        id: number;
        number: string;
        tower: string;
        floor: number;
        apartment_type: {
            name: string;
        };
    };
    received_by: {
        id: number;
        name: string;
    };
    delivered_by: {
        id: number;
        name: string;
    } | null;
    attachments: Array<{
        id: number;
        type: 'photo_evidence' | 'signature' | 'document';
        filename: string;
        original_filename: string;
        file_path: string;
        uploaded_by: {
            name: string;
        };
    }>;
}

const props = defineProps<{
    correspondence: Correspondence;
    canEdit: boolean;
    canDeliver: boolean;
}>();

const deliveryForm = useForm({
    delivery_notes: '',
    recipient_name: '',
    recipient_document: '',
    signature: null as File | null,
    requires_signature: props.correspondence.requires_signature,
});

const showDeliveryDialog = ref(false);
const showImageDialog = ref(false);
const selectedImage = ref('');
const signatureInput = ref<HTMLInputElement>();

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'package':
            return Package;
        case 'letter':
            return Mail;
        case 'document':
            return FileText;
        default:
            return Mail;
    }
};

const getTypeLabel = (type: string) => {
    switch (type) {
        case 'package':
            return 'Paquete';
        case 'letter':
            return 'Carta';
        case 'document':
            return 'Documento';
        case 'other':
            return 'Otro';
        default:
            return type;
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'received':
            return { text: 'Recibida', class: 'bg-blue-100 text-blue-800' };
        case 'delivered':
            return { text: 'Entregada', class: 'bg-green-100 text-green-800' };
        case 'pending_signature':
            return { text: 'Pendiente Firma', class: 'bg-yellow-100 text-yellow-800' };
        case 'returned':
            return { text: 'Devuelta', class: 'bg-red-100 text-red-800' };
        default:
            return { text: status, class: 'bg-gray-100 text-gray-800' };
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleSignatureUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (!file.type.startsWith('image/')) {
            alert('Por favor seleccione una imagen válida.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no puede ser mayor a 2MB.');
            return;
        }
        deliveryForm.signature = file;
    }
};

const markAsDelivered = () => {
    deliveryForm.post(route('correspondence.deliver', props.correspondence.id), {
        onSuccess: () => {
            showDeliveryDialog.value = false;
            deliveryForm.reset();
        },
        onError: () => {
            // Keep dialog open to show errors
            console.log('Validation errors:', deliveryForm.errors);
        },
        forceFormData: true,
    });
};

const openImageDialog = (imagePath: string) => {
    selectedImage.value = `/storage/${imagePath}`;
    showImageDialog.value = true;
};

// Get signature attachments
const signatureAttachments = computed(() => {
    return props.correspondence.attachments.filter((attachment) => attachment.type === 'signature');
});

// Get non-signature attachments for the general attachments section
const nonSignatureAttachments = computed(() => {
    return props.correspondence.attachments.filter((attachment) => attachment.type !== 'signature');
});

// Delete attachment function (to be implemented when needed)
// const deleteAttachment = (attachmentId: number) => {
//     if (confirm('¿Está seguro de que desea eliminar este archivo?')) {
//         console.log('Delete attachment:', attachmentId);
//     }
// };
</script>

<template>
    <Head :title="`Correspondencia ${correspondence.tracking_number}`" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('correspondence.index')"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Correspondencia {{ correspondence.tracking_number }}</h1>
                        <p class="mt-1 text-sm text-gray-600">Detalles de la correspondencia recibida</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <Link
                        v-if="canEdit"
                        :href="route('correspondence.edit', correspondence.id)"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                    </Link>

                    <Button v-if="canDeliver" @click="showDeliveryDialog = true" class="inline-flex items-center">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        Marcar como Entregada
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic information -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center space-x-2">
                                    <component :is="getTypeIcon(correspondence.type)" class="h-5 w-5" />
                                    <span>{{ getTypeLabel(correspondence.type) }}</span>
                                </CardTitle>
                                <Badge :class="getStatusBadge(correspondence.status).class">
                                    {{ getStatusBadge(correspondence.status).text }}
                                </Badge>
                            </div>
                            <CardDescription> Información general de la correspondencia </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-sm font-medium text-gray-500">Número de Seguimiento</Label>
                                    <p class="font-mono text-lg">{{ correspondence.tracking_number }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-gray-500">Tipo</Label>
                                    <p>{{ getTypeLabel(correspondence.type) }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-gray-500">Remitente</Label>
                                    <p>{{ correspondence.sender_name }}</p>
                                    <p v-if="correspondence.sender_company" class="text-sm text-gray-600">
                                        {{ correspondence.sender_company }}
                                    </p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-gray-500">Requiere Firma</Label>
                                    <p>{{ correspondence.requires_signature ? 'Sí' : 'No' }}</p>
                                </div>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-gray-500">Descripción</Label>
                                <p class="mt-1">{{ correspondence.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Delivery information -->
                    <Card v-if="correspondence.delivered_at">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <CheckCircle class="h-5 w-5 text-green-600" />
                                <span>Información de Entrega</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-sm font-medium text-gray-500">Entregado por</Label>
                                    <p>{{ correspondence.delivered_by?.name }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-gray-500">Fecha de Entrega</Label>
                                    <p>{{ formatDate(correspondence.delivered_at) }}</p>
                                </div>
                                <div v-if="correspondence.recipient_name">
                                    <Label class="text-sm font-medium text-gray-500">Recibido por</Label>
                                    <p>{{ correspondence.recipient_name }}</p>
                                    <p v-if="correspondence.recipient_document" class="text-sm text-gray-600">
                                        CC: {{ correspondence.recipient_document }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="correspondence.delivery_notes">
                                <Label class="text-sm font-medium text-gray-500">Notas de Entrega</Label>
                                <p class="mt-1">{{ correspondence.delivery_notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recipient Information -->
                    <Card v-if="correspondence.delivered_at && (correspondence.recipient_name || signatureAttachments.length > 0)">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <User class="h-5 w-5 text-blue-600" />
                                <span>Información del Receptor</span>
                            </CardTitle>
                            <CardDescription> Detalles de la persona que recibió la correspondencia </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Recipient Details -->
                            <div v-if="correspondence.recipient_name" class="rounded-lg bg-gray-50 p-4">
                                <div class="flex items-start space-x-3">
                                    <User class="mt-1 h-5 w-5 text-gray-600" />
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ correspondence.recipient_name }}</h4>
                                        <div v-if="correspondence.recipient_document" class="mt-1">
                                            <Label class="text-sm font-medium text-gray-500">Documento de Identidad</Label>
                                            <p class="font-mono text-sm">{{ correspondence.recipient_document }}</p>
                                        </div>
                                        <div class="mt-2">
                                            <Label class="text-sm font-medium text-gray-500">Fecha de Recepción</Label>
                                            <p class="text-sm">{{ formatDate(correspondence.delivered_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Signature Display -->
                            <div v-if="signatureAttachments.length > 0" class="space-y-3">
                                <div class="flex items-center space-x-2">
                                    <PenTool class="h-4 w-4 text-gray-600" />
                                    <Label class="text-sm font-medium text-gray-700">Firma Digital del Receptor</Label>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div
                                        v-for="signature in signatureAttachments"
                                        :key="signature.id"
                                        class="rounded-lg border border-gray-200 bg-white p-3"
                                    >
                                        <div class="group cursor-pointer" @click="openImageDialog(signature.file_path)">
                                            <div class="aspect-video overflow-hidden rounded-md border bg-gray-50">
                                                <img
                                                    :src="`/storage/${signature.file_path}`"
                                                    :alt="`Firma de ${correspondence.recipient_name || 'Receptor'}`"
                                                    class="h-full w-full object-contain transition-transform group-hover:scale-105"
                                                />
                                            </div>
                                            <div class="mt-2 text-center">
                                                <p class="text-xs text-gray-500">Haga clic para ampliar</p>
                                                <p class="text-xs text-gray-400">Capturada por {{ signature.uploaded_by.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Attachments (excluding signatures) -->
                    <Card v-if="nonSignatureAttachments.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Camera class="h-5 w-5" />
                                <span>Archivos Adjuntos</span>
                            </CardTitle>
                            <CardDescription> Evidencia fotográfica y documentos adjuntos (las firmas se muestran arriba) </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                                <div
                                    v-for="attachment in nonSignatureAttachments"
                                    :key="attachment.id"
                                    class="group relative cursor-pointer"
                                    @click="openImageDialog(attachment.file_path)"
                                >
                                    <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                        <img
                                            :src="`/storage/${attachment.file_path}`"
                                            :alt="attachment.original_filename"
                                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    </div>
                                    <div class="mt-2">
                                        <p class="truncate text-xs font-medium">{{ attachment.original_filename }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ attachment.type === 'photo_evidence' ? 'Evidencia' : 'Documento' }}
                                        </p>
                                        <p class="text-xs text-gray-400">Por {{ attachment.uploaded_by.name }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <MapPin class="h-5 w-5" />
                                <span>Destino</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <p class="text-lg font-semibold">{{ correspondence.apartment.tower }}-{{ correspondence.apartment.number }}</p>
                            <p class="text-sm text-gray-600">Piso {{ correspondence.apartment.floor }}</p>
                            <p class="text-sm text-gray-600">
                                {{ correspondence.apartment.apartment_type.name }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Calendar class="h-5 w-5" />
                                <span>Fechas</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Recibida</Label>
                                <p class="text-sm">{{ formatDate(correspondence.received_at) }}</p>
                                <p class="text-xs text-gray-500">Por {{ correspondence.received_by.name }}</p>
                            </div>

                            <div v-if="correspondence.delivered_at">
                                <Label class="text-sm font-medium text-gray-500">Entregada</Label>
                                <p class="text-sm">{{ formatDate(correspondence.delivered_at) }}</p>
                                <p class="text-xs text-gray-500">Por {{ correspondence.delivered_by?.name }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Delivery Dialog -->
        <Dialog v-model:open="showDeliveryDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Marcar como Entregada</DialogTitle>
                    <DialogDescription> Complete los detalles de la entrega de la correspondencia </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="markAsDelivered" class="space-y-4">
                    <!-- Show validation errors if any -->
                    <div v-if="Object.keys(deliveryForm.errors).length > 0" class="rounded-md border border-red-200 bg-red-50 p-3">
                        <h4 class="mb-2 text-sm font-medium text-red-800">Por favor corrija los siguientes errores:</h4>
                        <ul class="space-y-1 text-sm text-red-600">
                            <li v-for="(error, field) in deliveryForm.errors" :key="field">• {{ error }}</li>
                        </ul>
                    </div>

                    <div v-if="correspondence.requires_signature || Object.keys(deliveryForm.errors).length > 0" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="recipient_name"> Nombre de quien recibe {{ correspondence.requires_signature ? '*' : '' }} </Label>
                            <Input
                                id="recipient_name"
                                v-model="deliveryForm.recipient_name"
                                :class="{ 'border-red-500': deliveryForm.errors.recipient_name }"
                                :required="correspondence.requires_signature"
                            />
                            <div v-if="deliveryForm.errors.recipient_name" class="text-sm text-red-600">
                                {{ deliveryForm.errors.recipient_name }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="recipient_document">Número de documento</Label>
                            <Input id="recipient_document" v-model="deliveryForm.recipient_document" placeholder="Ej: 12345678" />
                        </div>

                        <div class="space-y-2">
                            <Label> Firma digital {{ correspondence.requires_signature ? '*' : '' }} </Label>
                            <input
                                ref="signatureInput"
                                type="file"
                                accept="image/*"
                                @change="handleSignatureUpload"
                                :class="{ 'border-red-500': deliveryForm.errors.signature }"
                                class="w-full rounded-md border border-gray-300 px-3 py-2"
                                :required="correspondence.requires_signature"
                            />
                            <p class="text-xs text-gray-500">
                                {{
                                    correspondence.requires_signature
                                        ? 'Capture o suba una imagen de la firma del receptor'
                                        : 'Opcional: capture o suba una imagen de la firma del receptor'
                                }}
                            </p>
                            <div v-if="deliveryForm.errors.signature" class="text-sm text-red-600">
                                {{ deliveryForm.errors.signature }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="delivery_notes">Notas de entrega (opcional)</Label>
                        <Textarea
                            id="delivery_notes"
                            v-model="deliveryForm.delivery_notes"
                            placeholder="Observaciones sobre la entrega..."
                            rows="3"
                        />
                    </div>

                    <div class="flex justify-end space-x-2">
                        <Button type="button" variant="outline" @click="showDeliveryDialog = false"> Cancelar </Button>
                        <Button type="submit" :disabled="deliveryForm.processing">
                            {{ deliveryForm.processing ? 'Procesando...' : 'Marcar como Entregada' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Image Dialog -->
        <Dialog v-model:open="showImageDialog">
            <DialogContent class="sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Vista de Imagen</DialogTitle>
                </DialogHeader>
                <div class="flex justify-center">
                    <img :src="selectedImage" alt="Imagen ampliada" class="max-h-96 max-w-full object-contain" />
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
