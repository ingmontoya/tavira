<template>
    <footer class="border-t border-gray-100 bg-[#F5F5F5] px-6 py-16">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 md:grid-cols-4">
                <div class="md:col-span-2">
                    <div class="mb-6 flex items-center space-x-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#1D3557] to-[#06D6A0] shadow-lg">
                            <span class="text-xl font-bold text-white font-brand">T</span>
                        </div>
                        <span class="text-3xl font-bold text-[#1D3557] font-brand tracking-tight">Tavira</span>
                    </div>
                    <p class="mb-6 max-w-lg text-[#1D3557] text-lg leading-relaxed">
                        {{ $t('footer.description') }}
                    </p>
                    <div class="inline-flex items-center rounded-full bg-gradient-to-r from-[#1D3557] to-[#06D6A0] px-4 py-2 text-sm font-medium text-white shadow-md">
                        Gestión simple, comunidades fuertes
                    </div>
                    <div class="mt-6 text-sm text-gray-500">{{ $t('footer.copyright') }}</div>
                </div>

                <div>
                    <h4 class="mb-6 font-bold text-[#1D3557] text-lg">{{ $t('footer.product.title') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.product.features') }}</a>
                        </li>
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.product.pricing') }}</a>
                        </li>
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.product.security') }}</a>
                        </li>
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.product.integrations') }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-6 font-bold text-[#1D3557] text-lg">{{ $t('footer.support.title') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.support.helpCenter') }}</a>
                        </li>
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.support.contact') }}</a>
                        </li>
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.support.status') }}</a>
                        </li>
                        <li>
                            <a href="#" class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">{{ $t('footer.support.api') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</template>
