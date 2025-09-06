<template>
  <Head title="Gestión de Features - Central Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Filtros Avanzados -->
      <Card class="mb-4 p-4">
        <div class="space-y-4">
          <!-- Búsqueda General -->
          <div>
            <Label for="search">Búsqueda General</Label>
            <div class="relative mt-3">
              <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
              <Input
                id="search"
                v-model="searchQuery"
                placeholder="Buscar tenants por ID, nombre o email..."
                class="max-w-md pl-10"
                @input="search"
              />
            </div>
          </div>

          <!-- Filtros por estado de features -->
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="min-w-0 space-y-2">
              <Label for="template_filter">Template Aplicado</Label>
              <Select v-model="templateFilter">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Todos los templates" class="truncate" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos los templates</SelectItem>
                  <SelectItem value="starter">Plan Inicial</SelectItem>
                  <SelectItem value="basic">Plan Básico</SelectItem>
                  <SelectItem value="standard">Plan Estándar</SelectItem>
                  <SelectItem value="professional">Plan Profesional</SelectItem>
                  <SelectItem value="enterprise">Plan Empresarial</SelectItem>
                  <SelectItem value="custom">Configuración Personalizada</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="min-w-0 space-y-2">
              <Label for="features_count">Cantidad de Features</Label>
              <Select v-model="featuresCountFilter">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Todas las cantidades" class="truncate" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todas las cantidades</SelectItem>
                  <SelectItem value="none">Sin features (0)</SelectItem>
                  <SelectItem value="low">Pocas features (1-3)</SelectItem>
                  <SelectItem value="medium">Mediano (4-6)</SelectItem>
                  <SelectItem value="high">Muchas features (7+)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="min-w-0 space-y-2">
              <Label for="specific_feature">Feature Específico</Label>
              <Select v-model="specificFeatureFilter">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Todos los features" class="truncate" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos los features</SelectItem>
                  <SelectItem v-for="feature in availableFeatures" :key="feature" :value="feature">
                    {{ getFeatureLabel(feature) }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex items-center justify-between">
            <Button variant="outline" @click="clearFilters" v-if="hasActiveFilters">
              <X class="mr-2 h-4 w-4" />
              Limpiar filtros
            </Button>
            <div class="text-sm text-muted-foreground">Mostrando {{ filteredTenants.length }} de {{ tenants.data.length }} tenants</div>
          </div>
        </div>
      </Card>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 py-4">
        <Button variant="outline" @click="refreshData">
          <RotateCcw class="mr-2 h-4 w-4" />
          Actualizar
        </Button>
        
        <Button variant="outline" @click="exportTenantFeatures">
          <Download class="mr-2 h-4 w-4" />
          Exportar
        </Button>
        
        <div class="ml-auto flex items-center space-x-2">
          <span class="text-sm text-muted-foreground">{{ filteredTenants.length }} tenant(s)</span>
        </div>
      </div>

      <!-- Tenants Cards -->
      <div class="space-y-6">
        <Card v-for="tenant in filteredTenants" :key="tenant.id" class="p-6">
          <!-- Tenant Header -->
          <div class="mb-6 flex items-start justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ tenant.admin_name || tenant.id }}</h3>
              <p class="text-sm text-muted-foreground">{{ tenant.admin_email }}</p>
              <p class="text-xs text-muted-foreground">ID: {{ tenant.id }}</p>
            </div>
            <div class="flex space-x-2">
              <!-- Template Selector -->
              <Select @update:model-value="(value) => applyTemplate(tenant.id, value)">
                <SelectTrigger class="w-48">
                  <SelectValue placeholder="Aplicar template..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="starter">Plan Inicial</SelectItem>
                  <SelectItem value="basic">Plan Básico</SelectItem>
                  <SelectItem value="standard">Plan Estándar</SelectItem>
                  <SelectItem value="professional">Plan Profesional</SelectItem>
                  <SelectItem value="enterprise">Plan Empresarial</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Features Grid -->
          <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <div v-for="feature in availableFeatures" :key="feature" class="flex items-center space-x-2">
              <Checkbox
                :id="`${tenant.id}-${feature}`"
                :model-value="isFeatureEnabled(tenant, feature)"
                @update:model-value="(checked) => toggleFeature(tenant.id, feature, checked)"
              />
              <Label 
                :for="`${tenant.id}-${feature}`" 
                class="text-sm font-normal cursor-pointer"
              >
                {{ getFeatureLabel(feature) }}
              </Label>
            </div>
          </div>

          <!-- Features Summary -->
          <div class="mt-6 flex items-center justify-between border-t pt-4">
            <div class="text-sm text-muted-foreground">
              {{ getEnabledFeaturesCount(tenant) }} de {{ availableFeatures.length }} features habilitados
            </div>
            <div class="flex space-x-1">
              <span
                v-for="feature in availableFeatures"
                :key="feature"
                :class="[
                  'h-2 w-2 rounded-full transition-colors',
                  isFeatureEnabled(tenant, feature) ? 'bg-green-500' : 'bg-muted'
                ]"
              ></span>
            </div>
          </div>
        </Card>
      </div>

      <!-- Pagination -->
      <div v-if="tenants.links && tenants.links.length > 0" class="flex items-center justify-end space-x-2 py-4">
        <div class="flex-1 text-sm text-muted-foreground">
          Mostrando {{ tenants.from || 1 }} a {{ tenants.to || tenants.data.length }} de {{ tenants.total || tenants.data.length }} resultados
        </div>
        <div class="space-x-2">
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="!tenants.prev_page_url"
            @click="navigateToPage(tenants.prev_page_url)"
          >
            Anterior
          </Button>
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="!tenants.next_page_url"
            @click="navigateToPage(tenants.next_page_url)"
          >
            Siguiente
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Download, RotateCcw, Search, X } from 'lucide-vue-next'
import { computed, ref, withDefaults } from 'vue'
import { route } from 'ziggy-js'

interface TenantFeature {
  id: number
  tenant_id: string
  feature: string
  enabled: boolean
}

interface Tenant {
  id: string
  admin_name?: string
  admin_email?: string
  features: TenantFeature[]
}

interface Props {
  tenants: {
    data: Tenant[]
    links: any[]
  }
  availableFeatures: string[]
  filters: {
    search?: string
  }
}

const props = withDefaults(defineProps<Props>(), {
  tenants: () => ({ data: [], links: [] }),
  availableFeatures: () => [],
  filters: () => ({})
})

// Filter states
const searchQuery = ref(props.filters.search || '')
const templateFilter = ref('all')
const featuresCountFilter = ref('all')
const specificFeatureFilter = ref('all')

// Breadcrumbs
const breadcrumbs = [
  {
    title: 'Central Dashboard',
    href: '/central-dashboard'
  },
  {
    title: 'Gestión de Features',
    href: '/tenant-features'
  }
]

const featureLabels: Record<string, string> = {
  // Comunicación y Notificaciones
  correspondence: 'Correspondencia',
  announcements: 'Anuncios',
  support_tickets: 'Tickets de Soporte / PQRS',
  notifications: 'Notificaciones Push',
  institutional_email: 'Correo Institucional',
  messaging: 'Mensajería Interna',
  
  // Administración Básica
  basic_administration: 'Administración Básica',
  resident_management: 'Gestión de Residentes',
  apartment_management: 'Gestión de Apartamentos',
  
  // Mantenimiento
  maintenance_requests: 'Solicitudes de Mantenimiento',
  
  // Gestión de Visitantes y Seguridad
  visitor_management: 'Gestión de Visitantes',
  security_scanner: 'Escáner de Seguridad QR',
  access_control: 'Control de Acceso',
  
  // Finanzas y Contabilidad
  accounting: 'Contabilidad Completa',
  payment_agreements: 'Acuerdos de Pago',
  expense_approvals: 'Aprobaciones de Gastos',
  financial_reports: 'Reportes Financieros',
  provider_management: 'Gestión de Proveedores',
  
  // Reservas y Espacios Comunes
  reservations: 'Reservas de Espacios',
  
  // Documentos y Actas
  documents: 'Gestión de Documentos',
  meeting_minutes: 'Actas de Reuniones',
  
  // Reportes y Análisis
  advanced_reports: 'Reportes Avanzados',
  analytics_dashboard: 'Dashboard de Análisis',
  
  // Configuración Avanzada
  system_settings: 'Configuración del Sistema',
  audit_logs: 'Auditoría y Logs',
  bulk_operations: 'Operaciones Masivas'
}

function getFeatureLabel(feature: string): string {
  return featureLabels[feature] || feature
}

function isFeatureEnabled(tenant: Tenant, feature: string): boolean {
  return tenant.features.some(f => f.feature === feature && f.enabled)
}

function getEnabledFeaturesCount(tenant: Tenant): number {
  return tenant.features.filter(f => f.enabled).length
}

function toggleFeature(tenantId: string, feature: string, enabled: boolean) {
  console.log('toggleFeature called:', { tenantId, feature, enabled })
  
  router.put(route('tenant-features.update-feature', { tenant: tenantId, feature }), {
    enabled
  }, {
    preserveState: false,  // Cambiado a false para refrescar los datos
    preserveScroll: true,
    onSuccess: () => {
      console.log('Feature toggled successfully')
    },
    onError: (errors) => {
      console.error('Error toggling feature:', errors)
    }
  })
}

function applyTemplate(tenantId: string, template: string) {
  if (!template) return
  
  router.post(route('tenant-features.apply-template', tenantId), {
    template
  }, {
    preserveState: false,  // Cambiado a false para refrescar los datos
    preserveScroll: true,
    onSuccess: () => {
      console.log(`Template ${template} aplicado al tenant ${tenantId}`)
    }
  })
}

// Computed filtered data
const filteredTenants = computed(() => {
  let filtered = props.tenants.data

  // Search filter
  if (searchQuery.value) {
    const searchTerm = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (tenant) =>
        tenant.id.toLowerCase().includes(searchTerm) ||
        tenant.admin_name?.toLowerCase().includes(searchTerm) ||
        tenant.admin_email?.toLowerCase().includes(searchTerm)
    )
  }

  // Features count filter
  if (featuresCountFilter.value !== 'all') {
    filtered = filtered.filter((tenant) => {
      const count = getEnabledFeaturesCount(tenant)
      switch (featuresCountFilter.value) {
        case 'none': return count === 0
        case 'low': return count >= 1 && count <= 3
        case 'medium': return count >= 4 && count <= 6
        case 'high': return count >= 7
        default: return true
      }
    })
  }

  // Specific feature filter
  if (specificFeatureFilter.value !== 'all') {
    filtered = filtered.filter((tenant) => 
      isFeatureEnabled(tenant, specificFeatureFilter.value)
    )
  }

  return filtered
})

// Check if filters are active
const hasActiveFilters = computed(() => {
  return searchQuery.value !== '' || 
         templateFilter.value !== 'all' ||
         featuresCountFilter.value !== 'all' ||
         specificFeatureFilter.value !== 'all'
})

// Clear all filters
function clearFilters() {
  searchQuery.value = ''
  templateFilter.value = 'all'
  featuresCountFilter.value = 'all'
  specificFeatureFilter.value = 'all'
}

function search() {
  router.get(route('tenant-features.index'), {
    search: searchQuery.value
  }, {
    preserveState: true,
    replace: true
  })
}

function refreshData() {
  router.reload({ only: ['tenants'] })
}

function exportTenantFeatures() {
  // Implementation for export functionality
  console.log('Exportando features de tenants...')
}

function navigateToPage(url: string | null) {
  if (url) {
    router.get(url, {}, {
      preserveState: true,
      preserveScroll: true
    })
  }
}
</script>