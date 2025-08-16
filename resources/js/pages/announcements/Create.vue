<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Save, Send } from 'lucide-vue-next';

interface ApartmentType {
    id: number;
    name: string;
}

interface Apartment {
    id: number;
    label: string;
    tower: string;
    apartment_type_id: number;
}

interface Props {
    towers: string[];
    apartmentTypes: ApartmentType[];
    apartments: Apartment[];
}

const props = defineProps<Props>();

const form = useForm({
    title: '',
    content: '',
    priority: 'normal',
    type: 'general',
    status: 'draft',
    is_pinned: false,
    requires_confirmation: false,
    published_at: '',
    expires_at: '',
    attachments: [],
    target_scope: 'general',
    target_towers: [],
    target_apartment_type_ids: [],
    target_apartment_ids: [],
});

const submit = () => {
    // Ensure boolean values are properly converted
    const transformedData = {
        ...form.data(),
        is_pinned: Boolean(form.is_pinned),
        requires_confirmation: Boolean(form.requires_confirmation),
    };

    // Only include the targeting fields that are relevant for the selected scope
    if (form.target_scope === 'tower') {
        transformedData.target_towers = form.target_towers;
    } else if (form.target_scope === 'apartment_type') {
        transformedData.target_apartment_type_ids = form.target_apartment_type_ids;
    } else if (form.target_scope === 'apartment') {
        transformedData.target_apartment_ids = form.target_apartment_ids;
    }

    form.transform(() => transformedData).post(route('announcements.store'));
};

const publishAndSend = () => {
    form.status = 'published';
    if (!form.published_at) {
        form.published_at = new Date().toISOString().slice(0, 16);
    }

    // Ensure boolean values are properly converted
    const transformedData = {
        ...form.data(),
        is_pinned: Boolean(form.is_pinned),
        requires_confirmation: Boolean(form.requires_confirmation),
        status: 'published',
        published_at: form.published_at,
    };

    // Only include the targeting fields that are relevant for the selected scope
    if (form.target_scope === 'tower') {
        transformedData.target_towers = form.target_towers;
    } else if (form.target_scope === 'apartment_type') {
        transformedData.target_apartment_type_ids = form.target_apartment_type_ids;
    } else if (form.target_scope === 'apartment') {
        transformedData.target_apartment_ids = form.target_apartment_ids;
    }

    form.transform(() => transformedData).post(route('announcements.store'));
};

const breadcrumbs = [
    { title: 'Comunicación', href: '#' },
    { title: 'Anuncios', href: route('announcements.index') },
    { title: 'Nuevo Anuncio', href: route('announcements.create') },
];
</script>

<template>
    <Head title="Nuevo Anuncio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Nuevo Anuncio</h1>
                    <p class="mt-1 text-sm text-gray-600">Crea un nuevo anuncio para los residentes</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="router.visit(route('announcements.index'))"> Cancelar </Button>
                    <Button @click="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Borrador
                    </Button>
                    <Button @click="publishAndSend" :disabled="form.processing">
                        <Send class="mr-2 h-4 w-4" />
                        Publicar
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Información Básica</CardTitle>
                        <CardDescription> Información principal del anuncio </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="title">Título *</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Título del anuncio"
                                    :class="{ 'border-red-500': form.errors.title }"
                                />
                                <p v-if="form.errors.title" class="text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="priority">Prioridad *</Label>
                                <Select v-model="form.priority">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="normal">Normal</SelectItem>
                                        <SelectItem value="important">Importante</SelectItem>
                                        <SelectItem value="urgent">Urgente</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.priority" class="text-sm text-red-600">
                                    {{ form.errors.priority }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="type">Tipo *</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="general">General</SelectItem>
                                        <SelectItem value="administrative">Administrativo</SelectItem>
                                        <SelectItem value="maintenance">Mantenimiento</SelectItem>
                                        <SelectItem value="emergency">Emergencia</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-sm text-red-600">
                                    {{ form.errors.type }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="status">Estado *</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Borrador</SelectItem>
                                        <SelectItem value="published">Publicado</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="text-sm text-red-600">
                                    {{ form.errors.status }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="content">Contenido *</Label>
                            <Textarea
                                id="content"
                                v-model="form.content"
                                placeholder="Escribe el contenido del anuncio..."
                                rows="6"
                                :class="{ 'border-red-500': form.errors.content }"
                            />
                            <p v-if="form.errors.content" class="text-sm text-red-600">
                                {{ form.errors.content }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Alcance del Anuncio</CardTitle>
                        <CardDescription> Define a quién va dirigido este anuncio </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="target_scope">Tipo de Alcance *</Label>
                            <Select v-model="form.target_scope">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona el alcance" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="general">General (Todo el conjunto)</SelectItem>
                                    <SelectItem value="tower">Por Torre(s)</SelectItem>
                                    <SelectItem value="apartment_type">Por Tipo de Apartamento</SelectItem>
                                    <SelectItem value="apartment">Apartamento Específico</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.target_scope" class="text-sm text-red-600">
                                {{ form.errors.target_scope }}
                            </p>
                        </div>

                        <!-- Tower Selection -->
                        <div v-if="form.target_scope === 'tower'" class="space-y-2">
                            <Label>Torres *</Label>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                <div v-for="tower in props.towers" :key="tower" class="flex items-center space-x-2">
                                    <Checkbox
                                        :id="`tower-${tower}`"
                                        :checked="form.target_towers.includes(tower)"
                                        @update:checked="
                                            (checked) => {
                                                if (checked) {
                                                    if (!form.target_towers.includes(tower)) {
                                                        form.target_towers = [...form.target_towers, tower];
                                                    }
                                                } else {
                                                    form.target_towers = form.target_towers.filter((t) => t !== tower);
                                                }
                                            }
                                        "
                                    />
                                    <Label :for="`tower-${tower}`" class="text-sm">Torre {{ tower }}</Label>
                                </div>
                            </div>
                            <p v-if="form.errors.target_towers" class="text-sm text-red-600">
                                {{ form.errors.target_towers }}
                            </p>
                        </div>

                        <!-- Apartment Type Selection -->
                        <div v-if="form.target_scope === 'apartment_type'" class="space-y-2">
                            <Label>Tipos de Apartamento *</Label>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                <div v-for="type in props.apartmentTypes" :key="type.id" class="flex items-center space-x-2">
                                    <Checkbox
                                        :id="`type-${type.id}`"
                                        :checked="form.target_apartment_type_ids.includes(type.id)"
                                        @update:checked="
                                            (checked) => {
                                                if (checked) {
                                                    if (!form.target_apartment_type_ids.includes(type.id)) {
                                                        form.target_apartment_type_ids = [...form.target_apartment_type_ids, type.id];
                                                    }
                                                } else {
                                                    form.target_apartment_type_ids = form.target_apartment_type_ids.filter((t) => t !== type.id);
                                                }
                                            }
                                        "
                                    />
                                    <Label :for="`type-${type.id}`" class="text-sm">{{ type.name }}</Label>
                                </div>
                            </div>
                            <p v-if="form.errors.target_apartment_type_ids" class="text-sm text-red-600">
                                {{ form.errors.target_apartment_type_ids }}
                            </p>
                        </div>

                        <!-- Apartment Selection -->
                        <div v-if="form.target_scope === 'apartment'" class="space-y-2">
                            <Label>Apartamentos *</Label>
                            <div class="max-h-40 overflow-y-auto rounded border p-2">
                                <div class="grid grid-cols-1 gap-1 md:grid-cols-2">
                                    <div v-for="apartment in props.apartments" :key="apartment.id" class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            :id="`apt-${apartment.id}`"
                                            :value="apartment.id"
                                            v-model="form.target_apartment_ids"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <Label :for="`apt-${apartment.id}`" class="text-sm">{{ apartment.label }}</Label>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.target_apartment_ids" class="text-sm text-red-600">
                                {{ form.errors.target_apartment_ids }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Configuración Avanzada</CardTitle>
                        <CardDescription> Opciones adicionales para el anuncio </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center">
                                    <input id="is_pinned" v-model="form.is_pinned" type="checkbox" class="sr-only" />
                                    <label
                                        for="is_pinned"
                                        class="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full transition-colors focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                        :class="form.is_pinned ? 'bg-primary' : 'bg-gray-200'"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            :class="form.is_pinned ? 'translate-x-6' : 'translate-x-1'"
                                        ></span>
                                    </label>
                                </div>
                                <Label for="is_pinned" class="cursor-pointer text-sm font-medium">
                                    Fijar anuncio
                                    <span class="ml-1 text-xs text-gray-500"> ({{ form.is_pinned ? 'Activado' : 'Desactivado' }}) </span>
                                </Label>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="flex items-center">
                                    <input id="requires_confirmation" v-model="form.requires_confirmation" type="checkbox" class="sr-only" />
                                    <label
                                        for="requires_confirmation"
                                        class="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full transition-colors focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                        :class="form.requires_confirmation ? 'bg-primary' : 'bg-gray-200'"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            :class="form.requires_confirmation ? 'translate-x-6' : 'translate-x-1'"
                                        ></span>
                                    </label>
                                </div>
                                <Label for="requires_confirmation" class="cursor-pointer text-sm font-medium">
                                    Requiere confirmación
                                    <span class="ml-1 text-xs text-gray-500"> ({{ form.requires_confirmation ? 'Activado' : 'Desactivado' }}) </span>
                                </Label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="published_at">Fecha de Publicación</Label>
                                <Input id="published_at" v-model="form.published_at" type="datetime-local" />
                                <p class="text-xs text-gray-500">Deja vacío para publicar inmediatamente</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="expires_at">Fecha de Expiración</Label>
                                <Input id="expires_at" v-model="form.expires_at" type="datetime-local" />
                                <p class="text-xs text-gray-500">Opcional. El anuncio dejará de ser visible después de esta fecha</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions (Mobile) -->
                <Card class="md:hidden">
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-2">
                            <Button type="button" @click="publishAndSend" :disabled="form.processing" class="w-full">
                                <Send class="mr-2 h-4 w-4" />
                                Publicar
                            </Button>
                            <Button type="submit" variant="outline" :disabled="form.processing" class="w-full">
                                <Save class="mr-2 h-4 w-4" />
                                Guardar Borrador
                            </Button>
                            <Button type="button" variant="outline" @click="router.visit(route('announcements.index'))" class="w-full">
                                Cancelar
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
