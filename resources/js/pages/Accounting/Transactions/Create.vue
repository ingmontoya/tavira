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

interface FormData {
    reference: string;
    description: string;
    transaction_date: string;
    apartment_id: number | null;
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

const props = defineProps<{
    accounts: Account[];
    apartments: Apartment[];
    duplicateFrom?: {
        description: string;
        reference_type: string;
        entries: TransactionEntry[];
    };
}>();

// Form state
const form = useForm<FormData>({
    reference: '',
    description: props.duplicateFrom?.description || '',
    transaction_date: new Date().toISOString().split('T')[0],
    apartment_id: null,
    entries: props.duplicateFrom?.entries.map((entry) => ({
        account_id: entry.account_id,
        account: entry.account,
        description: entry.description,
        debit_amount: entry.debit_amount,
        credit_amount: entry.credit_amount,
        third_party_type: entry.third_party_type || null,
        third_party_id: entry.third_party_id || null,
    })) || [
        { account_id: null, description: '', debit_amount: 0, credit_amount: 0, third_party_type: null, third_party_id: null },
        { account_id: null, description: '', debit_amount: 0, credit_amount: 0, third_party_type: null, third_party_id: null },
    ],
});

// UI state
const isUnsavedChanges = ref(false);
const apartmentSearchQuery = ref('');

// Computed properties
const activeAccounts = computed(() => {
    return props.accounts.filter((account) => account.is_active);
});

const filteredApartments = computed(() => {
    if (!apartmentSearchQuery.value) {
        return props.apartments;
    }

    const query = apartmentSearchQuery.value.toLowerCase();
    return props.apartments.filter((apartment) => {
        return apartment.number.toLowerCase().includes(query);
    });
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
        isBalanced.value &&
        form.entries.length >= 2 &&
        form.entries.every((entry) => entry.account_id && (entry.debit_amount > 0 || entry.credit_amount > 0))
    );
});

// Methods
const addEntry = () => {
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
    if (form.entries.length > 2) {
        form.entries.splice(index, 1);
    }
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

        // If account requires third party, set type to apartment by default
        if (account.requires_third_party) {
            form.entries[index].third_party_type = 'apartment';
            // Clear third_party_id to force user selection
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

const clearOppositeAmount = (index: number, type: 'debit' | 'credit') => {
    // Only clear the opposite field if the current field has a value
    if (type === 'debit' && form.entries[index].debit_amount > 0) {
        form.entries[index].credit_amount = 0;
    } else if (type === 'credit' && form.entries[index].credit_amount > 0) {
        form.entries[index].debit_amount = 0;
    }
};

const submit = () => {
    form.post(route('accounting.transactions.store'), {
        onSuccess: () => {
            isUnsavedChanges.value = false;
        },
    });
};

const resetForm = () => {
    form.reset();
    form.entries = [
        { account_id: null, description: '', debit_amount: 0, credit_amount: 0, third_party_type: null, third_party_id: null },
        { account_id: null, description: '', debit_amount: 0, credit_amount: 0, third_party_type: null, third_party_id: null },
    ];
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
    { title: 'Nueva Transacción', href: '/accounting/transactions/create' },
];
</script>

<template>
    <Head title="Nueva Transacción" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Nueva Transacción</h1>
                    <p class="text-muted-foreground">Crear un nuevo asiento contable</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/accounting/transactions">
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
                                :class="{ 'border-red-500': form.errors.description }"
                                rows="3"
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Apartment (optional) -->
                        <div class="space-y-2">
                            <Label for="apartment_id">Apartamento (Opcional)</Label>
                            <Select v-model="form.apartment_id">
                                <SelectTrigger id="apartment_id">
                                    <SelectValue placeholder="Seleccionar apartamento (opcional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <div class="p-2">
                                        <Input v-model="apartmentSearchQuery" placeholder="Buscar apartamento..." class="mb-2" @click.stop />
                                    </div>
                                    <SelectItem :value="null">Ninguno</SelectItem>
                                    <SelectItem v-for="apartment in filteredApartments" :key="apartment.id" :value="apartment.id">
                                        {{ apartment.number }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">Asocia esta transacción con un apartamento específico</p>
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
                            <Button type="button" variant="outline" size="sm" @click="addEntry" class="gap-2">
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
                                            <AccountCombobox
                                                :accounts="activeAccounts"
                                                :model-value="entry.account_id"
                                                placeholder="Seleccionar cuenta..."
                                                @update:model-value="
                                                    (value) => {
                                                        entry.account_id = value;
                                                        onAccountChange(index, value);
                                                    }
                                                "
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Select v-if="getAccountRequiresThirdParty(entry.account_id)" v-model="entry.third_party_id">
                                                <SelectTrigger
                                                    :class="{
                                                        'border-red-500': getAccountRequiresThirdParty(entry.account_id) && !entry.third_party_id,
                                                    }"
                                                >
                                                    <SelectValue placeholder="Apto..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="apt in apartments" :key="apt.id" :value="apt.id">
                                                        {{ apt.number }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <span v-else class="text-sm text-muted-foreground">-</span>
                                        </TableCell>
                                        <TableCell>
                                            <Input v-model="entry.description" placeholder="Descripción del movimiento" class="w-full" />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                v-model.number="entry.debit_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                class="text-right font-mono"
                                                @input="clearOppositeAmount(index, 'debit')"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                v-model.number="entry.credit_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                class="text-right font-mono"
                                                @input="clearOppositeAmount(index, 'credit')"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                :disabled="form.entries.length <= 2"
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
                    <Button type="button" variant="outline" @click="resetForm"> Limpiar Formulario </Button>

                    <div class="flex items-center gap-3">
                        <div v-if="!isBalanced" class="text-sm text-red-600">El asiento debe estar balanceado para continuar</div>

                        <Button type="submit" :disabled="form.processing || !canSubmit" class="gap-2">
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Guardar Transacción' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
