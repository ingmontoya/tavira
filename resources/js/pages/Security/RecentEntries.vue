<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, FileText, Shield, User } from 'lucide-vue-next';

interface Visit {
    id: number;
    visitor_name: string;
    visitor_document_number: string;
    visitor_phone: string | null;
    visit_reason: string | null;
    entry_time: string;
    security_notes: string | null;
    apartment: {
        id: number;
        number: string;
        tower: string;
    };
    creator: {
        id: number;
        name: string;
    };
    authorizer: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    entries: Visit[];
}>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatTime = (date: string) => {
    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const isToday = (date: string) => {
    const today = new Date();
    const entryDate = new Date(date);
    return entryDate.toDateString() === today.toDateString();
};

const groupedEntries = props.entries.reduce((groups: Record<string, Visit[]>, entry) => {
    const date = new Date(entry.entry_time).toDateString();
    if (!groups[date]) {
        groups[date] = [];
    }
    groups[date].push(entry);
    return groups;
}, {});
</script>

<template>
    <Head title="Entradas Recientes" />

    <AppLayout>
        <div class="container mx-auto max-w-6xl px-6 py-8">
            <div class="mb-8">
                <div class="mb-4 flex items-center gap-4">
                    <Link :href="route('security.visits.scanner')" class="text-gray-500 hover:text-gray-700">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Entradas Recientes</h1>
                        <p class="text-gray-600">Registro de visitas autorizadas por seguridad</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Summary Stats -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center">
                                <div class="rounded-lg bg-blue-100 p-2">
                                    <Clock class="h-5 w-5 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <div class="text-2xl font-bold">{{ props.entries.length }}</div>
                                    <div class="text-sm text-gray-600">Entradas registradas</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center">
                                <div class="rounded-lg bg-green-100 p-2">
                                    <User class="h-5 w-5 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <div class="text-2xl font-bold">
                                        {{ props.entries.filter((entry) => isToday(entry.entry_time)).length }}
                                    </div>
                                    <div class="text-sm text-gray-600">Entradas hoy</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center">
                                <div class="rounded-lg bg-purple-100 p-2">
                                    <Shield class="h-5 w-5 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <div class="text-2xl font-bold">
                                        {{ new Set(props.entries.map((e) => e.authorizer.id)).size }}
                                    </div>
                                    <div class="text-sm text-gray-600">Guardias activos</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Entries by Date -->
                <div v-if="Object.keys(groupedEntries).length === 0" class="py-12 text-center">
                    <div class="text-gray-500">
                        <Clock class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p class="text-lg font-medium">No hay entradas registradas</p>
                        <p class="text-sm">Las entradas autorizadas aparecerán aquí</p>
                    </div>
                </div>

                <div v-else class="space-y-6">
                    <div v-for="(entries, date) in groupedEntries" :key="date">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Calendar class="h-5 w-5" />
                                    {{
                                        new Date(date).toLocaleDateString('es-ES', {
                                            weekday: 'long',
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })
                                    }}
                                    <Badge variant="secondary" class="ml-2">
                                        {{ entries.length }} {{ entries.length === 1 ? 'entrada' : 'entradas' }}
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Hora</TableHead>
                                                <TableHead>Visitante</TableHead>
                                                <TableHead>Apartamento</TableHead>
                                                <TableHead>Motivo</TableHead>
                                                <TableHead>Autorizado por</TableHead>
                                                <TableHead>Notas</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50">
                                                <TableCell>
                                                    <div class="font-mono text-sm font-medium">
                                                        {{ formatTime(entry.entry_time) }}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div class="font-medium">{{ entry.visitor_name }}</div>
                                                        <div class="text-sm text-gray-500">{{ entry.visitor_document_number }}</div>
                                                        <div v-if="entry.visitor_phone" class="text-xs text-gray-400">
                                                            {{ entry.visitor_phone }}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div class="font-medium">{{ entry.apartment.tower }}-{{ entry.apartment.number }}</div>
                                                        <div class="text-sm text-gray-500">Anfitrión: {{ entry.creator.name }}</div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div v-if="entry.visit_reason" class="max-w-xs text-sm">
                                                        {{ entry.visit_reason }}
                                                    </div>
                                                    <div v-else class="text-sm text-gray-400 italic">Sin especificar</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="flex items-center gap-2">
                                                        <Shield class="h-4 w-4 text-blue-500" />
                                                        <span class="text-sm font-medium">{{ entry.authorizer.name }}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div v-if="entry.security_notes" class="max-w-xs">
                                                        <div class="mb-1 flex items-center gap-1">
                                                            <FileText class="h-3 w-3 text-gray-400" />
                                                            <span class="text-xs text-gray-500">Notas:</span>
                                                        </div>
                                                        <div class="text-sm text-gray-700">{{ entry.security_notes }}</div>
                                                    </div>
                                                    <div v-else class="text-sm text-gray-400 italic">Sin notas</div>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-center">
                    <Link :href="route('security.visits.scanner')">
                        <Button class="bg-primary">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver al Escáner
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
