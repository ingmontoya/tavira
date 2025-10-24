<script setup lang="ts">
import AccountCombobox from '@/components/AccountCombobox.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import ValidationErrors from '@/components/ValidationErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calculator, FileText, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface TransactionEntry {
    id?: number;
    account_id: number | null;
    account?: {
        code: string;
        name: string;
        requires_third_party?: boolean;
    };
    description: string;
    debit_amount: number;
    credit_amount: number;
    third_party_type?: string | null;
    third_party_id?: number | null;
}

interface AccountingTransaction {
    id: number;
    reference: string;
    transaction_number: string;
    description: string;
    transaction_date: string;
    status: string;
    entries: TransactionEntry[];
}

interface FormData {
    reference: string;
    description: string;
    transaction_date: string;
    status: string;
    entries: TransactionEntry[];
}

interface Account {
    id: number;
    code: string;
    name: string;
    account_type: string;
    is_active: boolean;
    requires_third_party: boolean;
    nature: string;
}

interface Apartment {
    id: number;
    number: string;
    label: string;
}

interface Provider {
    id: number;
    name: string;
    label: string;
    document_number: string;
    category: string;
}

const props = defineProps<{
    transaction: AccountingTransaction;
    accounts: Account[];
    apartments: Apartment[];
    providers: Provider[];
}>();

// Form state
const form = useForm<FormData>({
    reference: props.transaction.reference,
    description: props.transaction.description,
    transaction_date: props.transaction.transaction_date,
    status: props.transaction.status,
    entries: props.transaction.entries.map((entry) => ({
        id: entry.id,
        account_id: entry.account_id,
        account: entry.account,
        description: entry.description,
        debit_amount: Number(entry.debit_amount) || 0,
        credit_amount: Number(entry.credit_amount) || 0,
        third_party_type: entry.third_party_type || null,
        third_party_id: entry.third_party_id || null,
    })),
});

// UI state
const isUnsavedChanges = ref(false);
const canEdit = computed(() => props.transaction.status === 'borrador');

// Computed properties
const activeAccounts = computed(() => {
    return props.accounts.filter((account) => account.is_active);
});

const totalDebits = computed(() => {
    return form.entries.reduce((sum, entry) => sum + (Number(entry.debit_amount) || 0), 0);
});

const totalCredits = computed(() => {
    return form.entries.reduce((sum, entry) => sum + (Number(entry.credit_amount) || 0), 0);
});

const isBalanced = computed(() => {
    return Math.abs(totalDebits.value - totalCredits.value) < 0.01;
});

const balanceDifference = computed(() => {
    return totalDebits.value - totalCredits.value;
});

const canSubmit = computed(() => {
    if (!canEdit.value || !isBalanced.value || form.entries.length < 2) {
        return false;
    }

    // Check all entries have account and amount
    const hasBasicValidation = form.entries.every((entry) => entry.account_id && (entry.debit_amount > 0 || entry.credit_amount > 0));

    if (!hasBasicValidation) {
        return false;
    }

    // If trying to post (status = 'contabilizado'), check third party requirements
    if (form.status === 'contabilizado') {
        const allThirdPartiesValid = form.entries.every((entry) => {
            if (!entry.account_id) return false;
            const requiresThirdParty = getAccountRequiresThirdParty(entry.account_id);
            return !requiresThirdParty || entry.third_party_id;
        });

        return allThirdPartiesValid;
    }

    return true;
});

// Methods
const addEntry = () => {
    if (!canEdit.value) return;

    form.entries.push({
        account_id: null,
        description: '',
        debit_amount: 0,
        credit_amount: 0,
        third_party_type: null,
        third_party_id: null,
    });
};

const removeEntry = (index: number) => {
    if (!canEdit.value || form.entries.length <= 2) return;

    form.entries.splice(index, 1);
};

const getAccountDisplay = (accountId: number) => {
    const account = activeAccounts.value.find((acc) => acc.id === accountId);
    return account ? `${account.code} - ${account.name}` : '';
};

const onAccountChange = (index: number, accountId: number) => {
    const account = activeAccounts.value.find((acc) => acc.id === accountId);
    if (account) {
        form.entries[index].account = {
            code: account.code,
            name: account.name,
            requires_third_party: account.requires_third_party,
        };

        // If account requires third party, keep existing type or set to apartment by default
        if (account.requires_third_party) {
            if (!form.entries[index].third_party_type) {
                form.entries[index].third_party_type = 'apartment';
            }
            // Clear third_party_id to force user selection if changing type
            if (!form.entries[index].third_party_id) {
                form.entries[index].third_party_id = null;
            }
        } else {
            // Clear third party fields if account doesn't require it
            form.entries[index].third_party_type = null;
            form.entries[index].third_party_id = null;
        }
    }
};

const getAccountRequiresThirdParty = (accountId: number | null): boolean => {
    if (!accountId) return false;
    const account = activeAccounts.value.find((acc) => acc.id === accountId);
    return account?.requires_third_party || false;
};

const clearAmount = (index: number, type: 'debit' | 'credit') => {
    if (!canEdit.value) return;

    if (type === 'debit') {
        form.entries[index].credit_amount = 0;
    } else {
        form.entries[index].debit_amount = 0;
    }
};

const submit = () => {
    form.put(route('accounting.transactions.update', props.transaction.id), {
        onSuccess: () => {
            isUnsavedChanges.value = false;
        },
    });
};

const deleteTransaction = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta transacción? Esta acción no se puede deshacer.')) {
        form.delete(route('accounting.transactions.destroy', props.transaction.id));
    }
};

const resetForm = () => {
    form.reset();
    isUnsavedChanges.value = false;
};

// Watch for third_party_type changes to reset third_party_id
watch(
    () => form.entries.map(e => e.third_party_type),
    (newTypes, oldTypes) => {
        newTypes.forEach((newType, index) => {
            if (oldTypes && newType !== oldTypes[index]) {
                form.entries[index].third_party_id = null;
            }
        });
    },
);

// Watch for form changes
watch(
    form,
    () => {
        isUnsavedChanges.value = true;
    },
    { deep: true },
);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Transacciones', href: '/accounting/transactions' },
    { title: `Editar: ${props.transaction.reference}`, href: `/accounting/transactions/${props.transaction.id}/edit` },
];
</script>

<template>

    <Head :title="`Editarss: ${transaction}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar Transacción</h1>
                    <p class="text-muted-foreground">{{ transaction.reference }} - {{ transaction.status }}</p>
                    <div v-if="!canEdit" class="inline-block rounded bg-orange-50 px-2 py-1 text-sm text-orange-600">
                        ⚠️ Solo se pueden editar transacciones en estado "Borrador"
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="`/accounting/transactions/${transaction.id}`">
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
                <!-- Transaction Details -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Información de la Transacción
                        </CardTitle>
                        <CardDescription> Datos básicos del asiento contable </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Reference and Date -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="reference">Referencia *</Label>
                                <Input id="reference" v-model="form.reference" placeholder="Ej: ASI-001-2024"
                                    class="font-mono" :disabled="!canEdit"
                                    :class="{ 'border-red-500': form.errors.reference }" />
                                <p class="text-xs text-muted-foreground">Código único para identificar la transacción
                                </p>
                                <p v-if="form.errors.reference" class="text-sm text-red-600">
                                    {{ form.errors.reference }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="transaction_date">Fecha de Transacción *</Label>
                                <Input id="transaction_date" v-model="form.transaction_date" type="date"
                                    :disabled="!canEdit" :class="{ 'border-red-500': form.errors.transaction_date }" />
                                <p v-if="form.errors.transaction_date" class="text-sm text-red-600">
                                    {{ form.errors.transaction_date }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label for="status">Estado *</Label>
                            <Select v-model="form.status" :disabled="!canEdit">
                                <SelectTrigger id="status" :class="{ 'border-red-500': form.errors.status }">
                                    <SelectValue placeholder="Seleccionar estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="borrador">
                                        <div class="flex items-center gap-2">
                                            <span>Borrador</span>
                                            <span class="text-xs text-muted-foreground">- Transacción editable</span>
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="contabilizado">
                                        <div class="flex items-center gap-2">
                                            <span>Contabilizado</span>
                                            <span class="text-xs text-muted-foreground">- Transacción confirmada</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">El estado "Contabilizado" confirma y bloquea la
                                transacción permanentemente</p>
                            <p v-if="form.errors.status" class="text-sm text-red-600">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Descripción *</Label>
                            <Textarea id="description" v-model="form.description"
                                placeholder="Descripción detallada de la transacción..." :disabled="!canEdit"
                                :class="{ 'border-red-500': form.errors.description }" rows="3" />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Transaction Entries -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Calculator class="h-5 w-5" />
                                    Asientos Contables
                                </CardTitle>
                                <CardDescription> Detalle de débitos y créditos (debe estar balanceado)
                                </CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" :disabled="!canEdit" @click="addEntry"
                                class="gap-2">
                                <Plus class="h-4 w-4" />
                                Agregar Línea
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Balance Summary -->
                        <div class="mb-6 grid grid-cols-3 gap-4 rounded-lg bg-muted p-4">
                            <div class="text-center">
                                <p class="text-sm text-muted-foreground">Total Débitos</p>
                                <p class="text-xl font-bold text-red-600">${{ totalDebits.toLocaleString() }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-muted-foreground">Total Créditos</p>
                                <p class="text-xl font-bold text-green-600">${{ totalCredits.toLocaleString() }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-muted-foreground">Diferencia</p>
                                <p :class="['text-xl font-bold', isBalanced ? 'text-green-600' : 'text-red-600']">
                                    ${{ Math.abs(balanceDifference).toLocaleString() }}
                                </p>
                                <p v-if="!isBalanced" class="text-xs text-red-600">No balanceado</p>
                                <p v-else class="text-xs text-green-600">✓ Balanceado</p>
                            </div>
                        </div>

                        <!-- Entries Table -->
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[250px]">Cuenta</TableHead>
                                        <TableHead class="w-[120px]">Tercero</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead class="w-[130px] text-right">Débito</TableHead>
                                        <TableHead class="w-[130px] text-right">Crédito</TableHead>
                                        <TableHead class="w-[80px]">Acción</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(entry, index) in form.entries" :key="index">
                                        <TableCell>
                                            <AccountCombobox :accounts="activeAccounts" :model-value="entry.account_id"
                                                :disabled="!canEdit" placeholder="Seleccionar cuenta..."
                                                @update:model-value="
                                                    (value) => {
                                                        entry.account_id = value;
                                                        onAccountChange(index, value);
                                                    }
                                                " />
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="getAccountRequiresThirdParty(entry.account_id)"
                                                class="flex gap-1">
                                                <Select v-model="entry.third_party_type" :disabled="!canEdit"
                                                    class="w-24">
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="apartment">Apto</SelectItem>
                                                        <SelectItem value="provider">Prov</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <Select v-model="entry.third_party_id" :disabled="!canEdit"
                                                    class="flex-1">
                                                    <SelectTrigger :class="{
                                                        'border-red-500': !entry.third_party_id,
                                                    }">
                                                        <SelectValue
                                                            :placeholder="entry.third_party_type === 'provider' ? 'Proveedor...' : 'Apto...'" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <template v-if="entry.third_party_type === 'apartment'">
                                                            <SelectItem v-for="apt in apartments" :key="apt.id"
                                                                :value="apt.id">
                                                                {{ apt.number }}
                                                            </SelectItem>
                                                        </template>
                                                        <template v-else-if="entry.third_party_type === 'provider'">
                                                            <SelectItem v-for="provider in providers" :key="provider.id"
                                                                :value="provider.id">
                                                                {{ provider.name }}
                                                            </SelectItem>
                                                        </template>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">-</span>
                                        </TableCell>
                                        <TableCell>
                                            <Input v-model="entry.description" placeholder="Descripción del movimiento"
                                                :disabled="!canEdit" class="w-full" />
                                        </TableCell>
                                        <TableCell>
                                            <Input v-model.number="entry.debit_amount" type="number" step="0.01" min="0"
                                                placeholder="0.00" :disabled="!canEdit" class="text-right font-mono"
                                                @focus="clearAmount(index, 'debit')" />
                                        </TableCell>
                                        <TableCell>
                                            <Input v-model.number="entry.credit_amount" type="number" step="0.01"
                                                min="0" placeholder="0.00" :disabled="!canEdit"
                                                class="text-right font-mono" @focus="clearAmount(index, 'credit')" />
                                        </TableCell>
                                        <TableCell>
                                            <Button type="button" variant="ghost" size="sm"
                                                :disabled="!canEdit || form.entries.length <= 2"
                                                @click="removeEntry(index)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <p v-if="form.errors.entries" class="mt-2 text-sm text-red-600">
                            {{ form.errors.entries }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex items-center justify-between">
                    <Button type="button" variant="destructive" :disabled="!canEdit" @click="deleteTransaction"
                        class="gap-2">
                        <Trash2 class="h-4 w-4" />
                        Eliminar Transacción
                    </Button>

                    <div class="flex items-center gap-3">
                        <Button type="button" variant="outline" @click="resetForm" :disabled="!canEdit"> Descartar
                            Cambios </Button>

                        <div v-if="!isBalanced && canEdit" class="text-sm text-red-600">El asiento debe estar balanceado
                            para continuar
                        </div>

                        <Button type="submit" :disabled="form.processing || !canSubmit" class="gap-2">
                            <Save class="h-4 w-4" />
                            {{
                                form.processing ? 'Guardando...' : form.status === 'contabilizado' ?
                                    'Guardar y Contabilizar' : 'Guardar Cambios' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
