<template>
    <div class="space-y-4">
        <div class="flex w-full items-center justify-center">
            <label for="file-upload" :class="dropzoneClasses" @drop="handleDrop" @dragover.prevent @dragenter.prevent @dragleave="isDragging = false">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="mb-4 h-8 w-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-500">{{ allowedTypesText }} (MAX. {{ maxSizeText }})</p>
                </div>
                <input id="file-upload" type="file" class="hidden" :multiple="multiple" :accept="accept" @change="handleFileSelect" />
            </label>
        </div>

        <!-- File List -->
        <div v-if="files.length > 0" class="space-y-2">
            <div v-for="(file, index) in files" :key="index" class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                <div class="flex items-center space-x-3">
                    <div :class="getFileIconClass(file.type)">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                        <p class="text-xs text-gray-500">{{ formatBytes(file.size) }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <div v-if="file.status === 'uploading'" class="flex items-center space-x-2">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></div>
                        <span class="text-sm text-gray-500">{{ file.progress }}%</span>
                    </div>

                    <div v-else-if="file.status === 'success'" class="text-green-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>

                    <div v-else-if="file.status === 'error'" class="text-red-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>

                    <button @click="removeFile(index)" class="text-red-600 transition-colors hover:text-red-800">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        <div v-if="errors.length > 0" class="space-y-2">
            <SecurityAlert v-for="error in errors" :key="error" type="error" :message="error" :dismissible="true" @dismiss="removeError(error)" />
        </div>

        <!-- Upload Button -->
        <div v-if="files.length > 0 && !autoUpload" class="flex justify-end">
            <button
                @click="uploadFiles"
                :disabled="isUploading"
                class="rounded-md bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ isUploading ? 'Uploading...' : 'Upload Files' }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useSecurity } from '../composables/useSecurity';
import SecurityAlert from './SecurityAlert.vue';

interface FileWithStatus extends File {
    status: 'pending' | 'uploading' | 'success' | 'error';
    progress: number;
    error?: string;
}

interface Props {
    multiple?: boolean;
    accept?: string;
    maxSize?: number;
    allowedTypes?: string[];
    uploadUrl?: string;
    autoUpload?: boolean;
    maxFiles?: number;
}

const props = withDefaults(defineProps<Props>(), {
    multiple: false,
    maxSize: 10485760, // 10MB
    allowedTypes: () => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'],
    autoUpload: false,
    maxFiles: 5,
});

const emit = defineEmits<{
    upload: [files: FileWithStatus[]];
    success: [files: FileWithStatus[]];
    error: [errors: string[]];
}>();

const { validateFile, formatBytes } = useSecurity();

const files = ref<FileWithStatus[]>([]);
const errors = ref<string[]>([]);
const isDragging = ref(false);
const isUploading = ref(false);

const dropzoneClasses = computed(() => ({
    'flex flex-col items-center justify-center w-full h-64 border-2 border-dashed rounded-lg cursor-pointer transition-colors': true,
    'border-gray-300 bg-gray-50 hover:bg-gray-100': !isDragging.value,
    'border-blue-400 bg-blue-50': isDragging.value,
}));

const allowedTypesText = computed(() => {
    const extensions = props.allowedTypes
        .map((type) => {
            const ext = type.split('/')[1];
            return ext.toUpperCase();
        })
        .join(', ');
    return extensions;
});

const maxSizeText = computed(() => formatBytes(props.maxSize));

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    const droppedFiles = Array.from(event.dataTransfer?.files || []);
    processFiles(droppedFiles);
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const selectedFiles = Array.from(target.files || []);
    processFiles(selectedFiles);
};

const processFiles = (newFiles: File[]) => {
    errors.value = [];

    // Check file count limit
    if (files.value.length + newFiles.length > props.maxFiles) {
        errors.value.push(`Maximum ${props.maxFiles} files allowed`);
        return;
    }

    const validFiles: FileWithStatus[] = [];

    for (const file of newFiles) {
        const validation = validateFile(file);

        if (!validation.valid) {
            errors.value.push(...validation.errors);
            continue;
        }

        // Additional size check
        if (file.size > props.maxSize) {
            errors.value.push(`${file.name} exceeds maximum size of ${maxSizeText.value}`);
            continue;
        }

        // Check if file already exists
        if (files.value.some((f) => f.name === file.name && f.size === file.size)) {
            errors.value.push(`${file.name} is already selected`);
            continue;
        }

        validFiles.push({
            ...file,
            status: 'pending',
            progress: 0,
        } as FileWithStatus);
    }

    files.value.push(...validFiles);

    if (props.autoUpload && validFiles.length > 0) {
        uploadFiles();
    }
};

const uploadFiles = async () => {
    if (!props.uploadUrl) {
        emit('upload', files.value);
        return;
    }

    isUploading.value = true;
    const pendingFiles = files.value.filter((file) => file.status === 'pending');

    for (const file of pendingFiles) {
        file.status = 'uploading';
        file.progress = 0;

        try {
            await uploadFile(file);
            file.status = 'success';
            file.progress = 100;
        } catch (error) {
            file.status = 'error';
            file.error = error instanceof Error ? error.message : 'Upload failed';
            errors.value.push(`Failed to upload ${file.name}: ${file.error}`);
        }
    }

    isUploading.value = false;

    const successFiles = files.value.filter((file) => file.status === 'success');
    const errorFiles = files.value.filter((file) => file.status === 'error');

    if (successFiles.length > 0) {
        emit('success', successFiles);
    }

    if (errorFiles.length > 0) {
        emit(
            'error',
            errorFiles.map((f) => f.error || 'Unknown error'),
        );
    }
};

const uploadFile = (file: FileWithStatus): Promise<void> => {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                file.progress = Math.round((event.loaded * 100) / event.total);
            }
        };

        xhr.onload = () => {
            if (xhr.status === 200) {
                resolve();
            } else {
                reject(new Error(`Upload failed with status ${xhr.status}`));
            }
        };

        xhr.onerror = () => {
            reject(new Error('Network error occurred'));
        };

        xhr.open('POST', props.uploadUrl!);
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
        xhr.send(formData);
    });
};

const removeFile = (index: number) => {
    files.value.splice(index, 1);
};

const removeError = (error: string) => {
    const index = errors.value.indexOf(error);
    if (index > -1) {
        errors.value.splice(index, 1);
    }
};

const getFileIconClass = (type: string) => {
    if (type.startsWith('image/')) {
        return 'text-green-600';
    } else if (type === 'application/pdf') {
        return 'text-red-600';
    } else if (type.includes('excel') || type.includes('spreadsheet')) {
        return 'text-green-600';
    } else {
        return 'text-blue-600';
    }
};
</script>
