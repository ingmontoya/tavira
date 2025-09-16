<?php

namespace App\Http\Requests\Auth;

use App\Rules\SecurePasswordRule;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
        // For API requests (mobile app), use simpler validation for login
        if ($this->expectsJson() || $this->is('api/*')) {
            return [
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string'],
                'remember' => ['boolean'],
            ];
        }

        // For web requests, use strict validation
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'lowercase',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'password' => ['required', 'string', new SecurePasswordRule],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.regex' => 'Please enter a valid email address.',
            'email.lowercase' => 'Email address must be lowercase.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Only apply transformations for web requests
        if (! $this->expectsJson() && ! $this->is('api/*')) {
            $this->merge([
                'email' => strtolower(trim($this->email ?? '')),
            ]);
        } else {
            // For API requests, just trim email
            $this->merge([
                'email' => trim($this->email ?? ''),
            ]);
        }
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // For API requests, explicitly use web guard
        $guard = ($this->expectsJson() || $this->is('api/*')) ? 'web' : null;

        if (! Auth::guard($guard)->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
