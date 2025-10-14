<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePqrsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users with permission can update
        return $this->user()?->can('manage_pqrs') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pendiente,en_revision,en_proceso,resuelta,cerrada',
            'priority' => 'sometimes|in:baja,media,alta,urgente',
            'assigned_to' => 'nullable|exists:users,id',
            'admin_response' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'El estado seleccionado no es válido',
            'priority.in' => 'La prioridad seleccionada no es válida',
            'assigned_to.exists' => 'El usuario asignado no existe',
        ];
    }
}
