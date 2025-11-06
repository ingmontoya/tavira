import { useEcho } from './useEcho';
import type { Channel } from 'laravel-echo';
import { onMounted, onUnmounted, ref } from 'vue';

export interface SecurityAlert {
    id: string;
    type: 'panic' | 'security' | 'emergency';
    message: string;
    user_name: string;
    apartment: string;
    location?: string;
    created_at: string;
    status: 'triggered' | 'acknowledged' | 'resolved';
    severity: 'low' | 'medium' | 'high' | 'critical';
}

export interface PanicAlertEvent {
    alert_id: string | number;
    user: {
        id: number;
        name: string;
    };
    apartment: {
        id?: number;
        address: string;
    };
    location: {
        lat?: number;
        lng?: number;
        string: string;
    };
    status: 'triggered' | 'confirmed' | 'resolved' | 'cancelled';
    timestamp: string;
    time_ago: string;
}

/**
 * Composable for managing panic alerts via WebSockets
 * Provides real-time updates without polling
 */
export function usePanicAlerts() {
    const activeAlerts = ref<SecurityAlert[]>([]);
    const isLoading = ref(false);
    const isConnected = ref(false);

    let securityChannel: Channel | null = null;

    /**
     * Fetch initial active alerts from API (fallback/bootstrap)
     * This runs once on mount to get current state
     */
    const fetchActiveAlerts = async () => {
        try {
            isLoading.value = true;
            const response = await fetch('/api/security/alerts/active', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                activeAlerts.value = data.alerts || [];
            } else if (response.status === 401 || response.status === 403) {
                // User not authenticated or no permissions - silently set empty array
                activeAlerts.value = [];
            }
        } catch (error) {
            // Silently fail - WebSocket will handle real-time updates if user gets permissions later
            activeAlerts.value = [];
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Connect to WebSocket channel and listen for panic alert events
     */
    const connectToWebSocket = () => {
        try {
            const echo = useEcho();

            // Listen to private security-dashboard channel
            securityChannel = echo.private('security-dashboard');

            // Listen for new panic alerts
            securityChannel.listen('.panic-alert.triggered', (event: PanicAlertEvent) => {
                console.log('🚨 New panic alert received via WebSocket:', event);

                // Transform the event to match SecurityAlert interface
                const newAlert: SecurityAlert = {
                    id: String(event.alert_id),
                    type: 'panic',
                    message: 'Alerta de pánico activada',
                    user_name: event.user.name,
                    apartment: event.apartment.address,
                    location: event.location.string,
                    created_at: event.timestamp,
                    status: event.status as SecurityAlert['status'],
                    severity: 'critical',
                };

                // Check if alert already exists
                const existingIndex = activeAlerts.value.findIndex(alert => alert.id === newAlert.id);

                if (existingIndex === -1) {
                    // Add new alert at the beginning
                    activeAlerts.value.unshift(newAlert);
                } else {
                    // Update existing alert
                    activeAlerts.value[existingIndex] = newAlert;
                }
            });

            // Listen for alert status updates
            securityChannel.listen('.panic-alert.updated', (event: PanicAlertEvent) => {
                console.log('📝 Panic alert updated via WebSocket:', event);

                const alertId = String(event.alert_id);
                const existingIndex = activeAlerts.value.findIndex(alert => alert.id === alertId);

                if (existingIndex !== -1) {
                    if (event.status === 'resolved' || event.status === 'cancelled') {
                        // Remove resolved/cancelled alerts
                        activeAlerts.value.splice(existingIndex, 1);
                    } else {
                        // Update alert status
                        activeAlerts.value[existingIndex].status = event.status as SecurityAlert['status'];
                    }
                }
            });

            // Listen for connection events
            securityChannel.subscribed(() => {
                console.log('✅ Connected to security-dashboard channel');
                isConnected.value = true;
            });

            securityChannel.error((error: any) => {
                console.error('❌ WebSocket error:', error);
                isConnected.value = false;
            });

        } catch (error) {
            console.error('Error connecting to WebSocket:', error);
            isConnected.value = false;
        }
    };

    /**
     * Disconnect from WebSocket channel
     */
    const disconnectFromWebSocket = () => {
        if (securityChannel) {
            securityChannel.stopListening('.panic-alert.triggered');
            securityChannel.stopListening('.panic-alert.updated');

            const echo = useEcho();
            echo.leave('security-dashboard');

            securityChannel = null;
            isConnected.value = false;
        }
    };

    /**
     * Initialize: fetch initial data and connect to WebSocket
     */
    const init = async () => {
        // Fetch initial alerts
        await fetchActiveAlerts();

        // Connect to WebSocket for real-time updates
        connectToWebSocket();
    };

    /**
     * Cleanup on component unmount
     */
    const cleanup = () => {
        disconnectFromWebSocket();
    };

    return {
        activeAlerts,
        isLoading,
        isConnected,
        init,
        cleanup,
        fetchActiveAlerts, // Expose for manual refresh if needed
    };
}
