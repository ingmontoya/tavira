<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    items: NavItem[];
    title?: string;
}>();

const page = usePage();
const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            v-if="props.title"
            class="group/label relative mb-2 text-xs font-semibold tracking-wider text-muted-foreground/70 uppercase transition-all duration-300 hover:text-muted-foreground"
        >
            <div class="flex items-center gap-2">
                <span>{{ props.title }}</span>
                <div
                    class="h-1 w-1 rounded-full bg-primary/50 opacity-0 transition-all duration-300 group-hover/label:animate-pulse group-hover/label:opacity-100"
                />
            </div>
            <div
                class="absolute bottom-0 left-0 h-px w-0 bg-gradient-to-r from-primary/20 to-transparent transition-all duration-500 group-hover/label:w-full"
            />
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem
                v-for="(item, index) in props.items"
                :key="item.title"
                class="transform transition-all duration-300 ease-out"
                :class="[isVisible ? 'translate-x-0 opacity-100' : 'translate-x-4 opacity-0']"
                :style="{ transitionDelay: `${index * 50}ms` }"
            >
                <SidebarMenuButton
                    as-child
                    :is-active="item.href === page.url"
                    :tooltip="item.title"
                    class="group relative overflow-hidden transition-all duration-200 hover:scale-105 hover:shadow-sm"
                >
                    <Link
                        :href="item.href!"
                        :data-tour="item.tourId"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-gradient-to-r hover:from-primary/10 hover:to-primary/5"
                    >
                        <component :is="item.icon" class="h-4 w-4 transition-all duration-200 group-hover:scale-110 group-hover:rotate-3" />
                        <span class="transition-all duration-200 group-hover:translate-x-0.5">
                            {{ item.title }}
                        </span>
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                        />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
