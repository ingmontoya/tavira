<template>
    <footer class="border-t border-gray-200 bg-white px-6 py-12">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 md:grid-cols-4">
                <div class="md:col-span-2">
                    <div class="mb-4 flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600">
                            <span class="text-xl font-bold text-white">H</span>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">Habitta</span>
                    </div>
                    <p class="mb-4 max-w-md text-gray-600">
                        {{ $t('footer.description') }}
                    </p>
                    <div class="text-sm text-gray-500">{{ $t('footer.copyright') }}</div>
                </div>

                <div>
                    <h4 class="mb-4 font-semibold text-gray-900">{{ $t('footer.product.title') }}</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.product.features') }}</a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.product.pricing') }}</a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.product.security') }}</a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.product.integrations') }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-4 font-semibold text-gray-900">{{ $t('footer.support.title') }}</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.support.helpCenter') }}</a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.support.contact') }}</a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.support.status') }}</a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-blue-600">{{ $t('footer.support.api') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</template>
