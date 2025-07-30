<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\SecurePasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\'\.]+$/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                new SecurePasswordRule,
            ],
            'password_confirmation' => ['required', 'string'],
        ];

        // Add email validation for admin registration
        if ($this->routeIs('register.admin')) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users'];
        } else {
            // Token required for regular invitation-based registration
            $rules['token'] = ['required', 'string', 'exists:invitations,token'];
            
            // If token is for a mass invitation, email and apartment are required
            if ($this->has('token')) {
                $invitation = \App\Models\Invitation::where('token', $this->token)->first();
                if ($invitation && $invitation->is_mass_invitation) {
                    $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users'];
                    $rules['apartment_id'] = ['required', 'exists:apartments,id'];
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password_confirmation' => 'password confirmation',
        ];
    }
}
