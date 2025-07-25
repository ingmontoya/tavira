import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export interface Program {
    id?: number;
    name: string;
    total_semesters: number;
    status?: string;
    created_at?: string;
    updated_at?: string;
    students_count?: number;
}

export interface ProgramFilters {
    search?: string;
    status?: string;
}

export const usePrograms = () => {
    const loading = ref(false);
    const error = ref<string | null>(null);

    const getPrograms = async (filters: ProgramFilters = {}) => {
        loading.value = true;
        error.value = null;

        try {
            router.get('/programs', filters, {
                preserveScroll: true,
                preserveState: true,
            });
        } catch (err) {
            error.value = 'Error al obtener programas';
            console.error('Error fetching programs:', err);
        } finally {
            loading.value = false;
        }
    };

    const getProgram = async (id: number) => {
        loading.value = true;
        error.value = null;

        try {
            router.get(`/programs/${id}`);
        } catch (err) {
            error.value = 'Error al obtener el programa';
            console.error('Error fetching program:', err);
        } finally {
            loading.value = false;
        }
    };

    const createProgram = async (data: Omit<Program, 'id' | 'created_at' | 'updated_at'>) => {
        loading.value = true;
        error.value = null;

        try {
            router.post('/programs', data, {
                onSuccess: () => {
                    router.visit('/programs');
                },
                onError: (errors) => {
                    error.value = 'Error al crear el programa';
                    console.error('Error creating program:', errors);
                },
            });
        } catch (err) {
            error.value = 'Error al crear el programa';
            console.error('Error creating program:', err);
        } finally {
            loading.value = false;
        }
    };

    const updateProgram = async (id: number, data: Partial<Program>) => {
        loading.value = true;
        error.value = null;

        try {
            router.put(`/programs/${id}`, data, {
                onSuccess: () => {
                    router.visit('/programs');
                },
                onError: (errors) => {
                    error.value = 'Error al actualizar el programa';
                    console.error('Error updating program:', errors);
                },
            });
        } catch (err) {
            error.value = 'Error al actualizar el programa';
            console.error('Error updating program:', err);
        } finally {
            loading.value = false;
        }
    };

    const deleteProgram = async (id: number) => {
        loading.value = true;
        error.value = null;

        try {
            router.delete(`/programs/${id}`, {
                onSuccess: () => {
                    router.visit('/programs');
                },
                onError: (errors) => {
                    error.value = 'Error al eliminar el programa';
                    console.error('Error deleting program:', errors);
                },
            });
        } catch (err) {
            error.value = 'Error al eliminar el programa';
            console.error('Error deleting program:', err);
        } finally {
            loading.value = false;
        }
    };

    const exportPrograms = async (format: 'csv' | 'excel' = 'excel') => {
        loading.value = true;
        error.value = null;

        try {
            window.location.href = `/programs/export?format=${format}`;
        } catch (err) {
            error.value = 'Error al exportar programas';
            console.error('Error exporting programs:', err);
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        getPrograms,
        getProgram,
        createProgram,
        updateProgram,
        deleteProgram,
        exportPrograms,
    };
};
