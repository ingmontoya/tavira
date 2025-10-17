import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { useFeatures } from '@/composables/useFeatures';
import {
    BarChart3,
    Bell,
    BookOpen,
    Building2,
    Calculator,
    CalendarDays,
    CheckCircle,
    Clock,
    CreditCard,
    FileQuestion,
    FileSpreadsheet,
    FileText,
    Home,
    LayoutGrid,
    Mail,
    MapPin,
    MessageSquare,
    Phone,
    QrCode,
    Receipt,
    Send,
    Settings,
    Shield,
    TrendingDown,
    TrendingUp,
    Truck,
    UserCheck,
    UserCog,
    Users,
    Vote,
    Wallet,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';

export function useNavigation() {
    const page = usePage();
    const { isFeatureEnabled } = useFeatures();
    const user = computed(() => page.props.auth?.user);
    const permissions = computed(() => page.props.auth?.permissions || []);
    const roles = computed(() => page.props.auth?.roles || []);
    const conjuntoConfigured = computed(() => page.props.conjuntoConfigured || { exists: false, isActive: false });
    
    // Check if we're in the central dashboard context
    const isCentralDashboard = computed(() => {
        // Check if we're on a central domain (not a subdomain tenant)
        const currentHost = window.location.hostname;
        const centralDomains = ['127.0.0.1', 'localhost', 'tavira.com.co'];
        const isOnCentralDomain = centralDomains.includes(currentHost);
        
        // Must be superadmin and on central domain
        return isOnCentralDomain && roles.value.includes('superadmin');
    });

    const hasPermission = (permission: string): boolean => {
        // Ensure permissions array exists and is not empty
        const permsArray = permissions.value || [];
        const hasPerms = Array.isArray(permsArray) && permsArray.includes(permission);

        return hasPerms;
    };

    // Check if navigation should be enabled (conjunto configured OR accessing allowed routes)
    const isNavigationEnabled = (item: any): boolean => {
        // Always allow dashboard and configuration routes
        const alwaysAllowedRoutes = ['/dashboard', '/conjunto-config', '/settings', '/profile', '/support', '/docs', '/tenants'];

        // Check if the item href matches any always allowed route
        if (item.href && alwaysAllowedRoutes.some((route) => item.href.startsWith(route))) {
            return true;
        }

        // For central dashboard context, always enable navigation
        if (isCentralDashboard.value) {
            return true;
        }

        // For other routes, require conjunto to be configured
        return conjuntoConfigured.value.exists;
    };

    const mainNavItems = computed((): NavItem[] => [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            tourId: 'nav-dashboard',
            visible: hasPermission('view_dashboard'),
        },
        {
            title: 'Administración',
            icon: Users,
            visible:
                hasPermission('view_residents') ||
                hasPermission('view_apartments') ||
                hasPermission('manage_invitations') ||
                hasPermission('view_conjunto_config'),
            items: [
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
                {
                    title: 'Mantenimiento',
                    icon: Wrench,
                    visible: isFeatureEnabled('maintenance_requests') && (
                        hasPermission('view_maintenance_requests') ||
                        hasPermission('view_maintenance_categories') ||
                        hasPermission('view_maintenance_staff')
                    ),
                    items: [
                        {
                            title: 'Solicitudes',
                            href: '/maintenance-requests',
                            icon: Wrench,
                            tourId: 'nav-maintenance-requests',
                            visible: hasPermission('view_maintenance_requests') && isFeatureEnabled('maintenance_requests'),
                        },
                        {
                            title: 'Categorías',
                            href: '/maintenance-categories',
                            icon: Settings,
                            tourId: 'nav-maintenance-categories',
                            visible: hasPermission('view_maintenance_categories') && isFeatureEnabled('maintenance_requests'),
                        },
                        {
                            title: 'Personal',
                            href: '/maintenance-staff',
                            icon: UserCog,
                            tourId: 'nav-maintenance-staff',
                            visible: hasPermission('view_maintenance_staff') && isFeatureEnabled('maintenance_requests'),
                        },
                        {
                            title: 'Cronograma',
                            href: '/maintenance-requests-calendar',
                            icon: Clock,
                            tourId: 'nav-maintenance-calendar',
                            visible: hasPermission('view_maintenance_requests') && isFeatureEnabled('maintenance_requests'),
                        },
                    ],
                },
            ],
        },
        {
            title: 'Asambleas',
            icon: Vote,
            visible: hasPermission('view_assemblies'),
            items: [
                {
                    title: 'Asambleas',
                    href: '/assemblies',
                    icon: Vote,
                    tourId: 'nav-assemblies',
                    visible: hasPermission('view_assemblies'),
                },
            ],
        },
        {
            title: 'Finanzas',
            icon: Wallet,
            visible: isFeatureEnabled('accounting') && (hasPermission('view_account_statement') || hasPermission('view_payments')),
            items: [
                {
                    title: 'Estado de Cuenta',
                    href: '/account-statement',
                    icon: CreditCard,
                    tourId: 'nav-account-statement',
                    visible: hasPermission('view_account_statement'),
                },
                {
                    title: 'Pagos y Cobros',
                    icon: CreditCard,
                    visible: hasPermission('view_payments'),
                    items: [
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
                            title: 'Envío de Facturas',
                            href: '/invoices/email',
                            icon: Mail,
                            tourId: 'nav-invoice-email',
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
                            visible: hasPermission('view_payments') && isFeatureEnabled('payment_agreements'),
                        },
                        {
                            title: 'Conciliación Jelpit',
                            href: '/finance/jelpit-reconciliation',
                            icon: FileSpreadsheet,
                            tourId: 'nav-jelpit-reconciliation',
                            visible: hasPermission('view_payments'),
                        },
                    ],
                },
                {
                    title: 'Gastos',
                    icon: TrendingDown,
                    visible: isFeatureEnabled('expense_approvals') && (hasPermission('view_expenses') || hasPermission('manage_expense_categories') || hasPermission('approve_expenses')),
                    items: [
                        {
                            title: 'Egresos',
                            href: '/expenses',
                            icon: TrendingDown,
                            tourId: 'nav-expenses',
                            visible: hasPermission('view_expenses'),
                        },
                        {
                            title: 'Aprobaciones',
                            href: '/expenses/approvals/dashboard',
                            icon: Clock,
                            tourId: 'nav-expenses-approvals',
                            visible: hasPermission('approve_expenses'),
                        },
                        {
                            title: 'Categorías de Gastos',
                            href: '/expense-categories',
                            icon: Settings,
                            tourId: 'nav-expense-categories',
                            visible: hasPermission('manage_expense_categories'),
                        },
                    ],
                },
                {
                    title: 'Config. de Pagos',
                    href: '/settings/payments',
                    icon: Settings,
                    tourId: 'nav-payment-settings',
                    visible: hasPermission('view_payments'),
                },
            ],
        },
        {
            title: 'Proveedores',
            icon: Truck,
            visible: isFeatureEnabled('provider_management') && (hasPermission('view_payments') || hasPermission('review_provider_proposals')),
            items: [
                {
                    title: 'Proveedores',
                    href: '/providers',
                    icon: UserCog,
                    tourId: 'nav-providers',
                    visible: hasPermission('view_payments'),
                },
                {
                    title: 'Solicitudes de Cotización',
                    href: '/quotation-requests',
                    icon: FileText,
                    tourId: 'nav-quotation-requests',
                    visible: hasPermission('view_payments'),
                },
                {
                    title: 'Propuestas',
                    href: '/provider-proposals',
                    icon: Truck,
                    tourId: 'nav-provider-proposals',
                    visible: hasPermission('review_provider_proposals'),
                },
            ],
        },
        {
            title: 'Contabilidad',
            icon: Calculator,
            visible: isFeatureEnabled('accounting') && (hasPermission('view_accounting') || hasPermission('view_payments')),
            items: [
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
                    title: 'Mapeo de Cuentas',
                    href: '/payment-method-account-mappings',
                    icon: MapPin,
                    tourId: 'nav-payment-method-account-mappings',
                    visible: hasPermission('view_payments'),
                },
                {
                    title: 'Reportes Contables',
                    href: '/accounting/reports',
                    icon: TrendingUp,
                    tourId: 'nav-accounting-reports',
                    visible: isFeatureEnabled('financial_reports') && hasPermission('view_accounting'),
                },
            ],
        },
        {
            title: 'Reservas',
            icon: CalendarDays,
            visible: isFeatureEnabled('reservations') && (hasPermission('view_reservations') || hasPermission('manage_reservable_assets')),
            items: [
                {
                    title: 'Mis Reservas',
                    href: '/reservations',
                    icon: CalendarDays,
                    tourId: 'nav-my-reservations',
                    visible: hasPermission('view_reservations'),
                },
                {
                    title: 'Nueva Reserva',
                    href: '/reservations/create',
                    icon: CalendarDays,
                    tourId: 'nav-create-reservation',
                    visible: hasPermission('view_reservations'),
                },
                {
                    title: 'Activos Reservables',
                    href: '/reservable-assets',
                    icon: Building2,
                    tourId: 'nav-reservable-assets',
                    visible: hasPermission('manage_reservable_assets'),
                },
            ],
        },

        {
            title: 'Comunicación',
            icon: MessageSquare,
            visible:
                hasPermission('view_correspondence') ||
                hasPermission('view_announcements') ||
                hasPermission('invite_visitors') ||
                hasPermission('receive_notifications') ||
                hasPermission('send_pqrs') ||
                hasPermission('send_messages_to_admin') ||
                hasPermission('manage_visitors') ||
                hasPermission('view_admin_email') ||
                hasPermission('view_council_email') ||
                hasPermission('manage_email_templates'),
            items: [
                {
                    title: 'Correspondencia',
                    href: '/correspondence',
                    icon: Mail,
                    tourId: 'nav-correspondence',
                    visible: hasPermission('view_correspondence') && isFeatureEnabled('correspondence'),
                },
                {
                    title: 'Correo Electrónico',
                    icon: Mail,
                    visible: isFeatureEnabled('institutional_email') && (hasPermission('view_admin_email') || hasPermission('view_council_email') || hasPermission('manage_email_templates')),
                    items: [
                        {
                            title: 'Correo Administración',
                            href: '/email/admin',
                            icon: Mail,
                            tourId: 'nav-email-admin',
                            visible: hasPermission('view_admin_email'),
                        },
                        {
                            title: 'Correo Concejo',
                            href: '/email/concejo',
                            icon: Mail,
                            tourId: 'nav-email-concejo',
                            visible: hasPermission('view_council_email'),
                        },
                        {
                            title: 'Plantillas de Email',
                            href: '/email-templates',
                            icon: FileText,
                            tourId: 'nav-email-templates',
                            visible: hasPermission('manage_email_templates'),
                        },
                    ],
                },
                {
                    title: 'Anuncios',
                    href: '/resident/announcements',
                    icon: MessageSquare,
                    tourId: 'nav-announcements-resident',
                    visible: isFeatureEnabled('announcements') && !hasPermission('create_announcements') && !hasPermission('edit_announcements'),
                },
                {
                    title: 'Gestionar Anuncios',
                    href: '/announcements',
                    icon: MessageSquare,
                    tourId: 'nav-announcements-admin',
                    visible: isFeatureEnabled('announcements') && (hasPermission('create_announcements') || hasPermission('edit_announcements')),
                },
                {
                    title: 'Visitantes',
                    icon: UserCheck,
                    visible: isFeatureEnabled('visitor_management') && (hasPermission('invite_visitors') || hasPermission('manage_visitors')),
                    items: [
                        {
                            title: 'Invitar Visitantes',
                            href: '/visits',
                            icon: UserCheck,
                            tourId: 'nav-visitor-invitations',
                            visible: hasPermission('invite_visitors') && isFeatureEnabled('visitor_management'),
                        },
                        {
                            title: 'Visitas',
                            href: '/visits',
                            icon: UserCheck,
                            tourId: 'nav-visits',
                            visible: hasPermission('manage_visitors') && isFeatureEnabled('visitor_management'),
                        },
                    ],
                },
                {
                    title: 'Notificaciones',
                    href: '/notifications',
                    icon: Bell,
                    tourId: 'nav-notifications',
                    visible: isFeatureEnabled('notifications') && hasPermission('receive_notifications'),
                },
                {
                    title: 'PQRS',
                    href: '/pqrs',
                    icon: FileQuestion,
                    tourId: 'nav-pqrs',
                    visible: hasPermission('manage_pqrs'),
                },
                {
                    title: 'Mensajería',
                    href: '/messages',
                    icon: MessageSquare,
                    tourId: 'nav-messages',
                    visible: isFeatureEnabled('messaging') && hasPermission('send_messages_to_admin'),
                },
            ],
        },
        {
            title: 'Documentos',
            icon: FileText,
            visible: isFeatureEnabled('documents') && hasPermission('view_announcements'),
            items: [
                {
                    title: 'Documentos',
                    href: '/documents',
                    icon: FileText,
                    tourId: 'nav-documents',
                    visible: hasPermission('view_announcements') && isFeatureEnabled('documents'),
                },
                {
                    title: 'Actas',
                    href: '/minutes',
                    icon: FileText,
                    tourId: 'nav-minutes',
                    visible: hasPermission('view_announcements') && isFeatureEnabled('meeting_minutes'),
                },
            ],
        },
        {
            title: 'Seguridad',
            icon: Shield,
            visible: (isFeatureEnabled('security_scanner') || isFeatureEnabled('access_control')) && (hasPermission('manage_visitors') || hasPermission('view_access_logs')),
            items: [
                {
                    title: 'Escáner de Visitas',
                    href: '/security/visits/scanner',
                    icon: QrCode,
                    tourId: 'nav-visit-scanner',
                    visible: isFeatureEnabled('security_scanner') && hasPermission('manage_visitors'),
                },
                {
                    title: 'Entradas Recientes',
                    href: '/security/visits/recent-entries',
                    icon: Clock,
                    tourId: 'nav-recent-entries',
                    visible: isFeatureEnabled('access_control') && hasPermission('manage_visitors'),
                },
            ],
        },
        {
            title: 'Sistema',
            icon: Settings,
            visible: hasPermission('view_reports') || hasPermission('view_access_logs') || hasPermission('edit_conjunto_config'),
            items: [
                {
                    title: 'Reportes',
                    href: '/reports',
                    icon: BarChart3,
                    tourId: 'nav-reports',
                    visible: isFeatureEnabled('advanced_reports') && hasPermission('view_reports'),
                },
                {
                    title: 'Seguridad',
                    href: '/settings/security',
                    icon: Shield,
                    tourId: 'nav-security',
                    visible: isFeatureEnabled('audit_logs') && hasPermission('view_access_logs'),
                },
                {
                    title: 'Configuración',
                    href: '/settings',
                    icon: Settings,
                    tourId: 'nav-settings',
                    visible: isFeatureEnabled('system_settings') && hasPermission('edit_conjunto_config'),
                },
            ],
        },
    ]);

    // Central dashboard navigation items (for superadmin)
    const centralNavItems = computed((): NavItem[] => [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            visible: true,
        },
        {
            title: 'Gestión de Tenants',
            icon: Building2,
            visible: true,
            items: [
                {
                    title: 'Ver Tenants',
                    href: '/tenants',
                    icon: Users,
                    visible: true,
                },
                {
                    title: 'Crear Tenant',
                    href: '/tenants/create',
                    icon: Building2,
                    visible: true,
                },
            ],
        },
        {
            title: 'Proveedores',
            icon: Truck,
            visible: true,
            items: [
                {
                    title: 'Proveedores Aprobados',
                    href: '/admin/providers',
                    icon: UserCog,
                    visible: true,
                },
                {
                    title: 'Solicitudes de Registro',
                    href: '/admin/provider-registrations',
                    icon: CheckCircle,
                    visible: true,
                },
            ],
        },
        {
            title: 'Gestión de Features',
            href: '/tenant-features',
            icon: Settings,
            visible: true,
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

    // Filter visible items recursively
    const filterVisibleItems = (items: NavItem[]): NavItem[] => {
        return items.filter((item) => {
            if (item.visible === false) return false;

            // Check if navigation is enabled for this item
            if (!isNavigationEnabled(item)) {
                // Add disabled property for styling purposes
                item.disabled = true;
            }

            if (item.items) {
                item.items = filterVisibleItems(item.items);
                // Show parent if any children are visible
                return item.items.length > 0;
            }
            return item.visible === true;
        });
    };

    return {
        user,
        permissions,
        roles,
        hasPermission,
        mainNavItems: computed(() => {
            // Use central navigation when in central dashboard context
            if (isCentralDashboard.value) {
                return filterVisibleItems(centralNavItems.value);
            }
            return filterVisibleItems(mainNavItems.value);
        }),
        footerNavItems: computed(() => filterVisibleItems(footerNavItems.value)),
    };
}
