<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  ArrowLeft,
  Edit,
  Play,
  XCircle,
  Calendar,
  DollarSign,
  Users,
  TrendingUp,
  Building,
  CheckCircle,
  Clock,
  AlertCircle
} from 'lucide-vue-next'

interface Assessment {
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
  notes: string | null
  created_at: string
}

interface ApartmentDetail {
  id: number
  apartment_id: number
  apartment_number: string
  apartment_tower: string
  total_amount: number
  installment_amount: number
  installments_paid: number
  amount_paid: number
  amount_pending: number
  status: string
  status_label: string
  progress_percentage: number
  first_payment_date: string | null
  last_payment_date: string | null
}

const props = defineProps<{
  assessment: Assessment
  apartments: ApartmentDetail[]
}>()

const showActivateDialog = ref(false)
const showCancelDialog = ref(false)

const activateForm = useForm({})
const cancelForm = useForm({})

const getStatusColor = (status: string) => {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    active: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    overdue: 'bg-red-100 text-red-800'
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

const formatDate = (date: string | null) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-CO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const activate = () => {
  activateForm.post(route('extraordinary-assessments.activate', props.assessment.id), {
    onSuccess: () => {
      showActivateDialog.value = false
    }
  })
}

const cancel = () => {
  cancelForm.post(route('extraordinary-assessments.cancel', props.assessment.id), {
    onSuccess: () => {
      showCancelDialog.value = false
    }
  })
}

const getApartmentStatusIcon = (status: string) => {
  switch (status) {
    case 'completed':
      return CheckCircle
    case 'in_progress':
      return Clock
    case 'overdue':
      return AlertCircle
    default:
      return Clock
  }
}
</script>

<template>
  <AppLayout>
    <Head :title="assessment.name" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <Link :href="route('extraordinary-assessments.index')">
            <Button variant="ghost" size="sm" class="mb-4">
              <ArrowLeft class="mr-2 h-4 w-4" />
              Volver a la lista
            </Button>
          </Link>
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900">{{ assessment.name }}</h1>
                <Badge :class="getStatusColor(assessment.status)">
                  {{ assessment.status_label }}
                </Badge>
              </div>
              <p class="mt-2 text-gray-600">{{ assessment.description }}</p>
            </div>
            <div class="flex gap-2">
              <Link
                v-if="assessment.status === 'draft'"
                :href="route('extraordinary-assessments.edit', assessment.id)"
              >
                <Button variant="outline">
                  <Edit class="mr-2 h-4 w-4" />
                  Editar
                </Button>
              </Link>
              <Button
                v-if="assessment.status === 'draft'"
                @click="showActivateDialog = true"
              >
                <Play class="mr-2 h-4 w-4" />
                Activar
              </Button>
              <Button
                v-if="assessment.status === 'active'"
                variant="destructive"
                @click="showCancelDialog = true"
              >
                <XCircle class="mr-2 h-4 w-4" />
                Cancelar
              </Button>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          <!-- Total Amount -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Monto Total</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <DollarSign class="mr-2 h-5 w-5 text-blue-500" />
                <span class="text-2xl font-bold">
                  {{ formatCurrency(assessment.total_amount) }}
                </span>
              </div>
            </CardContent>
          </Card>

          <!-- Collected -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Recaudado</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <TrendingUp class="mr-2 h-5 w-5 text-green-500" />
                <span class="text-2xl font-bold text-green-600">
                  {{ formatCurrency(assessment.total_collected) }}
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                {{ assessment.progress_percentage }}% del total
              </p>
            </CardContent>
          </Card>

          <!-- Pending -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Pendiente</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <DollarSign class="mr-2 h-5 w-5 text-orange-500" />
                <span class="text-2xl font-bold text-orange-600">
                  {{ formatCurrency(assessment.total_pending) }}
                </span>
              </div>
            </CardContent>
          </Card>

          <!-- Installments -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm font-medium text-gray-600">Cuotas</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center">
                <Calendar class="mr-2 h-5 w-5 text-purple-500" />
                <span class="text-2xl font-bold">
                  {{ assessment.installments_generated }}/{{ assessment.number_of_installments }}
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                {{ assessment.number_of_installments - assessment.installments_generated }} cuotas restantes
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Progress Card -->
        <Card class="mb-8">
          <CardHeader>
            <CardTitle>Progreso de Recaudación</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div>
                <div class="mb-2 flex items-center justify-between">
                  <span class="text-sm font-medium">Progreso General</span>
                  <span class="text-sm text-gray-600">{{ assessment.progress_percentage }}%</span>
                </div>
                <Progress :value="assessment.progress_percentage" class="h-3" />
              </div>
              <div class="grid gap-4 md:grid-cols-3 text-sm">
                <div>
                  <span class="text-gray-600">Total:</span>
                  <span class="ml-2 font-semibold">{{ formatCurrency(assessment.total_amount) }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Recaudado:</span>
                  <span class="ml-2 font-semibold text-green-600">{{ formatCurrency(assessment.total_collected) }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Pendiente:</span>
                  <span class="ml-2 font-semibold text-orange-600">{{ formatCurrency(assessment.total_pending) }}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Details Grid -->
        <div class="mb-8 grid gap-6 lg:grid-cols-2">
          <!-- Project Details -->
          <Card>
            <CardHeader>
              <CardTitle>Detalles del Proyecto</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">Fecha de Inicio</span>
                <span class="font-medium">{{ formatDate(assessment.start_date) }}</span>
              </div>
              <div v-if="assessment.end_date" class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">Fecha de Finalización</span>
                <span class="font-medium">{{ formatDate(assessment.end_date) }}</span>
              </div>
              <div class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">Tipo de Distribución</span>
                <span class="font-medium">{{ assessment.distribution_label }}</span>
              </div>
              <div class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">Apartamentos Asignados</span>
                <span class="font-medium">{{ apartments.length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Creado</span>
                <span class="font-medium">{{ formatDate(assessment.created_at) }}</span>
              </div>
              <div v-if="assessment.notes" class="pt-3 border-t">
                <span class="text-sm text-gray-600">Notas:</span>
                <p class="mt-1 text-sm">{{ assessment.notes }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Payment Summary -->
          <Card>
            <CardHeader>
              <CardTitle>Resumen de Pagos</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">Apartamentos al Día</span>
                <span class="font-medium text-green-600">
                  {{ apartments.filter(a => a.status === 'completed').length }}
                </span>
              </div>
              <div class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">En Progreso</span>
                <span class="font-medium text-blue-600">
                  {{ apartments.filter(a => a.status === 'in_progress').length }}
                </span>
              </div>
              <div class="flex justify-between border-b pb-3">
                <span class="text-sm text-gray-600">Pendientes</span>
                <span class="font-medium text-yellow-600">
                  {{ apartments.filter(a => a.status === 'pending').length }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Con Mora</span>
                <span class="font-medium text-red-600">
                  {{ apartments.filter(a => a.status === 'overdue').length }}
                </span>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Apartments Table -->
        <Card>
          <CardHeader>
            <CardTitle>Progreso por Apartamento</CardTitle>
            <CardDescription>
              Detalle del estado de pago de cada apartamento
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Apartamento</TableHead>
                  <TableHead>Total Asignado</TableHead>
                  <TableHead>Pagado</TableHead>
                  <TableHead>Pendiente</TableHead>
                  <TableHead>Cuotas</TableHead>
                  <TableHead>Progreso</TableHead>
                  <TableHead>Estado</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="apt in apartments" :key="apt.id">
                  <TableCell class="font-medium">
                    <div class="flex items-center">
                      <Building class="mr-2 h-4 w-4 text-gray-400" />
                      Torre {{ apt.apartment_tower }} - {{ apt.apartment_number }}
                    </div>
                  </TableCell>
                  <TableCell>{{ formatCurrency(apt.total_amount) }}</TableCell>
                  <TableCell class="text-green-600 font-medium">
                    {{ formatCurrency(apt.amount_paid) }}
                  </TableCell>
                  <TableCell class="text-orange-600 font-medium">
                    {{ formatCurrency(apt.amount_pending) }}
                  </TableCell>
                  <TableCell>
                    {{ apt.installments_paid }}/{{ assessment.number_of_installments }}
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <Progress :value="apt.progress_percentage" class="w-20" />
                      <span class="text-sm text-gray-600 w-10">{{ apt.progress_percentage }}%</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :class="getStatusColor(apt.status)" class="flex items-center gap-1 w-fit">
                      <component :is="getApartmentStatusIcon(apt.status)" class="h-3 w-3" />
                      {{ apt.status_label }}
                    </Badge>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <!-- Draft State Alert -->
        <Alert v-if="assessment.status === 'draft'" class="mt-8">
          <AlertCircle class="h-4 w-4" />
          <AlertDescription>
            Esta cuota extraordinaria está en estado <strong>Borrador</strong>.
            Debes <strong>activarla</strong> para que comience a generar cobros automáticos en las facturas mensuales.
            Una vez activada, se asignará a todos los apartamentos y no podrá ser editada.
          </AlertDescription>
        </Alert>
      </div>
    </div>

    <!-- Activate Dialog -->
    <AlertDialog v-model:open="showActivateDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Activar Cuota Extraordinaria?</AlertDialogTitle>
          <AlertDialogDescription>
            Al activar esta cuota extraordinaria:
            <ul class="mt-2 list-disc list-inside space-y-1">
              <li>Se asignará automáticamente a todos los apartamentos elegibles</li>
              <li>Se calculará el monto por apartamento según el tipo de distribución</li>
              <li>Comenzará a generar ítems en las facturas mensuales</li>
              <li>No podrás editar ni eliminar esta cuota extraordinaria</li>
            </ul>
            <p class="mt-3 font-semibold">Esta acción no se puede deshacer.</p>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancelar</AlertDialogCancel>
          <AlertDialogAction @click="activate" :disabled="activateForm.processing">
            {{ activateForm.processing ? 'Activando...' : 'Sí, Activar' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Cancel Dialog -->
    <AlertDialog v-model:open="showCancelDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Cancelar Cuota Extraordinaria?</AlertDialogTitle>
          <AlertDialogDescription>
            Al cancelar esta cuota extraordinaria:
            <ul class="mt-2 list-disc list-inside space-y-1">
              <li>Se detendrá la generación de nuevas cuotas mensuales</li>
              <li>Los cobros ya aplicados permanecerán en las facturas</li>
              <li>El progreso de recaudación se mantendrá</li>
              <li>Podrás reactivarla más adelante si es necesario</li>
            </ul>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>No Cancelar</AlertDialogCancel>
          <AlertDialogAction @click="cancel" :disabled="cancelForm.processing" class="bg-red-600 hover:bg-red-700">
            {{ cancelForm.processing ? 'Cancelando...' : 'Sí, Cancelar Cuota' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
