<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle, Edit, Eye, Plus, Settings, Trash2, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { useToast } from '@/composables/useToast';

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
];

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
    categories: ExpenseCategory[];
}>();

// Get page data for errors and flash messages
const page = usePage();
const { error } = useToast();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const deleteCategory = (categoryId: number) => {
    const category = props.categories.find(c => c.id === categoryId);
    
    if (category && category.expenses_count > 0) {
        error('No se puede eliminar una categoría que tiene gastos asociados');
        return;
    }

    if (confirm('¿Está seguro de que desea eliminar esta categoría?')) {
        router.delete(`/expense-categories/${categoryId}`);
    }
};
</script>

<template>
    <Head title="Categorías de Gastos" />

    <AppLayout title="Categorías de Gastos" :breadcrumbs="breadcrumbs">
        <ValidationErrors :errors="errors" />
        
        <!-- Success Alert -->
        <Alert v-if="flashSuccess" class="mb-6 border-green-200 bg-green-50">
            <CheckCircle class="h-4 w-4 text-green-600" />
            <AlertDescription class="text-green-800">
                {{ flashSuccess }}
            </AlertDescription>
        </Alert>

        <!-- Error Alert -->
        <Alert v-if="flashError" class="mb-6 border-red-200 bg-red-50">
            <XCircle class="h-4 w-4 text-red-600" />
            <AlertDescription class="text-red-800">
                {{ flashError }}
            </AlertDescription>
        </Alert>

        <div class="space-y-4">
            <!-- Header with actions -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h2 class="text-2xl font-semibold tracking-tight">Categorías de Gastos</h2>
                    <p class="text-sm text-muted-foreground">
                        Gestiona las categorías para clasificar los gastos
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <Button asChild size="sm">
                        <Link href="/expense-categories/create">
                            <Plus class="mr-2 h-4 w-4" />
                            Nueva Categoría
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Categories Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Categorías Configuradas</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="categories.length === 0" class="text-center py-8 text-muted-foreground">
                        No hay categorías configuradas. 
                        <Link href="/expense-categories/create" class="text-blue-600 hover:underline">
                            Crear la primera categoría
                        </Link>
                    </div>
                    
                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Configuración</TableHead>
                                <TableHead>Gastos</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in categories" :key="category.id">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div 
                                            class="w-4 h-4 rounded-full"
                                            :style="{ backgroundColor: category.color }"
                                        ></div>
                                        <div>
                                            <div class="font-medium">{{ category.name }}</div>
                                            <div v-if="category.requires_approval" class="text-xs text-amber-600">
                                                Requiere aprobación
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <div class="max-w-xs">
                                        <p class="text-sm text-muted-foreground truncate">
                                            {{ category.description || 'Sin descripción' }}
                                        </p>
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <div class="space-y-1 text-xs">
                                        <div v-if="category.default_debit_account">
                                            <span class="font-medium">Débito:</span>
                                            {{ category.default_debit_account.code }}
                                        </div>
                                        <div v-if="category.default_credit_account">
                                            <span class="font-medium">Crédito:</span>
                                            {{ category.default_credit_account.code }}
                                        </div>
                                        <div v-if="category.budget_account">
                                            <span class="font-medium">Presupuesto:</span>
                                            {{ category.budget_account.code }}
                                        </div>
                                        <div v-if="!category.default_debit_account && !category.default_credit_account" class="text-muted-foreground">
                                            Sin configurar
                                        </div>
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <div class="text-center">
                                        <span class="font-medium">{{ category.expenses_count }}</span>
                                        <div class="text-xs text-muted-foreground">gastos</div>
                                    </div>
                                </TableCell>
                                
                                <TableCell>
                                    <Badge 
                                        :class="category.is_active 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ category.is_active ? 'Activa' : 'Inactiva' }}
                                    </Badge>
                                </TableCell>
                                
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Button asChild variant="ghost" size="sm">
                                            <Link :href="`/expense-categories/${category.id}`">
                                                <Eye class="w-4 h-4" />
                                            </Link>
                                        </Button>
                                        
                                        <Button asChild variant="ghost" size="sm">
                                            <Link :href="`/expense-categories/${category.id}/edit`">
                                                <Edit class="w-4 h-4" />
                                            </Link>
                                        </Button>
                                        
                                        <Button 
                                            variant="ghost" 
                                            size="sm"
                                            @click="deleteCategory(category.id)"
                                            :disabled="category.expenses_count > 0"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Help Text -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-start gap-4">
                        <Settings class="w-5 h-5 text-muted-foreground mt-0.5" />
                        <div>
                            <h4 class="font-medium mb-2">Configuración de Categorías</h4>
                            <div class="text-sm text-muted-foreground space-y-1">
                                <p>• Las categorías ayudan a clasificar y organizar los gastos del conjunto.</p>
                                <p>• Configura cuentas contables predeterminadas para automatizar los asientos.</p>
                                <p>• Las categorías con "Requiere aprobación" necesitarán autorización de administradores.</p>
                                <p>• No puedes eliminar categorías que tienen gastos asociados.</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>