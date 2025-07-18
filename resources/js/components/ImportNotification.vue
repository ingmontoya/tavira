<script setup lang="ts">
import { ref, watch } from 'vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { CheckCircle2, AlertCircle, X, FileSpreadsheet } from 'lucide-vue-next'

interface ImportResult {
  success: boolean
  message: string
  imported_count?: number
  failed_count?: number
  errors?: Array<{
    row: number
    field: string
    message: string
  }>
}

interface Props {
  result: ImportResult | null
  visible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
}>()

const isVisible = ref(props.visible)

watch(() => props.visible, (newValue) => {
  isVisible.value = newValue
})

const closeNotification = () => {
  isVisible.value = false
  emit('close')
}

// Auto-close success notifications after 5 seconds
watch(() => props.result, (newResult) => {
  if (newResult?.success) {
    setTimeout(() => {
      closeNotification()
    }, 5000)
  }
})
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="transform opacity-0 scale-95"
    enter-to-class="transform opacity-100 scale-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="transform opacity-100 scale-100"
    leave-to-class="transform opacity-0 scale-95"
  >
    <div
      v-if="isVisible && result"
      class="fixed top-4 right-4 z-50 w-96 max-w-sm"
    >
      <Alert :variant="result.success ? 'default' : 'destructive'" class="relative">
        <CheckCircle2 v-if="result.success" class="h-4 w-4" />
        <AlertCircle v-else class="h-4 w-4" />
        
        <Button
          variant="ghost"
          size="sm"
          class="absolute top-2 right-2 h-auto w-auto p-1"
          @click="closeNotification"
        >
          <X class="h-3 w-3" />
        </Button>
        
        <AlertDescription class="pr-6">
          <div class="space-y-2">
            <div class="font-medium">
              {{ result.success ? 'Importación Exitosa' : 'Error en Importación' }}
            </div>
            
            <div class="text-sm">
              {{ result.message }}
            </div>
            
            <!-- Success stats -->
            <div v-if="result.success && (result.imported_count !== undefined)" class="flex items-center gap-2 text-sm">
              <FileSpreadsheet class="h-3 w-3" />
              <span>{{ result.imported_count }} estudiantes importados</span>
            </div>
            
            <!-- Error stats -->
            <div v-if="!result.success && result.failed_count" class="text-sm">
              {{ result.failed_count }} registros fallaron
            </div>
            
            <!-- First few errors -->
            <div v-if="result.errors && result.errors.length > 0" class="space-y-1">
              <div class="text-xs font-medium">Primeros errores:</div>
              <div class="space-y-1">
                <div 
                  v-for="error in result.errors.slice(0, 3)" 
                  :key="`${error.row}-${error.field}`"
                  class="text-xs bg-red-50 p-1 rounded"
                >
                  <strong>Fila {{ error.row }}:</strong> {{ error.message }}
                </div>
                <div v-if="result.errors.length > 3" class="text-xs text-gray-600">
                  ... y {{ result.errors.length - 3 }} errores más
                </div>
              </div>
            </div>
          </div>
        </AlertDescription>
      </Alert>
    </div>
  </Transition>
</template>