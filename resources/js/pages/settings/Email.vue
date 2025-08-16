<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { type BreadcrumbItem } from '@/types';
import { AlertCircle, CheckCircle2, Eye, EyeOff, Mail, RotateCcw, Send, Settings, Shield, TestTube, Zap } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

interface EmailSettings {
    [key: string]: any;
}

interface EmailPreset {
    name: string;
    description: string;
    settings: Record<string, any>;
}

interface MailpitStatus {
    available: boolean;
    message: string;
    version?: string;
}

interface Props {
    settings: EmailSettings;
    emailPresets: Record<string, EmailPreset>;
    isConfigured: boolean;
    mailpitStatus: MailpitStatus;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configuración de Correo',
        href: '/settings/email',
    },
];

const form = useForm(props.settings);
const presetForm = useForm({
    preset: '',
});

const testConnectionForm = useForm({
    smtp_host: props.settings.smtp_host,
    smtp_port: props.settings.smtp_port,
    smtp_username: props.settings.smtp_username,
    smtp_password: '',
    smtp_encryption: props.settings.smtp_encryption,
});

const testEmailForm = useForm({
    to_email: '',
    email_type: 'admin',
});

const showAdvanced = ref(false);
const activeTab = ref('smtp');
const showPassword = ref(false);
const isTestingConnection = ref(false);
const isSendingTestEmail = ref(false);
const connectionTestResult = ref<{ success: boolean; message: string } | null>(null);
const testEmailResult = ref<{ success: boolean; message: string } | null>(null);

const configurationStatus = computed(() => {
    if (props.isConfigured) {
        return {
            icon: CheckCircle2,
            text: 'Configuración completa',
            color: 'text-green-600',
            bgColor: 'bg-green-50',
        };
    } else {
        return {
            icon: AlertCircle,
            text: 'Configuración incompleta',
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
        };
    }
});

const submit = () => {
    form.post(route('settings.email.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset test results on successful save
            connectionTestResult.value = null;
            testEmailResult.value = null;
        },
    });
};

const applyPreset = () => {
    if (!presetForm.preset) return;

    presetForm.post(route('settings.email.apply-preset'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reload the page to get updated settings
            window.location.reload();
        },
    });
};

const resetDefaults = () => {
    if (confirm('¿Está seguro de que desea restablecer todas las configuraciones a sus valores predeterminados?')) {
        form.post(route('settings.email.reset-defaults'), {
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};

const testConnection = async () => {
    isTestingConnection.value = true;
    connectionTestResult.value = null;

    try {
        const response = await fetch(route('settings.email.test-connection'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(testConnectionForm.data()),
        });

        const result = await response.json();
        connectionTestResult.value = result;
    } catch (error) {
        connectionTestResult.value = {
            success: false,
            message: 'Error de conexión: ' + (error as Error).message,
        };
    } finally {
        isTestingConnection.value = false;
    }
};

const sendTestEmail = async () => {
    if (!testEmailForm.to_email) return;

    isSendingTestEmail.value = true;
    testEmailResult.value = null;

    try {
        const response = await fetch(route('settings.email.test-email'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(testEmailForm.data()),
        });

        const result = await response.json();
        testEmailResult.value = result;
    } catch (error) {
        testEmailResult.value = {
            success: false,
            message: 'Error al enviar: ' + (error as Error).message,
        };
    } finally {
        isSendingTestEmail.value = false;
    }
};
</script>

<template>
    <Head title="Configuración de Correo" />

    <AppLayout>
        <SettingsLayout :breadcrumb-items="breadcrumbItems">
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <HeadingSmall>Configuración de Correo Electrónico</HeadingSmall>
                        <p class="text-sm text-muted-foreground">Configure las cuentas de correo para administración y concejo</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Badge :class="[configurationStatus.color, configurationStatus.bgColor]" class="flex items-center gap-1">
                            <component :is="configurationStatus.icon" class="h-3 w-3" />
                            {{ configurationStatus.text }}
                        </Badge>
                    </div>
                </div>

                <!-- Mailpit Status -->
                <Alert v-if="mailpitStatus.available" class="border-blue-200 bg-blue-50">
                    <CheckCircle2 class="h-4 w-4 text-blue-600" />
                    <AlertDescription class="text-blue-800">
                        {{ mailpitStatus.message }}
                        <span v-if="mailpitStatus.version" class="ml-2 text-xs"> (v{{ mailpitStatus.version }}) </span>
                    </AlertDescription>
                </Alert>

                <Alert v-else class="border-orange-200 bg-orange-50">
                    <AlertCircle class="h-4 w-4 text-orange-600" />
                    <AlertDescription class="text-orange-800">
                        {{ mailpitStatus.message }}
                    </AlertDescription>
                </Alert>

                <!-- Main Content -->
                <form @submit.prevent="submit" class="space-y-6">
                    <Tabs v-model="activeTab" class="w-full">
                        <TabsList class="grid w-full grid-cols-4">
                            <TabsTrigger value="smtp">Servidor SMTP</TabsTrigger>
                            <TabsTrigger value="accounts">Cuentas de Correo</TabsTrigger>
                            <TabsTrigger value="general">Configuración General</TabsTrigger>
                            <TabsTrigger value="advanced">Avanzado</TabsTrigger>
                        </TabsList>

                        <!-- SMTP Configuration -->
                        <TabsContent value="smtp" class="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Settings class="h-5 w-5" />
                                        Configuración del Servidor SMTP
                                    </CardTitle>
                                    <CardDescription> Configure la conexión al servidor de correo saliente </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <!-- Presets -->
                                    <div class="space-y-2">
                                        <Label>Configuraciones Predefinidas</Label>
                                        <div class="flex gap-2">
                                            <Select v-model="presetForm.preset">
                                                <SelectTrigger class="w-64">
                                                    <SelectValue placeholder="Seleccionar preset" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="(preset, key) in emailPresets" :key="key" :value="key">
                                                        {{ preset.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Button @click="applyPreset" type="button" variant="outline" :disabled="!presetForm.preset">
                                                <Zap class="mr-2 h-4 w-4" />
                                                Aplicar
                                            </Button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label for="smtp_host">Servidor SMTP *</Label>
                                            <Input
                                                id="smtp_host"
                                                v-model="form.smtp_host"
                                                placeholder="smtp.gmail.com"
                                                :class="{ 'border-red-500': form.errors.smtp_host }"
                                            />
                                            <p v-if="form.errors.smtp_host" class="text-sm text-red-500">
                                                {{ form.errors.smtp_host }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="smtp_port">Puerto *</Label>
                                            <Input
                                                id="smtp_port"
                                                v-model="form.smtp_port"
                                                type="number"
                                                placeholder="587"
                                                :class="{ 'border-red-500': form.errors.smtp_port }"
                                            />
                                            <p v-if="form.errors.smtp_port" class="text-sm text-red-500">
                                                {{ form.errors.smtp_port }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="smtp_username">Usuario *</Label>
                                            <Input
                                                id="smtp_username"
                                                v-model="form.smtp_username"
                                                placeholder="usuario@ejemplo.com"
                                                :class="{ 'border-red-500': form.errors.smtp_username }"
                                            />
                                            <p v-if="form.errors.smtp_username" class="text-sm text-red-500">
                                                {{ form.errors.smtp_username }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="smtp_password">Contraseña *</Label>
                                            <div class="relative">
                                                <Input
                                                    id="smtp_password"
                                                    v-model="form.smtp_password"
                                                    :type="showPassword ? 'text' : 'password'"
                                                    placeholder="••••••••"
                                                    :class="{ 'border-red-500': form.errors.smtp_password }"
                                                    class="pr-10"
                                                />
                                                <Button
                                                    @click="showPassword = !showPassword"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="absolute top-0 right-0 h-full px-3"
                                                >
                                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                                    <EyeOff v-else class="h-4 w-4" />
                                                </Button>
                                            </div>
                                            <p v-if="form.errors.smtp_password" class="text-sm text-red-500">
                                                {{ form.errors.smtp_password }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="smtp_encryption">Encriptación</Label>
                                            <Select v-model="form.smtp_encryption">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar encriptación" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">Sin encriptación</SelectItem>
                                                    <SelectItem value="tls">TLS</SelectItem>
                                                    <SelectItem value="ssl">SSL</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="smtp_timeout">Timeout (segundos)</Label>
                                            <Input id="smtp_timeout" v-model="form.smtp_timeout" type="number" min="1" max="300" />
                                        </div>
                                    </div>

                                    <!-- Test Connection -->
                                    <div class="border-t pt-4">
                                        <div class="flex items-center gap-4">
                                            <Button
                                                @click="testConnection"
                                                type="button"
                                                variant="outline"
                                                :disabled="isTestingConnection || !form.smtp_host || !form.smtp_username"
                                            >
                                                <TestTube class="mr-2 h-4 w-4" />
                                                {{ isTestingConnection ? 'Probando...' : 'Probar Conexión' }}
                                            </Button>

                                            <div v-if="connectionTestResult" class="flex items-center gap-2">
                                                <CheckCircle2 v-if="connectionTestResult.success" class="h-4 w-4 text-green-600" />
                                                <AlertCircle v-else class="h-4 w-4 text-red-600" />
                                                <span :class="connectionTestResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm">
                                                    {{ connectionTestResult.message }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Email Accounts -->
                        <TabsContent value="accounts" class="space-y-4">
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <!-- Admin Account -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Shield class="h-5 w-5" />
                                            Cuenta de Administración
                                        </CardTitle>
                                        <CardDescription> Configuración del correo para la administración del conjunto </CardDescription>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="admin_email_address">Dirección de Correo *</Label>
                                            <Input
                                                id="admin_email_address"
                                                v-model="form.admin_email_address"
                                                type="email"
                                                placeholder="admin@miconjunto.com"
                                                :class="{ 'border-red-500': form.errors.admin_email_address }"
                                            />
                                            <p v-if="form.errors.admin_email_address" class="text-sm text-red-500">
                                                {{ form.errors.admin_email_address }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="admin_email_name">Nombre para Mostrar</Label>
                                            <Input id="admin_email_name" v-model="form.admin_email_name" placeholder="Administración" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="admin_email_signature">Firma de Correo</Label>
                                            <Textarea
                                                id="admin_email_signature"
                                                v-model="form.admin_email_signature"
                                                rows="3"
                                                placeholder="Administración del Conjunto..."
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Council Account -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Mail class="h-5 w-5" />
                                            Cuenta del Concejo
                                        </CardTitle>
                                        <CardDescription> Configuración del correo para el concejo de administración </CardDescription>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="council_email_address">Dirección de Correo *</Label>
                                            <Input
                                                id="council_email_address"
                                                v-model="form.council_email_address"
                                                type="email"
                                                placeholder="concejo@miconjunto.com"
                                                :class="{ 'border-red-500': form.errors.council_email_address }"
                                            />
                                            <p v-if="form.errors.council_email_address" class="text-sm text-red-500">
                                                {{ form.errors.council_email_address }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="council_email_name">Nombre para Mostrar</Label>
                                            <Input
                                                id="council_email_name"
                                                v-model="form.council_email_name"
                                                placeholder="Concejo de Administración"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="council_email_signature">Firma de Correo</Label>
                                            <Textarea
                                                id="council_email_signature"
                                                v-model="form.council_email_signature"
                                                rows="3"
                                                placeholder="Concejo de Administración..."
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Test Email Section -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Send class="h-5 w-5" />
                                        Probar Envío de Correo
                                    </CardTitle>
                                    <CardDescription> Envía un correo de prueba para verificar la configuración </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="space-y-2">
                                            <Label for="test_to_email">Enviar a</Label>
                                            <Input
                                                id="test_to_email"
                                                v-model="testEmailForm.to_email"
                                                type="email"
                                                placeholder="prueba@ejemplo.com"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="test_email_type">Tipo de Cuenta</Label>
                                            <Select v-model="testEmailForm.email_type">
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="admin">Administración</SelectItem>
                                                    <SelectItem value="council">Concejo</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="flex items-end">
                                            <Button
                                                @click="sendTestEmail"
                                                type="button"
                                                variant="outline"
                                                :disabled="isSendingTestEmail || !testEmailForm.to_email || !isConfigured"
                                                class="w-full"
                                            >
                                                <Send class="mr-2 h-4 w-4" />
                                                {{ isSendingTestEmail ? 'Enviando...' : 'Enviar Prueba' }}
                                            </Button>
                                        </div>
                                    </div>

                                    <div v-if="testEmailResult" class="flex items-center gap-2">
                                        <CheckCircle2 v-if="testEmailResult.success" class="h-4 w-4 text-green-600" />
                                        <AlertCircle v-else class="h-4 w-4 text-red-600" />
                                        <span :class="testEmailResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm">
                                            {{ testEmailResult.message }}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- General Settings -->
                        <TabsContent value="general" class="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Configuración General</CardTitle>
                                    <CardDescription> Opciones generales para el sistema de correo </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-4">
                                            <div class="flex items-center space-x-2">
                                                <Checkbox id="email_enabled" v-model:checked="form.email_enabled" />
                                                <Label for="email_enabled">Habilitar envío de correos</Label>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <Checkbox id="email_queue_enabled" v-model:checked="form.email_queue_enabled" />
                                                <Label for="email_queue_enabled">Usar cola de correos</Label>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <Checkbox id="mailpit_enabled" v-model:checked="form.mailpit_enabled" />
                                                <Label for="mailpit_enabled">Habilitar Mailpit</Label>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="email_retry_attempts">Intentos de reenvío</Label>
                                                <Input id="email_retry_attempts" v-model="form.email_retry_attempts" type="number" min="1" max="10" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="email_retry_delay">Demora entre intentos (seg)</Label>
                                                <Input id="email_retry_delay" v-model="form.email_retry_delay" type="number" min="1" max="3600" />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Advanced Settings -->
                        <TabsContent value="advanced" class="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Configuración Avanzada</CardTitle>
                                    <CardDescription> Opciones avanzadas para usuarios experimentados </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="emails_per_hour_limit">Límite por hora</Label>
                                                <Input
                                                    id="emails_per_hour_limit"
                                                    v-model="form.emails_per_hour_limit"
                                                    type="number"
                                                    min="1"
                                                    max="10000"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="emails_per_day_limit">Límite por día</Label>
                                                <Input
                                                    id="emails_per_day_limit"
                                                    v-model="form.emails_per_day_limit"
                                                    type="number"
                                                    min="1"
                                                    max="50000"
                                                />
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="flex items-center space-x-2">
                                                <Checkbox id="rate_limiting_enabled" v-model:checked="form.rate_limiting_enabled" />
                                                <Label for="rate_limiting_enabled">Habilitar límites de velocidad</Label>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <Checkbox id="email_backup_enabled" v-model:checked="form.email_backup_enabled" />
                                                <Label for="email_backup_enabled">Respaldar correos</Label>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between border-t pt-6">
                        <Button @click="resetDefaults" type="button" variant="outline">
                            <RotateCcw class="mr-2 h-4 w-4" />
                            Restablecer por Defecto
                        </Button>

                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Guardando...' : 'Guardar Configuración' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
