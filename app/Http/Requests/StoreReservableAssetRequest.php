<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservableAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin_conjunto', 'superadmin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conjunto_config_id' => 'required|exists:conjunto_configs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:common_area,amenity,facility,sports,recreation,meeting_room,event_space',
            'availability_rules' => 'nullable|array',
            'availability_rules.allowed_days' => 'nullable|array',
            'availability_rules.allowed_days.*' => 'integer|min:0|max:6',
            'availability_rules.time_slots' => 'nullable|array',
            'availability_rules.time_slots.*.start' => 'nullable|date_format:H:i',
            'availability_rules.time_slots.*.end' => 'nullable|date_format:H:i|after:availability_rules.time_slots.*.start',
            'max_reservations_per_user' => 'required|integer|min:0|max:10',
            'reservation_duration_minutes' => 'required|integer|min:15|max:1440',
            'advance_booking_days' => 'required|integer|min:1|max:365',
            'reservation_cost' => 'required|numeric|min:0|max:999999.99',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'conjunto_config_id' => 'configuración del conjunto',
            'name' => 'nombre',
            'description' => 'descripción',
            'type' => 'tipo de activo',
            'availability_rules.allowed_days' => 'días permitidos',
            'availability_rules.time_slots' => 'horarios disponibles',
            'max_reservations_per_user' => 'máximo de reservas por usuario',
            'reservation_duration_minutes' => 'duración de reserva en minutos',
            'advance_booking_days' => 'días máximos de reserva anticipada',
            'reservation_cost' => 'costo de reserva',
            'requires_approval' => 'requiere aprobación',
            'is_active' => 'activo',
            'image' => 'imagen',
            'metadata' => 'metadata',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de activo debe ser uno de los valores permitidos.',
            'availability_rules.allowed_days.*.min' => 'Los días permitidos deben estar entre 0 (domingo) y 6 (sábado).',
            'availability_rules.allowed_days.*.max' => 'Los días permitidos deben estar entre 0 (domingo) y 6 (sábado).',
            'availability_rules.time_slots.*.start.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'availability_rules.time_slots.*.end.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'availability_rules.time_slots.*.end.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'max_reservations_per_user.max' => 'El máximo de reservas por usuario no puede exceder 10.',
            'reservation_duration_minutes.min' => 'La duración mínima de reserva es de 15 minutos.',
            'reservation_duration_minutes.max' => 'La duración máxima de reserva es de 24 horas (1440 minutos).',
            'advance_booking_days.max' => 'El período máximo de reserva anticipada es de 365 días.',
            'reservation_cost.max' => 'El costo de reserva no puede exceder $999,999.99.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe estar en formato JPEG, PNG o JPG.',
            'image.max' => 'La imagen no puede ser mayor a 2MB.',
        ];
    }
}
