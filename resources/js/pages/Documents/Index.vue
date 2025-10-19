<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { FileText, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../utils';

export interface Document {
    id: number;
    filename: string;
    created_at: string;
    module: string;
    uploader: string;
    type: string;
    file_size: string;
    file_type: string;
}

interface Props {
    documents: Document[];
}

const props = defineProps<Props>();

const data = ref(props.documents);
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

// Search and filter states
const searchQuery = ref('');
const selectedModule = ref('all');

const columnHelper = createColumnHelper<Document>();

const columns = [
    columnHelper.accessor('filename', {
        header: 'Nombre del Archivo',
        cell: ({ row }) => {
            return h('div', { class: 'font-medium max-w-xs truncate' }, row.getValue('filename'));
        },
    }),
    columnHelper.accessor('created_at', {
        header: 'Fecha de Subida',
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at'));
            return h(
                'div',
                { class: 'text-sm' },
                date.toLocaleDateString('es-CO', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }),
            );
        },
    }),
    columnHelper.accessor('module', {
        header: 'Módulo',
        cell: ({ row }) => {
            const module = row.getValue('module') as string;
            const moduleColors: Record<string, string> = {
                Mantenimiento: 'bg-blue-100 text-blue-800',
                Correspondencia: 'bg-green-100 text-green-800',
                Finanzas: 'bg-purple-100 text-purple-800',
                Anuncios: 'bg-orange-100 text-orange-800',
            };
            return h(
                Badge,
                {
                    class: `${moduleColors[module] || 'bg-gray-100 text-gray-800'} text-xs`,
                },
                () => module,
            );
        },
    }),
    columnHelper.accessor('uploader', {
        header: 'Usuario',
        cell: ({ row }) => {
            return h('div', { class: 'text-sm' }, row.getValue('uploader'));
        },
    }),
    columnHelper.accessor('file_size', {
        header: 'Tamaño',
        cell: ({ row }) => {
            return h('div', { class: 'text-sm' }, row.getValue('file_size'));
        },
    }),
    columnHelper.accessor('file_type', {
        header: 'Tipo',
        cell: ({ row }) => {
            const fileType = row.getValue('file_type') as string;
            const typeLabel = fileType.split('/')[1]?.toUpperCase() || 'DESCONOCIDO';
            return h('div', { class: 'text-xs font-mono' }, typeLabel);
        },
    }),
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

// Filter logic
const filteredData = computed(() => {
    let filtered = props.documents;

    if (searchQuery.value) {
        filtered = filtered.filter(
            (doc) =>
                doc.filename.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                doc.uploader.toLowerCase().includes(searchQuery.value.toLowerCase()),
        );
    }

    if (selectedModule.value !== 'all') {
        filtered = filtered.filter((doc) => doc.module === selectedModule.value);
    }

    return filtered;
});

// Update table data when filters change
const updateTableData = () => {
    data.value = filteredData.value;
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedModule.value = 'all';
    updateTableData();
};

const hasActiveFilters = computed(() => {
    return searchQuery.value || (selectedModule.value && selectedModule.value !== 'all');
});

const uniqueModules = computed(() => {
    const modules = [...new Set(props.documents.map((doc) => doc.module))];
    return modules.sort();
});

// Watch for filter changes
const applyFilters = () => {
    updateTableData();
};

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
        title: 'Documentos',
        href: '/documents',
    },
];
</script>

<template>
    <Head title="Documentos del Sistema" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <FileText class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Documentos del Sistema</h1>
                </div>
                <div class="text-sm text-gray-500">{{ filteredData.length }} documentos encontrados</div>
            </div>

            <!-- Filters -->
            <Card class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Filtros</h3>
                        <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearFilters">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search class="absolute top-3 left-3 h-4 w-4 text-gray-400" />
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    placeholder="Buscar por nombre o usuario..."
                                    class="pl-9"
                                    @input="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="module">Módulo</Label>
                            <Select v-model="selectedModule" @update:modelValue="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los módulos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los módulos</SelectItem>
                                    <SelectItem v-for="module in uniqueModules" :key="module" :value="module">
                                        {{ module }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
            </Card>

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
                                        <FlexRender
                                            v-if="!header.isPlaceholder"
                                            :render="header.column.columnDef.header"
                                            :props="header.getContext()"
                                        />
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="table.getRowModel().rows?.length">
                                    <TableRow
                                        v-for="row in table.getRowModel().rows"
                                        :key="row.id"
                                        :data-state="row.getIsSelected() && 'selected'"
                                        class="hover:bg-gray-50"
                                    >
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell :colspan="columns.length" class="h-24 text-center"> No se encontraron documentos. </TableCell>
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
