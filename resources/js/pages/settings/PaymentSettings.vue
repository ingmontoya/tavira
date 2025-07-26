<template>
  <AppLayout>
    <Head title="Configuración de Pagos" />
    <SettingsLayout>
      <div class="space-y-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Configuración de Pagos</h1>
          <p class="mt-1 text-sm text-gray-600">
            Configurar descuentos por pronto pago y cálculo de intereses de mora.
          </p>
        </div>

      <form @submit.prevent="updateSettings" class="space-y-8">
        <!-- Early Payment Discount Section -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <PercentIcon class="w-5 h-5" />
              <span>Descuento por Pronto Pago</span>
            </CardTitle>
            <CardDescription>
              Configurar descuentos para pagos realizados dentro del período establecido.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center space-x-2">
              <Switch 
                id="early-discount" 
                v-model:checked="form.early_discount_enabled"
              />
              <Label for="early-discount">Habilitar descuento por pronto pago</Label>
            </div>

            <div v-if="form.early_discount_enabled" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="discount-days">Días para descuento</Label>
                  <Input
                    id="discount-days"
                    type="number"
                    v-model.number="form.early_discount_days"
                    min="1"
                    max="30"
                    required
                  />
                  <p class="text-xs text-gray-500">
                    Número de días desde la fecha de facturación para aplicar el descuento
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="discount-percentage">Porcentaje de descuento (%)</Label>
                  <Input
                    id="discount-percentage"
                    type="number"
                    step="0.1"
                    v-model.number="form.early_discount_percentage"
                    min="0"
                    max="100"
                    required
                  />
                  <p class="text-xs text-gray-500">
                    Porcentaje de descuento sobre el subtotal de la factura
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Late Fees Section -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <AlertTriangleIcon class="w-5 h-5" />
              <span>Intereses de Mora</span>
            </CardTitle>
            <CardDescription>
              Configurar el cálculo de intereses para facturas vencidas.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center space-x-2">
              <Switch 
                id="late-fees" 
                v-model:checked="form.late_fees_enabled"
              />
              <Label for="late-fees">Habilitar intereses de mora</Label>
            </div>

            <div v-if="form.late_fees_enabled" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="late-fee-percentage">Porcentaje de mora mensual (%)</Label>
                  <Input
                    id="late-fee-percentage"
                    type="number"
                    step="0.1"
                    v-model.number="form.late_fee_percentage"
                    min="0"
                    max="100"
                    required
                  />
                  <p class="text-xs text-gray-500">
                    Porcentaje mensual aplicado sobre el subtotal de la factura
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="grace-period">Días de gracia</Label>
                  <Input
                    id="grace-period"
                    type="number"
                    v-model.number="form.grace_period_days"
                    min="0"
                    max="30"
                    required
                  />
                  <p class="text-xs text-gray-500">
                    Días después del vencimiento antes de aplicar intereses
                  </p>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <Switch 
                  id="compound-monthly" 
                  v-model:checked="form.late_fees_compound_monthly"
                />
                <Label for="compound-monthly">Aplicar interés compuesto mensual</Label>
              </div>
              <p class="text-xs text-gray-500">
                Si está habilitado, los intereses se calculan de forma compuesta cada mes.
                Si está deshabilitado, se aplica interés simple.
              </p>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end">
          <Button type="submit" :disabled="form.processing">
            <template v-if="form.processing">
              Guardando...
            </template>
            <template v-else>
              Guardar Configuración
            </template>
          </Button>
        </div>
      </form>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import { PercentIcon, AlertTriangleIcon } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import { useToast } from '@/composables/useToast'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'

interface PaymentSettings {
  early_discount_enabled: boolean
  early_discount_days: number
  early_discount_percentage: number
  late_fees_enabled: boolean
  late_fee_percentage: number
  late_fees_compound_monthly: boolean
  grace_period_days: number
}

interface Props {
  settings: PaymentSettings
}

const props = defineProps<Props>()
const { success } = useToast()

const form = useForm({
  early_discount_enabled: props.settings.early_discount_enabled,
  early_discount_days: props.settings.early_discount_days,
  early_discount_percentage: props.settings.early_discount_percentage,
  late_fees_enabled: props.settings.late_fees_enabled,
  late_fee_percentage: props.settings.late_fee_percentage,
  late_fees_compound_monthly: props.settings.late_fees_compound_monthly,
  grace_period_days: props.settings.grace_period_days,
})

const updateSettings = () => {
  form.post(route('payments.update'), {
    preserveScroll: true,
    onSuccess: () => {
      success('Configuración de pagos actualizada correctamente')
    }
  })
}
</script>