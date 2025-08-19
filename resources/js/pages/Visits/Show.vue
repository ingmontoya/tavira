<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Download, QrCode, Share, Shield, User } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Visit {
    id: number;
    visitor_name: string;
    visitor_document_type: string;
    visitor_document_number: string;
    visitor_phone: string | null;
    visit_reason: string | null;
    valid_from: string;
    valid_until: string;
    qr_code: string;
    status: 'pending' | 'active' | 'used' | 'expired' | 'cancelled';
    entry_time: string | null;
    apartment: {
        id: number;
        number: string;
        tower: string;
    };
    creator: {
        id: number;
        name: string;
    };
    authorizer?: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    visit: Visit;
}>();

const qrCodeRef = ref<HTMLDivElement>();
const isGenerating = ref(false);

const statusConfig = {
    pending: { label: 'Pendiente', color: 'bg-gray-100 text-gray-800' },
    active: { label: 'Activa', color: 'bg-blue-100 text-blue-800' },
    used: { label: 'Utilizada', color: 'bg-green-100 text-green-800' },
    expired: { label: 'Expirada', color: 'bg-red-100 text-red-800' },
    cancelled: { label: 'Cancelada', color: 'bg-red-100 text-red-800' },
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const generateQRCode = async () => {
    if (!qrCodeRef.value) return;

    try {
        // Dynamic import of QRCode library
        const QRCode = await import('qrcode');

        // Validate QR code data
        if (!props.visit.qr_code || props.visit.qr_code.trim() === '') {
            console.error('QR code data is empty or null:', props.visit.qr_code);
            throw new Error('QR code data is empty');
        }

        // Create canvas directly
        const canvas = document.createElement('canvas');
        await QRCode.toCanvas(canvas, props.visit.qr_code, {
            width: 140,
            margin: 2,
            errorCorrectionLevel: 'M',
            color: {
                dark: '#000000',
                light: '#FFFFFF',
            },
        });

        qrCodeRef.value.innerHTML = '';
        qrCodeRef.value.appendChild(canvas);
    } catch (error) {
        console.error('Error generating QR code:', error);
        // Fallback text display
        if (qrCodeRef.value) {
            qrCodeRef.value.innerHTML = `
                <div class="w-48 h-48 bg-gray-100 flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300">
                    <div class="text-center">
                        <QrCode class="h-8 w-8 mx-auto mb-2 text-gray-400" />
                        <p class="text-sm font-mono text-gray-600">${props.visit.qr_code}</p>
                    </div>
                </div>
            `;
        }
    }
};

const downloadTicket = async () => {
    if (isGenerating.value) return;
    isGenerating.value = true;

    try {
        // Get the ticket element
        const ticketElement = document.getElementById('visit-ticket');
        if (!ticketElement) {
            alert('No se pudo encontrar el ticket para descargar');
            return;
        }

        // Force regenerate QR code to ensure it's in DOM
        await generateQRCode();

        // Wait for rendering
        await new Promise((resolve) => setTimeout(resolve, 300));

        // Import html2canvas with special configuration
        const html2canvas = (await import('html2canvas')).default;

        const canvas = await html2canvas(ticketElement, {
            scale: 2,
            backgroundColor: null,
            logging: true,
            useCORS: true,
            allowTaint: true,
            onclone: (clonedDocument, element) => {
                // Add styles directly to cloned elements
                const style = clonedDocument.createElement('style');
                style.textContent = `
                    .bg-gradient-to-r {
                        background: linear-gradient(to right, #2563eb, #1e40af) !important;
                    }
                    .from-blue-600 { background-color: #2563eb !important; }
                    .to-blue-800 { background-color: #1e40af !important; }
                    .from-blue-700 { background-color: #1d4ed8 !important; }
                    .to-blue-900 { background-color: #1e3a8a !important; }
                    .bg-white { background-color: #ffffff !important; }
                    .text-white { color: #ffffff !important; }
                    .text-blue-200 { color: #dbeafe !important; }
                    .text-blue-100 { color: #dbeafe !important; }
                    .text-blue-300 { color: #93c5fd !important; }
                    .text-yellow-100 { color: #fef3c7 !important; }
                    .text-yellow-300 { color: #fcd34d !important; }
                    .bg-yellow-400 { background-color: #fbbf24 !important; }
                    .via-yellow-500 { background-color: #f59e0b !important; }
                    .bg-white\\/10 { background-color: rgba(255, 255, 255, 0.1) !important; }
                    .bg-white\\/20 { background-color: rgba(255, 255, 255, 0.2) !important; }
                    .shadow-inner { box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06) !important; }
                    .shadow-xl {
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                    }
                    .backdrop-blur-sm { backdrop-filter: blur(4px) !important; }
                    .border-blue-400\\/30 { border-color: rgba(96, 165, 250, 0.3) !important; }
                    * {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                    }
                `;
                clonedDocument.head.appendChild(style);
            },
        });

        // Create download link
        const link = document.createElement('a');
        link.download = `pase-visita-${props.visit.qr_code}.png`;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Error capturing ticket:', error);
        alert('Error al generar la imagen del pase');
    } finally {
        isGenerating.value = false;
    }
};

const shareTicket = async () => {
    if (isGenerating.value) return;
    isGenerating.value = true;

    try {
        // Get the ticket element
        const ticketElement = document.getElementById('visit-ticket');
        if (!ticketElement) return;

        // Force regenerate QR code
        await generateQRCode();

        // Wait for rendering
        await new Promise((resolve) => setTimeout(resolve, 300));

        // Import html2canvas with special configuration
        const html2canvas = (await import('html2canvas')).default;

        const canvas = await html2canvas(ticketElement, {
            scale: 2,
            backgroundColor: null,
            logging: true,
            useCORS: true,
            allowTaint: true,
            onclone: (clonedDocument, element) => {
                // Add styles directly to cloned elements
                const style = clonedDocument.createElement('style');
                style.textContent = `
                    .bg-gradient-to-r {
                        background: linear-gradient(to right, #2563eb, #1e40af) !important;
                    }
                    .from-blue-600 { background-color: #2563eb !important; }
                    .to-blue-800 { background-color: #1e40af !important; }
                    .from-blue-700 { background-color: #1d4ed8 !important; }
                    .to-blue-900 { background-color: #1e3a8a !important; }
                    .bg-white { background-color: #ffffff !important; }
                    .text-white { color: #ffffff !important; }
                    .text-blue-200 { color: #dbeafe !important; }
                    .text-blue-100 { color: #dbeafe !important; }
                    .text-blue-300 { color: #93c5fd !important; }
                    .text-yellow-100 { color: #fef3c7 !important; }
                    .text-yellow-300 { color: #fcd34d !important; }
                    .bg-yellow-400 { background-color: #fbbf24 !important; }
                    .via-yellow-500 { background-color: #f59e0b !important; }
                    .bg-white\\/10 { background-color: rgba(255, 255, 255, 0.1) !important; }
                    .bg-white\\/20 { background-color: rgba(255, 255, 255, 0.2) !important; }
                    .shadow-inner { box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06) !important; }
                    .shadow-xl {
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                    }
                    .backdrop-blur-sm { backdrop-filter: blur(4px) !important; }
                    .border-blue-400\\/30 { border-color: rgba(96, 165, 250, 0.3) !important; }
                    * {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                    }
                `;
                clonedDocument.head.appendChild(style);
            },
        });

        // Convert canvas to blob
        canvas.toBlob(async (blob) => {
            if (!blob) return;

            try {
                const file = new File([blob], `pase-visita-${props.visit.qr_code}.png`, { type: 'image/png' });

                if (navigator.share && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: `Pase de Visita - ${props.visit.visitor_name}`,
                        files: [file],
                    });
                } else {
                    // Fallback download
                    const link = document.createElement('a');
                    link.download = `pase-visita-${props.visit.qr_code}.png`;
                    link.href = URL.createObjectURL(blob);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);

                    alert('Imagen descargada. Puedes compartirla manualmente.');
                }
            } catch (error) {
                console.error('Error sharing:', error);
                alert('No se pudo compartir. Intenta descargar el pase.');
            }
        }, 'image/png');
    } catch (error) {
        console.error('Error sharing ticket:', error);
        alert('Error al generar el pase para compartir.');
    } finally {
        isGenerating.value = false;
    }
};

onMounted(() => {
    generateQRCode();
});
</script>

<template>
    <Head :title="`Visita - ${visit.visitor_name}`" />

    <AppLayout>
        <div class="container mx-auto max-w-4xl px-6 py-8">
            <div class="mb-8">
                <div class="mb-4 flex items-center gap-4">
                    <Link :href="route('visits.index')" class="text-gray-500 hover:text-gray-700">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Código de Visita</h1>
                        <p class="text-gray-600">{{ visit.visitor_name }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Ticket Design -->
                <div class="space-y-6">
                    <!-- Compact Concert Ticket Style Card -->
                    <div
                        id="visit-ticket"
                        class="relative mx-auto max-w-sm overflow-hidden rounded-lg bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-xl"
                    >
                        <!-- Decorative border pattern -->
                        <div class="absolute top-0 right-0 left-0 h-1 bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-400"></div>

                        <!-- Main ticket content -->
                        <div class="p-4">
                            <!-- Compact Header -->
                            <div class="mb-3 text-center">
                                <div class="mb-2 inline-flex items-center gap-1 rounded bg-white/10 px-2 py-1 backdrop-blur-sm">
                                    <Shield class="h-3 w-3 text-yellow-300" />
                                    <span class="text-xs font-bold text-yellow-100">Tavira ACCESS</span>
                                </div>
                                <h2 class="text-lg font-bold">PASE DE VISITA</h2>
                            </div>

                            <!-- Compact Visitor Info -->
                            <div class="mb-3 space-y-1 text-center">
                                <div class="font-semibold">{{ visit.visitor_name }}</div>
                                <div class="text-sm text-blue-200">Torre {{ visit.apartment.tower }} - Apt {{ visit.apartment.number }}</div>
                            </div>

                            <!-- QR Code Section -->
                            <div class="mb-3 text-center">
                                <div class="inline-block rounded-lg bg-white p-2 shadow-inner">
                                    <div ref="qrCodeRef" class="flex justify-center"></div>
                                </div>
                                <div class="mt-2 font-mono text-xs tracking-wider text-blue-100">
                                    {{ visit.qr_code }}
                                </div>
                            </div>

                            <!-- Compact Validity -->
                            <div class="space-y-1 border-t border-blue-400/30 pt-2">
                                <div class="text-center text-xs">
                                    <div class="text-blue-200">Válido:</div>
                                    <div class="font-mono">{{ formatDate(visit.valid_from) }}</div>
                                    <div class="text-blue-200">hasta</div>
                                    <div class="font-mono">{{ formatDate(visit.valid_until) }}</div>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="mt-3 flex justify-center">
                                <div class="inline-flex items-center gap-1 rounded-full bg-white/20 px-3 py-1 backdrop-blur-sm">
                                    <div
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            visit.status === 'pending'
                                                ? 'bg-yellow-400'
                                                : visit.status === 'active'
                                                  ? 'bg-blue-400'
                                                  : visit.status === 'used'
                                                    ? 'bg-green-400'
                                                    : 'bg-red-400',
                                        ]"
                                    ></div>
                                    <span class="text-xs font-medium">{{ statusConfig[visit.status].label }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Bottom section -->
                        <div class="bg-gradient-to-r from-blue-700 to-blue-900 px-4 py-2">
                            <div class="text-center text-xs text-blue-200">
                                <div class="text-blue-300">Por: {{ visit.creator.name }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-center gap-2 print:hidden">
                        <Button @click="downloadTicket" variant="outline" class="flex-1" :disabled="isGenerating">
                            <Download class="mr-2 h-4 w-4" />
                            {{ isGenerating ? 'Generando...' : 'Descargar' }}
                        </Button>
                        <Button @click="shareTicket" variant="outline" class="flex-1" :disabled="isGenerating">
                            <Share class="mr-2 h-4 w-4" />
                            Compartir
                        </Button>
                    </div>
                </div>

                <!-- Visit Details -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Detalles del Visitante
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Nombre completo</label>
                                    <div class="text-base font-medium">{{ visit.visitor_name }}</div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Documento</label>
                                        <div class="text-base">{{ visit.visitor_document_type }}</div>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Número</label>
                                        <div class="font-mono text-base">{{ visit.visitor_document_number }}</div>
                                    </div>
                                </div>

                                <div v-if="visit.visitor_phone">
                                    <label class="text-sm font-medium text-gray-500">Teléfono</label>
                                    <div class="text-base">{{ visit.visitor_phone }}</div>
                                </div>

                                <div v-if="visit.visit_reason">
                                    <label class="text-sm font-medium text-gray-500">Motivo de la visita</label>
                                    <div class="text-base">{{ visit.visit_reason }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Shield class="h-5 w-5" />
                                Estado de la Visita
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Estado actual</label>
                                <div class="mt-1">
                                    <Badge :class="statusConfig[visit.status].color">
                                        {{ statusConfig[visit.status].label }}
                                    </Badge>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Creada por</label>
                                <div class="text-base">{{ visit.creator.name }}</div>
                            </div>

                            <div v-if="visit.entry_time">
                                <label class="text-sm font-medium text-gray-500">Hora de entrada</label>
                                <div class="text-base">{{ formatDate(visit.entry_time) }}</div>
                            </div>

                            <div v-if="visit.authorizer">
                                <label class="text-sm font-medium text-gray-500">Autorizada por</label>
                                <div class="text-base">{{ visit.authorizer.name }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div class="mb-2 flex items-center gap-2 text-blue-800">
                            <QrCode class="h-5 w-5" />
                            <h3 class="font-medium">Instrucciones de uso</h3>
                        </div>
                        <ul class="space-y-1 text-sm text-blue-700">
                            <li>• Comparte este código QR con tu visitante</li>
                            <li>• El visitante debe presentar el código en portería</li>
                            <li>• El código solo es válido durante el período especificado</li>
                            <li>• Una vez utilizado, el código no podrá ser reutilizado</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    #visit-ticket,
    #visit-ticket * {
        visibility: visible;
    }

    #visit-ticket {
        position: absolute;
        left: 0;
        top: 0;
        transform: none !important;
        max-width: none !important;
    }

    .print\:hidden {
        display: none !important;
    }
}

/* Ensure these styles are applied globally for html2canvas */
.bg-gradient-to-r {
    background: linear-gradient(to right, #2563eb, #1e40af) !important;
}
.from-blue-600 {
    background-color: #2563eb !important;
}
.to-blue-800 {
    background-color: #1e40af !important;
}
.from-blue-700 {
    background-color: #1d4ed8 !important;
}
.to-blue-900 {
    background-color: #1e3a8a !important;
}
.bg-white {
    background-color: #ffffff !important;
}
.text-white {
    color: #ffffff !important;
}
.text-blue-200 {
    color: #dbeafe !important;
}
.text-blue-100 {
    color: #dbeafe !important;
}
.text-blue-300 {
    color: #93c5fd !important;
}
.text-yellow-100 {
    color: #fef3c7 !important;
}
.text-yellow-300 {
    color: #fcd34d !important;
}
.bg-yellow-400 {
    background-color: #fbbf24 !important;
}
.via-yellow-500 {
    background-color: #f59e0b !important;
}
.bg-white\/10 {
    background-color: rgba(255, 255, 255, 0.1) !important;
}
.bg-white\/20 {
    background-color: rgba(255, 255, 255, 0.2) !important;
}
.shadow-inner {
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06) !important;
}
.shadow-xl {
    box-shadow:
        0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
}
.backdrop-blur-sm {
    backdrop-filter: blur(4px) !important;
}
.border-blue-400\/30 {
    border-color: rgba(96, 165, 250, 0.3) !important;
}
</style>
