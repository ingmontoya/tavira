<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import ImportNotification from '@/components/ImportNotification.vue';
import StudentImportDialog from '@/components/StudentImportDialog.vue';
import TourContinueButton from '@/components/TourContinueButton.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import VirtualTour from '@/components/VirtualTour.vue';
import { useFlow1Tour } from '@/composables/useFlow1Tour';
import { useTourState } from '@/composables/useTourState';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, ExpandedState, SortingState, VisibilityState } from '@tanstack/vue-table';
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ChevronDown, ChevronsUpDown, Download, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, onMounted, ref } from 'vue';
import { cn, valueUpdater } from '../../utils';

export interface Student {
    student_code: string;
    first_name: string;
    last_name: string;
    document_id: string;
    gender: string;
    birth_date: string;
    personal_email: string;
    institutional_email: string;
    phone: string;
    group: string;
    program_id: number;
    current_semester_id: number;
    credits_completed: number;
    total_credits: number;
    progress_rate: number;
    program?: {
        id: number;
        name: string;
    };
}
const props = defineProps<{
    students: Student[];
    programs?: Array<{ id: number; name: string }>;
}>();

const data: Student[] = props.students;

// Custom filters state
const customFilters = ref({
    search: '',
    program: 'all',
    gender: 'all',
    progressRange: 'all',
    group: 'all',
});

// Computed values for filter options
const uniquePrograms = computed(() => {
    if (!data.length) return [];
    const programs = data.map((student) => student.program).filter((program) => program);
    return [...new Map(programs.map((p) => [p.id, p])).values()].sort((a, b) => a.name.localeCompare(b.name));
});

const uniqueGroups = computed(() => {
    if (!data.length) return [];
    const groups = [...new Set(data.map((student) => student.group).filter(Boolean))];
    return groups.sort();
});

const uniqueGenders = computed(() => {
    if (!data.length) return [];
    const genders = [...new Set(data.map((student) => student.gender).filter(Boolean))];
    return genders.sort();
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        program: 'all',
        gender: 'all',
        progressRange: 'all',
        group: 'all',
    };
    // Also clear table filters
    table.getColumn('first_name')?.setFilterValue('');
};

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = data;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (student) =>
                student.first_name?.toLowerCase().includes(searchTerm) ||
                student.last_name?.toLowerCase().includes(searchTerm) ||
                student.student_code?.toLowerCase().includes(searchTerm) ||
                student.personal_email?.toLowerCase().includes(searchTerm) ||
                student.institutional_email?.toLowerCase().includes(searchTerm),
        );
    }

    // Program filter
    if (customFilters.value.program !== 'all') {
        filtered = filtered.filter((student) => student.program?.id.toString() === customFilters.value.program);
    }

    // Gender filter
    if (customFilters.value.gender !== 'all') {
        filtered = filtered.filter((student) => student.gender === customFilters.value.gender);
    }

    // Progress range filter
    if (customFilters.value.progressRange !== 'all') {
        filtered = filtered.filter((student) => {
            const progress = student.progress_rate;
            switch (customFilters.value.progressRange) {
                case 'low':
                    return progress < 25;
                case 'medium-low':
                    return progress >= 25 && progress < 50;
                case 'medium-high':
                    return progress >= 50 && progress < 75;
                case 'high':
                    return progress >= 75;
                default:
                    return true;
            }
        });
    }

    // Group filter
    if (customFilters.value.group !== 'all') {
        filtered = filtered.filter((student) => student.group === customFilters.value.group);
    }

    return filtered;
});

const columnHelper = createColumnHelper<Student>();

const columns = [
    columnHelper.display({
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                modelValue: table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }) => {
            return h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value) => row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            });
        },
        enableSorting: false,
        enableHiding: false,
    }),
    columnHelper.accessor('student_code', {
        enablePinning: true,
        header: 'Código',
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('student_code')),
    }),
    columnHelper.accessor('first_name', {
        enablePinning: true,
        header: 'Nombres',
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('first_name')),
    }),
    columnHelper.accessor('last_name', {
        enablePinning: true,
        header: 'Apellidos',
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('last_name')),
    }),
    columnHelper.accessor('personal_email', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Email Personal', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('personal_email')),
    }),
    columnHelper.accessor('phone', {
        enablePinning: true,
        header: 'Teléfono',
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('phone')),
    }),
    columnHelper.accessor('group', {
        enablePinning: true,
        header: 'Groupo',
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('group')),
    }),
    columnHelper.accessor('credits_completed', {
        enablePinning: true,
        header: 'Créditos Completados',
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('credits_completed')),
    }),
    columnHelper.accessor('total_credits', {
        enablePinning: true,
        header: 'Total Créditos',
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('total_credits')),
    }),
    columnHelper.accessor('progress_rate', {
        enablePinning: true,
        header: 'Tasa de Progreso',
        cell: ({ row }) => {
            const progressValue = row.getValue('progress_rate') as number;
            return h('div', { class: 'flex items-center gap-2 min-w-[120px]' }, [
                h(Progress, {
                    value: progressValue,
                    max: 100,
                    class: 'flex-1',
                }),
                h('span', { class: 'text-sm font-medium min-w-[35px]' }, `${progressValue}%`),
            ]);
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const student = row.original.student_code;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    student: student,
                }),
            );
        },
    },
];

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});
const expanded = ref<ExpandedState>({});

const table = useVueTable({
    get data() {
        return filteredData.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: (updaterOrValue) => valueUpdater(updaterOrValue, expanded),

    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get expanded() {
            return expanded.value;
        },
        columnPinning: {
            left: ['status'],
        },
    },
});
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Estudiantes',
        href: '/students',
    },
];

// Import notification state
const importResult = ref<any>(null);
const showNotification = ref(false);

const exportStudents = () => {
    window.location.href = '/students/export';
};

const handleImportSuccess = (result: any) => {
    // Show notification
    importResult.value = result;
    showNotification.value = true;

    // Refresh the page to show newly imported students
    router.reload({ only: ['students'] });
};

const closeNotification = () => {
    showNotification.value = false;
    importResult.value = null;
};

// Virtual Tour functionality
const virtualTourRef = ref(null);
const { tourSteps } = useFlow1Tour();
const { hasSavedTour, checkSavedTour } = useTourState();

onMounted(() => {
    checkSavedTour();

    // Si hay un tour guardado, verificar si debe continuar automáticamente
    if (hasSavedTour.value && virtualTourRef.value) {
        virtualTourRef.value.loadAndContinueTour();
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Filtros Avanzados -->
            <Card class="mb-4 p-4">
                <div class="space-y-4">
                    <!-- Búsqueda General -->
                    <div>
                        <Label for="search">Búsqueda General</Label>
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="customFilters.search"
                                placeholder="Buscar por nombre, código, email..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_program">Programa</Label>
                            <Select v-model="customFilters.program">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los programas" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los programas</SelectItem>
                                    <SelectItem v-for="program in uniquePrograms" :key="program.id" :value="program.id.toString()">
                                        <span class="truncate" :title="program.name">
                                            {{ program.name }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_gender">Género</Label>
                            <Select v-model="customFilters.gender">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los géneros" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los géneros</SelectItem>
                                    <SelectItem v-for="gender in uniqueGenders" :key="gender" :value="gender">
                                        {{ gender === 'M' ? 'Masculino' : gender === 'F' ? 'Femenino' : gender }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_progress">Progreso Académico</Label>
                            <Select v-model="customFilters.progressRange">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los rangos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los rangos</SelectItem>
                                    <SelectItem value="low">Bajo (0-24%)</SelectItem>
                                    <SelectItem value="medium-low">Medio-Bajo (25-49%)</SelectItem>
                                    <SelectItem value="medium-high">Medio-Alto (50-74%)</SelectItem>
                                    <SelectItem value="high">Alto (75-100%)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_group">Grupo</Label>
                            <Select v-model="customFilters.group">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los grupos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los grupos</SelectItem>
                                    <SelectItem v-for="group in uniqueGroups" :key="group" :value="group"> Grupo {{ group }} </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between">
                        <Button variant="outline" @click="clearCustomFilters" v-if="hasActiveCustomFilters">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} estudiantes</div>
                    </div>
                </div>
            </Card>

            <div class="flex items-center gap-2 py-4">
                <Input
                    class="max-w-sm"
                    placeholder="Filtro adicional por nombre..."
                    :model-value="table.getColumn('first_name')?.getFilterValue() as string"
                    @update:model-value="table.getColumn('first_name')?.setFilterValue($event)"
                />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="ml-auto">
                            Columnas
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuCheckboxItem
                            v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                            :key="column.id"
                            class="capitalize"
                            :model-value="column.getIsVisible()"
                            @update:model-value="
                                (value) => {
                                    column.toggleVisibility(!!value);
                                }
                            "
                        >
                            {{ column.id }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button @click="router.visit('/students/create')" data-tour="new-student-btn">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Estudiante
                </Button>

                <StudentImportDialog @success="handleImportSuccess" />

                <Button variant="outline" @click="exportStudents">
                    <Download class="mr-2 h-4 w-4" />
                    Exportar
                </Button>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                :data-pinned="header.column.getIsPinned()"
                                :class="
                                    cn(
                                        { 'sticky bg-background/95': header.column.getIsPinned() },
                                        header.column.getIsPinned() === 'left' ? 'left-0' : 'right-0',
                                    )
                                "
                            >
                                <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows?.length">
                            <template v-for="row in table.getRowModel().rows" :key="row.id">
                                <TableRow
                                    :data-state="row.getIsSelected() && 'selected'"
                                    class="cursor-pointer transition-colors hover:bg-muted/50"
                                    @click="router.visit(`/students/${row.original.student_code}`)"
                                >
                                    <TableCell
                                        v-for="cell in row.getVisibleCells()"
                                        :key="cell.id"
                                        :data-pinned="cell.column.getIsPinned()"
                                        :class="
                                            cn(
                                                { 'sticky bg-background/95': cell.column.getIsPinned() },
                                                cell.column.getIsPinned() === 'left' ? 'left-0' : 'right-0',
                                                cell.column.id === 'select' || cell.column.id === 'actions' ? 'cursor-default' : '',
                                            )
                                        "
                                        @click="cell.column.id === 'select' || cell.column.id === 'actions' ? $event.stopPropagation() : null"
                                    >
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                        </template>

                        <TableRow v-else>
                            <TableCell :colspan="columns.length" class="h-24 text-center"> Sin Resultados. </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="flex items-center justify-end space-x-2 py-4">
                <div class="flex-1 text-sm text-muted-foreground">
                    {{ table.getFilteredSelectedRowModel().rows.length }} de {{ table.getFilteredRowModel().rows.length }} fila(s) seleccionadas.
                </div>
                <div class="space-x-2">
                    <Button variant="outline" size="sm" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()"> Anterior </Button>
                    <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()"> Siguente </Button>
                </div>
            </div>
        </div>

        <!-- Import Notification -->
        <ImportNotification :result="importResult" :visible="showNotification" @close="closeNotification" />

        <!-- Virtual Tour Component -->
        <VirtualTour ref="virtualTourRef" :steps="tourSteps" />

        <!-- Tour Continue Button -->
        <TourContinueButton />
    </AppLayout>
</template>
