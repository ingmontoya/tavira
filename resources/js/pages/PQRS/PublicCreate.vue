<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { MessageSquare } from 'lucide-vue-next';

interface Apartment {
    id: number;
    number: string;
    type: string;
}

interface Props {
    apartments: Apartment[];
}

const props = defineProps<Props>();

const form = useForm({
    type: 'peticion',
    subject: '',
    description: '',
    submitter_name: '',
    submitter_email: '',
    submitter_phone: '',
    apartment_id: null as number | null,
});

const submit = () => {
    form.post(route('pqrs.public.store'));
};
</script>

<template>
    <Head title="Enviar PQRS" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 text-center">
                <div class="mb-4 flex justify-center">
                    <div class="rounded-full bg-blue-600 p-4">
                        <MessageSquare class="h-8 w-8 text-white" />
                    </div>
                </div>
                <h1 class="mb-2 text-3xl font-bold text-gray-900">Enviar PQRS</h1>
                <p class="text-lg text-gray-600">
                    Peticiones, Quejas, Reclamos y Sugerencias
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Formulario de PQRS</CardTitle>
                    <CardDescription>
                        Complete el siguiente formulario para enviar su petición, queja, reclamo o
                        sugerencia a la administración del conjunto.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Type Selection -->
                        <div class="space-y-2">
                            <Label for="type">Tipo de PQRS *</Label>
                            <Select v-model="form.type" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione el tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="peticion">Petición</SelectItem>
                                    <SelectItem value="queja">Queja</SelectItem>
                                    <SelectItem value="reclamo">Reclamo</SelectItem>
                                    <SelectItem value="sugerencia">Sugerencia</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-sm text-red-600">
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <!-- Subject -->
                        <div class="space-y-2">
                            <Label for="subject">Asunto *</Label>
                            <Input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                placeholder="Ingrese el asunto de su PQRS"
                                required
                            />
                            <p v-if="form.errors.subject" class="text-sm text-red-600">
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Descripción *</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describa detalladamente su petición, queja, reclamo o sugerencia"
                                rows="6"
                                required
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Divider -->
                        <div class="border-t pt-6">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                                Información de Contacto
                            </h3>
                        </div>

                        <!-- Submitter Name -->
                        <div class="space-y-2">
                            <Label for="submitter_name">Nombre Completo *</Label>
                            <Input
                                id="submitter_name"
                                v-model="form.submitter_name"
                                type="text"
                                placeholder="Ingrese su nombre completo"
                                required
                            />
                            <p v-if="form.errors.submitter_name" class="text-sm text-red-600">
                                {{ form.errors.submitter_name }}
                            </p>
                        </div>

                        <!-- Submitter Email -->
                        <div class="space-y-2">
                            <Label for="submitter_email">Correo Electrónico *</Label>
                            <Input
                                id="submitter_email"
                                v-model="form.submitter_email"
                                type="email"
                                placeholder="correo@ejemplo.com"
                                required
                            />
                            <p v-if="form.errors.submitter_email" class="text-sm text-red-600">
                                {{ form.errors.submitter_email }}
                            </p>
                        </div>

                        <!-- Submitter Phone -->
                        <div class="space-y-2">
                            <Label for="submitter_phone">Teléfono (Opcional)</Label>
                            <Input
                                id="submitter_phone"
                                v-model="form.submitter_phone"
                                type="tel"
                                placeholder="3001234567"
                            />
                            <p v-if="form.errors.submitter_phone" class="text-sm text-red-600">
                                {{ form.errors.submitter_phone }}
                            </p>
                        </div>

                        <!-- Apartment Selection -->
                        <div class="space-y-2">
                            <Label for="apartment_id">Apartamento/Casa (Opcional)</Label>
                            <Select v-model="form.apartment_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione su apartamento" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="apartment in apartments"
                                        :key="apartment.id"
                                        :value="apartment.id"
                                    >
                                        {{ apartment.number }} - {{ apartment.type }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.apartment_id" class="text-sm text-red-600">
                                {{ form.errors.apartment_id }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                                {{ form.processing ? 'Enviando...' : 'Enviar PQRS' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Information Card -->
            <Card class="mt-6 border-blue-200 bg-blue-50">
                <CardContent class="pt-6">
                    <h3 class="mb-2 font-semibold text-blue-900">¿Qué es una PQRS?</h3>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li>
                            <strong>Petición:</strong> Solicitud de información, documentos o
                            actuaciones.
                        </li>
                        <li>
                            <strong>Queja:</strong> Manifestación de inconformidad con un servicio
                            o comportamiento.
                        </li>
                        <li>
                            <strong>Reclamo:</strong> Solicitud de corrección o solución a una
                            situación.
                        </li>
                        <li>
                            <strong>Sugerencia:</strong> Propuesta de mejora para el conjunto.
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
