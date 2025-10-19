<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, MessageSquare, Send } from 'lucide-vue-next';

const form = useForm({
    title: '',
    description: '',
    priority: 'medium',
    category: 'general',
});

const priorityOptions = [
    { value: 'low', label: 'Baja', description: 'Problema menor que no afecta el funcionamiento crítico' },
    { value: 'medium', label: 'Media', description: 'Problema moderado que requiere atención' },
    { value: 'high', label: 'Alta', description: 'Problema importante que afecta funcionalidad clave' },
    { value: 'urgent', label: 'Urgente', description: 'Problema crítico que requiere atención inmediata' },
];

const categoryOptions = [
    { value: 'technical', label: 'Técnico', description: 'Problemas técnicos y errores del sistema' },
    { value: 'billing', label: 'Facturación', description: 'Consultas sobre pagos, facturas y cuentas' },
    { value: 'general', label: 'General', description: 'Consultas generales y dudas sobre el servicio' },
    { value: 'feature_request', label: 'Solicitud de función', description: 'Sugerencias para nuevas funcionalidades' },
    { value: 'bug_report', label: 'Reporte de error', description: 'Reportar errores o comportamientos inesperados' },
];

const submit = () => {
    form.post(route('support.store'), {
        onSuccess: () => {
            router.visit('/support');
        },
    });
};

const cancel = () => {
    router.visit('/support');
};
</script>

<template>
    <Head title="Crear Ticket de Soporte" />

    <AppLayout>
        <div class="container mx-auto max-w-3xl px-4 py-8">
            <div class="mb-6 flex items-center gap-4">
                <Button variant="ghost" size="sm" @click="cancel" class="p-2">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Crear Ticket de Soporte</h1>
                    <p class="mt-1 text-sm text-gray-600">Describe tu problema o consulta para recibir ayuda</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Información del Ticket
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Título -->
                        <div class="space-y-2">
                            <Label for="title" class="text-sm font-medium"> Título del ticket * </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Describe brevemente tu problema o consulta"
                                :class="{ 'border-red-500': form.errors.title }"
                                required
                            />
                            <p v-if="form.errors.title" class="text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Categoría -->
                        <div class="space-y-2">
                            <Label for="category" class="text-sm font-medium"> Categoría * </Label>
                            <Select v-model="form.category" required>
                                <SelectTrigger :class="{ 'border-red-500': form.errors.category }">
                                    <SelectValue placeholder="Selecciona la categoría que mejor describe tu consulta" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in categoryOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.category" class="text-sm text-red-600">
                                {{ form.errors.category }}
                            </p>
                            <p v-else-if="form.category" class="text-xs text-muted-foreground">
                                {{ categoryOptions.find((opt) => opt.value === form.category)?.description }}
                            </p>
                        </div>

                        <!-- Prioridad -->
                        <div class="space-y-2">
                            <Label for="priority" class="text-sm font-medium"> Prioridad * </Label>
                            <Select v-model="form.priority" required>
                                <SelectTrigger :class="{ 'border-red-500': form.errors.priority }">
                                    <SelectValue placeholder="Selecciona la prioridad del ticket" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.priority" class="text-sm text-red-600">
                                {{ form.errors.priority }}
                            </p>
                            <p v-else-if="form.priority" class="text-xs text-muted-foreground">
                                {{ priorityOptions.find((opt) => opt.value === form.priority)?.description }}
                            </p>
                        </div>

                        <!-- Descripción -->
                        <div class="space-y-2">
                            <Label for="description" class="text-sm font-medium"> Descripción detallada * </Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe detalladamente tu problema o consulta. Incluye pasos para reproducir el problema si es aplicable."
                                rows="6"
                                :class="{ 'border-red-500': form.errors.description }"
                                required
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Proporciona la mayor cantidad de detalles posible para ayudarnos a resolver tu consulta más rápidamente.
                            </p>
                        </div>

                        <!-- Información adicional -->
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <h3 class="mb-2 font-medium text-blue-900">¿Qué sucede después?</h3>
                            <ul class="space-y-1 text-sm text-blue-800">
                                <li>• Recibirás una confirmación por email cuando se cree tu ticket</li>
                                <li>• Nuestro equipo de soporte revisará tu consulta</li>
                                <li>• Te notificaremos por email cuando recibas una respuesta</li>
                                <li>• Podrás seguir el progreso desde esta plataforma</li>
                            </ul>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex gap-3 pt-4">
                            <Button type="submit" :disabled="form.processing" class="flex items-center gap-2">
                                <Send class="h-4 w-4" />
                                {{ form.processing ? 'Creando...' : 'Crear Ticket' }}
                            </Button>
                            <Button type="button" variant="outline" @click="cancel" :disabled="form.processing"> Cancelar </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
