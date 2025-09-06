<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, ChevronDown, Plus, Search, Users, Vote, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface Assembly {
    id: number;
    title: string;
    description: string | null;
    type: 'ordinary' | 'extraordinary';
    status: 'scheduled' | 'in_progress' | 'closed' | 'cancelled';
    scheduled_at: string;
    started_at: string | null;
    ended_at: string | null;
    required_quorum_percentage: number;
    duration_minutes: number | null;
    is_active: boolean;
    can_vote: boolean;
    status_badge: {
        text: string;
        class: string;
    };
    quorum_status: {
        total_apartments: number;
        participating_apartments: number;
        quorum_percentage: number;
        required_quorum_percentage: number;
        has_quorum: boolean;
    };
    creator: {
        id: number;
        name: string;
    };
    votes_count: number;
    delegates_count: number;
}

const props = defineProps<{
    assemblies: {
        data: Assembly[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    stats: {
        total: number;
        scheduled: number;
        in_progress: number;
        closed: number;
    };
    filters?: {
        search?: string;
        status?: string;
        type?: string;
    };
}>();

// Filter state
const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedType = ref(props.filters?.type || '');

// Helper functions
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getTypeIcon = (type: string) => {
    return type === 'extraordinary' ? '⚡' : '📋';
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'scheduled': return '📅';
        case 'in_progress': return '🟢';
        case 'closed': return '✅';
        case 'cancelled': return '❌';
        default: return '❓';
    }
};

// Filter functions
const applyFilters = () => {
    const params: Record<string, any> = {};
    
    if (search.value) params.search = search.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedType.value) params.type = selectedType.value;
    
    router.get(route('assemblies.index'), params, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedType.value = '';
    
    router.get(route('assemblies.index'), {}, {
        preserveState: true,
        replace: true,
    });
};

const hasFilters = computed(() => {
    return search.value || selectedStatus.value || selectedType.value;
});

// Actions
const actions = [
    { label: 'Ver Detalle', action: (assembly: Assembly) => router.visit(route('assemblies.show', assembly.id)) },
    { label: 'Editar', action: (assembly: Assembly) => router.visit(route('assemblies.edit', assembly.id)), condition: (assembly: Assembly) => assembly.status === 'scheduled' },
    { label: 'Iniciar Asamblea', action: (assembly: Assembly) => router.post(route('assemblies.start', assembly.id)), condition: (assembly: Assembly) => assembly.status === 'scheduled' },
    { label: 'Cerrar Asamblea', action: (assembly: Assembly) => router.post(route('assemblies.close', assembly.id)), condition: (assembly: Assembly) => assembly.status === 'in_progress' },
    { label: 'Cancelar', action: (assembly: Assembly) => router.post(route('assemblies.cancel', assembly.id)), condition: (assembly: Assembly) => ['scheduled', 'in_progress'].includes(assembly.status) },
    { separator: true },
    { label: 'Eliminar', action: (assembly: Assembly) => router.delete(route('assemblies.destroy', assembly.id)), condition: (assembly: Assembly) => ['scheduled', 'closed'].includes(assembly.status), class: 'text-red-600' },
];
</script>

<template>
    <Head title="Asambleas" />
    
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Asambleas Digitales
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Gestiona asambleas y votaciones de tu conjunto residencial
                    </p>
                </div>
                <Link :href="route('assemblies.create')" class="inline-flex items-center">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Asamblea
                    </Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card class="p-4">
                        <div class="flex items-center">
                            <Calendar class="h-8 w-8 text-blue-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total</p>
                                <p class="text-2xl font-bold">{{ stats.total }}</p>
                            </div>
                        </div>
                    </Card>
                    
                    <Card class="p-4">
                        <div class="flex items-center">
                            <Calendar class="h-8 w-8 text-yellow-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Programadas</p>
                                <p class="text-2xl font-bold">{{ stats.scheduled }}</p>
                            </div>
                        </div>
                    </Card>
                    
                    <Card class="p-4">
                        <div class="flex items-center">
                            <Vote class="h-8 w-8 text-green-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">En Curso</p>
                                <p class="text-2xl font-bold">{{ stats.in_progress }}</p>
                            </div>
                        </div>
                    </Card>
                    
                    <Card class="p-4">
                        <div class="flex items-center">
                            <Users class="h-8 w-8 text-gray-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Cerradas</p>
                                <p class="text-2xl font-bold">{{ stats.closed }}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Filters -->
                <Card class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Buscar</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    v-model="search"
                                    placeholder="Buscar asambleas..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Estado</label>
                            <Select v-model="selectedStatus">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos los estados</SelectItem>
                                    <SelectItem value="scheduled">Programada</SelectItem>
                                    <SelectItem value="in_progress">En Curso</SelectItem>
                                    <SelectItem value="closed">Cerrada</SelectItem>
                                    <SelectItem value="cancelled">Cancelada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Tipo</label>
                            <Select v-model="selectedType">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos los tipos</SelectItem>
                                    <SelectItem value="ordinary">Ordinaria</SelectItem>
                                    <SelectItem value="extraordinary">Extraordinaria</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <Button @click="applyFilters" class="flex-1">
                                Filtrar
                            </Button>
                            <Button v-if="hasFilters" @click="clearFilters" variant="outline" size="icon">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- Assemblies Table -->
                <Card>
                    <div class="p-6">
                        <h3 class="text-lg font-medium mb-4">Asambleas</h3>
                        
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Asamblea</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Fecha Programada</TableHead>
                                        <TableHead>Quorum</TableHead>
                                        <TableHead>Votaciones</TableHead>
                                        <TableHead>Delegados</TableHead>
                                        <TableHead>Creado por</TableHead>
                                        <TableHead class="w-16"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="assembly in assemblies.data" :key="assembly.id">
                                        <TableCell>
                                            <div>
                                                <Link 
                                                    :href="route('assemblies.show', assembly.id)"
                                                    class="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                                >
                                                    {{ assembly.title }}
                                                </Link>
                                                <p v-if="assembly.description" class="text-sm text-gray-500 mt-1">
                                                    {{ assembly.description }}
                                                </p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center">
                                                <span class="mr-2">{{ getTypeIcon(assembly.type) }}</span>
                                                <span class="capitalize">{{ assembly.type === 'ordinary' ? 'Ordinaria' : 'Extraordinaria' }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center">
                                                <span class="mr-2">{{ getStatusIcon(assembly.status) }}</span>
                                                <Badge :class="assembly.status_badge.class">
                                                    {{ assembly.status_badge.text }}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {{ formatDate(assembly.scheduled_at) }}
                                        </TableCell>
                                        <TableCell>
                                            <div class="space-y-1">
                                                <div class="flex items-center text-sm">
                                                    <span class="font-medium">{{ assembly.quorum_status.quorum_percentage }}%</span>
                                                    <span class="text-gray-500 ml-1">({{ assembly.quorum_status.participating_apartments }}/{{ assembly.quorum_status.total_apartments }})</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        class="h-2 rounded-full transition-all duration-300"
                                                        :class="assembly.quorum_status.has_quorum ? 'bg-green-500' : 'bg-yellow-500'"
                                                        :style="{ width: `${Math.min(assembly.quorum_status.quorum_percentage, 100)}%` }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Link 
                                                :href="route('assemblies.votes.index', assembly.id)"
                                                class="inline-flex items-center px-2 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200"
                                            >
                                                {{ assembly.votes_count }} Votaciones
                                            </Link>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Link 
                                                :href="route('assemblies.delegates.index', assembly.id)"
                                                class="inline-flex items-center px-2 py-1 text-sm bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200"
                                            >
                                                {{ assembly.delegates_count }} Delegados
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            <span class="text-sm text-gray-600">{{ assembly.creator.name }}</span>
                                        </TableCell>
                                        <TableCell>
                                            <DropdownAction :actions="actions" :item="assembly" />
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- No assemblies message -->
                        <div v-if="assemblies.data.length === 0" class="text-center py-12">
                            <Calendar class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay asambleas</h3>
                            <p class="text-gray-500 mb-4">Crea la primera asamblea para tu conjunto residencial.</p>
                            <Link :href="route('assemblies.create')">
                                <Button>
                                    <Plus class="mr-2 h-4 w-4" />
                                    Nueva Asamblea
                                </Button>
                            </Link>
                        </div>

                        <!-- Pagination info -->
                        <div v-if="assemblies.data.length > 0" class="flex items-center justify-between pt-6 border-t">
                            <p class="text-sm text-gray-700">
                                Mostrando <span class="font-medium">{{ assemblies.from }}</span> a 
                                <span class="font-medium">{{ assemblies.to }}</span> de 
                                <span class="font-medium">{{ assemblies.total }}</span> resultados
                            </p>
                            <div class="flex space-x-2">
                                <Button 
                                    v-if="assemblies.prev_page_url"
                                    @click="router.visit(assemblies.prev_page_url!)"
                                    variant="outline"
                                    size="sm"
                                >
                                    Anterior
                                </Button>
                                <Button 
                                    v-if="assemblies.next_page_url"
                                    @click="router.visit(assemblies.next_page_url!)"
                                    variant="outline"
                                    size="sm"
                                >
                                    Siguiente
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>