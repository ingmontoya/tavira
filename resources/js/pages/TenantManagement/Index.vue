<template>

    <Head title="Gestión de Tenants" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Gestión de Tenants</h1>
                    <p class="text-muted-foreground">Administra todos los conjuntos residenciales en la plataforma</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button @click="router.visit('/tenants/create')" class="gap-2">
                        <Icon name="plus" class="h-4 w-4" />
                        Crear Tenant
                    </Button>
                </div>
            </div>

            <!-- Filtros Avanzados -->
            <Card class="mb-4">
                <CardContent class="p-4">
                    <div class="space-y-4">
                        <!-- Búsqueda General -->
                        <div>
                            <Label for="search">Búsqueda General</Label>
                            <div class="relative mt-3">
                                <Icon name="search"
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                <Input id="search" v-model="searchForm.search"
                                    placeholder="Buscar por nombre, email o ID..." @input="debouncedSearch"
                                    class="max-w-md pl-10" />
                            </div>
                        </div>

                        <!-- Filtros por categorías -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div class="min-w-0 space-y-2">
                                <Label for="filter_status">Estado</Label>
                                <Select v-model="searchForm.status" @update:model-value="search">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Todos los estados" class="truncate" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los estados</SelectItem>
                                        <SelectItem value="active">Activos</SelectItem>
                                        <SelectItem value="suspended">Suspendidos</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex items-center justify-between">
                            <Button variant="outline" @click="clearFilters" v-if="hasActiveFilters" class="gap-2">
                                <Icon name="x" class="h-4 w-4" />
                                Limpiar filtros
                            </Button>
                            <div class="text-sm text-muted-foreground">Mostrando {{ filteredCount }} de {{ tenants.total
                                }} tenants</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tenants Table -->
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Conjunto</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Dominios</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Creado</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="tenants.data.length === 0">
                            <TableCell :colspan="6" class="h-24 text-center">
                                <div class="flex flex-col items-center space-y-2 text-muted-foreground">
                                    <Icon name="building" class="h-12 w-12 opacity-50" />
                                    <p>No se encontraron tenants</p>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="tenant in tenants.data" :key="tenant.id"
                            class="cursor-pointer transition-colors hover:bg-muted/50"
                            @click="router.visit(`/tenants/${tenant.id}`)">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                        <Icon name="building" class="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ tenant.name }}</p>
                                        <p class="text-sm text-muted-foreground">ID: {{ tenant.id.substring(0, 8) }}...
                                        </p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm">{{ tenant.email || 'Sin email' }}</span>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="domain in tenant.domains" :key="domain" variant="secondary"
                                        class="text-xs">
                                        {{ domain }}
                                    </Badge>
                                    <span v-if="tenant.domains.length === 0" class="text-sm text-muted-foreground">Sin
                                        dominios</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusVariant(tenant.status)" class="capitalize">
                                    {{ getStatusText(tenant.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ tenant.created_at }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2" @click.stop>
                                    <!-- Impersonate only for superadmin and active tenants -->
                                    <Button v-if="isSuperAdmin && tenant.status === 'active'" variant="outline" size="sm"
                                        @click="impersonateTenant(tenant.id)" class="gap-1">
                                        <Icon name="log-in" class="h-4 w-4" />
                                        Ingresar
                                    </Button>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="sm">
                                                <Icon name="more-horizontal" class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="router.visit(`/tenants/${tenant.id}`)">
                                                <Icon name="eye" class="h-4 w-4 mr-2" />
                                                Ver detalles
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="router.visit(`/tenants/${tenant.id}/edit`)">
                                                <Icon name="edit" class="h-4 w-4 mr-2" />
                                                Editar
                                            </DropdownMenuItem>
                                            <!-- Superadmin-only actions -->
                                            <template v-if="isSuperAdmin">
                                                <DropdownMenuSeparator />
                                                <!-- Suspension actions -->
                                                <DropdownMenuItem v-if="tenant.status === 'active'"
                                                    @click="suspendTenant(tenant.id)" class="text-orange-600">
                                                    <Icon name="pause" class="h-4 w-4 mr-2" />
                                                    Suspender
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="tenant.status === 'suspended'"
                                                    @click="activateTenant(tenant.id)" class="text-green-600">
                                                    <Icon name="play" class="h-4 w-4 mr-2" />
                                                    Reactivar
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="deleteTenant(tenant.id)" class="text-red-600">
                                                    <Icon name="trash" class="h-4 w-4 mr-2" />
                                                    Eliminar
                                                </DropdownMenuItem>
                                            </template>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="tenants.last_page > 1" class="flex items-center justify-end space-x-2 py-4">
                <div class="flex-1 text-sm text-muted-foreground">
                    Mostrando {{ tenants.from }} a {{ tenants.to }} de {{ tenants.total }} tenants
                </div>
                <div class="space-x-2">
                    <Button variant="outline" size="sm" :disabled="!tenants.prev_page_url"
                        @click="router.visit(tenants.prev_page_url)">
                        Anterior
                    </Button>
                    <Button variant="outline" size="sm" :disabled="!tenants.next_page_url"
                        @click="router.visit(tenants.next_page_url)">
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import { debounce } from 'lodash'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import Icon from '@/components/Icon.vue'

interface Tenant {
    id: string
    name: string
    email: string | null
    status: string
    created_at: string
    updated_at: string
    domains: string[]
}

interface Props {
    tenants: {
        data: Tenant[]
        links: any[]
        from: number
        to: number
        total: number
        last_page: number
        prev_page_url: string | null
        next_page_url: string | null
    }
    filters: {
        search?: string
        status?: string
    }
}

const props = defineProps<Props>()

// Access current user data
const page = usePage()
const user = computed(() => page.props.auth?.user)
const isSuperAdmin = computed(() => user.value?.roles?.some((role: any) => role.name === 'superadmin'))

const breadcrumbs = [
    {
        title: 'Panel Central',
        href: '/dashboard',
    },
    {
        title: 'Gestión de Tenants',
        href: '/tenants',
    },
]

const searchForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || 'all',
})

// Check if custom filters are active
const hasActiveFilters = computed(() => {
    return Object.values(searchForm).some((value) => value !== '' && value !== 'all');
})

// Compute filtered count (use the data length for now)
const filteredCount = computed(() => {
    return props.tenants.data.length;
})

const search = () => {
    router.get(route('tenant-management.index'), searchForm, {
        preserveState: true,
        replace: true,
    })
}

const debouncedSearch = debounce(search, 300)

const clearFilters = () => {
    searchForm.search = ''
    searchForm.status = 'all'
    search()
}

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'default'
        case 'suspended': return 'destructive'
        default: return 'secondary'
    }
}

const getStatusText = (status: string) => {
    switch (status) {
        case 'active': return 'Activo'
        case 'suspended': return 'Suspendido'
        default: return status
    }
}

const impersonateTenant = (tenantId: string) => {
    router.post(route('tenant-management.impersonate', tenantId))
}

const suspendTenant = (tenantId: string) => {
    if (confirm('¿Estás seguro de que quieres suspender este tenant?')) {
        router.post(route('tenant-management.suspend', tenantId))
    }
}

const activateTenant = (tenantId: string) => {
    router.post(route('tenant-management.activate', tenantId))
}

const approveTenant = (tenantId: string) => {
    if (confirm('¿Estás seguro de que quieres aprobar este tenant?')) {
        router.post(route('tenant-management.approve', tenantId))
    }
}

const rejectTenant = (tenantId: string) => {
    const reason = prompt('Razón del rechazo (opcional):')
    if (confirm('¿Estás seguro de que quieres rechazar este tenant?')) {
        router.post(route('tenant-management.reject', tenantId), {
            reason: reason || null
        })
    }
}

const deleteTenant = (tenantId: string) => {
    if (confirm('¿Estás seguro de que quieres eliminar este tenant? Esta acción no se puede deshacer.')) {
        router.delete(route('tenant-management.destroy', tenantId))
    }
}
</script>
