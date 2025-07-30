<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';

const breadcrumbs = [
    { label: 'PQRS', href: '#', current: true }
];
</script>

<template>
    <Head title="PQRS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6">
            <div class="text-center">
                <Construction class="mx-auto h-24 w-24 text-orange-500 mb-6" />
                <h1 class="text-3xl font-bold text-gray-900 mb-4">PQRS</h1>
                <p class="text-lg text-gray-600 mb-8">
                    Esta sección está en construcción. Aquí podrás enviar Peticiones, Quejas, 
                    Reclamos y Sugerencias a la administración del conjunto.
                </p>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Próximamente disponible:</h3>
                    <ul class="text-left text-blue-800 space-y-2">
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