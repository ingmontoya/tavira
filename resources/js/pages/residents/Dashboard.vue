<template>
    <AppLayout>
        <AppContent class="space-y-6 p-6">
            <!-- Welcome Header -->
            <div class="mb-8">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold tracking-tight">Mi Panel Residencial</h1>
                        <p class="text-muted-foreground">Información personalizada para residentes de {{ conjuntoName }}</p>
                        <div v-if="apartment" class="mt-2">
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                <Icon name="home" class="mr-1 h-4 w-4" />
                                Apartamento {{ apartment.number }} - Torre {{ apartment.tower }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">{{ currentDate }}</p>
                    </div>
                </div>
            </div>

            <!-- Account Status Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Account Balance -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Estado de Cuenta</p>
                                <p class="text-3xl font-bold" :class="accountStatus.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                                    ${{ Math.abs(accountStatus.balance).toLocaleString() }}
                                </p>
                                <p class="mt-2 text-sm" :class="accountStatus.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ accountStatus.balance >= 0 ? 'A favor' : 'Saldo pendiente' }}
                                </p>
                            </div>
                            <div class="rounded-full p-3" :class="accountStatus.balance >= 0 ? 'bg-green-100' : 'bg-red-100'">
                                <Icon name="credit-card" class="h-8 w-8" :class="accountStatus.balance >= 0 ? 'text-green-600' : 'text-red-600'" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1" :class="accountStatus.balance >= 0 ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600'"></div>
                </Card>

                <!-- Next Payment -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Próximo Pago</p>
                                <p class="text-2xl font-bold">${{ nextPayment.amount.toLocaleString() }}</p>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    Vence {{ nextPayment.dueDate }}
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3">
                                <Icon name="calendar" class="h-8 w-8 text-blue-600" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
                </Card>

                <!-- Payment Status -->
                <Card class="relative overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Estado</p>
                                <p class="text-2xl font-bold" :class="paymentStatusColor">{{ paymentStatus.label }}</p>
                                <p class="mt-2 text-sm text-muted-foreground">{{ paymentStatus.description }}</p>
                            </div>
                            <div class="rounded-full p-3" :class="paymentStatus.bgColor">
                                <Icon :name="paymentStatus.icon" class="h-8 w-8" :class="paymentStatus.color" />
                            </div>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 left-0 h-1" :class="paymentStatus.gradientClass"></div>
                </Card>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Communications -->
                <Card class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Comunicados</h3>
                            <p class="text-sm text-muted-foreground">Últimas noticias de la administración</p>
                        </div>
                        <Link href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                            Ver todos
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="announcement in communications" :key="announcement.id" class="rounded-lg border border-border p-4">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 rounded-full bg-blue-100 p-2">
                                    <Icon name="megaphone" class="h-4 w-4 text-blue-600" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium">{{ announcement.title }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground line-clamp-2">{{ announcement.content }}</p>
                                    <p class="mt-2 text-xs text-muted-foreground">{{ formatDate(announcement.date) }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span v-if="announcement.isNew" class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                        Nuevo
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="communications.length === 0" class="text-center py-8">
                            <Icon name="megaphone" class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-muted-foreground">No hay comunicados recientes</p>
                        </div>
                    </div>
                </Card>

                <!-- Visits Management -->
                <Card class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Mis Visitas</h3>
                            <p class="text-sm text-muted-foreground">Gestiona visitantes y autorizaciones</p>
                        </div>
                        <button class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            <Icon name="plus" class="mr-1 h-4 w-4" />
                            Nueva visita
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div v-for="visit in visits" :key="visit.id" class="rounded-lg border border-border p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 rounded-full bg-green-100 p-2">
                                        <Icon name="user-check" class="h-4 w-4 text-green-600" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium">{{ visit.visitorName }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ visit.purpose }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ visit.date }} • {{ visit.time }}
                                        </p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="getVisitStatusClass(visit.status)">
                                    {{ visit.status }}
                                </span>
                            </div>
                        </div>
                        <div v-if="visits.length === 0" class="text-center py-8">
                            <Icon name="users" class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-muted-foreground">No hay visitas programadas</p>
                        </div>
                    </div>
                </Card>

                <!-- Package Tracking -->
                <Card class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Paquetes</h3>
                            <p class="text-sm text-muted-foreground">Seguimiento de entregas</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                            {{ packages.filter(p => p.status === 'Pendiente').length }} pendientes
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div v-for="packageItem in packages" :key="packageItem.id" class="rounded-lg border border-border p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 rounded-full bg-orange-100 p-2">
                                        <Icon name="package" class="h-4 w-4 text-orange-600" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium">{{ packageItem.sender }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ packageItem.description }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            Recibido {{ formatDate(packageItem.receivedDate) }}
                                        </p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="getPackageStatusClass(packageItem.status)">
                                    {{ packageItem.status }}
                                </span>
                            </div>
                        </div>
                        <div v-if="packages.length === 0" class="text-center py-8">
                            <Icon name="package" class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-muted-foreground">No hay paquetes registrados</p>
                        </div>
                    </div>
                </Card>

                <!-- Account Summary -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Resumen de Cuenta</h3>
                        <p class="text-sm text-muted-foreground">Últimos movimientos financieros</p>
                    </div>
                    <div class="space-y-4">
                        <div v-for="transaction in accountTransactions" :key="transaction.id" class="flex items-center justify-between rounded-lg border border-border p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 rounded-full p-2" :class="transaction.type === 'payment' ? 'bg-green-100' : 'bg-blue-100'">
                                    <Icon :name="transaction.type === 'payment' ? 'credit-card' : 'file-text'" class="h-4 w-4" :class="transaction.type === 'payment' ? 'text-green-600' : 'text-blue-600'" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ transaction.description }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(transaction.date) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium" :class="transaction.type === 'payment' ? 'text-green-600' : 'text-blue-600'">
                                    {{ transaction.type === 'payment' ? '-' : '+' }}${{ Math.abs(transaction.amount).toLocaleString() }}
                                </p>
                            </div>
                        </div>
                        <div class="text-center">
                            <Link href="/account-statement" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                Ver estado de cuenta completo
                            </Link>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card class="p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">Acciones Rápidas</h3>
                    <p class="text-sm text-muted-foreground">Funciones frecuentes para residentes</p>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <button class="flex flex-col items-center justify-center rounded-lg border border-border p-4 text-center hover:bg-muted/50 transition-colors">
                        <Icon name="credit-card" class="h-8 w-8 text-blue-600 mb-2" />
                        <span class="text-sm font-medium">Pagar</span>
                    </button>
                    <button class="flex flex-col items-center justify-center rounded-lg border border-border p-4 text-center hover:bg-muted/50 transition-colors">
                        <Icon name="file-text" class="h-8 w-8 text-green-600 mb-2" />
                        <span class="text-sm font-medium">Certificados</span>
                    </button>
                    <button class="flex flex-col items-center justify-center rounded-lg border border-border p-4 text-center hover:bg-muted/50 transition-colors">
                        <Icon name="user-plus" class="h-8 w-8 text-purple-600 mb-2" />
                        <span class="text-sm font-medium">Autorizar Visita</span>
                    </button>
                    <button class="flex flex-col items-center justify-center rounded-lg border border-border p-4 text-center hover:bg-muted/50 transition-colors">
                        <Icon name="phone" class="h-8 w-8 text-orange-600 mb-2" />
                        <span class="text-sm font-medium">PQRS</span>
                    </button>
                </div>
            </Card>
        </AppContent>
    </AppLayout>
</template>

<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import Icon from '@/components/Icon.vue';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    accountStatus: {
        type: Object,
        default: () => ({ balance: 0 }),
    },
    apartment: {
        type: Object,
        default: null,
    },
    conjuntoName: {
        type: String,
        default: 'Tu Conjunto',
    },
    nextPayment: {
        type: Object,
        default: () => ({ amount: 0, dueDate: '' }),
    },
    paymentStatus: {
        type: Object,
        default: () => ({ status: 'al_dia', daysOverdue: 0 }),
    },
    communications: {
        type: Array,
        default: () => [],
    },
    visits: {
        type: Array,
        default: () => [],
    },
    packages: {
        type: Array,
        default: () => [],
    },
    accountTransactions: {
        type: Array,
        default: () => [],
    },
});

const currentDate = computed(() => {
    return new Date().toLocaleDateString('es-CO', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const paymentStatusColor = computed(() => {
    switch (props.paymentStatus.status) {
        case 'al_dia':
            return 'text-green-600';
        case 'vencido':
            return 'text-red-600';
        case 'proximo_vencimiento':
            return 'text-yellow-600';
        default:
            return 'text-gray-600';
    }
});

const paymentStatus = computed(() => {
    const baseStatus = props.paymentStatus;
    
    switch (baseStatus.status) {
        case 'al_dia':
            return {
                label: 'Al día',
                description: 'Sin pagos pendientes',
                icon: 'check-circle',
                color: 'text-green-600',
                bgColor: 'bg-green-100',
                gradientClass: 'bg-gradient-to-r from-green-500 to-green-600',
            };
        case 'proximo_vencimiento':
            return {
                label: 'Próximo vencimiento',
                description: `Vence en ${baseStatus.daysUntilDue || 0} días`,
                icon: 'clock',
                color: 'text-yellow-600',
                bgColor: 'bg-yellow-100',
                gradientClass: 'bg-gradient-to-r from-yellow-500 to-yellow-600',
            };
        case 'vencido':
            return {
                label: 'Vencido',
                description: `${baseStatus.daysOverdue || 0} días de mora`,
                icon: 'alert-circle',
                color: 'text-red-600',
                bgColor: 'bg-red-100',
                gradientClass: 'bg-gradient-to-r from-red-500 to-red-600',
            };
        default:
            return {
                label: 'Estado desconocido',
                description: '',
                icon: 'help-circle',
                color: 'text-gray-600',
                bgColor: 'bg-gray-100',
                gradientClass: 'bg-gradient-to-r from-gray-500 to-gray-600',
            };
    }
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getVisitStatusClass = (status: string) => {
    switch (status) {
        case 'Autorizada':
            return 'text-green-700 bg-green-50 ring-green-600/20';
        case 'Pendiente':
            return 'text-yellow-700 bg-yellow-50 ring-yellow-600/20';
        case 'Completada':
            return 'text-blue-700 bg-blue-50 ring-blue-600/20';
        case 'Cancelada':
            return 'text-red-700 bg-red-50 ring-red-600/20';
        default:
            return 'text-gray-700 bg-gray-50 ring-gray-600/20';
    }
};

const getPackageStatusClass = (status: string) => {
    switch (status) {
        case 'Entregado':
            return 'text-green-700 bg-green-50 ring-green-600/20';
        case 'Pendiente':
            return 'text-orange-700 bg-orange-50 ring-orange-600/20';
        default:
            return 'text-gray-700 bg-gray-50 ring-gray-600/20';
    }
};
</script>