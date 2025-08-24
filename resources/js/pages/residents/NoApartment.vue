<template>
  <AppLayout>
    <div class="container mx-auto p-6">
      <div class="flex items-center justify-center min-h-[60vh]">
        <Card class="w-full max-w-md">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100">
              <AlertTriangle class="h-8 w-8 text-yellow-600" />
            </div>
            <CardTitle class="text-xl font-semibold text-gray-900">
              Apartamento no asignado
            </CardTitle>
            <CardDescription class="text-gray-600">
              {{ message }}
            </CardDescription>
          </CardHeader>
          <CardContent class="text-center space-y-4">
            <p class="text-sm text-gray-500">
              Bienvenido a <strong>{{ conjuntoName }}</strong>
            </p>
            <p class="text-sm text-gray-700">
              Para acceder a tu dashboard de residente, necesitas tener un apartamento asignado por el administrador.
            </p>
          </CardContent>
          <CardFooter class="flex justify-center">
            <Button @click="contactAdmin" variant="default">
              Contactar Administrador
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

interface Props {
  conjuntoName: string
  message: string
}

defineProps<Props>()

const contactAdmin = () => {
  // Simple mailto link for now - can be enhanced with a contact form later
  window.location.href = 'mailto:admin@habitta.com?subject=Solicitud de asignación de apartamento'
}
</script>