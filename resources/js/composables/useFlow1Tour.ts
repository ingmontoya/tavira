export interface TourStep {
    id: string;
    title: string;
    description: string;
    element?: string;
    position?: 'top' | 'bottom' | 'left' | 'right';
    action?: () => void;
}

export function useFlow1Tour() {
    const tourSteps: TourStep[] = [
        {
            id: 'welcome',
            title: '¡Bienvenido al Tour Guiado!',
            description:
                'Te guiaremos para configurar completamente el Sistema SIA. Crearemos el programa "Ingeniería de Sistemas" siguiendo el Flujo 1: Configuración Manual.',
            element: '[data-tour="dashboard"]',
            position: 'bottom',
        },
        {
            id: 'step-1-programs',
            title: 'Paso 1.1: Crear Programas',
            description:
                'Comenzamos creando programas académicos. HAZ CLIC en "Programas" en el menú lateral (el tour avanzará automáticamente cuando hagas clic).',
            element: '[data-tour="nav-programs"]',
            position: 'right',
        },
        {
            id: 'programs-new-button',
            title: 'Crear Nuevo Programa',
            description: 'Perfecto! Ahora HAZ CLIC en el botón "Nuevo Programa" para ir al formulario (el tour continuará automáticamente).',
            element: '[data-tour="new-program-btn"]',
            position: 'bottom',
        },
        {
            id: 'program-form-step',
            title: 'Formulario de Programa',
            description:
                'Excelente! Estás en el formulario. Completa los datos: Nombre: "Ingeniería de Sistemas", Semestres: 10, Estado: Activo y HAZ CLIC en "Crear Programa".',
            position: 'bottom',
        },
        {
            id: 'step-1-periods',
            title: 'Paso 1.2: Períodos Académicos',
            description: 'Programa creado exitosamente! Ahora HAZ CLIC en "Períodos Académicos" en el menú.',
            element: '[data-tour="nav-periods"]',
            position: 'right',
        },
        {
            id: 'periods-new-button',
            title: 'Crear Período Académico',
            description: 'Crea el período "2024-2" del 15/08/2024 al 15/12/2024 como período activo.',
            element: '[data-tour="new-period-btn"]',
            position: 'bottom',
        },
        {
            id: 'step-1-study-plans',
            title: 'Paso 1.3: Crear Plan de Estudios',
            description: 'Crearemos el plan de estudios para Ingeniería de Sistemas. Navega a Planes de Estudio.',
            element: '[data-tour="nav-study-plans"]',
            position: 'right',
        },
        {
            id: 'study-plans-new-button',
            title: 'Crear Plan de Estudios',
            description: 'Crea el "Plan de Estudios Ingeniería de Sistemas 2024" con 160 créditos totales y 10 semestres de duración.',
            element: '[data-tour="new-study-plan-btn"]',
            position: 'bottom',
        },
        {
            id: 'step-1-subjects',
            title: 'Paso 1.4: Crear Asignaturas',
            description: 'Ahora crearemos las asignaturas del primer semestre. Navega a la sección de Asignaturas.',
            element: '[data-tour="nav-subjects"]',
            position: 'right',
        },
        {
            id: 'subjects-new-button',
            title: 'Crear Asignaturas',
            description:
                'Crea las asignaturas: MAT101-Cálculo Diferencial (4 créditos), PRG101-Programación I (4 créditos), FIS101-Física I (4 créditos), ALG101-Álgebra Lineal (3 créditos).',
            element: '[data-tour="new-subject-btn"]',
            position: 'bottom',
        },
        {
            id: 'step-1-groups',
            title: 'Paso 1.5: Crear Grupos',
            description: 'Crearemos los grupos para cada asignatura. Navega a la sección de Grupos.',
            element: '[data-tour="nav-groups"]',
            position: 'right',
        },
        {
            id: 'groups-new-button',
            title: 'Crear Grupos',
            description:
                'Crea grupos para cada asignatura (ej: Cálculo Diferencial - Grupo A, con Dr. Carlos Mendoza, Aula 201, horario Lunes y Miércoles 8:00-10:00, capacidad 30).',
            element: '[data-tour="new-group-btn"]',
            position: 'bottom',
        },
        {
            id: 'step-2-students',
            title: 'Paso 2: Registro de Estudiantes',
            description: 'Ahora registraremos a la estudiante Ana García Rodríguez. Navega a la sección de Estudiantes.',
            element: '[data-tour="nav-students"]',
            position: 'right',
        },
        {
            id: 'students-new-button',
            title: 'Registrar Estudiante',
            description:
                'Registra a Ana García Rodríguez (código 2024001, CC 1234567890, ana.garcia@modulyx.edu.co) en Ingeniería de Sistemas, Semestre 1.',
            element: '[data-tour="new-student-btn"]',
            position: 'bottom',
        },
        {
            id: 'step-3-enrollments',
            title: 'Paso 3: Proceso de Matrículas',
            description: 'Finalmente matricularemos a Ana en las asignaturas del semestre. Navega a Matrículas.',
            element: '[data-tour="nav-enrollments"]',
            position: 'right',
        },
        {
            id: 'enrollments-new-button',
            title: 'Matricular Estudiante',
            description:
                'Matricula a Ana García en todas las asignaturas del primer semestre: Cálculo Diferencial, Programación I, Física I y Álgebra Lineal.',
            element: '[data-tour="new-enrollment-btn"]',
            position: 'bottom',
        },
        {
            id: 'step-4-dashboard',
            title: 'Paso 4: Seguimiento y Gestión',
            description: 'Regresa al Dashboard para verificar las métricas actualizadas y gestionar el sistema.',
            element: '[data-tour="nav-dashboard"]',
            position: 'right',
        },
        {
            id: 'dashboard-metrics',
            title: '¡Configuración Completa!',
            description:
                'Has completado exitosamente la configuración manual del Sistema SIA. Puedes ver las métricas actualizadas y gestionar el sistema desde aquí.',
            element: '[data-tour="dashboard-metrics"]',
            position: 'bottom',
        },
    ];

    return {
        tourSteps,
    };
}
