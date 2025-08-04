import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Bell,
    BookOpen,
    Building2,
    Calculator,
    CreditCard,
    FileQuestion,
    FileText,
    Home,
    LayoutGrid,
    Mail,
    MessageSquare,
    Phone,
    Receipt,
    Send,
    Settings,
    Shield,
    TrendingUp,
    Truck,
    UserCheck,
    UserCog,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

export function useNavigation() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const permissions = computed(() => page.props.auth?.permissions || []);
    const roles = computed(() => page.props.auth?.roles || []);

    const hasPermission = (permission: string): boolean => {
        // Ensure permissions array exists and is not empty
        const permsArray = permissions.value || [];
        const hasPerms = Array.isArray(permsArray) && permsArray.includes(permission);

        return hasPerms;
    };

    const mainNavItems = computed((): NavItem[] => [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            tourId: 'nav-dashboard',
            visible: hasPermission('view_dashboard'),
        },
    ]);

    const residentsNavItems = computed((): NavItem[] => [
        {
            title: 'Residentes',
            href: '/residents',
            icon: Users,
            tourId: 'nav-residents',
            visible: hasPermission('view_residents'),
        },
        {
            title: 'Apartamentos',
            href: '/apartments',
            icon: Home,
            tourId: 'nav-apartments',
            visible: hasPermission('view_apartments'),
        },
        {
            title: 'Invitaciones',
            href: '/invitations',
            icon: Send,
            tourId: 'nav-invitations',
            visible: hasPermission('manage_invitations'),
        },
        {
            title: 'Config. Conjuntos',
            href: '/conjunto-config',
            icon: Building2,
            tourId: 'nav-conjunto-config',
            visible: hasPermission('view_conjunto_config'),
        },
    ]);

    const financeNavItems = computed((): NavItem[] => [
        {
            title: 'Estado de Cuenta',
            href: '/account-statement',
            icon: CreditCard,
            tourId: 'nav-account-statement',
            visible: hasPermission('view_account_statement'),
        },
        {
            title: 'Pagos',
            href: '/finance/payments',
            icon: CreditCard,
            tourId: 'nav-payments',
            visible: hasPermission('view_payments'),
        },
        {
            title: 'Facturas',
            href: '/invoices',
            icon: Receipt,
            tourId: 'nav-invoices',
            visible: hasPermission('view_payments'),
        },
        {
            title: 'Conceptos de Pago',
            href: '/payment-concepts',
            icon: Settings,
            tourId: 'nav-payment-concepts',
            visible: hasPermission('view_payments'),
        },
        {
            title: 'Acuerdos de Pago',
            href: '/payment-agreements',
            icon: FileText,
            tourId: 'nav-payment-agreements',
            visible: hasPermission('view_payments'),
        },
        {
            title: 'Proveedores',
            href: '/providers',
            icon: UserCog,
            tourId: 'nav-providers',
            visible: hasPermission('view_payments'),
        },
        {
            title: 'Propuestas de Proveedores',
            href: '/provider-proposals',
            icon: Truck,
            tourId: 'nav-provider-proposals',
            visible: hasPermission('review_provider_proposals'),
        },
        {
            title: 'Config. de Pagos',
            href: '/settings/payments',
            icon: Settings,
            tourId: 'nav-payment-settings',
            visible: hasPermission('view_payments'),
        },
    ]);

    const accountingNavItems = computed((): NavItem[] => [
        {
            title: 'Plan de Cuentas',
            href: '/accounting/chart-of-accounts',
            icon: Calculator,
            tourId: 'nav-chart-of-accounts',
            visible: hasPermission('view_accounting'),
        },
        {
            title: 'Transacciones',
            href: '/accounting/transactions',
            icon: FileText,
            tourId: 'nav-accounting-transactions',
            visible: hasPermission('view_accounting'),
        },
        {
            title: 'Presupuestos',
            href: '/accounting/budgets',
            icon: Wallet,
            tourId: 'nav-budgets',
            visible: hasPermission('view_accounting'),
        },
        {
            title: 'Reportes Contables',
            href: '/accounting/reports',
            icon: TrendingUp,
            tourId: 'nav-accounting-reports',
            visible: hasPermission('view_accounting'),
        },
    ]);

    const communicationNavItems = computed((): NavItem[] => [
        {
            title: 'Correspondencia',
            href: '/correspondence',
            icon: Mail,
            tourId: 'nav-correspondence',
            visible: hasPermission('view_announcements'),
        },
        {
            title: 'Comunicados',
            href: '/announcements',
            icon: MessageSquare,
            tourId: 'nav-announcements',
            visible: hasPermission('view_announcements'),
        },
        {
            title: 'Invitar Visitantes',
            href: '/visitor-invitations',
            icon: UserCheck,
            tourId: 'nav-visitor-invitations',
            visible: hasPermission('invite_visitors'),
        },
        {
            title: 'Notificaciones',
            href: '/notifications',
            icon: Bell,
            tourId: 'nav-notifications',
            visible: hasPermission('receive_notifications'),
        },
        {
            title: 'PQRS',
            href: '/pqrs',
            icon: FileQuestion,
            tourId: 'nav-pqrs',
            visible: hasPermission('send_pqrs'),
        },
        {
            title: 'Mensajería',
            href: '/messages',
            icon: MessageSquare,
            tourId: 'nav-messages',
            visible: hasPermission('send_messages_to_admin'),
        },
        {
            title: 'Visitas',
            href: '/visits',
            icon: UserCheck,
            tourId: 'nav-visits',
            visible: hasPermission('manage_visitors'),
        },
    ]);

    const documentsNavItems = computed((): NavItem[] => [
        {
            title: 'Documentos',
            href: '/documents',
            icon: FileText,
            tourId: 'nav-documents',
            visible: hasPermission('view_announcements'),
        },
        {
            title: 'Actas',
            href: '/minutes',
            icon: FileText,
            tourId: 'nav-minutes',
            visible: hasPermission('view_announcements'),
        },
    ]);

    const systemNavItems = computed((): NavItem[] => [
        {
            title: 'Reportes',
            href: '/reports',
            icon: BarChart3,
            tourId: 'nav-reports',
            visible: hasPermission('view_reports'),
        },
        {
            title: 'Seguridad',
            href: '/settings/security',
            icon: Shield,
            tourId: 'nav-security',
            visible: hasPermission('view_access_logs'),
        },
        {
            title: 'Configuración',
            href: '/settings',
            icon: Settings,
            tourId: 'nav-settings',
            visible: hasPermission('edit_conjunto_config'),
        },
    ]);

    const footerNavItems = computed((): NavItem[] => [
        {
            title: 'Soporte',
            href: '/support',
            icon: Phone,
            visible: true,
        },
        {
            title: 'Documentación',
            href: '/docs',
            icon: BookOpen,
            visible: true,
        },
    ]);

    // Filter visible items
    const filterVisibleItems = (items: NavItem[]): NavItem[] => items.filter((item) => item.visible === true);

    return {
        user,
        permissions,
        roles,
        hasPermission,
        mainNavItems: computed(() => filterVisibleItems(mainNavItems.value)),
        residentsNavItems: computed(() => filterVisibleItems(residentsNavItems.value)),
        financeNavItems: computed(() => filterVisibleItems(financeNavItems.value)),
        accountingNavItems: computed(() => filterVisibleItems(accountingNavItems.value)),
        communicationNavItems: computed(() => filterVisibleItems(communicationNavItems.value)),
        documentsNavItems: computed(() => filterVisibleItems(documentsNavItems.value)),
        systemNavItems: computed(() => filterVisibleItems(systemNavItems.value)),
        footerNavItems: computed(() => filterVisibleItems(footerNavItems.value)),
    };
}
