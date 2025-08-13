<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ArrowLeft, Loader2 } from 'lucide-vue-next'
import { computed } from 'vue';

interface ExpenseCategory {
    id: number;
    name: string;
    code?: string;
    description: string;
    color: string;
    icon: string;
    is_active: boolean;
    requires_approval: boolean;
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
    {
        title: 'Editar',
        href: `/expense-categories/${props.category.id}/edit`,
    },
];

// Get page data for errors
const page = usePage();
const errors = computed(() => page.props.errors || {});

const form = useForm({
  name: props.category.name,
  code: props.category.code || '',
  description: props.category.description || '',
  is_active: props.category.is_active,
})

const submit = () => {
  form.put(route('expense-categories.update', props.category.id))
}
</script>

<template>
    <Head :title="`Editar: ${category.name}`" />

    <AppLayout :title="`Editar: ${category.name}`" :breadcrumbs="breadcrumbs">
        <ValidationErrors :errors="errors" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with back button -->
            <div class="flex items-center gap-4">
                <Button asChild variant="outline" size="sm">
                    <Link :href="`/expense-categories/${category.id}`">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <h2 class="text-2xl font-semibold tracking-tight">Editar Categoría</h2>
            </div>

            <Card>
              <CardHeader>
                <CardTitle>Información de la Categoría</CardTitle>
                <CardDescription>
                  Modifique los detalles de la categoría de gastos
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="space-y-2">
                      <Label for="name">Nombre</Label>
                      <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Ej: Servicios Públicos"
                        :class="{ 'border-red-500': form.errors.name }"
                      />
                      <div v-if="form.errors.name" class="text-sm text-red-500">
                        {{ form.errors.name }}
                      </div>
                    </div>

                    <div class="space-y-2">
                      <Label for="code">Código</Label>
                      <Input
                        id="code"
                        v-model="form.code"
                        type="text"
                        placeholder="Ej: SERV_PUB"
                        :class="{ 'border-red-500': form.errors.code }"
                      />
                      <div v-if="form.errors.code" class="text-sm text-red-500">
                        {{ form.errors.code }}
                      </div>
                    </div>
                  </div>

                  <div class="space-y-2">
                    <Label for="description">Descripción</Label>
                    <Textarea
                      id="description"
                      v-model="form.description"
                      placeholder="Descripción opcional de la categoría"
                      :class="{ 'border-red-500': form.errors.description }"
                    />
                    <div v-if="form.errors.description" class="text-sm text-red-500">
                      {{ form.errors.description }}
                    </div>
                  </div>

                  <div class="flex items-center space-x-2">
                    <input
                      id="is_active"
                      v-model="form.is_active"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <Label for="is_active">Categoría activa</Label>
                  </div>

                  <div class="flex items-center justify-end space-x-4">
                    <Button
                      type="button"
                      variant="outline"
                      @click="$inertia.visit(route('expense-categories.show', category.id))"
                    >
                      Cancelar
                    </Button>
                    <Button
                      type="submit"
                      :disabled="form.processing"
                    >
                      <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                      Actualizar Categoría
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>