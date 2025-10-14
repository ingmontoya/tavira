<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePqrsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public endpoint - anyone can submit
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:peticion,queja,reclamo,sugerencia',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'submitter_name' => 'required_without:user_id|string|max:255',
            'submitter_email' => 'required_without:user_id|email|max:255',
            'submitter_phone' => 'nullable|string|max:20',
            'apartment_id' => 'nullable|exists:apartments,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Debe seleccionar un tipo de PQRS',
            'type.in' => 'El tipo de PQRS no es válido',
            'subject.required' => 'El asunto es obligatorio',
            'subject.max' => 'El asunto no puede exceder 255 caracteres',
            'description.required' => 'La descripción es obligatoria',
            'description.min' => 'La descripción debe tener al menos 10 caracteres',
            'submitter_name.required_without' => 'El nombre es obligatorio',
            'submitter_email.required_without' => 'El correo electrónico es obligatorio',
            'submitter_email.email' => 'Debe proporcionar un correo electrónico válido',
            'apartment_id.exists' => 'El apartamento seleccionado no existe',
        ];
    }
}
