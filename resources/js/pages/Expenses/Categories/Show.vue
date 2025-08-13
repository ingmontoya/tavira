<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Settings } from 'lucide-vue-next';
import { computed } from 'vue';

interface ExpenseCategory {
    id: number;
    name: string;
    description: string;
    color: string;
    icon: string;
    is_active: boolean;
    requires_approval: boolean;
    expenses_count: number;
    default_debit_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
    default_credit_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
    budget_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
}

const props = defineProps<{
    category: ExpenseCategory;
}>();

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Egresos',
        href: '/expenses',
    },
    {
        title: 'Categorías',
        href: '/expense-categories',
    },
    {
        title: props.category.name,
        href: `/expense-categories/${props.category.id}`,
    },
];

// Get page data for errors
const page = usePage();
const errors = computed(() => page.props.errors || {});
</script>

<template>
    <Head :title="`Categoría: ${category.name}`" />

    <AppLayout :title="`Categoría: ${category.name}`" :breadcrumbs="breadcrumbs">
        <ValidationErrors :errors="errors" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with back button and edit action -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button asChild variant="outline" size="sm">
                        <Link href="/expense-categories">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-6 h-6 rounded-full"
                            :style="{ backgroundColor: category.color }"
                        ></div>
                        <h2 class="text-2xl font-semibold tracking-tight">{{ category.name }}</h2>
                        <Badge
                            :class="category.is_active
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-800'"
                        >
                            {{ category.is_active ? 'Activa' : 'Inactiva' }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <Button asChild size="sm">
                        <Link :href="`/expense-categories/${category.id}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Category Details -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Básica</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label class="text-sm font-medium text-muted-foreground">Nombre</Label>
                            <p class="text-sm">{{ category.name }}</p>
                        </div>
                        
                        <div v-if="category.description">
                            <Label class="text-sm font-medium text-muted-foreground">Descripción</Label>
                            <p class="text-sm">{{ category.description }}</p>
                        </div>

                        <div>
                            <Label class="text-sm font-medium text-muted-foreground">Estado</Label>
                            <div class="flex items-center gap-2 mt-1">
                                <Badge
                                    :class="category.is_active
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800'"
                                >
                                    {{ category.is_active ? 'Activa' : 'Inactiva' }}
                                </Badge>
                                <Badge
                                    v-if="category.requires_approval"
                                    class="bg-amber-100 text-amber-800"
                                >
                                    Requiere aprobación
                                </Badge>
                            </div>
                        </div>

                        <div>
                            <Label class="text-sm font-medium text-muted-foreground">Gastos asociados</Label>
                            <p class="text-sm font-medium">{{ category.expenses_count }} gastos</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Accounting Configuration -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración Contable</CardTitle>
                        <CardDescription>
                            Cuentas predeterminadas para esta categoría
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="category.default_debit_account">
                            <Label class="text-sm font-medium text-muted-foreground">Cuenta de Débito</Label>
                            <div class="mt-1">
                                <p class="text-sm font-medium">{{ category.default_debit_account.code }}</p>
                                <p class="text-xs text-muted-foreground">{{ category.default_debit_account.full_name }}</p>
                            </div>
                        </div>

                        <div v-if="category.default_credit_account">
                            <Label class="text-sm font-medium text-muted-foreground">Cuenta de Crédito</Label>
                            <div class="mt-1">
                                <p class="text-sm font-medium">{{ category.default_credit_account.code }}</p>
                                <p class="text-xs text-muted-foreground">{{ category.default_credit_account.full_name }}</p>
                            </div>
                        </div>

                        <div v-if="category.budget_account">
                            <Label class="text-sm font-medium text-muted-foreground">Cuenta de Presupuesto</Label>
                            <div class="mt-1">
                                <p class="text-sm font-medium">{{ category.budget_account.code }}</p>
                                <p class="text-xs text-muted-foreground">{{ category.budget_account.full_name }}</p>
                            </div>
                        </div>

                        <div v-if="!category.default_debit_account && !category.default_credit_account && !category.budget_account">
                            <Alert>
                                <Settings class="h-4 w-4" />
                                <AlertDescription>
                                    Esta categoría no tiene cuentas contables configuradas.
                                    <Link :href="`/expense-categories/${category.id}/edit`" class="text-blue-600 hover:underline">
                                        Configurar cuentas
                                    </Link>
                                </AlertDescription>
                            </Alert>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Help Text -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-start gap-4">
                        <Settings class="w-5 h-5 text-muted-foreground mt-0.5" />
                        <div>
                            <h4 class="font-medium mb-2">Acerca de esta Categoría</h4>
                            <div class="text-sm text-muted-foreground space-y-1">
                                <p>• Esta categoría se utiliza para clasificar gastos del conjunto.</p>
                                <p v-if="category.default_debit_account || category.default_credit_account">
                                    • Los asientos contables se generan automáticamente usando las cuentas configuradas.
                                </p>
                                <p v-if="category.requires_approval">
                                    • Los gastos en esta categoría requieren aprobación de administradores.
                                </p>
                                <p v-if="category.expenses_count > 0">
                                    • No se puede eliminar esta categoría porque tiene {{ category.expenses_count }} gastos asociados.
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>