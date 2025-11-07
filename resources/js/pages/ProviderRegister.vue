<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, Building2, CheckCircle, Mail, Phone, User } from 'lucide-vue-next';
import { computed } from 'vue';

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

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-tavira-blue to-sky-900">
        <!-- Navigation -->
        <nav class="relative z-50 border-b border-white/10 bg-tavira-blue/95 px-6 py-4 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link :href="route('home')" class="flex items-center">
                    <span class="font-brand text-2xl font-bold text-white">Tavira</span>
                </Link>
                <div class="flex items-center space-x-4">
                    <Link :href="route('login')" class="font-medium text-white transition-colors hover:text-tavira-turquoise"> Iniciar Sesión </Link>
                    <Link :href="route('register')">
                        <Button variant="outline" class="border-tavira-turquoise/50 bg-tavira-turquoise/10 font-medium text-white transition-all hover:bg-tavira-turquoise hover:border-tavira-turquoise"> Registro Clientes </Button>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="container mx-auto px-4 py-12">
            <div class="mx-auto max-w-6xl">
                <div class="mb-12 text-center">
                    <h1 class="font-brand mb-4 text-4xl font-bold text-white md:text-5xl">¿Eres un Proveedor de Servicios?</h1>
                    <p class="mb-8 text-xl text-white/90">Conecta con cientos de conjuntos residenciales y aumenta tu cartera de clientes</p>
                </div>

                <div class="grid gap-8 md:grid-cols-2">
                    <!-- Benefits Section -->
                    <div class="space-y-6">
                        <Card class="border-white/10 bg-slate-800/50 backdrop-blur-lg shadow-2xl">
                            <CardHeader>
                                <CardTitle class="font-brand text-2xl text-white">Beneficios de Unirte</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="mt-1 h-6 w-6 flex-shrink-0 text-tavira-turquoise" />
                                    <div>
                                        <h3 class="font-semibold text-white">Visibilidad Aumentada</h3>
                                        <p class="text-sm text-gray-300">Accede a una red de conjuntos residenciales buscando servicios de calidad</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="mt-1 h-6 w-6 flex-shrink-0 text-tavira-turquoise" />
                                    <div>
                                        <h3 class="font-semibold text-white">Gestión Simplificada</h3>
                                        <p class="text-sm text-gray-300">Administra tus servicios, cotizaciones y pagos desde una sola plataforma</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="mt-1 h-6 w-6 flex-shrink-0 text-tavira-turquoise" />
                                    <div>
                                        <h3 class="font-semibold text-white">Pagos Seguros</h3>
                                        <p class="text-sm text-gray-300">Sistema de pagos confiable y transparente</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <CheckCircle class="mt-1 h-6 w-6 flex-shrink-0 text-tavira-turquoise" />
                                    <div>
                                        <h3 class="font-semibold text-white">Sin Comisiones Iniciales</h3>
                                        <p class="text-sm text-gray-300">Regístrate gratis y empieza a recibir solicitudes de servicios</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-white/10 bg-slate-800/50 backdrop-blur-lg shadow-2xl">
                            <CardHeader>
                                <CardTitle class="font-brand text-white">Servicios que Buscamos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-3 text-sm text-gray-200">
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
                    <Card class="bg-white shadow-2xl border-0 overflow-hidden">
                        <CardHeader class="bg-tavira-blue text-white py-6 px-6">
                            <CardTitle class="font-brand text-2xl text-white mb-2">Registro de Proveedor</CardTitle>
                            <CardDescription class="text-white"> Completa el formulario y nuestro equipo se pondrá en contacto contigo </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Flash Messages -->
                            <div v-if="flashSuccess" class="mb-4 flex items-start space-x-3 rounded-lg border border-tavira-turquoise/30 bg-emerald-50 p-4">
                                <CheckCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-tavira-turquoise" />
                                <p class="text-sm text-emerald-800">{{ flashSuccess }}</p>
                            </div>
                            <div v-if="flashError" class="mb-4 flex items-start space-x-3 rounded-lg border border-tavira-coral/30 bg-red-50 p-4">
                                <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-tavira-coral" />
                                <p class="text-sm text-red-800">{{ flashError }}</p>
                            </div>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="company_name">Nombre de la Empresa *</Label>
                                    <div class="relative">
                                        <Building2 class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
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
                                        <User class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
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
                                        <Mail class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input id="email" v-model="form.email" type="email" required placeholder="correo@empresa.com" class="pl-10" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="phone">Teléfono *</Label>
                                    <div class="relative">
                                        <Phone class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input id="phone" v-model="form.phone" type="tel" required placeholder="300 123 4567" class="pl-10" />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <Label>Categorías de Servicio *</Label>
                                        <p class="text-sm text-muted-foreground">Selecciona todas las categorías que apliquen</p>
                                    </div>
                                    <div class="grid max-h-64 grid-cols-1 gap-3 overflow-y-auto rounded-lg border bg-muted/30 p-4">
                                        <div
                                            v-for="category in categories"
                                            :key="category.id"
                                            class="flex items-start space-x-3 rounded p-2 transition-colors hover:bg-muted/50"
                                        >
                                            <input
                                                :id="`category-${category.id}`"
                                                type="checkbox"
                                                :value="category.id"
                                                :checked="isCategorySelected(category.id)"
                                                @change="toggleCategory(category.id)"
                                                class="mt-1 h-4 w-4 cursor-pointer rounded border-gray-300 text-tavira-blue focus:ring-2 focus:ring-tavira-turquoise"
                                            />
                                            <div class="flex-1">
                                                <label :for="`category-${category.id}`" class="cursor-pointer font-medium">
                                                    {{ category.name }}
                                                </label>
                                                <p v-if="category.description" class="mt-0.5 text-xs text-muted-foreground">
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

                                <Button type="submit" class="w-full bg-tavira-blue hover:bg-slate-700 text-white transition-all shadow-lg hover:shadow-xl font-semibold text-base py-6" :disabled="form.processing">
                                    {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                                </Button>

                                <p class="text-center text-xs text-muted-foreground">
                                    Al enviar este formulario, aceptas que nos comuniquemos contigo sobre tu registro
                                </p>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Footer Note -->
                <div class="mt-12 text-center">
                    <p class="text-base text-gray-200">
                        ¿Eres un conjunto residencial?
                        <Link :href="route('register')" class="font-semibold text-tavira-turquoise underline hover:text-white transition-colors"> Regístrate aquí </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
