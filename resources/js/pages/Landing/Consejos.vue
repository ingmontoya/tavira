<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  CheckCircle2,
  Shield,
  Eye,
  TrendingDown,
  Bell,
  FileText,
  Users,
  Smartphone,
  ChevronDown,
  Building2,
  DollarSign,
  AlertTriangle,
  Clock,
  BarChart3,
  CheckCheck,
  X
} from 'lucide-vue-next';

// Form handling
const form = useForm({
  name: '',
  email: '',
  phone: '',
  conjunto_name: '',
  num_units: '',
  role: '',
  message: '',
  lead_source: 'Website - Landing Consejos'
});

const currentSlide = ref(0);
const showContactForm = ref(false);
const isSubmitting = ref(false);
const showSuccessMessage = ref(false);

// Slide content
const slides = [
  {
    id: 1,
    title: '¿Su Consejo Tiene Control Real?',
    subtitle: 'Como miembros del consejo, ustedes tienen responsabilidad legal y financiera sobre las decisiones del conjunto...',
    question: '¿Pero realmente saben qué está pasando?',
    icon: Shield,
    color: 'from-blue-600 to-indigo-600'
  },
  {
    id: 2,
    title: 'La Realidad Actual',
    subtitle: 'Lo Que NO Están Viendo Hoy',
    problems: [
      'Dependen 100% de lo que el administrador les dice',
      'No saben si pagan precios justos a proveedores',
      'Se enteran tarde de problemas de cartera',
      'Sin trazabilidad de decisiones y gastos',
      'Reaccionan a emergencias que pudieron prevenirse'
    ],
    note: 'No es culpa del administrador. Es falta de herramientas de supervisión.',
    icon: AlertTriangle,
    color: 'from-orange-600 to-red-600'
  },
  {
    id: 3,
    title: 'Transparencia Total',
    subtitle: 'Todo Visible. Todo el Tiempo.',
    features: [
      {
        icon: BarChart3,
        title: 'Estado Financiero en Tiempo Real',
        items: ['Cartera al día vs vencida', 'Gastos del mes con comprobantes', 'Comparación con presupuesto']
      },
      {
        icon: FileText,
        title: 'Gestión Operativa Completa',
        items: ['Cada mantenimiento documentado', 'PQRs con tiempos de respuesta', 'Contratos y proveedores activos']
      },
      {
        icon: Eye,
        title: 'Auditoría Automática',
        items: ['Cada decisión registrada', 'Cada peso justificado', 'Todo exportable para asambleas']
      }
    ],
    color: 'from-green-600 to-emerald-600'
  },
  {
    id: 4,
    title: 'Control de Costos',
    subtitle: 'Termine con los Sobrecostos Ocultos',
    example: {
      title: 'Pintura de fachada - 3 cotizaciones instantáneas',
      quotes: [
        { provider: 'Proveedor A', amount: '$12M', isLowest: true },
        { provider: 'Proveedor B', amount: '$18M', isLowest: false },
        { provider: 'Proveedor C', amount: '$15M', isLowest: false }
      ],
      savings: '$6M'
    },
    benefits: [
      'Proveedores verificados y calificados',
      'Historial de precios de la zona',
      'Por fin sabrán si están pagando precios justos'
    ],
    icon: TrendingDown,
    color: 'from-purple-600 to-pink-600'
  },
  {
    id: 5,
    title: 'La Red Tavira',
    subtitle: 'Sepa lo que Pasa ANTES que Llegue',
    alert: {
      time: '10:32 AM',
      source: 'Conjunto Villa Sol (3 cuadras)',
      message: 'Intento de robo, sospechosos en moto roja'
    },
    comparison: {
      without: 'Se enteran mañana... o nunca',
      with: [
        'Alerta inmediata al consejo',
        'Seguridad reforzada preventivamente',
        'Residentes informados',
        'Incidente evitado'
      ]
    },
    network: '500+ conjuntos protegiéndose mutuamente',
    icon: Bell,
    color: 'from-red-600 to-orange-600'
  }
];

const benefits = [
  {
    icon: CheckCircle2,
    title: 'Cumplimiento Legal',
    description: 'Ley 675 - Todo documentado y en orden'
  },
  {
    icon: Users,
    title: 'Portal para Propietarios',
    description: 'App móvil con transparencia total'
  },
  {
    icon: Clock,
    title: 'Implementación Rápida',
    description: 'En 5 días con capacitación incluida'
  },
  {
    icon: DollarSign,
    title: 'Inversión Inteligente',
    description: 'Desde $2.327/mes por apartamento'
  }
];

const faqs = [
  {
    question: '¿El administrador querrá esto?',
    answer: 'Un buen administrador valora la transparencia. Tavira lo protege y facilita su trabajo con herramientas profesionales.'
  },
  {
    question: '¿Es muy caro?',
    answer: 'Menos de $2.500/mes por unidad. Un solo sobrecosto detectado paga la inversión del año completo.'
  },
  {
    question: '¿Pueden ver todo en tiempo real?',
    answer: 'Sí. Dashboard 24/7 con estado financiero, cartera, gastos, mantenimientos y más. Acceso desde cualquier dispositivo.'
  },
  {
    question: '¿Necesitamos consultarlo en asamblea?',
    answer: 'Ofrecemos el primer mes gratis para que el consejo lo pruebe y presente resultados en la asamblea.'
  }
];

const nextSlide = () => {
  if (currentSlide.value < slides.length - 1) {
    currentSlide.value++;
  } else {
    showContactForm.value = true;
  }
};

const prevSlide = () => {
  if (currentSlide.value > 0) {
    currentSlide.value--;
  }
};

const goToSlide = (index: number) => {
  currentSlide.value = index;
};

const submitForm = () => {
  form.post(route('landing.consejos.submit'), {
    onSuccess: () => {
      showSuccessMessage.value = true;
      form.reset();
      setTimeout(() => {
        showSuccessMessage.value = false;
      }, 5000);
    },
    onError: () => {
      // Error handling
    }
  });
};

const openContactForm = () => {
  showContactForm.value = true;
  // Scroll to form
  setTimeout(() => {
    document.getElementById('contact-form')?.scrollIntoView({ behavior: 'smooth' });
  }, 100);
};
</script>

<template>
  <Head title="Tavira - Control y Transparencia para su Consejo" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <Building2 class="h-8 w-8 text-indigo-600" />
            <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
              Tavira
            </span>
          </div>
          <Button @click="openContactForm" size="lg" class="bg-indigo-600 hover:bg-indigo-700">
            Solicitar Demo
          </Button>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4">
      <div class="container mx-auto max-w-6xl">
        <div class="text-center mb-16">
          <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 bg-clip-text text-transparent">
            Control Total para su Tranquilidad
          </h1>
          <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto">
            Transparencia y supervisión en tiempo real para consejos de administración responsables
          </p>
          <Button @click="openContactForm" size="lg" class="bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-lg px-8 py-6">
            Descubra lo que NO está viendo hoy
          </Button>
        </div>

        <!-- Slides -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-12">
          <div class="relative">
            <!-- Slide Content -->
            <div class="min-h-[500px] p-8 md:p-12">
              <!-- Slide 1 -->
              <div v-if="currentSlide === 0" class="space-y-6 animate-in fade-in duration-500">
                <div class="flex items-center justify-center mb-8">
                  <div :class="`w-20 h-20 rounded-full bg-gradient-to-br ${slides[0].color} flex items-center justify-center`">
                    <component :is="slides[0].icon" class="h-10 w-10 text-white" />
                  </div>
                </div>
                <h2 class="text-4xl font-bold text-center mb-4">{{ slides[0].title }}</h2>
                <p class="text-xl text-gray-600 text-center mb-6">{{ slides[0].subtitle }}</p>
                <p class="text-2xl font-semibold text-center text-indigo-600">{{ slides[0].question }}</p>
              </div>

              <!-- Slide 2 -->
              <div v-if="currentSlide === 1" class="space-y-6 animate-in fade-in duration-500">
                <div class="flex items-center justify-center mb-8">
                  <div :class="`w-20 h-20 rounded-full bg-gradient-to-br ${slides[1].color} flex items-center justify-center`">
                    <component :is="slides[1].icon" class="h-10 w-10 text-white" />
                  </div>
                </div>
                <h2 class="text-4xl font-bold text-center mb-4">{{ slides[1].title }}</h2>
                <p class="text-xl text-gray-600 text-center mb-8">{{ slides[1].subtitle }}</p>
                <div class="space-y-4 max-w-2xl mx-auto">
                  <div v-for="(problem, index) in slides[1].problems" :key="index" class="flex items-start space-x-3 p-4 bg-red-50 rounded-lg">
                    <X class="h-6 w-6 text-red-600 flex-shrink-0 mt-1" />
                    <span class="text-lg text-gray-700">{{ problem }}</span>
                  </div>
                </div>
                <p class="text-center text-gray-600 italic mt-8">{{ slides[1].note }}</p>
              </div>

              <!-- Slide 3 -->
              <div v-if="currentSlide === 2" class="space-y-8 animate-in fade-in duration-500">
                <h2 class="text-4xl font-bold text-center mb-4">{{ slides[2].title }}</h2>
                <p class="text-xl text-gray-600 text-center mb-8">{{ slides[2].subtitle }}</p>
                <div class="grid md:grid-cols-3 gap-6">
                  <div v-for="(feature, index) in slides[2].features" :key="index" class="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-xl border border-green-200">
                    <div class="flex items-center space-x-3 mb-4">
                      <component :is="feature.icon" class="h-8 w-8 text-green-600" />
                      <h3 class="font-bold text-lg">{{ feature.title }}</h3>
                    </div>
                    <ul class="space-y-2">
                      <li v-for="(item, idx) in feature.items" :key="idx" class="flex items-start space-x-2">
                        <CheckCircle2 class="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span class="text-gray-700">{{ item }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Slide 4 -->
              <div v-if="currentSlide === 3" class="space-y-6 animate-in fade-in duration-500">
                <div class="flex items-center justify-center mb-8">
                  <div :class="`w-20 h-20 rounded-full bg-gradient-to-br ${slides[3].color} flex items-center justify-center`">
                    <component :is="slides[3].icon" class="h-10 w-10 text-white" />
                  </div>
                </div>
                <h2 class="text-4xl font-bold text-center mb-4">{{ slides[3].title }}</h2>
                <p class="text-xl text-gray-600 text-center mb-8">{{ slides[3].subtitle }}</p>

                <div class="max-w-2xl mx-auto">
                  <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-8 rounded-xl border border-purple-200">
                    <h3 class="text-xl font-bold mb-6 text-center">{{ slides[3].example.title }}</h3>
                    <div class="space-y-3 mb-6">
                      <div v-for="(quote, index) in slides[3].example.quotes" :key="index"
                           :class="`flex items-center justify-between p-4 rounded-lg ${quote.isLowest ? 'bg-green-100 border-2 border-green-500' : 'bg-white'}`">
                        <span class="font-semibold">{{ quote.provider }}</span>
                        <span :class="`text-lg font-bold ${quote.isLowest ? 'text-green-700' : 'text-gray-700'}`">{{ quote.amount }}</span>
                      </div>
                    </div>
                    <div class="bg-green-600 text-white p-4 rounded-lg text-center">
                      <p class="text-lg font-bold">✅ Ahorro identificado: {{ slides[3].example.savings }}</p>
                    </div>
                  </div>
                  <div class="mt-6 space-y-2">
                    <div v-for="(benefit, index) in slides[3].benefits" :key="index" class="flex items-center space-x-2">
                      <CheckCheck class="h-5 w-5 text-green-600" />
                      <span class="text-gray-700">{{ benefit }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Slide 5 -->
              <div v-if="currentSlide === 4" class="space-y-6 animate-in fade-in duration-500">
                <div class="flex items-center justify-center mb-8">
                  <div :class="`w-20 h-20 rounded-full bg-gradient-to-br ${slides[4].color} flex items-center justify-center`">
                    <component :is="slides[4].icon" class="h-10 w-10 text-white" />
                  </div>
                </div>
                <h2 class="text-4xl font-bold text-center mb-4">{{ slides[4].title }}</h2>
                <p class="text-xl text-gray-600 text-center mb-8">{{ slides[4].subtitle }}</p>

                <div class="max-w-2xl mx-auto">
                  <div class="bg-gradient-to-br from-red-50 to-orange-50 p-6 rounded-xl border-2 border-red-300 mb-6">
                    <div class="flex items-start space-x-4">
                      <Bell class="h-8 w-8 text-red-600 flex-shrink-0 animate-pulse" />
                      <div>
                        <p class="text-sm text-gray-600 mb-1">{{ slides[4].alert.time }}</p>
                        <p class="font-bold text-lg mb-2">Alerta de {{ slides[4].alert.source }}</p>
                        <p class="text-gray-700">{{ slides[4].alert.message }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-100 p-6 rounded-xl">
                      <h4 class="font-bold text-red-600 mb-3">❌ Sin Tavira</h4>
                      <p class="text-gray-700">{{ slides[4].comparison.without }}</p>
                    </div>
                    <div class="bg-green-50 p-6 rounded-xl border border-green-200">
                      <h4 class="font-bold text-green-600 mb-3">✅ Con Tavira</h4>
                      <ul class="space-y-2">
                        <li v-for="(item, index) in slides[4].comparison.with" :key="index" class="flex items-start space-x-2">
                          <CheckCircle2 class="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                          <span class="text-gray-700 text-sm">{{ item }}</span>
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div class="text-center">
                    <p class="text-xl font-bold text-indigo-600">{{ slides[4].network }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between p-6 bg-gray-50 border-t">
              <Button
                @click="prevSlide"
                variant="outline"
                :disabled="currentSlide === 0"
                class="flex items-center space-x-2"
              >
                <span>← Anterior</span>
              </Button>

              <div class="flex items-center space-x-2">
                <button
                  v-for="(slide, index) in slides"
                  :key="slide.id"
                  @click="goToSlide(index)"
                  :class="`w-3 h-3 rounded-full transition-all ${currentSlide === index ? 'bg-indigo-600 w-8' : 'bg-gray-300 hover:bg-gray-400'}`"
                />
              </div>

              <Button
                @click="nextSlide"
                class="bg-indigo-600 hover:bg-indigo-700 flex items-center space-x-2"
              >
                <span>{{ currentSlide === slides.length - 1 ? 'Contactar' : 'Siguiente →' }}</span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 px-4 bg-white">
      <div class="container mx-auto max-w-6xl">
        <h2 class="text-4xl font-bold text-center mb-12">¿Por Qué Consejos Líderes Eligen Tavira?</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div v-for="(benefit, index) in benefits" :key="index" class="text-center p-6 rounded-xl bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-200">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-indigo-600 flex items-center justify-center">
              <component :is="benefit.icon" class="h-8 w-8 text-white" />
            </div>
            <h3 class="text-xl font-bold mb-2">{{ benefit.title }}</h3>
            <p class="text-gray-600">{{ benefit.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQs Section -->
    <section class="py-20 px-4 bg-gradient-to-br from-slate-50 to-indigo-50">
      <div class="container mx-auto max-w-4xl">
        <h2 class="text-4xl font-bold text-center mb-12">Preguntas Frecuentes</h2>
        <div class="space-y-4">
          <div v-for="(faq, index) in faqs" :key="index" class="bg-white p-6 rounded-xl shadow-md">
            <h3 class="text-xl font-bold mb-3 text-indigo-600">{{ faq.question }}</h3>
            <p class="text-gray-600">{{ faq.answer }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section id="contact-form" class="py-20 px-4 bg-white">
      <div class="container mx-auto max-w-2xl">
        <div class="bg-gradient-to-br from-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 md:p-12 text-white">
          <div class="text-center mb-8">
            <h2 class="text-4xl font-bold mb-4">Solicite una Demostración Privada</h2>
            <p class="text-xl opacity-90">Descubra en 15 minutos lo que su consejo no está viendo hoy</p>
          </div>

          <div v-if="showSuccessMessage" class="mb-6 p-4 bg-green-500 rounded-lg flex items-center space-x-3">
            <CheckCircle2 class="h-6 w-6" />
            <p class="font-semibold">¡Gracias! Un asesor se contactará pronto con usted.</p>
          </div>

          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <Label for="name" class="text-white mb-2 block">Nombre Completo *</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  required
                  class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                  placeholder="Juan Pérez"
                />
              </div>
              <div>
                <Label for="email" class="text-white mb-2 block">Email *</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                  placeholder="juan@ejemplo.com"
                />
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <Label for="phone" class="text-white mb-2 block">Teléfono *</Label>
                <Input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  required
                  class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                  placeholder="+57 300 123 4567"
                />
              </div>
              <div>
                <Label for="role" class="text-white mb-2 block">Cargo en el Consejo *</Label>
                <Input
                  id="role"
                  v-model="form.role"
                  required
                  class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                  placeholder="Presidente, Tesorero, etc."
                />
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <Label for="conjunto_name" class="text-white mb-2 block">Nombre del Conjunto *</Label>
                <Input
                  id="conjunto_name"
                  v-model="form.conjunto_name"
                  required
                  class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                  placeholder="Conjunto Residencial..."
                />
              </div>
              <div>
                <Label for="num_units" class="text-white mb-2 block">Número de Unidades *</Label>
                <Input
                  id="num_units"
                  v-model="form.num_units"
                  type="number"
                  required
                  class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                  placeholder="150"
                />
              </div>
            </div>

            <div>
              <Label for="message" class="text-white mb-2 block">Mensaje (Opcional)</Label>
              <Textarea
                id="message"
                v-model="form.message"
                rows="4"
                class="bg-white/10 border-white/20 text-white placeholder:text-white/60"
                placeholder="Cuéntenos sobre los desafíos actuales de su conjunto..."
              />
            </div>

            <Button
              type="submit"
              size="lg"
              class="w-full bg-white text-indigo-600 hover:bg-gray-100 text-lg py-6"
              :disabled="form.processing"
            >
              <span v-if="form.processing">Enviando...</span>
              <span v-else>Solicitar Demostración Ahora</span>
            </Button>

            <p class="text-sm text-center text-white/80">
              * Primer mes GRATIS • Sin compromisos • Cancele cuando quiera
            </p>
          </form>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20 px-4 bg-gradient-to-br from-indigo-900 to-blue-900 text-white">
      <div class="container mx-auto max-w-4xl text-center">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">
          La Pregunta No Es SI Implementar Transparencia...
        </h2>
        <p class="text-2xl mb-8 opacity-90">
          Es CUÁNDO Empezar a Proteger su Conjunto
        </p>
        <Button @click="openContactForm" size="lg" class="bg-white text-indigo-600 hover:bg-gray-100 text-xl px-12 py-8">
          Agendar Demo Privada para el Consejo
        </Button>
        <div class="mt-12 flex items-center justify-center space-x-8 text-sm opacity-75">
          <div class="flex items-center space-x-2">
            <CheckCircle2 class="h-5 w-5" />
            <span>Primer mes gratis</span>
          </div>
          <div class="flex items-center space-x-2">
            <CheckCircle2 class="h-5 w-5" />
            <span>Sin compromisos</span>
          </div>
          <div class="flex items-center space-x-2">
            <CheckCircle2 class="h-5 w-5" />
            <span>Soporte prioritario</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-12 px-4">
      <div class="container mx-auto max-w-6xl">
        <div class="grid md:grid-cols-3 gap-8 mb-8">
          <div>
            <div class="flex items-center space-x-3 mb-4">
              <Building2 class="h-8 w-8 text-indigo-400" />
              <span class="text-2xl font-bold">Tavira</span>
            </div>
            <p class="text-gray-400">Control y transparencia para residenciales responsables</p>
          </div>
          <div>
            <h3 class="font-bold mb-4">Contacto</h3>
            <div class="space-y-2 text-gray-400">
              <p>📧 consejo@tavira.com.co</p>
              <p>📱 WhatsApp: +57 300 123 4567</p>
            </div>
          </div>
          <div>
            <h3 class="font-bold mb-4">Oferta Especial</h3>
            <p class="text-gray-400">Primeros 50 conjuntos:</p>
            <p class="text-indigo-400 font-semibold">✓ Primer mes GRATIS</p>
            <p class="text-indigo-400 font-semibold">✓ Capacitación exclusiva</p>
          </div>
        </div>
        <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
          <p>&copy; 2025 Tavira. Todos los derechos reservados.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-in {
  animation: fade-in 0.5s ease-out;
}
</style>
