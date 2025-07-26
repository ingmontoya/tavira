<template>
    <Head>
        <!-- Dynamic Title -->
        <title>{{ metaTitle }}</title>
        
        <!-- SEO Meta Tags -->
        <meta name="description" :content="metaDescription" />
        <meta name="keywords" :content="metaKeywords" />
        <meta name="author" content="Habitta" />
        <meta name="robots" content="index, follow" />
        <meta name="language" :content="currentLocale" />
        
        <!-- Open Graph Meta Tags -->
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="metaTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:image" content="/images/og-image.jpg" />
        <meta property="og:url" :content="currentUrl" />
        <meta property="og:site_name" content="Habitta" />
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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
        
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
        ? 'Habitta - Gestión Inteligente de Conjuntos Residenciales en Colombia'
        : 'Habitta - Smart Residential Complex Management in Colombia'
);

const metaDescription = computed(() => 
    currentLocale.value === 'es'
        ? 'La plataforma más avanzada para administrar conjuntos residenciales en Colombia. Gestión de residentes, pagos, comunicaciones y más con IA. Prueba gratis 30 días.'
        : 'The most advanced platform for managing residential complexes in Colombia. Resident management, payments, communications and more with AI. Free 30-day trial.'
);

const metaKeywords = computed(() => 
    currentLocale.value === 'es'
        ? 'gestión conjuntos residenciales, administración propiedad horizontal, software administración, pagos conjuntos, gestión residentes Colombia, proptech'
        : 'residential complex management, property management software, apartment administration, HOA management, residents management Colombia, proptech'
);

const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.href;
    }
    return 'https://habitta.com';
});

const alternateUrls = computed(() => ({
    es: currentUrl.value.replace(/[?&]lang=en/, '').includes('?') ? `${currentUrl.value}&lang=es` : `${currentUrl.value}?lang=es`,
    en: currentUrl.value.replace(/[?&]lang=es/, '').includes('?') ? `${currentUrl.value}&lang=en` : `${currentUrl.value}?lang=en`
}));

const ogLocale = computed(() => currentLocale.value === 'es' ? 'es_CO' : 'en_US');

const structuredData = computed(() => {
    const baseData = {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Habitta",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "COP",
            "priceValidUntil": "2025-12-31",
            "description": currentLocale.value === 'es' ? "Prueba gratuita de 30 días" : "30-day free trial"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.9",
            "reviewCount": "250"
        },
        "description": metaDescription.value,
        "url": "https://habitta.com",
        "author": {
            "@type": "Organization",
            "name": "Habitta",
            "url": "https://habitta.com"
        },
        "screenshot": "https://habitta.com/images/dashboard-screenshot.jpg",
        "softwareVersion": "2.0",
        "datePublished": "2024-01-01",
        "dateModified": "2024-12-01"
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