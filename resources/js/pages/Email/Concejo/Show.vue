<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Download, Forward, Printer, Reply, ReplyAll, Trash2, Users } from 'lucide-vue-next';

interface Email {
    ID: string;
    From: {
        Address: string;
        Name: string;
    };
    To: Array<{
        Address: string;
        Name: string;
    }>;
    Subject: string;
    Date: string;
    Read: boolean;
    Size: number;
    HTML?: string;
    Text?: string;
    Attachments?: Array<{
        FileName: string;
        ContentType: string;
        Size: number;
    }>;
}

interface Props {
    email: Email;
    view: string;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('es-CO', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const deleteEmail = () => {
    if (confirm('¿Está seguro de que desea eliminar este correo?')) {
        router.delete(route('email.concejo.destroy', props.email.ID), {
            onSuccess: () => {
                router.visit(route('email.concejo.index'));
            },
        });
    }
};

const replyToEmail = () => {
    router.visit(
        route('email.concejo.compose', {
            reply_to: props.email.ID,
            subject: `Re: ${props.email.Subject}`,
            to: props.email.From.Address,
        }),
    );
};

const forwardEmail = () => {
    router.visit(
        route('email.concejo.compose', {
            forward: props.email.ID,
            subject: `Fwd: ${props.email.Subject}`,
        }),
    );
};

const printEmail = () => {
    window.print();
};
</script>

<template>
    <Head :title="`${email.Subject} - Correo Concejo`" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button @click="$inertia.visit(route('email.concejo.index'))" variant="outline" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="truncate text-2xl font-bold tracking-tight">{{ email.Subject }}</h1>
                        <p class="flex items-center gap-2 text-muted-foreground">
                            <Users class="h-4 w-4" />
                            Correo Electrónico - Concejo
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button @click="replyToEmail" variant="outline" size="sm">
                        <Reply class="mr-2 h-4 w-4" />
                        Responder
                    </Button>
                    <Button @click="forwardEmail" variant="outline" size="sm">
                        <Forward class="mr-2 h-4 w-4" />
                        Reenviar
                    </Button>
                    <Button @click="printEmail" variant="outline" size="sm">
                        <Printer class="mr-2 h-4 w-4" />
                        Imprimir
                    </Button>
                    <Button @click="deleteEmail" variant="outline" size="sm">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <!-- Email Content -->
            <Card>
                <CardHeader>
                    <div class="space-y-4">
                        <!-- Email Header Info -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-semibold">{{ email.Subject }}</h2>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="flex items-center gap-1">
                                        <Users class="h-3 w-3" />
                                        Concejo
                                    </Badge>
                                    <Badge v-if="!email.Read" variant="default">No leído</Badge>
                                </div>
                            </div>

                            <div class="space-y-1 text-sm text-muted-foreground">
                                <div>
                                    <span class="font-medium">De:</span>
                                    {{ email.From.Name }} &lt;{{ email.From.Address }}&gt;
                                </div>
                                <div>
                                    <span class="font-medium">Para:</span>
                                    <span v-for="(recipient, index) in email.To" :key="index">
                                        {{ recipient.Name }} &lt;{{ recipient.Address }}&gt;
                                        <span v-if="index < email.To.length - 1">, </span>
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium">Fecha:</span>
                                    {{ formatDate(email.Date) }}
                                </div>
                            </div>
                        </div>

                        <Separator />
                    </div>
                </CardHeader>

                <CardContent>
                    <!-- Email Body -->
                    <div class="space-y-6">
                        <!-- HTML Content -->
                        <div v-if="email.HTML" class="prose prose-sm max-w-none" v-html="email.HTML"></div>

                        <!-- Text Content (fallback) -->
                        <div v-else-if="email.Text" class="text-sm whitespace-pre-wrap">
                            {{ email.Text }}
                        </div>

                        <!-- No Content -->
                        <div v-else class="py-8 text-center text-muted-foreground">
                            <p>No hay contenido disponible para este correo.</p>
                        </div>

                        <!-- Attachments -->
                        <div v-if="email.Attachments && email.Attachments.length > 0" class="space-y-2">
                            <Separator />
                            <h3 class="font-medium">Archivos adjuntos ({{ email.Attachments.length }})</h3>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="attachment in email.Attachments"
                                    :key="attachment.FileName"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border p-2 hover:bg-muted"
                                >
                                    <Download class="h-4 w-4 text-muted-foreground" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">{{ attachment.FileName }}</p>
                                        <p class="text-xs text-muted-foreground">{{ attachment.ContentType }} • {{ formatSize(attachment.Size) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Action Buttons -->
            <div class="flex items-center justify-center gap-4 pt-4">
                <Button @click="replyToEmail" variant="default">
                    <Reply class="mr-2 h-4 w-4" />
                    Responder
                </Button>
                <Button @click="replyToEmail" variant="outline">
                    <ReplyAll class="mr-2 h-4 w-4" />
                    Responder a todos
                </Button>
                <Button @click="forwardEmail" variant="outline">
                    <Forward class="mr-2 h-4 w-4" />
                    Reenviar
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Email content styling */
.prose img {
    max-width: 100%;
    height: auto;
}

.prose table {
    width: 100%;
    border-collapse: collapse;
}

.prose th,
.prose td {
    border: 1px solid #e5e7eb;
    padding: 8px 12px;
    text-align: left;
}

.prose th {
    background-color: #f9fafb;
    font-weight: 600;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
