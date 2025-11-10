<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import ValidationErrors from '@/components/ValidationErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calculator, ChevronDown, DollarSign, Lightbulb, Save, Search, TrendingDown, TrendingUp } from 'lucide-vue-next';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

interface Account {
    id: number;
    code: string;
    name: string;
    account_type: string;
}

interface Budget {
    id: number;
    name: string;
    fiscal_year: number;
    status: 'Draft' | 'Active' | 'Closed';
}

interface BudgetItem {
    id: number;
    account_id: number;
    account: Account;
    category: 'income' | 'expense';
    expense_type?: 'fixed' | 'variable' | 'special_fund';
    budgeted_amount: number;
    notes?: string;
    jan_amount: number;
    feb_amount: number;
    mar_amount: number;
    apr_amount: number;
    may_amount: number;
    jun_amount: number;
    jul_amount: number;
    aug_amount: number;
    sep_amount: number;
    oct_amount: number;
    nov_amount: number;
    dec_amount: number;
}

interface HistoricalData {
    total_amount: number;
    monthly_distribution: Record<number, number>;
    suggestions: {
        copy_previous_year: number;
        with_inflation: number;
        inflation_rate: number;
    };
    trend: 'stable' | 'increasing' | 'decreasing';
    has_data: boolean;
}

interface Props {
    budget: Budget;
    item: BudgetItem;
    usedAccountIds: number[];
    historicalData: HistoricalData;
}

const props = defineProps<Props>();

const page = usePage();
const errors = computed(() => page.props.errors || {});

// Account search state
const accountSearch = ref('');
const searchResults = ref<Account[]>([]);
const isSearching = ref(false);
const showDropdown = ref(false);
const selectedAccount = ref<Account | null>(props.item.account);

interface FormData {
    account_id: string;
    category: 'income' | 'expense';
    expense_type?: string;
    budgeted_amount: number;
    notes: string;
    use_monthly_distribution: boolean;
    monthly_distribution: Record<number, number>;
}

const hasMonthlyDistribution = props.item.jan_amount > 0 || props.item.feb_amount > 0 ||
    props.item.mar_amount > 0 || props.item.apr_amount > 0 || props.item.may_amount > 0 ||
    props.item.jun_amount > 0 || props.item.jul_amount > 0 || props.item.aug_amount > 0 ||
    props.item.sep_amount > 0 || props.item.oct_amount > 0 || props.item.nov_amount > 0 ||
    props.item.dec_amount > 0;

const form = useForm<FormData>({
    account_id: props.item.account_id.toString(),
    category: props.item.category,
    expense_type: props.item.expense_type || '',
    budgeted_amount: props.item.budgeted_amount,
    notes: props.item.notes || '',
    use_monthly_distribution: hasMonthlyDistribution,
    monthly_distribution: {
        1: props.item.jan_amount,
        2: props.item.feb_amount,
        3: props.item.mar_amount,
        4: props.item.apr_amount,
        5: props.item.may_amount,
        6: props.item.jun_amount,
        7: props.item.jul_amount,
        8: props.item.aug_amount,
        9: props.item.sep_amount,
        10: props.item.oct_amount,
        11: props.item.nov_amount,
        12: props.item.dec_amount,
    },
});

const monthlyTotal = computed(() => {
    return Object.values(form.monthly_distribution).reduce((sum, amount) => sum + (amount || 0), 0);
});

const distributionValid = computed(() => {
    if (!form.use_monthly_distribution) return true;
    return Math.abs(monthlyTotal.value - form.budgeted_amount) < 0.01;
});

const monthNames = {
    1: 'Enero',
    2: 'Febrero',
    3: 'Marzo',
    4: 'Abril',
    5: 'Mayo',
    6: 'Junio',
    7: 'Julio',
    8: 'Agosto',
    9: 'Septiembre',
    10: 'Octubre',
    11: 'Noviembre',
    12: 'Diciembre',
};

const distributeEqually = () => {
    const monthlyAmount = form.budgeted_amount / 12;
    for (let month = 1; month <= 12; month++) {
        form.monthly_distribution[month] = parseFloat(monthlyAmount.toFixed(2));
    }
};

const toggleMonthlyDistribution = () => {
    if (form.use_monthly_distribution) {
        distributeEqually();
    } else {
        for (let month = 1; month <= 12; month++) {
            form.monthly_distribution[month] = 0;
        }
    }
};

// Forecast functions
const applyCopyPreviousYear = () => {
    form.budgeted_amount = props.historicalData.suggestions.copy_previous_year;
};

const applyWithInflation = () => {
    form.budgeted_amount = props.historicalData.suggestions.with_inflation;
};

const applyHistoricalDistribution = () => {
    if (!form.use_monthly_distribution) {
        form.use_monthly_distribution = true;
    }

    form.budgeted_amount = props.historicalData.total_amount;

    for (let month = 1; month <= 12; month++) {
        form.monthly_distribution[month] = props.historicalData.monthly_distribution[month] || 0;
    }
};

const trendIcon = computed(() => {
    if (props.historicalData.trend === 'increasing') return TrendingUp;
    if (props.historicalData.trend === 'decreasing') return TrendingDown;
    return null;
});

const trendLabel = computed(() => {
    if (props.historicalData.trend === 'increasing') return 'Tendencia al alza';
    if (props.historicalData.trend === 'decreasing') return 'Tendencia a la baja';
    return 'Tendencia estable';
});

// Account search functions
const searchAccounts = async (query: string) => {
    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get('/accounting/accounts/search', {
            params: {
                q: query,
                type: form.category,
                exclude_ids: props.usedAccountIds,
            },
        });
        searchResults.value = response.data.accounts;
        showDropdown.value = true;
    } catch (error) {
        console.error('Error searching accounts:', error);
    } finally {
        isSearching.value = false;
    }
};

const selectAccount = (account: Account) => {
    selectedAccount.value = account;
    form.account_id = account.id.toString();
    accountSearch.value = `${account.code} - ${account.name}`;
    showDropdown.value = false;
};

// Watch for search input changes
watch(accountSearch, (newValue) => {
    if (newValue !== `${selectedAccount.value?.code} - ${selectedAccount.value?.name}`) {
        searchAccounts(newValue);
    }
});

// Initialize search with current account
if (props.item.account) {
    accountSearch.value = `${props.item.account.code} - ${props.item.account.name}`;
}

const submit = () => {
    const data = {
        account_id: form.account_id,
        category: form.category,
        expense_type: form.expense_type || undefined,
        budgeted_amount: form.budgeted_amount,
        notes: form.notes || undefined,
        ...(form.use_monthly_distribution ? { monthly_distribution: form.monthly_distribution } : {}),
    };

    form.put(route('accounting.budgets.items.update', [props.budget.id, props.item.id]), {
        data,
    });
};

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Presupuestos', href: '/accounting/budgets' },
    { title: props.budget.name, href: `/accounting/budgets/${props.budget.id}` },
    { title: 'Editar Partida', href: '#' },
];
</script>

<template>
    <Head title="Editar Partida Presupuestal" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar Partida Presupuestal</h1>
                    <p class="text-muted-foreground">{{ budget.name }} - {{ budget.fiscal_year }}</p>
                </div>
                <Link :href="`/accounting/budgets/${budget.id}`">
                    <Button variant="outline" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Volver al Presupuesto
                    </Button>
                </Link>
            </div>

            <ValidationErrors :errors="errors" />

            <form @submit.prevent="submit" class="space-y-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Cuenta Contable</CardTitle>
                                <CardDescription>Seleccione la cuenta y categoría para esta partida</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="category">Categoría</Label>
                                    <Select v-model="form.category" @update:model-value="form.account_id = ''">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar categoría" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="income">Ingresos</SelectItem>
                                            <SelectItem value="expense">Gastos</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="relative space-y-2">
                                    <Label for="account_id">Cuenta</Label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            id="account_search"
                                            v-model="accountSearch"
                                            type="text"
                                            placeholder="Buscar por código o nombre..."
                                            class="pl-10"
                                            @focus="showDropdown = searchResults.length > 0"
                                            @blur="setTimeout(() => showDropdown = false, 200)"
                                        />
                                    </div>

                                    <!-- Search Results Dropdown -->
                                    <div
                                        v-if="showDropdown && (searchResults.length > 0 || isSearching)"
                                        class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                                    >
                                        <div v-if="isSearching" class="flex items-center justify-center p-4">
                                            <span class="text-sm text-muted-foreground">Buscando...</span>
                                        </div>
                                        <button
                                            v-for="account in searchResults"
                                            :key="account.id"
                                            type="button"
                                            class="flex w-full cursor-pointer flex-col rounded-sm px-3 py-2 text-left hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                                            @click="selectAccount(account)"
                                        >
                                            <span class="font-mono text-xs text-muted-foreground">{{ account.code }}</span>
                                            <span class="text-sm">{{ account.name }}</span>
                                        </button>
                                        <div v-if="!isSearching && searchResults.length === 0" class="p-4 text-center">
                                            <span class="text-sm text-muted-foreground">No se encontraron cuentas</span>
                                        </div>
                                    </div>

                                    <!-- Selected Account Display -->
                                    <div v-if="selectedAccount" class="mt-2 rounded-md border bg-muted/50 p-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex flex-col">
                                                <span class="font-mono text-xs text-muted-foreground">{{ selectedAccount.code }}</span>
                                                <span class="text-sm font-medium">{{ selectedAccount.name }}</span>
                                            </div>
                                            <Badge variant="secondary">Seleccionada</Badge>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="form.category === 'expense'" class="space-y-2">
                                    <Label for="expense_type">Tipo de Gasto (opcional)</Label>
                                    <Select v-model="form.expense_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="fixed">Fijo</SelectItem>
                                            <SelectItem value="variable">Variable</SelectItem>
                                            <SelectItem value="special_fund">Fondo Especial</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Monto Presupuestado</CardTitle>
                                <CardDescription>Defina el monto anual para esta partida</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="budgeted_amount">Monto Anual</Label>
                                    <Input
                                        id="budgeted_amount"
                                        v-model.number="form.budgeted_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="text-right"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="notes">Notas (opcional)</Label>
                                    <Textarea
                                        id="notes"
                                        v-model="form.notes"
                                        placeholder="Descripción adicional de la partida..."
                                        class="min-h-[80px]"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Forecast Suggestions Card -->
                        <Card v-if="historicalData.has_data" class="border-blue-200 bg-blue-50/50">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 text-blue-900">
                                    <Lightbulb class="h-5 w-5" />
                                    Sugerencias Basadas en Datos Históricos
                                </CardTitle>
                                <CardDescription>
                                    Datos del año {{ budget.fiscal_year - 1 }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Historical Amount Display -->
                                <div class="rounded-lg border bg-white p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <Label class="text-xs text-muted-foreground">Ejecutado Año Anterior</Label>
                                            <p class="text-2xl font-bold text-blue-600">
                                                {{ formatCurrency(historicalData.total_amount) }}
                                            </p>
                                        </div>
                                        <div v-if="trendIcon" class="flex items-center gap-1 text-sm">
                                            <component :is="trendIcon" class="h-4 w-4" />
                                            <span>{{ trendLabel }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Action Buttons -->
                                <div class="space-y-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="w-full justify-start gap-2"
                                        @click="applyCopyPreviousYear"
                                    >
                                        <Calculator class="h-4 w-4" />
                                        <span class="flex-1 text-left">Copiar monto del año anterior</span>
                                        <Badge variant="secondary" class="text-xs">
                                            {{ formatCurrency(historicalData.suggestions.copy_previous_year) }}
                                        </Badge>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="w-full justify-start gap-2"
                                        @click="applyWithInflation"
                                    >
                                        <TrendingUp class="h-4 w-4" />
                                        <span class="flex-1 text-left">
                                            Aplicar inflación (+{{ historicalData.suggestions.inflation_rate }}%)
                                        </span>
                                        <Badge variant="secondary" class="text-xs">
                                            {{ formatCurrency(historicalData.suggestions.with_inflation) }}
                                        </Badge>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="w-full justify-start gap-2"
                                        @click="applyHistoricalDistribution"
                                    >
                                        <Calendar class="h-4 w-4" />
                                        <span class="flex-1 text-left">Copiar distribución mensual histórica</span>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Distribución Mensual</CardTitle>
                                        <CardDescription>Configure la distribución del presupuesto por meses</CardDescription>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input
                                            id="use_monthly"
                                            v-model="form.use_monthly_distribution"
                                            type="checkbox"
                                            class="rounded"
                                            @change="toggleMonthlyDistribution"
                                        />
                                        <Label for="use_monthly">Usar distribución manual</Label>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent v-if="form.use_monthly_distribution" class="space-y-4">
                                <div class="flex justify-end">
                                    <Button type="button" variant="outline" size="sm" @click="distributeEqually" class="gap-2">
                                        <Calculator class="h-4 w-4" />
                                        Distribuir Igualmente
                                    </Button>
                                </div>

                                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                                    <div v-for="month in 12" :key="month" class="space-y-1">
                                        <Label :for="`month-${month}`" class="text-xs">
                                            {{ monthNames[month] }}
                                        </Label>
                                        <Input
                                            :id="`month-${month}`"
                                            v-model.number="form.monthly_distribution[month]"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="text-right text-xs"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border-t pt-4">
                                    <span class="text-sm text-muted-foreground">Total distribución:</span>
                                    <div class="flex items-center gap-2">
                                        <span :class="['font-mono font-semibold', distributionValid ? 'text-green-600' : 'text-red-600']">
                                            {{ formatCurrency(monthlyTotal) }}
                                        </span>
                                        <Badge v-if="!distributionValid" variant="destructive" class="text-xs"> No coincide </Badge>
                                        <Badge v-else variant="default" class="bg-green-100 text-xs text-green-800"> Válido </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <DollarSign class="h-5 w-5" />
                                    Resumen
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-3">
                                    <div>
                                        <Label class="text-sm text-muted-foreground">Presupuesto</Label>
                                        <p class="text-lg font-bold">{{ budget.name }}</p>
                                    </div>

                                    <div>
                                        <Label class="text-sm text-muted-foreground">Año Fiscal</Label>
                                        <p class="text-sm">{{ budget.fiscal_year }}</p>
                                    </div>

                                    <div>
                                        <Label class="text-sm text-muted-foreground">Estado</Label>
                                        <Badge :variant="budget.status === 'Draft' ? 'secondary' : 'default'">
                                            {{ budget.status === 'Draft' ? 'Borrador' : budget.status }}
                                        </Badge>
                                    </div>
                                </div>

                                <div v-if="form.budgeted_amount > 0" class="border-t pt-3">
                                    <Label class="text-sm text-muted-foreground">Monto de esta partida</Label>
                                    <p class="text-xl font-semibold text-blue-600">
                                        {{ formatCurrency(form.budgeted_amount) }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Acciones</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <Button
                                    type="submit"
                                    :disabled="
                                        form.processing ||
                                        !form.account_id ||
                                        !form.budgeted_amount ||
                                        (form.use_monthly_distribution && !distributionValid)
                                    "
                                    class="w-full gap-2"
                                >
                                    <Save class="h-4 w-4" />
                                    Actualizar Partida
                                </Button>

                                <Link :href="`/accounting/budgets/${budget.id}`">
                                    <Button variant="outline" class="w-full gap-2">
                                        <ArrowLeft class="h-4 w-4" />
                                        Cancelar
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
