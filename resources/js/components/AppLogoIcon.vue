<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
    type?: string;
}

defineProps<Props>();
</script>

<template>
    <img :src="type === 'iso' ? '/img/iso_modulyx.png' : '/img/modulyx.png'" alt="Logo" :class="$attrs.className" />
</template>
