<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Building2, CheckCircle, Mail, Phone, User, AlertCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProviderCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
}

interface Props {
    categories: ProviderCategory[];
}

const props = defineProps<Props>();

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    service_type: '',
    description: '',
    category_ids: [] as number[],
});

const toggleCategory = (categoryId: number) => {
    const index = form.category_ids.indexOf(categoryId);
    if (index > -1) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(categoryId);
    }
};

const isCategorySelected = (categoryId: number) => {
    return form.category_ids.includes(categoryId);
};

const submit = () => {
    form.post(route('provider-register.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Registro de Proveedores - Tavira" />

    <div class="min-h-screen bg-gradient-to-br from-[#1D3557] via-[#457B9D] to-[#06D6A0]">
        <!-- Navigation -->
        <nav class="relative z-50 bg-[#1D3557]/80 backdrop-blur-sm px-6 py-4">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link :href="route('home')" class="flex items-center">
                    <span class="text-2xl font-bold text-white">Tavira</span>
                </Link>
                <div class="flex items-center space-x-4">
                    <Link :href="route('login')" class="text-white/80 hover:text-white transition-colors">
                        Iniciar Sesión
                    </Link>
                    <Link :href="route('register')">
                        <Button variant="outline" class="bg-white/10 text-white border-white/20 hover:bg-white/20">
                            Registro Clientes
                        </Button>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        ¿Eres un Proveedor de Servicios?
                    </h1>
                    <p class="text-xl text-white/80 mb-8">
                        Conecta con cientos de conjuntos residenciales y aumenta tu cartera de clientes
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Benefits Section -->
                    <div class="space-y-6">
                        <Card class="bg-white/10 backdrop-blur-lg border-white/20">
                            <CardHeader>
                                <CardTitle class="text-white text-2xl">Beneficios de Unirte</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="w-6 h-6 text-[#06D6A0] flex-shrink-0 mt-1" />
                                    <div>
                                        <h3 class="text-white font-semibold">Visibilidad Aumentada</h3>
                                        <p class="text-white/70 text-sm">Accede a una red de conjuntos residenciales buscando servicios de calidad</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="w-6 h-6 text-[#06D6A0] flex-shrink-0 mt-1" />
                                    <div>
                                        <h3 class="text-white font-semibold">Gestión Simplificada</h3>
                                        <p class="text-white/70 text-sm">Administra tus servicios, cotizaciones y pagos desde una sola plataforma</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="w-6 h-6 text-[#06D6A0] flex-shrink-0 mt-1" />
                                    <div>
                                        <h3 class="text-white font-semibold">Pagos Seguros</h3>
                                        <p class="text-white/70 text-sm">Sistema de pagos confiable y transparente</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="w-6 h-6 text-[#06D6A0] flex-shrink-0 mt-1" />
                                    <div>
                                        <h3 class="text-white font-semibold">Sin Comisiones Iniciales</h3>
                                        <p class="text-white/70 text-sm">Regístrate gratis y empieza a recibir solicitudes de servicios</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="bg-white/10 backdrop-blur-lg border-white/20">
                            <CardHeader>
                                <CardTitle class="text-white">Servicios que Buscamos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-3 text-white/80 text-sm">
                                    <div>• Plomería</div>
                                    <div>• Electricidad</div>
                                    <div>• Jardinería</div>
                                    <div>• Pintura</div>
                                    <div>• Construcción</div>
                                    <div>• Limpieza</div>
                                    <div>• Seguridad</div>
                                    <div>• Mantenimiento</div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Registration Form -->
                    <Card class="bg-white shadow-2xl">
                        <CardHeader>
                            <CardTitle class="text-2xl">Registro de Proveedor</CardTitle>
                            <CardDescription>
                                Completa el formulario y nuestro equipo se pondrá en contacto contigo
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Flash Messages -->
                            <div v-if="flashSuccess" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start space-x-3">
                                <CheckCircle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                                <p class="text-sm text-green-800">{{ flashSuccess }}</p>
                            </div>
                            <div v-if="flashError" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3">
                                <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                <p class="text-sm text-red-800">{{ flashError }}</p>
                            </div>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="company_name">Nombre de la Empresa *</Label>
                                    <div class="relative">
                                        <Building2 class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="company_name"
                                            v-model="form.company_name"
                                            type="text"
                                            required
                                            placeholder="Ej: Servicios ProMantenimiento"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="contact_name">Nombre de Contacto *</Label>
                                    <div class="relative">
                                        <User class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="contact_name"
                                            v-model="form.contact_name"
                                            type="text"
                                            required
                                            placeholder="Tu nombre completo"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="email">Correo Electrónico *</Label>
                                    <div class="relative">
                                        <Mail class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            required
                                            placeholder="correo@empresa.com"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="phone">Teléfono *</Label>
                                    <div class="relative">
                                        <Phone class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="phone"
                                            v-model="form.phone"
                                            type="tel"
                                            required
                                            placeholder="300 123 4567"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <Label>Categorías de Servicio *</Label>
                                        <p class="text-sm text-muted-foreground">Selecciona todas las categorías que apliquen</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 max-h-64 overflow-y-auto p-4 border rounded-lg bg-muted/30">
                                        <div
                                            v-for="category in categories"
                                            :key="category.id"
                                            class="flex items-start space-x-3 p-2 rounded hover:bg-muted/50 cursor-pointer transition-colors"
                                            @click="toggleCategory(category.id)"
                                        >
                                            <Checkbox
                                                :id="`category-${category.id}`"
                                                :checked="isCategorySelected(category.id)"
                                                @update:checked="() => toggleCategory(category.id)"
                                            />
                                            <div class="flex-1">
                                                <Label
                                                    :for="`category-${category.id}`"
                                                    class="font-medium cursor-pointer"
                                                >
                                                    {{ category.name }}
                                                </Label>
                                                <p v-if="category.description" class="text-xs text-muted-foreground mt-0.5">
                                                    {{ category.description }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.category_ids" class="text-sm text-red-600">
                                        {{ form.errors.category_ids }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="description">Descripción de Servicios</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Cuéntanos sobre tu experiencia y los servicios que ofreces..."
                                        rows="4"
                                    />
                                </div>

                                <Button type="submit" class="w-full bg-[#1D3557] hover:bg-[#2a4870]" :disabled="form.processing">
                                    {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                                </Button>

                                <p class="text-xs text-muted-foreground text-center">
                                    Al enviar este formulario, aceptas que nos comuniquemos contigo sobre tu registro
                                </p>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Footer Note -->
                <div class="mt-12 text-center">
                    <p class="text-white/60 text-sm">
                        ¿Eres un conjunto residencial?
                        <Link :href="route('register')" class="text-[#06D6A0] hover:underline font-medium">
                            Regístrate aquí
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
