<script setup lang="ts">
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

interface Props {
    items: NavItem[];
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
        <SidebarGroupContent>
            <SidebarMenu class="space-y-1">
                <SidebarMenuItem v-for="(item, index) in items" :key="item.title">
                    <SidebarMenuButton
                        class="group relative overflow-hidden text-neutral-600 transition-all duration-200 hover:scale-105 hover:text-neutral-800 hover:shadow-sm dark:text-neutral-300 dark:hover:text-neutral-100"
                        as-child
                    >
                        <a
                            :href="item.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200 hover:bg-gradient-to-r hover:from-muted/50 hover:to-muted/25"
                        >
                            <component :is="item.icon" class="h-4 w-4 transition-all duration-200 group-hover:scale-110 group-hover:-rotate-3" />
                            <span class="transition-all duration-200 group-hover:translate-x-0.5">
                                {{ item.title }}
                            </span>
                            <div
                                class="absolute inset-0 rounded-lg bg-gradient-to-r from-muted/10 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                            />
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
