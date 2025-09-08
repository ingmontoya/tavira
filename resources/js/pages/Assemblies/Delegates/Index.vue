<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Users, UserCheck, UserX } from 'lucide-vue-next';

interface Assembly {
    id: number;
    title: string;
    status: string;
}

interface Delegate {
    id: number;
    delegator_apartment: {
        number: string;
        type: string;
    };
    delegate_user: {
        name: string;
        email: string;
    };
    status: string;
    created_at: string;
}

const props = defineProps<{
    assembly: Assembly;
    delegates: Delegate[];
}>();

const getStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
        approved: { text: 'Aprobado', class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazado', class: 'bg-red-100 text-red-800' },
    };
    return badges[status] || badges.pending;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
    { title: 'Delegados', href: `/assemblies/${props.assembly.id}/delegates` },
];
</script>

<template>
    <Head title="Delegados - {{ assembly.title }}" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <Link 
                            :href="`/assemblies/${assembly.id}`"
                            class="flex items-center gap-2 text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Volver a la Asamblea
                        </Link>
                    </div>
                    <div class="flex items-center gap-3">
                        <Users class="h-8 w-8 text-blue-500" />
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight">Delegados</h1>
                            <p class="text-muted-foreground">{{ assembly.title }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        v-if="assembly.status === 'scheduled'"
                        asChild
                    >
                        <Link :href="`/assemblies/${assembly.id}/delegates/create`">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Delegado
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Delegates List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5" />
                        Lista de Delegados
                    </CardTitle>
                    <CardDescription>
                        Gestión de delegaciones para la asamblea
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="delegates.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Apartamento</TableHead>
                                    <TableHead>Delegado</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="delegate in delegates" :key="delegate.id">
                                    <TableCell>
                                        <div class="font-medium">{{ delegate.delegator_apartment.number }}</div>
                                        <div class="text-sm text-muted-foreground">{{ delegate.delegator_apartment.type }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="font-medium">{{ delegate.delegate_user.name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ delegate.delegate_user.email }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadge(delegate.status).class">
                                            {{ getStatusBadge(delegate.status).text }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ formatDate(delegate.created_at) }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button
                                                v-if="delegate.status === 'pending'"
                                                size="sm"
                                                variant="outline"
                                                @click="() => router.post(`/assemblies/${assembly.id}/delegates/${delegate.id}/approve`)"
                                            >
                                                <UserCheck class="mr-1 h-3 w-3" />
                                                Aprobar
                                            </Button>
                                            <Button
                                                v-if="delegate.status === 'pending'"
                                                size="sm"
                                                variant="outline"
                                                @click="() => router.post(`/assemblies/${assembly.id}/delegates/${delegate.id}/reject`)"
                                            >
                                                <UserX class="mr-1 h-3 w-3" />
                                                Rechazar
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    
                    <div v-else class="text-center py-12">
                        <Users class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay delegados</h3>
                        <p class="text-gray-500 mb-4">Aún no se han registrado delegaciones para esta asamblea.</p>
                        <Button
                            v-if="assembly.status === 'scheduled'"
                            asChild
                        >
                            <Link :href="`/assemblies/${assembly.id}/delegates/create`">
                                <Plus class="mr-2 h-4 w-4" />
                                Crear Primera Delegación
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>