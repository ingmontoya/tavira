<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Copy, Edit, Eye, Mail, MoreHorizontal, Plus, Power, PowerOff, Star, StarOff, Trash2 } from 'lucide-vue-next';
import type { AppPageProps, EmailTemplate, EmailTemplateResponse } from '@/types';

interface Props extends /* @vue-ignore */ AppPageProps {
    templates: EmailTemplateResponse;
    filters: {
        type?: string;
        status?: string;
        search?: string;
    };
    types: Record<string, string>;
}

const props = defineProps<Props>();

// Local state
const searchQuery = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || 'all');
const selectedStatus = ref(props.filters.status || 'all');
const deleteDialog = ref(false);
const templateToDelete = ref<EmailTemplate | null>(null);

// Toast composable
const { success, error } = useToast();

// Computed
const typeOptions = computed(() => [
    { value: 'all', label: 'Todos los tipos' },
    ...Object.entries(props.types).map(([value, label]) => ({ value, label }))
]);

const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'active', label: 'Activas' },
    { value: 'inactive', label: 'Inactivas' }
];

// Methods
const search = () => {
    router.get(route('email-templates.index'), {
        search: searchQuery.value || undefined,
        type: (selectedType.value && selectedType.value !== 'all') ? selectedType.value : undefined,
        status: (selectedStatus.value && selectedStatus.value !== 'all') ? selectedStatus.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedType.value = 'all';
    selectedStatus.value = 'all';
    router.get(route('email-templates.index'));
};

const setAsDefault = (template: EmailTemplate) => {
    router.post(route('email-templates.set-default', template.id), {}, {
        onSuccess: () => {
            success('Plantilla marcada como predeterminada.', 'Éxito');
        },
    });
};

const toggleStatus = (template: EmailTemplate) => {
    router.post(route('email-templates.toggle-status', template.id), {}, {
        onSuccess: () => {
            const status = template.is_active ? 'desactivada' : 'activada';
            success(`Plantilla ${status} exitosamente.`, 'Éxito');
        },
    });
};

const duplicateTemplate = (template: EmailTemplate) => {
    router.post(route('email-templates.duplicate', template.id), {}, {
        onSuccess: () => {
            success('Plantilla duplicada exitosamente.', 'Éxito');
        },
    });
};

const confirmDelete = (template: EmailTemplate) => {
    templateToDelete.value = template;
    deleteDialog.value = true;
};

const deleteTemplate = () => {
    if (!templateToDelete.value) return;

    router.delete(route('email-templates.destroy', templateToDelete.value.id), {
        onSuccess: () => {
            success('Plantilla eliminada exitosamente.', 'Éxito');
            deleteDialog.value = false;
            templateToDelete.value = null;
        },
        onError: () => {
            error('No se pudo eliminar la plantilla.', 'Error');
        },
    });
};

const getTypeColor = (type: string) => {
    const colors = {
        invoice: 'bg-blue-100 text-blue-800',
        payment_receipt: 'bg-green-100 text-green-800',
        payment_reminder: 'bg-yellow-100 text-yellow-800',
        welcome: 'bg-purple-100 text-purple-800',
        announcement: 'bg-indigo-100 text-indigo-800',
        custom: 'bg-gray-100 text-gray-800',
    };
    return colors[type as keyof typeof colors] || colors.custom;
};
</script>

<template>
    <AppLayout>
        <Head title="Plantillas de Email" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Plantillas de Email</h1>
                    <p class="text-muted-foreground">
                        Gestiona las plantillas para los envíos de correo electrónico
                    </p>
                </div>
                <Link :href="route('email-templates.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Plantilla
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <Input
                                v-model="searchQuery"
                                placeholder="Buscar plantillas..."
                                @keyup.enter="search"
                            />
                        </div>
                        <div>
                            <Select v-model="selectedType" @update:model-value="search">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in typeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Select v-model="selectedStatus" @update:model-value="search">
                                <SelectTrigger>
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex space-x-2">
                            <Button @click="search" variant="outline" class="flex-1">
                                Buscar
                            </Button>
                            <Button @click="clearFilters" variant="ghost">
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Templates Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card
                    v-for="template in templates.data"
                    :key="template.id"
                    class="hover:shadow-md transition-shadow"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1 flex-1">
                                <CardTitle class="text-lg flex items-center space-x-2">
                                    <Mail class="h-5 w-5 text-muted-foreground" />
                                    <span>{{ template.name }}</span>
                                    <Star
                                        v-if="template.is_default"
                                        class="h-4 w-4 text-yellow-500 fill-current"
                                    />
                                </CardTitle>
                                <CardDescription>{{ template.description }}</CardDescription>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="route('email-templates.show', template.id)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            Ver
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <Link :href="route('email-templates.edit', template.id)">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Editar
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="duplicateTemplate(template)">
                                        <Copy class="mr-2 h-4 w-4" />
                                        Duplicar
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        v-if="!template.is_default"
                                        @click="setAsDefault(template)"
                                    >
                                        <Star class="mr-2 h-4 w-4" />
                                        Marcar como Predeterminada
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="toggleStatus(template)">
                                        <component
                                            :is="template.is_active ? PowerOff : Power"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ template.is_active ? 'Desactivar' : 'Activar' }}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        v-if="!template.is_default"
                                        @click="confirmDelete(template)"
                                        class="text-destructive"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    
                    <CardContent class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <Badge :class="getTypeColor(template.type)">
                                {{ types[template.type] }}
                            </Badge>
                            <Badge variant="outline" :class="template.is_active ? 'text-green-600 border-green-200' : 'text-gray-500 border-gray-200'">
                                {{ template.is_active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                        </div>

                        <div class="space-y-2 text-sm text-muted-foreground">
                            <div>
                                <strong>Asunto:</strong> 
                                <span class="text-foreground">{{ template.subject }}</span>
                            </div>
                            <div>
                                <strong>Variables:</strong> 
                                <span class="text-foreground">{{ template.variables.length }} disponibles</span>
                            </div>
                        </div>

                        <div class="flex space-x-2 pt-2">
                            <Button as-child size="sm" variant="outline" class="flex-1">
                                <Link :href="route('email-templates.show', template.id)">
                                    <Eye class="mr-2 h-4 w-4" />
                                    Ver
                                </Link>
                            </Button>
                            <Button as-child size="sm" class="flex-1">
                                <Link :href="route('email-templates.edit', template.id)">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Editar
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="templates.data.length === 0" class="text-center py-12">
                <Mail class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">No hay plantillas</h3>
                <p class="mt-2 text-muted-foreground">
                    {{ filters.search || filters.type || filters.status 
                        ? 'No se encontraron plantillas con los filtros aplicados.' 
                        : 'Comienza creando tu primera plantilla de email.' 
                    }}
                </p>
                <Link
                    v-if="!filters.search && !filters.type && !filters.status"
                    :href="route('email-templates.create')"
                    class="mt-4 inline-flex"
                >
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Plantilla
                    </Button>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="templates.last_page > 1" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Mostrando {{ templates.from }} a {{ templates.to }} de {{ templates.total }} resultados
                </div>
                <div class="flex space-x-2">
                    <Button
                        v-if="templates.prev_page_url"
                        variant="outline"
                        size="sm"
                        @click="router.get(templates.prev_page_url)"
                    >
                        Anterior
                    </Button>
                    <Button
                        v-if="templates.next_page_url"
                        variant="outline"
                        size="sm"
                        @click="router.get(templates.next_page_url)"
                    >
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog v-model:open="deleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>¿Eliminar plantilla?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta acción no se puede deshacer. La plantilla "{{ templateToDelete?.name }}" 
                        será eliminada permanentemente.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteTemplate" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Eliminar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>