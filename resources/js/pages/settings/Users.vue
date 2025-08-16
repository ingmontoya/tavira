<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
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
import { ChevronDown, ChevronsUpDown, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../utils';

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    position?: string;
    department?: string;
    is_active: boolean;
    roles: Array<{
        id: number;
        name: string;
    }>;
    created_at: string;
}

const props = defineProps<{
    users: {
        data: User[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    roles?: Record<string, string>;
    departments?: Record<string, string>;
    filters?: {
        search?: string;
        role?: string;
        status?: string;
        department?: string;
    };
}>();

const data: User[] = props.users.data;

const customFilters = ref({
    search: props.filters?.search || '',
    role: props.filters?.role || 'all',
    status: props.filters?.status || 'all',
    department: props.filters?.department || 'all',
});

const uniqueDepartments = computed(() => {
    return props.departments || {};
});

const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        role: 'all',
        status: 'all',
        department: 'all',
    };
    table.getColumn('name')?.setFilterValue('');
};

const filteredData = computed(() => {
    let filtered = data;

    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (user) =>
                user.name?.toLowerCase().includes(searchTerm) ||
                user.email?.toLowerCase().includes(searchTerm) ||
                user.position?.toLowerCase().includes(searchTerm) ||
                user.department?.toLowerCase().includes(searchTerm),
        );
    }

    if (customFilters.value.role !== 'all') {
        filtered = filtered.filter((user) => user.roles.some((role) => role.name === customFilters.value.role));
    }

    if (customFilters.value.status !== 'all') {
        const isActive = customFilters.value.status === 'active';
        filtered = filtered.filter((user) => user.is_active === isActive);
    }

    if (customFilters.value.department !== 'all') {
        filtered = filtered.filter((user) => user.department === customFilters.value.department);
    }

    return filtered;
});

const columnHelper = createColumnHelper<User>();

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
    columnHelper.accessor('name', {
        enablePinning: true,
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Nombre', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('name')),
    }),
    columnHelper.accessor('email', {
        enablePinning: true,
        header: 'Email',
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('email')),
    }),
    columnHelper.display({
        id: 'roles',
        header: 'Rol',
        cell: ({ row }) => {
            const user = row.original;
            const role = user.roles[0];
            if (!role) return h('div', { class: 'text-muted-foreground text-sm' }, 'Sin rol');

            const roleLabels: Record<string, string> = {
                superadmin: 'Super Admin',
                admin_conjunto: 'Administrador',
                consejo: 'Consejo',
            };

            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                        role.name === 'superadmin'
                            ? 'bg-purple-100 text-purple-800'
                            : role.name === 'admin_conjunto'
                              ? 'bg-blue-100 text-blue-800'
                              : 'bg-green-100 text-green-800'
                    }`,
                },
                roleLabels[role.name] || role.name,
            );
        },
    }),
    columnHelper.accessor('position', {
        header: 'Cargo',
        cell: ({ row }) => {
            const position = row.getValue('position') as string;
            return h('div', { class: 'text-sm' }, position || '-');
        },
    }),
    columnHelper.accessor('department', {
        header: 'Departamento',
        cell: ({ row }) => {
            const department = row.getValue('department') as string;
            return h('div', { class: 'text-sm' }, department || '-');
        },
    }),
    columnHelper.accessor('is_active', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                        isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }`,
                },
                isActive ? 'Activo' : 'Inactivo',
            );
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const user = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    user: user,
                    baseRoute: 'settings.users',
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
            left: ['name'],
        },
    },
});

const breadcrumbs = [
    {
        title: 'Configuración',
        href: '/settings',
    },
    {
        title: 'Usuarios',
        href: '/settings/users',
    },
];
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <HeadingSmall title="Gestión de Usuarios" description="Administra los usuarios administrativos del sistema y sus roles" />
                    <Button @click="router.visit('/settings/users/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Usuario
                    </Button>
                </div>

                <!-- Filtros Avanzados -->
                <Card class="p-4">
                    <div class="space-y-4">
                        <!-- Búsqueda General -->
                        <div>
                            <Label for="search">Búsqueda General</Label>
                            <div class="relative mt-3">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                <Input
                                    id="search"
                                    v-model="customFilters.search"
                                    placeholder="Buscar por nombre, email, cargo, departamento..."
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
                                        <SelectItem value="superadmin">Super Admin</SelectItem>
                                        <SelectItem value="admin_conjunto">Administrador</SelectItem>
                                        <SelectItem value="consejo">Consejo</SelectItem>
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
                                        <SelectItem value="active">Activo</SelectItem>
                                        <SelectItem value="inactive">Inactivo</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="min-w-0 space-y-2">
                                <Label for="filter_department">Departamento</Label>
                                <Select v-model="customFilters.department">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Todos los departamentos" class="truncate" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los departamentos</SelectItem>
                                        <SelectItem v-for="(label, value) in uniqueDepartments" :key="value" :value="value">
                                            {{ label }}
                                        </SelectItem>
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
                            <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} usuarios</div>
                        </div>
                    </div>
                </Card>

                <div class="flex items-center gap-2 py-4">
                    <Input
                        class="max-w-sm"
                        placeholder="Filtro adicional por nombre..."
                        :model-value="table.getColumn('name')?.getFilterValue() as string"
                        @update:model-value="table.getColumn('name')?.setFilterValue($event)"
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
                                        @click="router.visit(`/settings/users/${row.original.id}`)"
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
        </SettingsLayout>
    </AppLayout>
</template>
