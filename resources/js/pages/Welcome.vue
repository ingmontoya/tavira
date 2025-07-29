<script setup lang="ts">
import LandingBenefits from '../components/landing/LandingBenefits.vue';
import LandingCTA from '../components/landing/LandingCTA.vue';
import LandingDetailedFeatures from '../components/landing/LandingDetailedFeatures.vue';
import LandingFeatures from '../components/landing/LandingFeatures.vue';
import LandingFooter from '../components/landing/LandingFooter.vue';
import LandingHero from '../components/landing/LandingHero.vue';
import LandingHowItWorks from '../components/landing/LandingHowItWorks.vue';
import LandingNavigation from '../components/landing/LandingNavigation.vue';
import LandingSEO from '../components/landing/LandingSEO.vue';
import LandingTestimonials from '../components/landing/LandingTestimonials.vue';
</script>

<template>
    <div class="min-h-screen overflow-x-hidden">
        <!-- SEO Meta Tags -->
        <LandingSEO />

        <!-- Navigation -->
        <LandingNavigation />

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

        <!-- Footer -->
        <LandingFooter />
    </div>
</template>

<style scoped>
.font-inter {
    font-family: 'Inter', sans-serif;
}
</style>
