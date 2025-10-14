<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, Copy } from 'lucide-vue-next';
import { ref } from 'vue';

interface Pqrs {
    ticket_number: string;
    type: string;
    subject: string;
    submitter_email: string;
    created_at: string;
}

interface Props {
    pqrs: Pqrs;
}

const props = defineProps<Props>();

const copied = ref(false);

const copyTicket = () => {
    navigator.clipboard.writeText(props.pqrs.ticket_number);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const typeLabels: Record<string, string> = {
    peticion: 'Petición',
    queja: 'Queja',
    reclamo: 'Reclamo',
    sugerencia: 'Sugerencia',
};
</script>

<template>
    <Head title="PQRS Enviada Exitosamente" />

    <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="mb-6 flex justify-center">
                    <div class="rounded-full bg-green-600 p-6">
                        <CheckCircle2 class="h-16 w-16 text-white" />
                    </div>
                </div>

                <h1 class="mb-4 text-3xl font-bold text-gray-900">¡PQRS Enviada Exitosamente!</h1>
                <p class="mb-8 text-lg text-gray-600">
                    Su {{ typeLabels[pqrs.type].toLowerCase() }} ha sido recibida y será procesada por
                    la administración.
                </p>
            </div>

            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Detalles de su PQRS</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Número de Ticket</p>
                        <div class="mt-1 flex items-center gap-2">
                            <p class="text-2xl font-bold text-gray-900">
                                {{ pqrs.ticket_number }}
                            </p>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="copyTicket"
                                class="h-8 w-8 p-0"
                            >
                                <Copy class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="copied" class="mt-1 text-sm text-green-600">¡Copiado!</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Tipo</p>
                        <p class="mt-1 text-base text-gray-900">{{ typeLabels[pqrs.type] }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Asunto</p>
                        <p class="mt-1 text-base text-gray-900">{{ pqrs.subject }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Correo Electrónico</p>
                        <p class="mt-1 text-base text-gray-900">{{ pqrs.submitter_email }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-blue-200 bg-blue-50">
                <CardContent class="pt-6">
                    <h3 class="mb-3 font-semibold text-blue-900">Información Importante</h3>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li>
                            • Guarde su <strong>número de ticket</strong> para consultar el estado de
                            su PQRS.
                        </li>
                        <li>
                            • Recibirá una notificación por correo electrónico cuando la
                            administración responda.
                        </li>
                        <li>
                            • El tiempo de respuesta puede variar según la complejidad del caso.
                        </li>
                        <li>
                            • Puede rastrear el estado de su PQRS usando el número de ticket.
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <div class="mt-8 flex justify-center gap-4">
                <Button variant="outline" as-child>
                    <a :href="route('pqrs.public.create')">Enviar otra PQRS</a>
                </Button>
                <Button as-child>
                    <a :href="route('pqrs.track')">Rastrear mi PQRS</a>
                </Button>
            </div>
        </div>
    </div>
</template>
