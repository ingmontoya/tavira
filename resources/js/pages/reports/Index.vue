<template>
  <AppLayout>
    <AppContent class="p-6">
      <div class="mb-6">
        <Heading>Reportes</Heading>
        <p class="text-muted-foreground">Centro de reportes y estadísticas del sistema</p>
      </div>

      <Card class="p-12 text-center">
        <Icon name="chart-bar" class="mx-auto h-16 w-16 text-muted-foreground mb-4" />
        <h3 class="text-xl font-semibold mb-2">Módulo en Desarrollo</h3>
        <p class="text-muted-foreground mb-6">{{ message }}</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 max-w-2xl mx-auto">
          <Card class="p-4 hover:bg-muted/50 cursor-pointer transition-colors">
            <Icon name="users" class="mx-auto h-8 w-8 mb-2" />
            <p class="text-sm">Reportes de Estudiantes</p>
          </Card>
          <Card class="p-4 hover:bg-muted/50 cursor-pointer transition-colors">
            <Icon name="book-open" class="mx-auto h-8 w-8 mb-2" />
            <p class="text-sm">Reportes Académicos</p>
          </Card>
          <Card class="p-4 hover:bg-muted/50 cursor-pointer transition-colors">
            <Icon name="bar-chart-3" class="mx-auto h-8 w-8 mb-2" />
            <p class="text-sm">Estadísticas</p>
          </Card>
          <Card class="p-4 hover:bg-muted/50 cursor-pointer transition-colors">
            <Icon name="file-text" class="mx-auto h-8 w-8 mb-2" />
            <p class="text-sm">Certificados</p>
          </Card>
          <Card class="p-4 hover:bg-muted/50 cursor-pointer transition-colors">
            <Icon name="credit-card" class="mx-auto h-8 w-8 mb-2" />
            <p class="text-sm">Reportes Financieros</p>
          </Card>
          <Card class="p-4 hover:bg-muted/50 cursor-pointer transition-colors">
            <Icon name="calendar" class="mx-auto h-8 w-8 mb-2" />
            <p class="text-sm">Reportes por Período</p>
          </Card>
        </div>
      </Card>
    </AppContent>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AppContent from '@/components/AppContent.vue'
import Heading from '@/components/Heading.vue'
import { Card } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'

const props = defineProps(['message'])
</script>