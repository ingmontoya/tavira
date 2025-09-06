<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { router } from '@inertiajs/vue3';
import { Check, Clock, Users, AlertCircle, Vote, ThumbsUp, ThumbsDown, Minus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface Vote {
    id: number;
    title: string;
    description: string | null;
    type: 'yes_no' | 'multiple_choice' | 'quantitative';
    status: 'draft' | 'active' | 'closed';
    is_active: boolean;
    can_vote: boolean;
    allows_abstention: boolean;
    required_quorum_percentage: number;
    required_approval_percentage: number;
    opens_at: string | null;
    closes_at: string | null;
    status_badge: {
        text: string;
        class: string;
    };
    options?: Array<{
        id: number;
        title: string;
        description: string | null;
        value: number | null;
    }>;
    results: any;
    participation_stats: {
        total_apartments: number;
        voted_apartments: number;
        participation_percentage: number;
        required_quorum_percentage: number;
        has_quorum: boolean;
    };
}

export interface UserVote {
    id: number;
    choice: string | null;
    vote_option_id: number | null;
    quantitative_value: number | null;
    display_choice: string;
    cast_at: string;
}

const props = defineProps<{
    vote: Vote;
    userVote: UserVote | null;
    canVote: boolean;
    userApartmentId: number | null;
    assemblyId: number;
}>();

const emit = defineEmits<{
    voteSubmitted: [vote: Vote, participation: any];
}>();

// Voting state
const selectedChoice = ref<string>('');
const selectedOptionId = ref<number | null>(null);
const quantitativeValue = ref<number | null>(null);
const isSubmitting = ref(false);
const error = ref<string | null>(null);

// Computed properties
const hasVoted = computed(() => props.userVote !== null);
const timeRemaining = computed(() => {
    if (!props.vote.closes_at) return null;
    
    const now = new Date().getTime();
    const closeTime = new Date(props.vote.closes_at).getTime();
    const diff = closeTime - now;
    
    if (diff <= 0) return null;
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours > 0) {
        return `${hours}h ${minutes}m restantes`;
    }
    return `${minutes}m restantes`;
});

const isValidSelection = computed(() => {
    if (props.vote.type === 'yes_no') {
        return selectedChoice.value !== '';
    } else if (props.vote.type === 'multiple_choice') {
        return selectedOptionId.value !== null;
    } else if (props.vote.type === 'quantitative') {
        return quantitativeValue.value !== null && quantitativeValue.value >= 0;
    }
    return false;
});

// Helper functions
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getVoteIcon = (type: string) => {
    switch (type) {
        case 'yes_no': return ThumbsUp;
        case 'multiple_choice': return Vote;
        case 'quantitative': return '#';
        default: return Vote;
    }
};

const getChoiceIcon = (choice: string) => {
    switch (choice) {
        case 'yes': return ThumbsUp;
        case 'no': return ThumbsDown;
        case 'abstain': return Minus;
        default: return Check;
    }
};

// Actions
const submitVote = async () => {
    if (!isValidSelection.value || !props.userApartmentId) return;
    
    isSubmitting.value = true;
    error.value = null;
    
    try {
        const voteData: any = {
            apartment_id: props.userApartmentId,
        };
        
        if (props.vote.type === 'yes_no') {
            voteData.choice = selectedChoice.value;
        } else if (props.vote.type === 'multiple_choice') {
            voteData.vote_option_id = selectedOptionId.value;
        } else if (props.vote.type === 'quantitative') {
            voteData.quantitative_value = quantitativeValue.value;
        }
        
        const response = await fetch(route('assemblies.votes.cast', [props.assemblyId, props.vote.id]), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(voteData),
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error al enviar el voto');
        }
        
        const result = await response.json();
        
        emit('voteSubmitted', props.vote, result.participation_stats);
        
        // Refresh the page to show updated data
        router.reload({ only: ['userVote', 'vote'] });
        
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Error desconocido';
    } finally {
        isSubmitting.value = false;
    }
};

// Initialize form values if user already voted
if (props.userVote) {
    if (props.vote.type === 'yes_no') {
        selectedChoice.value = props.userVote.choice || '';
    } else if (props.vote.type === 'multiple_choice') {
        selectedOptionId.value = props.userVote.vote_option_id;
    } else if (props.vote.type === 'quantitative') {
        quantitativeValue.value = props.userVote.quantitative_value;
    }
}
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <CardTitle class="flex items-center gap-2">
                        <component :is="getVoteIcon(vote.type)" class="h-5 w-5" />
                        {{ vote.title }}
                    </CardTitle>
                    <CardDescription v-if="vote.description" class="mt-2">
                        {{ vote.description }}
                    </CardDescription>
                </div>
                <Badge :class="vote.status_badge.class">
                    {{ vote.status_badge.text }}
                </Badge>
            </div>
            
            <!-- Vote timing -->
            <div v-if="vote.opens_at || vote.closes_at" class="flex items-center gap-4 text-sm text-gray-600 mt-4">
                <div v-if="vote.opens_at" class="flex items-center gap-1">
                    <Clock class="h-4 w-4" />
                    Abierta: {{ formatDate(vote.opens_at) }}
                </div>
                <div v-if="vote.closes_at" class="flex items-center gap-1">
                    <Clock class="h-4 w-4" />
                    Cierra: {{ formatDate(vote.closes_at) }}
                </div>
                <div v-if="timeRemaining" class="font-medium text-orange-600">
                    {{ timeRemaining }}
                </div>
            </div>
            
            <!-- Participation progress -->
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">Participación</span>
                    <span class="text-gray-600">
                        {{ vote.participation_stats.voted_apartments }} / {{ vote.participation_stats.total_apartments }}
                        ({{ vote.participation_stats.participation_percentage }}%)
                    </span>
                </div>
                <Progress 
                    :value="vote.participation_stats.participation_percentage" 
                    :class="vote.participation_stats.has_quorum ? 'text-green-600' : 'text-yellow-600'"
                />
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>Quorum requerido: {{ vote.participation_stats.required_quorum_percentage }}%</span>
                    <Badge 
                        :variant="vote.participation_stats.has_quorum ? 'default' : 'outline'"
                        class="text-xs"
                    >
                        <Users class="h-3 w-3 mr-1" />
                        {{ vote.participation_stats.has_quorum ? 'Quorum alcanzado' : 'Sin quorum' }}
                    </Badge>
                </div>
            </div>
        </CardHeader>
        
        <CardContent>
            <!-- Already voted message -->
            <div v-if="hasVoted" class="mb-6">
                <Alert>
                    <Check class="h-4 w-4" />
                    <AlertDescription>
                        <strong>Ya has votado:</strong> {{ userVote!.display_choice }}
                        <span class="text-gray-500 ml-2">
                            el {{ formatDate(userVote!.cast_at) }}
                        </span>
                    </AlertDescription>
                </Alert>
            </div>
            
            <!-- Voting interface -->
            <div v-else-if="canVote && vote.can_vote" class="space-y-4">
                
                <!-- Yes/No vote -->
                <div v-if="vote.type === 'yes_no'">
                    <Label class="text-base font-medium">¿Cuál es tu voto?</Label>
                    <RadioGroup v-model="selectedChoice" class="mt-3">
                        <div class="flex items-center space-x-2 p-3 rounded-lg border hover:bg-green-50">
                            <RadioGroupItem value="yes" id="yes" />
                            <Label for="yes" class="flex items-center gap-2 cursor-pointer">
                                <ThumbsUp class="h-4 w-4 text-green-600" />
                                Sí, a favor
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2 p-3 rounded-lg border hover:bg-red-50">
                            <RadioGroupItem value="no" id="no" />
                            <Label for="no" class="flex items-center gap-2 cursor-pointer">
                                <ThumbsDown class="h-4 w-4 text-red-600" />
                                No, en contra
                            </Label>
                        </div>
                        <div v-if="vote.allows_abstention" class="flex items-center space-x-2 p-3 rounded-lg border hover:bg-gray-50">
                            <RadioGroupItem value="abstain" id="abstain" />
                            <Label for="abstain" class="flex items-center gap-2 cursor-pointer">
                                <Minus class="h-4 w-4 text-gray-600" />
                                Me abstengo
                            </Label>
                        </div>
                    </RadioGroup>
                </div>
                
                <!-- Multiple choice vote -->
                <div v-else-if="vote.type === 'multiple_choice'">
                    <Label class="text-base font-medium">Selecciona una opción:</Label>
                    <RadioGroup v-model="selectedOptionId" class="mt-3">
                        <div 
                            v-for="option in vote.options" 
                            :key="option.id"
                            class="flex items-start space-x-3 p-3 rounded-lg border hover:bg-blue-50"
                        >
                            <RadioGroupItem :value="option.id" :id="`option-${option.id}`" class="mt-1" />
                            <div class="flex-1">
                                <Label :for="`option-${option.id}`" class="cursor-pointer font-medium">
                                    {{ option.title }}
                                </Label>
                                <p v-if="option.description" class="text-sm text-gray-600 mt-1">
                                    {{ option.description }}
                                </p>
                                <p v-if="option.value" class="text-sm font-medium text-blue-600 mt-1">
                                    Valor: {{ option.value }}
                                </p>
                            </div>
                        </div>
                    </RadioGroup>
                </div>
                
                <!-- Quantitative vote -->
                <div v-else-if="vote.type === 'quantitative'">
                    <Label for="quantitative" class="text-base font-medium">Ingresa tu valor:</Label>
                    <Input
                        id="quantitative"
                        v-model.number="quantitativeValue"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="mt-2"
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Ingresa el valor numérico para esta votación
                    </p>
                </div>
                
                <!-- Error message -->
                <Alert v-if="error" variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>
            </div>
            
            <!-- Cannot vote message -->
            <div v-else class="text-center py-6">
                <Vote class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-gray-600">
                    <span v-if="!vote.can_vote">Esta votación no está disponible</span>
                    <span v-else-if="!canVote">No tienes permisos para votar</span>
                    <span v-else>Votación no disponible</span>
                </p>
            </div>
            
            <!-- Results (if vote is closed) -->
            <div v-if="vote.status === 'closed' && vote.results" class="mt-6 space-y-4">
                <h4 class="font-medium text-gray-900">Resultados</h4>
                
                <!-- Yes/No results -->
                <div v-if="vote.type === 'yes_no'" class="space-y-2">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <span class="flex items-center gap-2">
                            <ThumbsUp class="h-4 w-4 text-green-600" />
                            Sí
                        </span>
                        <span class="font-medium">{{ vote.results.yes?.percentage || 0 }}%</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                        <span class="flex items-center gap-2">
                            <ThumbsDown class="h-4 w-4 text-red-600" />
                            No
                        </span>
                        <span class="font-medium">{{ vote.results.no?.percentage || 0 }}%</span>
                    </div>
                    <div v-if="vote.allows_abstention" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="flex items-center gap-2">
                            <Minus class="h-4 w-4 text-gray-600" />
                            Abstenciones
                        </span>
                        <span class="font-medium">{{ vote.results.abstain?.percentage || 0 }}%</span>
                    </div>
                </div>
                
                <!-- Multiple choice results -->
                <div v-else-if="vote.type === 'multiple_choice'" class="space-y-2">
                    <div 
                        v-for="result in vote.results" 
                        :key="result.option_id"
                        class="flex items-center justify-between p-3 bg-blue-50 rounded-lg"
                    >
                        <span>{{ result.option_title }}</span>
                        <span class="font-medium">{{ result.percentage }}%</span>
                    </div>
                </div>
                
                <!-- Quantitative results -->
                <div v-else-if="vote.type === 'quantitative'" class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-gray-50 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Total</p>
                        <p class="text-xl font-bold">{{ vote.results.total_value || 0 }}</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Promedio</p>
                        <p class="text-xl font-bold">{{ parseFloat(vote.results.average_value || 0).toFixed(2) }}</p>
                    </div>
                </div>
            </div>
        </CardContent>
        
        <CardFooter v-if="canVote && vote.can_vote && !hasVoted">
            <Button 
                @click="submitVote" 
                :disabled="!isValidSelection || isSubmitting"
                class="w-full"
                size="lg"
            >
                <Vote class="mr-2 h-4 w-4" />
                {{ isSubmitting ? 'Enviando voto...' : 'Enviar Voto' }}
            </Button>
        </CardFooter>
    </Card>
</template>