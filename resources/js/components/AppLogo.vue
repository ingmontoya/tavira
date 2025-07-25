<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground">
        <AppLogoIcon type="iso" class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-xl">
        <span class="mb-0.5 truncate bg-gradient-to-r from-[#2563EB] to-[#059669] bg-clip-text leading-tight font-extrabold text-transparent"
            >Habitta</span
        >
    </div>
</template>
