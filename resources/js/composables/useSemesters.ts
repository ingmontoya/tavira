import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

export interface Semester {
    id?: number
    name: string
    start_date: string
    end_date: string
    created_at?: string
    updated_at?: string
    students_count?: number
}

export interface SemesterFilters {
    search?: string
    year?: string
    active?: boolean
}

export const useSemesters = () => {
    const loading = ref(false)
    const error = ref<string | null>(null)

    const getSemesters = async (filters: SemesterFilters = {}) => {
        loading.value = true
        error.value = null
        
        try {
            router.get('/semesters', filters, {
                preserveScroll: true,
                preserveState: true,
            })
        } catch (err) {
            error.value = 'Error al obtener semestres'
            console.error('Error fetching semesters:', err)
        } finally {
            loading.value = false
        }
    }

    const getSemester = async (id: number) => {
        loading.value = true
        error.value = null
        
        try {
            router.get(`/semesters/${id}`)
        } catch (err) {
            error.value = 'Error al obtener el semestre'
            console.error('Error fetching semester:', err)
        } finally {
            loading.value = false
        }
    }

    const createSemester = async (data: Omit<Semester, 'id' | 'created_at' | 'updated_at'>) => {
        loading.value = true
        error.value = null
        
        try {
            router.post('/semesters', data, {
                onSuccess: () => {
                    router.visit('/semesters')
                },
                onError: (errors) => {
                    error.value = 'Error al crear el semestre'
                    console.error('Error creating semester:', errors)
                }
            })
        } catch (err) {
            error.value = 'Error al crear el semestre'
            console.error('Error creating semester:', err)
        } finally {
            loading.value = false
        }
    }

    const updateSemester = async (id: number, data: Partial<Semester>) => {
        loading.value = true
        error.value = null
        
        try {
            router.put(`/semesters/${id}`, data, {
                onSuccess: () => {
                    router.visit('/semesters')
                },
                onError: (errors) => {
                    error.value = 'Error al actualizar el semestre'
                    console.error('Error updating semester:', errors)
                }
            })
        } catch (err) {
            error.value = 'Error al actualizar el semestre'
            console.error('Error updating semester:', err)
        } finally {
            loading.value = false
        }
    }

    const deleteSemester = async (id: number) => {
        loading.value = true
        error.value = null
        
        try {
            router.delete(`/semesters/${id}`, {
                onSuccess: () => {
                    router.visit('/semesters')
                },
                onError: (errors) => {
                    error.value = 'Error al eliminar el semestre'
                    console.error('Error deleting semester:', errors)
                }
            })
        } catch (err) {
            error.value = 'Error al eliminar el semestre'
            console.error('Error deleting semester:', err)
        } finally {
            loading.value = false
        }
    }

    const exportSemesters = async (format: 'csv' | 'excel' = 'excel') => {
        loading.value = true
        error.value = null
        
        try {
            window.location.href = `/semesters/export?format=${format}`
        } catch (err) {
            error.value = 'Error al exportar semestres'
            console.error('Error exporting semesters:', err)
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        error,
        getSemesters,
        getSemester,
        createSemester,
        updateSemester,
        deleteSemester,
        exportSemesters
    }
}