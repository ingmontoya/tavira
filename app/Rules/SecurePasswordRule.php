<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecurePasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $config = config('security.password');

        // Check minimum length
        if (strlen($value) < $config['min_length']) {
            $fail("The :attribute must be at least {$config['min_length']} characters long.");

            return;
        }

        // Check for uppercase letters
        if ($config['require_uppercase'] && ! preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');

            return;
        }

        // Check for lowercase letters
        if ($config['require_lowercase'] && ! preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');

            return;
        }

        // Check for numbers
        if ($config['require_numbers'] && ! preg_match('/[0-9]/', $value)) {
            $fail('The :attribute must contain at least one number.');

            return;
        }

        // Check for symbols
        if ($config['require_symbols'] && ! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('The :attribute must contain at least one special character.');

            return;
        }

        // Check for common passwords
        if ($config['prevent_common_passwords'] && $this->isCommonPassword($value)) {
            $fail('The :attribute is too common. Please choose a more secure password.');

            return;
        }

        // Check for personal information (basic check)
        if ($config['prevent_personal_info'] && $this->containsPersonalInfo($value)) {
            $fail('The :attribute should not contain personal information.');

            return;
        }
    }

    /**
     * Check if password is in common passwords list.
     */
    protected function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '123456', '123456789', '12345678',
            'qwerty', 'abc123', 'password1', 'admin', 'root', 'user',
            'welcome', 'login', 'pass', 'secret', 'guest', 'test',
            'demo', 'default', 'changeme', 'temp', 'temporary',
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Check if password contains personal information.
     */
    protected function containsPersonalInfo(string $password): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $personalInfo = [
            strtolower($user->name ?? ''),
            strtolower($user->email ?? ''),
            strtolower(explode('@', $user->email ?? '')[0] ?? ''),
        ];

        $personalInfo = array_filter($personalInfo);

        foreach ($personalInfo as $info) {
            if (strlen($info) > 3 && str_contains(strtolower($password), $info)) {
                return true;
            }
        }

        return false;
    }
}
