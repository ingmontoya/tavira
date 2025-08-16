<script setup lang="ts">
import EmailTemplatePreview from '@/components/EmailTemplates/EmailTemplatePreview.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, EmailTemplate, EmailTemplateDesignConfig } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, Palette, Save, Type } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props extends /* @vue-ignore */ AppPageProps {
    template: EmailTemplate;
    types: Record<string, string>;
    defaultVariables: Record<string, string[]>;
    defaultDesignConfig: EmailTemplateDesignConfig;
}

const props = defineProps<Props>();

// Toast composable
const { success, error } = useToast();

// Form
const form = useForm({
    name: props.template.name,
    description: props.template.description || '',
    type: props.template.type,
    subject: props.template.subject,
    body: props.template.body,
    variables: props.template.variables || [],
    design_config: props.template.design_config || props.defaultDesignConfig,
    is_active: props.template.is_active,
    is_default: props.template.is_default,
});

// Local state
const previewMode = ref(false);
const activeTab = ref('content');

// Computed
const availableVariables = computed(() => {
    return form.type ? props.defaultVariables[form.type] || [] : [];
});

const typeOptions = computed(() => Object.entries(props.types).map(([value, label]) => ({ value, label })));

// Watchers
watch(
    () => form.type,
    (newType) => {
        if (newType) {
            form.variables = props.defaultVariables[newType] || [];
        }
    },
);

// Methods
const insertVariable = (variable: string) => {
    const textarea = document.getElementById('body-textarea') as HTMLTextAreaElement;
    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = form.body;
        form.body = text.substring(0, start) + variable + text.substring(end);

        // Set cursor position after inserted variable
        setTimeout(() => {
            textarea.focus();
            textarea.setSelectionRange(start + variable.length, start + variable.length);
        }, 10);
    } else {
        form.body += variable;
    }
};

const insertVariableInSubject = (variable: string) => {
    const input = document.getElementById('subject-input') as HTMLInputElement;
    if (input) {
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        const text = form.subject;
        form.subject = text.substring(0, start) + variable + text.substring(end);

        // Set cursor position after inserted variable
        setTimeout(() => {
            input.focus();
            input.setSelectionRange(start + variable.length, start + variable.length);
        }, 10);
    } else {
        form.subject += variable;
    }
};

const submit = () => {
    form.put(route('email-templates.update', props.template.id), {
        onSuccess: () => {
            success('Plantilla actualizada exitosamente.', 'Éxito');
        },
        onError: () => {
            error('Por favor revisa los errores en el formulario.', 'Error');
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Editar: ${template.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('email-templates.show', template.id)">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Editar Plantilla</h1>
                        <p class="text-muted-foreground">Modifica la plantilla "{{ template.name }}"</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <Button variant="outline" @click="previewMode = !previewMode">
                        <Eye class="mr-2 h-4 w-4" />
                        {{ previewMode ? 'Ocultar' : 'Vista Previa' }}
                    </Button>
                    <Button @click="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Form -->
                <div class="space-y-6">
                    <Tabs v-model="activeTab" class="w-full">
                        <TabsList class="grid w-full grid-cols-3">
                            <TabsTrigger value="content">
                                <Type class="mr-2 h-4 w-4" />
                                Contenido
                            </TabsTrigger>
                            <TabsTrigger value="design">
                                <Palette class="mr-2 h-4 w-4" />
                                Diseño
                            </TabsTrigger>
                            <TabsTrigger value="settings"> Configuración </TabsTrigger>
                        </TabsList>

                        <!-- Content Tab -->
                        <TabsContent value="content" class="space-y-6">
                            <!-- Basic Info -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información Básica</CardTitle>
                                    <CardDescription> Configura la información básica de la plantilla </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="name">Nombre de la Plantilla</Label>
                                            <Input
                                                id="name"
                                                v-model="form.name"
                                                placeholder="Ej: Factura Moderna"
                                                :class="{ 'border-destructive': form.errors.name }"
                                            />
                                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                                {{ form.errors.name }}
                                            </p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="type">Tipo de Plantilla</Label>
                                            <Select v-model="form.type">
                                                <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                                    <SelectValue placeholder="Selecciona un tipo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.type" class="text-sm text-destructive">
                                                {{ form.errors.type }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="description">Descripción</Label>
                                        <Textarea
                                            id="description"
                                            v-model="form.description"
                                            placeholder="Describe brevemente esta plantilla..."
                                            rows="2"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Subject -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Asunto del Email</CardTitle>
                                    <CardDescription> Define el asunto que aparecerá en el email </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Input
                                            id="subject-input"
                                            v-model="form.subject"
                                            placeholder="Ej: Factura {{invoice_number}} - {{apartment_address}}"
                                            :class="{ 'border-destructive': form.errors.subject }"
                                        />
                                        <p v-if="form.errors.subject" class="text-sm text-destructive">
                                            {{ form.errors.subject }}
                                        </p>
                                    </div>

                                    <!-- Variables for Subject -->
                                    <div v-if="availableVariables.length > 0" class="space-y-2">
                                        <Label class="text-sm font-medium">Variables Disponibles para Asunto:</Label>
                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                v-for="variable in availableVariables"
                                                :key="variable"
                                                variant="outline"
                                                size="sm"
                                                @click="insertVariableInSubject(variable)"
                                                class="text-xs"
                                            >
                                                {{ variable }}
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Body -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Contenido del Email</CardTitle>
                                    <CardDescription> Escribe el contenido principal del email. Puedes usar HTML. </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2">
                                        <Textarea
                                            id="body-textarea"
                                            v-model="form.body"
                                            placeholder="Escribe el contenido del email aquí..."
                                            rows="12"
                                            :class="{ 'border-destructive': form.errors.body }"
                                        />
                                        <p v-if="form.errors.body" class="text-sm text-destructive">
                                            {{ form.errors.body }}
                                        </p>
                                    </div>

                                    <!-- Variables for Body -->
                                    <div v-if="availableVariables.length > 0" class="space-y-2">
                                        <Label class="text-sm font-medium">Variables Disponibles para Contenido:</Label>
                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                v-for="variable in availableVariables"
                                                :key="variable"
                                                variant="outline"
                                                size="sm"
                                                @click="insertVariable(variable)"
                                                class="text-xs"
                                            >
                                                {{ variable }}
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Design Tab -->
                        <TabsContent value="design" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Configuración de Diseño</CardTitle>
                                    <CardDescription> Personaliza los colores y estilos de la plantilla </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-6">
                                    <!-- Colors -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium">Colores</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <Label for="primary-color">Color Primario</Label>
                                                <div class="flex space-x-2">
                                                    <Input
                                                        id="primary-color"
                                                        v-model="form.design_config.primary_color"
                                                        type="color"
                                                        class="h-10 w-16 p-1"
                                                    />
                                                    <Input v-model="form.design_config.primary_color" placeholder="#3b82f6" class="flex-1" />
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="secondary-color">Color Secundario</Label>
                                                <div class="flex space-x-2">
                                                    <Input
                                                        id="secondary-color"
                                                        v-model="form.design_config.secondary_color"
                                                        type="color"
                                                        class="h-10 w-16 p-1"
                                                    />
                                                    <Input v-model="form.design_config.secondary_color" placeholder="#1d4ed8" class="flex-1" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    <!-- Typography -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium">Tipografía</h4>
                                        <div class="space-y-2">
                                            <Label for="font-family">Fuente</Label>
                                            <Select v-model="form.design_config.font_family">
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="system-ui, -apple-system, sans-serif">System UI</SelectItem>
                                                    <SelectItem value="Arial, sans-serif">Arial</SelectItem>
                                                    <SelectItem value="Georgia, serif">Georgia</SelectItem>
                                                    <SelectItem value="'Times New Roman', serif">Times New Roman</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <Separator />

                                    <!-- Layout -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium">Layout</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <Label>Estilo de Header</Label>
                                                <Select v-model="form.design_config.header_style">
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="modern">Moderno</SelectItem>
                                                        <SelectItem value="classic">Clásico</SelectItem>
                                                        <SelectItem value="gradient">Gradiente</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div class="space-y-2">
                                                <Label>Ancho Máximo</Label>
                                                <Input v-model="form.design_config.max_width" placeholder="600px" />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Settings Tab -->
                        <TabsContent value="settings" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Configuración</CardTitle>
                                    <CardDescription> Configura el estado y comportamiento de la plantilla </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-6">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label>Plantilla Activa</Label>
                                            <p class="text-sm text-muted-foreground">
                                                Las plantillas inactivas no aparecerán en las opciones de envío
                                            </p>
                                        </div>
                                        <Switch v-model:checked="form.is_active" />
                                    </div>

                                    <Separator />

                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label>Plantilla Predeterminada</Label>
                                            <p class="text-sm text-muted-foreground">Esta plantilla se usará por defecto para este tipo de email</p>
                                        </div>
                                        <Switch v-model:checked="form.is_default" />
                                    </div>

                                    <div v-if="form.is_default" class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                                        <div class="text-sm text-yellow-800">
                                            <strong>Nota:</strong> Al marcar esta plantilla como predeterminada, las demás plantillas del mismo tipo
                                            perderán este estado automáticamente.
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Preview -->
                <div v-if="previewMode" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Vista Previa</CardTitle>
                            <CardDescription> Así se verá tu plantilla de email actualizada </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <EmailTemplatePreview
                                :template="{
                                    name: form.name || 'Plantilla sin nombre',
                                    subject: form.subject,
                                    body: form.body,
                                    variables: form.variables,
                                    design_config: form.design_config,
                                }"
                                :show-preview="true"
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
