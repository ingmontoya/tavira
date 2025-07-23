<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import { CreditCard, Receipt, Settings, TrendingUp, Users, AlertTriangle } from 'lucide-vue-next'

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Gestión de Pagos',
        href: '/payments',
    },
]
</script>

<template>
    <Head title="Gestión de Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Gestión de Pagos</h1>
                <p class="text-muted-foreground">
                    Administra facturas, conceptos de pago y reportes financieros del conjunto
                </p>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Facturas Pendientes</CardTitle>
                    <Receipt class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-orange-600">--</div>
                    <p class="text-xs text-muted-foreground">
                        Facturas por cobrar
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Recaudo Mensual</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">--</div>
                    <p class="text-xs text-muted-foreground">
                        Ingresos del mes actual
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Apartamentos en Mora</CardTitle>
                    <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">--</div>
                    <p class="text-xs text-muted-foreground">
                        Con pagos vencidos
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Conceptos Activos</CardTitle>
                    <Settings class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">--</div>
                    <p class="text-xs text-muted-foreground">
                        Conceptos de pago configurados
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Main Actions -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <!-- Facturas -->
            <Card class="cursor-pointer transition-all hover:shadow-md">
                <CardHeader>
                    <div class="flex items-center space-x-2">
                        <Receipt class="h-5 w-5 text-blue-600" />
                        <CardTitle class="text-lg">Facturas</CardTitle>
                    </div>
                    <CardDescription>
                        Gestiona las facturas de administración, visualiza estados de pago y registra pagos
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <Button asChild class="w-full" variant="default">
                            <Link href="/invoices">Ver Todas las Facturas</Link>
                        </Button>
                        <div class="flex space-x-2">
                            <Button asChild class="flex-1" variant="outline" size="sm">
                                <Link href="/invoices/create">Crear Factura</Link>
                            </Button>
                            <Button asChild class="flex-1" variant="outline" size="sm">
                                <Link href="/invoices?status=pending">Pendientes</Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Conceptos de Pago -->
            <Card class="cursor-pointer transition-all hover:shadow-md">
                <CardHeader>
                    <div class="flex items-center space-x-2">
                        <Settings class="h-5 w-5 text-green-600" />
                        <CardTitle class="text-lg">Conceptos de Pago</CardTitle>
                    </div>
                    <CardDescription>
                        Configura los conceptos de facturación: administración, sanciones, parqueaderos
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <Button asChild class="w-full" variant="default">
                            <Link href="/payment-concepts">Gestionar Conceptos</Link>
                        </Button>
                        <div class="flex space-x-2">
                            <Button asChild class="flex-1" variant="outline" size="sm">
                                <Link href="/payment-concepts/create">Nuevo Concepto</Link>
                            </Button>
                            <Button asChild class="flex-1" variant="outline" size="sm">
                                <Link href="/payment-concepts?type=common_expense">Administración</Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Generación Masiva -->
            <Card class="cursor-pointer transition-all hover:shadow-md">
                <CardHeader>
                    <div class="flex items-center space-x-2">
                        <CreditCard class="h-5 w-5 text-purple-600" />
                        <CardTitle class="text-lg">Facturación Masiva</CardTitle>
                    </div>
                    <CardDescription>
                        Genera facturas mensuales automáticamente para todos los apartamentos ocupados
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <Button class="w-full" variant="default" disabled>
                            Generar Facturas Mensual
                        </Button>
                        <div class="text-xs text-muted-foreground">
                            <p>• Facturas basadas en conceptos recurrentes</p>
                            <p>• Solo apartamentos ocupados</p>
                            <p>• Verificación de duplicados</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Activity -->
        <Card>
            <CardHeader>
                <CardTitle>Actividad Reciente</CardTitle>
                <CardDescription>
                    Últimas facturas creadas y pagos registrados
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="text-center py-8 text-muted-foreground">
                    <Receipt class="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p>No hay actividad reciente</p>
                    <p class="text-sm">Las facturas y pagos aparecerán aquí cuando se generen</p>
                </div>
            </CardContent>
        </Card>
    </div>
    </AppLayout>
</template>
