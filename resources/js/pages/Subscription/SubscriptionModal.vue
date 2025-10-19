<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal -->
            <div
                class="relative z-50 my-8 inline-block w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
            >
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Suscripción a {{ plan?.name }}</h3>
                        <p class="text-lg font-semibold text-indigo-600">${{ formatPrice(price) }}/{{ billingType === 'anual' ? 'año' : 'mes' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 transition-colors hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Conjunto Information -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Nombre del Conjunto * </label>
                            <input
                                v-model="form.conjunto_name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                placeholder="Ej: Conjunto Residencial Los Pinos"
                            />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Dirección del Conjunto </label>
                            <input
                                v-model="form.conjunto_address"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                placeholder="Ej: Calle 123 #45-67, Bogotá"
                            />
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="border-t pt-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-900">Información de Contacto</h4>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Nombre del Administrador * </label>
                                <input
                                    v-model="form.customer_name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Nombre completo"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Teléfono </label>
                                <input
                                    v-model="form.customer_phone"
                                    type="tel"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Ej: + +44 7447 313219"
                                />
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Correo Electrónico * </label>
                            <input
                                v-model="form.customer_email"
                                type="email"
                                required
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                placeholder="administracion@ejemplo.com"
                            />
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="border-t pt-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-900">Ubicación</h4>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Ciudad </label>
                                <select
                                    v-model="form.city"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="Bogotá">Bogotá</option>
                                    <option value="Medellín">Medellín</option>
                                    <option value="Cali">Cali</option>
                                    <option value="Barranquilla">Barranquilla</option>
                                    <option value="Cartagena">Cartagena</option>
                                    <option value="Bucaramanga">Bucaramanga</option>
                                    <option value="Pereira">Pereira</option>
                                    <option value="Manizales">Manizales</option>
                                    <option value="Otra">Otra</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Departamento </label>
                                <select
                                    v-model="form.region"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="Bogotá D.C.">Bogotá D.C.</option>
                                    <option value="Antioquia">Antioquia</option>
                                    <option value="Valle del Cauca">Valle del Cauca</option>
                                    <option value="Atlántico">Atlántico</option>
                                    <option value="Bolívar">Bolívar</option>
                                    <option value="Santander">Santander</option>
                                    <option value="Risaralda">Risaralda</option>
                                    <option value="Caldas">Caldas</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="border-t pt-6">
                        <label class="flex items-start">
                            <input
                                v-model="form.acceptTerms"
                                type="checkbox"
                                required
                                class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-3 text-sm text-gray-700">
                                Acepto los
                                <a href="/terminos" target="_blank" class="text-indigo-600 hover:text-indigo-500"> términos y condiciones </a>
                                y la
                                <a href="/privacidad" target="_blank" class="text-indigo-600 hover:text-indigo-500"> política de privacidad </a>
                                *
                            </span>
                        </label>
                    </div>

                    <!-- Summary -->
                    <div class="rounded-lg border-t bg-gray-50 p-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-900">Resumen</h4>
                        <div class="mb-2 flex items-center justify-between">
                            <span>Plan {{ plan?.name }} ({{ billingType }})</span>
                            <span class="font-semibold">${{ formatPrice(price) }}</span>
                        </div>
                        <div class="mb-2 flex items-center justify-between">
                            <span>IVA (19%)</span>
                            <span class="font-semibold">${{ formatPrice(Math.round(price * 0.19)) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t pt-2 text-lg font-bold">
                            <span>Total a Pagar</span>
                            <span>${{ formatPrice(Math.round(price * 1.19)) }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 pt-6">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-1 rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="!isFormValid || loading"
                            class="flex-1 rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="loading">Procesando...</span>
                            <span v-else>Continuar al Pago</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    plan: any;
    billingType: string;
    price: number;
    user?: any;
}>();

const emit = defineEmits<{
    close: [];
    submit: [formData: any];
}>();

const loading = ref(false);

const form = ref({
    conjunto_name: '',
    conjunto_address: '',
    customer_name: props.user?.name || '',
    customer_email: props.user?.email || '',
    customer_phone: '',
    city: 'Bogotá',
    region: 'Bogotá D.C.',
    acceptTerms: false,
});

const isFormValid = computed(() => {
    return form.value.conjunto_name && form.value.customer_name && form.value.customer_email && form.value.acceptTerms;
});

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('es-CO').format(price);
};

const handleSubmit = () => {
    if (!isFormValid.value) return;

    loading.value = true;
    emit('submit', form.value);
};
</script>
