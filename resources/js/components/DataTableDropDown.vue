<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { router } from '@inertiajs/vue3';
import { Copy, Edit, Eye, MoreHorizontal, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    resident?: any;
    apartment?: any;
}>();

// Resident actions
const viewResident = () => {
    router.visit(`/residents/${props.resident.id}`);
};

const editResident = () => {
    router.visit(`/residents/${props.resident.id}/edit`);
};

const deleteResident = () => {
    if (confirm('¿Está seguro que desea eliminar este residente?')) {
        router.delete(`/residents/${props.resident.id}`);
    }
};

const copyResidentCode = () => {
    navigator.clipboard.writeText(props.resident.document_number);
};

// Apartment actions
const viewApartment = () => {
    router.visit(`/apartments/${props.apartment.id}`);
};

const editApartment = () => {
    router.visit(`/apartments/${props.apartment.id}/edit`);
};

const deleteApartment = () => {
    if (confirm('¿Está seguro que desea eliminar este apartamento?')) {
        router.delete(`/apartments/${props.apartment.id}`);
    }
};

const copyApartmentNumber = () => {
    navigator.clipboard.writeText(props.apartment.number);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0">
                <span class="sr-only">Open menu</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <!-- Resident actions -->
            <template v-if="resident">
                <DropdownMenuItem @click="copyResidentCode">
                    <Copy class="mr-2 h-4 w-4" />
                    Copiar documento
                </DropdownMenuItem>
                <DropdownMenuItem @click="viewResident">
                    <Eye class="mr-2 h-4 w-4" />
                    Ver residente
                </DropdownMenuItem>
                <DropdownMenuItem @click="editResident">
                    <Edit class="mr-2 h-4 w-4" />
                    Editar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="deleteResident" class="text-red-600">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Eliminar
                </DropdownMenuItem>
            </template>

            <!-- Apartment actions -->
            <template v-if="apartment">
                <DropdownMenuItem @click="copyApartmentNumber">
                    <Copy class="mr-2 h-4 w-4" />
                    Copiar número
                </DropdownMenuItem>
                <DropdownMenuItem @click="viewApartment">
                    <Eye class="mr-2 h-4 w-4" />
                    Ver apartamento
                </DropdownMenuItem>
                <DropdownMenuItem @click="editApartment">
                    <Edit class="mr-2 h-4 w-4" />
                    Editar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="deleteApartment" class="text-red-600">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Eliminar
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
