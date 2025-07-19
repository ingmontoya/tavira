<script setup lang="ts">
import type {
    ColumnFiltersState,
    ExpandedState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table'
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import { ChevronDown, ChevronsUpDown } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { h, ref, computed, watch } from 'vue'
import { cn, valueUpdater } from '../../utils'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import DropdownAction from '@/components/DataTableDropDown.vue'
import { Plus, X, Search, Home, Building } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'

export interface Apartment {
    id: number
    number: string
    tower: string
    floor: number
    position_on_floor: number
    status: 'Available' | 'Occupied' | 'Maintenance' | 'Reserved'
    monthly_fee: number
    apartment_type: {
        id: number
        name: string
        area_sqm: number
        bedrooms: number
        bathrooms: number
    }
    conjunto_config: {
        id: number
        name: string
    }
    residents: Array<{
        id: number
        first_name: string
        last_name: string
        resident_type: string
        status: string
    }>
    full_address: string
}

const props = defineProps<{
    apartments: {
        data: Apartment[]
        from: number
        to: number
        total: number
        prev_page_url?: string
        next_page_url?: string
    }
    conjuntoConfigs: Array<{
        id: number
        name: string
    }>
    apartmentTypes: Array<{
        id: number
        name: string
        area_sqm: number
    }>
    towers: string[]
    floors: number[]
    statuses: string[]
    filters?: {
        search?: string
        tower?: string
        floor?: string
        status?: string
        apartment_type_id?: string
    }
}>()

const data = computed(() => props.apartments.data)

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    tower: props.filters?.tower || 'all',
    floor: props.filters?.floor || 'all',
    status: props.filters?.status || 'all',
    apartment_type_id: props.filters?.apartment_type_id || 'all'
})

// Computed values for filter options
const uniqueTowers = computed(() => {
    return props.towers || []
})

const uniqueFloors = computed(() => {
    return props.floors || []
})

const uniqueStatuses = computed(() => {
    return props.statuses || []
})

const uniqueApartmentTypes = computed(() => {
    return props.apartmentTypes || []
})

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some(value =>
        value !== '' && value !== 'all'
    )
})

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        tower: 'all',
        floor: 'all',
        status: 'all',
        apartment_type_id: 'all'
    }
    
    router.visit('/apartments', {
        preserveState: true,
        preserveScroll: true,
        only: ['apartments', 'filters']
    })
}

// Apply filters function for server-side filtering
const applyFilters = () => {
    const filterData: Record<string, string> = {}
    
    // Add filters to data object
    if (customFilters.value.search && customFilters.value.search.trim()) {
        filterData.search = customFilters.value.search.trim()
    }
    if (customFilters.value.tower && customFilters.value.tower !== 'all') {
        filterData.tower = customFilters.value.tower
    }
    if (customFilters.value.floor && customFilters.value.floor !== 'all') {
        filterData.floor = customFilters.value.floor
    }
    if (customFilters.value.status && customFilters.value.status !== 'all') {
        filterData.status = customFilters.value.status
    }
    if (customFilters.value.apartment_type_id && customFilters.value.apartment_type_id !== 'all') {
        filterData.apartment_type_id = customFilters.value.apartment_type_id
    }
    
    // Always reset to page 1 when filtering
    filterData.page = '1'
    
    router.visit('/apartments', {
        data: filterData,
        preserveState: true,
        preserveScroll: true,
    })
}

// Manual filter application instead of watchers

const columnHelper = createColumnHelper<Apartment>()

const getStatusLabel = (status: string) => {
    const labels = {
        'Available': 'Disponible',
        'Occupied': 'Ocupado',
        'Maintenance': 'Mantenimiento',
        'Reserved': 'Reservado'
    }
    return labels[status] || status
}

const getStatusColor = (status: string) => {
    const colors = {
        'Available': 'bg-green-100 text-green-800',
        'Occupied': 'bg-blue-100 text-blue-800',
        'Maintenance': 'bg-yellow-100 text-yellow-800',
        'Reserved': 'bg-purple-100 text-purple-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getDisplayNumber = (apartment: Apartment) => {
    // Format: Tower[Floor][Position] -> Tower[Floor][Position]
    // Example: 4101 should display as 4101 (Tower 4, Floor 1, Position 1)
    return apartment.number.toString();
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(value)
}

const columns = [
    columnHelper.display({
        id: 'select',
        header: ({ table }) => h(Checkbox, {
            'modelValue': table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
            'onUpdate:modelValue': value => table.toggleAllPageRowsSelected(!!value),
            'ariaLabel': 'Select all',
        }),
        cell: ({ row }) => {
            return h(Checkbox, {
                'modelValue': row.getIsSelected(),
                'onUpdate:modelValue': value => row.toggleSelected(!!value),
                'ariaLabel': 'Select row',
            })
        },
        enableSorting: false,
        enableHiding: false,
    }),
    columnHelper.accessor('number', {
        enablePinning: true,
        header: ({ column }) => {
            return h(Button, {
                variant: 'ghost',
                onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
            }, () => ['Número', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })])
        },
        cell: ({ row }) => {
            const apartment = row.original
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, getDisplayNumber(apartment)),
                h('div', { class: 'text-xs text-muted-foreground' }, apartment.full_address)
            ])
        },
    }),
    columnHelper.display({
        id: 'location',
        header: 'Ubicación',
        cell: ({ row }) => {
            const apartment = row.original
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, `Torre ${apartment.tower} - Piso ${apartment.floor}`),
                h('div', { class: 'text-xs text-muted-foreground' }, `Posición ${apartment.position_on_floor}`)
            ])
        },
    }),
    columnHelper.display({
        id: 'type',
        header: 'Tipo',
        cell: ({ row }) => {
            const apartment = row.original
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, apartment.apartment_type?.name || 'N/A'),
                h('div', { class: 'text-xs text-muted-foreground' }, `${apartment.apartment_type?.area_sqm || 0}m² - ${apartment.apartment_type?.bedrooms || 0}hab/${apartment.apartment_type?.bathrooms || 0}baños`)
            ])
        },
    }),
    columnHelper.accessor('status', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status') as string
            return h('span', {
                class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${getStatusColor(status)}`
            }, getStatusLabel(status))
        },
    }),
    columnHelper.accessor('monthly_fee', {
        header: ({ column }) => {
            return h(Button, {
                variant: 'ghost',
                onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
            }, () => ['Cuota', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })])
        },
        cell: ({ row }) => {
            const fee = row.getValue('monthly_fee') as number
            return h('div', { class: 'font-medium' }, formatCurrency(fee))
        },
    }),
    columnHelper.display({
        id: 'residents',
        header: 'Residentes',
        cell: ({ row }) => {
            const apartment = row.original
            const residentsCount = apartment.residents?.length || 0
            const activeResidents = apartment.residents?.filter(r => r.status === 'Active').length || 0
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, `${residentsCount} residentes`),
                h('div', { class: 'text-xs text-muted-foreground' }, `${activeResidents} activos`)
            ])
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const apartment = row.original
            return h('div', { class: 'relative' }, h(DropdownAction, {
                'apartment': apartment,
            }))
        },
    },
]

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

const table = useVueTable({
    get data() { return data.value },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
    manualPagination: true,
    pageCount: Math.ceil(props.apartments.total / 20),

    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFilters.value },
        get columnVisibility() { return columnVisibility.value },
        get rowSelection() { return rowSelection.value },
        get expanded() { return expanded.value },
        columnPinning: {
            left: ['status'],
        },
    },
})

// Pagination functions
const nextPage = () => {
    if (props.apartments.next_page_url) {
        router.visit(props.apartments.next_page_url)
    }
}

const previousPage = () => {
    if (props.apartments.prev_page_url) {
        router.visit(props.apartments.prev_page_url)
    }
}

const currentPage = computed(() => {
    const url = new URL(window.location.href)
    return parseInt(url.searchParams.get('page') || '1')
})

const totalPages = computed(() => {
    return Math.ceil(props.apartments.total / 20)
})

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Apartamentos',
        href: '/apartments',
    },
]
</script>

<template>
    <Head title="Apartamentos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Filtros Avanzados -->
            <Card class="p-4 mb-4">
                <div class="space-y-4">
                    <!-- Búsqueda General -->
                    <div>
                        <Label for="search">Búsqueda General</Label>
                        <div class="relative mt-3">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="customFilters.search"
                                placeholder="Buscar por número, torre, tipo, conjunto..."
                                class="pl-10 max-w-md"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="space-y-2 min-w-0">
                            <Label for="filter_tower">Torre</Label>
                            <Select v-model="customFilters.tower">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todas las torres" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las torres</SelectItem>
                                    <SelectItem
                                        v-for="tower in uniqueTowers"
                                        :key="tower"
                                        :value="tower"
                                    >
                                        Torre {{ tower }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <Label for="filter_floor">Piso</Label>
                            <Select v-model="customFilters.floor">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los pisos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los pisos</SelectItem>
                                    <SelectItem
                                        v-for="floor in uniqueFloors"
                                        :key="floor"
                                        :value="floor.toString()"
                                    >
                                        Piso {{ floor }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem
                                        v-for="status in uniqueStatuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ getStatusLabel(status) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <Label for="filter_type">Tipo</Label>
                            <Select v-model="customFilters.apartment_type_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los tipos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem
                                        v-for="type in uniqueApartmentTypes"
                                        :key="type.id"
                                        :value="type.id.toString()"
                                    >
                                        {{ type.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-between items-center">
                        <div class="flex gap-2">
                            <Button
                                @click="applyFilters"
                                class="gap-2"
                            >
                                <Search class="h-4 w-4" />
                                Aplicar Filtros
                            </Button>
                            <Button
                                variant="outline"
                                @click="clearCustomFilters"
                                v-if="hasActiveCustomFilters"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Limpiar filtros
                            </Button>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ props.apartments.from || 0 }} - {{ props.apartments.to || 0 }} de {{ props.apartments.total || 0 }} apartamentos
                        </div>
                    </div>
                </div>
            </Card>

            <div class="flex gap-2 items-center py-4">
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
                            :key="column.id" class="capitalize" :model-value="column.getIsVisible()"
                            @update:model-value="(value) => {
                                column.toggleVisibility(!!value)
                            }">
                            {{ column.id }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button @click="router.visit('/apartments/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Apartamento
                </Button>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id"
                                :data-pinned="header.column.getIsPinned()" :class="cn(
                                    { 'sticky bg-background/95': header.column.getIsPinned() },
                                    header.column.getIsPinned() === 'left' ? 'left-0' : 'right-0',
                                )">
                                <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                    :props="header.getContext()" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows?.length">
                            <template v-for="row in table.getRowModel().rows" :key="row.id">
                                <TableRow
                                    :data-state="row.getIsSelected() && 'selected'"
                                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                                    @click="router.visit(`/apartments/${row.original.id}`)"
                                >
                                    <TableCell
                                        v-for="cell in row.getVisibleCells()"
                                        :key="cell.id"
                                        :data-pinned="cell.column.getIsPinned()"
                                        :class="cn(
                                            { 'sticky bg-background/95': cell.column.getIsPinned() },
                                            cell.column.getIsPinned() === 'left' ? 'left-0' : 'right-0',
                                            cell.column.id === 'select' || cell.column.id === 'actions' ? 'cursor-default' : ''
                                        )"
                                        @click="cell.column.id === 'select' || cell.column.id === 'actions' ? $event.stopPropagation() : null"
                                    >
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                        </template>

                        <TableRow v-else>
                            <TableCell :colspan="columns.length" class="h-24 text-center">
                                Sin Resultados.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="flex items-center justify-between space-x-2 py-4">
                <div class="flex-1 text-sm text-muted-foreground">
                    {{ table.getFilteredSelectedRowModel().rows.length }} de
                    {{ props.apartments.total }} fila(s) seleccionadas.
                </div>
                <div class="flex items-center space-x-6 lg:space-x-8">
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">Página</p>
                        <div class="flex items-center space-x-1">
                            <span class="text-sm font-medium">{{ currentPage }}</span>
                            <span class="text-sm text-muted-foreground">de {{ totalPages }}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            :disabled="!props.apartments.prev_page_url"
                            @click="previousPage"
                        >
                            Anterior
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            :disabled="!props.apartments.next_page_url" 
                            @click="nextPage"
                        >
                            Siguiente
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>