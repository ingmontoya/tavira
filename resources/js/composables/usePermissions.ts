import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    // Get permissions from shared Inertia data
    const permissions = computed(() => {
        return page.props.auth?.permissions || [];
    });

    // Get roles from shared Inertia data
    const roles = computed(() => {
        return page.props.auth?.roles || [];
    });

    /**
     * Check if user has a specific permission
     */
    const hasPermission = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    /**
     * Check if user has any of the specified permissions
     */
    const hasAnyPermission = (permissionList: string[]): boolean => {
        return permissionList.some(permission => hasPermission(permission));
    };

    /**
     * Check if user has all of the specified permissions
     */
    const hasAllPermissions = (permissionList: string[]): boolean => {
        return permissionList.every(permission => hasPermission(permission));
    };

    /**
     * Check if user has a specific role
     */
    const hasRole = (role: string): boolean => {
        return roles.value.includes(role);
    };

    /**
     * Check if user has any of the specified roles
     */
    const hasAnyRole = (roleList: string[]): boolean => {
        return roleList.some(role => hasRole(role));
    };

    /**
     * Check if user has all of the specified roles
     */
    const hasAllRoles = (roleList: string[]): boolean => {
        return roleList.every(role => hasRole(role));
    };

    /**
     * Check if user is superadmin
     */
    const isSuperAdmin = computed(() => hasRole('superadmin'));

    /**
     * Check if user is admin_conjunto
     */
    const isAdminConjunto = computed(() => hasRole('admin_conjunto'));

    /**
     * Check if user is consejo (council)
     */
    const isConsejo = computed(() => hasRole('consejo'));

    /**
     * Check if user is porteria (security/doorman)
     */
    const isPorteria = computed(() => hasRole('porteria'));

    /**
     * Check if user is propietario (owner)
     */
    const isPropietario = computed(() => hasRole('propietario'));

    /**
     * Check if user is residente (resident)
     */
    const isResidente = computed(() => hasRole('residente'));

    /**
     * Check if user can view security alerts
     */
    const canViewSecurityAlerts = computed(() =>
        hasAnyPermission([
            'view_security_alerts',
            'view_panic_alerts',
            'manage_security_alerts'
        ]) || hasAnyRole(['superadmin', 'admin_conjunto', 'consejo', 'porteria'])
    );

    /**
     * Check if user can manage security alerts
     */
    const canManageSecurityAlerts = computed(() =>
        hasAnyPermission([
            'manage_security_alerts',
            'respond_to_panic_alerts',
            'resolve_panic_alerts'
        ]) || hasAnyRole(['superadmin', 'admin_conjunto', 'porteria'])
    );

    /**
     * Check if user can resolve security incidents
     */
    const canResolveSecurityIncidents = computed(() =>
        hasAnyPermission([
            'resolve_security_incidents',
            'resolve_panic_alerts'
        ]) || hasAnyRole(['superadmin', 'admin_conjunto', 'porteria'])
    );

    return {
        // State
        permissions,
        roles,

        // Permission checks
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,

        // Role checks
        hasRole,
        hasAnyRole,
        hasAllRoles,

        // Role helpers
        isSuperAdmin,
        isAdminConjunto,
        isConsejo,
        isPorteria,
        isPropietario,
        isResidente,

        // Security-specific permissions
        canViewSecurityAlerts,
        canManageSecurityAlerts,
        canResolveSecurityIncidents,
    };
}