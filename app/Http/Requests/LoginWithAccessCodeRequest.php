<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginWithAccessCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'access_code' => 'required|string|max:10',
            'remember' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'access_code.required' => 'El código de acceso es obligatorio.',
            'access_code.string' => 'El código de acceso debe ser texto.',
            'access_code.max' => 'El código de acceso no puede tener más de 10 caracteres.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('access_code')) {
            $this->merge([
                'access_code' => strtoupper(trim($this->access_code)),
            ]);
        }
    }

    // Removed automatic validation since it's now handled in the controller

    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function clearRateLimiter()
    {
        RateLimiter::clear($this->throttleKey());
    }

    public function throttleKey()
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
