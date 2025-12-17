<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Props {
    success: boolean;
    message: string;
    alreadyVerified?: boolean;
    requiresApproval?: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Verificacion de Email" />

    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="flex justify-center">
                <img src="/img/tavira_logo.png" alt="Tavira" class="h-16 w-auto" />
            </div>

            <!-- Result Card -->
            <div class="bg-white shadow-lg rounded-2xl p-8 space-y-6">
                <!-- Success State -->
                <div v-if="success" class="text-center space-y-4">
                    <div class="mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ alreadyVerified ? 'Email ya verificado' : 'Email verificado' }}
                    </h2>
                    <p class="text-gray-600">{{ message }}</p>

                    <!-- Approval Notice -->
                    <div v-if="requiresApproval" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-left">
                                <p class="text-sm text-amber-800 font-medium">Pendiente de aprobacion</p>
                                <p class="text-xs text-amber-600 mt-1">
                                    Tu cuenta esta pendiente de aprobacion por el administrador del conjunto.
                                    Te notificaremos cuando tu cuenta sea activada.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4">
                        <p class="text-sm text-blue-800">
                            <strong>Puedes cerrar esta pagina.</strong><br>
                            <span v-if="requiresApproval">
                                Te enviaremos un correo cuando tu cuenta sea aprobada.
                            </span>
                            <span v-else>
                                Ya puedes iniciar sesion en la aplicacion movil.
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else class="text-center space-y-4">
                    <div class="mx-auto h-16 w-16 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Error de verificacion</h2>
                    <p class="text-gray-600">{{ message }}</p>

                    <!-- Help Text -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mt-4">
                        <p class="text-sm text-gray-600">
                            Si el enlace ha expirado, puedes solicitar un nuevo correo de verificacion desde la aplicacion movil.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-400">
                &copy; 2025 Tavira. Todos los derechos reservados.
            </p>
        </div>
    </div>
</template>
