<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, Edit, Plus, Settings, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

interface PaymentConcept {
    id: number;
    name: string;
    type: string;
    type_label: string;
}

interface Account {
    id: number;
    code: string;
    name: string;
    full_name: string;
}

interface PaymentConceptMapping {
    id: number;
    payment_concept: PaymentConcept;
    income_account: Account | null;
    receivable_account: Account | null;
    is_active: boolean;
    notes: string | null;
}

interface Props {
    mappings: PaymentConceptMapping[];
    concepts_without_mapping: PaymentConcept[];
    income_accounts: Account[];
    asset_accounts: Account[];
}

const props = defineProps<Props>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingMapping = ref<PaymentConceptMapping | null>(null);

const createForm = useForm({
    payment_concept_id: '',
    income_account_id: '',
    receivable_account_id: '',
    notes: '',
});

const editForm = useForm({
    income_account_id: null,
    receivable_account_id: null,
    is_active: true,
    notes: '',
});

const openCreateDialog = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateDialog.value = true;
};

const openEditDialog = (mapping: PaymentConceptMapping) => {
    editingMapping.value = mapping;
    
    // Resetear el form primero para limpiar el estado anterior
    editForm.reset();
    
    // Luego asignar los nuevos valores
    editForm.income_account_id = mapping.income_account?.id?.toString() || null;
    editForm.receivable_account_id = mapping.receivable_account?.id?.toString() || null;
    editForm.is_active = mapping.is_active;
    editForm.notes = mapping.notes || '';
    
    showEditDialog.value = true;
};

const createMapping = () => {
    // Ensure proper data types before submission
    const formData = {
        payment_concept_id: createForm.payment_concept_id,
        income_account_id: createForm.income_account_id,
        receivable_account_id: createForm.receivable_account_id,
        notes: createForm.notes || ''
    };
    
    console.log('Creating mapping with data:', formData);
    
    // Convert string IDs to integers if they exist and are not empty
    if (formData.payment_concept_id && formData.payment_concept_id !== '') {
        formData.payment_concept_id = parseInt(formData.payment_concept_id);
    }
    if (formData.income_account_id && formData.income_account_id !== '') {
        formData.income_account_id = parseInt(formData.income_account_id);
    }
    if (formData.receivable_account_id && formData.receivable_account_id !== '') {
        formData.receivable_account_id = parseInt(formData.receivable_account_id);
    }
    
    createForm.transform(() => formData).post(route('settings.payment-concept-mapping.store'), {
        onSuccess: () => {
            showCreateDialog.value = false;
            createForm.reset();
        },
        onError: (errors) => {
            console.log('Form errors:', errors);
        }
    });
};

const updateMapping = () => {
    if (!editingMapping.value) return;

    editForm.put(route('settings.payment-concept-mapping.update', editingMapping.value.id), {
        onSuccess: () => {
            showEditDialog.value = false;
            editingMapping.value = null;
            editForm.reset();
        },
    });
};

const deleteMapping = (mapping: PaymentConceptMapping) => {
    router.delete(route('settings.payment-concept-mapping.destroy', mapping.id));
};

const toggleActive = (mapping: PaymentConceptMapping) => {
    router.post(route('settings.payment-concept-mapping.toggle-active', mapping.id));
};

const createDefaultMappings = () => {
    router.post(route('settings.payment-concept-mapping.create-defaults'));
};

const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
        common_expense: 'bg-blue-100 text-blue-800',
        sanction: 'bg-red-100 text-red-800',
        parking: 'bg-green-100 text-green-800',
        late_fee: 'bg-orange-100 text-orange-800',
        special: 'bg-purple-100 text-purple-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const hasUnmappedConcepts = computed(() => props.concepts_without_mapping.length > 0);
</script>

<template>
    <Head title="Mapeo de Cuentas Contables" />

    <AppLayout>
        <SettingsLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mapeo de Cuentas Contables</h1>
                    <p class="text-gray-600">Configure qué cuentas contables se afectan por cada concepto de pago</p>
                </div>

                <div class="flex gap-2">
                    <Button v-if="hasUnmappedConcepts" @click="createDefaultMappings" variant="outline">
                        <Settings class="mr-2 h-4 w-4" />
                        Crear Mapeos por Defecto
                    </Button>

                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Mapeo
                    </Button>
                </div>
            </div>

            <!-- Alert for unmapped concepts -->
            <div v-if="hasUnmappedConcepts" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-amber-800">Conceptos sin mapeo contable</h3>
                        <div class="mt-2 text-sm text-amber-700">
                            <p>Hay {{ concepts_without_mapping.length }} conceptos de pago que no tienen mapeo contable configurado.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mappings Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Mapeos Configurados</CardTitle>
                    <CardDescription> Lista de todos los conceptos de pago y sus cuentas contables asociadas </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Concepto de Pago</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="mapping in mappings" 
                                :key="mapping.id"
                                @click="openEditDialog(mapping)"
                                class="cursor-pointer hover:bg-gray-50 transition-colors"
                            >
                                <TableCell class="font-medium">
                                    <div>
                                        <div class="font-semibold">{{ mapping.payment_concept.name }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span v-if="mapping.income_account">
                                                Ingreso: {{ mapping.income_account.code }} - {{ mapping.income_account.name }}
                                            </span>
                                            <span v-else>Sin cuenta de ingreso configurada</span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <span v-if="mapping.receivable_account">
                                                Cartera: {{ mapping.receivable_account.code }} - {{ mapping.receivable_account.name }}
                                            </span>
                                            <span v-else>Sin cuenta por cobrar configurada</span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getTypeColor(mapping.payment_concept.type)">
                                        {{ mapping.payment_concept.type_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle v-if="mapping.is_active" class="h-4 w-4 text-green-600" />
                                        <XCircle v-else class="h-4 w-4 text-red-600" />
                                        <span :class="mapping.is_active ? 'text-green-600' : 'text-red-600'">
                                            {{ mapping.is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-2">
                                        <Button @click="toggleActive(mapping)" variant="outline" size="sm">
                                            {{ mapping.is_active ? 'Desactivar' : 'Activar' }}
                                        </Button>

                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button variant="destructive" size="sm">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>¿Eliminar mapeo?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Esta acción no se puede deshacer. Se eliminará el mapeo contable para el concepto "{{
                                                            mapping.payment_concept.name
                                                        }}".
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                    <AlertDialogAction @click="deleteMapping(mapping)"> Eliminar </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Create Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Nuevo Mapeo de Cuentas</DialogTitle>
                    <DialogDescription> Configure las cuentas contables para un concepto de pago </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="createMapping" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="payment_concept_id">Concepto de Pago</Label>
                        <Select v-model="createForm.payment_concept_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione un concepto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="concept in concepts_without_mapping" :key="concept.id" :value="concept.id.toString()">
                                    {{ concept.name }} ({{ concept.type_label }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="createForm.errors.payment_concept_id" class="text-sm text-red-600">
                            {{ createForm.errors.payment_concept_id }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="income_account_id">Cuenta de Ingresos</Label>
                        <Select v-model="createForm.income_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione cuenta de ingresos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="account in income_accounts" :key="account.id" :value="account.id.toString()">
                                    {{ account.full_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="createForm.errors.income_account_id" class="text-sm text-red-600">
                            {{ createForm.errors.income_account_id }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="receivable_account_id">Cuenta por Cobrar</Label>
                        <Select v-model="createForm.receivable_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione cuenta por cobrar" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="account in asset_accounts" :key="account.id" :value="account.id.toString()">
                                    {{ account.full_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="createForm.errors.receivable_account_id" class="text-sm text-red-600">
                            {{ createForm.errors.receivable_account_id }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notas (opcional)</Label>
                        <Textarea v-model="createForm.notes" placeholder="Notas adicionales sobre este mapeo..." class="resize-none" rows="3" />
                    </div>
                </form>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="showCreateDialog = false"> Cancelar </Button>
                    <Button @click="createMapping" :disabled="createForm.processing"> Crear Mapeo </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Mapeo de Cuentas</DialogTitle>
                    <DialogDescription> Modifique las cuentas contables para "{{ editingMapping?.payment_concept.name }}" </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="updateMapping" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit_income_account_id">Cuenta de Ingresos</Label>
                        <Select v-model="editForm.income_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione cuenta de ingresos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="account in income_accounts" :key="account.id" :value="account.id.toString()">
                                    {{ account.full_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit_receivable_account_id">Cuenta por Cobrar</Label>
                        <Select v-model="editForm.receivable_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione cuenta por cobrar" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="account in asset_accounts" :key="account.id" :value="account.id.toString()">
                                    {{ account.full_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch v-model:checked="editForm.is_active" id="is_active" />
                        <Label for="is_active">Mapeo activo</Label>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit_notes">Notas</Label>
                        <Textarea v-model="editForm.notes" placeholder="Notas adicionales sobre este mapeo..." class="resize-none" rows="3" />
                    </div>
                </form>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="showEditDialog = false"> Cancelar </Button>
                    <Button @click="updateMapping" :disabled="editForm.processing"> Actualizar </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        </SettingsLayout>
    </AppLayout>
</template>
