import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

export interface Student {
    id?: number
    student_code: string
    first_name: string
    last_name: string
    document_id: string
    gender: string
    birth_date: string
    personal_email: string
    institutional_email: string
    phone: string
    group: string
    program_id: number
    current_semester_id: number
    credits_completed: number
    total_credits: number
    progress_rate: number
    dpto: string
    city: string
    address: string
    initial_status: string
    country: string
    created_at?: string
    updated_at?: string
    program?: {
        id: number
        name: string
    }
    current_semester?: {
        id: number
        name: string
        number: number
    }
}

export interface StudentFilters {
    search?: string
    program_id?: number
    semester_id?: number
    status?: string
    group?: string
}

export const useStudents = () => {
    const loading = ref(false)
    const error = ref<string | null>(null)

    const getStudents = async (filters: StudentFilters = {}) => {
        loading.value = true
        error.value = null
        
        try {
            router.get('/students', filters, {
                preserveScroll: true,
                preserveState: true,
            })
        } catch (err) {
            error.value = 'Error al obtener estudiantes'
            console.error('Error fetching students:', err)
        } finally {
            loading.value = false
        }
    }

    const getStudent = async (id: number) => {
        loading.value = true
        error.value = null
        
        try {
            router.get(`/students/${id}`)
        } catch (err) {
            error.value = 'Error al obtener el estudiante'
            console.error('Error fetching student:', err)
        } finally {
            loading.value = false
        }
    }

    const createStudent = async (data: Omit<Student, 'id' | 'created_at' | 'updated_at'>) => {
        loading.value = true
        error.value = null
        
        try {
            router.post('/students', data, {
                onSuccess: () => {
                    router.visit('/students')
                },
                onError: (errors) => {
                    error.value = 'Error al crear el estudiante'
                    console.error('Error creating student:', errors)
                }
            })
        } catch (err) {
            error.value = 'Error al crear el estudiante'
            console.error('Error creating student:', err)
        } finally {
            loading.value = false
        }
    }

    const updateStudent = async (id: number, data: Partial<Student>) => {
        loading.value = true
        error.value = null
        
        try {
            router.put(`/students/${id}`, data, {
                onSuccess: () => {
                    router.visit('/students')
                },
                onError: (errors) => {
                    error.value = 'Error al actualizar el estudiante'
                    console.error('Error updating student:', errors)
                }
            })
        } catch (err) {
            error.value = 'Error al actualizar el estudiante'
            console.error('Error updating student:', err)
        } finally {
            loading.value = false
        }
    }

    const deleteStudent = async (id: number) => {
        loading.value = true
        error.value = null
        
        try {
            router.delete(`/students/${id}`, {
                onSuccess: () => {
                    router.visit('/students')
                },
                onError: (errors) => {
                    error.value = 'Error al eliminar el estudiante'
                    console.error('Error deleting student:', errors)
                }
            })
        } catch (err) {
            error.value = 'Error al eliminar el estudiante'
            console.error('Error deleting student:', err)
        } finally {
            loading.value = false
        }
    }

    const exportStudents = async (format: 'csv' | 'excel' = 'excel') => {
        loading.value = true
        error.value = null
        
        try {
            window.location.href = `/students/export?format=${format}`
        } catch (err) {
            error.value = 'Error al exportar estudiantes'
            console.error('Error exporting students:', err)
        } finally {
            loading.value = false
        }
    }

    const importStudents = async (file: File) => {
        loading.value = true
        error.value = null
        
        const formData = new FormData()
        formData.append('file', file)
        
        try {
            return new Promise((resolve, reject) => {
                router.post('/students/import', formData, {
                    forceFormData: true,
                    onSuccess: (page) => {
                        router.visit('/students')
                        resolve(page)
                    },
                    onError: (errors) => {
                        error.value = 'Error al importar estudiantes'
                        console.error('Error importing students:', errors)
                        reject(errors)
                    },
                    onFinish: () => {
                        loading.value = false
                    }
                })
            })
        } catch (err) {
            error.value = 'Error al importar estudiantes'
            console.error('Error importing students:', err)
            loading.value = false
            throw err
        }
    }

    const downloadImportTemplate = () => {
        window.location.href = '/students/import/template'
    }

    return {
        loading,
        error,
        getStudents,
        getStudent,
        createStudent,
        updateStudent,
        deleteStudent,
        exportStudents,
        importStudents,
        downloadImportTemplate
    }
}