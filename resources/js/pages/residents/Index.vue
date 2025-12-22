<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import ImportNotification from '@/components/ImportNotification.vue';
import TourContinueButton from '@/components/TourContinueButton.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

export interface Resident {
    id: number;
    first_name: string;
    last_name: string;
    document_type: string;
    document_number: string;
    email: string;
    phone: string;
    apartment?: {
        id: number;
        number: string;
        tower: string;
        floor: number;
        apartment_type: {
            name: string;
            area_sqm: number;
        };
    };
    resident_type: 'Owner' | 'Tenant' | 'Family';
    status: 'Active' | 'Inactive' | 'Pending';
}

const props = defineProps<{
    residents: {
        data: Resident[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    towers?: string[];
    conjuntos?: string[];
    filters?: {
        search?: string;
        status?: string;
        resident_type?: string;
        conjunto?: string;
        tower?: string;
    };
}>();

const data = computed(() => props.residents.data);

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    resident_type: props.filters?.resident_type || 'all',
    conjunto: props.filters?.conjunto || 'all',
    tower: props.filters?.tower || 'all',
});

// Computed values for filter options
const uniqueConjuntos = computed(() => {
    return props.conjuntos || [];
});

const uniqueTowers = computed(() => {
    return props.towers || [];
});

const uniqueResidentTypes = computed(() => {
    return ['Owner', 'Tenant', 'Family'];
});

const uniqueStatuses = computed(() => {
    return ['Active', 'Inactive', 'Pending'];
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        status: 'all',
        resident_type: 'all',
        conjunto: 'all',
        tower: 'all',
    };
    // Also clear table filters
    table.getColumn('first_name')?.setFilterValue('');
};

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = data.value;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (resident) =>
                resident.first_name?.toLowerCase().includes(searchTerm) ||
                resident.last_name?.toLowerCase().includes(searchTerm) ||
                resident.document_number?.toLowerCase().includes(searchTerm) ||
                resident.email?.toLowerCase().includes(searchTerm) ||
                resident.apartment?.number?.toLowerCase().includes(searchTerm) ||
                resident.apartment?.tower?.toLowerCase().includes(searchTerm),
        );
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        filtered = filtered.filter((resident) => resident.status === customFilters.value.status);
    }

    // Resident type filter
    if (customFilters.value.resident_type !== 'all') {
        filtered = filtered.filter((resident) => resident.resident_type === customFilters.value.resident_type);
    }

    // Conjunto filter
    if (customFilters.value.conjunto !== 'all') {
        filtered = filtered.filter((resident) => resident.conjunto === customFilters.value.conjunto);
    }

    // Tower filter
    if (customFilters.value.tower !== 'all') {
        filtered = filtered.filter((resident) => resident.apartment?.tower === customFilters.value.tower);
    }

    return filtered;
});

const columnHelper = createColumnHelper<Resident>();

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
    columnHelper.accessor('document_number', {
        enablePinning: true,
        header: 'Documento',
        cell: ({ row }) => {
            const resident = row.original;
            return h('div', { class: 'capitalize' }, `${resident.document_type} ${resident.document_number}`);
        },
    }),
    columnHelper.accessor('email', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Email', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('email')),
    }),
    columnHelper.display({
        id: 'apartment',
        header: 'Ubicación',
        cell: ({ row }) => {
            const resident = row.original;
            if (!resident.apartment) {
                return h('div', { class: 'text-muted-foreground text-sm' }, 'Sin apartamento');
            }
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, `Torre ${resident.apartment.tower} - Apt ${resident.apartment.number}`),
                h('div', { class: 'text-xs text-muted-foreground' }, `Piso ${resident.apartment.floor} - ${resident.apartment.apartment_type}`),
            ]);
        },
    }),
    columnHelper.accessor('resident_type', {
        enablePinning: true,
        header: 'Tipo',
        cell: ({ row }) => {
            const type = row.getValue('resident_type') as string;
            const typeMap = {
                Owner: 'Propietario',
                Tenant: 'Arrendatario',
                Family: 'Familiar',
            };
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                        type === 'Owner'
                            ? 'bg-green-100 text-green-800'
                            : type === 'Tenant'
                              ? 'bg-blue-100 text-blue-800'
                              : 'bg-yellow-100 text-yellow-800'
                    }`,
                },
                typeMap[type] || type,
            );
        },
    }),
    columnHelper.accessor('status', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const statusMap: Record<string, { label: string; class: string }> = {
                Active: { label: 'Activo', class: 'bg-green-100 text-green-800' },
                Inactive: { label: 'Inactivo', class: 'bg-red-100 text-red-800' },
                Pending: { label: 'Pendiente', class: 'bg-amber-100 text-amber-800' },
            };
            const statusInfo = statusMap[status] || { label: status, class: 'bg-gray-100 text-gray-800' };
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${statusInfo.class}`,
                },
                statusInfo.label,
            );
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const resident = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    resident: resident,
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
        title: 'Residentes',
        href: '/residents',
    },
];

// Import notification state
const importResult = ref<any>(null);
const showNotification = ref(false);

const exportResidents = () => {
    window.location.href = '/residents/export';
};

const handleImportSuccess = (result: any) => {
    // Show notification
    importResult.value = result;
    showNotification.value = true;

    // Refresh the page to show newly imported residents
    router.reload({ only: ['residents'] });
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
    <Head title="Residentes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Filtros Avanzados -->
            <Card class="mb-4 p-4">
                <div class="space-y-4">
                    <!-- Búsqueda General -->
                    <div>
                        <Label for="search">Búsqueda General</Label>
                        <div class="relative mt-3">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="customFilters.search"
                                placeholder="Buscar por nombre, documento, email, apartamento..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="Pending">Pendiente</SelectItem>
                                    <SelectItem value="Active">Activo</SelectItem>
                                    <SelectItem value="Inactive">Inactivo</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_resident_type">Tipo</Label>
                            <Select v-model="customFilters.resident_type">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los tipos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem value="Owner">Propietario</SelectItem>
                                    <SelectItem value="Tenant">Arrendatario</SelectItem>
                                    <SelectItem value="Family">Familiar</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_conjunto">Conjunto</Label>
                            <Select v-model="customFilters.conjunto">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los conjuntos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los conjuntos</SelectItem>
                                    <SelectItem v-for="conjunto in uniqueConjuntos" :key="conjunto" :value="conjunto">
                                        {{ conjunto }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_tower">Torre</Label>
                            <Select v-model="customFilters.tower">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todas las torres" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las torres</SelectItem>
                                    <SelectItem v-for="tower in uniqueTowers" :key="tower" :value="tower"> Torre {{ tower }} </SelectItem>
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
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} residentes</div>
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

                <Button @click="router.visit('/residents/create')" data-tour="new-resident-btn">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Residente
                </Button>

                <Button variant="outline" @click="exportResidents">
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
                                    @click="router.visit(`/residents/${row.original.id}`)"
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
                    <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()"> Siguiente </Button>
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
