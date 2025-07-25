<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AlertCircle, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    errors: Record<string, string | string[]>;
    show?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    show: true,
});

const emit = defineEmits<{
    dismiss: [];
}>();

const errorList = computed(() => {
    const errors: { field: string; message: string }[] = [];

    Object.entries(props.errors).forEach(([field, messages]) => {
        if (Array.isArray(messages)) {
            messages.forEach((message) => {
                errors.push({ field, message });
            });
        } else if (messages) {
            errors.push({ field, message: messages });
        }
    });

    return errors;
});

const hasErrors = computed(() => {
    return errorList.value.length > 0;
});

const alertRef = ref<HTMLElement>();

const getFieldLabel = (field: string): string => {
    const fieldLabels: Record<string, string> = {
        document_type: 'Tipo de Documento',
        document_number: 'Número de Documento',
        first_name: 'Nombres',
        last_name: 'Apellidos',
        email: 'Correo Electrónico',
        phone: 'Teléfono',
        mobile_phone: 'Celular',
        birth_date: 'Fecha de Nacimiento',
        gender: 'Género',
        emergency_contact: 'Contacto de Emergencia',
        apartment_id: 'Apartamento',
        resident_type: 'Tipo de Residente',
        status: 'Estado',
        start_date: 'Fecha de Inicio',
        end_date: 'Fecha de Fin',
        notes: 'Notas',
        documents: 'Documentos',
        email_notifications: 'Notificaciones por Email',
        whatsapp_notifications: 'Notificaciones por WhatsApp',
        whatsapp_number: 'Número de WhatsApp',
        // Invoice fields
        type: 'Tipo de Factura',
        billing_date: 'Fecha de Facturación',
        due_date: 'Fecha de Vencimiento',
        billing_period_year: 'Año del Período',
        billing_period_month: 'Mes del Período',
        billing_period: 'Período de Facturación',
        items: 'Artículos de Factura',
        year: 'Año',
        month: 'Mes',
        error: 'Error',
    };

    return fieldLabels[field] || field;
};

// Auto-scroll to errors when they appear
watch(hasErrors, (newValue) => {
    if (newValue && alertRef.value) {
        nextTick(() => {
            alertRef.value?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        });
    }
});
</script>

<template>
    <Alert v-if="hasErrors && show" ref="alertRef" variant="destructive" class="mb-6">
        <AlertCircle class="h-4 w-4" />
        <div class="flex w-full items-start justify-between">
            <div class="flex-1">
                <h4 class="mb-2 font-medium">
                    {{ errorList.length === 1 ? 'Error de validación' : 'Errores de validación' }}
                </h4>
                <AlertDescription>
                    <ul class="list-inside list-disc space-y-1">
                        <li v-for="error in errorList" :key="`${error.field}-${error.message}`" class="text-sm">
                            <span class="font-medium">{{ getFieldLabel(error.field) }}:</span>
                            {{ error.message }}
                        </li>
                    </ul>
                </AlertDescription>
            </div>
            <Button variant="ghost" size="sm" @click="emit('dismiss')" class="ml-2 text-red-800 hover:text-red-900">
                <X class="h-4 w-4" />
            </Button>
        </div>
    </Alert>
</template>
