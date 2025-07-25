<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Student {
    id: number;
    student_code: string;
    first_name: string;
    last_name: string;
    document_id: string;
    gender: string;
    birth_date: string;
    personal_email: string;
    institutional_email: string;
    phone: string;
    group: string;
    program_id: number;
    current_semester_id: number;
    credits_completed: number;
    total_credits: number;
    progress_rate: number;
    dpto: string;
    city: string;
    address: string;
    initial_status: string;
    country: string;
}

interface Program {
    id: number;
    name: string;
}

interface Semester {
    id: number;
    name: string;
    number: number;
}

interface Props {
    student: Student;
    programs?: Program[];
    semesters?: Semester[];
}

const props = defineProps<Props>();

const breadcrumbItems = computed(() => [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Estudiantes',
        href: '/students',
    },
    {
        title: 'Editar estudiante',
        href: `/students/${props.student.id}/edit`,
    },
]);

const form = useForm({
    student_code: props.student.student_code,
    first_name: props.student.first_name,
    last_name: props.student.last_name,
    document_id: props.student.document_id,
    gender: props.student.gender,
    birth_date: props.student.birth_date,
    personal_email: props.student.personal_email,
    institutional_email: props.student.institutional_email,
    phone: props.student.phone,
    group: props.student.group,
    program_id: props.student.program_id,
    current_semester_id: props.student.current_semester_id,
    credits_completed: props.student.credits_completed,
    total_credits: props.student.total_credits,
    progress_rate: props.student.progress_rate,
    dpto: props.student.dpto,
    city: props.student.city,
    address: props.student.address,
    initial_status: props.student.initial_status,
    country: props.student.country,
});

const submit = () => {
    form.put(`/students/${props.student.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/students');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="flex h-full w-full max-w-4xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Head title="Editar estudiante" />
            <HeadingSmall
                title="Editar estudiante"
                :description="`Modifica la información de ${props.student.first_name} ${props.student.last_name}`"
            />

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Información Personal -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Personal</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="student_code">Código de Estudiante</Label>
                            <Input id="student_code" v-model="form.student_code" placeholder="Ej: 202012345" required />
                            <InputError :message="form.errors.student_code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="document_id">Documento de Identidad</Label>
                            <Input id="document_id" v-model="form.document_id" placeholder="Ej: 1234567890" required />
                            <InputError :message="form.errors.document_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="first_name">Nombres</Label>
                            <Input id="first_name" v-model="form.first_name" placeholder="Ej: Juan Carlos" required />
                            <InputError :message="form.errors.first_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="last_name">Apellidos</Label>
                            <Input id="last_name" v-model="form.last_name" placeholder="Ej: Pérez González" required />
                            <InputError :message="form.errors.last_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="gender">Género</Label>
                            <Select v-model="form.gender">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione género" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Género</SelectLabel>
                                        <SelectItem value="M">Masculino</SelectItem>
                                        <SelectItem value="F">Femenino</SelectItem>
                                        <SelectItem value="O">Otro</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.gender" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="birth_date">Fecha de Nacimiento</Label>
                            <Input id="birth_date" type="date" v-model="form.birth_date" required />
                            <InputError :message="form.errors.birth_date" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Información de Contacto -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información de Contacto</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="personal_email">Email Personal</Label>
                            <Input id="personal_email" type="email" v-model="form.personal_email" placeholder="ejemplo@correo.com" required />
                            <InputError :message="form.errors.personal_email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="institutional_email">Email Institucional</Label>
                            <Input
                                id="institutional_email"
                                type="email"
                                v-model="form.institutional_email"
                                placeholder="ejemplo@universidad.edu"
                                required
                            />
                            <InputError :message="form.errors.institutional_email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Teléfono</Label>
                            <Input id="phone" v-model="form.phone" placeholder="Ej: +57 300 123 4567" required />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="country">País</Label>
                            <Input id="country" v-model="form.country" placeholder="Ej: Colombia" required />
                            <InputError :message="form.errors.country" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="dpto">Departamento</Label>
                            <Input id="dpto" v-model="form.dpto" placeholder="Ej: Antioquia" required />
                            <InputError :message="form.errors.dpto" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="city">Ciudad</Label>
                            <Input id="city" v-model="form.city" placeholder="Ej: Medellín" required />
                            <InputError :message="form.errors.city" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="address">Dirección</Label>
                            <Input id="address" v-model="form.address" placeholder="Ej: Calle 123 # 45-67" required />
                            <InputError :message="form.errors.address" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Información Académica -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Académica</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="program_id">Programa</Label>
                            <Select v-model="form.program_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione programa" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Programas</SelectLabel>
                                        <SelectItem v-for="program in programs" :key="program.id" :value="program.id.toString()">
                                            {{ program.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.program_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="current_semester_id">Semestre Actual</Label>
                            <Select v-model="form.current_semester_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione semestre" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Semestres</SelectLabel>
                                        <SelectItem v-for="semester in semesters" :key="semester.id" :value="semester.id.toString()">
                                            {{ semester.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.current_semester_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="group">Grupo</Label>
                            <Input id="group" v-model="form.group" placeholder="Ej: A1" required />
                            <InputError :message="form.errors.group" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="initial_status">Estado Inicial</Label>
                            <Select v-model="form.initial_status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Estados</SelectLabel>
                                        <SelectItem value="active">Activo</SelectItem>
                                        <SelectItem value="inactive">Inactivo</SelectItem>
                                        <SelectItem value="graduated">Graduado</SelectItem>
                                        <SelectItem value="suspended">Suspendido</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.initial_status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="credits_completed">Créditos Completados</Label>
                            <Input id="credits_completed" type="number" v-model="form.credits_completed" placeholder="0" min="0" />
                            <InputError :message="form.errors.credits_completed" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="total_credits">Total de Créditos</Label>
                            <Input id="total_credits" type="number" v-model="form.total_credits" placeholder="0" min="0" />
                            <InputError :message="form.errors.total_credits" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="progress_rate">Tasa de Progreso (%)</Label>
                            <Input id="progress_rate" type="number" v-model="form.progress_rate" placeholder="0" min="0" max="100" step="0.1" />
                            <InputError :message="form.errors.progress_rate" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing"> Actualizar Estudiante </Button>

                    <Button type="button" variant="outline" @click="router.visit('/students')"> Cancelar </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Actualizado exitosamente.</p>
                    </Transition>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
