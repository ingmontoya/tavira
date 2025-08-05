<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const user = page.props.auth.user as User;
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="group relative overflow-hidden transition-all duration-200 hover:scale-105 hover:shadow-lg data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div class="flex w-full items-center gap-3 transition-all duration-200 group-hover:translate-x-0.5">
                            <UserInfo :user="user" />
                            <ChevronsUpDown class="ml-auto size-4 transition-all duration-200 group-hover:scale-110 group-hover:rotate-180" />
                        </div>
                        <div
                            class="absolute inset-0 rounded-lg bg-gradient-to-r from-primary/5 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                        />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 animate-in rounded-lg border-border/50 bg-background/95 shadow-xl backdrop-blur-sm duration-200 fade-in-0 zoom-in-95"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
