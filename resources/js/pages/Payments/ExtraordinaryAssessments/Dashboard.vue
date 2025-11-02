<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
  PlusCircle,
  TrendingUp,
  DollarSign,
  Calendar,
  Activity,
  CheckCircle2,
  AlertCircle,
  BarChart3
} from 'lucide-vue-next'

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

interface DashboardStats {
  total_assessments: number
  active_assessments: number
  completed_assessments: number
  total_amount: number
  total_collected: number
  total_pending: number
  overall_progress_percentage: number
}

const props = defineProps<{
  stats: DashboardStats
  activeAssessments: ExtraordinaryAssessment[]
  recentAssessments: ExtraordinaryAssessment[]
}>()

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
    month: 'short',
    day: 'numeric'
  })
}

const getStatusColor = (status: string) => {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    active: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const collectionEfficiency = computed(() => {
  if (props.stats.total_amount === 0) return 0
  return (props.stats.total_collected / props.stats.total_amount) * 100
})
</script>

<template>
  <AppLayout>
    <Head title="Dashboard - Cuotas Extraordinarias" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard de Cuotas Extraordinarias</h1>
            <p class="mt-2 text-sm text-gray-600">
              Resumen ejecutivo y estadísticas de recaudación
            </p>
          </div>
          <div class="flex gap-3">
            <Link :href="route('extraordinary-assessments.index')">
              <Button variant="outline">
                Ver Todas
              </Button>
            </Link>
            <Link :href="route('extraordinary-assessments.create')">
              <Button>
                <PlusCircle class="mr-2 h-4 w-4" />
                Nueva Cuota
              </Button>
            </Link>
          </div>
        </div>

        <!-- Main Stats Grid -->
        <div class="mb-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <!-- Total Assessments -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Total Proyectos</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-3xl font-bold">{{ stats.total_assessments }}</div>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ stats.active_assessments }} activos
                  </p>
                </div>
                <Activity class="h-10 w-10 text-blue-500 opacity-50" />
              </div>
            </CardContent>
          </Card>

          <!-- Total Amount -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Monto Total</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-2xl font-bold">{{ formatCurrency(stats.total_amount) }}</div>
                  <p class="text-xs text-gray-500 mt-1">
                    En proyectos activos
                  </p>
                </div>
                <BarChart3 class="h-10 w-10 text-purple-500 opacity-50" />
              </div>
            </CardContent>
          </Card>

          <!-- Total Collected -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Recaudado</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-2xl font-bold text-green-600">
                    {{ formatCurrency(stats.total_collected) }}
                  </div>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ collectionEfficiency.toFixed(1) }}% del total
                  </p>
                </div>
                <DollarSign class="h-10 w-10 text-green-500 opacity-50" />
              </div>
            </CardContent>
          </Card>

          <!-- Total Pending -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Pendiente</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-2xl font-bold text-orange-600">
                    {{ formatCurrency(stats.total_pending) }}
                  </div>
                  <p class="text-xs text-gray-500 mt-1">
                    Por recaudar
                  </p>
                </div>
                <AlertCircle class="h-10 w-10 text-orange-500 opacity-50" />
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Overall Progress Card -->
        <Card class="mb-8">
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Progreso General de Recaudación</CardTitle>
                <CardDescription>
                  Estado global de todas las cuotas extraordinarias activas
                </CardDescription>
              </div>
              <Badge class="bg-blue-100 text-blue-800 text-lg px-4 py-2">
                {{ stats.overall_progress_percentage }}%
              </Badge>
            </div>
          </CardHeader>
          <CardContent>
            <Progress :value="stats.overall_progress_percentage" class="h-4" />
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
              <div class="border-r">
                <div class="text-2xl font-bold">{{ stats.total_assessments }}</div>
                <div class="text-xs text-gray-500">Proyectos Totales</div>
              </div>
              <div class="border-r">
                <div class="text-2xl font-bold text-blue-600">{{ stats.active_assessments }}</div>
                <div class="text-xs text-gray-500">En Curso</div>
              </div>
              <div>
                <div class="text-2xl font-bold text-green-600">{{ stats.completed_assessments }}</div>
                <div class="text-xs text-gray-500">Completados</div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Two Column Layout -->
        <div class="grid gap-8 lg:grid-cols-2">
          <!-- Active Assessments -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Proyectos Activos</CardTitle>
                  <CardDescription>
                    Cuotas extraordinarias en recaudación
                  </CardDescription>
                </div>
                <TrendingUp class="h-5 w-5 text-blue-500" />
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="activeAssessments.length > 0" class="space-y-4">
                <div
                  v-for="assessment in activeAssessments"
                  :key="assessment.id"
                  class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                  @click="$inertia.visit(route('extraordinary-assessments.show', assessment.id))"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ assessment.name }}</h4>
                      <p class="text-sm text-gray-600 line-clamp-1 mt-1">
                        {{ assessment.description }}
                      </p>
                    </div>
                    <Badge :class="getStatusColor(assessment.status)" class="ml-2">
                      {{ assessment.status_label }}
                    </Badge>
                  </div>

                  <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-600">Progreso</span>
                      <span class="font-medium">{{ assessment.progress_percentage }}%</span>
                    </div>
                    <Progress :value="assessment.progress_percentage" class="h-2" />

                    <div class="grid grid-cols-2 gap-2 text-xs mt-3">
                      <div>
                        <span class="text-gray-500">Recaudado:</span>
                        <span class="font-medium ml-1 text-green-600">
                          {{ formatCurrency(assessment.total_collected) }}
                        </span>
                      </div>
                      <div>
                        <span class="text-gray-500">Pendiente:</span>
                        <span class="font-medium ml-1 text-orange-600">
                          {{ formatCurrency(assessment.total_pending) }}
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500 mt-2 pt-2 border-t">
                      <span>
                        Cuotas: {{ assessment.installments_generated }}/{{ assessment.number_of_installments }}
                      </span>
                      <span>
                        {{ assessment.apartments_count }} apartamentos
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="py-8 text-center">
                <AlertCircle class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600">No hay proyectos activos</p>
              </div>
            </CardContent>
          </Card>

          <!-- Recent Assessments -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Actividad Reciente</CardTitle>
                  <CardDescription>
                    Últimos proyectos creados o actualizados
                  </CardDescription>
                </div>
                <Calendar class="h-5 w-5 text-purple-500" />
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="recentAssessments.length > 0" class="space-y-4">
                <div
                  v-for="assessment in recentAssessments"
                  :key="assessment.id"
                  class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                  @click="$inertia.visit(route('extraordinary-assessments.show', assessment.id))"
                >
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ assessment.name }}</h4>
                      <p class="text-xs text-gray-500 mt-1">
                        Creado: {{ formatDate(assessment.created_at) }}
                      </p>
                    </div>
                    <Badge :class="getStatusColor(assessment.status)">
                      {{ assessment.status_label }}
                    </Badge>
                  </div>

                  <div class="grid grid-cols-2 gap-2 text-sm mt-3">
                    <div>
                      <span class="text-gray-600">Monto Total:</span>
                      <div class="font-semibold">{{ formatCurrency(assessment.total_amount) }}</div>
                    </div>
                    <div>
                      <span class="text-gray-600">Distribución:</span>
                      <div class="font-semibold text-sm">{{ assessment.distribution_label }}</div>
                    </div>
                  </div>

                  <div v-if="assessment.status === 'active'" class="mt-3 pt-3 border-t">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-gray-600">Progreso de recaudación</span>
                      <span class="font-medium">{{ assessment.progress_percentage }}%</span>
                    </div>
                    <Progress :value="assessment.progress_percentage" class="h-1 mt-1" />
                  </div>

                  <div v-if="assessment.status === 'completed'" class="mt-3 pt-3 border-t flex items-center text-green-600 text-sm">
                    <CheckCircle2 class="h-4 w-4 mr-1" />
                    <span>Proyecto completado</span>
                  </div>
                </div>
              </div>

              <div v-else class="py-8 text-center">
                <Calendar class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600">No hay actividad reciente</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Quick Actions -->
        <Card class="mt-8">
          <CardHeader>
            <CardTitle>Acciones Rápidas</CardTitle>
            <CardDescription>
              Gestiona tus cuotas extraordinarias
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-4 md:grid-cols-3">
              <Link :href="route('extraordinary-assessments.create')">
                <Button variant="outline" class="w-full justify-start">
                  <PlusCircle class="mr-2 h-4 w-4" />
                  Crear Nueva Cuota
                </Button>
              </Link>

              <Link :href="route('extraordinary-assessments.index')">
                <Button variant="outline" class="w-full justify-start">
                  <BarChart3 class="mr-2 h-4 w-4" />
                  Ver Todos los Proyectos
                </Button>
              </Link>

              <Link :href="route('extraordinary-assessments.index', { status: 'active' })">
                <Button variant="outline" class="w-full justify-start">
                  <Activity class="mr-2 h-4 w-4" />
                  Ver Solo Activos
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
