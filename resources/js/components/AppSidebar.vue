<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useNavigation } from '@/composables/useNavigation';
import { Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

const { mainNavItems, footerNavItems } = useNavigation();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="relative overflow-hidden">
        <SidebarHeader class="relative z-10">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" class="flex items-center gap-3 rounded-lg p-3">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="relative z-10 space-y-4 py-2">
            <div v-if="mainNavItems.length > 0">
                <NavMain :items="mainNavItems" />
            </div>
        </SidebarContent>

        <SidebarFooter class="relative z-10">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
