<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { AlertCircle, Eye, EyeOff, Lock, RotateCcw, Shield, ShieldCheck, ShieldX } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

interface SecuritySettings {
    [key: string]: any;
}

interface SecurityLevel {
    name: string;
    description: string;
    settings: Record<string, any>;
}

interface Props {
    settings: SecuritySettings;
    securityLevels: Record<string, SecurityLevel>;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: '/settings/security',
    },
];

const form = useForm(props.settings);
const levelForm = useForm({
    level: '',
});

const showAdvanced = ref(false);
const activeTab = ref('authentication');

const currentSecurityLevel = computed(() => {
    const levels = props.securityLevels;
    for (const [key, level] of Object.entries(levels)) {
        const matches = Object.entries(level.settings).every(([settingKey, settingValue]) => {
            return form[settingKey] === settingValue;
        });
        if (matches) {
            return { key, ...level };
        }
    }
    return null;
});

const getSecurityScore = computed(() => {
    let score = 0;
    let total = 0;

    // Rate limiting score
    if (form.rate_limits_auth_attempts <= 3) score += 10;
    else if (form.rate_limits_auth_attempts <= 5) score += 8;
    else if (form.rate_limits_auth_attempts <= 10) score += 5;
    total += 10;

    // Password policy score
    if (form.password_min_length >= 12) score += 10;
    else if (form.password_min_length >= 8) score += 7;
    else if (form.password_min_length >= 6) score += 4;
    total += 10;

    if (form.password_require_uppercase) score += 5;
    if (form.password_require_lowercase) score += 5;
    if (form.password_require_numbers) score += 5;
    if (form.password_require_symbols) score += 5;
    total += 20;

    // Two-factor authentication
    if (form.two_factor_enabled) score += 15;
    total += 15;

    // File upload security
    if (form.uploads_scan_for_malware) score += 8;
    if (form.uploads_quarantine_suspicious_files) score += 7;
    total += 15;

    // Audit logging
    if (form.audit_enabled) score += 5;
    if (form.audit_log_requests) score += 5;
    total += 10;

    // Input sanitization
    if (form.sanitization_enabled) score += 5;
    if (form.sanitization_block_suspicious_input) score += 5;
    total += 10;

    // Session security
    if (form.session_absolute_timeout <= 240) score += 5;
    else if (form.session_absolute_timeout <= 480) score += 3;
    total += 5;

    return Math.round((score / total) * 100);
});

const getSecurityBadge = computed(() => {
    const score = getSecurityScore.value;
    if (score >= 90) return { color: 'bg-green-100 text-green-800', text: 'Excelente', icon: ShieldCheck };
    if (score >= 75) return { color: 'bg-blue-100 text-blue-800', text: 'Buena', icon: Shield };
    if (score >= 60) return { color: 'bg-yellow-100 text-yellow-800', text: 'Media', icon: Shield };
    if (score >= 40) return { color: 'bg-orange-100 text-orange-800', text: 'Baja', icon: AlertCircle };
    return { color: 'bg-red-100 text-red-800', text: 'Crítica', icon: ShieldX };
});

const updateSettings = () => {
    form.post(route('security.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Settings updated successfully
        },
    });
};

const applySecurityLevel = (level: string) => {
    levelForm.level = level;
    levelForm.post(route('security.apply-level'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reload the page to reflect new settings
            window.location.reload();
        },
    });
};

const resetToDefaults = () => {
    if (confirm('¿Estás seguro de que deseas restaurar la configuración de seguridad a los valores predeterminados?')) {
        form.post(route('security.reset-defaults'), {
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};

const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const tabs = [
    { id: 'authentication', label: 'Autenticación' },
    { id: 'rate-limits', label: 'Límites de Velocidad' },
    { id: 'uploads', label: 'Cargas de Archivos' },
    { id: 'audit', label: 'Auditoría' },
    { id: 'session', label: 'Sesiones' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configuración de Seguridad" />

        <SettingsLayout>
            <div class="max-w-none space-y-8">
                <HeadingSmall title="Configuración de Seguridad" description="Configura los ajustes de seguridad de tu aplicación" />

                <!-- Security Overview -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Shield class="h-5 w-5" />
                            Resumen de Seguridad
                        </CardTitle>
                        <CardDescription> Configuración actual de seguridad y recomendaciones </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="rounded-lg bg-blue-50 p-4 text-center">
                                <div class="mb-2 text-3xl font-bold text-blue-600">{{ getSecurityScore }}%</div>
                                <div class="text-sm text-gray-600">Puntuación de Seguridad</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 text-center">
                                <div class="mb-2">
                                    <Badge :class="getSecurityBadge.color" class="inline-flex items-center gap-1 px-3 py-1">
                                        <component :is="getSecurityBadge.icon" class="h-4 w-4" />
                                        {{ getSecurityBadge.text }}
                                    </Badge>
                                </div>
                                <div class="text-sm text-gray-600">Nivel de Seguridad</div>
                            </div>
                            <div class="rounded-lg bg-green-50 p-4 text-center">
                                <div class="mb-2 text-xl font-bold text-green-600" v-if="currentSecurityLevel">
                                    {{ currentSecurityLevel.name }}
                                </div>
                                <div class="mb-2 text-xl font-bold text-gray-600" v-else>Personalizado</div>
                                <div class="text-sm text-gray-600">Configuración</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Security Levels -->
                <Card>
                    <CardHeader>
                        <CardTitle>Niveles de Seguridad</CardTitle>
                        <CardDescription> Aplica configuraciones de seguridad predefinidas para diferentes entornos </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div v-for="(level, key) in securityLevels" :key="key" class="rounded-lg border p-6 transition-shadow hover:shadow-md">
                                <div class="mb-3 flex items-start justify-between">
                                    <h3 class="text-lg font-semibold">{{ level.name }}</h3>
                                    <Badge v-if="currentSecurityLevel?.key === key" class="bg-blue-100 px-2 py-1 text-blue-800"> Actual </Badge>
                                </div>
                                <p class="mb-6 min-h-[60px] text-sm leading-relaxed text-gray-600">{{ level.description }}</p>
                                <Button @click="applySecurityLevel(key)" :disabled="levelForm.processing" variant="outline" class="w-full">
                                    Aplicar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Advanced Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex flex-wrap items-center justify-between gap-4">
                            <span>Configuración Avanzada</span>
                            <Button @click="showAdvanced = !showAdvanced" variant="outline" size="sm">
                                <component :is="showAdvanced ? EyeOff : Eye" class="mr-2 h-4 w-4" />
                                {{ showAdvanced ? 'Ocultar' : 'Mostrar' }} Avanzada
                            </Button>
                        </CardTitle>
                        <CardDescription> Ajusta configuraciones individuales de seguridad </CardDescription>
                    </CardHeader>
                    <CardContent v-if="showAdvanced">
                        <form @submit.prevent="updateSettings">
                            <!-- Tab Navigation -->
                            <div class="mb-8 border-b border-gray-200">
                                <nav class="flex flex-wrap gap-2 md:gap-8" aria-label="Tabs">
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.id"
                                        type="button"
                                        @click="activeTab = tab.id"
                                        :class="[
                                            activeTab === tab.id
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                            'border-b-2 px-2 py-3 text-sm font-medium whitespace-nowrap',
                                        ]"
                                    >
                                        {{ tab.label }}
                                    </button>
                                </nav>
                            </div>

                            <!-- Tab Content -->
                            <div v-if="activeTab === 'authentication'" class="space-y-8">
                                <div class="rounded-lg bg-gray-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Políticas de Contraseña</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="password_min_length">Longitud Mínima de Contraseña</Label>
                                            <Input
                                                id="password_min_length"
                                                type="number"
                                                v-model="form.password_min_length"
                                                :min="4"
                                                :max="128"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="password_max_age_days">Edad Máxima de Contraseña (días)</Label>
                                            <Input
                                                id="password_max_age_days"
                                                type="number"
                                                v-model="form.password_max_age_days"
                                                :min="1"
                                                :max="365"
                                                class="w-full"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-gray-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Requisitos de Contraseña</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="password_require_uppercase" v-model:checked="form.password_require_uppercase" />
                                            <Label for="password_require_uppercase" class="text-sm">Requerir Mayúsculas (A-Z)</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="password_require_lowercase" v-model:checked="form.password_require_lowercase" />
                                            <Label for="password_require_lowercase" class="text-sm">Requerir Minúsculas (a-z)</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="password_require_numbers" v-model:checked="form.password_require_numbers" />
                                            <Label for="password_require_numbers" class="text-sm">Requerir Números (0-9)</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="password_require_symbols" v-model:checked="form.password_require_symbols" />
                                            <Label for="password_require_symbols" class="text-sm">Requerir Símbolos (!@#$%)</Label>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-blue-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Autenticación de Dos Factores</h3>
                                    <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                        <Checkbox id="two_factor_enabled" v-model:checked="form.two_factor_enabled" />
                                        <Label for="two_factor_enabled" class="text-sm">Habilitar Autenticación de Dos Factores</Label>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'rate-limits'" class="space-y-8">
                                <div class="rounded-lg bg-yellow-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Límites de Autenticación</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="rate_limits_auth_attempts">Intentos de Autenticación (por minuto)</Label>
                                            <Input
                                                id="rate_limits_auth_attempts"
                                                type="number"
                                                v-model="form.rate_limits_auth_attempts"
                                                :min="1"
                                                :max="50"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-gray-600">Número máximo de intentos de login por minuto</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="rate_limits_default_attempts">Intentos por Defecto (por minuto)</Label>
                                            <Input
                                                id="rate_limits_default_attempts"
                                                type="number"
                                                v-model="form.rate_limits_default_attempts"
                                                :min="1"
                                                :max="1000"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-gray-600">Límite general para todas las rutas</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-gray-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Límites de API y Cargas</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="rate_limits_api_attempts">Intentos de API (por minuto)</Label>
                                            <Input
                                                id="rate_limits_api_attempts"
                                                type="number"
                                                v-model="form.rate_limits_api_attempts"
                                                :min="1"
                                                :max="1000"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-gray-600">Límite para rutas de API</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="rate_limits_upload_attempts">Intentos de Carga (por minuto)</Label>
                                            <Input
                                                id="rate_limits_upload_attempts"
                                                type="number"
                                                v-model="form.rate_limits_upload_attempts"
                                                :min="1"
                                                :max="100"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-gray-600">Límite para cargas de archivos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'uploads'" class="space-y-8">
                                <div class="rounded-lg bg-purple-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Configuración de Archivos</h3>
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="uploads_max_file_size">Tamaño Máximo de Archivo (bytes)</Label>
                                            <Input
                                                id="uploads_max_file_size"
                                                type="number"
                                                v-model="form.uploads_max_file_size"
                                                :min="1024"
                                                :max="104857600"
                                                class="w-full"
                                            />
                                            <p class="mt-2 rounded bg-blue-100 p-2 text-sm text-gray-600">
                                                Tamaño actual: {{ formatBytes(form.uploads_max_file_size) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-red-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Seguridad de Archivos</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="uploads_scan_for_malware" v-model:checked="form.uploads_scan_for_malware" />
                                            <Label for="uploads_scan_for_malware" class="text-sm">Escanear por Malware</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox
                                                id="uploads_quarantine_suspicious_files"
                                                v-model:checked="form.uploads_quarantine_suspicious_files"
                                            />
                                            <Label for="uploads_quarantine_suspicious_files" class="text-sm"
                                                >Cuarentena de Archivos Sospechosos</Label
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'audit'" class="space-y-8">
                                <div class="rounded-lg bg-green-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Configuración de Auditoría</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="audit_enabled" v-model:checked="form.audit_enabled" />
                                            <Label for="audit_enabled" class="text-sm">Habilitar Registro de Auditoría</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="audit_log_requests" v-model:checked="form.audit_log_requests" />
                                            <Label for="audit_log_requests" class="text-sm">Registrar Solicitudes HTTP</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="audit_log_responses" v-model:checked="form.audit_log_responses" />
                                            <Label for="audit_log_responses" class="text-sm">Registrar Respuestas HTTP</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="sanitization_enabled" v-model:checked="form.sanitization_enabled" />
                                            <Label for="sanitization_enabled" class="text-sm">Habilitar Sanitización de Entrada</Label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'session'" class="space-y-8">
                                <div class="rounded-lg bg-indigo-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Configuración de Sesiones</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="session_absolute_timeout">Tiempo de Sesión (minutos)</Label>
                                            <Input
                                                id="session_absolute_timeout"
                                                type="number"
                                                v-model="form.session_absolute_timeout"
                                                :min="5"
                                                :max="1440"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-gray-600">Tiempo máximo de inactividad</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="session_timeout_warning">Advertencia de Tiempo (minutos)</Label>
                                            <Input
                                                id="session_timeout_warning"
                                                type="number"
                                                v-model="form.session_timeout_warning"
                                                :min="1"
                                                :max="60"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-gray-600">Minutos antes de mostrar advertencia</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-gray-50 p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Seguridad de Sesiones</h3>
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="session_secure_cookie" v-model:checked="form.session_secure_cookie" />
                                            <Label for="session_secure_cookie" class="text-sm">Cookies Seguras (HTTPS)</Label>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded border bg-white p-3">
                                            <Checkbox id="session_regenerate_on_auth" v-model:checked="form.session_regenerate_on_auth" />
                                            <Label for="session_regenerate_on_auth" class="text-sm">Regenerar en Autenticación</Label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 flex flex-col gap-4 border-t pt-6 sm:flex-row sm:justify-between">
                                <Button @click="resetToDefaults" variant="outline" :disabled="form.processing" type="button" class="w-full sm:w-auto">
                                    <RotateCcw class="mr-2 h-4 w-4" />
                                    Restaurar Valores Predeterminados
                                </Button>

                                <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                                    <Lock class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Guardar Configuración' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
