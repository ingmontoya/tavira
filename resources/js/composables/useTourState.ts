import { onMounted, ref } from 'vue';

const TOUR_STORAGE_KEY = 'sia-tour-state';

export function useTourState() {
    const hasSavedTour = ref(false);
    const savedTourStep = ref(0);

    const checkSavedTour = () => {
        try {
            const saved = localStorage.getItem(TOUR_STORAGE_KEY);
            if (saved) {
                const state = JSON.parse(saved);
                if (state.tourId === 'flow1-manual-config' && state.isActive) {
                    hasSavedTour.value = true;
                    savedTourStep.value = state.currentStepIndex;
                    return true;
                }
            }
        } catch (error) {
            console.warn('Error checking tour state:', error);
        }
        hasSavedTour.value = false;
        savedTourStep.value = 0;
        return false;
    };

    // Escuchar cambios en localStorage desde otras pestañas/ventanas
    const handleStorageChange = (e: StorageEvent) => {
        if (e.key === TOUR_STORAGE_KEY) {
            checkSavedTour();
        }
    };

    const clearTourState = () => {
        localStorage.removeItem(TOUR_STORAGE_KEY);
        hasSavedTour.value = false;
        savedTourStep.value = 0;
    };

    const goToDashboard = () => {
        window.location.href = '/dashboard';
    };

    const continueTourOnCurrentPage = () => {
        // Buscar el componente VirtualTour en la página actual
        const tourElements = document.querySelectorAll('.virtual-tour');
        if (tourElements.length > 0) {
            // Si hay un tour activo en la página actual, no hacer nada
            return;
        }

        // Si no hay tour activo, redirigir al dashboard para continuar
        goToDashboard();
    };

    let interval: number | null = null;

    onMounted(() => {
        checkSavedTour();
        window.addEventListener('storage', handleStorageChange);

        // Verificar menos frecuentemente para evitar problemas
        interval = setInterval(checkSavedTour, 5000);
    });

    // Limpiar al desmontar
    const cleanup = () => {
        window.removeEventListener('storage', handleStorageChange);
        if (interval) {
            clearInterval(interval);
        }
    };

    return {
        hasSavedTour,
        savedTourStep,
        checkSavedTour,
        clearTourState,
        goToDashboard,
        continueTourOnCurrentPage,
        cleanup,
    };
}
