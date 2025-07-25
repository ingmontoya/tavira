<template>
    <div v-if="isActive" class="virtual-tour">
        <!-- Overlay solo en el área del contenido, NO en el sidebar -->
        <div
            v-if="!isCurrentStepInSidebar"
            class="fixed z-30 bg-black/60 backdrop-blur-sm transition-all duration-300"
            :style="contentOverlayStyle"
            @click="pauseTour"
        />

        <div
            v-if="currentStep"
            class="fixed z-50 animate-in rounded-lg border border-gray-200 bg-white shadow-2xl duration-300 fade-in"
            :class="tooltipClasses"
            :style="stepPosition"
        >
            <div class="p-5">
                <div class="mb-3 flex items-start justify-between">
                    <h3 class="pr-2 text-base font-semibold text-gray-900">{{ currentStep.title }}</h3>
                    <button @click="pauseTour" class="flex-shrink-0 text-gray-400 hover:text-gray-600" title="Pausar tour">
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <p class="mb-5 text-sm leading-relaxed text-gray-600">{{ currentStep.description }}</p>

                <div class="space-y-4">
                    <div class="flex items-center justify-center space-x-2">
                        <button
                            @click="previousStep"
                            :disabled="currentStepIndex === 0"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Anterior
                        </button>
                        <button @click="nextStep" class="rounded-md bg-blue-600 px-3 py-2 text-sm text-white transition-colors hover:bg-blue-700">
                            {{ isLastStep ? 'Finalizar' : 'Siguiente' }}
                        </button>
                        <button
                            @click="closeTour"
                            class="rounded-md bg-red-600 px-3 py-2 text-sm text-white transition-colors hover:bg-red-700"
                            title="Cerrar tour completamente"
                        >
                            Salir
                        </button>
                    </div>

                    <div class="flex items-center justify-center space-x-3">
                        <div class="flex space-x-1">
                            <div
                                v-for="(step, index) in steps"
                                :key="step.id"
                                class="h-2 w-2 rounded-full transition-colors"
                                :class="index === currentStepIndex ? 'bg-blue-600' : index < currentStepIndex ? 'bg-blue-300' : 'bg-gray-300'"
                            />
                        </div>
                        <span class="text-xs text-gray-500"> {{ currentStepIndex + 1 }} de {{ steps.length }} </span>
                    </div>
                </div>
            </div>

            <div class="absolute h-3 w-3 rotate-45 transform border-t border-l bg-white" :style="arrowPosition" />
        </div>

        <div
            v-if="currentStep?.element"
            class="pointer-events-none fixed z-40 animate-pulse rounded-lg border-2 border-blue-500 shadow-lg"
            :style="highlightStyle"
        >
            <div class="absolute inset-0 rounded-lg bg-blue-500/10"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import Icon from './Icon.vue';

interface TourStep {
    id: string;
    title: string;
    description: string;
    element?: string;
    position?: 'top' | 'bottom' | 'left' | 'right';
    action?: () => void;
}

interface Props {
    steps: TourStep[];
    autoStart?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    autoStart: false,
});

const emit = defineEmits<{
    start: [];
    complete: [];
    close: [];
    stepChange: [step: TourStep, index: number];
}>();

const isActive = ref(false);
const currentStepIndex = ref(0);
const stepPosition = ref({});
const highlightStyle = ref({});
const arrowPosition = ref({});
const contentOverlayStyle = ref({});

const currentStep = computed(() => props.steps[currentStepIndex.value]);
const isLastStep = computed(() => currentStepIndex.value === props.steps.length - 1);
const isCurrentStepInSidebar = computed(() => {
    const stepId = currentStep.value?.id;
    const sidebarSteps = [
        'step-1-programs',
        'step-1-periods',
        'step-1-study-plans',
        'step-1-subjects',
        'step-1-groups',
        'step-2-students',
        'step-3-enrollments',
        'step-4-dashboard',
    ];
    return sidebarSteps.includes(stepId || '');
});

const tooltipClasses = computed(() => {
    const baseClasses = 'max-w-sm w-80';
    // En pantallas pequeñas, usar casi todo el ancho disponible
    const responsiveClasses = 'sm:w-80 sm:max-w-sm w-[calc(100vw-2rem)] max-w-[calc(100vw-2rem)]';
    return `${baseClasses} ${responsiveClasses}`;
});

// Persistencia del estado del tour
const TOUR_STORAGE_KEY = 'sia-tour-state';

const saveTourState = () => {
    const state = {
        isActive: isActive.value,
        currentStepIndex: currentStepIndex.value,
        tourId: 'flow1-manual-config',
    };
    localStorage.setItem(TOUR_STORAGE_KEY, JSON.stringify(state));
};

const loadTourState = () => {
    try {
        const saved = localStorage.getItem(TOUR_STORAGE_KEY);
        if (saved) {
            const state = JSON.parse(saved);
            if (state.tourId === 'flow1-manual-config' && state.isActive) {
                isActive.value = true;
                currentStepIndex.value = Math.min(state.currentStepIndex, props.steps.length - 1);
                emit('start');
                nextTick(() => {
                    updateStepPosition();
                    updateOverlay();
                    // Configurar listeners para el paso cargado
                    setupTourClickListeners();
                });
                return true;
            }
        }
    } catch (error) {
        console.warn('Error loading tour state:', error);
    }
    return false;
};

const loadAndContinueTour = () => {
    const loaded = loadTourState();
    if (!loaded) {
        startTour();
    } else {
        // Si se cargó un tour existente, también configurar los listeners
        setupTourClickListeners();
    }
};

const clearTourState = () => {
    localStorage.removeItem(TOUR_STORAGE_KEY);
};

const isOnCorrectPageForStep = () => {
    const currentPath = window.location.pathname;
    const stepId = currentStep.value?.id;

    const pageMapping = {
        'programs-new-button': '/programs',
        'periods-new-button': '/periods',
        'study-plans-new-button': '/study-plans',
        'subjects-new-button': '/subjects',
        'groups-new-button': '/groups',
        'students-new-button': '/students',
        'enrollments-new-button': '/enrollments',
    };

    if (stepId && pageMapping[stepId]) {
        return currentPath === pageMapping[stepId];
    }

    return true; // Por defecto asumir que está en la página correcta
};

const updateOverlay = () => {
    const sidebar = document.querySelector('[data-sidebar="sidebar"]');

    if (isCurrentStepInSidebar.value) {
        // Si el paso está en el sidebar, NO mostrar overlay para permitir interacción completa
        contentOverlayStyle.value = {
            display: 'none',
        };
    } else if (sidebar) {
        const sidebarRect = sidebar.getBoundingClientRect();

        // Overlay que cubre solo el área del contenido, NO el sidebar
        contentOverlayStyle.value = {
            display: 'block',
            top: '0',
            left: `${sidebarRect.width}px`,
            right: '0',
            bottom: '0',
            transition: 'left 0.3s ease',
        };
    } else {
        // Si no hay sidebar, cubrir toda la pantalla
        contentOverlayStyle.value = {
            display: 'block',
            top: '0',
            left: '0',
            right: '0',
            bottom: '0',
        };
    }
};

const startTour = () => {
    // Limpiar cualquier estado previo si existe
    clearTourState();

    isActive.value = true;
    currentStepIndex.value = 0;

    // Asegurar que el sidebar tenga mayor z-index durante el tour
    const sidebar = document.querySelector('[data-sidebar="sidebar"]');
    if (sidebar) {
        sidebar.style.zIndex = '35';
    }

    // Agregar event listeners para detectar clicks en elementos del tour
    setupTourClickListeners();

    emit('start');
    saveTourState();
    nextTick(() => {
        updateStepPosition();
        updateOverlay();
    });
};

const pauseTour = () => {
    isActive.value = false;

    // Restaurar el z-index del sidebar
    const sidebar = document.querySelector('[data-sidebar="sidebar"]');
    if (sidebar) {
        sidebar.style.zIndex = '';
    }

    // Limpiar event listeners
    cleanupTourClickListeners();

    // NO limpiar el estado para poder continuar después
    emit('close');
};

const closeTour = () => {
    isActive.value = false;

    // Restaurar el z-index del sidebar
    const sidebar = document.querySelector('[data-sidebar="sidebar"]');
    if (sidebar) {
        sidebar.style.zIndex = '';
    }

    // Limpiar event listeners
    cleanupTourClickListeners();

    // Limpiar completamente el estado
    clearTourState();
    emit('close');
};

const nextStep = () => {
    if (isLastStep.value) {
        completeTour();
    } else {
        currentStepIndex.value++;
        saveTourState();
        emit('stepChange', currentStep.value, currentStepIndex.value);
        nextTick(() => {
            updateStepPosition();
            updateOverlay();
            // Reconfigurar listeners para el nuevo paso
            setupTourClickListeners();
        });
    }
};

const previousStep = () => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--;
        saveTourState();
        emit('stepChange', currentStep.value, currentStepIndex.value);
        nextTick(() => {
            updateStepPosition();
            updateOverlay();
            // Reconfigurar listeners para el nuevo paso
            setupTourClickListeners();
        });
    }
};

const completeTour = () => {
    isActive.value = false;

    // Restaurar el z-index del sidebar
    const sidebar = document.querySelector('[data-sidebar="sidebar"]');
    if (sidebar) {
        sidebar.style.zIndex = '';
    }

    // Limpiar event listeners
    cleanupTourClickListeners();

    clearTourState();
    emit('complete');
};

const handleKeydown = (event: KeyboardEvent) => {
    if (!isActive.value) return;

    switch (event.key) {
        case 'Escape':
            pauseTour();
            break;
        case 'ArrowLeft':
            if (currentStepIndex.value > 0) {
                previousStep();
            }
            break;
        case 'ArrowRight':
            nextStep();
            break;
        case 'Enter':
            nextStep();
            break;
    }
};

const updateStepPosition = () => {
    if (!currentStep.value?.element) {
        // Si no hay elemento específico, centrar el tooltip
        stepPosition.value = {
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
        };
        arrowPosition.value = { display: 'none' };
        highlightStyle.value = { display: 'none' };
        return;
    }

    const element = document.querySelector(currentStep.value.element);
    if (!element) {
        // Si estamos buscando un botón de "nuevo" pero no estamos en la página correcta
        if (currentStep.value.element.includes('new-') && !isOnCorrectPageForStep()) {
            // Mostrar mensaje centrado indicando que navegue
            stepPosition.value = {
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
            };
            arrowPosition.value = { display: 'none' };
            highlightStyle.value = { display: 'none' };
            return;
        }

        // Fallback a posición centrada
        stepPosition.value = {
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
        };
        arrowPosition.value = { display: 'none' };
        highlightStyle.value = { display: 'none' };
        return;
    }

    const rect = element.getBoundingClientRect();
    const position = currentStep.value.position || 'bottom';

    const offset = 20;
    const isMobile = window.innerWidth < 640;
    const tooltipWidth = isMobile ? Math.min(320, window.innerWidth - 32) : 320; // w-80 = 320px, but responsive
    const tooltipHeight = 200; // Estimated height including content and buttons
    let top = 0;
    let left = 0;
    let arrowTop = '';
    let arrowLeft = '';
    let actualPosition = position;

    // Try the preferred position first, then fallback if it doesn't fit
    const positions = [position, 'bottom', 'top', 'right', 'left'];

    for (const pos of positions) {
        switch (pos) {
            case 'top':
                top = rect.top - tooltipHeight - offset;
                left = rect.left + rect.width / 2 - tooltipWidth / 2;
                if (top >= 20) {
                    actualPosition = 'top';
                    arrowTop = '100%';
                    arrowLeft = '50%';
                    break;
                }
                continue;
            case 'bottom':
                top = rect.bottom + offset;
                left = rect.left + rect.width / 2 - tooltipWidth / 2;
                if (top + tooltipHeight <= window.innerHeight - 20) {
                    actualPosition = 'bottom';
                    arrowTop = '-6px';
                    arrowLeft = '50%';
                    break;
                }
                continue;
            case 'left':
                top = rect.top + rect.height / 2 - tooltipHeight / 2;
                left = rect.left - tooltipWidth - offset;
                if (left >= 20) {
                    actualPosition = 'left';
                    arrowTop = '50%';
                    arrowLeft = '100%';
                    break;
                }
                continue;
            case 'right':
                top = rect.top + rect.height / 2 - tooltipHeight / 2;
                left = rect.right + offset;
                if (left + tooltipWidth <= window.innerWidth - 20) {
                    actualPosition = 'right';
                    arrowTop = '50%';
                    arrowLeft = '-6px';
                    break;
                }
                continue;
        }
        break;
    }

    // Ensure tooltip stays within viewport bounds with padding
    const padding = 20;
    const maxTop = window.innerHeight - tooltipHeight - padding;
    const maxLeft = window.innerWidth - tooltipWidth - padding;

    const finalTop = Math.max(padding, Math.min(maxTop, top));
    const finalLeft = Math.max(padding, Math.min(maxLeft, left));

    stepPosition.value = {
        top: `${finalTop}px`,
        left: `${finalLeft}px`,
    };

    arrowPosition.value = {
        top: arrowTop,
        left: arrowLeft,
        transform: 'translate(-50%, -50%)',
    };

    highlightStyle.value = {
        top: `${rect.top - 4}px`,
        left: `${rect.left - 4}px`,
        width: `${rect.width + 8}px`,
        height: `${rect.height + 8}px`,
    };

    // Scroll element into view if needed
    element.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

    // Actualizar overlay después del scroll
    setTimeout(() => {
        updateOverlay();
    }, 100);
};

const handleResize = () => {
    if (isActive.value) {
        updateStepPosition();
    }
};

let elementCheckInterval: number | null = null;
let tourClickListeners: Array<{ element: Element; handler: EventListener }> = [];

const setupTourClickListeners = () => {
    // Limpiar listeners previos
    cleanupTourClickListeners();

    // Si no hay paso actual, no configurar listeners
    if (!currentStep.value) return;

    // Configurar listener para el elemento actual del tour
    if (currentStep.value.element) {
        const element = document.querySelector(currentStep.value.element);
        if (element) {
            const handler = (e: Event) => {
                if (isActive.value) {
                    // Mostrar feedback visual inmediato
                    showClickFeedback();

                    // Para pasos de navegación, permitir la navegación y avanzar después
                    if (isNavigationStep(currentStep.value.id)) {
                        const targetPath = getTargetPathForStep(currentStep.value.id);
                        if (targetPath) {
                            // Permitir la navegación normal
                            setTimeout(() => {
                                // Verificar si estamos en la página correcta y avanzar
                                if (window.location.pathname === targetPath) {
                                    nextStep();
                                }
                            }, 500);
                        }
                    } else {
                        // Para elementos de acción (botones), verificar si es una navegación de formulario
                        if (isFormNavigationStep(currentStep.value.id)) {
                            // Para botones que llevan a formularios, detectar cuando la navegación se complete
                            const checkNavigation = () => {
                                // Verificar si la URL cambió (indica navegación completada)
                                const currentPath = window.location.pathname;
                                if (currentPath.includes('/create') || currentPath.includes('/edit')) {
                                    nextStep();
                                } else {
                                    // Si no ha navegado aún, seguir verificando
                                    setTimeout(checkNavigation, 100);
                                }
                            };
                            setTimeout(checkNavigation, 200); // Dar un momento para que inicie la navegación
                        } else {
                            // Para otros elementos de acción, avanzar inmediatamente
                            setTimeout(() => {
                                nextStep();
                            }, 300);
                        }
                    }
                }
            };

            element.addEventListener('click', handler);
            tourClickListeners.push({ element, handler });
        }
    }
};

const isNavigationStep = (stepId: string) => {
    const navigationSteps = [
        'step-1-programs',
        'step-1-periods',
        'step-1-study-plans',
        'step-1-subjects',
        'step-1-groups',
        'step-2-students',
        'step-3-enrollments',
        'step-4-dashboard',
    ];
    return navigationSteps.includes(stepId);
};

const isFormNavigationStep = (stepId: string) => {
    const formNavigationSteps = [
        'programs-new-button',
        'periods-new-button',
        'study-plans-new-button',
        'subjects-new-button',
        'groups-new-button',
        'students-new-button',
        'enrollments-new-button',
    ];
    return formNavigationSteps.includes(stepId);
};

const getTargetPathForStep = (stepId: string) => {
    const pathMapping: { [key: string]: string } = {
        'step-1-programs': '/programs',
        'step-1-periods': '/periods',
        'step-1-study-plans': '/study-plans',
        'step-1-subjects': '/subjects',
        'step-1-groups': '/groups',
        'step-2-students': '/students',
        'step-3-enrollments': '/enrollments',
        'step-4-dashboard': '/dashboard',
    };
    return pathMapping[stepId];
};

const cleanupTourClickListeners = () => {
    tourClickListeners.forEach(({ element, handler }) => {
        element.removeEventListener('click', handler);
    });
    tourClickListeners = [];
};

const showClickFeedback = () => {
    // Crear un elemento de feedback visual temporal
    const feedback = document.createElement('div');
    feedback.textContent = '✓ ¡Perfecto!';
    feedback.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #10b981;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: tourFeedback 1.5s ease-out forwards;
  `;

    // Agregar animación CSS si no existe
    if (!document.querySelector('#tour-feedback-styles')) {
        const style = document.createElement('style');
        style.id = 'tour-feedback-styles';
        style.textContent = `
      @keyframes tourFeedback {
        0% {
          opacity: 0;
          transform: translate(-50%, -50%) scale(0.8);
        }
        20% {
          opacity: 1;
          transform: translate(-50%, -50%) scale(1.1);
        }
        40% {
          transform: translate(-50%, -50%) scale(1);
        }
        100% {
          opacity: 0;
          transform: translate(-50%, -60%) scale(1);
        }
      }
    `;
        document.head.appendChild(style);
    }

    document.body.appendChild(feedback);

    // Remover el feedback después de la animación
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.parentNode.removeChild(feedback);
        }
    }, 1500);
};

onMounted(() => {
    // Solo cargar automáticamente si autoStart está activado
    if (props.autoStart) {
        const hasState = loadTourState();
        if (!hasState) {
            startTour();
        }
    }

    // Verificar periódicamente si el elemento del tour está disponible
    const checkElementAvailability = () => {
        if (isActive.value && currentStep.value?.element) {
            const element = document.querySelector(currentStep.value.element);
            if (element) {
                updateStepPosition();
                updateOverlay();
                // Reconfigurar listeners si el elemento ahora está disponible
                if (tourClickListeners.length === 0) {
                    setupTourClickListeners();
                }
            }
        }
    };

    elementCheckInterval = setInterval(checkElementAvailability, 500);

    window.addEventListener('resize', handleResize);
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    if (elementCheckInterval) {
        clearInterval(elementCheckInterval);
    }
    cleanupTourClickListeners();
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('keydown', handleKeydown);
});

defineExpose({
    startTour,
    closeTour,
    pauseTour,
    nextStep,
    previousStep,
    loadAndContinueTour,
});
</script>

<style scoped>
.virtual-tour {
    font-family: inherit;
}
</style>
