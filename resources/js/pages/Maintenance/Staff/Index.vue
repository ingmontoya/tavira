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
import { Edit, Eye, Mail, Phone, Plus, Trash2, UserCog } from 'lucide-vue-next';
import { h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';
import { useNavigation } from '@/composables/useNavigation';

export interface MaintenanceStaff {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    specialties: string[];
    hourly_rate: number | null;
    is_internal: boolean;
    is_active: boolean;
    availability_schedule: Record<string, any> | null;
    created_at: string;
}

interface Props {
    staff: MaintenanceStaff[];
}

const props = defineProps<Props>();

const data = ref(props.staff);
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const columnHelper = createColumnHelper<MaintenanceStaff>();

const columns = [
    columnHelper.accessor('name', {
        header: 'Nombre',
        cell: ({ row }) => {
            const staff = row.original;
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, staff.name),
                h('div', { class: 'flex items-center space-x-2 text-sm text-gray-500' }, [
                    staff.is_internal
                        ? h(Badge, { variant: 'default', class: 'text-xs' }, () => 'Interno')
                        : h(Badge, { variant: 'secondary', class: 'text-xs' }, () => 'Externo'),
                ]),
            ]);
        },
    }),
    columnHelper.accessor('specialties', {
        header: 'Especialidades',
        cell: ({ row }) => {
            const specialties = row.getValue('specialties') as string[];
            if (!specialties || specialties.length === 0) {
                return h('div', { class: 'text-sm text-gray-400' }, 'Sin especialidades');
            }
            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                specialties
                    .slice(0, 2)
                    .map((specialty, index) =>
                        h(
                            Badge,
                            {
                                key: index,
                                variant: 'outline',
                                class: 'text-xs',
                            },
                            () => specialty,
                        ),
                    )
                    .concat(specialties.length > 2 ? [h('span', { class: 'text-xs text-gray-500' }, `+${specialties.length - 2}`)] : []),
            );
        },
    }),
    columnHelper.accessor('phone', {
        header: 'Contacto',
        cell: ({ row }) => {
            const staff = row.original;
            return h('div', { class: 'space-y-1 text-sm' }, [
                staff.phone ? h('div', { class: 'flex items-center space-x-1' }, [h(Phone, { class: 'h-3 w-3' }), h('span', {}, staff.phone)]) : null,
                staff.email ? h('div', { class: 'flex items-center space-x-1' }, [h(Mail, { class: 'h-3 w-3' }), h('span', {}, staff.email)]) : null,
                !staff.phone && !staff.email ? h('span', { class: 'text-gray-400' }, 'Sin contacto') : null,
            ]);
        },
    }),
    columnHelper.accessor('hourly_rate', {
        header: 'Tarifa/Hora',
        cell: ({ row }) => {
            const rate = row.getValue('hourly_rate') as number;
            return h(
                'div',
                { class: 'text-sm font-medium' },
                rate
                    ? new Intl.NumberFormat('es-CO', {
                          style: 'currency',
                          currency: 'COP',
                          minimumFractionDigits: 0,
                      }).format(rate)
                    : 'N/A',
            );
        },
    }),
    columnHelper.accessor('availability_schedule', {
        header: 'Disponibilidad',
        cell: ({ row }) => {
            const schedule = row.getValue('availability_schedule') as Record<string, any>;
            if (!schedule) {
                return h('div', { class: 'text-sm text-gray-400' }, 'No definida');
            }
            const activeDays = Object.keys(schedule).length;
            return h('div', { class: 'text-sm' }, `${activeDays} días/semana`);
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
                () => (isActive ? 'Activo' : 'Inactivo'),
            );
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const staff = row.original;
            return h(DropdownAction, {
                align: 'end',
                items: [
                    {
                        label: 'Ver detalles',
                        icon: Eye,
                        href: `/maintenance-staff/${staff.id}`,
                    },
                    {
                        label: 'Editar',
                        icon: Edit,
                        href: `/maintenance-staff/${staff.id}/edit`,
                    },
                    {
                        label: 'Eliminar',
                        icon: Trash2,
                        href: `/maintenance-staff/${staff.id}`,
                        method: 'delete',
                        class: 'text-red-600 focus:text-red-600',
                        confirm: '¿Estás seguro de que quieres eliminar este miembro del personal?',
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
        title: 'Personal',
        href: '/maintenance-staff',
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
    <Head title="Personal de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <UserCog class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Personal de Mantenimiento</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <Link v-if="hasPermission('create_maintenance_staff')" :href="route('maintenance-staff.create')">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Agregar Personal
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
                                    @click="router.visit(route('maintenance-staff.show', row.original.id))"
                                >
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No se encontró personal de mantenimiento.
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
