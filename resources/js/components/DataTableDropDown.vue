<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { router } from '@inertiajs/vue3';
import { CheckCircle, Clock, Copy, Edit, Eye, MoreHorizontal, Play, QrCode, RefreshCw, Trash2, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    resident?: any;
    apartment?: any;
    invitation?: any;
    budget?: any;
}>();

const deleteDialogOpen = ref(false);
const approveDialogOpen = ref(false);

// Resident actions
const viewResident = () => {
    router.visit(`/residents/${props.resident.id}`);
};

const editResident = () => {
    router.visit(`/residents/${props.resident.id}/edit`);
};

const deleteResident = () => {
    if (confirm('¿Está seguro que desea eliminar este residente?')) {
        router.delete(`/residents/${props.resident.id}`, {
            preserveScroll: true,
        });
    }
};

const copyResidentCode = () => {
    navigator.clipboard.writeText(props.resident.document_number);
};

const approveResident = () => {
    router.post(`/residents/${props.resident.id}/approve`, {}, {
        preserveScroll: true,
    });
};

const rejectResident = () => {
    if (confirm('¿Está seguro que desea rechazar este residente? Su cuenta quedará inactiva.')) {
        router.post(`/residents/${props.resident.id}/reject`, {}, {
            preserveScroll: true,
        });
    }
};

// Apartment actions
const viewApartment = () => {
    router.visit(`/apartments/${props.apartment.id}`);
};

const editApartment = () => {
    router.visit(`/apartments/${props.apartment.id}/edit`);
};

const deleteApartment = () => {
    if (confirm('¿Está seguro que desea eliminar este apartamento?')) {
        router.delete(`/apartments/${props.apartment.id}`);
    }
};

const copyApartmentNumber = () => {
    navigator.clipboard.writeText(props.apartment.number);
};

// Invitation actions
const viewInvitation = () => {
    router.visit(`/invitations/${props.invitation.id}`);
};

const getInvitationUrl = () => {
    router.visit(`/invitations/${props.invitation.id}/url`);
};

const resendInvitation = () => {
    router.post(`/invitations/${props.invitation.id}/resend`);
};

const deleteInvitation = () => {
    if (confirm('¿Está seguro que desea eliminar esta invitación?')) {
        router.delete(`/invitations/${props.invitation.id}`);
    }
};

// Budget actions
const viewBudget = () => {
    router.visit(`/accounting/budgets/${props.budget.id}`);
};

const editBudget = () => {
    router.visit(`/accounting/budgets/${props.budget.id}/edit`);
};

const confirmApproveBudget = () => {
    approveDialogOpen.value = false;
    router.post(`/accounting/budgets/${props.budget.id}/approve`);
};

const activateBudget = () => {
    if (confirm('¿Está seguro que desea activar este presupuesto? Esto desactivará otros presupuestos activos del mismo año.')) {
        router.post(`/accounting/budgets/${props.budget.id}/activate`);
    }
};

const closeBudget = () => {
    if (confirm('¿Está seguro que desea cerrar este presupuesto?')) {
        router.post(`/accounting/budgets/${props.budget.id}/close`);
    }
};

const confirmDeleteBudget = () => {
    deleteDialogOpen.value = false;
    router.delete(`/accounting/budgets/${props.budget.id}`);
};

const viewBudgetExecution = () => {
    router.visit(`/accounting/budgets/${props.budget.id}/execution`);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0">
                <span class="sr-only">Open menu</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <!-- Resident actions -->
            <template v-if="resident">
                <DropdownMenuItem @click="copyResidentCode">
                    <Copy class="mr-2 h-4 w-4" />
                    Copiar documento
                </DropdownMenuItem>
                <DropdownMenuItem @click="viewResident">
                    <Eye class="mr-2 h-4 w-4" />
                    Ver residente
                </DropdownMenuItem>
                <DropdownMenuItem @click="editResident">
                    <Edit class="mr-2 h-4 w-4" />
                    Editar
                </DropdownMenuItem>
                <!-- Approval actions for pending residents -->
                <template v-if="resident.status === 'Pending'">
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="approveResident" class="text-green-600">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        Aprobar
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="rejectResident" class="text-red-600">
                        <XCircle class="mr-2 h-4 w-4" />
                        Rechazar
                    </DropdownMenuItem>
                </template>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="deleteResident" class="text-red-600">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Eliminar
                </DropdownMenuItem>
            </template>

            <!-- Apartment actions -->
            <template v-if="apartment">
                <DropdownMenuItem @click="copyApartmentNumber">
                    <Copy class="mr-2 h-4 w-4" />
                    Copiar número
                </DropdownMenuItem>
                <DropdownMenuItem @click="viewApartment">
                    <Eye class="mr-2 h-4 w-4" />
                    Ver apartamento
                </DropdownMenuItem>
                <DropdownMenuItem @click="editApartment">
                    <Edit class="mr-2 h-4 w-4" />
                    Editar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="deleteApartment" class="text-red-600">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Eliminar
                </DropdownMenuItem>
            </template>

            <!-- Invitation actions -->
            <template v-if="invitation">
                <DropdownMenuItem @click="getInvitationUrl">
                    <QrCode class="mr-2 h-4 w-4" />
                    Ver URL y QR
                </DropdownMenuItem>
                <DropdownMenuItem @click="viewInvitation">
                    <Eye class="mr-2 h-4 w-4" />
                    Ver invitación
                </DropdownMenuItem>
                <DropdownMenuItem @click="resendInvitation" v-if="!invitation.accepted_at">
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Reenviar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="deleteInvitation" class="text-red-600">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Eliminar
                </DropdownMenuItem>
            </template>

            <!-- Budget actions -->
            <template v-if="budget">
                <DropdownMenuItem @click="viewBudget">
                    <Eye class="mr-2 h-4 w-4" />
                    Ver presupuesto
                </DropdownMenuItem>
                <DropdownMenuItem @click="editBudget" v-if="budget.status === 'Draft'">
                    <Edit class="mr-2 h-4 w-4" />
                    Editar
                </DropdownMenuItem>
                <DropdownMenuItem @click="viewBudgetExecution">
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Ver ejecución
                </DropdownMenuItem>
                <DropdownMenuSeparator />

                <!-- Estado: Aprobar (Draft -> Approved) - Solo para usuarios del Concejo -->
                <AlertDialog v-model:open="approveDialogOpen">
                    <AlertDialogTrigger as-child>
                        <DropdownMenuItem
                            @click="$event.preventDefault()"
                            v-if="budget.status === 'Draft' && budget.can_approve"
                            class="text-blue-600"
                        >
                            <CheckCircle class="mr-2 h-4 w-4" />
                            Aprobar
                        </DropdownMenuItem>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Aprobar Presupuesto</AlertDialogTitle>
                            <AlertDialogDescription>
                                ¿Está seguro que desea aprobar el presupuesto "{{ budget.name }}" del año {{ budget.year }}? <br /><br />
                                Esta acción marcará el presupuesto como aprobado por el Concejo de Administración y permitirá su posterior activación.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction @click="confirmApproveBudget" class="bg-blue-600 hover:bg-blue-700"
                                >Aprobar Presupuesto</AlertDialogAction
                            >
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                <!-- Estado: Activar (Approved -> Active) -->
                <DropdownMenuItem @click="activateBudget" v-if="budget.status === 'Approved'" class="text-green-600">
                    <Play class="mr-2 h-4 w-4" />
                    Activar
                </DropdownMenuItem>

                <!-- Estado: Cerrar (Active -> Closed) -->
                <DropdownMenuItem @click="closeBudget" v-if="budget.status === 'Active'" class="text-orange-600">
                    <Clock class="mr-2 h-4 w-4" />
                    Cerrar
                </DropdownMenuItem>

                <DropdownMenuSeparator v-if="budget.status !== 'Active'" />
                <!-- Eliminar con AlertDialog -->
                <AlertDialog v-model:open="deleteDialogOpen">
                    <AlertDialogTrigger as-child>
                        <DropdownMenuItem @click="$event.preventDefault()" v-if="budget.status !== 'Active'" class="text-red-600">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                        </DropdownMenuItem>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Eliminar Presupuesto</AlertDialogTitle>
                            <AlertDialogDescription>
                                ¿Está seguro que desea eliminar el presupuesto "{{ budget.name }}" del año {{ budget.year }}? <br /><br />
                                <strong class="text-red-600">Esta acción no se puede deshacer</strong> y se eliminarán todas las partidas
                                presupuestales asociadas.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction @click="confirmDeleteBudget" class="bg-red-600 hover:bg-red-700"
                                >Eliminar Presupuesto</AlertDialogAction
                            >
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
