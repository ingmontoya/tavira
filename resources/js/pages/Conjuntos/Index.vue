<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Building2, MapPin, Users, Settings } from 'lucide-vue-next';

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Conjuntos',
        href: '/conjuntos',
    },
];
</script>

<template>
    <Head title="Conjuntos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Gestión de Conjuntos</h1>
                    <p class="text-muted-foreground">
                        Administra la configuración de conjuntos residenciales
                    </p>
                </div>
                <Button>
                    <Building2 class="mr-2 h-4 w-4" />
                    Ver Configuración
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Conjunto Actual</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">1</div>
                        <p class="text-xs text-muted-foreground">
                            conjunto configurado
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Apartamentos</CardTitle>
                        <MapPin class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">-</div>
                        <p class="text-xs text-muted-foreground">
                            apartamentos disponibles
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Residentes</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">-</div>
                        <p class="text-xs text-muted-foreground">
                            residentes registrados
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Configuración</CardTitle>
                        <Settings class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">Activa</div>
                        <p class="text-xs text-muted-foreground">
                            estado del sistema
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Configuración del Conjunto</CardTitle>
                    <CardDescription>
                        Gestiona la configuración actual del conjunto residencial
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <Building2 class="h-16 w-16 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-semibold mb-2">Configuración de Conjunto</h3>
                        <p class="text-muted-foreground mb-6 max-w-md">
                            El sistema está configurado para manejar un conjunto residencial único. 
                            Aquí puedes ver y gestionar la configuración actual del conjunto.
                        </p>
                        <div class="flex gap-2">
                            <Button variant="outline">
                                <Settings class="mr-2 h-4 w-4" />
                                Ver Configuración
                            </Button>
                            <Button>
                                <MapPin class="mr-2 h-4 w-4" />
                                Ver Apartamentos
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm">Información del Sistema</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        Esta aplicación está configurada para gestionar un solo conjunto residencial. 
                        Para configurar o modificar los datos del conjunto, visita la sección de 
                        <strong>Configuración del Conjunto</strong> en el menú principal.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>