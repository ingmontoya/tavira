<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { Edit, Eye, Plus, Settings, Trash2 } from 'lucide-vue-next';
import { h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';
import { useNavigation } from '@/composables/useNavigation';

export interface MaintenanceCategory {
    id: number;
    name: string;
    description: string | null;
    color: string;
    priority_level: number;
    requires_approval: boolean;
    estimated_hours: number | null;
    is_active: boolean;
    maintenance_requests_count: number;
    created_at: string;
}

interface Props {
    categories: MaintenanceCategory[];
}

const props = defineProps<Props>();

const data = ref(props.categories);
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const columnHelper = createColumnHelper<MaintenanceCategory>();

const columns = [
    columnHelper.accessor('name', {
        header: 'Nombre',
        cell: ({ row }) => {
            const category = row.original;
            return h('div', { class: 'flex items-center space-x-2' }, [
                h('div', {
                    class: 'h-4 w-4 rounded-full',
                    style: { backgroundColor: category.color },
                }),
                h('span', { class: 'font-medium' }, category.name),
            ]);
        },
    }),
    columnHelper.accessor('description', {
        header: 'Descripción',
        cell: ({ row }) => {
            const description = row.getValue('description') as string;
            return h('div', { class: 'text-sm text-gray-600 max-w-xs truncate' }, description || 'Sin descripción');
        },
    }),
    columnHelper.accessor('priority_level', {
        header: 'Prioridad',
        cell: ({ row }) => {
            const level = row.getValue('priority_level') as number;
            const priorityLabels = {
                1: { label: 'Crítica', color: 'destructive' },
                2: { label: 'Alta', color: 'destructive' },
                3: { label: 'Media', color: 'default' },
                4: { label: 'Baja', color: 'secondary' },
            };
            const priority = priorityLabels[level as keyof typeof priorityLabels] || { label: 'Desconocida', color: 'secondary' };
            return h(
                Badge,
                {
                    variant: priority.color as any,
                },
                () => priority.label,
            );
        },
    }),
    columnHelper.accessor('requires_approval', {
        header: 'Req. Aprobación',
        cell: ({ row }) => {
            const requiresApproval = row.getValue('requires_approval') as boolean;
            return h(
                Badge,
                {
                    variant: requiresApproval ? 'default' : 'secondary',
                },
                () => (requiresApproval ? 'Sí' : 'No'),
            );
        },
    }),
    columnHelper.accessor('estimated_hours', {
        header: 'Horas Est.',
        cell: ({ row }) => {
            const hours = row.getValue('estimated_hours') as number;
            return h('div', { class: 'text-sm' }, hours ? `${hours}h` : 'N/A');
        },
    }),
    columnHelper.accessor('maintenance_requests_count', {
        header: 'Solicitudes',
        cell: ({ row }) => {
            const count = row.getValue('maintenance_requests_count') as number;
            return h('div', { class: 'text-center font-medium' }, count.toString());
        },
    }),
    columnHelper.accessor('is_active', {
        header: 'Estado',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return h(
                Badge,
                {
                    variant: isActive ? 'default' : 'secondary',
                },
                () => (isActive ? 'Activa' : 'Inactiva'),
            );
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const category = row.original;
            return h(DropdownAction, {
                align: 'end',
                items: [
                    {
                        label: 'Ver detalles',
                        icon: Eye,
                        href: `/maintenance-categories/${category.id}`,
                    },
                    {
                        label: 'Editar',
                        icon: Edit,
                        href: `/maintenance-categories/${category.id}/edit`,
                    },
                    {
                        label: 'Eliminar',
                        icon: Trash2,
                        href: `/maintenance-categories/${category.id}`,
                        method: 'delete',
                        class: 'text-red-600 focus:text-red-600',
                        confirm: '¿Estás seguro de que quieres eliminar esta categoría?',
                    },
                ],
            });
        },
    }),
];

const { hasPermission } = useNavigation();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Administración',
        href: '#',
    },
    {
        title: 'Mantenimiento',
        href: '#',
    },
    {
        title: 'Categorías',
        href: '/maintenance-categories',
    },
];

const table = useVueTable({
    data: data.value,
    columns,
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    state: {
        sorting: sorting.value,
        columnFilters: columnFilters.value,
        columnVisibility: columnVisibility.value,
        rowSelection: rowSelection.value,
    },
});
</script>

<template>
    <Head title="Categorías de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Settings class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Categorías de Mantenimiento</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <Link v-if="hasPermission('create_maintenance_categories')" :href="route('maintenance-categories.create')">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Nueva Categoría
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Table -->
            <Card>
            <div class="p-6">
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead
                                    v-for="header in headerGroup.headers"
                                    :key="header.id"
                                    :class="cn('text-left', header.column.getCanSort() && 'cursor-pointer select-none')"
                                    @click="header.column.getToggleSortingHandler()?.($event)"
                                >
                                    <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="table.getRowModel().rows?.length">
                                <TableRow
                                    v-for="row in table.getRowModel().rows"
                                    :key="row.id"
                                    :data-state="row.getIsSelected() && 'selected'"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="router.visit(route('maintenance-categories.show', row.original.id))"
                                >
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No se encontraron categorías de mantenimiento.
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </Card>
        </div>
    </AppLayout>
</template>
