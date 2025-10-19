<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { AlertCircle, Check, Clock, UserCheck, Users, UserX, Wifi } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Assembly {
    id: number;
    title: string;
    status: 'scheduled' | 'in_progress' | 'closed' | 'cancelled';
    required_quorum_percentage: number;
    quorum_status: {
        has_quorum: boolean;
        quorum_percentage: number;
    };
}

interface AttendanceStatus {
    status: 'not_registered' | 'present' | 'absent' | 'delegated';
    registered_at?: string;
    can_register: boolean;
    is_online: boolean;
    delegate_info?: {
        name: string;
        apartment: string;
    };
}

const props = defineProps<{
    assembly: Assembly;
    attendanceStatus: AttendanceStatus;
}>();

const emit = defineEmits<{
    attendanceRegistered: [status: string];
}>();

const isSubmitting = ref(false);

// Toast notifications
const { success, error: showError } = useToast();

const canRegisterAttendance = computed(() => {
    return props.assembly.status === 'in_progress' && props.attendanceStatus.can_register && props.attendanceStatus.status === 'not_registered';
});

const statusConfig = computed(() => {
    const configs = {
        not_registered: {
            text: 'Sin registrar',
            class: 'bg-yellow-100 text-yellow-800',
            icon: Clock,
            description: 'Tu asistencia no ha sido registrada',
        },
        present: {
            text: 'Presente',
            class: 'bg-green-100 text-green-800',
            icon: UserCheck,
            description: 'Asistencia confirmada',
        },
        absent: {
            text: 'Ausente',
            class: 'bg-red-100 text-red-800',
            icon: UserX,
            description: 'Marcado como ausente',
        },
        delegated: {
            text: 'Delegado',
            class: 'bg-blue-100 text-blue-800',
            icon: Users,
            description: 'Voto delegado a otro residente',
        },
    };
    return configs[props.attendanceStatus.status] || configs.not_registered;
});

const registerAttendance = async () => {
    isSubmitting.value = true;
    try {
        const response = await fetch(`/api/assemblies/${props.assembly.id}/attendance/self-register`, {
            method: 'POST',
            credentials: 'same-origin', // Include cookies for Sanctum auth
            headers: {
                Accept: 'application/json',
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

        success('Su asistencia ha sido confirmada exitosamente.', '¡Asistencia registrada!');

        emit('attendanceRegistered', 'present');
    } catch (error) {
        console.error('Error registering attendance:', error);

        showError(
            error instanceof Error
                ? error.message
                : 'No se pudo registrar su asistencia. Por favor, inténtelo nuevamente o contacte al administrador.',
            'Error al registrar asistencia',
        );
    } finally {
        isSubmitting.value = false;
    }
};

const formatTime = (dateString?: string) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Card
        class="border-2"
        :class="{
            'border-green-200 bg-green-50': attendanceStatus.status === 'present',
            'border-yellow-200 bg-yellow-50': attendanceStatus.status === 'not_registered' && assembly.status === 'in_progress',
            'border-red-200 bg-red-50': attendanceStatus.status === 'absent',
            'border-blue-200 bg-blue-50': attendanceStatus.status === 'delegated',
            'border-gray-200': assembly.status !== 'in_progress',
        }"
    >
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <component :is="statusConfig.icon" class="h-5 w-5" />
                    <CardTitle class="text-lg">Mi Asistencia</CardTitle>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Online Status -->
                    <div class="flex items-center gap-1">
                        <Wifi v-if="attendanceStatus.is_online" class="h-4 w-4 text-green-500" />
                        <span class="text-xs" :class="attendanceStatus.is_online ? 'text-green-600' : 'text-gray-500'">
                            {{ attendanceStatus.is_online ? 'En línea' : 'Desconectado' }}
                        </span>
                    </div>

                    <!-- Status Badge -->
                    <Badge :class="statusConfig.class">
                        {{ statusConfig.text }}
                    </Badge>
                </div>
            </div>
            <CardDescription>
                {{ statusConfig.description }}
                <span v-if="attendanceStatus.registered_at" class="mt-1 block text-xs">
                    Registrado: {{ formatTime(attendanceStatus.registered_at) }}
                </span>
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Delegate Information -->
            <div
                v-if="attendanceStatus.status === 'delegated' && attendanceStatus.delegate_info"
                class="rounded-lg border border-blue-200 bg-blue-100 p-3"
            >
                <div class="flex items-center gap-2 text-blue-800">
                    <Users class="h-4 w-4" />
                    <span class="font-medium">Voto Delegado</span>
                </div>
                <p class="mt-1 text-sm text-blue-700">
                    Tu voto ha sido delegado a <strong>{{ attendanceStatus.delegate_info.name }}</strong> ({{
                        attendanceStatus.delegate_info.apartment
                    }})
                </p>
            </div>

            <!-- Registration Actions -->
            <div v-if="assembly.status === 'in_progress'">
                <!-- Can Register -->
                <div v-if="canRegisterAttendance" class="space-y-3">
                    <div class="rounded-lg border border-green-200 bg-green-100 p-3">
                        <div class="mb-2 flex items-center gap-2 text-green-800">
                            <Check class="h-4 w-4" />
                            <span class="font-medium">Asamblea en Progreso</span>
                        </div>
                        <p class="text-sm text-green-700">La asamblea está activa y puedes registrar tu asistencia ahora.</p>
                        <div v-if="attendanceStatus.is_online" class="mt-1 text-xs text-green-600">
                            ✓ Detectado como conectado - Registro automático disponible
                        </div>
                    </div>

                    <Button @click="registerAttendance" :disabled="isSubmitting" class="w-full gap-2 bg-green-600 hover:bg-green-700" size="lg">
                        <UserCheck class="h-5 w-5" />
                        {{ isSubmitting ? 'Registrando...' : 'Confirmar Mi Asistencia' }}
                    </Button>
                </div>

                <!-- Already Registered -->
                <div v-else-if="attendanceStatus.status !== 'not_registered'" class="p-4 text-center">
                    <component
                        :is="statusConfig.icon"
                        class="mx-auto mb-2 h-8 w-8"
                        :class="{
                            'text-green-500': attendanceStatus.status === 'present',
                            'text-red-500': attendanceStatus.status === 'absent',
                            'text-blue-500': attendanceStatus.status === 'delegated',
                        }"
                    />
                    <p class="font-medium">{{ statusConfig.text }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ statusConfig.description }}</p>
                </div>

                <!-- Cannot Register -->
                <div v-else class="rounded-lg border border-amber-200 bg-amber-100 p-3">
                    <div class="flex items-center gap-2 text-amber-800">
                        <AlertCircle class="h-4 w-4" />
                        <span class="font-medium">No Habilitado para Registrar</span>
                    </div>
                    <p class="mt-1 text-sm text-amber-700">
                        El registro de asistencia no está disponible para tu apartamento en este momento. Contacta a la administración si necesitas
                        asistencia.
                    </p>
                </div>
            </div>

            <!-- Assembly Not Active -->
            <div v-else class="p-4 text-center text-gray-600">
                <Clock class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                <p class="font-medium">Asamblea No Activa</p>
                <p class="mt-1 text-sm">
                    <span v-if="assembly.status === 'scheduled'"> La asamblea aún no ha comenzado </span>
                    <span v-else-if="assembly.status === 'closed'"> La asamblea ha finalizado </span>
                    <span v-else-if="assembly.status === 'cancelled'"> La asamblea fue cancelada </span>
                </p>
            </div>

            <!-- Quorum Status Info -->
            <div class="border-t border-gray-200 pt-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Estado del Quórum:</span>
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ assembly.quorum_status.quorum_percentage.toFixed(1) }}%</span>
                        <Badge
                            size="sm"
                            :variant="assembly.quorum_status.has_quorum ? 'default' : 'secondary'"
                            :class="assembly.quorum_status.has_quorum ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ assembly.quorum_status.has_quorum ? 'Válido' : 'Falta' }}
                        </Badge>
                    </div>
                </div>
                <div class="mt-1 text-xs text-gray-500">Mínimo requerido: {{ assembly.required_quorum_percentage }}%</div>
            </div>
        </CardContent>
    </Card>
</template>
