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
                    <SidebarMenuButton class="text-neutral-600 dark:text-neutral-300" as-child>
                        <a
                            :href="item.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>
                                {{ item.title }}
                            </span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
