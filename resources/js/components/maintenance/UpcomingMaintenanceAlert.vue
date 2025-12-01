<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { usePage, Link } from '@inertiajs/vue3';
import { AlertCircle, Calendar, Clock, MapPin, RefreshCw, Wrench, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface UpcomingMaintenance {
    id: number;
    title: string;
    category: string;
    next_occurrence_date: string;
    days_until: number;
    priority: string;
    location: string | null;
    recurrence_frequency: string | null;
    url: string;
}

const page = usePage();
const upcomingMaintenance = computed(() => (page.props.upcomingMaintenance as UpcomingMaintenance[]) || []);
const isDismissed = ref(false);

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'critical':
            return 'destructive';
        case 'high':
            return 'default'; // orange-ish
        case 'medium':
            return 'secondary';
        case 'low':
            return 'outline';
        default:
            return 'secondary';
    }
};

const getPriorityLabel = (priority: string) => {
    switch (priority) {
        case 'critical':
            return 'Crítica';
        case 'high':
            return 'Alta';
        case 'medium':
            return 'Media';
        case 'low':
            return 'Baja';
        default:
            return priority;
    }
};

const getUrgencyColor = (daysUntil: number) => {
    if (daysUntil <= 3) return 'text-red-600 bg-red-50 border-red-200';
    if (daysUntil <= 7) return 'text-orange-600 bg-orange-50 border-orange-200';
    return 'text-blue-600 bg-blue-50 border-blue-200';
};

const dismiss = () => {
    isDismissed.value = true;
};
</script>

<template>
    <div v-if="upcomingMaintenance.length > 0 && !isDismissed" class="mb-6">
        <Card class="border-blue-200 bg-blue-50 shadow-lg">
            <CardHeader class="rounded-t-lg bg-blue-600 py-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Wrench class="h-5 w-5" />
                            Mantenimientos Programados Próximos ({{ upcomingMaintenance.length }})
                        </CardTitle>
                        <CardDescription class="text-blue-100">
                            Revisa los mantenimientos recurrentes que se aproximan
                        </CardDescription>
                    </div>
                    <Button variant="ghost" size="sm" @click="dismiss" class="text-white hover:bg-blue-700">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div v-for="(maintenance, index) in upcomingMaintenance" :key="maintenance.id"
                     class="border-b border-blue-100 bg-white p-4 last:border-b-0"
                     :class="index < 3 ? '' : 'hidden lg:block'">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start space-x-4 flex-1">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2"
                                :class="getUrgencyColor(maintenance.days_until)"
                            >
                                <Calendar class="h-6 w-6" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h4 class="font-bold text-gray-900 truncate">
                                        {{ maintenance.title }}
                                    </h4>
                                    <Badge :variant="getPriorityColor(maintenance.priority)" class="shrink-0">
                                        {{ getPriorityLabel(maintenance.priority) }}
                                    </Badge>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-700 flex items-center gap-1">
                                        <span class="font-medium">{{ maintenance.category }}</span>
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
                                        <span class="flex items-center gap-1">
                                            <Clock class="h-3 w-3" />
                                            {{ maintenance.next_occurrence_date }}
                                        </span>
                                        <span
                                            class="flex items-center gap-1 font-semibold"
                                            :class="{
                                                'text-red-600': maintenance.days_until <= 3,
                                                'text-orange-600': maintenance.days_until > 3 && maintenance.days_until <= 7,
                                                'text-blue-600': maintenance.days_until > 7
                                            }"
                                        >
                                            <AlertCircle class="h-3 w-3" />
                                            {{ maintenance.days_until }} {{ maintenance.days_until === 1 ? 'día' : 'días' }} restantes
                                        </span>
                                        <span v-if="maintenance.recurrence_frequency" class="flex items-center gap-1">
                                            <RefreshCw class="h-3 w-3" />
                                            {{ maintenance.recurrence_frequency }}
                                        </span>
                                        <span v-if="maintenance.location" class="flex items-center gap-1">
                                            <MapPin class="h-3 w-3" />
                                            {{ maintenance.location }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Link :href="maintenance.url">
                            <Button size="sm" variant="outline" class="shrink-0">
                                Ver Detalles
                            </Button>
                        </Link>
                    </div>
                </div>

                <div v-if="upcomingMaintenance.length > 3" class="bg-gray-50 p-3 text-center lg:hidden">
                    <Link href="/maintenance-requests" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Ver todos los {{ upcomingMaintenance.length }} mantenimientos próximos →
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
