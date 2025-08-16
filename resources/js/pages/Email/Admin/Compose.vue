<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, File, FileText, Image, Paperclip, Save, Send, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    view: string;
}

const props = defineProps<Props>();

const form = useForm({
    to: '',
    cc: '',
    bcc: '',
    subject: '',
    body: '',
    view: props.view,
    attachments: [] as File[],
});

const showCc = ref(false);
const showBcc = ref(false);
const isDraft = ref(false);
const fileInput = ref<HTMLInputElement>();

const sendEmail = () => {
    form.post(route('email.admin.send'), {
        onSuccess: () => {
            // Email sent successfully
        },
    });
};

const saveDraft = () => {
    isDraft.value = true;
    // TODO: Implement save draft functionality
    console.log('Saving draft...');
};

const addAttachment = () => {
    fileInput.value?.click();
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const newFiles = Array.from(target.files);
        form.attachments.push(...newFiles);
    }
};

const removeAttachment = (index: number) => {
    form.attachments.splice(index, 1);
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getFileIcon = (file: File) => {
    if (file.type.startsWith('image/')) return Image;
    if (file.type.includes('text') || file.type.includes('document')) return FileText;
    return File;
};

const quickTemplates = [
    {
        name: 'Saludo formal',
        content:
            'Estimado/a [Nombre],\n\nEspero que este correo le encuentre bien.\n\n[Contenido del mensaje]\n\nQuedo atento/a a su respuesta.\n\nCordialmente,\n[Su nombre]',
    },
    {
        name: 'Solicitud de información',
        content:
            'Estimado/a [Nombre],\n\nMe dirijo a usted para solicitar información sobre [tema].\n\n[Detalles específicos]\n\nAgradezco de antemano su colaboración.\n\nSaludos cordiales,\n[Su nombre]',
    },
    {
        name: 'Confirmación de reunión',
        content:
            'Estimado/a [Nombre],\n\nPor medio de la presente confirmo nuestra reunión programada para el [fecha] a las [hora] en [lugar].\n\nTemas a tratar:\n- [Tema 1]\n- [Tema 2]\n\nSaludos cordiales,\n[Su nombre]',
    },
];

const useTemplate = (template: string) => {
    form.body = template;
};
</script>

<template>
    <Head title="Redactar Correo - Administración" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button @click="$inertia.visit(route('email.admin.index'))" variant="outline" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Redactar Correo</h1>
                        <p class="text-muted-foreground">Correo Electrónico - Administración</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button @click="saveDraft" variant="outline" size="sm">
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Borrador
                    </Button>
                    <Button @click="sendEmail" :disabled="form.processing" size="sm">
                        <Send class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Enviando...' : 'Enviar' }}
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <!-- Templates Sidebar -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Plantillas Rápidas</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button
                            v-for="template in quickTemplates"
                            :key="template.name"
                            @click="useTemplate(template.content)"
                            variant="ghost"
                            class="h-auto w-full justify-start p-2 text-left"
                        >
                            <div>
                                <div class="text-sm font-medium">{{ template.name }}</div>
                                <div class="truncate text-xs text-muted-foreground">{{ template.content.substring(0, 50) }}...</div>
                            </div>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Compose Form -->
                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Nuevo Correo Electrónico</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="sendEmail" class="space-y-4">
                            <!-- Recipients -->
                            <div class="space-y-4">
                                <!-- To -->
                                <div class="space-y-2">
                                    <Label for="to">Para</Label>
                                    <Input
                                        id="to"
                                        v-model="form.to"
                                        type="email"
                                        placeholder="destinatario@ejemplo.com"
                                        required
                                        :class="{ 'border-red-500': form.errors.to }"
                                    />
                                    <p v-if="form.errors.to" class="text-sm text-red-500">{{ form.errors.to }}</p>
                                </div>

                                <!-- CC/BCC Toggle -->
                                <div class="flex gap-2">
                                    <Button @click="showCc = !showCc" type="button" variant="ghost" size="sm"> CC </Button>
                                    <Button @click="showBcc = !showBcc" type="button" variant="ghost" size="sm"> CCO </Button>
                                </div>

                                <!-- CC -->
                                <div v-if="showCc" class="space-y-2">
                                    <Label for="cc">CC (Con Copia)</Label>
                                    <Input id="cc" v-model="form.cc" type="email" placeholder="copia@ejemplo.com" />
                                </div>

                                <!-- BCC -->
                                <div v-if="showBcc" class="space-y-2">
                                    <Label for="bcc">CCO (Con Copia Oculta)</Label>
                                    <Input id="bcc" v-model="form.bcc" type="email" placeholder="copia-oculta@ejemplo.com" />
                                </div>
                            </div>

                            <Separator />

                            <!-- Subject -->
                            <div class="space-y-2">
                                <Label for="subject">Asunto</Label>
                                <Input
                                    id="subject"
                                    v-model="form.subject"
                                    placeholder="Asunto del correo"
                                    required
                                    :class="{ 'border-red-500': form.errors.subject }"
                                />
                                <p v-if="form.errors.subject" class="text-sm text-red-500">{{ form.errors.subject }}</p>
                            </div>

                            <!-- Attachments -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Label>Archivos Adjuntos</Label>
                                    <Button @click="addAttachment" type="button" variant="outline" size="sm">
                                        <Paperclip class="mr-2 h-4 w-4" />
                                        Adjuntar
                                    </Button>
                                </div>

                                <input ref="fileInput" type="file" multiple @change="handleFileSelect" class="hidden" />

                                <div v-if="form.attachments.length > 0" class="space-y-2">
                                    <div v-for="(file, index) in form.attachments" :key="index" class="flex items-center gap-2 rounded-lg border p-2">
                                        <component :is="getFileIcon(file)" class="h-4 w-4 text-muted-foreground" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium">{{ file.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
                                        </div>
                                        <Button @click="removeAttachment(index)" type="button" variant="ghost" size="sm">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Message Body -->
                            <div class="space-y-2">
                                <Label for="body">Mensaje</Label>
                                <Textarea
                                    id="body"
                                    v-model="form.body"
                                    placeholder="Escriba su mensaje aquí..."
                                    rows="12"
                                    required
                                    :class="{ 'border-red-500': form.errors.body }"
                                />
                                <p v-if="form.errors.body" class="text-sm text-red-500">{{ form.errors.body }}</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between pt-4">
                                <div class="flex items-center gap-2">
                                    <Badge v-if="isDraft" variant="secondary"> Borrador guardado </Badge>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button @click="$inertia.visit(route('email.admin.index'))" type="button" variant="outline"> Cancelar </Button>
                                    <Button @click="saveDraft" type="button" variant="outline">
                                        <Save class="mr-2 h-4 w-4" />
                                        Guardar Borrador
                                    </Button>
                                    <Button type="submit" :disabled="form.processing">
                                        <Send class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Enviando...' : 'Enviar' }}
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
