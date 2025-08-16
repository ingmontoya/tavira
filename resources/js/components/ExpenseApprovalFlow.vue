<script setup lang="ts">
import { computed } from 'vue';
import MermaidFlowchart from './MermaidFlowchart.vue';

interface Expense {
    id: number;
    status: string;
    total_amount: number;
    approved_at?: string;
    council_approved_at?: string;
    paid_at?: string;
    created_at: string;
}

interface Props {
    expense: Expense;
    approvalThreshold?: number;
    councilApprovalRequired?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    approvalThreshold: 4000000,
    councilApprovalRequired: true,
});

const requiresCouncilApproval = computed(() => {
    return props.councilApprovalRequired && props.expense.total_amount >= props.approvalThreshold;
});

const flowchartDefinition = computed(() => {
    const status = props.expense.status;
    const needsCouncil = requiresCouncilApproval.value;

    let definition = `flowchart LR
    A["📝<br/><strong>CREACIÓN</strong><br/>Borrador"]
    B["⏳<br/><strong>PENDIENTE</strong><br/>Aprobación Admin"]
    ${
        needsCouncil
            ? `
    C["🏛️<br/><strong>PENDIENTE</strong><br/>Concejo"]
    `
            : ''
    }
    D["✅<br/><strong>APROBADO</strong><br/>Listo para Pago"]
    E["💰<br/><strong>PAGADO</strong><br/>Proceso Completo"]
    F["❌<br/><strong>RECHAZADO</strong><br/>No Aprobado"]
    G["⏹️<br/><strong>CANCELADO</strong><br/>Proceso Detenido"]
    
    A ===> B
    ${
        needsCouncil
            ? `
    B ===> C
    C ===> D
    `
            : `
    B ===> D
    `
    }
    D ===> E
    
    B -.- F
    ${needsCouncil ? 'C -.- F' : ''}
    A -.- G
    B -.- G
    ${needsCouncil ? 'C -.- G' : ''}
    
    classDef default fill:#f9f9f9,stroke:#333,stroke-width:2px,color:#000
    classDef pathHighlight stroke:#3B82F6,stroke-width:4px
  `;

    // Apply state-specific styling
    switch (status) {
        case 'borrador':
            definition += '\n    class A active';
            break;
        case 'pendiente':
            definition += '\n    class A completed';
            definition += '\n    class B active';
            break;
        case 'pendiente_concejo':
            definition += '\n    class A completed';
            definition += '\n    class B completed';
            if (needsCouncil) {
                definition += '\n    class C active';
            }
            break;
        case 'aprobado':
            definition += '\n    class A completed';
            definition += '\n    class B completed';
            if (needsCouncil) {
                definition += '\n    class C completed';
            }
            definition += '\n    class D active';
            break;
        case 'pagado':
            definition += '\n    class A completed';
            definition += '\n    class B completed';
            if (needsCouncil) {
                definition += '\n    class C completed';
            }
            definition += '\n    class D completed';
            definition += '\n    class E active';
            break;
        case 'rechazado':
            definition += '\n    class A completed';
            if (props.expense.approved_at) {
                definition += '\n    class B completed';
                if (needsCouncil && props.expense.council_approved_at) {
                    definition += '\n    class C completed';
                } else if (needsCouncil) {
                    definition += '\n    class C active';
                }
            } else {
                definition += '\n    class B active';
            }
            definition += '\n    class F rejected';
            break;
        case 'cancelado':
            definition += '\n    class A completed';
            if (props.expense.approved_at) {
                definition += '\n    class B completed';
                if (needsCouncil && props.expense.council_approved_at) {
                    definition += '\n    class C completed';
                } else if (needsCouncil) {
                    definition += '\n    class C active';
                }
            } else if (status !== 'cancelado') {
                definition += '\n    class B active';
            }
            definition += '\n    class G cancelled';
            break;
        default:
            definition += '\n    class A pending';
            definition += '\n    class B pending';
            if (needsCouncil) {
                definition += '\n    class C pending';
            }
            definition += '\n    class D pending';
            definition += '\n    class E pending';
    }

    return definition;
});

const currentStepInfo = computed(() => {
    const status = props.expense.status;
    const needsCouncil = requiresCouncilApproval.value;

    switch (status) {
        case 'borrador':
            return {
                title: 'Gasto en borrador',
                description: 'El gasto está siendo creado y aún no ha sido enviado para aprobación.',
                icon: '📝',
                color: 'text-gray-600',
            };
        case 'pendiente':
            return {
                title: 'Esperando aprobación administrativa',
                description: 'El gasto está pendiente de aprobación por parte del administrador.',
                icon: '⏳',
                color: 'text-yellow-600',
            };
        case 'pendiente_concejo':
            return {
                title: 'Esperando aprobación del consejo',
                description: `Debido a que el monto (${formatCurrency(props.expense.total_amount)}) supera el umbral de aprobación, requiere autorización del consejo de administración.`,
                icon: '🏛️',
                color: 'text-orange-600',
            };
        case 'aprobado':
            return {
                title: 'Gasto aprobado',
                description: needsCouncil
                    ? 'El gasto ha sido aprobado por el administrador y el consejo.'
                    : 'El gasto ha sido aprobado por el administrador.',
                icon: '✅',
                color: 'text-blue-600',
            };
        case 'pagado':
            return {
                title: 'Gasto pagado',
                description: 'El gasto ha sido completamente procesado y pagado.',
                icon: '💰',
                color: 'text-green-600',
            };
        case 'rechazado':
            return {
                title: 'Gasto rechazado',
                description: 'El gasto ha sido rechazado y no será procesado.',
                icon: '❌',
                color: 'text-red-600',
            };
        case 'cancelado':
            return {
                title: 'Gasto cancelado',
                description: 'El gasto ha sido cancelado y no será procesado.',
                icon: '⏹️',
                color: 'text-gray-600',
            };
        default:
            return {
                title: 'Estado desconocido',
                description: 'El estado del gasto no es reconocido.',
                icon: '❓',
                color: 'text-gray-400',
            };
    }
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const timelineItems = computed(() => {
    const items = [];
    const needsCouncil = requiresCouncilApproval.value;

    // Creation
    items.push({
        title: 'Gasto creado',
        date: props.expense.created_at,
        completed: true,
        icon: '📝',
    });

    // Admin approval
    if (props.expense.approved_at) {
        items.push({
            title: 'Aprobado por administrador',
            date: props.expense.approved_at,
            completed: true,
            icon: '✅',
        });
    } else if (['pendiente', 'pendiente_concejo', 'aprobado', 'pagado'].includes(props.expense.status)) {
        items.push({
            title: 'Pendiente aprobación administrativa',
            date: null,
            completed: false,
            icon: '⏳',
        });
    }

    // Council approval (if required)
    if (needsCouncil) {
        if (props.expense.council_approved_at) {
            items.push({
                title: 'Aprobado por consejo',
                date: props.expense.council_approved_at,
                completed: true,
                icon: '🏛️',
            });
        } else if (['pendiente_concejo', 'aprobado', 'pagado'].includes(props.expense.status)) {
            items.push({
                title: 'Pendiente aprobación del consejo',
                date: null,
                completed: false,
                icon: '🏛️',
            });
        }
    }

    // Payment
    if (props.expense.paid_at) {
        items.push({
            title: 'Gasto pagado',
            date: props.expense.paid_at,
            completed: true,
            icon: '💰',
        });
    } else if (['aprobado'].includes(props.expense.status)) {
        items.push({
            title: 'Pendiente de pago',
            date: null,
            completed: false,
            icon: '💰',
        });
    }

    return items;
});

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getProgressPercentage = (): number => {
    const status = props.expense.status;
    const needsCouncil = requiresCouncilApproval.value;

    // Calculate total steps
    const totalSteps = needsCouncil ? 5 : 4; // borrador -> pendiente -> [concejo] -> aprobado -> pagado

    switch (status) {
        case 'borrador':
            return Math.round((1 / totalSteps) * 100);
        case 'pendiente':
            return Math.round((2 / totalSteps) * 100);
        case 'pendiente_concejo':
            return Math.round((3 / totalSteps) * 100);
        case 'aprobado':
            return needsCouncil ? Math.round((4 / totalSteps) * 100) : Math.round((3 / totalSteps) * 100);
        case 'pagado':
            return 100;
        case 'rechazado':
        case 'cancelado':
            return Math.round((2 / totalSteps) * 100); // Stopped at second step
        default:
            return 0;
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Current Status Info - Enhanced -->
        <div
            :class="[
                'relative overflow-hidden rounded-lg border p-4',
                expense.status === 'borrador'
                    ? 'border-gray-300 bg-gray-50'
                    : expense.status === 'pendiente'
                      ? 'border-yellow-300 bg-yellow-50'
                      : expense.status === 'pendiente_concejo'
                        ? 'border-orange-300 bg-orange-50'
                        : expense.status === 'aprobado'
                          ? 'border-blue-300 bg-blue-50'
                          : expense.status === 'pagado'
                            ? 'border-green-300 bg-green-50'
                            : expense.status === 'rechazado'
                              ? 'border-red-300 bg-red-50'
                              : expense.status === 'cancelado'
                                ? 'border-gray-400 bg-gray-50'
                                : 'border-gray-200 bg-white',
            ]"
        >
            <!-- Animated Background Indicator -->
            <div
                v-if="['pendiente', 'pendiente_concejo'].includes(expense.status)"
                class="absolute inset-0 animate-pulse bg-gradient-to-r from-transparent via-white/20 to-transparent"
            ></div>

            <div class="relative z-10 flex items-start space-x-4">
                <div
                    :class="[
                        'flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full text-2xl font-bold',
                        expense.status === 'borrador'
                            ? 'bg-gray-100 text-gray-700'
                            : expense.status === 'pendiente'
                              ? 'animate-bounce bg-yellow-100 text-yellow-700'
                              : expense.status === 'pendiente_concejo'
                                ? 'animate-bounce bg-orange-100 text-orange-700'
                                : expense.status === 'aprobado'
                                  ? 'bg-blue-100 text-blue-700'
                                  : expense.status === 'pagado'
                                    ? 'bg-green-100 text-green-700'
                                    : expense.status === 'rechazado'
                                      ? 'bg-red-100 text-red-700'
                                      : expense.status === 'cancelado'
                                        ? 'bg-gray-200 text-gray-600'
                                        : 'bg-gray-100 text-gray-600',
                    ]"
                >
                    {{ currentStepInfo.icon }}
                </div>
                <div class="flex-1">
                    <h3 :class="['mb-2 text-xl font-bold', currentStepInfo.color]">
                        {{ currentStepInfo.title }}
                    </h3>
                    <p class="mb-3 text-sm text-gray-700">
                        {{ currentStepInfo.description }}
                    </p>

                    <!-- Process Progress Indicator -->
                    <div class="mb-3">
                        <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
                            <span>Progreso del proceso</span>
                            <span>{{ getProgressPercentage() }}% completado</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-200">
                            <div
                                :class="[
                                    'h-2 rounded-full transition-all duration-500 ease-out',
                                    expense.status === 'pagado'
                                        ? 'bg-green-500'
                                        : expense.status === 'aprobado'
                                          ? 'bg-blue-500'
                                          : expense.status === 'pendiente_concejo'
                                            ? 'bg-orange-500'
                                            : expense.status === 'pendiente'
                                              ? 'bg-yellow-500'
                                              : expense.status === 'borrador'
                                                ? 'bg-gray-400'
                                                : 'bg-red-500',
                                ]"
                                :style="{ width: getProgressPercentage() + '%' }"
                            ></div>
                        </div>
                    </div>

                    <div
                        v-if="requiresCouncilApproval && ['borrador', 'pendiente'].includes(expense.status)"
                        class="rounded border-l-4 border-orange-500 bg-orange-100 p-3 text-sm text-orange-800"
                    >
                        <div class="flex items-center">
                            <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                />
                            </svg>
                            <div>
                                <strong>Requiere aprobación del consejo</strong><br />
                                Monto: {{ formatCurrency(expense.total_amount) }} ≥ {{ formatCurrency(approvalThreshold) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flowchart Diagram -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h4 class="mb-4 text-lg font-semibold text-gray-900">Flujo de Aprobación</h4>
            <MermaidFlowchart :definition="flowchartDefinition" height="200" width="100%" />
        </div>

        <!-- Timeline -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h4 class="mb-4 text-lg font-semibold text-gray-900">Cronología del Proceso</h4>
            <div class="space-y-4">
                <div v-for="(item, index) in timelineItems" :key="index" class="flex items-start space-x-3">
                    <div
                        :class="[
                            'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-medium',
                            item.completed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500',
                        ]"
                    >
                        <span class="text-xs">{{ item.icon }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p :class="['text-sm font-medium', item.completed ? 'text-gray-900' : 'text-gray-500']">
                            {{ item.title }}
                        </p>
                        <p v-if="item.date" class="mt-1 text-xs text-gray-500">
                            {{ formatDate(item.date) }}
                        </p>
                        <p v-else class="mt-1 text-xs text-gray-400">Pendiente</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
