<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Edit, Trash2, ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { computed } from 'vue';

interface Student {
    id: number;
    student_code: string;
    first_name: string;
    last_name: string;
    document_id: string;
    gender: string;
    birth_date: string;
    personal_email: string;
    institutional_email: string;
    phone: string;
    group: string;
    program_id: number;
    current_semester_id: number;
    credits_completed: number;
    total_credits: number;
    progress_rate: number;
    dpto: string;
    city: string;
    address: string;
    initial_status: string;
    country: string;
    created_at: string;
    updated_at: string;
    program?: {
        id: number;
        name: string;
    };
    current_semester?: {
        id: number;
        name: string;
        number: number;
    };
}

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const breadcrumbItems = computed(() => [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Estudiantes',
        href: '/students',
    },
    {
        title: props.student.first_name + ' ' + props.student.last_name,
        href: `/students/${props.student.id}`,
    },
]);

const fullName = computed(() => `${props.student.first_name} ${props.student.last_name}`);

const genderLabel = computed(() => {
    switch (props.student.gender) {
        case 'M': return 'Masculino';
        case 'F': return 'Femenino';
        case 'O': return 'Otro';
        default: return props.student.gender;
    }
});

const statusLabel = computed(() => {
    switch (props.student.initial_status) {
        case 'active': return 'Activo';
        case 'inactive': return 'Inactivo';
        case 'graduated': return 'Graduado';
        case 'suspended': return 'Suspendido';
        default: return props.student.initial_status;
    }
});

const statusVariant = computed(() => {
    switch (props.student.initial_status) {
        case 'active': return 'default';
        case 'inactive': return 'secondary';
        case 'graduated': return 'outline';
        case 'suspended': return 'destructive';
        default: return 'secondary';
    }
});

const progressPercentage = computed(() => {
    if (props.student.total_credits === 0) return 0;
    return Math.round((props.student.credits_completed / props.student.total_credits) * 100);
});

const editStudent = () => {
    router.visit(`/students/${props.student.id}/edit`);
};

const deleteStudent = () => {
    if (confirm('¿Está seguro que desea eliminar este estudiante?')) {
        router.delete(`/students/${props.student.id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto w-full max-w-6xl">
            <Head :title="`${fullName} - Detalles del Estudiante`" />
            
            <div class="flex items-center justify-between">
                <HeadingSmall 
                    :title="fullName" 
                    :description="`Código: ${props.student.student_code}`" 
                />
                <div class="flex items-center gap-2">
                    <Badge :variant="statusVariant">
                        {{ statusLabel }}
                    </Badge>
                    <Button variant="outline" size="sm" @click="router.visit('/students')">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </Button>
                    <Button variant="outline" size="sm" @click="editStudent">
                        <Edit class="w-4 h-4 mr-2" />
                        Editar
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteStudent">
                        <Trash2 class="w-4 h-4 mr-2" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información Personal -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Personal</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Nombres</p>
                                <p class="text-sm">{{ props.student.first_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Apellidos</p>
                                <p class="text-sm">{{ props.student.last_name }}</p>
                            </div>
                        </div>
                        
                        <Separator />
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Documento</p>
                                <p class="text-sm">{{ props.student.document_id }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Género</p>
                                <p class="text-sm">{{ genderLabel }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Fecha de Nacimiento</p>
                            <p class="text-sm">{{ new Date(props.student.birth_date).toLocaleDateString() }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información de Contacto -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información de Contacto</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Email Personal</p>
                            <p class="text-sm">{{ props.student.personal_email }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Email Institucional</p>
                            <p class="text-sm">{{ props.student.institutional_email }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Teléfono</p>
                            <p class="text-sm">{{ props.student.phone }}</p>
                        </div>
                        
                        <Separator />
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Dirección</p>
                            <p class="text-sm">{{ props.student.address }}</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Ciudad</p>
                                <p class="text-sm">{{ props.student.city }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Departamento</p>
                                <p class="text-sm">{{ props.student.dpto }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">País</p>
                            <p class="text-sm">{{ props.student.country }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información Académica -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Académica</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Programa</p>
                            <p class="text-sm">{{ props.student.program?.name || 'No asignado' }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Semestre Actual</p>
                            <p class="text-sm">{{ props.student.current_semester?.name || 'No asignado' }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Grupo</p>
                            <p class="text-sm">{{ props.student.group }}</p>
                        </div>
                        
                        <Separator />
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Créditos Completados</p>
                                <p class="text-sm">{{ props.student.credits_completed }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Créditos</p>
                                <p class="text-sm">{{ props.student.total_credits }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Progreso</p>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                        :style="{ width: `${progressPercentage}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm text-muted-foreground">{{ progressPercentage }}%</span>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Tasa de Progreso</p>
                            <p class="text-sm">{{ props.student.progress_rate }}%</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Información Adicional -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Sistema</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Fecha de Creación</p>
                            <p class="text-sm">{{ new Date(props.student.created_at).toLocaleDateString() }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Última Actualización</p>
                            <p class="text-sm">{{ new Date(props.student.updated_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>