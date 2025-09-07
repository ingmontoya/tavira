<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Calendar, 
    CheckCircle, 
    Clock, 
    Eye, 
    Pause, 
    Play, 
    Plus, 
    Vote, 
    X 
} from 'lucide-vue-next';

interface Assembly {
    id: number;
    title: string;
    status: string;
}

interface Vote {
    id: number;
    title: string;
    description: string;
    type: 'simple' | 'qualified' | 'unanimous';
    status: 'draft' | 'active' | 'closed';
    started_at?: string;
    ended_at?: string;
    duration_minutes?: number;
    required_percentage: number;
    total_votes: number;
    yes_votes: number;
    no_votes: number;
    abstentions: number;
    results?: {
        approved: boolean;
        percentage: number;
        total_participants: number;
    };
    created_at: string;
}

const props = defineProps<{
    assembly: Assembly;
    votes: {
        data: Vote[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};

const getVoteTypeLabel = (type: string) => {
    const typeMap = {
        simple: 'Mayoría Simple',
        qualified: 'Mayoría Calificada',
        unanimous: 'Unanimidad',
    };
    return typeMap[type] || type;
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'draft':
            return Clock;
        case 'active':
            return Play;
        case 'closed':
            return CheckCircle;
        default:
            return Clock;
    }
};

const getStatusBadge = (status: string) => {
    const statusMap = {
        draft: { text: 'Borrador', class: 'bg-gray-100 text-gray-800' },
        active: { text: 'Activa', class: 'bg-green-100 text-green-800' },
        closed: { text: 'Cerrada', class: 'bg-blue-100 text-blue-800' },
    };
    return statusMap[status] || { text: status, class: 'bg-gray-100 text-gray-800' };
};

const activateVote = (voteId: number) => {
    router.post(route('assemblies.votes.activate', [props.assembly.id, voteId]));
};

const closeVote = (voteId: number) => {
    router.post(route('assemblies.votes.close', [props.assembly.id, voteId]));
};

const deleteVote = (voteId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta votación?')) {
        router.delete(route('assemblies.votes.destroy', [props.assembly.id, voteId]));
    }
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
    { title: 'Votaciones', href: `/assemblies/${props.assembly.id}/votes` },
];
</script>

<template>
    <Head :title="`Votaciones - ${assembly.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Votaciones</h1>
                    </div>
                    <p class="text-muted-foreground">{{ assembly.title }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="`/assemblies/${assembly.id}`">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver a Asamblea
                        </Button>
                    </Link>

                    <Link 
                        :href="`/assemblies/${assembly.id}/votes/create`"
                        v-if="assembly.status === 'in_progress'"
                    >
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Nueva Votación
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <Vote class="h-8 w-8 text-blue-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total</p>
                                <p class="text-2xl font-bold">{{ votes.total }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <Clock class="h-8 w-8 text-yellow-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Borradores</p>
                                <p class="text-2xl font-bold">
                                    {{ votes.data.filter(v => v.status === 'draft').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <Play class="h-8 w-8 text-green-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Activas</p>
                                <p class="text-2xl font-bold">
                                    {{ votes.data.filter(v => v.status === 'active').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <CheckCircle class="h-8 w-8 text-blue-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Cerradas</p>
                                <p class="text-2xl font-bold">
                                    {{ votes.data.filter(v => v.status === 'closed').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Votes Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Vote class="h-5 w-5" />
                        Lista de Votaciones
                    </CardTitle>
                    <CardDescription>
                        Gestiona todas las votaciones de esta asamblea
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="votes.data.length > 0" class="space-y-4">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Votación</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Participación</TableHead>
                                        <TableHead>Resultados</TableHead>
                                        <TableHead>Duración</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="vote in votes.data" :key="vote.id">
                                        <!-- Vote Title and Description -->
                                        <TableCell>
                                            <div>
                                                <Link 
                                                    :href="`/assemblies/${assembly.id}/votes/${vote.id}`"
                                                    class="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                                >
                                                    {{ vote.title }}
                                                </Link>
                                                <p v-if="vote.description" class="text-sm text-gray-500 mt-1">
                                                    {{ vote.description.substring(0, 100) }}{{ vote.description.length > 100 ? '...' : '' }}
                                                </p>
                                            </div>
                                        </TableCell>

                                        <!-- Vote Type -->
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Badge variant="outline">
                                                    {{ getVoteTypeLabel(vote.type) }}
                                                </Badge>
                                                <span class="text-xs text-gray-500">
                                                    {{ vote.required_percentage }}%
                                                </span>
                                            </div>
                                        </TableCell>

                                        <!-- Status -->
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <component :is="getStatusIcon(vote.status)" class="h-4 w-4" />
                                                <Badge :class="getStatusBadge(vote.status).class">
                                                    {{ getStatusBadge(vote.status).text }}
                                                </Badge>
                                            </div>
                                        </TableCell>

                                        <!-- Participation -->
                                        <TableCell>
                                            <div class="space-y-2">
                                                <div class="text-sm">
                                                    {{ vote.total_votes }} votos
                                                </div>
                                                <div v-if="vote.status === 'active' || vote.status === 'closed'" class="space-y-1">
                                                    <div class="flex text-xs gap-2">
                                                        <span class="text-green-600">Sí: {{ vote.yes_votes }}</span>
                                                        <span class="text-red-600">No: {{ vote.no_votes }}</span>
                                                        <span class="text-gray-600">Abst: {{ vote.abstentions }}</span>
                                                    </div>
                                                    <Progress 
                                                        :value="vote.total_votes > 0 ? (vote.yes_votes / vote.total_votes) * 100 : 0" 
                                                        class="h-2"
                                                    />
                                                </div>
                                            </div>
                                        </TableCell>

                                        <!-- Results -->
                                        <TableCell>
                                            <div v-if="vote.results" class="space-y-1">
                                                <Badge 
                                                    :variant="vote.results.approved ? 'default' : 'secondary'"
                                                    :class="vote.results.approved ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                >
                                                    {{ vote.results.approved ? 'Aprobada' : 'Rechazada' }}
                                                </Badge>
                                                <div class="text-xs text-gray-500">
                                                    {{ vote.results.percentage.toFixed(1) }}% de aprobación
                                                </div>
                                            </div>
                                            <div v-else class="text-sm text-gray-500">
                                                {{ vote.status === 'active' ? 'En progreso' : 'Pendiente' }}
                                            </div>
                                        </TableCell>

                                        <!-- Duration -->
                                        <TableCell>
                                            <div v-if="vote.duration_minutes" class="text-sm">
                                                {{ formatDuration(vote.duration_minutes) }}
                                            </div>
                                            <div v-else-if="vote.started_at" class="text-sm text-gray-500">
                                                Iniciada: {{ formatDate(vote.started_at) }}
                                            </div>
                                            <div v-else class="text-sm text-gray-500">
                                                -
                                            </div>
                                        </TableCell>

                                        <!-- Actions -->
                                        <TableCell>
                                            <div class="flex items-center gap-1">
                                                <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}`">
                                                    <Button size="sm" variant="ghost">
                                                        <Eye class="h-4 w-4" />
                                                    </Button>
                                                </Link>

                                                <template v-if="vote.status === 'draft'">
                                                    <Button 
                                                        @click="activateVote(vote.id)" 
                                                        size="sm" 
                                                        variant="ghost"
                                                        title="Activar votación"
                                                    >
                                                        <Play class="h-4 w-4" />
                                                    </Button>
                                                    <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}/edit`">
                                                        <Button size="sm" variant="ghost">
                                                            <Edit class="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button 
                                                        @click="deleteVote(vote.id)" 
                                                        size="sm" 
                                                        variant="ghost"
                                                        class="text-red-600 hover:text-red-700"
                                                    >
                                                        <X class="h-4 w-4" />
                                                    </Button>
                                                </template>

                                                <template v-else-if="vote.status === 'active'">
                                                    <Button 
                                                        @click="closeVote(vote.id)" 
                                                        size="sm" 
                                                        variant="ghost"
                                                        title="Cerrar votación"
                                                    >
                                                        <Pause class="h-4 w-4" />
                                                    </Button>
                                                </template>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="votes.total > 0" class="flex items-center justify-between pt-6 border-t">
                            <p class="text-sm text-gray-700">
                                Mostrando <span class="font-medium">{{ votes.from }}</span> a 
                                <span class="font-medium">{{ votes.to }}</span> de 
                                <span class="font-medium">{{ votes.total }}</span> resultados
                            </p>
                            <div class="flex space-x-2">
                                <Button 
                                    v-if="votes.prev_page_url"
                                    @click="router.visit(votes.prev_page_url!)"
                                    variant="outline"
                                    size="sm"
                                >
                                    Anterior
                                </Button>
                                <Button 
                                    v-if="votes.next_page_url"
                                    @click="router.visit(votes.next_page_url!)"
                                    variant="outline"
                                    size="sm"
                                >
                                    Siguiente
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- No votes message -->
                    <div v-else class="text-center py-12">
                        <Vote class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay votaciones</h3>
                        <p class="text-gray-500 mb-4">Crea la primera votación para esta asamblea.</p>
                        <Link 
                            :href="`/assemblies/${assembly.id}/votes/create`"
                            v-if="assembly.status === 'in_progress'"
                        >
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Nueva Votación
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>