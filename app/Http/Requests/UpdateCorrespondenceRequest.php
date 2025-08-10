<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCorrespondenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sender_name' => 'required|string|max:255',
            'sender_company' => 'nullable|string|max:255',
            'type' => 'required|in:package,letter,document,other',
            'description' => 'required|string|max:1000',
            'apartment_id' => 'required|exists:apartments,id',
            'requires_signature' => 'boolean',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sender_name' => 'nombre del remitente',
            'sender_company' => 'empresa del remitente',
            'type' => 'tipo de correspondencia',
            'description' => 'descripción',
            'apartment_id' => 'apartamento',
            'requires_signature' => 'requiere firma',
            'photos' => 'fotos de evidencia',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'No se pueden subir más de 5 fotos de evidencia.',
            'photos.*.image' => 'Todos los archivos deben ser imágenes.',
            'photos.*.mimes' => 'Las fotos deben estar en formato JPEG, PNG o JPG.',
            'photos.*.max' => 'Cada foto no puede ser mayor a 2MB.',
        ];
    }
}
