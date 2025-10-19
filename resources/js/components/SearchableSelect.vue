<template>
    <div class="relative">
        <div class="relative">
            <Input :id="id" v-model="searchQuery" :placeholder="placeholder" class="pr-10" @focus="showDropdown = true" @input="onSearch" />
            <Button type="button" variant="ghost" size="sm" class="absolute top-0 right-0 h-full px-3" @click="showDropdown = !showDropdown">
                <ChevronDownIcon class="h-4 w-4" />
            </Button>
        </div>

        <div
            v-if="showDropdown"
            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover text-popover-foreground shadow-lg"
        >
            <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-muted-foreground">No se encontraron opciones</div>
            <div
                v-for="option in filteredOptions"
                :key="getOptionValue(option)"
                class="cursor-pointer px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                @click="selectOption(option)"
            >
                {{ getOptionLabel(option) }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ChevronDownIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    id?: string;
    modelValue?: string | number;
    options: any[];
    placeholder?: string;
    optionValue?: string;
    optionLabel?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string | number): void;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Seleccionar...',
    optionValue: 'value',
    optionLabel: 'label',
});

const emit = defineEmits<Emits>();

const searchQuery = ref('');
const showDropdown = ref(false);

// Computed property for filtered options
const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return props.options;
    }

    const query = searchQuery.value.toLowerCase();
    return props.options.filter((option) => {
        const label = getOptionLabel(option).toLowerCase();
        return label.includes(query);
    });
});

// Helper functions for dynamic option handling
const getOptionValue = (option: any): string | number => {
    if (typeof option === 'string' || typeof option === 'number') {
        return option;
    }
    return option[props.optionValue];
};

const getOptionLabel = (option: any): string => {
    if (typeof option === 'string') {
        return option;
    }
    if (typeof option === 'number') {
        return option.toString();
    }
    return option[props.optionLabel] || option[props.optionValue] || '';
};

const selectOption = (option: any) => {
    const value = getOptionValue(option);
    const label = getOptionLabel(option);

    searchQuery.value = label;
    showDropdown.value = false;
    emit('update:modelValue', value);
};

const onSearch = () => {
    showDropdown.value = true;
};

// Initialize search query with selected option label
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            const selectedOption = props.options.find((option) => getOptionValue(option) === newValue);
            if (selectedOption) {
                searchQuery.value = getOptionLabel(selectedOption);
            }
        } else {
            searchQuery.value = '';
        }
    },
    { immediate: true },
);

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as Element;
    if (!target.closest('.relative')) {
        showDropdown.value = false;
    }
};

// Add event listener for outside clicks
if (typeof window !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
}
</script>
