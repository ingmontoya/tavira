<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4" data-tour="dashboard">
            <!-- Emergency Panic Alerts Section -->
            <div v-if="activePanicAlerts.length > 0" class="mb-6">
                <Card class="animate-pulse border-red-500 bg-red-50 shadow-lg">
                    <CardHeader class="rounded-t-lg bg-red-600 py-2 text-white">
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="alert-triangle" class="h-6 w-6" />
                            🚨 ALERTAS DE PÁNICO ACTIVAS ({{ activePanicAlerts.length }})
                        </CardTitle>
                        <CardDescription class="text-red-100"> EMERGENCIAS QUE REQUIEREN ATENCIÓN INMEDIATA </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-for="alert in activePanicAlerts" :key="alert.id" class="border-b border-red-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                        <Icon name="alert-triangle" class="h-6 w-6 animate-pulse text-red-600" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-red-900">
                                            {{ alert.user_name || 'Usuario desconocido' }}
                                        </h4>
                                        <p class="text-sm text-red-700">
                                            {{ alert.apartment || 'Apartamento no especificado' }}
                                        </p>
                                        <p class="text-xs text-red-600">Activada: {{ alert.time_elapsed }} | ID: {{ alert.id }}</p>
                                        <p v-if="alert.location" class="text-xs text-red-600">📍 {{ alert.location }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Badge variant="destructive" class="animate-pulse">
                                        {{ alert.status?.toUpperCase() }}
                                    </Badge>
                                    <Button size="sm" variant="outline" @click="resolveAlert(alert.id)"> Marcar como Atendida </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Setup Banner -->
            <ConjuntoSetupBanner />

            <!-- Header with Month Selector and Tour Button -->
            <div class="mb-8">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold tracking-tight">Dashboard Tavira</h1>
                        <p class="text-muted-foreground">Resumen general del Sistema de Gestión para Propiedad Horizontal</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Month Selector -->
                        <div class="flex items-center space-x-2">
                            <Icon name="calendar" class="h-4 w-4 text-muted-foreground" />
                            <select
                                v-model="selectedMonth"
                                @change="onMonthChange"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="
                                    () => {
                                        hasSavedTour ? continueTour() : startTour();
                                    }
                                "
                                class="inline-flex flex-col items-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                                :class="hasSavedTour ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'"
                            >
                                <div class="flex items-center">
                                    <Icon name="play" class="mr-2 h-4 w-4" />
                                    {{ hasSavedTour ? 'Continuar Tour' : 'Tour Guiado' }}
                                </div>
                                <div v-if="hasSavedTour" class="mt-1 text-xs opacity-90">Paso {{ savedTourStep + 1 }} de {{ tourSteps.length }}</div>
                            </button>
                            <button
                                v-if="hasSavedTour"
                                @click="restartTour"
                                class="inline-flex items-center rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700"
                            >
                                <Icon name="refresh-cw" class="mr-2 h-4 w-4" />
                                Reiniciar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs Row -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-5" data-tour="dashboard-metrics">
                <!-- Total Residents KPI -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Residentes</p>
                                <p class="text-3xl font-bold">{{ (kpis.totalResidents || 0).toLocaleString() }}</p>
                                <div class="mt-2 flex items-center">
                                    <Icon
                                        :name="(kpis.residentGrowth || 0) >= 0 ? 'trending-up' : 'trending-down'"
                                        :class="(kpis.residentGrowth || 0) >= 0 ? 'text-green-500' : 'text-red-500'"
                                        class="mr-1 h-4 w-4"
                                    />
                                    <span :class="(kpis.residentGrowth || 0) >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                        {{ Math.abs(kpis.residentGrowth || 0) }}%
                                    </span>
                                    <span class="ml-1 text-sm text-muted-foreground">vs mes anterior</span>
                                </div>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3">
                                <Icon name="users" class="h-8 w-8 text-blue-600" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
                </Card>

                <!-- Total Apartments KPI -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Apartamentos</p>
                                <p class="text-3xl font-bold">{{ kpis.totalApartments || 0 }}</p>
                                <p class="mt-2 text-sm text-muted-foreground">Unidades habitacionales</p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3">
                                <Icon name="home" class="h-8 w-8 text-green-600" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-green-600"></div>
                </Card>

                <!-- Pending Payments KPI -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pagos Pendientes</p>
                                <p class="text-3xl font-bold">{{ kpis.pendingPayments || 0 }}</p>
                                <p class="mt-2 text-sm text-muted-foreground">de {{ kpis.expectedPayments || 0 }} esperados</p>
                            </div>
                            <div class="rounded-full bg-red-100 p-3">
                                <Icon name="alert-circle" class="h-8 w-8 text-red-600" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1 bg-gradient-to-r from-red-500 to-red-600"></div>
                </Card>

                <!-- Expected Payments KPI -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pagos Esperados</p>
                                <p class="text-3xl font-bold">{{ kpis.expectedPayments || 0 }}</p>
                                <p class="mt-2 text-sm text-muted-foreground">Total del mes</p>
                            </div>
                            <div class="rounded-full bg-purple-100 p-3">
                                <Icon name="target" class="h-8 w-8 text-purple-600" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1 bg-gradient-to-r from-purple-500 to-purple-600"></div>
                </Card>

                <!-- Monthly Visitors KPI -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Visitas del Mes</p>
                                <p class="text-3xl font-bold">{{ (kpis.monthlyVisitors || 0).toLocaleString() }}</p>
                                <div class="mt-2 flex items-center">
                                    <Icon
                                        :name="(kpis.visitorGrowth || 0) >= 0 ? 'trending-up' : 'trending-down'"
                                        :class="(kpis.visitorGrowth || 0) >= 0 ? 'text-green-500' : 'text-red-500'"
                                        class="mr-1 h-4 w-4"
                                    />
                                    <span :class="(kpis.visitorGrowth || 0) >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                        {{ Math.abs(kpis.visitorGrowth || 0) }}%
                                    </span>
                                    <span class="ml-1 text-sm text-muted-foreground">vs mes anterior</span>
                                </div>
                            </div>
                            <div class="rounded-full bg-orange-100 p-3">
                                <Icon name="user-check" class="h-8 w-8 text-orange-600" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1 bg-gradient-to-r from-orange-500 to-orange-600"></div>
                </Card>
            </div>

            <!-- Payment Summary Row -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2" v-if="kpis.totalPaymentsExpected || kpis.totalPaymentsReceived">
                <!-- Expected Amount -->
                <Card class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Monto Esperado</h3>
                            <p class="text-3xl font-bold text-blue-600">${{ (kpis.totalPaymentsExpected || 0).toLocaleString() }}</p>
                            <p class="text-sm text-muted-foreground">{{ selectedMonthLabel }}</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3">
                            <Icon name="target" class="h-8 w-8 text-blue-600" />
                        </div>
                    </div>
                </Card>

                <!-- Received Amount -->
                <Card class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Monto Recibido</h3>
                            <p class="text-3xl font-bold text-green-600">${{ (kpis.totalPaymentsReceived || 0).toLocaleString() }}</p>
                            <div class="mt-2 flex items-center">
                                <div class="mr-2 h-2 w-32 rounded-full bg-gray-200">
                                    <div
                                        class="h-2 rounded-full bg-green-500 transition-all"
                                        :style="{
                                            width: `${
                                                kpis.totalPaymentsExpected > 0 ? (kpis.totalPaymentsReceived / kpis.totalPaymentsExpected) * 100 : 0
                                            }%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{
                                        kpis.totalPaymentsExpected > 0
                                            ? Math.round((kpis.totalPaymentsReceived / kpis.totalPaymentsExpected) * 100)
                                            : 0
                                    }}%
                                </span>
                            </div>
                        </div>
                        <div class="rounded-full bg-green-100 p-3">
                            <Icon name="dollar-sign" class="h-8 w-8 text-green-600" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Overdue Payments from Previous Months -->
            <div
                v-if="
                    overduePayments &&
                    (overduePayments.total_overdue_apartments > 0 ||
                        overduePayments.total_overdue_invoices > 0 ||
                        overduePayments.total_overdue_amount > 0)
                "
            >
                <Card class="border-red-200 bg-red-50">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-red-900">Pagos Vencidos de Meses Anteriores</h3>
                                <p class="text-sm text-red-700">Facturas pendientes generadas en meses previos al seleccionado</p>
                            </div>
                            <div class="rounded-full bg-red-200 p-3">
                                <Icon name="alert-triangle" class="h-8 w-8 text-red-700" />
                            </div>
                        </div>

                        <!-- Summary Cards Grid -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <!-- Overdue Apartments -->
                            <Card class="bg-white">
                                <div class="p-4">
                                    <p class="text-sm font-medium text-gray-600">Apartamentos en Mora</p>
                                    <p class="mt-2 text-3xl font-bold text-red-600">{{ overduePayments.total_overdue_apartments || 0 }}</p>
                                </div>
                            </Card>

                            <!-- Overdue Invoices -->
                            <Card class="bg-white">
                                <div class="p-4">
                                    <p class="text-sm font-medium text-gray-600">Facturas Vencidas</p>
                                    <p class="mt-2 text-3xl font-bold text-red-600">{{ overduePayments.total_overdue_invoices || 0 }}</p>
                                </div>
                            </Card>

                            <!-- Total Overdue Amount -->
                            <Card class="bg-white">
                                <div class="p-4">
                                    <p class="text-sm font-medium text-gray-600">Monto Total en Mora</p>
                                    <p class="mt-2 text-3xl font-bold text-red-600">
                                        ${{ (overduePayments.total_overdue_amount || 0).toLocaleString() }}
                                    </p>
                                </div>
                            </Card>

                            <!-- Average Days Overdue -->
                            <Card class="bg-white">
                                <div class="p-4">
                                    <p class="text-sm font-medium text-gray-600">Promedio Días Mora</p>
                                    <p class="mt-2 text-3xl font-bold text-red-600">{{ overduePayments.average_days_overdue || 0 }}</p>
                                    <p class="text-xs text-gray-500">días</p>
                                </div>
                            </Card>
                        </div>

                        <!-- Overdue Distribution by Range -->
                        <div v-if="overduePayments.overdue_by_range" class="mt-6">
                            <h4 class="mb-3 text-sm font-semibold text-red-900">Distribución por Rango de Días</h4>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <!-- 0-30 Days -->
                                <Card class="border-l-4 border-orange-400 bg-white">
                                    <div class="p-3">
                                        <p class="text-xs font-medium text-gray-600">0-30 días</p>
                                        <p class="text-xl font-bold text-orange-600">
                                            {{ overduePayments.overdue_by_range['0-30']?.count || 0 }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            ${{ (overduePayments.overdue_by_range['0-30']?.amount || 0).toLocaleString() }}
                                        </p>
                                    </div>
                                </Card>

                                <!-- 30-60 Days -->
                                <Card class="border-l-4 border-red-400 bg-white">
                                    <div class="p-3">
                                        <p class="text-xs font-medium text-gray-600">30-60 días</p>
                                        <p class="text-xl font-bold text-red-600">
                                            {{ overduePayments.overdue_by_range['30-60']?.count || 0 }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            ${{ (overduePayments.overdue_by_range['30-60']?.amount || 0).toLocaleString() }}
                                        </p>
                                    </div>
                                </Card>

                                <!-- 60-90 Days -->
                                <Card class="border-l-4 border-red-600 bg-white">
                                    <div class="p-3">
                                        <p class="text-xs font-medium text-gray-600">60-90 días</p>
                                        <p class="text-xl font-bold text-red-700">
                                            {{ overduePayments.overdue_by_range['60-90']?.count || 0 }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            ${{ (overduePayments.overdue_by_range['60-90']?.amount || 0).toLocaleString() }}
                                        </p>
                                    </div>
                                </Card>

                                <!-- 90+ Days -->
                                <Card class="border-l-4 border-red-900 bg-white">
                                    <div class="p-3">
                                        <p class="text-xs font-medium text-gray-600">90+ días</p>
                                        <p class="text-xl font-bold text-red-900">
                                            {{ overduePayments.overdue_by_range['90+']?.count || 0 }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            ${{ (overduePayments.overdue_by_range['90+']?.amount || 0).toLocaleString() }}
                                        </p>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Payment Status Chart -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Estado de Pagos</h3>
                        <p class="text-sm text-muted-foreground">Distribución de pagos por estado - {{ selectedMonthLabel }}</p>
                    </div>
                    <div class="flex h-80 items-center justify-center">
                        <div v-if="charts.paymentsByStatus && charts.paymentsByStatus.length > 0" class="h-full w-full">
                            <canvas ref="statusChart" class="max-h-full max-w-full"></canvas>
                        </div>
                        <div v-else class="text-center text-muted-foreground">
                            <Icon name="doughnut-chart" class="mx-auto mb-2 h-12 w-12 opacity-50" />
                            <p>No hay datos disponibles</p>
                        </div>
                    </div>
                </Card>

                <!-- Collection Efficiency -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Eficiencia de Recaudo</h3>
                        <p class="text-sm text-muted-foreground">Comparación de recaudo vs esperado - {{ selectedMonthLabel }}</p>
                    </div>
                    <div class="flex h-80 flex-col items-center justify-center">
                        <div v-if="kpis.totalPaymentsExpected > 0" class="w-full space-y-6">
                            <!-- Progress Circle -->
                            <div class="flex justify-center">
                                <div class="relative h-48 w-48">
                                    <svg class="h-full w-full -rotate-90 transform">
                                        <circle
                                            cx="96"
                                            cy="96"
                                            r="80"
                                            stroke="currentColor"
                                            stroke-width="16"
                                            fill="transparent"
                                            class="text-gray-200"
                                        />
                                        <circle
                                            cx="96"
                                            cy="96"
                                            r="80"
                                            stroke="currentColor"
                                            stroke-width="16"
                                            fill="transparent"
                                            :stroke-dasharray="502.4"
                                            :stroke-dashoffset="
                                                502.4 -
                                                (502.4 *
                                                    (kpis.totalPaymentsExpected > 0
                                                        ? kpis.totalPaymentsReceived / kpis.totalPaymentsExpected
                                                        : 0))
                                            "
                                            :class="
                                                kpis.totalPaymentsExpected > 0 &&
                                                kpis.totalPaymentsReceived / kpis.totalPaymentsExpected >= 0.8
                                                    ? 'text-green-500'
                                                    : kpis.totalPaymentsReceived / kpis.totalPaymentsExpected >= 0.6
                                                      ? 'text-yellow-500'
                                                      : 'text-red-500'
                                            "
                                            class="transition-all duration-1000 ease-out"
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-3xl font-bold">
                                            {{
                                                kpis.totalPaymentsExpected > 0
                                                    ? Math.round((kpis.totalPaymentsReceived / kpis.totalPaymentsExpected) * 100)
                                                    : 0
                                            }}%
                                        </span>
                                        <span class="text-xs text-muted-foreground">Recaudado</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4 px-8">
                                <div class="space-y-1 text-center">
                                    <p class="text-xs text-muted-foreground">Esperado</p>
                                    <p class="text-lg font-semibold text-blue-600">${{ (kpis.totalPaymentsExpected || 0).toLocaleString() }}</p>
                                </div>
                                <div class="space-y-1 text-center">
                                    <p class="text-xs text-muted-foreground">Recaudado</p>
                                    <p class="text-lg font-semibold text-green-600">${{ (kpis.totalPaymentsReceived || 0).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-muted-foreground">
                            <Icon name="bar-chart-2" class="mx-auto mb-2 h-12 w-12 opacity-50" />
                            <p>No hay datos de recaudo disponibles</p>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Occupancy Status -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Estado de Ocupación</h3>
                        <p class="text-sm text-muted-foreground">Distribución por estado de apartamentos</p>
                    </div>
                    <div class="space-y-4">
                        <div v-for="status in charts.occupancyStatus || []" :key="status.status" class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-4 w-4 rounded" :style="{ backgroundColor: status.color }"></div>
                                <span class="text-sm font-medium">{{ status.status }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-muted-foreground">{{ status.count }}</span>
                                <div class="h-2 w-20 rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full transition-all"
                                        :style="{
                                            backgroundColor: status.color,
                                            width: `${getTotalApartments() > 0 ? (status.count / getTotalApartments()) * 100 : 0}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Monthly Expenses -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Gastos Mensuales</h3>
                        <p class="text-sm text-muted-foreground">Top categorías de gastos</p>
                    </div>
                    <div class="max-h-64 space-y-3 overflow-y-auto">
                        <div v-for="expense in (charts.monthlyExpenses || []).slice(0, 8)" :key="expense.category" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="truncate text-sm font-medium">{{ expense.category }}</span>
                                <span class="text-xs text-muted-foreground">${{ (expense.amount || 0).toLocaleString() }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full transition-all"
                                    :style="{
                                        backgroundColor: expense.color,
                                        width: `${expense.percentage}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Recent Activity -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Actividad Reciente</h3>
                        <p class="text-sm text-muted-foreground">Últimas acciones en el sistema</p>
                    </div>
                    <div class="max-h-64 space-y-4 overflow-y-auto">
                        <div v-for="activity in recentActivity || []" :key="activity.message" class="flex items-start space-x-3">
                            <div class="flex-shrink-0 rounded-full bg-muted p-2">
                                <Icon :name="activity.icon" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm">{{ activity.message }}</p>
                                <p class="text-xs text-muted-foreground">{{ activity.time }}</p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Payment Collection Trend Chart -->
            <Card class="p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">Tendencia de Recaudo</h3>
                    <p class="text-sm text-muted-foreground">Evolución del recaudo en los últimos 6 meses</p>
                </div>
                <div class="flex h-80 items-center justify-center">
                    <div v-if="charts.paymentTrend && charts.paymentTrend.length > 0" class="h-full w-full">
                        <canvas ref="trendChart" class="max-h-full max-w-full"></canvas>
                    </div>
                    <div v-else class="text-center text-muted-foreground">
                        <Icon name="trending-up" class="mx-auto mb-2 h-12 w-12 opacity-50" />
                        <p>No hay datos de tendencia disponibles</p>
                    </div>
                </div>
            </Card>

            <!-- Pending Notifications -->
            <Card class="p-6" v-if="pendingNotifications && pendingNotifications.length > 0">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">Notificaciones Pendientes</h3>
                    <p class="text-sm text-muted-foreground">Avisos y comunicados por enviar</p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="notification in pendingNotifications || []" :key="notification.id" class="border-l-4 border-l-yellow-500 p-4">
                        <div class="space-y-2">
                            <h4 class="font-semibold">{{ notification.title }}</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Destinatarios:</span>
                                    <span class="ml-1 font-medium">{{ notification.recipients_count }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Tipo:</span>
                                    <span class="ml-1 font-medium">{{ notification.type }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatDate(notification.created_at) }}
                            </div>
                        </div>
                    </Card>
                </div>
            </Card>
        </div>

        <!-- Virtual Tour Component -->
        <VirtualTour ref="virtualTourRef" :steps="tourSteps" @complete="onTourComplete" @close="onTourClose" />
    </AppLayout>
</template>

<script setup lang="ts">
import ConjuntoSetupBanner from '@/components/ConjuntoSetupBanner.vue';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import VirtualTour from '@/components/VirtualTour.vue';
import { useFlow1Tour } from '@/composables/useFlow1Tour';
import { globalToast } from '@/composables/useToast';
import { useTourState } from '@/composables/useTourState';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({
    kpis: {
        type: Object,
        default: () => ({}),
    },
    charts: {
        type: Object,
        default: () => ({}),
    },
    overduePayments: {
        type: Object,
        default: () => ({
            total_overdue_apartments: 0,
            total_overdue_amount: 0,
            total_overdue_invoices: 0,
            average_days_overdue: 0,
            overdue_by_range: {},
        }),
    },
    pendingNotifications: {
        type: Array,
        default: () => [],
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
    selectedMonth: {
        type: String,
        default: '',
    },
    availableMonths: {
        type: Array,
        default: () => [],
    },
});

const statusChart = ref(null);
const trendChart = ref(null);
const selectedMonth = ref(props.selectedMonth);

// Panic alerts functionality
const activePanicAlerts = ref([]);
const { success: showToast } = globalToast;

// Virtual Tour functionality
const virtualTourRef = ref(null);
const { tourSteps } = useFlow1Tour();
const { hasSavedTour, savedTourStep, checkSavedTour } = useTourState();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
];

const startTour = () => {
    if (virtualTourRef.value) {
        virtualTourRef.value.startTour();
        setTimeout(() => {
            checkSavedTour();
        }, 100);
    }
};

const continueTour = () => {
    if (virtualTourRef.value) {
        virtualTourRef.value.loadAndContinueTour();
    }
};

const restartTour = () => {
    // Limpiar estado guardado y empezar de nuevo
    localStorage.removeItem('sia-tour-state');
    if (virtualTourRef.value) {
        virtualTourRef.value.startTour();
        checkSavedTour();
    }
};

const onTourComplete = () => {
    // Esperar un momento para que se limpie el localStorage
    setTimeout(() => {
        checkSavedTour();
    }, 100);
};

const onTourClose = () => {
    // Esperar un momento para que se limpie el localStorage
    setTimeout(() => {
        checkSavedTour();
    }, 100);
};

const selectedMonthLabel = computed(() => {
    const monthOption = props.availableMonths.find((m) => m.value === selectedMonth.value);
    return monthOption ? monthOption.label : '';
});

const onMonthChange = () => {
    router.get(
        route('dashboard'),
        { month: selectedMonth.value },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const getTotalApartments = () => {
    return props.charts?.occupancyStatus?.reduce((sum, item) => sum + item.count, 0) || 0;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-CO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const initCharts = async () => {
    await nextTick();

    try {
        const Chart = (await import('chart.js/auto')).default;

        // Payment Status Chart
        if (statusChart.value && props.charts?.paymentsByStatus?.length > 0) {
            // Validate and sanitize data
            const validStatusData = props.charts.paymentsByStatus.filter(
                (item) => item && typeof item.count === 'number' && !isNaN(item.count) && item.count >= 0 && item.status && item.color,
            );

            if (validStatusData.length > 0) {
                new Chart(statusChart.value, {
                    type: 'doughnut',
                    data: {
                        labels: validStatusData.map((item) => item.status),
                        datasets: [
                            {
                                data: validStatusData.map((item) => Math.max(0, item.count)),
                                backgroundColor: validStatusData.map((item) => item.color),
                                borderWidth: 2,
                                borderColor: '#ffffff',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                },
                            },
                        },
                    },
                });
            }
        }

        // Payment Trend Chart
        if (trendChart.value && props.charts?.paymentTrend?.length > 0) {
            // Validate and sanitize data
            const validTrendData = props.charts.paymentTrend.filter(
                (item) => item && typeof item.amount === 'number' && !isNaN(item.amount) && item.amount >= 0 && item.label,
            );

            if (validTrendData.length > 0) {
                new Chart(trendChart.value, {
                    type: 'line',
                    data: {
                        labels: validTrendData.map((item) => item.label),
                        datasets: [
                            {
                                label: 'Recaudo',
                                data: validTrendData.map((item) => Math.max(0, item.amount)),
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#3b82f6',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 6,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)',
                                },
                            },
                            x: {
                                grid: {
                                    display: false,
                                },
                            },
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                    },
                });
            }
        }
    } catch (error) {
        console.error('Error loading Chart.js:', error);
    }
};

// Panic alerts functions
const loadPanicAlerts = async () => {
    try {
        const response = await fetch('/api/dashboard/panic-alerts', {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success && data.alerts) {
                // Only show triggered alerts
                activePanicAlerts.value = data.alerts.filter((alert) => alert.status === 'triggered');
            }
        } else {
            console.warn('Failed to load panic alerts:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Error loading panic alerts:', error);
    }
};

const resolveAlert = async (alertId) => {
    try {
        const response = await fetch(`/api/dashboard/panic-alerts/${alertId}/resolve`, {
            method: 'PATCH',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            // Remove from active alerts
            activePanicAlerts.value = activePanicAlerts.value.filter((alert) => alert.id !== alertId);

            // Show success message with toast
            showToast(`Alerta ${alertId} marcada como atendida`, 'Éxito');
        } else {
            alert('Error al marcar la alerta como atendida');
        }
    } catch (error) {
        console.error('Error resolving panic alert:', error);
        alert('Error al marcar la alerta como atendida');
    }
};

onMounted(() => {
    initCharts();
    checkSavedTour();

    // Load panic alerts initially
    loadPanicAlerts();

    // Check for new panic alerts every 5 seconds
    setInterval(loadPanicAlerts, 5000);

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});
</script>
