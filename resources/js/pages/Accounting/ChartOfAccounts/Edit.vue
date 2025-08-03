<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import ValidationErrors from '@/components/ValidationErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, TrendingUp, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    account_type: string;
    parent_account_id?: number;
    description?: string;
    level: number;
    nature: string;
    accepts_posting: boolean;
    requires_third_party: boolean;
    is_active: boolean;
}

interface ParentAccount {
    id: number;
    code: string;
    name: string;
    account_type: string;
}

interface FormData {
    code: string;
    name: string;
    account_type: string;
    parent_id: number | null;
    description: string;
    level: number;
    nature: string;
    accepts_posting: boolean;
    requires_third_party: boolean;
    is_active: boolean;
}

const props = defineProps<{
    account: ChartOfAccount;
    parentAccounts: ParentAccount[];
}>();

// Form state
const form = useForm<FormData>({
    code: props.account.code,
    name: props.account.name,
    account_type: props.account.account_type,
    parent_id: props.account.parent_account_id || null,
    description: props.account.description || '',
    level: props.account.level,
    nature: props.account.nature,
    accepts_posting: props.account.accepts_posting,
    requires_third_party: props.account.requires_third_party,
    is_active: props.account.is_active,
});

// UI state
const isUnsavedChanges = ref(false);

// Computed properties
const accountTypes = [
    { value: 'asset', label: 'Activo', description: 'Recursos económicos controlados por la entidad' },
    { value: 'liability', label: 'Pasivo', description: 'Obligaciones presentes de la entidad' },
    { value: 'equity', label: 'Patrimonio', description: 'Participación residual en los activos' },
    { value: 'income', label: 'Ingreso', description: 'Incrementos en los beneficios económicos' },
    { value: 'expense', label: 'Gasto', description: 'Decrementos en los beneficios económicos' },
];

const natures = [
    { value: 'debit', label: 'Débito', description: 'Aumenta con el debe' },
    { value: 'credit', label: 'Crédito', description: 'Aumenta con el haber' },
];

const filteredParentAccounts = computed(() => {
    if (!form.account_type) return [];
    return props.parentAccounts.filter(account => 
        account.account_type === form.account_type && account.id !== props.account.id
    );
});

const selectedAccountType = computed(() => {
    return accountTypes.find(type => type.value === form.account_type);
});

// Methods
const submit = () => {
    form.put(route('accounting.chart-of-accounts.update', props.account.id), {
        onSuccess: () => {
            isUnsavedChanges.value = false;
        },
    });
};

const deleteAccount = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta cuenta? Esta acción no se puede deshacer.')) {
        form.delete(route('accounting.chart-of-accounts.destroy', props.account.id));
    }
};

const resetForm = () => {
    form.reset();
    isUnsavedChanges.value = false;
};

// Watch for form changes
watch(
    form,
    () => {
        isUnsavedChanges.value = true;
    },
    { deep: true },
);

// Clear parent account when account type changes
watch(
    () => form.account_type,
    () => {
        if (form.account_type !== props.account.account_type) {
            form.parent_id = null;
        }
    }
);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Plan de Cuentas', href: '/accounting/chart-of-accounts' },
    { title: `Editar: ${props.account.name}`, href: `/accounting/chart-of-accounts/${props.account.id}/edit` },
];
</script>

<template>
    <Head :title="`Editar: ${account.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar Cuenta</h1>
                    <p class="text-muted-foreground">{{ account.code }} - {{ account.name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="`/accounting/chart-of-accounts/${account.id}`">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Validation Errors -->
            <ValidationErrors :errors="form.errors" />

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Información de la Cuenta
                        </CardTitle>
                        <CardDescription>
                            Modifica los datos de la cuenta contable
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Code and Name -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="code">Código de Cuenta *</Label>
                                <Input
                                    id="code"
                                    v-model="form.code"
                                    placeholder="Ej: 1105"
                                    class="font-mono"
                                    :class="{ 'border-red-500': form.errors.code }"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Código numérico único para identificar la cuenta
                                </p>
                                <p v-if="form.errors.code" class="text-sm text-red-600">
                                    {{ form.errors.code }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="name">Nombre de la Cuenta *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Ej: Caja General"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div class="space-y-2">
                            <Label for="account_type">Tipo de Cuenta *</Label>
                            <Select v-model="form.account_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona el tipo de cuenta">
                                        <template v-if="selectedAccountType">
                                            {{ selectedAccountType.label }}
                                        </template>
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in accountTypes" :key="type.value" :value="type.value">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ type.label }}</span>
                                            <span class="text-xs text-muted-foreground">{{ type.description }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="selectedAccountType" class="text-xs text-muted-foreground">
                                {{ selectedAccountType.description }}
                            </p>
                            <p v-if="form.errors.account_type" class="text-sm text-red-600">
                                {{ form.errors.account_type }}
                            </p>
                        </div>

                        <!-- Level and Nature -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="level">Nivel de Cuenta *</Label>
                                <Select v-model="form.level">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el nivel" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="1">Nivel 1 - Clase (1 dígito)</SelectItem>
                                        <SelectItem :value="2">Nivel 2 - Grupo (2 dígitos)</SelectItem>
                                        <SelectItem :value="3">Nivel 3 - Cuenta (4 dígitos)</SelectItem>
                                        <SelectItem :value="4">Nivel 4 - Subcuenta (6 dígitos)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.level" class="text-sm text-red-600">
                                    {{ form.errors.level }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="nature">Naturaleza *</Label>
                                <Select v-model="form.nature">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona la naturaleza" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="nature in natures" :key="nature.value" :value="nature.value">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ nature.label }}</span>
                                                <span class="text-xs text-muted-foreground">{{ nature.description }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.nature" class="text-sm text-red-600">
                                    {{ form.errors.nature }}
                                </p>
                            </div>
                        </div>

                        <!-- Parent Account -->
                        <div class="space-y-2" v-if="form.account_type">
                            <Label for="parent_id">Cuenta Principal (Opcional)</Label>
                            <Select v-model="form.parent_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona una cuenta principal" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Sin cuenta principal</SelectItem>
                                    <SelectItem v-for="account in filteredParentAccounts" :key="account.id" :value="account.id">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ account.code }} - {{ account.name }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">
                                Las subcuentas heredan el tipo de la cuenta principal
                            </p>
                            <p v-if="form.errors.parent_id" class="text-sm text-red-600">
                                {{ form.errors.parent_id }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Descripción</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Descripción detallada de la cuenta y su uso..."
                                :class="{ 'border-red-500': form.errors.description }"
                                rows="3"
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Configuration Options -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between rounded-lg border p-4">
                                <div class="space-y-0.5">
                                    <Label for="accepts_posting">Acepta Movimientos</Label>
                                    <p class="text-sm text-muted-foreground">
                                        Las cuentas que aceptan movimientos pueden tener transacciones directas
                                    </p>
                                </div>
                                <Switch
                                    v-model:checked="form.accepts_posting"
                                    :class="{ 'border-red-500': form.errors.accepts_posting }"
                                />
                            </div>

                            <div class="flex items-center justify-between rounded-lg border p-4">
                                <div class="space-y-0.5">
                                    <Label for="requires_third_party">Requiere Tercero</Label>
                                    <p class="text-sm text-muted-foreground">
                                        Indica si la cuenta requiere información de terceros (clientes, proveedores)
                                    </p>
                                </div>
                                <Switch
                                    v-model:checked="form.requires_third_party"
                                    :class="{ 'border-red-500': form.errors.requires_third_party }"
                                />
                            </div>

                            <div class="flex items-center justify-between rounded-lg border p-4">
                                <div class="space-y-0.5">
                                    <Label for="is_active">Estado de la Cuenta</Label>
                                    <p class="text-sm text-muted-foreground">
                                        Las cuentas activas aparecen en los formularios de transacciones
                                    </p>
                                </div>
                                <Switch
                                    v-model:checked="form.is_active"
                                    :class="{ 'border-red-500': form.errors.is_active }"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex items-center justify-between">
                    <Button 
                        type="button" 
                        variant="destructive" 
                        @click="deleteAccount"
                        class="gap-2"
                    >
                        <Trash2 class="h-4 w-4" />
                        Eliminar Cuenta
                    </Button>

                    <div class="flex items-center gap-3">
                        <Button type="button" variant="outline" @click="resetForm">
                            Descartar Cambios
                        </Button>
                        
                        <Button 
                            type="submit" 
                            :disabled="form.processing"
                            class="gap-2"
                        >
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>