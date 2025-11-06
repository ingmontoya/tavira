<template>
  <div class="min-h-screen overflow-x-hidden">
    <!-- Hero Section -->
    <LandingHero />

    <!-- Problem Section -->
    <LandingProblem />

    <!-- Red Tavira Section - Main Differentiator -->
    <LandingRedTavira />

    <!-- Fast Response Section -->
    <LandingFastResponse />

    <!-- Features Overview Section -->
    <LandingFeatures />

    <!-- Platform Benefits Section -->
    <LandingBenefits />

    <!-- Testimonials Section -->
    <LandingTestimonials />

    <!-- Why Tavira Section -->
    <LandingWhyTavira />

    <!-- Detailed Features Section -->
    <LandingDetailedFeatures />

    <!-- How it Works Section -->
    <LandingHowItWorks />

    <!-- Lead Form Section -->
    <LandingLeadForm />
  </div>
</template>

<script setup lang="ts">
const { locale } = useI18n()

const canonicalUrl = computed(() => 'https://tavira.com.co/')

const metaTitle = computed(() =>
  locale.value === 'es'
    ? 'Tavira - La Red de Seguridad Inteligente para Tu Conjunto | 500+ Conjuntos Conectados'
    : 'Tavira - The Intelligent Security Network for Your Complex | 500+ Connected Complexes',
)

const metaDescription = computed(() =>
  locale.value === 'es'
    ? '500+ conjuntos conectados compartiendo información en tiempo real. Red Tavira crea seguridad colectiva. Respuesta Rápida en 30 segundos. Apps iOS/Android, gestión completa y seguridad bancaria.'
    : '500+ connected complexes sharing information in real-time. Tavira Network creates collective security. Fast Response in 30 seconds. iOS/Android apps, complete management and banking security.',
)

const metaKeywords = computed(() =>
  locale.value === 'es'
    ? 'red tavira, red neuronal seguridad, conjuntos conectados colombia, seguridad colectiva, respuesta rápida emergencias, software administración conjuntos, red inteligente seguridad, alertas tiempo real, 500 conjuntos conectados'
    : 'tavira network, neural security network, connected complexes colombia, collective security, fast emergency response, residential complex software, intelligent security network, real-time alerts, 500 connected complexes',
)

// Structured Data - Organization
const organizationSchema = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'Organization',
  name: 'Tavira',
  legalName: 'Tavira SAS',
  url: 'https://tavira.com.co',
  logo: 'https://tavira.com.co/tavira_logo.svg',
  description:
    locale.value === 'es'
      ? 'La Red de Seguridad Inteligente con 500+ conjuntos conectados en Colombia. Red Tavira crea seguridad colectiva compartiendo información en tiempo real.'
      : 'The Intelligent Security Network with 500+ connected complexes in Colombia. Tavira Network creates collective security by sharing information in real-time.',
  address: {
    '@type': 'PostalAddress',
    addressCountry: 'CO',
    addressRegion: 'Colombia',
  },
  contactPoint: {
    '@type': 'ContactPoint',
    telephone: '+44-7447-313219',
    contactType: 'customer service',
    areaServed: 'CO',
    availableLanguage: ['es', 'en'],
  },
  sameAs: ['https://twitter.com/tavira_co', 'https://www.linkedin.com/company/tavira'],
}))

// Structured Data - Software Application
const softwareSchema = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'SoftwareApplication',
  name: 'Tavira',
  applicationCategory: 'BusinessApplication',
  operatingSystem: 'Web, iOS, Android',
  offers: {
    '@type': 'Offer',
    price: '0',
    priceCurrency: 'COP',
    description:
      locale.value === 'es'
        ? 'Prueba gratuita de 30 días sin tarjeta de crédito'
        : '30-day free trial without credit card',
  },
  aggregateRating: {
    '@type': 'AggregateRating',
    ratingValue: '4.8',
    ratingCount: '500',
    bestRating: '5',
  },
  description:
    locale.value === 'es'
      ? 'La Red de Seguridad Inteligente para conjuntos residenciales en Colombia. 500+ conjuntos conectados compartiendo información en tiempo real. Red Tavira crea seguridad colectiva, Respuesta Rápida en 30 segundos, apps móviles iOS/Android y gestión completa.'
      : 'The Intelligent Security Network for residential complexes in Colombia. 500+ connected complexes sharing information in real-time. Tavira Network creates collective security, Fast Response in 30 seconds, iOS/Android mobile apps and complete management.',
  featureList: [
    locale.value === 'es' ? 'Red Tavira - Red Neuronal de Seguridad' : 'Tavira Network - Neural Security Network',
    locale.value === 'es' ? 'Respuesta Rápida en 30 Segundos' : 'Fast Response in 30 Seconds',
    locale.value === 'es' ? '500+ Conjuntos Conectados' : '500+ Connected Complexes',
    locale.value === 'es' ? 'Alertas en Tiempo Real' : 'Real-Time Alerts',
    locale.value === 'es' ? 'Apps Móviles iOS y Android' : 'iOS and Android Mobile Apps',
    locale.value === 'es' ? 'Gestión Financiera y Contabilidad' : 'Financial Management and Accounting',
    locale.value === 'es' ? 'Portería Digital con Citófonos' : 'Digital Concierge with Intercoms',
    locale.value === 'es' ? 'Marketplace Comunitario' : 'Community Marketplace',
  ],
}))

// Structured Data - Breadcrumb
const breadcrumbSchema = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'BreadcrumbList',
  itemListElement: [
    {
      '@type': 'ListItem',
      position: 1,
      name: 'Inicio',
      item: 'https://tavira.com.co/',
    },
  ],
}))

// SEO
useHead({
  title: metaTitle,
  htmlAttrs: {
    lang: locale,
  },
  link: [
    {
      rel: 'canonical',
      href: canonicalUrl,
    },
  ],
  meta: [
    { name: 'description', content: metaDescription },
    { name: 'keywords', content: metaKeywords },
    { name: 'robots', content: 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1' },
    { name: 'author', content: 'Tavira' },
    { name: 'publisher', content: 'Tavira' },
    { name: 'geo.region', content: 'CO' },
    { name: 'geo.placename', content: 'Colombia' },
    { name: 'language', content: locale },
    { name: 'mobile-web-app-capable', content: 'yes' },
    { name: 'apple-mobile-web-app-capable', content: 'yes' },
    { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' },
    { name: 'theme-color', content: '#002e82' },
  ],
  script: [
    {
      type: 'application/ld+json',
      children: JSON.stringify(organizationSchema.value),
    },
    {
      type: 'application/ld+json',
      children: JSON.stringify(softwareSchema.value),
    },
    {
      type: 'application/ld+json',
      children: JSON.stringify(breadcrumbSchema.value),
    },
  ],
})

// Open Graph y Twitter
useSeoMeta({
  title: metaTitle,
  ogTitle: metaTitle,
  description: metaDescription,
  ogDescription: metaDescription,
  ogImage: 'https://tavira.com.co/og-image.jpg',
  ogImageWidth: 1200,
  ogImageHeight: 630,
  ogType: 'website',
  ogUrl: canonicalUrl,
  ogLocale: computed(() => (locale.value === 'es' ? 'es_CO' : 'en_US')),
  ogSiteName: 'Tavira',
  twitterCard: 'summary_large_image',
  twitterTitle: metaTitle,
  twitterDescription: metaDescription,
  twitterImage: 'https://tavira.com.co/og-image.jpg',
  twitterSite: '@tavira_co',
})
</script>
