<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';

const breadcrumbs = [{ label: 'PQRS', href: '#', current: true }];
</script>

<template>
    <Head title="PQRS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6">
            <div class="text-center">
                <Construction class="mx-auto mb-6 h-24 w-24 text-orange-500" />
                <h1 class="mb-4 text-3xl font-bold text-gray-900">PQRS</h1>
                <p class="mb-8 text-lg text-gray-600">
                    Esta sección está en construcción. Aquí podrás enviar Peticiones, Quejas, Reclamos y Sugerencias a la administración del conjunto.
                </p>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-6">
                    <h3 class="mb-2 text-lg font-semibold text-blue-900">Próximamente disponible:</h3>
                    <ul class="space-y-2 text-left text-blue-800">
                        <li>• Enviar peticiones</li>
                        <li>• Registrar quejas</li>
                        <li>• Presentar reclamos</li>
                        <li>• Enviar sugerencias</li>
                        <li>• Seguimiento de solicitudes</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
