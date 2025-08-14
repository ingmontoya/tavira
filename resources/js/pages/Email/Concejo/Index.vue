<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { 
    Mail, 
    MailOpen, 
    Search, 
    Plus, 
    Trash2, 
    Archive,
    StarOff,
    RefreshCw,
    Users
} from 'lucide-vue-next';

interface Email {
    ID: string;
    From: {
        Address: string;
        Name: string;
    };
    To: Array<{
        Address: string;
        Name: string;
    }>;
    Subject: string;
    Date: string;
    Read: boolean;
    Size: number;
    Snippet: string;
}

interface Props {
    emails: Email[];
    view: string;
}

const props = defineProps<Props>();

const searchQuery = ref('');
const selectedEmails = ref<string[]>([]);

const filteredEmails = computed(() => {
    if (!searchQuery.value) return props.emails;
    
    return props.emails.filter(email =>
        email.Subject.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        email.From.Address.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        email.From.Name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const unreadCount = computed(() => {
    return props.emails.filter(email => !email.Read).length;
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const emailDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    
    if (emailDate.getTime() === today.getTime()) {
        return date.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    } else if (emailDate.getTime() === today.getTime() - 86400000) {
        return 'Ayer';
    } else {
        return date.toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit' });
    }
};

const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const toggleEmailSelection = (emailId: string) => {
    const index = selectedEmails.value.indexOf(emailId);
    if (index > -1) {
        selectedEmails.value.splice(index, 1);
    } else {
        selectedEmails.value.push(emailId);
    }
};

const selectAllEmails = () => {
    if (selectedEmails.value.length === filteredEmails.value.length) {
        selectedEmails.value = [];
    } else {
        selectedEmails.value = filteredEmails.value.map(email => email.ID);
    }
};

const deleteSelectedEmails = () => {
    if (selectedEmails.value.length > 0) {
        // TODO: Implement bulk delete
        console.log('Deleting emails:', selectedEmails.value);
        selectedEmails.value = [];
    }
};

const markAsRead = (emailId: string) => {
    router.post(route('email.concejo.read', emailId));
};

const markAsUnread = (emailId: string) => {
    router.post(route('email.concejo.unread', emailId));
};

const refreshEmails = () => {
    router.reload();
};
</script>

<template>
    <Head title="Correo Electrónico - Concejo" />
    
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Correo Electrónico</h1>
                    <p class="text-muted-foreground">Bandeja de entrada - Concejo</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        @click="refreshEmails"
                        variant="outline"
                        size="sm"
                    >
                        <RefreshCw class="w-4 h-4 mr-2" />
                        Actualizar
                    </Button>
                    <Button 
                        @click="$inertia.visit(route('email.concejo.compose'))"
                        size="sm"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        Redactar
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <Users class="w-4 h-4" />
                            Concejo - Bandeja
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-muted">
                            <div class="flex items-center gap-2">
                                <Mail class="w-4 h-4" />
                                <span class="text-sm">Recibidos</span>
                            </div>
                            <Badge variant="secondary" v-if="unreadCount > 0">{{ unreadCount }}</Badge>
                        </div>
                        <div class="flex items-center gap-2 p-2 rounded-lg hover:bg-muted cursor-pointer">
                            <StarOff class="w-4 h-4" />
                            <span class="text-sm">Destacados</span>
                        </div>
                        <div class="flex items-center gap-2 p-2 rounded-lg hover:bg-muted cursor-pointer">
                            <Archive class="w-4 h-4" />
                            <span class="text-sm">Archivados</span>
                        </div>
                        <div class="flex items-center gap-2 p-2 rounded-lg hover:bg-muted cursor-pointer">
                            <Trash2 class="w-4 h-4" />
                            <span class="text-sm">Papelera</span>
                        </div>
                        
                        <Separator class="my-3" />
                        
                        <!-- Quick Access -->
                        <div class="space-y-1">
                            <h4 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Acceso Rápido</h4>
                            <div class="flex items-center gap-2 p-2 rounded-lg hover:bg-muted cursor-pointer">
                                <Users class="w-4 h-4" />
                                <span class="text-sm">Administración</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-lg hover:bg-muted cursor-pointer">
                                <Mail class="w-4 h-4" />
                                <span class="text-sm">Circulares</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Email List -->
                <Card class="lg:col-span-3">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <input
                                    type="checkbox"
                                    @change="selectAllEmails"
                                    :checked="selectedEmails.length === filteredEmails.length && filteredEmails.length > 0"
                                    class="rounded border-gray-300"
                                >
                                <Button
                                    @click="deleteSelectedEmails"
                                    variant="outline"
                                    size="sm"
                                    :disabled="selectedEmails.length === 0"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                            <div class="relative w-72">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar correos..."
                                    class="pl-10"
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-1">
                            <div
                                v-for="email in filteredEmails"
                                :key="email.ID"
                                class="flex items-center gap-4 p-3 rounded-lg hover:bg-muted cursor-pointer group"
                                :class="{ 'bg-muted/50': selectedEmails.includes(email.ID) }"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedEmails.includes(email.ID)"
                                    @change="toggleEmailSelection(email.ID)"
                                    class="rounded border-gray-300"
                                    @click.stop
                                >
                                
                                <div 
                                    class="flex-1 min-w-0 grid grid-cols-12 gap-4 items-center"
                                    @click="$inertia.visit(route('email.concejo.show', email.ID))"
                                >
                                    <!-- From -->
                                    <div class="col-span-3">
                                        <div class="flex items-center gap-2">
                                            <Mail class="w-4 h-4" :class="email.Read ? 'text-muted-foreground' : 'text-blue-600'" />
                                            <span class="font-medium truncate" :class="email.Read ? 'text-muted-foreground' : 'text-foreground'">
                                                {{ email.From.Name || email.From.Address }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Subject -->
                                    <div class="col-span-5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium truncate" :class="email.Read ? 'text-muted-foreground' : 'text-foreground'">
                                                {{ email.Subject }}
                                            </span>
                                            <span class="text-sm text-muted-foreground truncate">
                                                - {{ email.Snippet }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Date & Size -->
                                    <div class="col-span-3 text-right">
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(email.Date) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ formatSize(email.Size) }}
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="col-span-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Button
                                            @click.stop="email.Read ? markAsUnread(email.ID) : markAsRead(email.ID)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <MailOpen v-if="email.Read" class="w-4 h-4" />
                                            <Mail v-else class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="filteredEmails.length === 0" class="text-center py-8 text-muted-foreground">
                                <Mail class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p>No hay correos electrónicos</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>