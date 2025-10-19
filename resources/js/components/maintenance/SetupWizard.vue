<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, CheckCircle, Lightbulb, Settings, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    hasCategoriesConfigured: boolean;
    hasStaffConfigured?: boolean;
    showStaffStep?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    hasStaffConfigured: true,
    showStaffStep: false,
});

interface SetupStep {
    id: string;
    title: string;
    description: string;
    icon: any;
    completed: boolean;
    action: () => void;
    actionLabel: string;
}

const steps = computed<SetupStep[]>(() => {
    const baseSteps: SetupStep[] = [
        {
            id: 'categories',
            title: 'Crear Categorías de Mantenimiento',
            description:
                'Primero necesita configurar las categorías de mantenimiento (Ej: Plomería, Electricidad, Pintura, etc.). Estas categorías le ayudarán a organizar y clasificar las solicitudes.',
            icon: Settings,
            completed: props.hasCategoriesConfigured,
            action: () => router.visit(route('maintenance-categories.index')),
            actionLabel: 'Ir a Categorías',
        },
    ];

    if (props.showStaffStep) {
        baseSteps.push({
            id: 'staff',
            title: 'Configurar Personal de Mantenimiento (Opcional)',
            description: 'Configure el personal de mantenimiento disponible para asignar solicitudes. Este paso es opcional pero recomendado.',
            icon: Wrench,
            completed: props.hasStaffConfigured ?? false,
            action: () => router.visit(route('maintenance-staff.index')),
            actionLabel: 'Ir a Personal',
        });
    }

    return baseSteps;
});

const allStepsCompleted = computed(() => {
    return steps.value.every((step) => step.completed);
});

const nextIncompleteStep = computed(() => {
    return steps.value.find((step) => !step.completed);
});

const canProceed = computed(() => {
    // At minimum, categories must be configured
    return props.hasCategoriesConfigured;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Alert Banner -->
        <Card class="border-yellow-300 bg-yellow-50">
            <CardContent class="p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <AlertTriangle class="h-8 w-8 text-yellow-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-yellow-900">Configuración Inicial Requerida</h3>
                        <p class="mt-2 text-sm text-yellow-800">
                            Antes de crear su primera solicitud de mantenimiento, necesita completar algunos pasos de configuración. Esta es una
                            configuración única que facilitará la gestión de todas las solicitudes futuras.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Setup Steps -->
        <div class="space-y-4">
            <div class="mb-4 flex items-center space-x-2">
                <Lightbulb class="h-5 w-5 text-blue-600" />
                <h3 class="text-lg font-semibold text-gray-900">Pasos de Configuración</h3>
            </div>

            <div class="space-y-4">
                <Card
                    v-for="(step, index) in steps"
                    :key="step.id"
                    :class="{
                        'border-green-300 bg-green-50': step.completed,
                        'border-blue-300 bg-blue-50': !step.completed && index === 0,
                        'border-gray-200': !step.completed && index > 0,
                    }"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <div
                                    :class="{
                                        'bg-green-100': step.completed,
                                        'bg-blue-100': !step.completed && index === 0,
                                        'bg-gray-100': !step.completed && index > 0,
                                    }"
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg"
                                >
                                    <CheckCircle v-if="step.completed" class="h-6 w-6 text-green-600" />
                                    <component v-else :is="step.icon" class="h-6 w-6 text-gray-600" />
                                </div>
                                <div>
                                    <CardTitle
                                        class="flex items-center space-x-2"
                                        :class="{
                                            'text-green-900': step.completed,
                                            'text-blue-900': !step.completed && index === 0,
                                            'text-gray-700': !step.completed && index > 0,
                                        }"
                                    >
                                        <span>Paso {{ index + 1 }}: {{ step.title }}</span>
                                    </CardTitle>
                                    <CardDescription
                                        class="mt-2"
                                        :class="{
                                            'text-green-700': step.completed,
                                            'text-blue-800': !step.completed && index === 0,
                                            'text-gray-600': !step.completed && index > 0,
                                        }"
                                    >
                                        {{ step.description }}
                                    </CardDescription>
                                </div>
                            </div>
                            <div v-if="step.completed" class="flex-shrink-0">
                                <CheckCircle class="h-6 w-6 text-green-600" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="!step.completed" class="pt-0">
                        <Button @click="step.action" :variant="index === 0 ? 'default' : 'outline'" class="w-full">
                            {{ step.actionLabel }}
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Quick Setup Option -->
        <Card v-if="!allStepsCompleted && !hasCategoriesConfigured" class="border-blue-300 bg-blue-50">
            <CardContent class="p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <Lightbulb class="h-6 w-6 text-blue-600" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-blue-900">¿Necesita ayuda para comenzar?</h4>
                        <p class="mt-1 text-sm text-blue-800">
                            Puede crear categorías predeterminadas comunes (Plomería, Electricidad, Pintura, etc.) con un solo clic.
                        </p>
                        <Button @click="router.post(route('maintenance-categories.seed'))" variant="outline" class="mt-4">
                            Crear Categorías Predeterminadas
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Success State -->
        <Card v-if="canProceed" class="border-green-300 bg-green-50">
            <CardContent class="p-6">
                <div class="flex items-center space-x-4">
                    <CheckCircle class="h-8 w-8 flex-shrink-0 text-green-600" />
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-green-900">¡Listo para crear solicitudes!</h3>
                        <p class="mt-1 text-sm text-green-800">
                            Ha completado la configuración mínima requerida. Ya puede crear solicitudes de mantenimiento.
                        </p>
                    </div>
                    <Button @click="router.visit(route('maintenance-requests.create'))" class="flex-shrink-0">
                        Crear Solicitud
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
