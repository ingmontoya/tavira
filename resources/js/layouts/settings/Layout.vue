<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useNavigation } from '@/composables/useNavigation';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { hasPermission } = useNavigation();

const sidebarNavItems = computed((): NavItem[] =>
    [
        {
            title: 'Perfil',
            href: '/settings/profile',
            visible: true,
        },
        {
            title: 'Contraseña',
            href: '/settings/password',
            visible: true,
        },
        {
            title: 'Seguridad',
            href: '/settings/security',
            visible: true,
        },
        {
            title: 'Correo Electrónico',
            href: '/settings/email',
            visible: hasPermission('edit_conjunto_config'),
        },
        {
            title: 'Apariencia',
            href: '/settings/appearance',
            visible: true,
        },
        {
            title: 'Pagos',
            href: '/settings/payments',
            visible: hasPermission('view_payments'),
        },
        {
            title: 'Gastos',
            href: '/settings/expenses',
            visible: hasPermission('manage_expenses'),
        },
        {
            title: 'Usuarios',
            href: '/settings/users',
            visible: hasPermission('view_users'),
        },
        {
            title: 'Permisos',
            href: '/settings/permissions',
            visible: hasPermission('edit_users'),
        },
        {
            title: 'Configuración Contable',
            href: '/settings/accounting',
            visible: hasPermission('manage_accounting'),
        },
        {
            title: 'Mapeo Contable',
            href: '/settings/payment-concept-mapping',
            visible: hasPermission('manage_accounting'),
        },
        {
            title: 'Configuración DIAN',
            href: '/settings/dian',
            visible: hasPermission('edit_conjunto_config'),
        },
    ].filter((item) => item.visible !== false),
);

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading :title="'Configuración'" :description="'Administra tu perfil y la configuración de la cuenta'" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 lg:max-w-6xl">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
