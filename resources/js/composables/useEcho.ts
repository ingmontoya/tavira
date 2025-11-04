import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Laravel Echo
window.Pusher = Pusher;

/**
 * Laravel Echo instance for real-time WebSocket communication
 * Uses Laravel Reverb as the WebSocket server
 */
let echo: Echo | null = null;

/**
 * Initialize Laravel Echo singleton
 * Only creates one instance throughout the application lifecycle
 */
export function useEcho(): Echo {
    if (echo) {
        return echo;
    }

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        // Include CSRF token for authentication
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        },
    });

    return echo;
}

/**
 * Disconnect Echo and clean up resources
 * Call this when you want to explicitly disconnect from WebSocket server
 */
export function disconnectEcho(): void {
    if (echo) {
        echo.disconnect();
        echo = null;
    }
}

// Extend Window interface for TypeScript
declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}
