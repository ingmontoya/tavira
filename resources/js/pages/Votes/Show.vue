<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Calendar, 
    CheckCircle, 
    Clock, 
    Download, 
    Edit, 
    Pause, 
    Play, 
    Users, 
    Vote, 
    X 
} from 'lucide-vue-next';

interface Assembly {
    id: number;
    title: string;
    status: string;
}

interface VoteRecord {
    id: number;
    apartment: {
        id: number;
        number: string;
        tower?: string;
    };
    resident?: {
        id: number;
        name: string;
    };
    delegate?: {
        id: number;
        name: string;
    };
    option: 'yes' | 'no' | 'abstention';
    cast_at: string;
}

interface VoteData {
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
        total_apartments: number;
        quorum_percentage: number;
    };
    vote_records?: VoteRecord[];
    created_at: string;
}

const props = defineProps<{
    assembly: Assembly;
    vote: VoteData;
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
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

const getVoteOptionLabel = (option: string) => {
    const optionMap = {
        yes: 'A Favor',
        no: 'En Contra',
        abstention: 'Abstención',
    };
    return optionMap[option] || option;
};

const getVoteOptionClass = (option: string) => {
    const classMap = {
        yes: 'bg-green-100 text-green-800',
        no: 'bg-red-100 text-red-800',
        abstention: 'bg-gray-100 text-gray-800',
    };
    return classMap[option] || 'bg-gray-100 text-gray-800';
};

// Actions
const activateVote = () => {
    router.post(route('assemblies.votes.activate', [props.assembly.id, props.vote.id]));
};

const closeVote = () => {
    if (confirm('¿Estás seguro de que deseas cerrar esta votación? Esta acción no se puede deshacer.')) {
        router.post(route('assemblies.votes.close', [props.assembly.id, props.vote.id]));
    }
};

const deleteVote = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta votación? Esta acción no se puede deshacer.')) {
        router.delete(route('assemblies.votes.destroy', [props.assembly.id, props.vote.id]));
    }
};

const exportResults = () => {
    window.location.href = `/assemblies/${props.assembly.id}/votes/${props.vote.id}/export`;
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
    { title: 'Votaciones', href: `/assemblies/${props.assembly.id}/votes` },
    { title: props.vote.title, href: `/assemblies/${props.assembly.id}/votes/${props.vote.id}` },
];
</script>

<template>
    <Head :title="vote.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ vote.title }}</h1>
                        <Badge :class="getStatusBadge(vote.status).class">
                            {{ getStatusBadge(vote.status).text }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-4 text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <component :is="getStatusIcon(vote.status)" class="h-4 w-4" />
                            <span>{{ getVoteTypeLabel(vote.type) }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Calendar class="h-4 w-4" />
                            <span>Creada: {{ formatDate(vote.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="`/assemblies/${assembly.id}/votes`">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>

                    <!-- Action buttons based on status -->
                    <template v-if="vote.status === 'draft'">
                        <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}/edit`">
                            <Button variant="outline" class="gap-2">
                                <Edit class="h-4 w-4" />
                                Editar
                            </Button>
                        </Link>
                        <Button @click="activateVote" class="gap-2">
                            <Play class="h-4 w-4" />
                            Activar Votación
                        </Button>
                    </template>

                    <template v-else-if="vote.status === 'active'">
                        <Button @click="closeVote" variant="outline" class="gap-2">
                            <Pause class="h-4 w-4" />
                            Cerrar Votación
                        </Button>
                    </template>

                    <template v-else-if="vote.status === 'closed'">
                        <Button @click="exportResults" variant="outline" class="gap-2">
                            <Download class="h-4 w-4" />
                            Exportar Resultados
                        </Button>
                    </template>
                </div>
            </div>

            <!-- Main content grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Descripción</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ vote.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Results -->
                    <Card v-if="vote.status === 'active' || vote.status === 'closed'">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Vote class="h-5 w-5" />
                                Resultados {{ vote.status === 'active' ? 'Parciales' : 'Finales' }}
                            </CardTitle>
                            <CardDescription>
                                {{ vote.status === 'active' ? 'Resultados en tiempo real' : 'Resultados oficiales de la votación' }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Overall Results -->
                            <div v-if="vote.results" class="text-center p-6 rounded-lg bg-muted/50">
                                <div class="text-4xl font-bold mb-2" 
                                     :class="vote.results.approved ? 'text-green-600' : 'text-red-600'">
                                    {{ vote.results.approved ? 'APROBADA' : 'RECHAZADA' }}
                                </div>
                                <div class="text-lg text-muted-foreground">
                                    {{ vote.results.percentage.toFixed(1) }}% de aprobación
                                </div>
                                <div class="text-sm text-muted-foreground mt-1">
                                    {{ vote.results.total_participants }} de {{ vote.results.total_apartments }} apartamentos participaron
                                    ({{ vote.results.quorum_percentage.toFixed(1) }}% de participación)
                                </div>
                            </div>

                            <!-- Vote Breakdown -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center p-4 rounded-lg bg-green-50 border border-green-200">
                                    <div class="text-3xl font-bold text-green-800">{{ vote.yes_votes }}</div>
                                    <div class="text-sm text-green-600">A Favor</div>
                                    <div class="text-xs text-green-500">
                                        {{ vote.total_votes > 0 ? ((vote.yes_votes / vote.total_votes) * 100).toFixed(1) : 0 }}%
                                    </div>
                                </div>
                                
                                <div class="text-center p-4 rounded-lg bg-red-50 border border-red-200">
                                    <div class="text-3xl font-bold text-red-800">{{ vote.no_votes }}</div>
                                    <div class="text-sm text-red-600">En Contra</div>
                                    <div class="text-xs text-red-500">
                                        {{ vote.total_votes > 0 ? ((vote.no_votes / vote.total_votes) * 100).toFixed(1) : 0 }}%
                                    </div>
                                </div>
                                
                                <div class="text-center p-4 rounded-lg bg-gray-50 border border-gray-200">
                                    <div class="text-3xl font-bold text-gray-800">{{ vote.abstentions }}</div>
                                    <div class="text-sm text-gray-600">Abstenciones</div>
                                    <div class="text-xs text-gray-500">
                                        {{ vote.total_votes > 0 ? ((vote.abstentions / vote.total_votes) * 100).toFixed(1) : 0 }}%
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Aprobación</span>
                                    <span>{{ vote.total_votes > 0 ? ((vote.yes_votes / vote.total_votes) * 100).toFixed(1) : 0 }}%</span>
                                </div>
                                <Progress 
                                    :value="vote.total_votes > 0 ? (vote.yes_votes / vote.total_votes) * 100 : 0" 
                                    class="h-3"
                                />
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>Requiere {{ vote.required_percentage }}% para aprobar</span>
                                    <span>{{ vote.total_votes }} votos totales</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Vote Records -->
                    <Card v-if="vote.vote_records && vote.vote_records.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Registro de Votos
                            </CardTitle>
                            <CardDescription>
                                Detalle de cada voto emitido
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                <div 
                                    v-for="record in vote.vote_records" 
                                    :key="record.id"
                                    class="flex items-center justify-between p-3 rounded-lg border"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="font-medium">
                                            Apt {{ record.apartment.number }}
                                            <span v-if="record.apartment.tower">- {{ record.apartment.tower }}</span>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ record.resident?.name || record.delegate?.name }}
                                            <span v-if="record.delegate" class="text-xs">(Delegado)</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <Badge :class="getVoteOptionClass(record.option)">
                                            {{ getVoteOptionLabel(record.option) }}
                                        </Badge>
                                        <div class="text-xs text-muted-foreground">
                                            {{ formatDate(record.cast_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Vote Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de la Votación</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <Vote class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Tipo</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ getVoteTypeLabel(vote.type) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <CheckCircle class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Requerimiento</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ vote.required_percentage }}% para aprobar
                                        </p>
                                    </div>
                                </div>

                                <div v-if="vote.started_at" class="flex items-center gap-2">
                                    <Play class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Iniciada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(vote.started_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="vote.ended_at" class="flex items-center gap-2">
                                    <CheckCircle class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Finalizada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(vote.ended_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="vote.duration_minutes" class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Duración</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDuration(vote.duration_minutes) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Stats -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Estadísticas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Total de Votos</span>
                                <span class="font-medium">{{ vote.total_votes }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm">A Favor</span>
                                <span class="font-medium text-green-600">{{ vote.yes_votes }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm">En Contra</span>
                                <span class="font-medium text-red-600">{{ vote.no_votes }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Abstenciones</span>
                                <span class="font-medium text-gray-600">{{ vote.abstentions }}</span>
                            </div>

                            <Separator />

                            <div v-if="vote.results" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm">Participación</span>
                                    <span class="font-medium">{{ vote.results.quorum_percentage.toFixed(1) }}%</span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-sm">Aprobación</span>
                                    <span class="font-medium">{{ vote.results.percentage.toFixed(1) }}%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <template v-if="vote.status === 'draft'">
                                <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}/edit`" class="block">
                                    <Button variant="outline" class="w-full justify-start gap-2">
                                        <Edit class="h-4 w-4" />
                                        Editar Votación
                                    </Button>
                                </Link>
                                
                                <Button @click="deleteVote" variant="destructive" class="w-full justify-start gap-2">
                                    <X class="h-4 w-4" />
                                    Eliminar Votación
                                </Button>
                            </template>

                            <template v-if="vote.status === 'closed'">
                                <Button @click="exportResults" variant="outline" class="w-full justify-start gap-2">
                                    <Download class="h-4 w-4" />
                                    Exportar Resultados
                                </Button>
                            </template>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>