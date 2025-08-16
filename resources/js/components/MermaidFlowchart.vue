<script setup lang="ts">
import mermaid from 'mermaid';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    definition: string;
    theme?: 'default' | 'dark' | 'base' | 'forest' | 'neutral';
    height?: number | string;
    width?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    theme: 'default',
    height: 'auto',
    width: '100%',
});

const mermaidContainer = ref<HTMLDivElement>();
const mermaidId = ref(`mermaid-${Math.random().toString(36).substring(2, 15)}`);

onMounted(async () => {
    mermaid.initialize({
        startOnLoad: false,
        theme: props.theme,
        flowchart: {
            useMaxWidth: true,
            htmlLabels: true,
            curve: 'cardinal',
            nodeSpacing: 50,
            rankSpacing: 80,
        },
        themeVariables: {
            primaryColor: '#3B82F6',
            primaryTextColor: '#FFFFFF',
            primaryBorderColor: '#1D4ED8',
            lineColor: '#6B7280',
            sectionBkgColor: '#F3F4F6',
            altSectionBkgColor: '#E5E7EB',
            gridColor: '#E5E7EB',
            tertiaryColor: '#F9FAFB',
        },
    });

    await renderDiagram();
});

watch(
    () => props.definition,
    async () => {
        await renderDiagram();
    },
    { flush: 'post' },
);

const renderDiagram = async () => {
    if (!mermaidContainer.value) return;

    try {
        await nextTick();

        // Clear container
        mermaidContainer.value.innerHTML = '';

        // Validate and render diagram
        const { svg } = await mermaid.render(mermaidId.value, props.definition);
        mermaidContainer.value.innerHTML = svg;

        // Apply custom styles after rendering
        await nextTick();
        applyCustomStyles();
    } catch (error) {
        console.error('Error rendering Mermaid diagram:', error);
        mermaidContainer.value.innerHTML = '<p class="text-red-500 text-sm">Error rendering diagram</p>';
    }
};

const applyCustomStyles = () => {
    if (!mermaidContainer.value) return;

    const svg = mermaidContainer.value.querySelector('svg');
    if (svg) {
        svg.style.maxWidth = '100%';
        svg.style.height = typeof props.height === 'number' ? `${props.height}px` : props.height.toString();
        svg.style.width = typeof props.width === 'number' ? `${props.width}px` : props.width.toString();
    }
};

const containerStyle = computed(() => ({
    height: typeof props.height === 'number' ? `${props.height}px` : props.height,
    width: typeof props.width === 'number' ? `${props.width}px` : props.width,
}));
</script>

<template>
    <div ref="mermaidContainer" :style="containerStyle" class="mermaid-container overflow-x-auto" />
</template>

<style scoped>
.mermaid-container {
    display: flex;
    justify-content: center;
    align-items: center;
}

:global(.mermaid-container .node rect) {
    transition: all 0.3s ease;
}

:global(.mermaid-container .node.active rect) {
    fill: #3b82f6 !important;
    stroke: #1d4ed8 !important;
    stroke-width: 4px !important;
    filter: drop-shadow(0 4px 8px rgba(59, 130, 246, 0.4)) !important;
    animation: pulse-active 2s ease-in-out infinite !important;
}

@keyframes pulse-active {
    0%,
    100% {
        stroke-width: 4px;
        filter: drop-shadow(0 4px 8px rgba(59, 130, 246, 0.4));
    }
    50% {
        stroke-width: 6px;
        filter: drop-shadow(0 6px 12px rgba(59, 130, 246, 0.6));
    }
}

:global(.mermaid-container .node.completed rect) {
    fill: #10b981 !important;
    stroke: #059669 !important;
    stroke-width: 2px !important;
}

:global(.mermaid-container .node.pending rect) {
    fill: #f3f4f6 !important;
    stroke: #d1d5db !important;
    stroke-width: 1px !important;
}

:global(.mermaid-container .node.rejected rect) {
    fill: #ef4444 !important;
    stroke: #dc2626 !important;
    stroke-width: 2px !important;
}

:global(.mermaid-container .node.cancelled rect) {
    fill: #6b7280 !important;
    stroke: #4b5563 !important;
    stroke-width: 2px !important;
}

:global(.mermaid-container .node.active text) {
    fill: #ffffff !important;
    font-weight: bold !important;
    font-size: 14px !important;
}

:global(.mermaid-container .node.completed text) {
    fill: #ffffff !important;
    font-weight: bold !important;
}

:global(.mermaid-container .node.pending text) {
    fill: #6b7280 !important;
}

:global(.mermaid-container .node.rejected text) {
    fill: #ffffff !important;
    font-weight: bold !important;
}

:global(.mermaid-container .node.cancelled text) {
    fill: #ffffff !important;
    font-weight: bold !important;
}

:global(.mermaid-container .edge-thickness-normal) {
    stroke: #6b7280 !important;
    stroke-width: 2px !important;
}

:global(.mermaid-container .edge-thickness-thick) {
    stroke: #3b82f6 !important;
    stroke-width: 4px !important;
    filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.3)) !important;
}

:global(.mermaid-container .flowchart-link) {
    stroke: #6b7280 !important;
    stroke-width: 2px !important;
    fill: none !important;
}

:global(.mermaid-container .path.active-path) {
    stroke: #3b82f6 !important;
    stroke-width: 4px !important;
    filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.3)) !important;
    animation: flow-animation 3s ease-in-out infinite !important;
}

@keyframes flow-animation {
    0%,
    100% {
        stroke-dasharray: 0;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 10, 5;
        stroke-dashoffset: -15px;
    }
}

:global(.mermaid-container .edge-pattern-dotted) {
    stroke-dasharray: 5, 5 !important;
    stroke: #ef4444 !important;
}
</style>
