<template>
    <AppLayout>
        <AppContent class="p-6">
            <div class="mb-6">
                <Heading>Reportes</Heading>
                <p class="text-muted-foreground">Centro de reportes y estadísticas del sistema</p>
            </div>

            <Card class="p-12 text-center">
                <Icon name="chart-bar" class="mx-auto mb-4 h-16 w-16 text-muted-foreground" />
                <h3 class="mb-2 text-xl font-semibold">Módulo en Desarrollo</h3>
                <p class="mb-6 text-muted-foreground">{{ message }}</p>
                <div class="mx-auto grid max-w-2xl grid-cols-2 gap-4 md:grid-cols-3">
                    <Card class="cursor-pointer p-4 transition-colors hover:bg-muted/50">
                        <Icon name="users" class="mx-auto mb-2 h-8 w-8" />
                        <p class="text-sm">Reportes de Estudiantes</p>
                    </Card>
                    <Card class="cursor-pointer p-4 transition-colors hover:bg-muted/50">
                        <Icon name="book-open" class="mx-auto mb-2 h-8 w-8" />
                        <p class="text-sm">Reportes Académicos</p>
                    </Card>
                    <Card class="cursor-pointer p-4 transition-colors hover:bg-muted/50">
                        <Icon name="bar-chart-3" class="mx-auto mb-2 h-8 w-8" />
                        <p class="text-sm">Estadísticas</p>
                    </Card>
                    <Card class="cursor-pointer p-4 transition-colors hover:bg-muted/50">
                        <Icon name="file-text" class="mx-auto mb-2 h-8 w-8" />
                        <p class="text-sm">Certificados</p>
                    </Card>
                    <Card class="cursor-pointer p-4 transition-colors hover:bg-muted/50">
                        <Icon name="credit-card" class="mx-auto mb-2 h-8 w-8" />
                        <p class="text-sm">Reportes Financieros</p>
                    </Card>
                    <Card class="cursor-pointer p-4 transition-colors hover:bg-muted/50">
                        <Icon name="calendar" class="mx-auto mb-2 h-8 w-8" />
                        <p class="text-sm">Reportes por Período</p>
                    </Card>
                </div>
            </Card>
        </AppContent>
    </AppLayout>
</template>

<script setup>
import AppContent from '@/components/AppContent.vue';
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps(['message']);
</script>
