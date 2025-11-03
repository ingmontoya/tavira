<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
    type?: string;
    black?: boolean;
}

defineProps<Props>();
</script>

<template>
    <img v-if="black" :src="type === 'iso' ? '/img/t2.svg' : '/img/tavira_logo.svg'" alt="Logo"
        :class="$attrs.className" />
    <img v-else :src="type === 'iso' ? '/img/t2.svg' : '/img/tavira_logo_blanco.png'" alt="Logo"
        :class="$attrs.className" />
</template>
