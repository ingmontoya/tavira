<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

interface Props {
    definition: string;
}

const props = defineProps<Props>();

const diagramRef = ref<HTMLDivElement>();
const isLoading = ref(false); // Start as false, set to true only during actual rendering
const error = ref<string | null>(null);
const isClient = ref(false);

const enhanceDiagramWithAnimations = () => {
    if (!diagramRef.value) return;

    const svg = diagramRef.value.querySelector('svg');
    if (!svg) return;

    // Add animation classes with delay for better visual effect
    setTimeout(() => {
        // Current node will be animated via CSS only
        // No animations for completed nodes to avoid lateral movement
        // No animations for pending nodes to avoid any movement
        // No path animations to avoid any visual movement
        // No animations on the entire diagram to avoid movement
    }, 100);
};

const renderDiagram = async () => {
    // Only render on client side
    if (typeof document === 'undefined' || !isClient.value) {
        return;
    }

    if (!diagramRef.value || !props.definition) {
        isLoading.value = false;
        if (!diagramRef.value) {
            error.value = 'No se pudo encontrar el contenedor del diagrama';
        } else {
            error.value = 'No hay definición de diagrama';
        }
        return;
    }

    try {
        isLoading.value = true;
        error.value = null;

        // Clear previous content
        diagramRef.value.innerHTML = '';

        // Import mermaid
        const mermaidModule = await import('mermaid');
        const mermaid = mermaidModule.default || mermaidModule;

        // Initialize mermaid
        mermaid.initialize({
            startOnLoad: false,
            theme: 'default',
            securityLevel: 'loose',
            themeVariables: {
                primaryColor: '#10b981',
                primaryTextColor: '#ffffff',
                primaryBorderColor: '#059669',
                lineColor: '#6b7280',
                secondaryColor: '#f3f4f6',
                tertiaryColor: '#ffffff',
            },
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true,
                curve: 'basis',
            },
        });

        // Generate unique ID
        const diagramId = `mermaid-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

        // Render the diagram
        const result = await mermaid.render(diagramId, props.definition);

        // Insert the SVG
        if (result.svg && diagramRef.value) {
            diagramRef.value.innerHTML = result.svg;

            // Add post-render enhancements
            enhanceDiagramWithAnimations();
        } else {
            throw new Error('No SVG returned from mermaid.render');
        }

        isLoading.value = false;
    } catch (err: any) {
        console.error('MermaidDiagram Error:', err.message);
        error.value = `Error: ${err.message || 'Error desconocido al renderizar el diagrama'}`;
        isLoading.value = false;
    }
};

// Watch for changes in definition (only on client)
watch(
    () => props.definition,
    () => {
        if (props.definition && isClient.value) {
            renderDiagram();
        }
    },
    { immediate: false },
);

onMounted(async () => {
    // Mark as client-side
    isClient.value = true;

    // Wait for next tick to ensure DOM is ready
    await new Promise((resolve) => setTimeout(resolve, 200));

    if (props.definition) {
        renderDiagram();
    }
});
</script>

<template>
    <div class="mermaid-container">
        <!-- Server-side placeholder -->
        <div v-if="!isClient" class="flex items-center justify-center py-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Preparando diagrama...</span>
        </div>

        <!-- Client-side content -->
        <div v-else class="relative">
            <!-- Diagram container - always present when on client -->
            <div ref="diagramRef" class="mermaid-diagram flex min-h-[200px] w-full justify-center" :class="{ 'opacity-0': isLoading || error }"></div>

            <!-- Loading overlay -->
            <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white py-8">
                <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Cargando diagrama...</span>
            </div>

            <!-- Error overlay -->
            <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-white py-8 text-red-600">
                <span>{{ error }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mermaid-container {
    width: 100%;
}

.mermaid-diagram {
    text-align: center;
}

/* Ensure SVG is responsive */
:deep(svg) {
    max-width: 100%;
    height: auto;
}

/* Style mermaid flowchart elements */
:deep(.node rect) {
    rx: 4px;
    ry: 4px;
    transition: all 0.3s ease;
}

:deep(.edgePath path) {
    stroke-width: 2px;
    transition: all 0.3s ease;
}

:deep(.edgeLabel) {
    background-color: white;
    padding: 2px 4px;
    border-radius: 2px;
    font-size: 12px;
}

/* Animación sutil con múltiples efectos sin desplazamiento */
@keyframes subtle-pulse {
    0%,
    100% {
        opacity: 1;
        box-shadow:
            0 0 5px rgba(16, 185, 129, 0.4),
            inset 0 0 3px rgba(16, 185, 129, 0.1);
        filter: brightness(1) saturate(1);
    }
    33% {
        opacity: 0.9;
        box-shadow:
            0 0 12px rgba(16, 185, 129, 0.6),
            inset 0 0 6px rgba(16, 185, 129, 0.2);
        filter: brightness(1.1) saturate(1.2);
    }
    66% {
        opacity: 0.95;
        box-shadow:
            0 0 18px rgba(16, 185, 129, 0.8),
            inset 0 0 8px rgba(16, 185, 129, 0.3);
        filter: brightness(1.15) saturate(1.3);
    }
}

/* Animación complementaria para el borde */
@keyframes border-glow {
    0%,
    100% {
        border: 2px solid rgba(16, 185, 129, 0.3);
    }
    50% {
        border: 2px solid rgba(16, 185, 129, 0.8);
    }
}

/* Aplicar animaciones a nodos según su clase - solo pulso sin glow */

/* Animación sutil para nodos completados */
:deep(.completed rect) {
    animation: completed-glow 5s ease-in-out infinite;
    filter: brightness(1.05);
}

@keyframes completed-glow {
    0%,
    100% {
        box-shadow: 0 0 2px rgba(16, 185, 129, 0.2);
        opacity: 0.95;
    }
    50% {
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.4);
        opacity: 1;
    }
}

/* Remover efecto hover */

/* Entrada suave del diagrama sin desplazamiento */
.mermaid-diagram {
    animation: gentle-entrance 1s ease-out;
}

@keyframes gentle-entrance {
    0% {
        opacity: 0;
        filter: blur(2px);
    }
    100% {
        opacity: 1;
        filter: blur(0px);
    }
}

/* Animaciones sutiles múltiples para el nodo actual */
:deep(.current rect) {
    animation:
        subtle-pulse 4s ease-in-out infinite,
        border-glow 2s ease-in-out infinite;
    border-radius: 6px;
    transition: all 0.3s ease;
}

/* Efecto adicional en el contenedor del nodo actual */
:deep(.current) {
    position: relative;
}

/* Pseudo-elemento para crear un efecto de aura */
:deep(.current::before) {
    content: '';
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
    border-radius: 8px;
    animation: aura-pulse 3s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes aura-pulse {
    0%,
    100% {
        opacity: 0.2;
        filter: blur(1px);
    }
    50% {
        opacity: 0.6;
        filter: blur(0px);
    }
}
</style>
