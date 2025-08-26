<?php

namespace App\Http\Requests;

use App\Models\ReservableAsset;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reservable_asset_id' => 'required|exists:reservable_assets,id',
            'apartment_id' => 'nullable|exists:apartments,id',
            'start_time' => [
                'required',
                'date',
                'after:now',
                function ($attribute, $value, $fail) {
                    if (!$this->reservable_asset_id) return;
                    
                    $asset = ReservableAsset::find($this->reservable_asset_id);
                    if (!$asset) return;

                    $startTime = Carbon::parse($value);
                    $maxDate = $asset->getMaxAdvanceBookingDate();
                    
                    if ($startTime->gt($maxDate)) {
                        $fail("La reserva no puede hacerse con más de {$asset->advance_booking_days} días de anticipación.");
                    }
                },
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
                function ($attribute, $value, $fail) {
                    if (!$this->start_time || !$this->reservable_asset_id) return;
                    
                    $asset = ReservableAsset::find($this->reservable_asset_id);
                    if (!$asset) return;

                    $startTime = Carbon::parse($this->start_time);
                    $endTime = Carbon::parse($value);
                    $durationMinutes = $startTime->diffInMinutes($endTime);
                    
                    if ($durationMinutes > $asset->reservation_duration_minutes) {
                        $durationHours = $asset->reservation_duration_minutes / 60;
                        $fail("La duración máxima de reserva es de {$durationHours} horas.");
                    }

                    if (!$asset->isAvailableAt($startTime, $endTime)) {
                        $fail('El activo no está disponible en el horario seleccionado.');
                    }
                },
            ],
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->reservable_asset_id) return;
            
            $asset = ReservableAsset::find($this->reservable_asset_id);
            if (!$asset) return;

            // Check if asset is active
            if (!$asset->is_active) {
                $validator->errors()->add('reservable_asset_id', 'El activo no está disponible para reservas.');
                return;
            }

            // Check if user can make more reservations for this asset
            if (!$asset->canUserReserve(auth()->id())) {
                $validator->errors()->add('reservable_asset_id', "Ya has alcanzado el máximo de {$asset->max_reservations_per_user} reserva(s) para este activo.");
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reservable_asset_id' => 'activo',
            'apartment_id' => 'apartamento',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'notes' => 'notas',
            'metadata' => 'metadata',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'start_time.after' => 'La hora de inicio debe ser en el futuro.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
        ];
    }
}
