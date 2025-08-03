<script setup lang="ts">
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
    };
    description: string;
    debit_amount: number;
    credit_amount: number;
}

interface AccountingTransaction {
    id: number;
    reference: string;
    description: string;
    transaction_date: string;
    status: string;
    entries: TransactionEntry[];
}

interface FormData {
    reference: string;
    description: string;
    transaction_date: string;
    entries: TransactionEntry[];
}

interface Account {
    id: number;
    code: string;
    name: string;
    account_type: string;
    is_active: boolean;
}

const props = defineProps<{
    transaction: AccountingTransaction;
    accounts: Account[];
}>();

// Form state
const form = useForm<FormData>({
    reference: props.transaction.reference,
    description: props.transaction.description,
    transaction_date: props.transaction.transaction_date,
    entries: props.transaction.entries.map((entry) => ({
        id: entry.id,
        account_id: entry.account_id,
        account: entry.account,
        description: entry.description,
        debit_amount: entry.debit_amount,
        credit_amount: entry.credit_amount,
    })),
});

// UI state
const isUnsavedChanges = ref(false);
const canEdit = computed(() => props.transaction.status === 'Draft');

// Computed properties
const activeAccounts = computed(() => {
    return props.accounts.filter((account) => account.is_active);
});

const totalDebits = computed(() => {
    return form.entries.reduce((sum, entry) => sum + (entry.debit_amount || 0), 0);
});

const totalCredits = computed(() => {
    return form.entries.reduce((sum, entry) => sum + (entry.credit_amount || 0), 0);
});

const isBalanced = computed(() => {
    return Math.abs(totalDebits.value - totalCredits.value) < 0.01;
});

const balanceDifference = computed(() => {
    return totalDebits.value - totalCredits.value;
});

const canSubmit = computed(() => {
    return (
        canEdit.value &&
        isBalanced.value &&
        form.entries.length >= 2 &&
        form.entries.every((entry) => entry.account_id && (entry.debit_amount > 0 || entry.credit_amount > 0))
    );
});

// Methods
const addEntry = () => {
    if (!canEdit.value) return;

    form.entries.push({
        account_id: null,
        description: '',
        debit_amount: 0,
        credit_amount: 0,
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
        };
    }
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
    <Head :title="`Editar: ${transaction.reference}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
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
                                <Input
                                    id="reference"
                                    v-model="form.reference"
                                    placeholder="Ej: ASI-001-2024"
                                    class="font-mono"
                                    :disabled="!canEdit"
                                    :class="{ 'border-red-500': form.errors.reference }"
                                />
                                <p class="text-xs text-muted-foreground">Código único para identificar la transacción</p>
                                <p v-if="form.errors.reference" class="text-sm text-red-600">
                                    {{ form.errors.reference }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="transaction_date">Fecha de Transacción *</Label>
                                <Input
                                    id="transaction_date"
                                    v-model="form.transaction_date"
                                    type="date"
                                    :disabled="!canEdit"
                                    :class="{ 'border-red-500': form.errors.transaction_date }"
                                />
                                <p v-if="form.errors.transaction_date" class="text-sm text-red-600">
                                    {{ form.errors.transaction_date }}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Descripción *</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Descripción detallada de la transacción..."
                                :disabled="!canEdit"
                                :class="{ 'border-red-500': form.errors.description }"
                                rows="3"
                            />
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
                                <CardDescription> Detalle de débitos y créditos (debe estar balanceado) </CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" :disabled="!canEdit" @click="addEntry" class="gap-2">
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
                                        <TableHead class="w-[300px]">Cuenta</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead class="w-[150px] text-right">Débito</TableHead>
                                        <TableHead class="w-[150px] text-right">Crédito</TableHead>
                                        <TableHead class="w-[80px]">Acción</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(entry, index) in form.entries" :key="index">
                                        <TableCell>
                                            <Select
                                                :model-value="entry.account_id"
                                                :disabled="!canEdit"
                                                @update:model-value="
                                                    (value) => {
                                                        entry.account_id = value;
                                                        onAccountChange(index, value);
                                                    }
                                                "
                                            >
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Seleccionar cuenta" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="account in activeAccounts" :key="account.id" :value="account.id">
                                                        <div class="flex flex-col">
                                                            <span class="font-mono text-sm">{{ account.code }}</span>
                                                            <span class="text-xs text-muted-foreground">{{ account.name }}</span>
                                                        </div>
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                v-model="entry.description"
                                                placeholder="Descripción del movimiento"
                                                :disabled="!canEdit"
                                                class="w-full"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                v-model.number="entry.debit_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                :disabled="!canEdit"
                                                class="text-right font-mono"
                                                @focus="clearAmount(index, 'debit')"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                v-model.number="entry.credit_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                :disabled="!canEdit"
                                                class="text-right font-mono"
                                                @focus="clearAmount(index, 'credit')"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                :disabled="!canEdit || form.entries.length <= 2"
                                                @click="removeEntry(index)"
                                            >
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
                    <Button type="button" variant="destructive" :disabled="!canEdit" @click="deleteTransaction" class="gap-2">
                        <Trash2 class="h-4 w-4" />
                        Eliminar Transacción
                    </Button>

                    <div class="flex items-center gap-3">
                        <Button type="button" variant="outline" @click="resetForm" :disabled="!canEdit"> Descartar Cambios </Button>

                        <div v-if="!isBalanced && canEdit" class="text-sm text-red-600">El asiento debe estar balanceado para continuar</div>

                        <Button type="submit" :disabled="form.processing || !canSubmit" class="gap-2">
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
