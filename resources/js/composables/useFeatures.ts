import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed } from 'vue';

interface TenantFeature {
    id: number;
    tenant_id: string;
    feature: string;
    enabled: boolean;
    created_at: string;
    updated_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    tenant_id?: string;
    // ... other user properties
}

interface PageProps {
    auth: {
        user: User;
    };
    tenant?: {
        id: string;
        features?: TenantFeature[];
    };
    // ... other page properties
}

export function useFeatures() {
    const page = usePage<PageProps>();

    // Get current tenant ID from user or tenant context
    const tenantId = computed(() => {
        return page.props.tenant?.id || page.props.auth.user.tenant_id;
    });

    // Get tenant features from page props
    const tenantFeatures = computed(() => {
        return page.props.tenant?.features || [];
    });

    /**
     * Check if a specific feature is enabled for the current tenant
     */
    const isFeatureEnabled = (feature: string): boolean => {
        if (!tenantId.value) return false;

        const featureRecord = tenantFeatures.value.find((f) => f.feature === feature);
        return featureRecord?.enabled ?? false;
    };

    /**
     * Get all enabled features for the current tenant
     */
    const getEnabledFeatures = computed(() => {
        return tenantFeatures.value.filter((f) => f.enabled).map((f) => f.feature);
    });

    /**
     * Get count of enabled features
     */
    const getEnabledFeaturesCount = computed(() => {
        return tenantFeatures.value.filter((f) => f.enabled).length;
    });

    /**
     * Check if tenant has any features enabled
     */
    const hasAnyFeatures = computed(() => {
        return getEnabledFeaturesCount.value > 0;
    });

    /**
     * Fetch fresh feature status from API (useful for real-time checks)
     */
    const fetchFeatureStatus = async (feature: string): Promise<boolean> => {
        if (!tenantId.value) return false;

        try {
            const response = await axios.get(`/api/features/${tenantId.value}/${feature}`);
            return response.data.enabled;
        } catch (error) {
            console.error('Failed to fetch feature status:', error);
            return false;
        }
    };

    /**
     * Fetch all features for the current tenant
     */
    const fetchAllFeatures = async (): Promise<Record<string, boolean>> => {
        if (!tenantId.value) return {};

        try {
            const response = await axios.get(`/api/features/${tenantId.value}`);
            return response.data.features;
        } catch (error) {
            console.error('Failed to fetch tenant features:', error);
            return {};
        }
    };

    /**
     * Feature gate function - throws error if feature is not enabled
     */
    const requireFeature = (feature: string): void => {
        if (!isFeatureEnabled(feature)) {
            throw new Error(`Feature '${feature}' is not enabled for this tenant`);
        }
    };

    /**
     * Get feature labels (human-readable names)
     */
    const getFeatureLabel = (feature: string): string => {
        const labels: Record<string, string> = {
            // Comunicación y Notificaciones
            correspondence: 'Correspondencia',
            announcements: 'Anuncios',
            support_tickets: 'Tickets de Soporte / PQRS',
            notifications: 'Notificaciones Push',
            institutional_email: 'Correo Institucional',
            messaging: 'Mensajería Interna',

            // Administración Básica
            basic_administration: 'Administración Básica',
            resident_management: 'Gestión de Residentes',
            apartment_management: 'Gestión de Apartamentos',

            // Mantenimiento
            maintenance_requests: 'Solicitudes de Mantenimiento',

            // Gestión de Visitantes y Seguridad
            visitor_management: 'Gestión de Visitantes',
            security_scanner: 'Escáner de Seguridad QR',
            access_control: 'Control de Acceso',

            // Finanzas y Contabilidad
            accounting: 'Contabilidad Completa',
            payment_agreements: 'Acuerdos de Pago',
            expense_approvals: 'Aprobaciones de Gastos',
            financial_reports: 'Reportes Financieros',
            provider_management: 'Gestión de Proveedores',

            // Reservas y Espacios Comunes
            reservations: 'Reservas de Espacios',

            // Documentos y Actas
            documents: 'Gestión de Documentos',
            meeting_minutes: 'Actas de Reuniones',

            // Reportes y Análisis
            advanced_reports: 'Reportes Avanzados',
            analytics_dashboard: 'Dashboard de Análisis',

            // Configuración Avanzada
            system_settings: 'Configuración del Sistema',
            audit_logs: 'Auditoría y Logs',
            bulk_operations: 'Operaciones Masivas',
        };
        return labels[feature] || feature;
    };

    return {
        // Computed properties
        tenantId: computed(() => tenantId.value),
        tenantFeatures: computed(() => tenantFeatures.value),
        getEnabledFeatures,
        getEnabledFeaturesCount,
        hasAnyFeatures,

        // Methods
        isFeatureEnabled,
        fetchFeatureStatus,
        fetchAllFeatures,
        requireFeature,
        getFeatureLabel,
    };
}
