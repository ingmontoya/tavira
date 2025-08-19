<template>
    <AppLayout>
        <Head title="Configuración DIAN" />
        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Configuración DIAN</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Configurar datos para facturación electrónica DIAN: rangos de numeración, municipios, tributos y unidades de medida.
                    </p>
                </div>

                <form @submit.prevent="updateSettings" class="space-y-8">
                    <!-- Enable DIAN Electronic Invoicing -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <FileTextIcon class="h-5 w-5" />
                                <span>Facturación Electrónica DIAN</span>
                            </CardTitle>
                            <CardDescription>
                                Habilitar y configurar la facturación electrónica según los requerimientos de la DIAN.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox" 
                                        id="dian-enabled"
                                        v-model="form.dian_electronic_invoicing_enabled"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                    />
                                    <Label for="dian-enabled">Habilitar facturación electrónica DIAN</Label>
                                </div>

                                <!-- Electronic Invoicing Mode -->
                                <div v-if="form.dian_electronic_invoicing_enabled" class="space-y-4 ml-6 pl-4 border-l-2 border-gray-200">
                                    <div class="space-y-2">
                                        <Label for="invoicing-mode">Modo de Facturación Electrónica</Label>
                                        <select
                                            id="invoicing-mode"
                                            v-model="form.dian_electronic_invoicing_mode"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <option value="disabled">Sin obligación DIAN</option>
                                            <option value="all">Obligatorio por normativa</option>
                                            <option value="optional">Los propietarios eligen</option>
                                            <option value="required_amount">Obligatorio por monto (Art. 616-1 ET)</option>
                                        </select>
                                    </div>

                                    <!-- Minimum amount for required_amount mode -->
                                    <div 
                                        v-if="form.dian_electronic_invoicing_mode === 'required_amount'" 
                                        class="space-y-2"
                                    >
                                        <Label for="min-amount">Monto Mínimo (COP)</Label>
                                        <Input
                                            id="min-amount"
                                            type="number"
                                            step="0.01"
                                            v-model.number="form.dian_electronic_invoicing_min_amount"
                                            placeholder="100000"
                                            min="0"
                                        />
                                        <p class="text-xs text-gray-500">
                                            Monto mínimo establecido por la DIAN para obligatoriedad de facturación electrónica
                                        </p>
                                    </div>

                                    <!-- Mode explanations -->
                                    <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-700">
                                        <p v-if="form.dian_electronic_invoicing_mode === 'disabled'">
                                            <strong>Sin obligación DIAN:</strong> El conjunto no está obligado a emitir facturación electrónica según la normativa vigente.
                                        </p>
                                        <p v-else-if="form.dian_electronic_invoicing_mode === 'all'">
                                            <strong>Obligatorio por normativa:</strong> Todas las facturas deben enviarse electrónicamente según obligación DIAN (p.ej. por actividad económica).
                                        </p>
                                        <p v-else-if="form.dian_electronic_invoicing_mode === 'optional'">
                                            <strong>Los propietarios eligen:</strong> Cada propietario puede elegir si desea recibir facturación electrónica o física. Esta preferencia se configura en su perfil.
                                        </p>
                                        <p v-else-if="form.dian_electronic_invoicing_mode === 'required_amount'">
                                            <strong>Obligatorio por monto:</strong> Facturas que superen el monto establecido en el Art. 616-1 del Estatuto Tributario deben ser electrónicas.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>


                    <div v-if="form.dian_electronic_invoicing_enabled" class="space-y-8">
                        <!-- Company Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <BuildingIcon class="h-5 w-5" />
                                    <span>Información de la Empresa</span>
                                </CardTitle>
                                <CardDescription>
                                    Datos de identificación de la empresa para facturación electrónica.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="company-nit">NIT</Label>
                                        <Input
                                            id="company-nit"
                                            v-model="form.dian_company_info.nit"
                                            placeholder="900123456-1"
                                            required
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="company-name">Razón Social</Label>
                                        <Input
                                            id="company-name"
                                            v-model="form.dian_company_info.business_name"
                                            placeholder="Conjunto Residencial Ejemplo S.A.S."
                                            required
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="company-trade-name">Nombre Comercial</Label>
                                        <Input
                                            id="company-trade-name"
                                            v-model="form.dian_company_info.trade_name"
                                            placeholder="Conjunto Ejemplo"
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="company-address">Dirección</Label>
                                        <Input
                                            id="company-address"
                                            v-model="form.dian_company_info.address"
                                            placeholder="Calle 123 # 45-67"
                                            required
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Numbering Ranges -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <HashIcon class="h-5 w-5" />
                                    <span>Rangos de Numeración</span>
                                </CardTitle>
                                <CardDescription>
                                    Configurar los rangos de numeración autorizados por la DIAN para cada tipo de documento.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div
                                        v-for="(range, index) in form.dian_numbering_ranges"
                                        :key="index"
                                        class="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-4"
                                    >
                                        <div class="space-y-2">
                                            <Label>Tipo de Documento</Label>
                                            <Input
                                                v-model="range.document_type"
                                                placeholder="FV - Factura de Venta"
                                                required
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Desde</Label>
                                            <Input
                                                v-model.number="range.from"
                                                type="number"
                                                placeholder="1"
                                                required
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Hasta</Label>
                                            <Input
                                                v-model.number="range.to"
                                                type="number"
                                                placeholder="10000"
                                                required
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                @click="removeNumberingRange(index)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="addNumberingRange"
                                        class="w-full"
                                    >
                                        Agregar Rango de Numeración
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Municipalities -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <MapPinIcon class="h-5 w-5" />
                                    <span>Municipios</span>
                                </CardTitle>
                                <CardDescription>
                                    Configurar los códigos DANE de municipios utilizados en la facturación.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <!-- Add from predefined list -->
                                    <div class="flex space-x-2">
                                        <div class="flex-1">
                                            <Label>Seleccionar municipio de la lista</Label>
                                            <SearchableSelect
                                                v-model="selectedMunicipality"
                                                :options="availableOptions.municipalities"
                                                option-value="code"
                                                option-label="name"
                                                placeholder="Buscar municipio..."
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <Button
                                                type="button"
                                                @click="addSelectedMunicipality"
                                                :disabled="!selectedMunicipality"
                                            >
                                                Agregar
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <!-- Selected municipalities -->
                                    <div class="space-y-2">
                                        <div
                                            v-for="(municipality, index) in form.dian_municipalities"
                                            :key="index"
                                            class="flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <span class="text-sm">
                                                <strong>{{ municipality.code }}</strong> - {{ municipality.name }}
                                            </span>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                @click="removeMunicipality(index)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <!-- Manual add option -->
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="addMunicipality"
                                        class="w-full"
                                    >
                                        + Agregar Municipio Manualmente
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Taxes -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <PercentIcon class="h-5 w-5" />
                                    <span>Tributos</span>
                                </CardTitle>
                                <CardDescription>
                                    Configurar los tributos (IVA, ICA, etc.) aplicables en la facturación.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <!-- Add from predefined list -->
                                    <div class="flex space-x-2">
                                        <div class="flex-1">
                                            <Label>Seleccionar tributo de la lista</Label>
                                            <SearchableSelect
                                                v-model="selectedTax"
                                                :options="availableOptions.taxes.map(tax => ({ ...tax, label: `${tax.name} (${tax.percentage}%)` }))"
                                                option-value="code"
                                                option-label="label"
                                                placeholder="Buscar tributo..."
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <Button
                                                type="button"
                                                @click="addSelectedTax"
                                                :disabled="!selectedTax"
                                            >
                                                Agregar
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <!-- Selected taxes -->
                                    <div class="space-y-2">
                                        <div
                                            v-for="(tax, index) in form.dian_taxes"
                                            :key="index"
                                            class="flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <span class="text-sm">
                                                <strong>{{ tax.code }}</strong> - {{ tax.name }} ({{ tax.percentage }}%)
                                            </span>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                @click="removeTax(index)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <!-- Manual add option -->
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="addTax"
                                        class="w-full"
                                    >
                                        + Agregar Tributo Manualmente
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Measurement Units -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <RulerIcon class="h-5 w-5" />
                                    <span>Unidades de Medida</span>
                                </CardTitle>
                                <CardDescription>
                                    Configurar las unidades de medida utilizadas en la facturación de servicios.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <!-- Add from predefined list -->
                                    <div class="flex space-x-2">
                                        <div class="flex-1">
                                            <Label>Seleccionar unidad de medida de la lista</Label>
                                            <SearchableSelect
                                                v-model="selectedMeasurementUnit"
                                                :options="availableOptions.measurementUnits.map(unit => ({ ...unit, label: `${unit.code} - ${unit.description}` }))"
                                                option-value="code"
                                                option-label="label"
                                                placeholder="Buscar unidad de medida..."
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <Button
                                                type="button"
                                                @click="addSelectedMeasurementUnit"
                                                :disabled="!selectedMeasurementUnit"
                                            >
                                                Agregar
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <!-- Selected measurement units -->
                                    <div class="space-y-2">
                                        <div
                                            v-for="(unit, index) in form.dian_measurement_units"
                                            :key="index"
                                            class="flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <span class="text-sm">
                                                <strong>{{ unit.code }}</strong> - {{ unit.description }}
                                            </span>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                @click="removeMeasurementUnit(index)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <!-- Manual add option -->
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="addMeasurementUnit"
                                        class="w-full"
                                    >
                                        + Agregar Unidad de Medida Manualmente
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Technical Configuration -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <SettingsIcon class="h-5 w-5" />
                                    <span>Configuración Técnica</span>
                                </CardTitle>
                                <CardDescription>
                                    Configuración técnica para la conexión con los servicios de la DIAN y proveedor de facturación electrónica.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- DIAN Configuration -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold">Configuración DIAN</h3>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="test-set-id">Test Set ID</Label>
                                            <Input
                                                id="test-set-id"
                                                v-model="form.dian_technical_config.test_set_id"
                                                placeholder="ID del conjunto de pruebas"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="software-id">Software ID</Label>
                                            <Input
                                                id="software-id"
                                                v-model="form.dian_technical_config.software_id"
                                                placeholder="ID del software registrado en DIAN"
                                            />
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <Label for="software-pin">Software PIN</Label>
                                            <Input
                                                id="software-pin"
                                                type="password"
                                                v-model="form.dian_technical_config.software_pin"
                                                placeholder="PIN del software"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Factus Configuration -->
                                <div class="space-y-4 border-t pt-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold">Configuración Factus (Proveedor)</h3>
                                        <div class="flex space-x-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="testFactusConnection"
                                                :disabled="testingFactus"
                                            >
                                                {{ testingFactus ? 'Probando...' : 'Probar Conexión' }}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="showTestInvoiceModal = true"
                                                :disabled="testingFactus || !form.dian_electronic_invoicing_enabled"
                                            >
                                                Probar Factura
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="factus-base-url">URL Base API</Label>
                                            <Input
                                                id="factus-base-url"
                                                v-model="form.dian_technical_config.factus_base_url"
                                                placeholder="https://api-sandbox.factus.com.co"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="factus-email">Email/Usuario</Label>
                                            <Input
                                                id="factus-email"
                                                type="email"
                                                v-model="form.dian_technical_config.factus_email"
                                                placeholder="sandbox@factus.com.co"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="factus-password">Contraseña</Label>
                                            <Input
                                                id="factus-password"
                                                type="password"
                                                v-model="form.dian_technical_config.factus_password"
                                                placeholder="Contraseña de Factus"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="factus-client-id">Client ID</Label>
                                            <Input
                                                id="factus-client-id"
                                                v-model="form.dian_technical_config.factus_client_id"
                                                placeholder="9f9ddd82-d0eb-47fe-9366-cb4607a6720a"
                                            />
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <Label for="factus-client-secret">Client Secret</Label>
                                            <Input
                                                id="factus-client-secret"
                                                type="password"
                                                v-model="form.dian_technical_config.factus_client_secret"
                                                placeholder="Client Secret de Factus"
                                            />
                                        </div>
                                    </div>

                                    <!-- Connection status -->
                                    <div 
                                        v-if="factusConnectionStatus"
                                        class="rounded-lg p-3 text-sm"
                                        :class="{
                                            'bg-green-50 text-green-700 border border-green-200': factusConnectionStatus.success,
                                            'bg-red-50 text-red-700 border border-red-200': !factusConnectionStatus.success
                                        }"
                                    >
                                        {{ factusConnectionStatus.message }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            <template v-if="form.processing"> Guardando... </template>
                            <template v-else> Guardar Configuración DIAN </template>
                        </Button>
                    </div>
                </form>

                <!-- Test Invoice Modal -->
                <div v-if="showTestInvoiceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                        <h3 class="text-lg font-semibold mb-4">Probar Factura Electrónica</h3>
                        <form @submit.prevent="testFactusInvoice" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="test-customer-name">Nombre del Cliente</Label>
                                <Input
                                    id="test-customer-name"
                                    v-model="testInvoiceForm.test_customer_name"
                                    placeholder="Juan Pérez"
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="test-customer-email">Email del Cliente</Label>
                                <Input
                                    id="test-customer-email"
                                    type="email"
                                    v-model="testInvoiceForm.test_customer_email"
                                    placeholder="juan@example.com"
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="test-customer-document">Documento del Cliente</Label>
                                <Input
                                    id="test-customer-document"
                                    v-model="testInvoiceForm.test_customer_document"
                                    placeholder="12345678"
                                    required
                                />
                            </div>
                            
                            <!-- Test status -->
                            <div 
                                v-if="testInvoiceStatus"
                                class="rounded-lg p-3 text-sm"
                                :class="{
                                    'bg-green-50 text-green-700 border border-green-200': testInvoiceStatus.success,
                                    'bg-red-50 text-red-700 border border-red-200': !testInvoiceStatus.success
                                }"
                            >
                                {{ testInvoiceStatus.message }}
                                <div v-if="testInvoiceStatus.data && testInvoiceStatus.success" class="mt-2 text-xs">
                                    <strong>UUID:</strong> {{ testInvoiceStatus.data.uuid }}<br>
                                    <strong>CUFE:</strong> {{ testInvoiceStatus.data.cufe }}
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showTestInvoiceModal = false"
                                    :disabled="testingInvoice"
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="testingInvoice"
                                >
                                    {{ testingInvoice ? 'Enviando...' : 'Enviar Factura de Prueba' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import SearchableSelect from '@/components/SearchableSelect.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import {
    BuildingIcon,
    FileTextIcon,
    HashIcon,
    MapPinIcon,
    PercentIcon,
    RulerIcon,
    SettingsIcon,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface NumberingRange {
    document_type: string
    from: number
    to: number
}

interface Municipality {
    code: string
    name: string
}

interface Tax {
    code: string
    name: string
    percentage: number
}

interface MeasurementUnit {
    code: string
    description: string
}

interface CompanyInfo {
    nit: string
    business_name: string
    trade_name: string
    address: string
}

interface TechnicalConfig {
    test_set_id: string
    software_id: string
    software_pin: string
}

interface DianConfig {
    enabled: boolean
    numberingRanges: NumberingRange[]
    municipalities: Municipality[]
    taxes: Tax[]
    measurementUnits: MeasurementUnit[]
    companyInfo: CompanyInfo
    technicalConfig: TechnicalConfig
}

interface Props {
    dianConfig: DianConfig
    availableOptions: {
        municipalities: Municipality[]
        taxes: Tax[]
        measurementUnits: MeasurementUnit[]
    }
}

const props = defineProps<Props>()
const { success } = useToast()

// Selection variables
const selectedMunicipality = ref('')
const selectedTax = ref('')
const selectedMeasurementUnit = ref('')

// Factus testing variables
const testingFactus = ref(false)
const factusConnectionStatus = ref<{ success: boolean, message: string } | null>(null)

// Test invoice variables
const showTestInvoiceModal = ref(false)
const testingInvoice = ref(false)
const testInvoiceStatus = ref<{ success: boolean, message: string, data?: any } | null>(null)
const testInvoiceForm = ref({
    test_customer_name: '',
    test_customer_email: '',
    test_customer_document: '',
})


const form = useForm({
    dian_electronic_invoicing_enabled: Boolean(props.dianConfig.enabled),
    dian_electronic_invoicing_mode: String(props.dianConfig.mode || 'disabled'),
    dian_electronic_invoicing_min_amount: Number(props.dianConfig.minAmount || 0),
    dian_numbering_ranges: Array.isArray(props.dianConfig.numberingRanges) ? props.dianConfig.numberingRanges : [],
    dian_municipalities: Array.isArray(props.dianConfig.municipalities) ? props.dianConfig.municipalities : [],
    dian_taxes: Array.isArray(props.dianConfig.taxes) ? props.dianConfig.taxes : [],
    dian_measurement_units: Array.isArray(props.dianConfig.measurementUnits) ? props.dianConfig.measurementUnits : [],
    dian_company_info: {
        nit: String(props.dianConfig.companyInfo?.nit || ''),
        business_name: String(props.dianConfig.companyInfo?.business_name || ''),
        trade_name: String(props.dianConfig.companyInfo?.trade_name || ''),
        address: String(props.dianConfig.companyInfo?.address || ''),
    },
    dian_technical_config: {
        test_set_id: String(props.dianConfig.technicalConfig?.test_set_id || ''),
        software_id: String(props.dianConfig.technicalConfig?.software_id || ''),
        software_pin: String(props.dianConfig.technicalConfig?.software_pin || ''),
        // Factus configuration with default sandbox values
        factus_base_url: String(props.dianConfig.technicalConfig?.factus_base_url || 'https://api-sandbox.factus.com.co'),
        factus_email: String(props.dianConfig.technicalConfig?.factus_email || 'sandbox@factus.com.co'),
        factus_password: String(props.dianConfig.technicalConfig?.factus_password || 'sandbox2024%'),
        factus_client_id: String(props.dianConfig.technicalConfig?.factus_client_id || '9f9ddd82-d0eb-47fe-9366-cb4607a6720a'),
        factus_client_secret: String(props.dianConfig.technicalConfig?.factus_client_secret || 'zL5SrKl50YJpibfrWPhRF3EkX0pXxLZxqiCwiy8a'),
    },
})

const addNumberingRange = () => {
    form.dian_numbering_ranges.push({
        document_type: '',
        from: 1,
        to: 1000,
    })
}

const removeNumberingRange = (index: number) => {
    form.dian_numbering_ranges.splice(index, 1)
}

const addMunicipality = () => {
    form.dian_municipalities.push({
        code: '',
        name: '',
    })
}

const removeMunicipality = (index: number) => {
    form.dian_municipalities.splice(index, 1)
}

const addTax = () => {
    form.dian_taxes.push({
        code: '',
        name: '',
        percentage: 0,
    })
}

const removeTax = (index: number) => {
    form.dian_taxes.splice(index, 1)
}

const addMeasurementUnit = () => {
    form.dian_measurement_units.push({
        code: '',
        description: '',
    })
}

const removeMeasurementUnit = (index: number) => {
    form.dian_measurement_units.splice(index, 1)
}

// Methods for adding from searchable selects
const addSelectedMunicipality = () => {
    if (!selectedMunicipality.value) return
    
    const municipality = props.availableOptions.municipalities.find(m => m.code === selectedMunicipality.value)
    if (!municipality) return
    
    // Check if already exists
    const exists = form.dian_municipalities.find(m => m.code === municipality.code)
    if (exists) return
    
    form.dian_municipalities.push({
        code: municipality.code,
        name: municipality.name,
    })
    
    selectedMunicipality.value = ''
}

const addSelectedTax = () => {
    if (!selectedTax.value) return
    
    const tax = props.availableOptions.taxes.find(t => t.code === selectedTax.value)
    if (!tax) return
    
    // Check if already exists
    const exists = form.dian_taxes.find(t => t.code === tax.code)
    if (exists) return
    
    form.dian_taxes.push({
        code: tax.code,
        name: tax.name,
        percentage: tax.percentage,
    })
    
    selectedTax.value = ''
}

const addSelectedMeasurementUnit = () => {
    if (!selectedMeasurementUnit.value) return
    
    const unit = props.availableOptions.measurementUnits.find(u => u.code === selectedMeasurementUnit.value)
    if (!unit) return
    
    // Check if already exists
    const exists = form.dian_measurement_units.find(u => u.code === unit.code)
    if (exists) return
    
    form.dian_measurement_units.push({
        code: unit.code,
        description: unit.description,
    })
    
    selectedMeasurementUnit.value = ''
}

const testFactusConnection = async () => {
    testingFactus.value = true
    factusConnectionStatus.value = null
    
    try {
        const response = await fetch(route('settings.dian.test-factus-connection'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                base_url: form.dian_technical_config.factus_base_url,
                email: form.dian_technical_config.factus_email,
                password: form.dian_technical_config.factus_password,
                client_id: form.dian_technical_config.factus_client_id,
                client_secret: form.dian_technical_config.factus_client_secret,
            }),
        })
        
        const result = await response.json()
        factusConnectionStatus.value = result
        
        if (result.success) {
            success('Conexión con Factus exitosa')
        }
    } catch (error) {
        factusConnectionStatus.value = {
            success: false,
            message: 'Error al probar la conexión: ' + error,
        }
    } finally {
        testingFactus.value = false
    }
}

const testFactusInvoice = async () => {
    testingInvoice.value = true
    testInvoiceStatus.value = null
    
    try {
        const response = await fetch(route('settings.dian.test-factus-invoice'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(testInvoiceForm.value),
        })
        
        const result = await response.json()
        testInvoiceStatus.value = result
        
        if (result.success) {
            success('Factura de prueba enviada exitosamente')
        }
    } catch (error) {
        testInvoiceStatus.value = {
            success: false,
            message: 'Error al enviar factura de prueba: ' + error,
        }
    } finally {
        testingInvoice.value = false
    }
}

const updateSettings = () => {
    form.post(route('settings.dian.update'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                success(page.props.flash.success)
            }
        },
        onError: (errors) => {
            console.error('Error updating DIAN settings:', errors)
        },
    })
}
</script>