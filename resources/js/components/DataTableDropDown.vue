<script setup lang="ts">
import { MoreHorizontal, Eye, Edit, Trash2, Copy } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
  resident: any
}>()

const viewResident = () => {
  router.visit(`/residents/${props.resident.id}`)
}

const editResident = () => {
  router.visit(`/residents/${props.resident.id}/edit`)
}

const deleteResident = () => {
  if (confirm('¿Está seguro que desea eliminar este residente?')) {
    router.delete(`/residents/${props.resident.id}`)
  }
}

const copyResidentCode = () => {
  navigator.clipboard.writeText(props.resident.document_number)
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
    </DropdownMenuContent>
  </DropdownMenu>
</template>
