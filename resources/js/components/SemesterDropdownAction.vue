<script setup lang="ts">
import { MoreHorizontal, Eye, Edit, Trash2, Users, Calendar } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { router } from '@inertiajs/vue3'

interface Semester {
    id: number
    name: string
    start_date: string
    end_date: string
    students_count?: number
}

const props = defineProps<{
  semester: Semester
}>()

const viewSemester = () => {
  router.visit(`/semesters/${props.semester.id}`)
}

const editSemester = () => {
  router.visit(`/semesters/${props.semester.id}/edit`)
}

const deleteSemester = () => {
  if (confirm('¿Está seguro que desea eliminar este semestre?')) {
    router.delete(`/semesters/${props.semester.id}`)
  }
}

const viewStudents = () => {
  router.visit(`/students?semester_id=${props.semester.id}`)
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="w-8 h-8 p-0">
        <span class="sr-only">Open menu</span>
        <MoreHorizontal class="w-4 h-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuLabel>Acciones</DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="viewSemester">
        <Eye class="mr-2 h-4 w-4" />
        Ver semestre
      </DropdownMenuItem>
      <DropdownMenuItem @click="editSemester">
        <Edit class="mr-2 h-4 w-4" />
        Editar
      </DropdownMenuItem>
      <DropdownMenuItem @click="viewStudents">
        <Users class="mr-2 h-4 w-4" />
        Ver estudiantes ({{ props.semester.students_count || 0 }})
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="deleteSemester" class="text-red-600">
        <Trash2 class="mr-2 h-4 w-4" />
        Eliminar
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>