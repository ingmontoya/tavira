<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarIcon, Save, Send } from 'lucide-vue-next';
import { computed } from 'vue';

interface Announcement {
    id: number;
    title: string;
    content: string;
    priority: 'urgent' | 'important' | 'normal';
    type: 'general' | 'administrative' | 'maintenance' | 'emergency';
    status: 'draft' | 'published' | 'archived';
    is_pinned: boolean;
    requires_confirmation: boolean;
    published_at: string | null;
    expires_at: string | null;
    attachments: any[];
}

interface Props {
    announcement: Announcement;
}

const props = defineProps<Props>();

const form = useForm({
    title: props.announcement.title,
    content: props.announcement.content,
    priority: props.announcement.priority,
    type: props.announcement.type,
    status: props.announcement.status,
    is_pinned: props.announcement.is_pinned,
    requires_confirmation: props.announcement.requires_confirmation,
    published_at: props.announcement.published_at ? props.announcement.published_at.slice(0, 16) : '',
    expires_at: props.announcement.expires_at ? props.announcement.expires_at.slice(0, 16) : '',
    attachments: props.announcement.attachments || [],
});

const submit = () => {
    // Ensure boolean values are properly converted
    form.transform((data) => ({
        ...data,
        is_pinned: Boolean(data.is_pinned),
        requires_confirmation: Boolean(data.requires_confirmation),
    })).put(route('announcements.update', props.announcement.id));
};

const publishAndSend = () => {
    form.status = 'published';
    if (!form.published_at) {
        form.published_at = new Date().toISOString().slice(0, 16);
    }
    // Ensure boolean values are properly converted
    form.transform((data) => ({
        ...data,
        is_pinned: Boolean(data.is_pinned),
        requires_confirmation: Boolean(data.requires_confirmation),
    })).put(route('announcements.update', props.announcement.id));
};

const breadcrumbs = [
    { title: 'Comunicación', href: '#' },
    { title: 'Anuncios', href: route('announcements.index') },
    { title: 'Editar Anuncio', href: route('announcements.edit', props.announcement.id) }
];
</script>

<template>
    <Head :title="`Editar: ${announcement.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Editar Anuncio</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Modifica el anuncio "{{ announcement.title }}"
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        @click="router.visit(route('announcements.index'))"
                    >
                        Cancelar
                    </Button>
                    <Button
                        @click="submit"
                        :disabled="form.processing"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Cambios
                    </Button>
                    <Button
                        v-if="announcement.status !== 'published'"
                        @click="publishAndSend"
                        :disabled="form.processing"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Publicar
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Información Básica</CardTitle>
                        <CardDescription>
                            Información principal del anuncio
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                        <SelectItem value="archived">Archivado</SelectItem>
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
                        <CardTitle>Configuración Avanzada</CardTitle>
                        <CardDescription>
                            Opciones adicionales para el anuncio
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center">
                                    <input
                                        id="is_pinned"
                                        v-model="form.is_pinned"
                                        type="checkbox"
                                        class="sr-only"
                                    />
                                    <label
                                        for="is_pinned"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        :class="form.is_pinned ? 'bg-primary' : 'bg-gray-200'"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            :class="form.is_pinned ? 'translate-x-6' : 'translate-x-1'"
                                        ></span>
                                    </label>
                                </div>
                                <Label for="is_pinned" class="text-sm font-medium cursor-pointer">
                                    Fijar anuncio
                                    <span class="text-xs text-gray-500 ml-1">
                                        ({{ form.is_pinned ? 'Activado' : 'Desactivado' }})
                                    </span>
                                </Label>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="flex items-center">
                                    <input
                                        id="requires_confirmation"
                                        v-model="form.requires_confirmation"
                                        type="checkbox"
                                        class="sr-only"
                                    />
                                    <label
                                        for="requires_confirmation"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        :class="form.requires_confirmation ? 'bg-primary' : 'bg-gray-200'"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            :class="form.requires_confirmation ? 'translate-x-6' : 'translate-x-1'"
                                        ></span>
                                    </label>
                                </div>
                                <Label for="requires_confirmation" class="text-sm font-medium cursor-pointer">
                                    Requiere confirmación
                                    <span class="text-xs text-gray-500 ml-1">
                                        ({{ form.requires_confirmation ? 'Activado' : 'Desactivado' }})
                                    </span>
                                </Label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="published_at">Fecha de Publicación</Label>
                                <Input
                                    id="published_at"
                                    v-model="form.published_at"
                                    type="datetime-local"
                                />
                                <p class="text-xs text-gray-500">
                                    Deja vacío para publicar inmediatamente
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="expires_at">Fecha de Expiración</Label>
                                <Input
                                    id="expires_at"
                                    v-model="form.expires_at"
                                    type="datetime-local"
                                />
                                <p class="text-xs text-gray-500">
                                    Opcional. El anuncio dejará de ser visible después de esta fecha
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions (Mobile) -->
                <Card class="md:hidden">
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-2">
                            <Button
                                v-if="announcement.status !== 'published'"
                                type="button"
                                @click="publishAndSend"
                                :disabled="form.processing"
                                class="w-full"
                            >
                                <Send class="mr-2 h-4 w-4" />
                                Publicar
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                Guardar Cambios
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.visit(route('announcements.index'))"
                                class="w-full"
                            >
                                Cancelar
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
