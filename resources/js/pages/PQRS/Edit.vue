<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, FileText, MessageSquare, Paperclip, Trash2, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface PqrsAttachment {
    id: number;
    filename: string;
    original_filename: string;
    mime_type: string;
    file_size: number;
    file_path: string;
    type: 'evidence' | 'document' | 'photo';
    uploaded_by: {
        id: number;
        name: string;
    };
    created_at: string;
    type_display: string;
    file_size_formatted: string;
}

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    apartment_type: {
        id: number;
        name: string;
    };
    residents: Array<{
        id: number;
        name: string;
        email: string;
    }>;
}

interface Pqrs {
    id: number;
    ticket_number: string;
    type: 'peticion' | 'queja' | 'reclamo' | 'sugerencia';
    subject: string;
    description: string;
    priority: 'baja' | 'media' | 'alta' | 'urgente';
    status: 'abierto' | 'en_proceso' | 'resuelto' | 'cerrado';
    contact_name: string;
    contact_email: string;
    contact_phone?: string;
    apartment_id?: number;
    attachments: PqrsAttachment[];
    type_display: string;
    priority_display: string;
    status_display: string;
    status_color: string;
    priority_color: string;
}

const props = defineProps<{
    pqrs: Pqrs;
    apartments: Apartment[];
    userApartment?: Apartment;
}>();

const form = useForm({
    type: props.pqrs.type,
    subject: props.pqrs.subject,
    description: props.pqrs.description,
    priority: props.pqrs.priority,
    apartment_id: props.pqrs.apartment_id || null,
    contact_name: props.pqrs.contact_name,
    contact_email: props.pqrs.contact_email,
    contact_phone: props.pqrs.contact_phone || '',
    attachments: [] as File[],
});

const fileInput = ref<HTMLInputElement>();
const selectedFiles = ref<File[]>([]);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const newFiles = Array.from(target.files);
        selectedFiles.value.push(...newFiles);
        form.attachments = selectedFiles.value;
    }
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    form.attachments = selectedFiles.value;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const deleteExistingAttachment = (attachmentId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este archivo?')) {
        router.delete(route('pqrs.attachments.destroy', attachmentId), {
            preserveScroll: true,
        });
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const submit = () => {
    form.patch(route('pqrs.update', props.pqrs.id), {
        forceFormData: true,
    });
};

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'peticion':
            return FileText;
        case 'queja':
            return AlertCircle;
        case 'reclamo':
            return MessageSquare;
        case 'sugerencia':
            return MessageSquare;
        default:
            return FileText;
    }
};

const typeOptions = [
    { value: 'peticion', label: 'Petición', icon: FileText, description: 'Solicitar información o documentos' },
    { value: 'queja', label: 'Queja', icon: AlertCircle, description: 'Expresar insatisfacción por un servicio' },
    { value: 'reclamo', label: 'Reclamo', icon: MessageSquare, description: 'Solicitar corrección de una situación' },
    { value: 'sugerencia', label: 'Sugerencia', icon: MessageSquare, description: 'Proponer mejoras o ideas' },
];

const priorityOptions = [
    { value: 'baja', label: 'Baja', color: 'text-blue-600', description: 'No es urgente, puede esperar' },
    { value: 'media', label: 'Media', color: 'text-yellow-600', description: 'Importante pero no urgente' },
    { value: 'alta', label: 'Alta', color: 'text-orange-600', description: 'Requiere atención pronta' },
    { value: 'urgente', label: 'Urgente', color: 'text-red-600', description: 'Requiere atención inmediata' },
];

const breadcrumbs = [
    { label: 'PQRS', href: route('pqrs.index'), current: false },
    { label: props.pqrs.ticket_number, href: route('pqrs.show', props.pqrs.id), current: false },
    { label: 'Editar', href: '#', current: true }
];
</script>

<template>
    <Head title="Editar PQRS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <component :is="getTypeIcon(pqrs.type)" class="h-6 w-6 text-gray-400" />
                        <h1 class="text-2xl font-semibold text-gray-900">{{ pqrs.ticket_number }}</h1>
                        <Badge :class="pqrs.status_color">{{ pqrs.status_display }}</Badge>
                        <Badge :class="pqrs.priority_color">{{ pqrs.priority_display }}</Badge>
                    </div>
                    <p class="text-gray-600">Editar tu solicitud de PQRS</p>
                </div>
                <Button :as="Link" :href="route('pqrs.show', pqrs.id)" variant="outline" class="flex items-center gap-2">
                    <ArrowLeft class="h-4 w-4" />
                    Volver
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Tipo de PQRS</CardTitle>
                        <CardDescription>Selecciona el tipo de solicitud que deseas realizar</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                v-for="typeOption in typeOptions"
                                :key="typeOption.value"
                                class="relative"
                            >
                                <input
                                    :id="typeOption.value"
                                    v-model="form.type"
                                    :value="typeOption.value"
                                    type="radio"
                                    name="type"
                                    class="peer sr-only"
                                />
                                <label
                                    :for="typeOption.value"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border-2 border-gray-200 p-4 text-sm transition-all hover:border-gray-300 peer-checked:border-primary peer-checked:bg-primary/5"
                                >
                                    <component :is="typeOption.icon" class="mt-0.5 h-5 w-5 text-gray-400" />
                                    <div>
                                        <div class="font-medium text-gray-900">{{ typeOption.label }}</div>
                                        <div class="text-gray-500">{{ typeOption.description }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div v-if="form.errors.type" class="text-sm text-red-600">{{ form.errors.type }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalles del PQRS</CardTitle>
                        <CardDescription>Proporciona información detallada sobre tu solicitud</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <Label for="subject">Asunto *</Label>
                                <Input
                                    id="subject"
                                    v-model="form.subject"
                                    placeholder="Resumen breve de tu solicitud"
                                    :class="{ 'border-red-500': form.errors.subject }"
                                />
                                <div v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</div>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="description">Descripción *</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Describe detalladamente tu petición, queja, reclamo o sugerencia..."
                                    rows="6"
                                    :class="{ 'border-red-500': form.errors.description }"
                                />
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <Label for="priority">Prioridad *</Label>
                                <Select v-model="form.priority">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.priority }">
                                        <SelectValue placeholder="Selecciona la prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="priority in priorityOptions"
                                            :key="priority.value"
                                            :value="priority.value"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span :class="priority.color" class="font-medium">{{ priority.label }}</span>
                                                <span class="text-gray-500">- {{ priority.description }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.priority" class="mt-1 text-sm text-red-600">{{ form.errors.priority }}</div>
                            </div>

                            <div v-if="apartments.length > 0">
                                <Label for="apartment_id">Apartamento</Label>
                                <Select v-model="form.apartment_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un apartamento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Sin apartamento específico</SelectItem>
                                        <SelectItem
                                            v-for="apartment in apartments"
                                            :key="apartment.id"
                                            :value="apartment.id"
                                        >
                                            {{ apartment.tower }}{{ apartment.number }} - {{ apartment.apartment_type.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.apartment_id" class="mt-1 text-sm text-red-600">{{ form.errors.apartment_id }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Información de Contacto</CardTitle>
                        <CardDescription>Datos para dar seguimiento a tu solicitud</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <Label for="contact_name">Nombre completo *</Label>
                                <Input
                                    id="contact_name"
                                    v-model="form.contact_name"
                                    placeholder="Tu nombre completo"
                                    :class="{ 'border-red-500': form.errors.contact_name }"
                                />
                                <div v-if="form.errors.contact_name" class="mt-1 text-sm text-red-600">{{ form.errors.contact_name }}</div>
                            </div>

                            <div>
                                <Label for="contact_email">Correo electrónico *</Label>
                                <Input
                                    id="contact_email"
                                    v-model="form.contact_email"
                                    type="email"
                                    placeholder="tucorreo@ejemplo.com"
                                    :class="{ 'border-red-500': form.errors.contact_email }"
                                />
                                <div v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_email }}</div>
                            </div>

                            <div>
                                <Label for="contact_phone">Teléfono (opcional)</Label>
                                <Input
                                    id="contact_phone"
                                    v-model="form.contact_phone"
                                    placeholder="3001234567"
                                    :class="{ 'border-red-500': form.errors.contact_phone }"
                                />
                                <div v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Existing Attachments -->
                <Card v-if="pqrs.attachments.length > 0">
                    <CardHeader>
                        <CardTitle>Archivos actuales</CardTitle>
                        <CardDescription>{{ pqrs.attachments.length }} archivo(s) adjunto(s)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="attachment in pqrs.attachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <Paperclip class="h-4 w-4 text-gray-400" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ attachment.original_filename }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ attachment.file_size_formatted }} • {{ attachment.type_display }}
                                            • Subido por {{ attachment.uploaded_by.name }}
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="deleteExistingAttachment(attachment.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- New Attachments -->
                <Card>
                    <CardHeader>
                        <CardTitle>Agregar archivos adjuntos (opcional)</CardTitle>
                        <CardDescription>Agrega documentos, fotos o evidencias adicionales</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-6 transition-colors hover:border-gray-400"
                            @click="triggerFileInput"
                        >
                            <div class="text-center">
                                <Upload class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm font-medium text-gray-900">Haz clic para agregar archivos</p>
                                <p class="mt-1 text-xs text-gray-500">PDF, Word, Excel, imágenes (máx. 10MB por archivo)</p>
                            </div>
                        </div>

                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                            class="hidden"
                            @change="handleFileSelect"
                        />

                        <div v-if="selectedFiles.length > 0" class="space-y-2">
                            <h4 class="font-medium text-gray-900">Archivos nuevos:</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(file, index) in selectedFiles"
                                    :key="index"
                                    class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <Paperclip class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                            <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeFile(index)"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.attachments" class="text-sm text-red-600">{{ form.errors.attachments }}</div>
                    </CardContent>
                </Card>

                <div class="flex items-center justify-end gap-4">
                    <Button :as="Link" :href="route('pqrs.show', pqrs.id)" variant="outline">
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2"
                    >
                        <component :is="getTypeIcon(form.type)" class="h-4 w-4" />
                        {{ form.processing ? 'Actualizando...' : 'Actualizar PQRS' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>