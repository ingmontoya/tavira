<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useNavigation } from '@/composables/useNavigation';
import { Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

const {
    mainNavItems,
    residentsNavItems,
    financeNavItems,
    accountingNavItems,
    communicationNavItems,
    documentsNavItems,
    systemNavItems,
    footerNavItems,
} = useNavigation();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="mainNavItems.length > 0" title="Principal" :items="mainNavItems" />
            <NavMain v-if="residentsNavItems.length > 0" title="Administración" :items="residentsNavItems" />
            <NavMain v-if="financeNavItems.length > 0" title="Finanzas" :items="financeNavItems" />
            <NavMain v-if="accountingNavItems.length > 0" title="Contabilidad" :items="accountingNavItems" />
            <NavMain v-if="communicationNavItems.length > 0" title="Comunicación" :items="communicationNavItems" />
            <NavMain v-if="documentsNavItems.length > 0" title="Documentos" :items="documentsNavItems" />
            <NavMain v-if="systemNavItems.length > 0" title="Sistema" :items="systemNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
