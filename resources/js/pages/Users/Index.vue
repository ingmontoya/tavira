<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Settings, Shield, UserPlus, Users } from 'lucide-vue-next';

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Usuarios',
        href: '/users',
    },
];
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Gestión de Usuarios</h1>
                    <p class="text-muted-foreground">Administra los usuarios del sistema y sus permisos</p>
                </div>
                <Button>
                    <UserPlus class="mr-2 h-4 w-4" />
                    Nuevo Usuario
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Usuarios</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">usuarios registrados</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Administradores</CardTitle>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">con permisos admin</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Usuarios Activos</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">activos este mes</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                        <Settings class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">invitaciones pendientes</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Lista de Usuarios</CardTitle>
                    <CardDescription> Gestiona los usuarios del sistema, sus roles y permisos </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <Users class="mb-4 h-16 w-16 text-muted-foreground" />
                        <h3 class="mb-2 text-lg font-semibold">Gestión de Usuarios</h3>
                        <p class="mb-6 max-w-md text-muted-foreground">
                            Esta funcionalidad está en desarrollo. Pronto podrás gestionar todos los usuarios del sistema, asignar roles y controlar
                            permisos de acceso.
                        </p>
                        <div class="flex gap-2">
                            <Button variant="outline">
                                <Shield class="mr-2 h-4 w-4" />
                                Ver Permisos
                            </Button>
                            <Button>
                                <UserPlus class="mr-2 h-4 w-4" />
                                Invitar Usuario
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
