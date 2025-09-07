<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Progress } from '@/components/ui/progress';
import AttendanceRegistration from '@/components/assemblies/AttendanceRegistration.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Building, 
    Calendar, 
    Clock, 
    Download, 
    Edit, 
    Eye, 
    FileText, 
    MapPin, 
    Play, 
    Square, 
    StopCircle, 
    User, 
    Users, 
    Vote, 
    X 
} from 'lucide-vue-next';

interface Assembly {
    id: number;
    title: string;
    description: string;
    type: string;
    status: 'scheduled' | 'in_progress' | 'closed' | 'cancelled';
    scheduled_at: string;
    started_at?: string;
    ended_at?: string;
    required_quorum_percentage: number;
    duration_minutes?: number;
    is_active: boolean;
    can_vote: boolean;
    quorum_status: {
        total_apartments: number;
        participating_apartments: number;
        quorum_percentage: number;
        required_quorum_percentage: number;
        has_quorum: boolean;
    };
    status_badge: {
        text: string;
        class: string;
    };
    documents?: any[];
    meeting_notes?: string;
    metadata?: {
        location?: string;
        agenda?: string[];
        organizer?: string;
        max_duration_hours?: number;
        notification_settings?: {
            email_reminder?: boolean;
            whatsapp_reminder?: boolean;
            reminder_hours_before?: number;
        };
    };
    votes?: any[];
    delegates?: any[];
    creator?: {
        id: number;
        name: string;
    };
    residents?: any[];
    attendance_stats?: {
        total_apartments: number;
        registered_apartments: number;
        present_apartments: number;
        absent_apartments: number;
        delegated_apartments: number;
        online_residents: number;
        total_residents: number;
        quorum_percentage: number;
        required_quorum_percentage: number;
        has_quorum: boolean;
    };
    can_manage_attendance?: boolean;
}

const props = defineProps<{
    assembly: Assembly;
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

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'scheduled':
            return Calendar;
        case 'in_progress':
            return Play;
        case 'closed':
            return StopCircle;
        case 'cancelled':
            return X;
        default:
            return Calendar;
    }
};

const getTypeLabel = (type: string) => {
    const typeMap = {
        ordinary: 'Ordinaria',
        extraordinary: 'Extraordinaria',
        budget: 'Presupuestal',
        other: 'Otra',
    };
    return typeMap[type] || type;
};

// Actions for assembly
const startAssembly = () => {
    router.post(route('assemblies.start', props.assembly.id));
};

const closeAssembly = () => {
    router.post(route('assemblies.close', props.assembly.id));
};

const cancelAssembly = () => {
    if (confirm('¿Estás seguro de que deseas cancelar esta asamblea?')) {
        router.post(route('assemblies.cancel', props.assembly.id));
    }
};

const deleteAssembly = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta asamblea? Esta acción no se puede deshacer.')) {
        router.delete(route('assemblies.destroy', props.assembly.id));
    }
};

// Handle attendance updates
const handleAttendanceUpdated = (newStats: any) => {
    // Update local assembly data with new stats
    if (props.assembly.attendance_stats) {
        Object.assign(props.assembly.attendance_stats, newStats);
    }
    // Also update quorum_status to keep it in sync
    props.assembly.quorum_status.participating_apartments = newStats.present_apartments;
    props.assembly.quorum_status.quorum_percentage = newStats.quorum_percentage;
    props.assembly.quorum_status.has_quorum = newStats.has_quorum;
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
];
</script>

<template>
    <Head :title="assembly.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ assembly.title }}</h1>
                        <Badge :class="assembly.status_badge.class">
                            {{ assembly.status_badge.text }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-4 text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <component :is="getStatusIcon(assembly.status)" class="h-4 w-4" />
                            <span>{{ getTypeLabel(assembly.type) }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Calendar class="h-4 w-4" />
                            <span>{{ formatDate(assembly.scheduled_at) }}</span>
                        </div>
                        <div v-if="assembly.metadata?.location" class="flex items-center gap-1">
                            <MapPin class="h-4 w-4" />
                            <span>{{ assembly.metadata.location }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/assemblies">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>

                    <!-- Action buttons based on status -->
                    <template v-if="assembly.status === 'scheduled'">
                        <Link :href="`/assemblies/${assembly.id}/edit`">
                            <Button variant="outline" class="gap-2">
                                <Edit class="h-4 w-4" />
                                Editar
                            </Button>
                        </Link>
                        <Button @click="startAssembly" class="gap-2">
                            <Play class="h-4 w-4" />
                            Iniciar Asamblea
                        </Button>
                        <Button @click="cancelAssembly" variant="destructive" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancelar
                        </Button>
                    </template>

                    <template v-else-if="assembly.status === 'in_progress'">
                        <Button @click="closeAssembly" variant="outline" class="gap-2">
                            <StopCircle class="h-4 w-4" />
                            Cerrar Asamblea
                        </Button>
                    </template>

                    <template v-else-if="assembly.status === 'closed'">
                        <Button variant="outline" class="gap-2">
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
                            <p class="text-gray-700 leading-relaxed">{{ assembly.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Attendance Registration -->
                    <AttendanceRegistration
                        v-if="assembly.residents && assembly.attendance_stats"
                        :assembly-id="assembly.id"
                        :residents="assembly.residents"
                        :stats="assembly.attendance_stats"
                        :can-manage-attendance="assembly.can_manage_attendance || false"
                        :is-active="assembly.status === 'in_progress'"
                        @attendance-updated="handleAttendanceUpdated"
                    />

                    <!-- Fallback Quorum Status (when attendance data not available) -->
                    <Card v-else>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Estado del Quórum
                            </CardTitle>
                            <CardDescription>
                                Participación actual de apartamentos en la asamblea
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <p class="text-2xl font-bold">
                                        {{ assembly.quorum_status.quorum_percentage.toFixed(1) }}%
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ assembly.quorum_status.participating_apartments }} de {{ assembly.quorum_status.total_apartments }} apartamentos
                                    </p>
                                </div>
                                <Badge 
                                    :variant="assembly.quorum_status.has_quorum ? 'default' : 'secondary'"
                                    :class="assembly.quorum_status.has_quorum ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                >
                                    {{ assembly.quorum_status.has_quorum ? 'Quórum Alcanzado' : 'Sin Quórum' }}
                                </Badge>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Participación</span>
                                    <span>{{ assembly.quorum_status.quorum_percentage.toFixed(1) }}%</span>
                                </div>
                                <Progress 
                                    :value="assembly.quorum_status.quorum_percentage" 
                                    :class="assembly.quorum_status.has_quorum ? 'text-green-600' : 'text-red-600'"
                                />
                                <div class="flex justify-between text-sm text-muted-foreground">
                                    <span>Mínimo requerido: {{ assembly.required_quorum_percentage }}%</span>
                                    <span>{{ assembly.quorum_status.has_quorum ? 'Válida' : 'Falta quórum' }}</span>
                                </div>
                            </div>
                            
                            <!-- Notification about enhanced attendance feature -->
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-sm text-blue-800">
                                    💡 <strong>Funcionalidad de Asistencia Mejorada:</strong> Para usar el registro automático de asistencia 
                                    y detección de usuarios conectados, asegúrate de que el backend esté configurado para proporcionar 
                                    datos de residentes y estadísticas de asistencia.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Agenda -->
                    <Card v-if="assembly.metadata?.agenda?.length">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Agenda / Orden del Día
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ol class="space-y-3">
                                <li 
                                    v-for="(item, index) in assembly.metadata.agenda" 
                                    :key="index"
                                    class="flex gap-3 p-3 rounded-lg bg-muted/50"
                                >
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-medium">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="flex-1 text-sm">{{ item }}</span>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>

                    <!-- Votes -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2">
                                    <Vote class="h-5 w-5" />
                                    Votaciones
                                </CardTitle>
                                <Link 
                                    :href="`/assemblies/${assembly.id}/votes/create`"
                                    v-if="assembly.status === 'in_progress'"
                                >
                                    <Button size="sm">Nueva Votación</Button>
                                </Link>
                            </div>
                            <CardDescription>
                                Temas sujetos a votación en esta asamblea
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="assembly.votes && assembly.votes.length > 0" class="space-y-4">
                                <div 
                                    v-for="vote in assembly.votes" 
                                    :key="vote.id"
                                    class="flex items-center justify-between p-4 border rounded-lg"
                                >
                                    <div>
                                        <h4 class="font-medium">{{ vote.title }}</h4>
                                        <p class="text-sm text-muted-foreground">{{ vote.description }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="vote.status === 'active' ? 'default' : 'secondary'">
                                            {{ vote.status }}
                                        </Badge>
                                        <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}`">
                                            <Button size="sm" variant="outline">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <Vote class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No hay votaciones programadas aún</p>
                                <Link 
                                    :href="`/assemblies/${assembly.id}/votes/create`"
                                    v-if="assembly.status === 'in_progress'"
                                >
                                    <Button class="mt-2" size="sm">Crear Primera Votación</Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Meeting Notes -->
                    <Card v-if="assembly.meeting_notes">
                        <CardHeader>
                            <CardTitle>Notas de la Asamblea</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="prose prose-sm max-w-none">
                                <p class="whitespace-pre-wrap">{{ assembly.meeting_notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Documents -->
                    <Card v-if="assembly.documents && assembly.documents.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Documentos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="document in assembly.documents" 
                                    :key="document.id"
                                    class="flex items-center justify-between p-3 border rounded-lg"
                                >
                                    <div class="flex items-center gap-3">
                                        <FileText class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium text-sm">{{ document.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ document.size }}</p>
                                        </div>
                                    </div>
                                    <Button size="sm" variant="outline">
                                        <Download class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Assembly Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Programada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(assembly.scheduled_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="assembly.started_at" class="flex items-center gap-2">
                                    <Play class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Iniciada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(assembly.started_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="assembly.ended_at" class="flex items-center gap-2">
                                    <StopCircle class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Finalizada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(assembly.ended_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="assembly.duration_minutes" class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Duración</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDuration(assembly.duration_minutes) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="assembly.metadata?.organizer" class="flex items-center gap-2">
                                    <User class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Organizador</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ assembly.metadata.organizer }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="assembly.creator" class="flex items-center gap-2">
                                    <User class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Creado por</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ assembly.creator.name }}
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
                                <span class="text-sm">Total Apartamentos</span>
                                <span class="font-medium">{{ assembly.quorum_status.total_apartments }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Participando</span>
                                <span class="font-medium">{{ assembly.quorum_status.participating_apartments }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Votaciones</span>
                                <span class="font-medium">{{ assembly.votes?.length || 0 }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Delegados</span>
                                <span class="font-medium">{{ assembly.delegates?.length || 0 }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Link :href="`/assemblies/${assembly.id}/votes`" class="block">
                                <Button variant="outline" class="w-full justify-start gap-2">
                                    <Vote class="h-4 w-4" />
                                    Ver Votaciones
                                </Button>
                            </Link>
                            
                            <Link :href="`/assemblies/${assembly.id}/delegates`" class="block">
                                <Button variant="outline" class="w-full justify-start gap-2">
                                    <Users class="h-4 w-4" />
                                    Gestionar Delegados
                                </Button>
                            </Link>
                            
                            <Button variant="outline" class="w-full justify-start gap-2">
                                <Download class="h-4 w-4" />
                                Exportar Datos
                            </Button>

                            <Separator class="my-4" />

                            <template v-if="assembly.status === 'scheduled'">
                                <Button @click="deleteAssembly" variant="destructive" class="w-full justify-start gap-2">
                                    <X class="h-4 w-4" />
                                    Eliminar Asamblea
                                </Button>
                            </template>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>