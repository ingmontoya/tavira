<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import { AlertCircle, CheckCircle, Download, FileText, Image, Plus, Trash2, Upload } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

export interface MaintenanceRequestDocument {
    id: number;
    name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    formatted_file_size: string;
    stage: string;
    document_type: string;
    description: string | null;
    is_evidence: boolean;
    is_required_approval: boolean;
    url: string;
    uploaded_by: {
        id: number;
        name: string;
    };
    created_at: string;
}

interface Props {
    maintenanceRequestId: number;
    canEdit: boolean;
}

const props = defineProps<Props>();
const { error: showError, success: showSuccess } = useToast();

const documents = ref<MaintenanceRequestDocument[]>([]);
const documentsByStage = ref<Record<string, MaintenanceRequestDocument[]>>({});
const stageLabels = ref<Record<string, string>>({});
const documentTypeLabels = ref<Record<string, string>>({});
const loading = ref(false);
const uploadDialogOpen = ref(false);

// Upload form
const uploadForm = ref({
    files: [] as File[],
    stage: '',
    document_type: '',
    description: '',
    is_evidence: false,
    is_required_approval: false,
});

const stages = computed(() => {
    return Object.entries(stageLabels.value).map(([key, label]) => ({
        value: key,
        label,
    }));
});

const documentTypes = computed(() => {
    return Object.entries(documentTypeLabels.value).map(([key, label]) => ({
        value: key,
        label,
    }));
});

const loadDocuments = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/maintenance-requests/${props.maintenanceRequestId}/documents`);

        documents.value = response.data.documents;
        documentsByStage.value = response.data.documentsByStage;
        stageLabels.value = response.data.stageLabels;
        documentTypeLabels.value = response.data.documentTypeLabels;
    } catch (error) {
        console.error('Error loading documents:', error);
        showError('No se pudieron cargar los documentos.', 'Error');
    } finally {
        loading.value = false;
    }
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        uploadForm.value.files = Array.from(target.files);
    }
};

const uploadDocuments = async () => {
    if (uploadForm.value.files.length === 0) {
        showError('Por favor selecciona al menos un archivo.', 'Error');
        return;
    }

    if (!uploadForm.value.stage || !uploadForm.value.document_type) {
        showError('Por favor completa todos los campos requeridos.', 'Error');
        return;
    }

    try {
        loading.value = true;

        const formData = new FormData();
        uploadForm.value.files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        formData.append('stage', uploadForm.value.stage);
        formData.append('document_type', uploadForm.value.document_type);
        formData.append('description', uploadForm.value.description);
        formData.append('is_evidence', uploadForm.value.is_evidence ? '1' : '0');
        formData.append('is_required_approval', uploadForm.value.is_required_approval ? '1' : '0');

        const response = await axios.post(`/maintenance-requests/${props.maintenanceRequestId}/documents`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        showSuccess(response.data.message, 'Éxito');

        // Reset form
        uploadForm.value = {
            files: [],
            stage: '',
            document_type: '',
            description: '',
            is_evidence: false,
            is_required_approval: false,
        };

        uploadDialogOpen.value = false;
        await loadDocuments();
    } catch (error: any) {
        console.error('Error uploading documents:', error);
        showError(error.response?.data?.message || 'No se pudieron subir los documentos.', 'Error');
    } finally {
        loading.value = false;
    }
};

const downloadDocument = (document: MaintenanceRequestDocument) => {
    window.open(`/maintenance-requests/${props.maintenanceRequestId}/documents/${document.id}/download`, '_blank');
};

const deleteDocument = async (document: MaintenanceRequestDocument) => {
    if (!confirm('¿Estás seguro de que quieres eliminar este documento?')) {
        return;
    }

    try {
        loading.value = true;

        await axios.delete(`/maintenance-requests/${props.maintenanceRequestId}/documents/${document.id}`);

        showSuccess('Documento eliminado exitosamente.', 'Éxito');

        await loadDocuments();
    } catch (error: any) {
        console.error('Error deleting document:', error);
        showError(error.response?.data?.message || 'No se pudo eliminar el documento.', 'Error');
    } finally {
        loading.value = false;
    }
};

const getDocumentIcon = (document: MaintenanceRequestDocument) => {
    if (document.file_type.startsWith('image/')) {
        return Image;
    }
    return FileText;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(() => {
    loadDocuments();
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <FileText class="h-5 w-5" />
                    Documentación
                </CardTitle>
                <Dialog v-if="canEdit" v-model:open="uploadDialogOpen">
                    <DialogTrigger asChild>
                        <Button size="sm" class="gap-2">
                            <Plus class="h-4 w-4" />
                            Subir Documentos
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-md">
                        <DialogHeader>
                            <DialogTitle>Subir Documentos</DialogTitle>
                        </DialogHeader>
                        <div class="space-y-4">
                            <div>
                                <Label for="files">Archivos (máx. 10MB c/u)</Label>
                                <Input
                                    id="files"
                                    type="file"
                                    multiple
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                    @change="handleFileSelect"
                                />
                                <p class="mt-1 text-sm text-muted-foreground">Formatos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX</p>
                            </div>

                            <div>
                                <Label for="stage">Etapa *</Label>
                                <Select v-model="uploadForm.stage">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una etapa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="stage in stages" :key="stage.value" :value="stage.value">
                                            {{ stage.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label for="document_type">Tipo de Documento *</Label>
                                <Select v-model="uploadForm.document_type">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in documentTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label for="description">Descripción</Label>
                                <Textarea
                                    id="description"
                                    v-model="uploadForm.description"
                                    placeholder="Descripción opcional del documento..."
                                    rows="2"
                                />
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="is_evidence" v-model:checked="uploadForm.is_evidence" />
                                    <Label for="is_evidence" class="text-sm">Marcar como evidencia</Label>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox id="is_required_approval" v-model:checked="uploadForm.is_required_approval" />
                                    <Label for="is_required_approval" class="text-sm">Requerido para aprobación</Label>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button variant="outline" @click="uploadDialogOpen = false"> Cancelar </Button>
                                <Button @click="uploadDocuments" :disabled="loading">
                                    <Upload class="mr-2 h-4 w-4" />
                                    {{ loading ? 'Subiendo...' : 'Subir' }}
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="loading && documents.length === 0" class="py-8 text-center">
                <p class="text-muted-foreground">Cargando documentos...</p>
            </div>

            <div v-else-if="documents.length === 0" class="py-8 text-center">
                <FileText class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                <p class="text-muted-foreground">No hay documentos adjuntos</p>
                <p class="text-sm text-muted-foreground">
                    {{ canEdit ? 'Sube documentos para mantener un registro completo del proceso.' : '' }}
                </p>
            </div>

            <Tabs v-else defaultValue="all" class="w-full">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="all">Todos ({{ documents.length }})</TabsTrigger>
                    <TabsTrigger value="evidence">Evidencia</TabsTrigger>
                    <TabsTrigger value="approval">Requeridos</TabsTrigger>
                </TabsList>

                <TabsContent value="all" class="space-y-4">
                    <div v-for="(stageDocuments, stage) in documentsByStage" :key="stage" class="space-y-2">
                        <h4 class="text-sm font-medium text-muted-foreground">
                            {{ stageLabels[stage] || stage }}
                        </h4>
                        <div class="grid gap-2">
                            <div
                                v-for="document in stageDocuments"
                                :key="document.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex flex-1 items-center gap-3">
                                    <component :is="getDocumentIcon(document)" class="h-5 w-5 text-muted-foreground" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium">{{ document.name }}</p>
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <span>{{ documentTypeLabels[document.document_type] }}</span>
                                            <span>•</span>
                                            <span>{{ document.formatted_file_size }}</span>
                                            <span>•</span>
                                            <span>{{ formatDate(document.created_at) }}</span>
                                        </div>
                                        <p v-if="document.description" class="mt-1 text-sm text-muted-foreground">
                                            {{ document.description }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge v-if="document.is_evidence" variant="secondary" class="gap-1">
                                            <CheckCircle class="h-3 w-3" />
                                            Evidencia
                                        </Badge>
                                        <Badge v-if="document.is_required_approval" variant="outline" class="gap-1">
                                            <AlertCircle class="h-3 w-3" />
                                            Requerido
                                        </Badge>
                                    </div>
                                </div>
                                <div class="ml-2 flex items-center gap-1">
                                    <Button variant="ghost" size="sm" @click="downloadDocument(document)" title="Descargar">
                                        <Download class="h-4 w-4" />
                                    </Button>
                                    <Button v-if="canEdit" variant="ghost" size="sm" @click="deleteDocument(document)" title="Eliminar">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent value="evidence" class="space-y-2">
                    <div
                        v-for="document in documents.filter((d) => d.is_evidence)"
                        :key="document.id"
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div class="flex flex-1 items-center gap-3">
                            <component :is="getDocumentIcon(document)" class="h-5 w-5 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ document.name }}</p>
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Badge variant="secondary" size="sm">{{ stageLabels[document.stage] }}</Badge>
                                    <span>•</span>
                                    <span>{{ documentTypeLabels[document.document_type] }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(document.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="ml-2 flex items-center gap-1">
                            <Button variant="ghost" size="sm" @click="downloadDocument(document)" title="Descargar">
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button v-if="canEdit" variant="ghost" size="sm" @click="deleteDocument(document)" title="Eliminar">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div v-if="!documents.some((d) => d.is_evidence)" class="py-4 text-center">
                        <p class="text-sm text-muted-foreground">No hay documentos marcados como evidencia</p>
                    </div>
                </TabsContent>

                <TabsContent value="approval" class="space-y-2">
                    <div
                        v-for="document in documents.filter((d) => d.is_required_approval)"
                        :key="document.id"
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div class="flex flex-1 items-center gap-3">
                            <component :is="getDocumentIcon(document)" class="h-5 w-5 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ document.name }}</p>
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Badge variant="outline" size="sm">{{ stageLabels[document.stage] }}</Badge>
                                    <span>•</span>
                                    <span>{{ documentTypeLabels[document.document_type] }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(document.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="ml-2 flex items-center gap-1">
                            <Button variant="ghost" size="sm" @click="downloadDocument(document)" title="Descargar">
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button v-if="canEdit" variant="ghost" size="sm" @click="deleteDocument(document)" title="Eliminar">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div v-if="!documents.some((d) => d.is_required_approval)" class="py-4 text-center">
                        <p class="text-sm text-muted-foreground">No hay documentos requeridos para aprobación</p>
                    </div>
                </TabsContent>
            </Tabs>
        </CardContent>
    </Card>
</template>
