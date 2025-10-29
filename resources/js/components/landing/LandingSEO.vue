<template>

    <Head>
        <!-- Dynamic Title -->
        <title>{{ metaTitle }}</title>

        <!-- SEO Meta Tags -->
        <meta name="description" :content="metaDescription" />
        <meta name="keywords" :content="metaKeywords" />
        <meta name="author" content="Tavira" />
        <meta name="robots" content="index, follow" />
        <meta name="language" :content="currentLocale" />

        <!-- Open Graph Meta Tags -->
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="metaTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:image" content="/images/og-image.svg" />
        <meta property="og:url" :content="currentUrl" />
        <meta property="og:site_name" content="Tavira" />
        <meta property="og:locale" :content="ogLocale" />

        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="metaTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta name="twitter:image" content="/images/twitter-card.svg" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="currentUrl" />

        <!-- Alternate Language Links -->
        <link rel="alternate" hreflang="es" :href="alternateUrls.es" />
        <link rel="alternate" hreflang="en" :href="alternateUrls.en" />
        <link rel="alternate" hreflang="x-default" :href="alternateUrls.es" />

        <!-- Preconnect for Performance -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap"
            rel="stylesheet" />

        <!-- DNS Prefetch -->
        <link rel="dns-prefetch" href="//fonts.googleapis.com" />
        <link rel="dns-prefetch" href="//fonts.gstatic.com" />
    </Head>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const currentLocale = computed(() => locale.value);

const metaTitle = computed(() =>
    currentLocale.value === 'es'
        ? 'Tavira - Software Líder para Administración de Conjuntos Residenciales en Colombia | Apps Móviles + Seguridad Bancaria'
        : 'Tavira - Leading Software for Residential Complex Management in Colombia | Mobile Apps + Banking Security',
);

const metaDescription = computed(() =>
    currentLocale.value === 'es'
        ? 'Software líder para administración de conjuntos residenciales en Colombia. Apps móviles iOS/Android, portería digital, citófonos, pases QR, facturación DIAN, contabilidad, marketplace comunitario. Base de datos exclusiva. Regístrate 30 días.'
        : 'Leading software for residential complex management in Colombia. iOS/Android mobile apps, digital concierge, intercoms, QR passes, DIAN invoicing, accounting, community marketplace. Exclusive database. Free 30-day trial.',
);

const metaKeywords = computed(() =>
    currentLocale.value === 'es'
        ? 'software administración conjuntos residenciales Colombia, aplicación móvil portería, citófonos digitales, pases QR temporal, gestión paquetes, facturación electrónica DIAN, contabilidad conjuntos, marketplace comunitario, sistema PQR digital, control acceso vehicular, comunicados residentes, mantenimiento predictivo, presupuestos condominios, conciliación bancaria, base datos exclusiva, seguridad bancaria, proptech Colombia, administración inmobiliaria, propiedad horizontal, condominios, torres residenciales, unidades cerradas, conjunto cerrado'
        : 'residential complex management software Colombia, mobile concierge app, digital intercoms, temporary QR passes, package management, DIAN electronic invoicing, condominium accounting, community marketplace, digital PQR system, vehicle access control, resident communications, predictive maintenance, condo budgets, bank reconciliation, exclusive database, banking security, proptech Colombia, real estate management, horizontal property, condominiums, residential towers, gated communities',
);

const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.href;
    }
    return 'https://tavira.com';
});

const alternateUrls = computed(() => ({
    es: currentUrl.value.replace(/[?&]lang=en/, '').includes('?') ? `${currentUrl.value}&lang=es` : `${currentUrl.value}?lang=es`,
    en: currentUrl.value.replace(/[?&]lang=es/, '').includes('?') ? `${currentUrl.value}&lang=en` : `${currentUrl.value}?lang=en`,
}));

const ogLocale = computed(() => (currentLocale.value === 'es' ? 'es_CO' : 'en_US'));

const structuredData = computed(() => {
    const baseData = {
        '@context': 'https://schema.org',
        '@type': 'SoftwareApplication',
        name: 'Tavira',
        applicationCategory: 'BusinessApplication',
        operatingSystem: 'Web',
        offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'COP',
            priceValidUntil: '2025-12-31',
            description: currentLocale.value === 'es' ? 'Prueba gratuita de 30 días' : '30-day free trial',
        },
        aggregateRating: {
            '@type': 'AggregateRating',
            ratingValue: '4.9',
            reviewCount: '250',
        },
        description: metaDescription.value,
        url: 'https://tavira.com',
        author: {
            '@type': 'Organization',
            name: 'Tavira',
            url: 'https://tavira.com.co',
            logo: 'https://tavira.com.co/images/tavira-logo.png',
            address: {
                '@type': 'PostalAddress',
                addressCountry: 'CO',
                addressLocality: 'Bogotá',
                addressRegion: 'Cundinamarca',
            },
            contactPoint: {
                '@type': 'ContactPoint',
                telephone: '+57-300-123-4567',
                contactType: 'Soporte Técnico',
                availableLanguage: ['Spanish', 'English'],
            },
        },
        screenshot: 'https://tavira.com/images/dashboard-screenshot.jpg',
        softwareVersion: '2.0',
        datePublished: '2024-01-01',
        dateModified: '2024-12-01',
    };

    return JSON.stringify(baseData);
});

// Add structured data to head manually
onMounted(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = structuredData.value;
    document.head.appendChild(script);
});
</script>
