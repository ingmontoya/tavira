<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { 
    BookOpen, 
    LayoutGrid, 
    Users, 
    DollarSign, 
    Package, 
    UserCheck, 
    MessageSquare, 
    FileText, 
    UserCog, 
    BarChart3, 
    Smartphone, 
    Settings, 
    Shield, 
    Building2, 
    Home, 
    Mail, 
    Phone, 
    Car, 
    Bell
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        tourId: 'nav-dashboard',
    },
];

const residentsNavItems: NavItem[] = [
    {
        title: 'Residentes',
        href: '/residents',
        icon: Users,
        tourId: 'nav-residents',
    },
    {
        title: 'Apartamentos',
        href: '/apartments',
        icon: Home,
        tourId: 'nav-apartments',
    },
    {
        title: 'Config. Conjuntos',
        href: '/conjunto-config',
        icon: Building2,
        tourId: 'nav-conjunto-config',
    },
];

const financeNavItems: NavItem[] = [
    {
        title: 'Finanzas',
        href: '/finances',
        icon: DollarSign,
        tourId: 'nav-finances',
    },
    {
        title: 'Cuotas',
        href: '/fees',
        icon: FileText,
        tourId: 'nav-fees',
    },
    {
        title: 'Pagos',
        href: '/payments',
        icon: DollarSign,
        tourId: 'nav-payments',
    },
    {
        title: 'Proveedores',
        href: '/providers',
        icon: UserCog,
        tourId: 'nav-providers',
    },
];

const communicationNavItems: NavItem[] = [
    {
        title: 'Correspondencia',
        href: '/correspondence',
        icon: Mail,
        tourId: 'nav-correspondence',
    },
    {
        title: 'Comunicados',
        href: '/announcements',
        icon: MessageSquare,
        tourId: 'nav-announcements',
    },
    {
        title: 'Visitas',
        href: '/visits',
        icon: UserCheck,
        tourId: 'nav-visits',
    },
];

const documentsNavItems: NavItem[] = [
    {
        title: 'Documentos',
        href: '/documents',
        icon: FileText,
        tourId: 'nav-documents',
    },
    {
        title: 'Actas',
        href: '/minutes',
        icon: FileText,
        tourId: 'nav-minutes',
    },
];

const systemNavItems: NavItem[] = [
    {
        title: 'Reportes',
        href: '/reports',
        icon: BarChart3,
        tourId: 'nav-reports',
    },
    {
        title: 'Seguridad',
        href: '/security',
        icon: Shield,
        tourId: 'nav-security',
    },
    {
        title: 'Configuración',
        href: '/settings',
        icon: Settings,
        tourId: 'nav-settings',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Soporte',
        href: '/support',
        icon: Phone,
    },
    {
        title: 'Documentación',
        href: '/docs',
        icon: BookOpen,
    },
];
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
            <NavMain title="Principal" :items="mainNavItems" />
            <NavMain title="Administración" :items="residentsNavItems" />
            <NavMain title="Finanzas" :items="financeNavItems" />
            <NavMain title="Comunicación" :items="communicationNavItems" />
            <NavMain title="Documentos" :items="documentsNavItems" />
            <NavMain title="Sistema" :items="systemNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
