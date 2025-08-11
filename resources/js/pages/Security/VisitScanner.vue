<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { QrCode, Scan, Check, X, AlertTriangle, User, Home, Calendar, Clock, Shield } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

interface Visit {
    id: number;
    visitor_name: string;
    visitor_document_number: string;
    visit_reason: string;
    valid_from: string;
    valid_until: string;
    qr_code: string;
    status: string;
    apartment: {
        number: string;
        tower: string;
    };
    creator: {
        name: string;
    };
}

const qrCode = ref('');
const securityNotes = ref('');
const isValidating = ref(false);
const validationResult = ref<{
    valid: boolean;
    message: string;
    visit?: Visit;
} | null>(null);
const isAuthorizing = ref(false);

const validateQR = async () => {
    if (!qrCode.value.trim()) return;
    
    isValidating.value = true;
    validationResult.value = null;

    try {
        const response = await fetch(route('security.visits.validate-qr'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                qr_code: qrCode.value.trim(),
            }),
        });

        const data = await response.json();
        validationResult.value = data;

    } catch (error) {
        console.error('Error validating QR:', error);
        validationResult.value = {
            valid: false,
            message: 'Error de conexión. Intenta nuevamente.',
        };
    } finally {
        isValidating.value = false;
    }
};

const authorizeEntry = async () => {
    if (!validationResult.value?.valid || !validationResult.value?.visit) return;

    isAuthorizing.value = true;

    try {
        const response = await fetch(route('security.visits.authorize-entry'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                qr_code: qrCode.value.trim(),
                security_notes: securityNotes.value.trim(),
            }),
        });

        const data = await response.json();

        if (data.success) {
            // Reset form and show success
            qrCode.value = '';
            securityNotes.value = '';
            validationResult.value = {
                valid: true,
                message: 'Entrada autorizada exitosamente',
            };
            
            // Auto-clear after 3 seconds
            setTimeout(() => {
                validationResult.value = null;
            }, 3000);
        } else {
            validationResult.value = {
                valid: false,
                message: data.message || 'Error al autorizar la entrada',
            };
        }

    } catch (error) {
        console.error('Error authorizing entry:', error);
        validationResult.value = {
            valid: false,
            message: 'Error de conexión. Intenta nuevamente.',
        };
    } finally {
        isAuthorizing.value = false;
    }
};

const resetForm = () => {
    qrCode.value = '';
    securityNotes.value = '';
    validationResult.value = null;
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

// Auto-focus QR input on mount
onMounted(() => {
    document.getElementById('qr-input')?.focus();
});
</script>

<template>
    <Head title="Escáner de Visitas" />
    
    <AppLayout>
        <div class="container mx-auto px-6 py-8 max-w-2xl">
            <div class="mb-8 text-center">
                <div class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl px-6 py-4 mb-4">
                    <Shield class="h-6 w-6" />
                    <h1 class="text-2xl font-bold">Control de Acceso</h1>
                </div>
                <p class="text-gray-600">Escanea el código QR del visitante para autorizar el ingreso</p>
            </div>

            <div class="space-y-6">
                <!-- QR Scanner -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Scan class="h-5 w-5" />
                            Escáner de Código QR
                        </CardTitle>
                        <CardDescription>
                            Ingresa o escanea el código QR del visitante
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label for="qr-input">Código QR</Label>
                            <Input
                                id="qr-input"
                                v-model="qrCode"
                                placeholder="Ingresa el código QR..."
                                class="font-mono text-lg text-center"
                                @keyup.enter="validateQR"
                                @input="validationResult = null"
                            />
                        </div>

                        <div class="flex gap-3">
                            <Button
                                @click="validateQR"
                                :disabled="!qrCode.trim() || isValidating"
                                class="flex-1"
                                variant="default"
                            >
                                <QrCode class="h-4 w-4 mr-2" />
                                {{ isValidating ? 'Validando...' : 'Validar Código' }}
                            </Button>
                            <Button @click="resetForm" variant="outline">
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Validation Result -->
                <div v-if="validationResult">
                    <!-- Valid Visit -->
                    <Card v-if="validationResult.valid && validationResult.visit" class="border-green-200 bg-green-50">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-green-800">
                                <Check class="h-5 w-5" />
                                Código Válido
                            </CardTitle>
                            <CardDescription class="text-green-700">
                                {{ validationResult.message }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Visit Details -->
                            <div class="bg-white rounded-lg p-4 space-y-3">
                                <div class="flex items-center gap-3">
                                    <User class="h-5 w-5 text-gray-500" />
                                    <div>
                                        <div class="font-semibold text-lg">{{ validationResult.visit.visitor_name }}</div>
                                        <div class="text-sm text-gray-600">{{ validationResult.visit.visitor_document_number }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Home class="h-5 w-5 text-gray-500" />
                                    <div>
                                        <div class="font-medium">
                                            Torre {{ validationResult.visit.apartment.tower }} - Apt {{ validationResult.visit.apartment.number }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            Autorizado por: {{ validationResult.visit.creator.name }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="validationResult.visit.visit_reason" class="flex items-start gap-3">
                                    <Clock class="h-5 w-5 text-gray-500 mt-0.5" />
                                    <div>
                                        <div class="text-sm text-gray-600 font-medium">Motivo de la visita:</div>
                                        <div class="text-sm">{{ validationResult.visit.visit_reason }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                                    <div>
                                        <div class="text-xs text-gray-500">Válido desde</div>
                                        <div class="text-sm font-mono">{{ formatDate(validationResult.visit.valid_from) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Válido hasta</div>
                                        <div class="text-sm font-mono">{{ formatDate(validationResult.visit.valid_until) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Notes -->
                            <div>
                                <Label for="security-notes">Notas de seguridad (opcional)</Label>
                                <Textarea
                                    id="security-notes"
                                    v-model="securityNotes"
                                    placeholder="Observaciones del personal de seguridad..."
                                    rows="3"
                                />
                            </div>

                            <!-- Authorize Button -->
                            <Button
                                @click="authorizeEntry"
                                :disabled="isAuthorizing"
                                class="w-full bg-green-600 hover:bg-green-700"
                                size="lg"
                            >
                                <Check class="h-5 w-5 mr-2" />
                                {{ isAuthorizing ? 'Autorizando...' : 'Autorizar Entrada' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Invalid Visit or Error -->
                    <Alert v-else-if="!validationResult.valid" class="border-red-200 bg-red-50">
                        <AlertTriangle class="h-4 w-4 text-red-600" />
                        <AlertDescription class="text-red-800">
                            <div class="font-medium mb-2">Código No Válido</div>
                            <div>{{ validationResult.message }}</div>
                            
                            <!-- Show visit details if available but invalid -->
                            <div v-if="validationResult.visit" class="mt-3 p-3 bg-white rounded border">
                                <div class="text-sm space-y-1">
                                    <div><strong>Visitante:</strong> {{ validationResult.visit.visitor_name }}</div>
                                    <div><strong>Apartamento:</strong> {{ validationResult.visit.apartment.tower }}-{{ validationResult.visit.apartment.number }}</div>
                                    <div><strong>Estado:</strong> {{ validationResult.visit.status }}</div>
                                </div>
                            </div>
                        </AlertDescription>
                    </Alert>

                    <!-- Success Message -->
                    <Alert v-else-if="validationResult.valid && !validationResult.visit" class="border-green-200 bg-green-50">
                        <Check class="h-4 w-4 text-green-600" />
                        <AlertDescription class="text-green-800">
                            <div class="font-medium">{{ validationResult.message }}</div>
                        </AlertDescription>
                    </Alert>
                </div>

                <!-- Quick Actions -->
                <Card class="bg-blue-50 border-blue-200">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-blue-800">
                            <Shield class="h-5 w-5" />
                            Acciones Rápidas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-3">
                            <Button
                                @click="router.get(route('security.visits.recent-entries'))"
                                variant="outline"
                                class="justify-start"
                            >
                                <Clock class="h-4 w-4 mr-2" />
                                Ver Entradas Recientes
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Instructions -->
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <h3 class="font-medium text-gray-800 mb-2">Instrucciones:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Solicita al visitante que muestre su código QR</li>
                        <li>• Ingresa o escanea el código en el campo superior</li>
                        <li>• Verifica la identidad del visitante con sus datos</li>
                        <li>• Autoriza la entrada si todo está correcto</li>
                        <li>• Registra cualquier observación en las notas de seguridad</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>