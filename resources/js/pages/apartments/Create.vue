<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Crear Apartamento</h2>
                    <p class="text-sm text-gray-600">Registra un nuevo apartamento</p>
                </div>
                <Link :href="route('apartments.index')" class="rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                    Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Basic Information -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Información Básica</h3>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Conjunto Config -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Conjunto <span class="text-red-500">*</span> </label>
                                <Select v-model="form.conjunto_config_id" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un conjunto" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="conjunto in conjuntoConfigs" :key="conjunto.id" :value="conjunto.id.toString()">
                                            {{ conjunto.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors.conjunto_config_id" />
                            </div>

                            <!-- Apartment Type -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    Tipo de Apartamento <span class="text-red-500">*</span>
                                </label>
                                <Select v-model="form.apartment_type_id" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in apartmentTypes" :key="type.id" :value="type.id.toString()">
                                            {{ type.name }} ({{ type.area_sqm }}m²)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors.apartment_type_id" />
                            </div>

                            <!-- Number -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Número <span class="text-red-500">*</span> </label>
                                <Input v-model="form.number" type="text" required maxlength="10" placeholder="Ej: 1101" />
                                <InputError :message="errors.number" />
                            </div>

                            <!-- Tower -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Torre <span class="text-red-500">*</span> </label>
                                <Input v-model="form.tower" type="text" required maxlength="10" placeholder="Ej: 1 o A" />
                                <InputError :message="errors.tower" />
                            </div>

                            <!-- Floor -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Piso <span class="text-red-500">*</span> </label>
                                <Input v-model="form.floor" type="number" required min="1" placeholder="Ej: 11" />
                                <InputError :message="errors.floor" />
                            </div>

                            <!-- Position on Floor -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    Posición en el piso <span class="text-red-500">*</span>
                                </label>
                                <Input v-model="form.position_on_floor" type="number" required min="1" placeholder="Ej: 1" />
                                <InputError :message="errors.position_on_floor" />
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Estado <span class="text-red-500">*</span> </label>
                                <Select v-model="form.status" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in statuses" :key="status" :value="status">
                                            {{ getStatusLabel(status) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors.status" />
                            </div>

                            <!-- Monthly Fee -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    Cuota Mensual <span class="text-red-500">*</span>
                                </label>
                                <Input v-model="form.monthly_fee" type="number" required min="0" step="0.01" placeholder="Ej: 150000" />
                                <InputError :message="errors.monthly_fee" />
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Información Adicional</h3>

                        <div class="space-y-6">
                            <!-- Utilities -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Servicios Públicos </label>
                                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="utilities.water"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Agua</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="utilities.electricity"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Electricidad</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="utilities.gas"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Gas</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="utilities.internet"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Internet</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="utilities.tv"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">TV</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="utilities.phone"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Teléfono</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Features -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Características </label>
                                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="features.parking"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Parqueadero</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="features.storage"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Depósito</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="features.balcony"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Balcón</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="features.terrace"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Terraza</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="features.pets_allowed"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Mascotas permitidas</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            v-model="features.furnished"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">Amoblado</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700"> Notas </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Información adicional sobre el apartamento..."
                                />
                                <InputError :message="errors.notes" />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-4">
                        <Link
                            :href="route('apartments.index')"
                            class="rounded-md bg-gray-300 px-6 py-2 text-sm font-medium text-gray-800 hover:bg-gray-400"
                        >
                            Cancelar
                        </Link>
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            {{ processing ? 'Guardando...' : 'Crear Apartamento' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    conjuntoConfigs: Array,
    apartmentTypes: Array,
    statuses: Array,
    errors: Object,
});

const form = useForm({
    conjunto_config_id: '',
    apartment_type_id: '',
    number: '',
    tower: '',
    floor: '',
    position_on_floor: '',
    status: 'Available',
    monthly_fee: '',
    utilities: {},
    features: {},
    notes: '',
});

const utilities = reactive({
    water: false,
    electricity: false,
    gas: false,
    internet: false,
    tv: false,
    phone: false,
});

const features = reactive({
    parking: false,
    storage: false,
    balcony: false,
    terrace: false,
    pets_allowed: false,
    furnished: false,
});

// Watch for changes in utilities and features and update form
watch(
    utilities,
    (newUtilities) => {
        form.utilities = { ...newUtilities };
    },
    { deep: true },
);

watch(
    features,
    (newFeatures) => {
        form.features = { ...newFeatures };
    },
    { deep: true },
);

const submit = () => {
    form.post(route('apartments.store'));
};

const getStatusLabel = (status) => {
    const labels = {
        Available: 'Disponible',
        Occupied: 'Ocupado',
        Maintenance: 'Mantenimiento',
        Reserved: 'Reservado',
    };
    return labels[status] || status;
};
</script>
