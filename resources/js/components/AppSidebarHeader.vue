<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <!-- Impersonation Banner -->
            <div v-if="$page.props.auth?.is_impersonating" class="flex items-center gap-2 px-3 py-1 bg-orange-100 text-orange-800 rounded-md text-sm">
                <Icon name="user-check" class="h-4 w-4" />
                <span class="font-medium">Super Admin en {{ $page.props.auth?.tenant_name }}</span>
                <Button 
                    variant="ghost" 
                    size="sm" 
                    @click="$inertia.post(route('stop-impersonation'))"
                    class="h-6 px-2 ml-2 text-orange-800 hover:bg-orange-200"
                >
                    Salir
                </Button>
            </div>
            <NotificationBell />
        </div>
    </header>
</template>
