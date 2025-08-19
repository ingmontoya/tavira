<template>
    <footer class="border-t border-gray-100 bg-[#F5F5F5] px-6 py-16">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 md:grid-cols-4">
                <div class="md:col-span-2">
                    <div class="mb-6 flex items-center space-x-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#1D3557] shadow-lg">
                            <span class="text-xl font-bold text-white font-brand">T</span>
                        </div>
                        <span class="text-3xl font-bold text-[#1D3557] font-brand tracking-tight">Tavira</span>
                    </div>
                    <p class="mb-6 max-w-lg text-[#1D3557] text-lg leading-relaxed">
                        {{ $t('footer.description') }}
                    </p>
                    <div class="inline-flex items-center rounded-xl bg-[#FF6868] px-4 py-2 text-sm font-medium text-white shadow-md">
                        Gestión simple, comunidades fuertes
                    </div>
                    <div class="mt-6 text-sm text-gray-500">{{ $t('footer.copyright') }}</div>
                </div>

                <div>
                    <h4 class="mb-6 font-bold text-[#1D3557] text-lg">{{ $t('footer.product.title') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li>
                            <a href="#features" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('features')">{{ $t('footer.product.features') }}</a>
                        </li>
                        <li>
                            <a href="#benefits" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('benefits')">{{ $t('footer.product.pricing') }}</a>
                        </li>
                        <li>
                            <a href="#detailed-features" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('detailed-features')">{{ $t('footer.product.security') }}</a>
                        </li>
                        <li>
                            <a href="#how-it-works" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('how-it-works')">{{ $t('footer.product.integrations') }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-6 font-bold text-[#1D3557] text-lg">{{ $t('footer.support.title') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li>
                            <a href="#testimonials" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('testimonials')">{{ $t('footer.support.helpCenter') }}</a>
                        </li>
                        <li>
                            <a href="#cta" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('cta')">{{ $t('footer.support.contact') }}</a>
                        </li>
                        <li>
                            <a href="#hero" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('hero')">{{ $t('footer.support.status') }}</a>
                        </li>
                        <li>
                            <a href="#features" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer" @click="scrollToSection('features')">{{ $t('footer.support.api') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
const scrollToSection = (sectionId: string) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
};
</script>
