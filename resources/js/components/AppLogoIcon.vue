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
    <img v-if="black" :src="type === 'iso' ? '/img/iso.png' : '/img/tavira_logo.png'" alt="Logo"
        :class="$attrs.className" />
    <img v-else :src="type === 'iso' ? '/img/iso.png' : '/img/tavira_logo.png'" alt="Logo" :class="$attrs.className" />
</template>
