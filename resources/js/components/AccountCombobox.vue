<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { computed, ref } from 'vue';

interface Account {
    id: number;
    code: string;
    name: string;
    account_type?: string;
    is_active?: boolean;
}

interface Props {
    accounts: Account[];
    modelValue: number | null;
    placeholder?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Seleccionar cuenta...',
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const searchQuery = ref('');
const isOpen = ref(false);

const selectedAccount = computed(() => {
    if (!props.modelValue) return null;
    return props.accounts.find((account) => account.id === props.modelValue);
});

// Show limited results for better performance
const filteredAccounts = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();

    // If no search query, show selected account + first 15 accounts
    if (!query) {
        const accounts = props.accounts.slice(0, 15);
        // Make sure selected account is included
        if (selectedAccount.value && !accounts.find((a) => a.id === selectedAccount.value!.id)) {
            accounts.unshift(selectedAccount.value);
        }
        return accounts;
    }

    // Filter and limit to 25 results for performance
    return props.accounts
        .filter((account) => {
            return (
                account.code.toLowerCase().includes(query) ||
                account.name.toLowerCase().includes(query) ||
                account.account_type?.toLowerCase().includes(query)
            );
        })
        .slice(0, 25);
});

const handleOpenChange = (open: boolean) => {
    isOpen.value = open;
    if (!open) {
        searchQuery.value = '';
    }
};
</script>

<template>
    <Select
        :model-value="modelValue"
        :disabled="disabled"
        @update:model-value="(value) => emit('update:modelValue', value)"
        @update:open="handleOpenChange"
    >
        <SelectTrigger class="w-full">
            <SelectValue :placeholder="placeholder">
                <span v-if="selectedAccount" class="flex items-center gap-2">
                    <span class="font-mono text-sm font-semibold">{{ selectedAccount.code }}</span>
                    <span class="text-sm">{{ selectedAccount.name }}</span>
                </span>
            </SelectValue>
        </SelectTrigger>
        <SelectContent>
            <div class="sticky top-0 z-10 bg-background p-2 border-b">
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar por código o nombre..."
                    class="h-8"
                    @click.stop
                    @keydown.stop
                />
            </div>
            <div v-if="filteredAccounts.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                No se encontraron cuentas.
            </div>
            <SelectItem v-for="account in filteredAccounts" :key="account.id" :value="account.id">
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm font-semibold">{{ account.code }}</span>
                        <span class="text-sm">{{ account.name }}</span>
                    </div>
                    <span v-if="account.account_type" class="text-xs text-muted-foreground">
                        {{ account.account_type }}
                    </span>
                </div>
            </SelectItem>
            <div
                v-if="!searchQuery && filteredAccounts.length >= 15"
                class="sticky bottom-0 bg-muted/50 px-2 py-1 text-xs text-center text-muted-foreground border-t"
            >
                Escribe para buscar más cuentas...
            </div>
        </SelectContent>
    </Select>
</template>
