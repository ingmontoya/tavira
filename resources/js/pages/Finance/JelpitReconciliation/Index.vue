<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Upload, Download, Filter, Eye, Check, X, AlertCircle, FileSpreadsheet } from 'lucide-vue-next'

interface JelpitImport {
  id: number
  payment_type: string
  reference_number?: string
  transaction_date: string
  transaction_amount: number
  originator_nit?: string
  payment_detail?: string
  reconciliation_status: string
  status_badge: { text: string; class: string }
  apartment?: {
    id: number
    number: string
    full_address: string
  }
  payment?: {
    id: number
    payment_number: string
  }
  match_type?: string
  match_notes?: string
  can_create_payment: boolean
  is_reconciled: boolean
  created_at: string
}

interface Statistics {
  total: number
  pending: number
  matched: number
  manual_review: number
  total_amount: number
}

interface Batch {
  id: string
  date: string
}

interface Props {
  imports: {
    data: JelpitImport[]
    links: any[]
    meta: any
  }
  statistics: Statistics
  batches: Batch[]
  filters: {
    status?: string
    batch?: string
  }
}

const props = defineProps<Props>()

const selectedImports = ref<number[]>([])
const showUploadDialog = ref(false)
const showBatchDialog = ref(false)

const uploadForm = useForm({
  file: null as File | null
})

const batchForm = useForm({
  action: '',
  import_ids: [] as number[],
  reason: ''
})

const filterForm = useForm({
  status: props.filters?.status || 'all',
  batch: props.filters?.batch || 'all'
})

const allSelected = computed(() => {
  return props.imports?.data?.length > 0 && selectedImports.value.length === props.imports.data.length
})

const someSelected = computed(() => {
  return selectedImports.value.length > 0 && selectedImports.value.length < props.imports.data.length
})

function toggleAll() {
  if (allSelected.value) {
    selectedImports.value = []
  } else {
    selectedImports.value = props.imports.data.map(item => item.id)
  }
}

function toggleSelection(id: number) {
  const index = selectedImports.value.indexOf(id)
  if (index > -1) {
    selectedImports.value.splice(index, 1)
  } else {
    selectedImports.value.push(id)
  }
}

function uploadFile() {
  uploadForm.post(route('finance.jelpit-reconciliation.upload'), {
    onSuccess: () => {
      showUploadDialog.value = false
      uploadForm.reset()
    },
    preserveScroll: true
  })
}

function applyFilters() {
  const filters = {
    status: filterForm.status === 'all' ? '' : filterForm.status,
    batch: filterForm.batch === 'all' ? '' : filterForm.batch
  }

  router.get(route('finance.jelpit-reconciliation.index'), filters, {
    preserveState: true,
    preserveScroll: true
  })
}

function clearFilters() {
  filterForm.status = 'all'
  filterForm.batch = 'all'
  router.get(route('finance.jelpit-reconciliation.index'))
}

function processBatch(action: string) {
  batchForm.action = action
  batchForm.import_ids = selectedImports.value
  showBatchDialog.value = true
}

function submitBatch() {
  batchForm.post(route('finance.jelpit-reconciliation.batch-process'), {
    onSuccess: () => {
      showBatchDialog.value = false
      batchForm.reset()
      selectedImports.value = []
    },
    preserveScroll: true
  })
}

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0
  }).format(amount)
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'matched': return Check
    case 'manual_review': return AlertCircle
    case 'rejected': return X
    default: return AlertCircle
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Conciliación Jelpit" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Conciliación Jelpit</h1>
          <p class="text-sm text-gray-600">Gestiona y concilia los pagos importados desde la plataforma Jelpit</p>
        </div>

        <div class="flex gap-2">
          <Button @click="showUploadDialog = true" class="gap-2">
            <Upload class="h-4 w-4" />
            Importar Archivo
          </Button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Registros</CardTitle>
            <FileSpreadsheet class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statistics?.total || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
            <AlertCircle class="h-4 w-4 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ statistics?.pending || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Conciliados</CardTitle>
            <Check class="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ statistics?.matched || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Revisión Manual</CardTitle>
            <Eye class="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ statistics?.manual_review || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Monto Total</CardTitle>
            <Download class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(statistics?.total_amount || 0) }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="text-lg flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filtros
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 items-end">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
              <div class="space-y-2">
                <Label>Estado</Label>
                <Select v-model="filterForm.status">
                  <SelectTrigger>
                    <SelectValue placeholder="Todos los estados" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos los estados</SelectItem>
                    <SelectItem value="pending">Pendiente</SelectItem>
                    <SelectItem value="matched">Conciliado</SelectItem>
                    <SelectItem value="manual_review">Revisión Manual</SelectItem>
                    <SelectItem value="rejected">Rechazado</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-2">
                <Label>Lote de Importación</Label>
                <Select v-model="filterForm.batch">
                  <SelectTrigger>
                    <SelectValue placeholder="Todos los lotes" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos los lotes</SelectItem>
                    <SelectItem v-for="batch in batches" :key="batch.id" :value="batch.id">
                      {{ batch.date }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="flex gap-2">
              <Button @click="applyFilters" variant="outline">Aplicar</Button>
              <Button @click="clearFilters" variant="ghost">Limpiar</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Batch Actions -->
      <div v-if="selectedImports.length > 0" class="flex items-center gap-2 p-4 bg-blue-50 rounded-lg border">
        <span class="text-sm text-blue-700">{{ selectedImports.length }} registros seleccionados</span>
        <div class="flex gap-2 ml-auto">
          <Button @click="processBatch('create_payments')" size="sm" variant="outline">
            Crear Pagos
          </Button>
          <Button @click="processBatch('reject_all')" size="sm" variant="destructive">
            Rechazar Todos
          </Button>
        </div>
      </div>

      <!-- Imports Table -->
      <Card>
        <CardHeader>
          <CardTitle>Registros Importados</CardTitle>
          <CardDescription>Lista de pagos importados desde Jelpit con su estado de conciliación</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-12">
                  <Checkbox
                    :checked="allSelected"
                    :indeterminate="someSelected"
                    @update:checked="toggleAll"
                  />
                </TableHead>
                <TableHead>Fecha</TableHead>
                <TableHead>Tipo de Pago</TableHead>
                <TableHead>No. Ref</TableHead>
                <TableHead>Monto</TableHead>
                <TableHead>NIT</TableHead>
                <TableHead>Apartamento</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead>Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="import_item in imports?.data" :key="import_item.id">
                <TableCell>
                  <Checkbox
                    :checked="selectedImports.includes(import_item.id)"
                    @update:checked="() => toggleSelection(import_item.id)"
                  />
                </TableCell>
                <TableCell class="font-medium">
                  {{ new Date(import_item.transaction_date).toLocaleDateString('es-CO') }}
                </TableCell>
                <TableCell>{{ import_item.payment_type }}</TableCell>
                <TableCell>{{ import_item.reference_number || '-' }}</TableCell>
                <TableCell>{{ formatCurrency(import_item.transaction_amount) }}</TableCell>
                <TableCell class="font-mono text-sm">{{ import_item.originator_nit || '-' }}</TableCell>
                <TableCell>
                  <span v-if="import_item.apartment" class="text-sm">
                    {{ import_item.apartment.full_address }}
                  </span>
                  <span v-else class="text-gray-500 text-sm">Sin asignar</span>
                </TableCell>
                <TableCell>
                  <Badge :class="import_item?.status_badge?.class" class="flex items-center gap-1 w-fit" v-if="import_item?.status_badge">
                    <component :is="getStatusIcon(import_item.reconciliation_status)" class="h-3 w-3" />
                    {{ import_item.status_badge.text }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <Button asChild size="sm" variant="outline">
                      <Link :href="route('finance.jelpit-reconciliation.show', import_item.id)">
                        <Eye class="h-4 w-4" />
                      </Link>
                    </Button>

                    <Button
                      v-if="import_item?.can_create_payment && import_item?.id"
                      @click="() => $inertia.post(route('finance.jelpit-reconciliation.create-payment', import_item.id))"
                      size="sm"
                      class="bg-green-600 text-white hover:bg-green-700"
                    >
                      Crear Pago
                    </Button>

                    <Button
                      v-if="import_item?.payment?.id"
                      asChild
                      size="sm"
                      variant="outline"
                    >
                      <Link :href="route('finance.payments.show', import_item.payment.id)">
                        Ver Pago
                      </Link>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Pagination -->
          <div v-if="imports?.links?.length > 3" class="mt-4 flex justify-center">
            <nav class="flex items-center gap-1">
              <template v-for="link in imports.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    'px-3 py-2 text-sm border rounded',
                    link.active
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  <span v-html="link.label"></span>
                </Link>
                <span
                  v-else
                  :class="[
                    'px-3 py-2 text-sm border rounded',
                    'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                  ]"
                  v-html="link.label"
                />
              </template>
            </nav>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Upload Dialog -->
    <Dialog v-model:open="showUploadDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Importar Archivo Jelpit</DialogTitle>
          <DialogDescription>
            Selecciona el archivo Excel (.xlsx) exportado desde la plataforma Jelpit
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="uploadFile" class="space-y-4">
          <div class="space-y-2">
            <Label for="file">Archivo Excel</Label>
            <Input
              id="file"
              type="file"
              accept=".xlsx,.xls"
              @change="uploadForm.file = $event.target.files[0]"
              required
            />
            <p class="text-sm text-gray-500">
              Formatos soportados: .xlsx, .xls (máximo 10MB)
            </p>
            <div v-if="uploadForm.errors.file" class="text-sm text-red-600">
              {{ uploadForm.errors.file }}
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <Button @click="showUploadDialog = false" variant="outline" type="button">
              Cancelar
            </Button>
            <Button type="submit" :disabled="uploadForm.processing">
              {{ uploadForm.processing ? 'Procesando...' : 'Importar' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Batch Process Dialog -->
    <Dialog v-model:open="showBatchDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {{ batchForm.action === 'create_payments' ? 'Crear Pagos' : 'Rechazar Registros' }}
          </DialogTitle>
          <DialogDescription>
            {{ batchForm.action === 'create_payments'
              ? `Se crearán pagos para ${selectedImports.length} registros conciliados.`
              : `Se marcarán como rechazados ${selectedImports.length} registros.`
            }}
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitBatch" class="space-y-4">
          <div v-if="batchForm.action === 'reject_all'" class="space-y-2">
            <Label for="reason">Razón del Rechazo</Label>
            <Textarea
              id="reason"
              v-model="batchForm.reason"
              placeholder="Especifica la razón por la cual se rechazan estos registros..."
              required
            />
            <div v-if="batchForm.errors.reason" class="text-sm text-red-600">
              {{ batchForm.errors.reason }}
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <Button @click="showBatchDialog = false" variant="outline" type="button">
              Cancelar
            </Button>
            <Button
              type="submit"
              :disabled="batchForm.processing"
              :class="batchForm.action === 'reject_all' ? 'bg-red-600 hover:bg-red-700' : ''"
            >
              {{ batchForm.processing ? 'Procesando...' : 'Confirmar' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
