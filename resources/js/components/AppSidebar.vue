<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useNavigation } from '@/composables/useNavigation';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
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

const isLoaded = ref(false);

onMounted(() => {
    setTimeout(() => {
        isLoaded.value = true;
    }, 200);
});
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="relative overflow-hidden transition-all duration-300 ease-in-out"
        :class="[
            'before:absolute before:inset-0 before:z-0 before:bg-gradient-to-b before:from-background/50 before:to-background/80 before:backdrop-blur-sm',
            isLoaded ? 'translate-x-0' : '-translate-x-full',
        ]"
    >
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-secondary/5 opacity-50" />

        <SidebarHeader class="relative z-10">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                    >
                        <Link
                            :href="route('dashboard')"
                            class="flex items-center gap-3 rounded-lg p-3"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="relative z-10 space-y-4 py-2">
            <div
                v-if="mainNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 100ms"
            >
                <NavMain title="Principal" :items="mainNavItems" />
            </div>

            <div
                v-if="residentsNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 200ms"
            >
                <NavMain title="Administración" :items="residentsNavItems" />
            </div>

            <div
                v-if="financeNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 300ms"
            >
                <NavMain title="Finanzas" :items="financeNavItems" />
            </div>

            <div
                v-if="accountingNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 400ms"
            >
                <NavMain title="Contabilidad" :items="accountingNavItems" />
            </div>

            <div
                v-if="communicationNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 500ms"
            >
                <NavMain title="Comunicación" :items="communicationNavItems" />
            </div>

            <div
                v-if="documentsNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 600ms"
            >
                <NavMain title="Documentos" :items="documentsNavItems" />
            </div>

            <div
                v-if="systemNavItems.length > 0"
                class="transform transition-all duration-500 ease-out"
                :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                style="transition-delay: 700ms"
            >
                <NavMain title="Sistema" :items="systemNavItems" />
            </div>
        </SidebarContent>

        <SidebarFooter
            class="relative z-10 transform transition-all duration-500 ease-out"
            :class="[isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
            style="transition-delay: 800ms"
        >
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
