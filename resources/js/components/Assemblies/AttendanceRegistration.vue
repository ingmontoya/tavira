<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import { 
    Check, 
    Clock, 
    Eye, 
    Home, 
    RefreshCw, 
    Users, 
    UserCheck, 
    UserX,
    Wifi,
    WifiOff
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Resident {
    id: number;
    name: string;
    email: string;
    apartment: {
        id: number;
        number: string;
        type: string;
    };
    is_online: boolean;
    last_seen?: string;
    attendance_status: 'not_registered' | 'present' | 'absent' | 'delegated';
    registered_at?: string;
    delegate_to?: {
        id: number;
        name: string;
        apartment: string;
    };
}

interface AttendanceStats {
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
}

const props = defineProps<{
    assemblyId: number;
    residents: Resident[];
    stats: AttendanceStats;
    canManageAttendance: boolean;
    isActive: boolean;
}>();

const emit = defineEmits<{
    attendanceUpdated: [stats: AttendanceStats];
}>();

// Real-time updates
const onlineResidents = ref<Resident[]>([...props.residents]);
const isRefreshing = ref(false);
const autoRefresh = ref(true);
let refreshInterval: NodeJS.Timeout | null = null;

// Toast notifications
const { success, error: showError } = useToast();

// Computed values
const sortedResidents = computed(() => {
    return [...onlineResidents.value].sort((a, b) => {
        // Priority: online first, then by attendance status, then by apartment number
        if (a.is_online !== b.is_online) {
            return b.is_online ? 1 : -1;
        }
        if (a.attendance_status !== b.attendance_status) {
            const statusOrder = { 'present': 0, 'delegated': 1, 'not_registered': 2, 'absent': 3 };
            return statusOrder[a.attendance_status] - statusOrder[b.attendance_status];
        }
        return a.apartment.number.localeCompare(b.apartment.number, undefined, { numeric: true });
    });
});

const onlineCount = computed(() => onlineResidents.value.filter(r => r.is_online).length);

// Methods
const refreshAttendance = async () => {
    isRefreshing.value = true;
    try {
        const response = await fetch(`/api/assemblies/${props.assemblyId}/attendance/status`, {
            method: 'GET',
            credentials: 'same-origin', // Include cookies for Sanctum auth
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.message || 'Error desconocido al obtener el estado de asistencia');
        }
        
        onlineResidents.value = data.residents;
        emit('attendanceUpdated', data.stats);
        
    } catch (error) {
        console.error('Error refreshing attendance:', error);
        
        showError(
            error instanceof Error ? error.message : "No se pudo obtener el estado actual de asistencia. Por favor, inténtelo nuevamente.",
            "Error al actualizar asistencia"
        );
    } finally {
        isRefreshing.value = false;
    }
};

const markAttendance = async (residentId: number, status: 'present' | 'absent') => {
    try {
        await router.post(`/assemblies/${props.assemblyId}/attendance`, {
            resident_id: residentId,
            status: status,
        }, {
            onError: (errors) => {
                showError(
                    "No se pudo registrar la asistencia. Por favor, inténtelo nuevamente.",
                    "Error al marcar asistencia"
                );
            },
            onSuccess: () => {
                success(
                    `El residente ha sido marcado como ${status === 'present' ? 'presente' : 'ausente'}.`,
                    "Asistencia registrada"
                );
            }
        });
        await refreshAttendance();
    } catch (error) {
        console.error('Error marking attendance:', error);
        showError(
            "Ocurrió un error inesperado. Por favor, inténtelo nuevamente.",
            "Error al marcar asistencia"
        );
    }
};

const registerSelfAttendance = async () => {
    try {
        const response = await fetch(`/api/assemblies/${props.assemblyId}/attendance/self-register`, {
            method: 'POST',
            credentials: 'same-origin', // Include cookies for Sanctum auth
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.message || 'Error desconocido al registrar asistencia');
        }
        
        success(
            "Su asistencia ha sido registrada exitosamente.",
            "Asistencia registrada"
        );
        
        await refreshAttendance();
        
    } catch (error) {
        console.error('Error registering self attendance:', error);
        
        showError(
            error instanceof Error ? error.message : "No se pudo registrar su asistencia. Por favor, inténtelo nuevamente.",
            "Error al registrar asistencia"
        );
    }
};

const getStatusBadge = (status: string, isOnline: boolean) => {
    const badges = {
        present: { text: 'Presente', class: 'bg-green-100 text-green-800' },
        absent: { text: 'Ausente', class: 'bg-red-100 text-red-800' },
        delegated: { text: 'Delegado', class: 'bg-blue-100 text-blue-800' },
        not_registered: { text: isOnline ? 'En línea' : 'Sin registrar', class: isOnline ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' },
    };
    return badges[status] || badges.not_registered;
};

const formatTime = (dateString: string | undefined) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Lifecycle
onMounted(() => {
    if (autoRefresh.value && props.isActive) {
        refreshInterval = setInterval(refreshAttendance, 30000); // Refresh every 30 seconds
    }
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center">
                        <Users class="h-8 w-8 text-blue-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Apartamentos</p>
                            <p class="text-2xl font-bold">{{ stats.total_apartments }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center">
                        <Wifi class="h-8 w-8 text-green-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Residentes En Línea</p>
                            <p class="text-2xl font-bold">{{ onlineCount }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center">
                        <UserCheck class="h-8 w-8 text-indigo-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Registrados</p>
                            <p class="text-2xl font-bold">{{ stats.registered_apartments }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center">
                        <Check :class="stats.has_quorum ? 'h-8 w-8 text-green-500' : 'h-8 w-8 text-red-500'" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Quórum</p>
                            <p class="text-2xl font-bold">{{ stats.quorum_percentage.toFixed(1) }}%</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Quorum Status -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="h-5 w-5" />
                    Estado del Quórum
                </CardTitle>
                <CardDescription>
                    Seguimiento en tiempo real de la asistencia para verificar quórum
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold">
                            {{ stats.quorum_percentage.toFixed(1) }}%
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ stats.present_apartments }} de {{ stats.total_apartments }} apartamentos presentes
                        </div>
                    </div>
                    <Badge
                        :variant="stats.has_quorum ? 'default' : 'secondary'"
                        :class="stats.has_quorum ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                        {{ stats.has_quorum ? 'Quórum Alcanzado' : 'Sin Quórum' }}
                    </Badge>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>{{ stats.quorum_percentage.toFixed(1) }}%</span>
                        <span>Mínimo: {{ stats.required_quorum_percentage }}%</span>
                    </div>
                    <Progress 
                        :value="stats.quorum_percentage" 
                        :class="stats.has_quorum ? 'text-green-600' : 'text-red-600'"
                        class="h-3"
                    />
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>{{ stats.has_quorum ? 'Asamblea válida' : 'Falta quórum' }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Online Residents Alert -->
        <Card v-if="onlineCount > 0 && isActive" class="border-blue-200 bg-blue-50">
            <CardContent class="p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-blue-100 p-2">
                        <Wifi class="h-5 w-5 text-blue-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-medium text-blue-800">Residentes Conectados Detectados</h3>
                        <p class="text-sm text-blue-600">
                            {{ onlineCount }} residentes están conectados y pueden registrar su asistencia automáticamente.
                        </p>
                    </div>
                    <Button 
                        @click="registerSelfAttendance" 
                        size="sm"
                        class="bg-blue-600 hover:bg-blue-700"
                    >
                        Registrar Mi Asistencia
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Attendance Management -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <UserCheck class="h-5 w-5" />
                            Registro de Asistencia
                        </CardTitle>
                        <CardDescription>
                            Gestión de asistencia por apartamento
                        </CardDescription>
                    </div>
                    <Button 
                        @click="refreshAttendance" 
                        :disabled="isRefreshing"
                        variant="outline" 
                        size="sm"
                        class="gap-2"
                    >
                        <RefreshCw :class="isRefreshing ? 'h-4 w-4 animate-spin' : 'h-4 w-4'" />
                        {{ isRefreshing ? 'Actualizando...' : 'Actualizar' }}
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Apartamento</TableHead>
                                <TableHead>Residente</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Conectado</TableHead>
                                <TableHead>Registrado</TableHead>
                                <TableHead v-if="canManageAttendance">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="resident in sortedResidents" 
                                :key="resident.id"
                                :class="resident.is_online ? 'bg-green-50' : ''"
                            >
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Home class="h-4 w-4 text-gray-400" />
                                        <span class="font-medium">{{ resident.apartment.number }}</span>
                                        <span class="text-xs text-gray-500">{{ resident.apartment.type }}</span>
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <div>
                                        <div class="font-medium">{{ resident.name }}</div>
                                        <div class="text-sm text-gray-500">{{ resident.email }}</div>
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <Badge :class="getStatusBadge(resident.attendance_status, resident.is_online).class">
                                        {{ getStatusBadge(resident.attendance_status, resident.is_online).text }}
                                    </Badge>
                                    <div v-if="resident.delegate_to" class="text-xs text-gray-500 mt-1">
                                        Delegado a: {{ resident.delegate_to.name }} ({{ resident.delegate_to.apartment }})
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <component 
                                            :is="resident.is_online ? Wifi : WifiOff" 
                                            :class="resident.is_online ? 'h-4 w-4 text-green-500' : 'h-4 w-4 text-gray-400'"
                                        />
                                        <span :class="resident.is_online ? 'text-green-600' : 'text-gray-500'" class="text-sm">
                                            {{ resident.is_online ? 'En línea' : 'Desconectado' }}
                                        </span>
                                    </div>
                                    <div v-if="resident.last_seen" class="text-xs text-gray-400">
                                        Último: {{ formatTime(resident.last_seen) }}
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <span class="text-sm">
                                        {{ formatTime(resident.registered_at) }}
                                    </span>
                                </TableCell>
                                
                                <TableCell v-if="canManageAttendance">
                                    <div class="flex items-center gap-1">
                                        <Button 
                                            @click="markAttendance(resident.id, 'present')"
                                            :disabled="resident.attendance_status === 'present'"
                                            size="sm" 
                                            variant="outline"
                                            class="gap-1"
                                        >
                                            <UserCheck class="h-3 w-3" />
                                            Presente
                                        </Button>
                                        <Button 
                                            @click="markAttendance(resident.id, 'absent')"
                                            :disabled="resident.attendance_status === 'absent'"
                                            size="sm" 
                                            variant="outline"
                                            class="gap-1"
                                        >
                                            <UserX class="h-3 w-3" />
                                            Ausente
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                
                <div v-if="sortedResidents.length === 0" class="text-center py-8">
                    <Users class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay residentes</h3>
                    <p class="text-gray-500">No se encontraron residentes para esta asamblea.</p>
                </div>
            </CardContent>
        </Card>

        <!-- Attendance Summary -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Eye class="h-5 w-5" />
                    Resumen de Asistencia
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.present_apartments }}</div>
                        <div class="text-sm text-gray-500">Presentes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ stats.absent_apartments }}</div>
                        <div class="text-sm text-gray-500">Ausentes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.delegated_apartments }}</div>
                        <div class="text-sm text-gray-500">Delegados</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ stats.total_apartments - stats.registered_apartments }}</div>
                        <div class="text-sm text-gray-500">Sin registrar</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>