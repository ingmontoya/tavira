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
import { Edit, Eye, MoreHorizontal, Trash2, Users } from 'lucide-vue-next';

interface Program {
    id: number;
    name: string;
    total_semesters: number;
    status?: string;
    students_count?: number;
}

const props = defineProps<{
    program: Program;
}>();

const viewProgram = () => {
    router.visit(`/programs/${props.program.id}`);
};

const editProgram = () => {
    router.visit(`/programs/${props.program.id}/edit`);
};

const deleteProgram = () => {
    if (confirm('¿Está seguro que desea eliminar este programa?')) {
        router.delete(`/programs/${props.program.id}`);
    }
};

const viewStudents = () => {
    router.visit(`/students?program_id=${props.program.id}`);
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
            <DropdownMenuItem @click="viewProgram">
                <Eye class="mr-2 h-4 w-4" />
                Ver programa
            </DropdownMenuItem>
            <DropdownMenuItem @click="editProgram">
                <Edit class="mr-2 h-4 w-4" />
                Editar
            </DropdownMenuItem>
            <DropdownMenuItem @click="viewStudents">
                <Users class="mr-2 h-4 w-4" />
                Ver estudiantes ({{ props.program.students_count || 0 }})
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="deleteProgram" class="text-red-600">
                <Trash2 class="mr-2 h-4 w-4" />
                Eliminar
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
