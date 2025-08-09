<script setup lang="ts">
import { 
    SidebarGroup, 
    SidebarGroupLabel, 
    SidebarMenu, 
    SidebarMenuButton, 
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
const props = defineProps<{
    items: NavItem[];
    title?: string;
}>();

const page = usePage();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            v-if="props.title"
            class="mb-2 text-xs font-semibold tracking-wider text-muted-foreground/70 uppercase"
        >
            <div class="flex items-center gap-2">
                <span>{{ props.title }}</span>
            </div>
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem
                v-for="(item, index) in props.items"
                :key="item.title"
            >
                <!-- Regular menu item (no children) -->
                <template v-if="!item.items || item.items.length === 0">
                    <SidebarMenuButton
                        as-child
                        :is-active="item.href === page.url"
                        :tooltip="item.title"
                    >
                        <Link
                            :href="item.href!"
                            :data-tour="item.tourId"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>
                                {{ item.title }}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </template>

                <!-- Collapsible menu item (has children) -->
                <template v-else>
                    <Collapsible 
                        :default-open="item.items?.some(subItem => subItem.href === page.url || subItem.items?.some(nestedItem => nestedItem.href === page.url))"
                    >
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :tooltip="item.title"
                            >
                                <div class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium w-full min-w-0">
                                    <component :is="item.icon" class="h-4 w-4 flex-shrink-0" />
                                    <span class="truncate flex-1">
                                        {{ item.title }}
                                    </span>
                                    <ChevronRight class="ml-auto h-4 w-4 flex-shrink-0" />
                                </div>
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <template v-for="subItem in item.items" :key="subItem.title">
                                    <!-- Sub-item without nested children -->
                                    <template v-if="!subItem.items || subItem.items.length === 0">
                                        <SidebarMenuSubItem>
                                            <SidebarMenuSubButton
                                                as-child
                                                :is-active="subItem.href === page.url"
                                            >
                                                <Link
                                                    :href="subItem.href!"
                                                    :data-tour="subItem.tourId"
                                                    class="flex items-center gap-3"
                                                >
                                                    <component :is="subItem.icon" class="h-3.5 w-3.5" />
                                                    <span>{{ subItem.title }}</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </template>

                                    <!-- Nested sub-item with children -->
                                    <template v-else>
                                        <SidebarMenuSubItem>
                                            <Collapsible 
                                                :default-open="subItem.items?.some(nestedItem => nestedItem.href === page.url)"
                                            >
                                                <CollapsibleTrigger as-child>
                                                    <SidebarMenuSubButton>
                                                        <div class="flex items-center gap-3 w-full min-w-0">
                                                            <component :is="subItem.icon" class="h-3.5 w-3.5 flex-shrink-0" />
                                                            <span class="truncate flex-1">{{ subItem.title }}</span>
                                                            <ChevronRight class="ml-auto h-3 w-3 flex-shrink-0" />
                                                        </div>
                                                    </SidebarMenuSubButton>
                                                </CollapsibleTrigger>
                                                <CollapsibleContent>
                                                    <SidebarMenuSub class="ml-4">
                                                        <SidebarMenuSubItem
                                                            v-for="nestedItem in subItem.items"
                                                            :key="nestedItem.title"
                                                        >
                                                            <SidebarMenuSubButton
                                                                as-child
                                                                :is-active="nestedItem.href === page.url"
                                                            >
                                                                <Link
                                                                    :href="nestedItem.href!"
                                                                    :data-tour="nestedItem.tourId"
                                                                    class="flex items-center gap-3"
                                                                >
                                                                    <component :is="nestedItem.icon" class="h-3 w-3" />
                                                                    <span class="text-xs">{{ nestedItem.title }}</span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    </SidebarMenuSub>
                                                </CollapsibleContent>
                                            </Collapsible>
                                        </SidebarMenuSubItem>
                                    </template>
                                </template>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
