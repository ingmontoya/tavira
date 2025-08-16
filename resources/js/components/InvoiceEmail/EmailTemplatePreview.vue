<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { EmailTemplate, Invoice } from '@/types';
import { Eye, EyeOff, Mail } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    template: EmailTemplate;
    sampleInvoice?: Invoice;
    showPreview?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showPreview: false,
});

// Local state
const isPreviewVisible = ref(props.showPreview);

// Toggle preview visibility
const togglePreview = () => {
    isPreviewVisible.value = !isPreviewVisible.value;
};

// Process template with sample data
const processedSubject = computed(() => {
    if (!props.sampleInvoice) return props.template.subject;

    let subject = props.template.subject;

    // Replace common variables
    subject = subject.replace(/\{\{invoice_number\}\}/g, props.sampleInvoice.invoice_number);
    subject = subject.replace(/\{\{apartment_number\}\}/g, props.sampleInvoice.apartment?.number || 'N/A');
    subject = subject.replace(/\{\{apartment_address\}\}/g, props.sampleInvoice.apartment?.full_address || 'Dirección no disponible');
    subject = subject.replace(/\{\{billing_period\}\}/g, props.sampleInvoice.billing_period_label);
    subject = subject.replace(/\{\{due_date\}\}/g, new Date(props.sampleInvoice.due_date).toLocaleDateString('es-ES'));
    subject = subject.replace(/\{\{balance_due\}\}/g, `$${parseFloat(props.sampleInvoice.balance_due.toString()).toLocaleString()}`);

    return subject;
});

const processedBody = computed(() => {
    if (!props.sampleInvoice) return props.template.body;

    let body = props.template.body;

    // Replace common variables
    body = body.replace(/\{\{invoice_number\}\}/g, props.sampleInvoice.invoice_number);
    body = body.replace(/\{\{apartment_number\}\}/g, props.sampleInvoice.apartment?.number || 'N/A');
    body = body.replace(/\{\{apartment_address\}\}/g, props.sampleInvoice.apartment?.full_address || 'Dirección no disponible');
    body = body.replace(/\{\{billing_period\}\}/g, props.sampleInvoice.billing_period_label);
    body = body.replace(/\{\{due_date\}\}/g, new Date(props.sampleInvoice.due_date).toLocaleDateString('es-ES'));
    body = body.replace(/\{\{balance_due\}\}/g, `$${parseFloat(props.sampleInvoice.balance_due.toString()).toLocaleString()}`);
    body = body.replace(/\{\{total_amount\}\}/g, `$${parseFloat(props.sampleInvoice.total_amount.toString()).toLocaleString()}`);

    // Convert line breaks to HTML
    body = body.replace(/\n/g, '<br>');

    return body;
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center space-x-2">
                        <Mail class="h-5 w-5" />
                        <span>Template: {{ template.name }}</span>
                    </CardTitle>
                    <CardDescription v-if="sampleInvoice">
                        Vista previa con datos de ejemplo de la factura {{ sampleInvoice.invoice_number }}
                    </CardDescription>
                    <CardDescription v-else> Vista previa del template de email </CardDescription>
                </div>

                <Button @click="togglePreview" variant="outline" size="sm">
                    <component :is="isPreviewVisible ? EyeOff : Eye" class="mr-2 h-4 w-4" />
                    {{ isPreviewVisible ? 'Ocultar' : 'Ver' }} Vista Previa
                </Button>
            </div>
        </CardHeader>

        <CardContent v-if="isPreviewVisible">
            <!-- Email Preview -->
            <div class="rounded-lg border bg-white p-4">
                <!-- Email Header -->
                <div class="mb-4 border-b pb-3">
                    <div class="mb-1 text-sm text-muted-foreground">Asunto:</div>
                    <div class="font-medium">{{ processedSubject }}</div>
                </div>

                <!-- Email Body -->
                <div class="prose prose-sm max-w-none">
                    <div v-html="processedBody"></div>
                </div>

                <!-- Email Footer (simulated) -->
                <Separator class="my-4" />
                <div class="text-xs text-muted-foreground">
                    <div>Este es un email automático generado por el sistema de administración.</div>
                    <div class="mt-1">Por favor no responda a este mensaje.</div>
                </div>
            </div>

            <!-- Template Variables -->
            <div v-if="template.variables.length > 0" class="mt-4">
                <div class="mb-2 text-sm font-medium">Variables disponibles:</div>
                <div class="flex flex-wrap gap-2">
                    <code v-for="variable in template.variables" :key="variable" class="rounded bg-gray-100 px-2 py-1 text-xs">
                        {{ variable }}
                    </code>
                </div>
            </div>

            <!-- Sample Data Notice -->
            <div v-if="sampleInvoice" class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
                <div class="text-sm text-blue-800">
                    <strong>Nota:</strong> Esta vista previa utiliza datos de ejemplo. Los valores reales se completarán automáticamente al enviar las
                    facturas.
                </div>
            </div>
        </CardContent>

        <!-- Template Info (when collapsed) -->
        <CardContent v-else>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium">Asunto:</span>
                    <div class="truncate text-muted-foreground">{{ template.subject }}</div>
                </div>
                <div>
                    <span class="font-medium">Variables:</span>
                    <div class="text-muted-foreground">{{ template.variables.length }} disponibles</div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
.prose {
    color: inherit;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
    color: inherit;
    font-weight: 600;
}

.prose p {
    margin-bottom: 1em;
}

.prose strong {
    font-weight: 600;
}
</style>
