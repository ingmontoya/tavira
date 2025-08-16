<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Eye, EyeOff, Mail } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { EmailTemplate, Invoice } from '@/types';

interface Props {
    template: Partial<EmailTemplate>;
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
    if (!props.sampleInvoice) return props.template.subject || '';
    
    let subject = props.template.subject || '';
    
    // Replace common variables with sample data
    const replacements = {
        '{{invoice_number}}': props.sampleInvoice.invoice_number || 'INV-001',
        '{{apartment_number}}': props.sampleInvoice.apartment?.number || '101',
        '{{apartment_address}}': props.sampleInvoice.apartment?.full_address || 'Apto 101 - Torre A',
        '{{billing_period}}': props.sampleInvoice.billing_period_label || 'Enero 2024',
        '{{due_date}}': new Date(props.sampleInvoice.due_date).toLocaleDateString('es-ES') || '31/01/2024',
        '{{balance_due}}': `$${parseFloat(props.sampleInvoice.balance_due.toString()).toLocaleString()}` || '$150.000',
        '{{total_amount}}': `$${parseFloat(props.sampleInvoice.total_amount.toString()).toLocaleString()}` || '$150.000',
        '{{conjunto_name}}': props.sampleInvoice.apartment?.conjuntoConfig?.name || 'Conjunto Residencial',
        '{{billing_date}}': new Date(props.sampleInvoice.billing_date).toLocaleDateString('es-ES') || '01/01/2024',
    };

    // Default sample data if no invoice provided
    const defaultReplacements = {
        '{{invoice_number}}': 'INV-2024-001',
        '{{apartment_number}}': '301',
        '{{apartment_address}}': 'Apto 301 - Torre B',
        '{{billing_period}}': 'Enero 2024',
        '{{due_date}}': '31/01/2024',
        '{{balance_due}}': '$180.000',
        '{{total_amount}}': '$180.000',
        '{{conjunto_name}}': 'Conjunto Los Álamos',
        '{{billing_date}}': '01/01/2024',
        '{{payment_amount}}': '$180.000',
        '{{payment_date}}': '15/01/2024',
        '{{payment_method}}': 'Transferencia Bancaria',
        '{{receipt_number}}': 'REC-2024-001',
        '{{overdue_amount}}': '$180.000',
        '{{days_overdue}}': '15',
    };

    const finalReplacements = props.sampleInvoice ? replacements : defaultReplacements;
    
    Object.entries(finalReplacements).forEach(([key, value]) => {
        subject = subject.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), value);
    });
    
    return subject;
});

const processedBody = computed(() => {
    if (!props.template.body) return '';
    
    let body = props.template.body;
    
    // Sample data replacements
    const defaultReplacements = {
        '{{invoice_number}}': 'INV-2024-001',
        '{{apartment_number}}': '301',
        '{{apartment_address}}': 'Apto 301 - Torre B',
        '{{billing_period}}': 'Enero 2024',
        '{{due_date}}': '31/01/2024',
        '{{balance_due}}': '$180.000',
        '{{total_amount}}': '$180.000',
        '{{conjunto_name}}': 'Conjunto Los Álamos',
        '{{billing_date}}': '01/01/2024',
        '{{payment_amount}}': '$180.000',
        '{{payment_date}}': '15/01/2024',
        '{{payment_method}}': 'Transferencia Bancaria',
        '{{receipt_number}}': 'REC-2024-001',
        '{{overdue_amount}}': '$180.000',
        '{{days_overdue}}': '15',
        '{{user_name}}': 'Juan Pérez',
        '{{login_url}}': 'https://habitta.com/login',
        '{{announcement_title}}': 'Mantenimiento Programado',
        '{{announcement_content}}': 'Informamos que se realizará mantenimiento en las áreas comunes...',
        '{{announcement_date}}': '15/01/2024',
        '{{author_name}}': 'Administración',
    };

    if (props.sampleInvoice) {
        const sampleReplacements = {
            '{{invoice_number}}': props.sampleInvoice.invoice_number || 'INV-001',
            '{{apartment_number}}': props.sampleInvoice.apartment?.number || '101',
            '{{apartment_address}}': props.sampleInvoice.apartment?.full_address || 'Apto 101 - Torre A',
            '{{billing_period}}': props.sampleInvoice.billing_period_label || 'Enero 2024',
            '{{due_date}}': new Date(props.sampleInvoice.due_date).toLocaleDateString('es-ES') || '31/01/2024',
            '{{balance_due}}': `$${parseFloat(props.sampleInvoice.balance_due.toString()).toLocaleString()}` || '$150.000',
            '{{total_amount}}': `$${parseFloat(props.sampleInvoice.total_amount.toString()).toLocaleString()}` || '$150.000',
            '{{conjunto_name}}': props.sampleInvoice.apartment?.conjuntoConfig?.name || 'Conjunto Residencial',
            '{{billing_date}}': new Date(props.sampleInvoice.billing_date).toLocaleDateString('es-ES') || '01/01/2024',
        };
        Object.assign(defaultReplacements, sampleReplacements);
    }
    
    Object.entries(defaultReplacements).forEach(([key, value]) => {
        body = body.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), value);
    });
    
    // Convert line breaks to HTML if not already HTML
    if (!body.includes('<p>') && !body.includes('<div>')) {
        body = body.replace(/\n/g, '<br>');
    }
    
    return body;
});

const designConfig = computed(() => {
    return props.template.design_config || {
        primary_color: '#3b82f6',
        secondary_color: '#1d4ed8',
        background_color: '#f8fafc',
        text_color: '#1e293b',
        font_family: 'system-ui, -apple-system, sans-serif',
        max_width: '600px',
    };
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center space-x-2">
                        <Mail class="h-5 w-5" />
                        <span>{{ template.name || 'Vista Previa de Plantilla' }}</span>
                    </CardTitle>
                    <CardDescription v-if="sampleInvoice">
                        Vista previa con datos de ejemplo de la factura {{ sampleInvoice.invoice_number }}
                    </CardDescription>
                    <CardDescription v-else>
                        Vista previa con datos de muestra
                    </CardDescription>
                </div>
                
                <Button 
                    @click="togglePreview"
                    variant="outline"
                    size="sm"
                >
                    <component 
                        :is="isPreviewVisible ? EyeOff : Eye" 
                        class="mr-2 h-4 w-4" 
                    />
                    {{ isPreviewVisible ? 'Ocultar' : 'Ver' }} Vista Previa
                </Button>
            </div>
        </CardHeader>
        
        <CardContent v-if="isPreviewVisible">
            <!-- Email Preview -->
            <div 
                class="border rounded-lg overflow-hidden bg-white"
                :style="{
                    fontFamily: designConfig.font_family,
                    color: designConfig.text_color,
                    maxWidth: designConfig.max_width,
                    margin: '0 auto'
                }"
            >
                <!-- Email Header (simulated) -->
                <div class="border-b p-4 bg-gray-50">
                    <div class="text-sm text-muted-foreground mb-2">Para: propietario@ejemplo.com</div>
                    <div class="text-sm text-muted-foreground mb-2">De: administracion@conjunto.com</div>
                    <div class="text-sm text-muted-foreground mb-1">Asunto:</div>
                    <div class="font-medium">{{ processedSubject || 'Sin asunto definido' }}</div>
                </div>
                
                <!-- Email Body -->
                <div class="p-6">
                    <div v-if="processedBody" v-html="processedBody" class="prose prose-sm max-w-none"></div>
                    <div v-else class="text-muted-foreground italic">
                        Sin contenido definido
                    </div>
                </div>
                
                <!-- Email Footer (simulated) -->
                <Separator />
                <div class="p-4 bg-gray-50 text-center">
                    <div class="text-xs text-muted-foreground">
                        <div>Este es un email automático generado por el sistema de administración.</div>
                        <div class="mt-1">Por favor no responda a este mensaje.</div>
                    </div>
                </div>
            </div>
            
            <!-- Template Variables -->
            <div v-if="template.variables && template.variables.length > 0" class="mt-6">
                <div class="text-sm font-medium mb-3">Variables disponibles:</div>
                <div class="flex flex-wrap gap-2">
                    <code 
                        v-for="variable in template.variables" 
                        :key="variable"
                        class="px-2 py-1 bg-gray-100 rounded text-xs"
                    >
                        {{ variable }}
                    </code>
                </div>
            </div>
            
            <!-- Sample Data Notice -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-sm text-blue-800">
                    <strong>Nota:</strong> Esta vista previa utiliza datos de muestra. 
                    Los valores reales se completarán automáticamente al enviar los emails.
                </div>
            </div>
        </CardContent>
        
        <!-- Template Info (when collapsed) -->
        <CardContent v-else>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium">Asunto:</span>
                    <div class="text-muted-foreground truncate">{{ template.subject || 'Sin asunto' }}</div>
                </div>
                <div>
                    <span class="font-medium">Variables:</span>
                    <div class="text-muted-foreground">{{ template.variables?.length || 0 }} disponibles</div>
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

.prose a {
    color: #3b82f6;
    text-decoration: underline;
}
</style>