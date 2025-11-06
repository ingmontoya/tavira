<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, Info, Calculator, AlertTriangle } from 'lucide-vue-next'

interface ExtraordinaryAssessment {
  id: number
  name: string
  description: string
  total_amount: number
  number_of_installments: number
  start_date: string
  distribution_type: 'equal' | 'by_coefficient'
  notes: string | null
  status: 'draft' | 'active' | 'completed' | 'cancelled'
  status_label: string
}

const props = defineProps<{
  assessment: ExtraordinaryAssessment
  totalApartments: number
}>()

const form = useForm({
  name: props.assessment.name,
  description: props.assessment.description,
  total_amount: props.assessment.total_amount.toString(),
  number_of_installments: props.assessment.number_of_installments,
  start_date: props.assessment.start_date,
  distribution_type: props.assessment.distribution_type,
  notes: props.assessment.notes || ''
})

const canEdit = computed(() => props.assessment.status === 'draft')

const amountPerApartment = computed(() => {
  const total = parseFloat(form.total_amount) || 0
  if (total === 0 || props.totalApartments === 0) return 0

  if (form.distribution_type === 'equal') {
    return total / props.totalApartments
  }
  // Para coeficiente, mostramos un promedio aproximado
  return total / props.totalApartments
})

const installmentPerApartment = computed(() => {
  if (form.number_of_installments === 0) return 0
  return amountPerApartment.value / form.number_of_installments
})

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const submit = () => {
  if (!canEdit.value) return
  form.put(route('extraordinary-assessments.update', props.assessment.id))
}
</script>

<template>
  <AppLayout>
    <Head :title="`Editar: ${assessment.name}`" />

    <div class="py-8">
      <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <Link :href="route('extraordinary-assessments.show', assessment.id)">
            <Button variant="ghost" size="sm" class="mb-4">
              <ArrowLeft class="mr-2 h-4 w-4" />
              Volver a detalles
            </Button>
          </Link>
          <h1 class="text-3xl font-bold text-gray-900">Editar Cuota Extraordinaria</h1>
          <p class="mt-2 text-sm text-gray-600">
            Modifica los detalles de la cuota extraordinaria
          </p>
        </div>

        <!-- Warning if not draft -->
        <Alert v-if="!canEdit" class="mb-6 border-red-200 bg-red-50">
          <AlertTriangle class="h-4 w-4 text-red-600" />
          <AlertDescription class="text-red-800">
            <strong>No se puede editar.</strong> Esta cuota extraordinaria tiene estado
            <strong>{{ assessment.status_label }}</strong>. Solo se pueden editar cuotas en estado Borrador.
          </AlertDescription>
        </Alert>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Basic Information Card -->
          <Card>
            <CardHeader>
              <CardTitle>Información del Proyecto</CardTitle>
              <CardDescription>
                Detalles principales de la cuota extraordinaria
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Name -->
              <div>
                <Label for="name">Nombre del Proyecto *</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="Ej: Pintura de Fachada 2025"
                  :disabled="!canEdit"
                  :class="{ 'border-red-500': form.errors.name }"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- Description -->
              <div>
                <Label for="description">Descripción *</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Describe el objetivo y alcance del proyecto..."
                  rows="4"
                  :disabled="!canEdit"
                  :class="{ 'border-red-500': form.errors.description }"
                />
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- Notes -->
              <div>
                <Label for="notes">Notas Adicionales (Opcional)</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Información adicional relevante..."
                  rows="3"
                  :disabled="!canEdit"
                />
              </div>
            </CardContent>
          </Card>

          <!-- Financial Configuration Card -->
          <Card>
            <CardHeader>
              <CardTitle>Configuración Financiera</CardTitle>
              <CardDescription>
                Monto total y forma de pago
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Total Amount -->
              <div>
                <Label for="total_amount">Monto Total del Proyecto *</Label>
                <Input
                  id="total_amount"
                  v-model="form.total_amount"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="0.00"
                  :disabled="!canEdit"
                  :class="{ 'border-red-500': form.errors.total_amount }"
                />
                <p v-if="form.errors.total_amount" class="mt-1 text-sm text-red-600">
                  {{ form.errors.total_amount }}
                </p>
              </div>

              <!-- Number of Installments -->
              <div>
                <Label for="number_of_installments">Número de Cuotas Mensuales *</Label>
                <Input
                  id="number_of_installments"
                  v-model.number="form.number_of_installments"
                  type="number"
                  min="1"
                  max="60"
                  :disabled="!canEdit"
                  :class="{ 'border-red-500': form.errors.number_of_installments }"
                />
                <p class="mt-1 text-xs text-gray-500">
                  El cobro se distribuirá en {{ form.number_of_installments }} meses
                </p>
                <p v-if="form.errors.number_of_installments" class="mt-1 text-sm text-red-600">
                  {{ form.errors.number_of_installments }}
                </p>
              </div>

              <!-- Start Date -->
              <div>
                <Label for="start_date">Fecha de Inicio del Cobro *</Label>
                <Input
                  id="start_date"
                  v-model="form.start_date"
                  type="date"
                  :disabled="!canEdit"
                  :class="{ 'border-red-500': form.errors.start_date }"
                />
                <p class="mt-1 text-xs text-gray-500">
                  Primera cuota se cobrará en este mes
                </p>
                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                  {{ form.errors.start_date }}
                </p>
              </div>

              <!-- Distribution Type -->
              <div>
                <Label for="distribution_type">Tipo de Distribución *</Label>
                <Select v-model="form.distribution_type" :disabled="!canEdit">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.distribution_type }">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="by_coefficient">Por Coeficiente de Copropiedad</SelectItem>
                    <SelectItem value="equal">Igual para Todos</SelectItem>
                  </SelectContent>
                </Select>
                <p class="mt-1 text-xs text-gray-500">
                  <span v-if="form.distribution_type === 'by_coefficient'">
                    Cada apartamento paga según su % de propiedad (más justo)
                  </span>
                  <span v-else>
                    Todos los apartamentos pagan el mismo monto
                  </span>
                </p>
                <p v-if="form.errors.distribution_type" class="mt-1 text-sm text-red-600">
                  {{ form.errors.distribution_type }}
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Preview Card -->
          <Card v-if="form.total_amount && form.number_of_installments" class="border-blue-200 bg-blue-50">
            <CardHeader>
              <div class="flex items-center">
                <Calculator class="mr-2 h-5 w-5 text-blue-600" />
                <CardTitle class="text-blue-900">Vista Previa del Cálculo</CardTitle>
              </div>
              <CardDescription class="text-blue-700">
                Estimación de distribución del costo
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div class="flex justify-between items-center pb-3 border-b border-blue-200">
                  <span class="font-medium text-blue-900">Monto Total</span>
                  <span class="text-xl font-bold text-blue-900">
                    {{ formatCurrency(parseFloat(form.total_amount) || 0) }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-blue-700">Total de Apartamentos</span>
                  <span class="font-semibold text-blue-900">{{ totalApartments }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-blue-700">Monto por Apartamento (aprox.)</span>
                  <span class="font-semibold text-blue-900">
                    {{ formatCurrency(amountPerApartment) }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-blue-700">Número de Cuotas</span>
                  <span class="font-semibold text-blue-900">{{ form.number_of_installments }} meses</span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-blue-200">
                  <span class="font-medium text-blue-900">Cuota Mensual por Apartamento (aprox.)</span>
                  <span class="text-xl font-bold text-blue-900">
                    {{ formatCurrency(installmentPerApartment) }}
                  </span>
                </div>
              </div>

              <Alert class="mt-4 bg-white border-blue-300">
                <Info class="h-4 w-4 text-blue-600" />
                <AlertDescription class="text-sm text-blue-700">
                  <span v-if="form.distribution_type === 'by_coefficient'">
                    Los montos exactos se calcularán según el coeficiente de copropiedad de cada apartamento.
                  </span>
                  <span v-else>
                    Todos los apartamentos pagarán exactamente este monto mensual.
                  </span>
                </AlertDescription>
              </Alert>
            </CardContent>
          </Card>

          <!-- Info Alert -->
          <Alert v-if="canEdit">
            <Info class="h-4 w-4" />
            <AlertDescription>
              Los cambios solo se aplicarán después de guardar. Si activas la cuota extraordinaria después de editar,
              se recalcularán los montos por apartamento según la nueva configuración.
            </AlertDescription>
          </Alert>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-4">
            <Link :href="route('extraordinary-assessments.show', assessment.id)">
              <Button type="button" variant="outline">
                Cancelar
              </Button>
            </Link>
            <Button
              type="submit"
              :disabled="form.processing || !canEdit"
            >
              {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
