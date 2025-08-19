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
import { ArrowLeft, Building2, File, FileText, Image, Paperclip, Save, Send, Users, X } from 'lucide-vue-next';
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
    form.post(route('email.concejo.send'), {
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
        name: 'Comunicado Oficial',
        content:
            'Estimada Administración,\n\nPor medio de la presente, el Concejo de Administración informa:\n\n[Contenido del comunicado]\n\nEste comunicado entra en vigencia a partir de [fecha].\n\nCordialmente,\nConcejo de Administración',
    },
    {
        name: 'Solicitud de Información',
        content:
            'Estimada Administración,\n\nEl Concejo de Administración solicita la siguiente información:\n\n• [Punto 1]\n• [Punto 2]\n• [Punto 3]\n\nSolicitamos respuesta en un plazo máximo de [X] días hábiles.\n\nCordialmente,\nConcejo de Administración',
    },
    {
        name: 'Aprobación de Propuesta',
        content:
            'Estimada Administración,\n\nDespués de revisar la propuesta presentada, el Concejo de Administración informa:\n\n✅ APROBADA la propuesta de [descripción]\n\nObservaciones:\n[Observaciones si las hay]\n\nCordialmente,\nConcejo de Administración',
    },
    {
        name: 'Convocatoria a Reunión',
        content:
            'Estimada Administración,\n\nEl Concejo de Administración convoca a reunión con los siguientes detalles:\n\n📅 Fecha: [fecha]\n🕐 Hora: [hora]\n📍 Lugar: [lugar]\n\nTemas a tratar:\n1. [Tema 1]\n2. [Tema 2]\n\nCordialmente,\nConcejo de Administración',
    },
];

const useTemplate = (template: string) => {
    form.body = template;
};

const quickRecipients = [
    { name: 'Administración', email: 'admin@Tavira.com' },
    { name: 'Gerencia', email: 'gerencia@Tavira.com' },
    { name: 'Contabilidad', email: 'contabilidad@Tavira.com' },
];

const addRecipient = (email: string) => {
    if (form.to) {
        form.to += `, ${email}`;
    } else {
        form.to = email;
    }
};
</script>

<template>
    <Head title="Redactar Correo - Concejo" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button @click="$inertia.visit(route('email.concejo.index'))" variant="outline" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Redactar Correo</h1>
                        <p class="flex items-center gap-2 text-muted-foreground">
                            <Users class="h-4 w-4" />
                            Correo Electrónico - Concejo
                        </p>
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
                <!-- Templates & Recipients Sidebar -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Plantillas del Concejo</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Quick Templates -->
                        <div class="space-y-2">
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
                        </div>

                        <Separator />

                        <!-- Quick Recipients -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium">Destinatarios Frecuentes</h4>
                            <div class="space-y-1">
                                <Button
                                    v-for="recipient in quickRecipients"
                                    :key="recipient.email"
                                    @click="addRecipient(recipient.email)"
                                    variant="ghost"
                                    size="sm"
                                    class="w-full justify-start"
                                >
                                    <Building2 class="mr-2 h-3 w-3" />
                                    <div class="text-left">
                                        <div class="text-xs font-medium">{{ recipient.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ recipient.email }}</div>
                                    </div>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Compose Form -->
                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Nuevo Correo del Concejo
                        </CardTitle>
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
                                    <Badge variant="outline" class="flex items-center gap-1">
                                        <Users class="h-3 w-3" />
                                        Enviado desde: Concejo
                                    </Badge>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button @click="$inertia.visit(route('email.concejo.index'))" type="button" variant="outline"> Cancelar </Button>
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
