<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import UpgradePlanBanner from '@/components/Provider/UpgradePlanBanner.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, Clock, Eye, FileText, Package, Plus, TrendingUp } from 'lucide-vue-next';

interface Provider {
    id: number;
    name: string;
    email: string;
    phone: string;
    categories: Array<{ id: number; name: string }>;
    subscription_plan?: string;
    leads_used_this_month?: number;
    leads_remaining?: number;
    has_seen_pricing?: boolean;
}

interface Stats {
    total_services: number;
    active_services: number;
    total_proposals: number;
    pending_requests: number;
}

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    deadline: string | null;
    status: string;
    tenant_id: string | null;
    categories: Array<{ id: number; name: string }>;
}

interface Proposal {
    id: number;
    quoted_amount: number;
    proposal: string | null;
    estimated_days: number | null;
    status: string;
    created_at: string;
    quotation_request: {
        id: number;
        title: string;
    };
    tenant_id: string;
    tenant_name: string;
}

interface Props {
    provider: Provider;
    stats: Stats;
    recentRequests: QuotationRequest[];
    recentProposals: Proposal[];
}

defineProps<Props>();

const breadcrumbs = [{ title: 'Dashboard', href: '/provider/dashboard' }];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusBadge = (status: string) => {
    const badges = {
        open: { text: 'Abierta', variant: 'default' as const },
        pending: { text: 'Pendiente', variant: 'secondary' as const },
        accepted: { text: 'Aceptada', variant: 'default' as const },
        rejected: { text: 'Rechazada', variant: 'destructive' as const },
    };
    return badges[status as keyof typeof badges] || badges.pending;
};
</script>

<template>
    <AppLayout title="Dashboard de Proveedor" :breadcrumbs="breadcrumbs">
        <Head title="Dashboard - Proveedor" />

        <div class="container mx-auto space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Bienvenido, {{ provider.name }}</h1>
                    <p class="text-muted-foreground">Panel de gestión de servicios y cotizaciones</p>
                </div>
                <Link :href="route('provider.services.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Servicio
                    </Button>
                </Link>
            </div>

            <!-- Upgrade Banner -->
            <UpgradePlanBanner
                :current-plan="provider.subscription_plan"
                :leads-used="provider.leads_used_this_month"
                :leads-limit="provider.leads_remaining"
                :has-seen-pricing="provider.has_seen_pricing"
            />

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium"> Servicios Activos </CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.active_services }}</div>
                        <p class="text-xs text-muted-foreground">de {{ stats.total_services }} totales</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium"> Propuestas Enviadas </CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_proposals }}</div>
                        <p class="text-xs text-muted-foreground">Total histórico</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium"> Sin Responder </CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_requests }}</div>
                        <p class="text-xs text-muted-foreground">Requieren tu atención</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium"> Categorías </CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ provider.categories.length }}</div>
                        <p class="text-xs text-muted-foreground">Servicios ofrecidos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Quotation Requests -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Solicitudes Sin Responder</CardTitle>
                            <CardDescription> Últimas solicitudes disponibles a las que aún no has respondido </CardDescription>
                        </div>
                        <Link :href="route('provider.quotations.index')">
                            <Button variant="outline" size="sm"> Ver todas </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="recentRequests.length === 0" class="py-8 text-center text-muted-foreground">
                        No hay solicitudes disponibles en este momento
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="request in recentRequests" :key="request.id" class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">{{ request.title }}</p>
                                    <Badge :variant="getStatusBadge(request.status).variant">
                                        {{ getStatusBadge(request.status).text }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ request.description }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                    <span v-if="request.deadline" class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        Vence: {{ formatDate(request.deadline) }}
                                    </span>
                                    <span>
                                        {{ request.categories.map((c) => c.name).join(', ') }}
                                    </span>
                                </div>
                            </div>
                            <Link :href="route('provider.quotations.show', [request.tenant_id, request.id])">
                                <Button variant="outline" size="sm">
                                    <Eye class="mr-2 h-4 w-4" />
                                    Ver detalles
                                </Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Proposals -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Mis Propuestas Recientes</CardTitle>
                            <CardDescription> Últimas propuestas enviadas </CardDescription>
                        </div>
                        <Link :href="route('provider.quotations.proposals')">
                            <Button variant="outline" size="sm"> Ver historial </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="recentProposals.length === 0" class="py-8 text-center text-muted-foreground">No has enviado propuestas aún</div>
                    <div v-else class="space-y-4">
                        <div v-for="proposal in recentProposals" :key="proposal.id" class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">{{ proposal.quotation_request.title }}</p>
                                    <Badge :variant="getStatusBadge(proposal.status).variant">
                                        {{ getStatusBadge(proposal.status).text }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ proposal.tenant_name }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                    <span class="font-medium text-foreground">
                                        {{ formatCurrency(proposal.quoted_amount) }}
                                    </span>
                                    <span>
                                        {{ formatDate(proposal.created_at) }}
                                    </span>
                                    <span v-if="proposal.estimated_days"> {{ proposal.estimated_days }} días </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
