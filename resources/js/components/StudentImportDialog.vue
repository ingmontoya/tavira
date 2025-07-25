<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Download, FileSpreadsheet, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ImportResult {
    success: boolean;
    message: string;
    imported_count?: number;
    errors?: Array<{
        row: number;
        field: string;
        message: string;
    }>;
}

const emit = defineEmits<{
    success: [result: ImportResult];
}>();

const isOpen = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const importProgress = ref(0);
const importResult = ref<ImportResult | null>(null);

const form = useForm({
    file: null as File | null,
});

const isValidFileType = computed(() => {
    if (!selectedFile.value) return true;
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
    ];
    return allowedTypes.includes(selectedFile.value.type) || selectedFile.value.name.endsWith('.csv');
});

const fileSize = computed(() => {
    if (!selectedFile.value) return '';
    const bytes = selectedFile.value.size;
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
});

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        selectedFile.value = file;
        form.file = file;
    }
};

const downloadTemplate = () => {
    window.location.href = '/students/import/template';
};

const submitImport = () => {
    if (!selectedFile.value || !isValidFileType.value) return;

    importProgress.value = 0;
    importResult.value = null;

    // Simulate progress
    const progressInterval = setInterval(() => {
        importProgress.value += 10;
        if (importProgress.value >= 90) {
            clearInterval(progressInterval);
        }
    }, 100);

    form.post('/students/import', {
        forceFormData: true,
        onProgress: (progress) => {
            if (progress.percentage) {
                importProgress.value = Math.min(progress.percentage, 90);
            }
        },
        onSuccess: (page) => {
            clearInterval(progressInterval);
            importProgress.value = 100;

            // Check for flash messages from redirect
            const flashData = (page as any).props?.flash || {};

            // Check if we have success data in flash
            if (flashData.import_success) {
                const result: ImportResult = {
                    success: true,
                    message: flashData.import_message || 'Estudiantes importados exitosamente',
                    imported_count: flashData.import_count || 0,
                };

                importResult.value = result;
                emit('success', result);

                // Close modal and reset form after success
                setTimeout(() => {
                    closeDialog();
                }, 2000);
            } else {
                // If no success flag, assume it's a successful import without explicit success data
                const result: ImportResult = {
                    success: true,
                    message: 'Estudiantes importados exitosamente',
                    imported_count: 0,
                };

                importResult.value = result;
                emit('success', result);

                // Close modal and reset form after success
                setTimeout(() => {
                    closeDialog();
                }, 2000);
            }
        },
        onError: (errors) => {
            clearInterval(progressInterval);
            importProgress.value = 0;

            // Check for import-specific errors from redirect
            if (errors.import_error) {
                const result: ImportResult = {
                    success: false,
                    message: errors.import_error,
                    errors: errors.import_validation_errors || [],
                };
                importResult.value = result;
            } else {
                // Handle regular form validation errors
                const result: ImportResult = {
                    success: false,
                    message: 'Error al importar estudiantes',
                    errors: Object.entries(errors).map(([field, message], index) => ({
                        row: index + 1,
                        field,
                        message: Array.isArray(message) ? message[0] : message,
                    })),
                };
                importResult.value = result;
            }
        },
        onFinish: () => {
            clearInterval(progressInterval);
        },
    });
};

const resetForm = () => {
    selectedFile.value = null;
    form.reset();
    form.clearErrors();
    importProgress.value = 0;
    importResult.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const closeDialog = () => {
    isOpen.value = false;
    setTimeout(resetForm, 300); // Allow dialog animation to complete
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button variant="outline">
                <FileSpreadsheet class="mr-2 h-4 w-4" />
                Importar Estudiantes
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Importar Estudiantes</DialogTitle>
                <DialogDescription>
                    Importa estudiantes desde un archivo Excel (.xlsx) o CSV. Descarga la plantilla para ver el formato requerido.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Template Download -->
                <div class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3">
                    <div class="flex items-center gap-2">
                        <Download class="h-4 w-4 text-blue-600" />
                        <span class="text-sm text-blue-800">Descargar plantilla</span>
                    </div>
                    <Button variant="outline" size="sm" @click="downloadTemplate" class="border-blue-300 text-blue-600 hover:bg-blue-100">
                        <Download class="mr-1 h-3 w-3" />
                        Plantilla
                    </Button>
                </div>

                <!-- File Upload -->
                <div class="space-y-2">
                    <Label for="file">Archivo de Estudiantes</Label>
                    <div class="relative">
                        <Input id="file" ref="fileInput" type="file" accept=".xlsx,.xls,.csv" @change="onFileChange" class="cursor-pointer" />
                        <Upload class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                    </div>
                    <InputError :message="form.errors.file" />

                    <!-- File Info -->
                    <div v-if="selectedFile" class="text-sm text-gray-600">
                        <div class="flex items-center justify-between">
                            <span>{{ selectedFile.name }}</span>
                            <span>{{ fileSize }}</span>
                        </div>
                    </div>

                    <!-- File Type Error -->
                    <Alert v-if="selectedFile && !isValidFileType" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription> Tipo de archivo no válido. Solo se permiten archivos .xlsx, .xls y .csv </AlertDescription>
                    </Alert>
                </div>

                <!-- Import Progress -->
                <div v-if="form.processing || importProgress > 0" class="space-y-2">
                    <Label>Progreso de Importación</Label>
                    <Progress :value="importProgress" class="w-full" />
                    <p class="text-sm text-gray-600">{{ importProgress }}% completado</p>
                </div>

                <!-- Import Results -->
                <div v-if="importResult" class="space-y-2">
                    <Alert :variant="importResult.success ? 'success' : 'destructive'">
                        <CheckCircle2 v-if="importResult.success" class="h-4 w-4" />
                        <AlertCircle v-else class="h-4 w-4" />
                        <AlertDescription>
                            {{ importResult.message }}
                            <span v-if="importResult.imported_count !== undefined && importResult.imported_count > 0">
                                ({{ importResult.imported_count }} estudiantes procesados)
                            </span>
                        </AlertDescription>
                    </Alert>

                    <!-- Error Details -->
                    <div v-if="importResult.errors && importResult.errors.length > 0" class="space-y-1">
                        <Label class="text-red-600">Errores encontrados:</Label>
                        <div class="max-h-32 space-y-1 overflow-y-auto">
                            <div
                                v-for="error in importResult.errors.slice(0, 5)"
                                :key="`${error.row}-${error.field}`"
                                class="rounded border border-red-200 bg-red-50 p-2 text-xs"
                            >
                                <strong>Fila {{ error.row }}, Campo "{{ error.field }}":</strong> {{ error.message }}
                            </div>
                            <div v-if="importResult.errors.length > 5" class="text-xs text-gray-600">
                                ... y {{ importResult.errors.length - 5 }} errores más
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Instructions -->
                <div class="space-y-1 text-xs text-gray-500">
                    <p><strong>Formato requerido:</strong></p>
                    <ul class="ml-2 list-inside list-disc space-y-0.5">
                        <li>Código de estudiante (requerido)</li>
                        <li>Nombres y apellidos (requeridos)</li>
                        <li>Documento de identidad (requerido)</li>
                        <li>Teléfono y género</li>
                        <li>Emails personal e institucional</li>
                        <li>Ubicación (departamento, ciudad, dirección)</li>
                    </ul>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeDialog"> Cancelar </Button>
                <Button @click="submitImport" :disabled="!selectedFile || !isValidFileType || form.processing">
                    <FileSpreadsheet class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Importando...' : 'Importar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
