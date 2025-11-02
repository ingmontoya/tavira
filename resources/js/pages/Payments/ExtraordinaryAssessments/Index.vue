<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { PlusCircle, TrendingUp, DollarSign, Calendar, Users } from 'lucide-vue-next'

interface ExtraordinaryAssessment {
  id: number
  name: string
  description: string
  total_amount: number
  total_collected: number
  total_pending: number
  number_of_installments: number
  installments_generated: number
  start_date: string
  end_date: string | null
  status: 'draft' | 'active' | 'completed' | 'cancelled'
  status_label: string
  distribution_type: string
  distribution_label: string
  progress_percentage: number
  apartments_count: number
  created_at: string
}

const props = defineProps<{
  assessments: ExtraordinaryAssessment[]
}>()

const statusFilter = ref<string>('all')

const filteredAssessments = computed(() => {
  if (statusFilter.value === 'all') {
    return props.assessments
  }
  return props.assessments.filter(a => a.status === statusFilter.value)
})

const getStatusColor = (status: string) => {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    active: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-CO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Cuotas Extraordinarias" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Cuotas Extraordinarias</h1>
            <p class="mt-2 text-sm text-gray-600">
              Gestiona proyectos especiales y su recaudación en cuotas mensuales
            </p>
          </div>
          <Link :href="route('extraordinary-assessments.create')">
            <Button>
              <PlusCircle class="mr-2 h-4 w-4" />
              Nueva Cuota Extraordinaria
            </Button>
          </Link>
        </div>

        <!-- Stats Cards -->
        <div class="mb-8 grid gap-4 md:grid-cols-4">
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Total Activas</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <TrendingUp class="mr-2 h-5 w-5 text-blue-500" />
                <span class="text-2xl font-bold">
                  {{ assessments.filter(a => a.status === 'active').length }}
                </span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Total Recaudado</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <DollarSign class="mr-2 h-5 w-5 text-green-500" />
                <span class="text-2xl font-bold">
                  {{ formatCurrency(assessments.filter(a => a.status === 'active').reduce((sum, a) => sum + a.total_collected, 0)) }}
                </span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Pendiente</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <DollarSign class="mr-2 h-5 w-5 text-orange-500" />
                <span class="text-2xl font-bold">
                  {{ formatCurrency(assessments.filter(a => a.status === 'active').reduce((sum, a) => sum + a.total_pending, 0)) }}
                </span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Completadas</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <Calendar class="mr-2 h-5 w-5 text-purple-500" />
                <span class="text-2xl font-bold">
                  {{ assessments.filter(a => a.status === 'completed').length }}
                </span>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Filters -->
        <div class="mb-6">
          <Select v-model="statusFilter">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="Filtrar por estado" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Todos los estados</SelectItem>
              <SelectItem value="draft">Borrador</SelectItem>
              <SelectItem value="active">Activas</SelectItem>
              <SelectItem value="completed">Completadas</SelectItem>
              <SelectItem value="cancelled">Canceladas</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Assessments List -->
        <div v-if="filteredAssessments.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="assessment in filteredAssessments"
            :key="assessment.id"
            class="hover:shadow-lg transition-shadow cursor-pointer"
            @click="router.visit(route('extraordinary-assessments.show', assessment.id))"
          >
            <CardHeader>
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <CardTitle class="text-lg">{{ assessment.name }}</CardTitle>
                  <CardDescription class="mt-1 line-clamp-2">
                    {{ assessment.description }}
                  </CardDescription>
                </div>
                <Badge :class="getStatusColor(assessment.status)">
                  {{ assessment.status_label }}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <!-- Progress -->
                <div>
                  <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-medium">Progreso</span>
                    <span class="text-gray-600">{{ assessment.progress_percentage }}%</span>
                  </div>
                  <Progress :value="assessment.progress_percentage" />
                </div>

                <!-- Amounts -->
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Total</span>
                    <span class="font-medium">{{ formatCurrency(assessment.total_amount) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Recaudado</span>
                    <span class="font-medium text-green-600">{{ formatCurrency(assessment.total_collected) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Pendiente</span>
                    <span class="font-medium text-orange-600">{{ formatCurrency(assessment.total_pending) }}</span>
                  </div>
                </div>

                <!-- Details -->
                <div class="border-t pt-4 space-y-2 text-sm">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center text-gray-600">
                      <Calendar class="mr-2 h-4 w-4" />
                      Cuotas
                    </div>
                    <span class="font-medium">
                      {{ assessment.installments_generated }}/{{ assessment.number_of_installments }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center text-gray-600">
                      <Users class="mr-2 h-4 w-4" />
                      Apartamentos
                    </div>
                    <span class="font-medium">{{ assessment.apartments_count }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Distribución</span>
                    <span class="font-medium">{{ assessment.distribution_label }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Inicio</span>
                    <span class="font-medium">{{ formatDate(assessment.start_date) }}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Empty State -->
        <Card v-else class="py-12">
          <CardContent class="text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
              <DollarSign class="h-8 w-8 text-gray-400" />
            </div>
            <h3 class="mb-2 text-lg font-medium text-gray-900">No hay cuotas extraordinarias</h3>
            <p class="mb-6 text-gray-600">
              Comienza creando tu primera cuota extraordinaria para proyectos especiales
            </p>
            <Link :href="route('extraordinary-assessments.create')">
              <Button>
                <PlusCircle class="mr-2 h-4 w-4" />
                Crear Cuota Extraordinaria
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
