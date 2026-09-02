<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{8,30}$/'],
            'birth_date' => ['nullable', 'string', 'max:32', 'regex:/^-?\d{1,12}-\d{2}-\d{2}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'address_province' => ['nullable', 'string', 'max:120'],
            'address_ward' => ['nullable', 'string', 'max:160'],
            'address_detail' => ['nullable', 'string', 'max:250'],
        ];
    }

    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null ? Rule::unique(User::class) : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
