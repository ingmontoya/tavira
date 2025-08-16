<template>
    <div v-if="!conjuntoConfigured.exists" class="mb-6 border-l-4 border-blue-400 bg-gradient-to-r from-blue-50 to-indigo-50 p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <Icon name="info" class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm text-blue-700">
                    <span class="font-medium">¡Configuración inicial requerida!</span>
                    Para utilizar todas las funcionalidades de Habitta, primero debes configurar tu conjunto residencial.
                </p>
            </div>
            <div class="ml-4 flex-shrink-0">
                <Link
                    :href="route('conjunto-config.index')"
                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                    <Icon name="settings" class="mr-2 h-4 w-4" />
                    Configurar Conjunto
                </Link>
            </div>
        </div>
        <div class="mt-3">
            <div class="text-xs text-blue-600">
                <p>📋 <strong>Qué necesitas configurar:</strong></p>
                <ul class="mt-1 ml-4 space-y-1">
                    <li>• Nombre y datos básicos del conjunto</li>
                    <li>• Configuración de torres y pisos</li>
                    <li>• Tipos de apartamentos</li>
                    <li>• Generación automática de apartamentos</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const conjuntoConfigured = computed(() => page.props.conjuntoConfigured || { exists: false, isActive: false, name: null });
</script>
