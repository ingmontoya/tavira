<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Package, Trash2, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    apartment_type: {
        name: string;
    };
    residents: Array<{
        first_name: string;
        last_name: string;
        resident_type: string;
    }>;
}

interface Attachment {
    id: number;
    type: 'photo_evidence' | 'signature' | 'document';
    filename: string;
    original_filename: string;
    file_path: string;
}

interface Correspondence {
    id: number;
    tracking_number: string;
    sender_name: string;
    sender_company: string | null;
    type: 'package' | 'letter' | 'document' | 'other';
    description: string;
    apartment_id: number;
    requires_signature: boolean;
    attachments: Attachment[];
}

const props = defineProps<{
    correspondence: Correspondence;
    apartments: Apartment[];
}>();

const form = useForm({
    sender_name: props.correspondence.sender_name,
    sender_company: props.correspondence.sender_company || '',
    type: props.correspondence.type,
    description: props.correspondence.description,
    apartment_id: props.correspondence.apartment_id.toString(),
    requires_signature: props.correspondence.requires_signature,
    photos: [] as File[],
});

const fileInput = ref<HTMLInputElement>();
const selectedFiles = ref<File[]>([]);

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const newFiles = Array.from(target.files);

        // Validate file types and size
        const validFiles = newFiles.filter((file) => {
            const isValidType = file.type.startsWith('image/');
            const isValidSize = file.size <= 2 * 1024 * 1024; // 2MB

            if (!isValidType) {
                alert(`${file.name} no es una imagen válida.`);
                return false;
            }
            if (!isValidSize) {
                alert(`${file.name} es muy grande. Máximo 2MB por imagen.`);
                return false;
            }
            return true;
        });

        // Limit to 5 files total (including existing attachments)
        const totalFiles = [...selectedFiles.value, ...validFiles];
        const maxNewFiles = 5 - props.correspondence.attachments.length;

        if (totalFiles.length > maxNewFiles) {
            alert(`Máximo ${maxNewFiles} imágenes adicionales permitidas.`);
            selectedFiles.value = totalFiles.slice(0, maxNewFiles);
        } else {
            selectedFiles.value = totalFiles;
        }

        form.photos = selectedFiles.value;
    }

    // Reset input
    if (target) {
        target.value = '';
    }
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    form.photos = selectedFiles.value;
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const deleteAttachment = (attachmentId: number) => {
    if (confirm('¿Está seguro de que desea eliminar este archivo?')) {
        // Using Inertia to delete attachment
        // This would call the deleteAttachment method in the controller
        // For now, we'll use a simple form submission
        const deleteForm = useForm({});
        deleteForm.delete(route('correspondence.attachments.destroy', attachmentId), {
            preserveScroll: true,
        });
    }
};

const submit = () => {
    form.put(route('correspondence.update', props.correspondence.id), {
        forceFormData: true,
    });
};

const getApartmentDisplay = (apartment: Apartment) => {
    const residents = apartment.residents.map((r) => `${r.first_name} ${r.last_name}`).join(', ');
    return `${apartment.tower}-${apartment.number} - ${apartment.apartment_type.name}${residents ? ` (${residents})` : ''}`;
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
    <Head :title="`Editar Correspondencia ${correspondence.tracking_number}`" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('correspondence.show', correspondence.id)"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Editar Correspondencia {{ correspondence.tracking_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Modificar información de la correspondencia</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main form -->
                    <div class="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <Package class="h-5 w-5" />
                                    <span>Información de la Correspondencia</span>
                                </CardTitle>
                                <CardDescription> Modifique los datos de la correspondencia </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="sender_name">Nombre del Remitente *</Label>
                                        <Input
                                            id="sender_name"
                                            v-model="form.sender_name"
                                            :class="{ 'border-red-500': form.errors.sender_name }"
                                            placeholder="Ej: Juan Pérez"
                                        />
                                        <div v-if="form.errors.sender_name" class="text-sm text-red-600">
                                            {{ form.errors.sender_name }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="sender_company">Empresa (Opcional)</Label>
                                        <Input id="sender_company" v-model="form.sender_company" placeholder="Ej: Servientrega, DHL" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="type">Tipo de Correspondencia *</Label>
                                        <Select v-model="form.type">
                                            <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                                                <SelectValue placeholder="Seleccionar tipo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="package">Paquete</SelectItem>
                                                <SelectItem value="letter">Carta</SelectItem>
                                                <SelectItem value="document">Documento</SelectItem>
                                                <SelectItem value="other">Otro</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.type" class="text-sm text-red-600">
                                            {{ form.errors.type }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="apartment_id">Apartamento Destino *</Label>
                                        <Select v-model="form.apartment_id">
                                            <SelectTrigger :class="{ 'border-red-500': form.errors.apartment_id }">
                                                <SelectValue placeholder="Seleccionar apartamento" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                                    {{ getApartmentDisplay(apartment) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.apartment_id" class="text-sm text-red-600">
                                            {{ form.errors.apartment_id }}
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="description">Descripción *</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        :class="{ 'border-red-500': form.errors.description }"
                                        placeholder="Describe el contenido o características de la correspondencia..."
                                        rows="3"
                                    />
                                    <div v-if="form.errors.description" class="text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="requires_signature"
                                        :checked="form.requires_signature"
                                        @update:checked="form.requires_signature = $event"
                                    />
                                    <Label for="requires_signature" class="text-sm font-medium"> Requiere firma al entregar </Label>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Existing attachments -->
                        <Card v-if="correspondence.attachments.length > 0">
                            <CardHeader>
                                <CardTitle>Archivos Existentes</CardTitle>
                                <CardDescription> Archivos ya adjuntos a esta correspondencia </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                                    <div v-for="attachment in correspondence.attachments" :key="attachment.id" class="group relative">
                                        <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                            <img
                                                :src="`/storage/${attachment.file_path}`"
                                                :alt="attachment.original_filename"
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div class="mt-2">
                                            <p class="truncate text-xs font-medium">{{ attachment.original_filename }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    attachment.type === 'photo_evidence'
                                                        ? 'Evidencia'
                                                        : attachment.type === 'signature'
                                                          ? 'Firma'
                                                          : 'Documento'
                                                }}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            class="absolute top-2 right-2 opacity-0 transition-opacity group-hover:opacity-100"
                                            @click="deleteAttachment(attachment.id)"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- New photo evidence -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Agregar Evidencia Fotográfica</CardTitle>
                                <CardDescription>
                                    Adjunte fotos adicionales como evidencia (máximo {{ 5 - correspondence.attachments.length }} imágenes más, 2MB
                                    cada una)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <input ref="fileInput" type="file" multiple accept="image/*" class="hidden" @change="handleFileUpload" />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="triggerFileInput"
                                        :disabled="selectedFiles.length + correspondence.attachments.length >= 5"
                                        class="w-full"
                                    >
                                        <Upload class="mr-2 h-4 w-4" />
                                        {{ selectedFiles.length > 0 ? 'Agregar más imágenes' : 'Seleccionar imágenes' }}
                                    </Button>

                                    <div v-if="form.errors.photos" class="text-sm text-red-600">
                                        {{ form.errors.photos }}
                                    </div>

                                    <!-- Preview selected files -->
                                    <div v-if="selectedFiles.length > 0" class="space-y-2">
                                        <div
                                            v-for="(file, index) in selectedFiles"
                                            :key="index"
                                            class="flex items-center justify-between rounded-md bg-gray-50 p-3"
                                        >
                                            <div class="flex items-center space-x-3">
                                                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-md bg-gray-200">
                                                    <img
                                                        :src="window.URL.createObjectURL(file)"
                                                        :alt="file.name"
                                                        class="h-full w-full object-cover"
                                                    />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                                    <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                                                </div>
                                            </div>
                                            <Button type="button" variant="ghost" size="sm" @click="removeFile(index)">
                                                <X class="h-4 w-4" />
                                            </Button>
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
                                <CardTitle>Acciones</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <Button type="submit" :disabled="form.processing" class="w-full">
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>

                                <Link
                                    :href="route('correspondence.show', correspondence.id)"
                                    class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancelar
                                </Link>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-sm">Información</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm text-gray-600">
                                <p><strong>Número de seguimiento:</strong></p>
                                <p class="font-mono">{{ correspondence.tracking_number }}</p>
                                <hr class="my-2" />
                                <p>• El número de seguimiento no se puede modificar</p>
                                <p>• Las fechas de recepción no se modifican</p>
                                <p>• Los archivos eliminados no se pueden recuperar</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
