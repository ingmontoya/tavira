<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ChevronDown, ChevronsUpDown, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';

export interface Invitation {
    id: number;
    email: string;
    role: string;
    apartment?: {
        id: number;
        number: string;
        tower: string;
        floor: number;
    };
    invited_by: {
        name: string;
    };
    accepted_by?: {
        name: string;
    };
    message?: string;
    created_at: string;
    expires_at: string;
    accepted_at?: string;
}

const props = defineProps<{
    invitations: {
        data: Invitation[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
}>();

const data: Invitation[] = props.invitations.data;

// Custom filters state
const customFilters = ref({
    search: '',
    role: 'all',
    status: 'all',
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        role: 'all',
        status: 'all',
    };
    table.getColumn('email')?.setFilterValue('');
};

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = data;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (invitation) =>
                invitation.email?.toLowerCase().includes(searchTerm) ||
                invitation.role?.toLowerCase().includes(searchTerm) ||
                invitation.invited_by?.name?.toLowerCase().includes(searchTerm),
        );
    }

    // Role filter
    if (customFilters.value.role !== 'all') {
        filtered = filtered.filter((invitation) => invitation.role === customFilters.value.role);
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        if (customFilters.value.status === 'accepted') {
            filtered = filtered.filter((invitation) => invitation.accepted_at);
        } else if (customFilters.value.status === 'pending') {
            filtered = filtered.filter((invitation) => !invitation.accepted_at && new Date(invitation.expires_at) > new Date());
        } else if (customFilters.value.status === 'expired') {
            filtered = filtered.filter((invitation) => !invitation.accepted_at && new Date(invitation.expires_at) <= new Date());
        }
    }

    return filtered;
});

const columnHelper = createColumnHelper<Invitation>();

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
    columnHelper.accessor('email', {
        enablePinning: true,
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
    columnHelper.accessor('role', {
        enablePinning: true,
        header: 'Rol',
        cell: ({ row }) => {
            const role = row.getValue('role') as string;
            const roleMap = {
                admin_conjunto: 'Admin Conjunto',
                consejo: 'Consejo',
                propietario: 'Propietario',
                residente: 'Residente',
                porteria: 'Portería',
            };
            return h('div', { class: 'capitalize' }, roleMap[role] || role);
        },
    }),
    columnHelper.display({
        id: 'apartment',
        header: 'Apartamento',
        cell: ({ row }) => {
            const invitation = row.original;
            if (!invitation.apartment) {
                return h('div', { class: 'text-muted-foreground text-sm' }, 'N/A');
            }
            return h('div', { class: 'font-medium' }, `Torre ${invitation.apartment.tower} - Apt ${invitation.apartment.number}`);
        },
    }),
    columnHelper.accessor('invited_by', {
        header: 'Invitado por',
        cell: ({ row }) => {
            const invitedBy = row.getValue('invited_by') as any;
            return h('div', { class: 'text-sm' }, invitedBy?.name || 'N/A');
        },
    }),
    columnHelper.display({
        id: 'status',
        header: 'Estado',
        cell: ({ row }) => {
            const invitation = row.original;
            const isExpired = new Date(invitation.expires_at) <= new Date();
            const isAccepted = !!invitation.accepted_at;

            let status = 'Pendiente';
            let className = 'bg-yellow-100 text-yellow-800';

            if (isAccepted) {
                status = 'Aceptada';
                className = 'bg-green-100 text-green-800';
            } else if (isExpired) {
                status = 'Expirada';
                className = 'bg-red-100 text-red-800';
            }

            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${className}`,
                },
                status,
            );
        },
    }),
    columnHelper.accessor('created_at', {
        header: 'Fecha',
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at'));
            return h('div', { class: 'text-sm' }, date.toLocaleDateString('es-CO'));
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const invitation = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    invitation: invitation,
                }),
            );
        },
    },
];

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const table = useVueTable({
    get data() {
        return filteredData.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),

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
    },
});

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Invitaciones',
        href: '/invitations',
    },
];
</script>

<template>
    <Head title="Invitaciones" />

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
                                placeholder="Buscar por email, rol, invitado por..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_role">Rol</Label>
                            <Select v-model="customFilters.role">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los roles" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los roles</SelectItem>
                                    <SelectItem value="admin_conjunto">Admin Conjunto</SelectItem>
                                    <SelectItem value="consejo">Consejo</SelectItem>
                                    <SelectItem value="propietario">Propietario</SelectItem>
                                    <SelectItem value="residente">Residente</SelectItem>
                                    <SelectItem value="porteria">Portería</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="pending">Pendiente</SelectItem>
                                    <SelectItem value="accepted">Aceptada</SelectItem>
                                    <SelectItem value="expired">Expirada</SelectItem>
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
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} invitaciones</div>
                    </div>
                </div>
            </Card>

            <div class="flex items-center gap-2 py-4">
                <Input
                    class="max-w-sm"
                    placeholder="Filtro adicional por email..."
                    :model-value="table.getColumn('email')?.getFilterValue() as string"
                    @update:model-value="table.getColumn('email')?.setFilterValue($event)"
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

                <Button @click="router.visit('/invitations/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Invitación
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
                                    @click="router.visit(`/invitations/${row.original.id}`)"
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
    </AppLayout>
</template>
