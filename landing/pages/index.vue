<template>
  <div class="min-h-screen overflow-x-hidden">
    <!-- Hero Section -->
    <LandingHero />

    <!-- Features Overview Section -->
    <LandingFeatures />

    <!-- Platform Benefits Section -->
    <LandingBenefits />

    <!-- Detailed Features Section -->
    <LandingDetailedFeatures />

    <!-- Testimonials Section -->
    <LandingTestimonials />

    <!-- How it Works Section -->
    <LandingHowItWorks />

    <!-- CTA Section -->
    <LandingCTA />
  </div>
</template>

<script setup lang="ts">
const { locale } = useI18n()

const canonicalUrl = computed(() => 'https://tavira.com.co/')

const metaTitle = computed(() =>
  locale.value === 'es'
    ? 'Tavira - Software Líder para Administración de Conjuntos Residenciales en Colombia | Apps Móviles + Seguridad Bancaria'
    : 'Tavira - Leading Software for Residential Complex Management in Colombia | Mobile Apps + Banking Security',
)

const metaDescription = computed(() =>
  locale.value === 'es'
    ? 'Software líder para administración de conjuntos residenciales en Colombia. Apps móviles iOS/Android, portería digital, citófonos, pases QR, facturación DIAN, contabilidad, marketplace comunitario. Base de datos exclusiva.'
    : 'Leading software for residential complex management in Colombia. iOS/Android mobile apps, digital concierge, intercoms, QR passes, DIAN electronic invoicing, accounting, community marketplace. Exclusive database.',
)

const metaKeywords = computed(() =>
  locale.value === 'es'
    ? 'software administración conjuntos residenciales Colombia, aplicación móvil portería, citófonos digitales, pases QR temporal, facturación electrónica DIAN, contabilidad conjuntos, marketplace comunitario, sistema PQR digital, control acceso vehicular'
    : 'residential complex management software Colombia, mobile concierge app, digital intercoms, temporary QR passes, DIAN electronic invoicing, condominium accounting, community marketplace, digital PQR system, vehicle access control',
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
      ? 'Plataforma líder en administración de conjuntos residenciales en Colombia con apps móviles, seguridad bancaria y base de datos exclusiva.'
      : 'Leading platform for residential complex management in Colombia with mobile apps, banking security and exclusive database.',
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
      ? 'Software integral para administración de conjuntos residenciales en Colombia. Gestión financiera con facturación DIAN, contabilidad doble partida, apps móviles nativas iOS/Android, portería digital, comunicación institucional y marketplace comunitario.'
      : 'Comprehensive software for residential complex management in Colombia. Financial management with DIAN invoicing, double-entry accounting, native iOS/Android mobile apps, digital concierge, institutional communication and community marketplace.',
  featureList: [
    locale.value === 'es' ? 'Gestión Financiera y Contabilidad' : 'Financial Management and Accounting',
    locale.value === 'es' ? 'Facturación Electrónica DIAN' : 'DIAN Electronic Invoicing',
    locale.value === 'es' ? 'Apps Móviles iOS y Android' : 'iOS and Android Mobile Apps',
    locale.value === 'es' ? 'Portería Digital con Citófonos' : 'Digital Concierge with Intercoms',
    locale.value === 'es' ? 'Sistema de Comunicación Institucional' : 'Institutional Communication System',
    locale.value === 'es' ? 'Marketplace Comunitario' : 'Community Marketplace',
    locale.value === 'es' ? 'Control de Acceso y Pases QR' : 'Access Control and QR Passes',
    locale.value === 'es' ? 'Gestión de Mantenimiento' : 'Maintenance Management',
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
