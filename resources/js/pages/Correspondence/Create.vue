<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Package, Upload, X, ArrowLeft } from 'lucide-vue-next';
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

defineProps<{
    apartments: Apartment[];
}>();

const form = useForm({
    sender_name: '',
    sender_company: '',
    type: 'package' as 'package' | 'letter' | 'document' | 'other',
    description: '',
    apartment_id: '',
    requires_signature: false,
    photos: [] as File[],
});

const fileInput = ref<HTMLInputElement>();
const selectedFiles = ref<File[]>([]);

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const newFiles = Array.from(target.files);

        // Validate file types and size
        const validFiles = newFiles.filter(file => {
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

        // Limit to 5 files total
        const totalFiles = [...selectedFiles.value, ...validFiles];
        if (totalFiles.length > 5) {
            alert('Máximo 5 imágenes permitidas.');
            selectedFiles.value = totalFiles.slice(0, 5);
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

const submit = () => {
    form.post(route('correspondence.store'), {
        forceFormData: true,
    });
};

const getApartmentDisplay = (apartment: Apartment) => {
    const residents = apartment.residents.map(r => `${r.first_name} ${r.last_name}`).join(', ');
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
    <Head title="Nueva Correspondencia" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('correspondence.index')"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Volver
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Nueva Correspondencia</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Registrar nueva correspondencia recibida
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main form -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <Package class="w-5 h-5" />
                                    <span>Información de la Correspondencia</span>
                                </CardTitle>
                                <CardDescription>
                                    Complete los datos de la correspondencia recibida
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label for="sender_name">Nombre del Remitente *</Label>
                                        <Input
                                            id="sender_name"
                                            v-model="form.sender_name"
                                            :class="{ 'border-red-500': form.errors.sender_name }"
                                            placeholder="Ej: Juan Pérez"
                                        />
                                        <div v-if="form.errors.sender_name" class="text-red-600 text-sm">
                                            {{ form.errors.sender_name }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="sender_company">Empresa (Opcional)</Label>
                                        <Input
                                            id="sender_company"
                                            v-model="form.sender_company"
                                            placeholder="Ej: Servientrega, DHL"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                        <div v-if="form.errors.type" class="text-red-600 text-sm">
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
                                                <SelectItem
                                                    v-for="apartment in apartments"
                                                    :key="apartment.id"
                                                    :value="apartment.id.toString()"
                                                >
                                                    {{ getApartmentDisplay(apartment) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.apartment_id" class="text-red-600 text-sm">
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
                                    <div v-if="form.errors.description" class="text-red-600 text-sm">
                                        {{ form.errors.description }}
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="requires_signature"
                                        :checked="form.requires_signature"
                                        @update:checked="form.requires_signature = $event"
                                    />
                                    <Label for="requires_signature" class="text-sm font-medium">
                                        Requiere firma al entregar
                                    </Label>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Photo evidence -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Evidencia Fotográfica</CardTitle>
                                <CardDescription>
                                    Adjunte fotos como evidencia de la correspondencia recibida (máximo 5 imágenes, 2MB cada una)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        multiple
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleFileUpload"
                                    />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="triggerFileInput"
                                        :disabled="selectedFiles.length >= 5"
                                        class="w-full"
                                    >
                                        <Upload class="w-4 h-4 mr-2" />
                                        {{ selectedFiles.length > 0 ? 'Agregar más imágenes' : 'Seleccionar imágenes' }}
                                    </Button>

                                    <div v-if="form.errors.photos" class="text-red-600 text-sm">
                                        {{ form.errors.photos }}
                                    </div>

                                    <!-- Preview selected files -->
                                    <div v-if="selectedFiles.length > 0" class="space-y-2">
                                        <div
                                            v-for="(file, index) in selectedFiles"
                                            :key="index"
                                            class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
                                        >
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-gray-200 rounded-md flex items-center justify-center overflow-hidden">
                                                    <img
                                                        :src="window.URL.createObjectURL(file)"
                                                        :alt="file.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
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
                                                <X class="w-4 h-4" />
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
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full"
                                >
                                    {{ form.processing ? 'Registrando...' : 'Registrar Correspondencia' }}
                                </Button>

                                <Link
                                    :href="route('correspondence.index')"
                                    class="inline-flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancelar
                                </Link>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-sm">Información</CardTitle>
                            </CardHeader>
                            <CardContent class="text-sm text-gray-600 space-y-2">
                                <p>• Se generará automáticamente un número de seguimiento</p>
                                <p>• La fecha de recepción será la actual</p>
                                <p>• Las imágenes se almacenan de forma segura</p>
                                <p>• Se enviará notificación al residente</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
