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
        <meta property="og:image" content="/images/og-image.jpg" />
        <meta property="og:url" :content="currentUrl" />
        <meta property="og:site_name" content="Tavira" />
        <meta property="og:locale" :content="ogLocale" />

        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="metaTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta name="twitter:image" content="/images/twitter-card.jpg" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="currentUrl" />

        <!-- Alternate Language Links -->
        <link rel="alternate" hreflang="es" :href="alternateUrls.es" />
        <link rel="alternate" hreflang="en" :href="alternateUrls.en" />
        <link rel="alternate" hreflang="x-default" :href="alternateUrls.es" />

        <!-- Preconnect for Performance -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

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
        ? 'Tavira - Gestión simple, comunidades fuertes'
        : 'Tavira - Simple management, strong communities',
);

const metaDescription = computed(() =>
    currentLocale.value === 'es'
        ? 'Tavira: la nueva forma de administrar propiedad horizontal. Eficiencia y claridad para tu conjunto residencial en Colombia. Prueba gratis 30 días.'
        : 'Tavira: the new way to manage horizontal property. Efficiency and clarity for your residential complex in Colombia. Free 30-day trial.',
);

const metaKeywords = computed(() =>
    currentLocale.value === 'es'
        ? 'Tavira, gestión simple conjuntos residenciales, administración propiedad horizontal, software administración eficiente, pagos conjuntos, gestión residentes Colombia, proptech, comunidades fuertes'
        : 'Tavira, simple residential complex management, property management software, efficient apartment administration, HOA management, residents management Colombia, proptech, strong communities',
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
            url: 'https://tavira.com',
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
