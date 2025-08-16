<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import EmailTemplatePreview from '@/components/EmailTemplates/EmailTemplatePreview.vue';
import { ArrowLeft, Calendar, Edit, Star, User } from 'lucide-vue-next';
import type { AppPageProps, EmailTemplate, Invoice } from '@/types';

interface Props extends /* @vue-ignore */ AppPageProps {
    template: EmailTemplate;
    sampleInvoice?: Invoice;
    types: Record<string, string>;
}

const props = defineProps<Props>();

// Computed
const typeColor = computed(() => {
    const colors = {
        invoice: 'bg-blue-100 text-blue-800',
        payment_receipt: 'bg-green-100 text-green-800',
        payment_reminder: 'bg-yellow-100 text-yellow-800',
        welcome: 'bg-purple-100 text-purple-800',
        announcement: 'bg-indigo-100 text-indigo-800',
        custom: 'bg-gray-100 text-gray-800',
    };
    return colors[props.template.type as keyof typeof colors] || colors.custom;
});

const statusColor = computed(() => {
    return props.template.is_active 
        ? 'bg-green-100 text-green-800 border-green-200' 
        : 'bg-gray-100 text-gray-500 border-gray-200';
});
</script>

<template>
    <AppLayout>
        <Head :title="`Plantilla: ${template.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('email-templates.index')">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight flex items-center space-x-3">
                            <span>{{ template.name }}</span>
                            <Star v-if="template.is_default" class="h-6 w-6 text-yellow-500 fill-current" />
                        </h1>
                        <p class="text-muted-foreground">
                            {{ template.description || 'Sin descripción' }}
                        </p>
                    </div>
                </div>
                <Link :href="route('email-templates.edit', template.id)">
                    <Button>
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Template Details -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Basic Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de la Plantilla</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Badge :class="typeColor">
                                    {{ types[template.type] }}
                                </Badge>
                                <Badge variant="outline" :class="statusColor">
                                    {{ template.is_active ? 'Activa' : 'Inactiva' }}
                                </Badge>
                                <Badge v-if="template.is_default" variant="outline" class="text-yellow-600 border-yellow-200">
                                    Predeterminada
                                </Badge>
                            </div>

                            <Separator />

                            <div class="space-y-3 text-sm">
                                <div>
                                    <div class="font-medium text-muted-foreground">Asunto:</div>
                                    <div class="text-foreground">{{ template.subject }}</div>
                                </div>
                                
                                <div>
                                    <div class="font-medium text-muted-foreground">Variables disponibles:</div>
                                    <div class="text-foreground">{{ template.variables.length }} variables</div>
                                </div>

                                <div v-if="template.created_by_user">
                                    <div class="font-medium text-muted-foreground flex items-center space-x-1">
                                        <User class="h-4 w-4" />
                                        <span>Creado por:</span>
                                    </div>
                                    <div class="text-foreground">{{ template.created_by_user.name }}</div>
                                </div>

                                <div>
                                    <div class="font-medium text-muted-foreground flex items-center space-x-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>Fecha de creación:</span>
                                    </div>
                                    <div class="text-foreground">
                                        {{ new Date(template.created_at).toLocaleDateString('es-ES', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </div>
                                </div>

                                <div v-if="template.updated_at !== template.created_at">
                                    <div class="font-medium text-muted-foreground">Última modificación:</div>
                                    <div class="text-foreground">
                                        {{ new Date(template.updated_at).toLocaleDateString('es-ES', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </div>
                                    <div v-if="template.updated_by_user" class="text-xs text-muted-foreground">
                                        por {{ template.updated_by_user.name }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Variables -->
                    <Card v-if="template.variables.length > 0">
                        <CardHeader>
                            <CardTitle>Variables Disponibles</CardTitle>
                            <CardDescription>
                                Estas variables se reemplazan automáticamente en el email
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <code 
                                    v-for="variable in template.variables" 
                                    :key="variable"
                                    class="px-2 py-1 bg-gray-100 rounded text-xs font-mono"
                                >
                                    {{ variable }}
                                </code>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Design Configuration -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración de Diseño</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div class="font-medium text-muted-foreground">Color Primario:</div>
                                    <div class="flex items-center space-x-2">
                                        <div 
                                            class="w-4 h-4 rounded border"
                                            :style="{ backgroundColor: template.design_config.primary_color }"
                                        ></div>
                                        <code class="text-xs">{{ template.design_config.primary_color }}</code>
                                    </div>
                                </div>
                                <div>
                                    <div class="font-medium text-muted-foreground">Color Secundario:</div>
                                    <div class="flex items-center space-x-2">
                                        <div 
                                            class="w-4 h-4 rounded border"
                                            :style="{ backgroundColor: template.design_config.secondary_color }"
                                        ></div>
                                        <code class="text-xs">{{ template.design_config.secondary_color }}</code>
                                    </div>
                                </div>
                                <div>
                                    <div class="font-medium text-muted-foreground">Fuente:</div>
                                    <div class="text-foreground text-xs">{{ template.design_config.font_family }}</div>
                                </div>
                                <div>
                                    <div class="font-medium text-muted-foreground">Ancho Máximo:</div>
                                    <div class="text-foreground text-xs">{{ template.design_config.max_width }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Template Preview -->
                <div class="lg:col-span-2">
                    <EmailTemplatePreview
                        :template="template"
                        :sample-invoice="sampleInvoice"
                        :show-preview="true"
                    />
                </div>
            </div>

            <!-- Raw Template Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Contenido del Template</CardTitle>
                    <CardDescription>
                        Este es el contenido sin procesar de la plantilla
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div>
                            <Label class="text-sm font-medium">Asunto:</Label>
                            <code class="block mt-1 p-3 bg-gray-50 rounded text-sm">{{ template.subject }}</code>
                        </div>
                        <div>
                            <Label class="text-sm font-medium">Contenido:</Label>
                            <pre class="mt-1 p-3 bg-gray-50 rounded text-sm whitespace-pre-wrap overflow-auto max-h-96">{{ template.body }}</pre>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>