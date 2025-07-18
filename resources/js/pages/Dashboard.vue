<template>
  <AppLayout>
    <AppContent class="p-6 space-y-6" data-tour="dashboard">
      <!-- Header with Tour Button -->
      <div class="mb-8">
        <div class="flex justify-between items-start">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Dashboard Habitta</h1>
            <p class="text-muted-foreground">
              Resumen general del Sistema de Gestión para Propiedad Horizontal
            </p>
          </div>
          <div class="flex space-x-2">
            <button 
              @click="() => { hasSavedTour ? continueTour() : startTour() }"
              class="inline-flex flex-col items-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-colors"
              :class="hasSavedTour ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'"
            >
              <div class="flex items-center">
                <Icon name="play" class="w-4 h-4 mr-2" />
                {{ hasSavedTour ? 'Continuar Tour' : 'Tour Guiado' }}
              </div>
              <div v-if="hasSavedTour" class="text-xs opacity-90 mt-1">
                Paso {{ savedTourStep + 1 }} de {{ tourSteps.length }}
              </div>
            </button>
            <button 
              v-if="hasSavedTour"
              @click="restartTour"
              class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors"
            >
              <Icon name="refresh-cw" class="w-4 h-4 mr-2" />
              Reiniciar
            </button>
          </div>
        </div>
      </div>

      <!-- KPIs Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" data-tour="dashboard-metrics">
        <!-- Total Residents KPI -->
        <Card class="relative overflow-hidden">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Residentes</p>
                <p class="text-3xl font-bold">{{ (kpis.totalResidents || 0).toLocaleString() }}</p>
                <div class="flex items-center mt-2">
                  <Icon 
                    :name="(kpis.residentGrowth || 0) >= 0 ? 'trending-up' : 'trending-down'" 
                    :class="(kpis.residentGrowth || 0) >= 0 ? 'text-green-500' : 'text-red-500'"
                    class="h-4 w-4 mr-1"
                  />
                  <span 
                    :class="(kpis.residentGrowth || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                    class="text-sm font-medium"
                  >
                    {{ Math.abs(kpis.residentGrowth || 0) }}%
                  </span>
                  <span class="text-sm text-muted-foreground ml-1">vs mes anterior</span>
                </div>
              </div>
              <div class="p-3 bg-blue-100 rounded-full">
                <Icon name="users" class="h-8 w-8 text-blue-600" />
              </div>
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
        </Card>

        <!-- Total Apartments KPI -->
        <Card class="relative overflow-hidden">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Apartamentos</p>
                <p class="text-3xl font-bold">{{ kpis.totalApartments || 0 }}</p>
                <p class="text-sm text-muted-foreground mt-2">Unidades habitacionales</p>
              </div>
              <div class="p-3 bg-green-100 rounded-full">
                <Icon name="home" class="h-8 w-8 text-green-600" />
              </div>
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-500 to-green-600"></div>
        </Card>

        <!-- Pending Payments KPI -->
        <Card class="relative overflow-hidden">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Pagos Pendientes</p>
                <p class="text-3xl font-bold">{{ kpis.pendingPayments || 0 }}</p>
                <p class="text-sm text-muted-foreground mt-2">Cuotas de administración</p>
              </div>
              <div class="p-3 bg-purple-100 rounded-full">
                <Icon name="dollar-sign" class="h-8 w-8 text-purple-600" />
              </div>
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-purple-600"></div>
        </Card>

        <!-- Monthly Visitors KPI -->
        <Card class="relative overflow-hidden">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Visitas del Mes</p>
                <p class="text-3xl font-bold">{{ (kpis.monthlyVisitors || 0).toLocaleString() }}</p>
                <div class="flex items-center mt-2">
                  <Icon 
                    :name="(kpis.visitorGrowth || 0) >= 0 ? 'trending-up' : 'trending-down'" 
                    :class="(kpis.visitorGrowth || 0) >= 0 ? 'text-green-500' : 'text-red-500'"
                    class="h-4 w-4 mr-1"
                  />
                  <span 
                    :class="(kpis.visitorGrowth || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                    class="text-sm font-medium"
                  >
                    {{ Math.abs(kpis.visitorGrowth || 0) }}%
                  </span>
                  <span class="text-sm text-muted-foreground ml-1">vs mes anterior</span>
                </div>
              </div>
              <div class="p-3 bg-orange-100 rounded-full">
                <Icon name="user-check" class="h-8 w-8 text-orange-600" />
              </div>
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-500 to-orange-600"></div>
        </Card>
      </div>

      <!-- Charts Row 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Residents by Tower Chart -->
        <Card class="p-6">
          <div class="mb-4">
            <h3 class="text-lg font-semibold">Residentes por Torre</h3>
            <p class="text-sm text-muted-foreground">Distribución de residentes en diferentes torres</p>
          </div>
          <div class="h-80 flex items-center justify-center">
            <div v-if="charts.residentsByTower && charts.residentsByTower.length > 0" class="w-full h-full">
              <canvas ref="towerChart" class="max-w-full max-h-full"></canvas>
            </div>
            <div v-else class="text-center text-muted-foreground">
              <Icon name="pie-chart" class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>No hay datos disponibles</p>
            </div>
          </div>
        </Card>

        <!-- Payment Status Chart -->
        <Card class="p-6">
          <div class="mb-4">
            <h3 class="text-lg font-semibold">Estado de Pagos</h3>
            <p class="text-sm text-muted-foreground">Distribución de pagos por estado</p>
          </div>
          <div class="h-80 flex items-center justify-center">
            <div v-if="charts.paymentsByStatus && charts.paymentsByStatus.length > 0" class="w-full h-full">
              <canvas ref="statusChart" class="max-w-full max-h-full"></canvas>
            </div>
            <div v-else class="text-center text-muted-foreground">
              <Icon name="doughnut-chart" class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>No hay datos disponibles</p>
            </div>
          </div>
        </Card>
      </div>

      <!-- Charts Row 2 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Occupancy Status -->
        <Card class="p-6">
          <div class="mb-4">
            <h3 class="text-lg font-semibold">Estado de Ocupación</h3>
            <p class="text-sm text-muted-foreground">Distribución por estado de apartamentos</p>
          </div>
          <div class="space-y-4">
            <div 
              v-for="status in (charts.occupancyStatus || [])" 
              :key="status.status"
              class="flex items-center justify-between"
            >
              <div class="flex items-center space-x-3">
                <div 
                  class="w-4 h-4 rounded"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <span class="text-sm font-medium">{{ status.status }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-muted-foreground">{{ status.count }}</span>
                <div class="w-20 bg-muted rounded-full h-2">
                  <div 
                    class="h-2 rounded-full transition-all"
                    :style="{ 
                      backgroundColor: status.color,
                      width: `${getTotalApartments() > 0 ? (status.count / getTotalApartments()) * 100 : 0}%`
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
          <div class="space-y-3 max-h-64 overflow-y-auto">
            <div 
              v-for="expense in (charts.monthlyExpenses || []).slice(0, 8)" 
              :key="expense.category"
              class="space-y-2"
            >
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium truncate">{{ expense.category }}</span>
                <span class="text-xs text-muted-foreground">${{ (expense.amount || 0).toLocaleString() }}</span>
              </div>
              <div class="w-full bg-muted rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all"
                  :style="{ 
                    backgroundColor: expense.color,
                    width: `${expense.percentage}%`
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
          <div class="space-y-4 max-h-64 overflow-y-auto">
            <div 
              v-for="activity in (recentActivity || [])" 
              :key="activity.message"
              class="flex items-start space-x-3"
            >
              <div class="p-2 bg-muted rounded-full flex-shrink-0">
                <Icon :name="activity.icon" class="h-4 w-4" />
              </div>
              <div class="flex-1 min-w-0">
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
        <div class="h-80 flex items-center justify-center">
          <div v-if="charts.paymentTrend && charts.paymentTrend.length > 0" class="w-full h-full">
            <canvas ref="trendChart" class="max-w-full max-h-full"></canvas>
          </div>
          <div v-else class="text-center text-muted-foreground">
            <Icon name="trending-up" class="h-12 w-12 mx-auto mb-2 opacity-50" />
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <Card 
            v-for="notification in (pendingNotifications || [])" 
            :key="notification.id"
            class="p-4 border-l-4 border-l-yellow-500"
          >
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
    </AppContent>

    <!-- Virtual Tour Component -->
    <VirtualTour 
      ref="virtualTourRef"
      :steps="tourSteps" 
      @complete="onTourComplete"
      @close="onTourClose"
    />
  </AppLayout>
</template>

<script setup>
import { onMounted, ref, nextTick } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AppContent from '@/components/AppContent.vue'
import { Card } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'
import VirtualTour from '@/components/VirtualTour.vue'
import { useFlow1Tour } from '@/composables/useFlow1Tour'
import { useTourState } from '@/composables/useTourState'

const props = defineProps({
  kpis: {
    type: Object,
    default: () => ({})
  },
  charts: {
    type: Object,
    default: () => ({})
  },
  pendingNotifications: {
    type: Array,
    default: () => []
  },
  recentActivity: {
    type: Array,
    default: () => []
  }
})

const towerChart = ref(null)
const statusChart = ref(null)
const trendChart = ref(null)

// Virtual Tour functionality
const virtualTourRef = ref(null)
const { tourSteps } = useFlow1Tour()
const { hasSavedTour, savedTourStep, checkSavedTour } = useTourState()

const startTour = () => {
  if (virtualTourRef.value) {
    virtualTourRef.value.startTour()
    setTimeout(() => {
      checkSavedTour()
    }, 100)
  }
}

const continueTour = () => {
  if (virtualTourRef.value) {
    virtualTourRef.value.loadAndContinueTour()
  }
}

const restartTour = () => {
  // Limpiar estado guardado y empezar de nuevo
  localStorage.removeItem('sia-tour-state')
  if (virtualTourRef.value) {
    virtualTourRef.value.startTour()
    checkSavedTour()
  }
}

const onTourComplete = () => {
  // Esperar un momento para que se limpie el localStorage
  setTimeout(() => {
    checkSavedTour()
  }, 100)
}

const onTourClose = () => {
  // Esperar un momento para que se limpie el localStorage
  setTimeout(() => {
    checkSavedTour()
  }, 100)
}

const getTotalApartments = () => {
  return props.charts?.occupancyStatus?.reduce((sum, item) => sum + item.count, 0) || 0
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-CO', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const initCharts = async () => {
  await nextTick()
  
  try {
    const Chart = (await import('chart.js/auto')).default
    
    // Residents by Tower Chart
    if (towerChart.value && props.charts?.residentsByTower?.length > 0) {
      new Chart(towerChart.value, {
        type: 'pie',
        data: {
          labels: props.charts.residentsByTower.map(item => item.name),
          datasets: [{
            data: props.charts.residentsByTower.map(item => item.residents),
            backgroundColor: props.charts.residentsByTower.map(item => item.color),
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            }
          }
        }
      })
    }

    // Payment Status Chart
    if (statusChart.value && props.charts?.paymentsByStatus?.length > 0) {
      new Chart(statusChart.value, {
        type: 'doughnut',
        data: {
          labels: props.charts.paymentsByStatus.map(item => item.status),
          datasets: [{
            data: props.charts.paymentsByStatus.map(item => item.count),
            backgroundColor: props.charts.paymentsByStatus.map(item => item.color),
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
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
                usePointStyle: true
              }
            }
          }
        }
      })
    }

    // Payment Trend Chart
    if (trendChart.value && props.charts?.paymentTrend?.length > 0) {
      new Chart(trendChart.value, {
        type: 'line',
        data: {
          labels: props.charts.paymentTrend.map(item => item.label),
          datasets: [{
            label: 'Recaudo',
            data: props.charts.paymentTrend.map(item => item.amount),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      })
    }
  } catch (error) {
    console.error('Error loading Chart.js:', error)
  }
}

onMounted(() => {
  initCharts()
  checkSavedTour()
})
</script>
