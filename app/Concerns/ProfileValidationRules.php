<?php

namespace App\Concerns;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /** @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>> */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{8,30}$/'],
            'birth_date' => [
                'nullable',
                'string',
                'max:32',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    $match = preg_match('/^(-?\d{1,12})-(\d{2})-(\d{2})$/', trim((string) $value), $parts);
                    if ($match !== 1) {
                        $fail('Ngày sinh không đúng định dạng.');
                        return;
                    }

                    $year = (int) $parts[1];
                    $month = (int) $parts[2];
                    $day = (int) $parts[3];

                    if ($year < -100000000000 || $year > 100000000000 || $month < 1 || $month > 12 || $day < 1) {
                        $fail('Ngày sinh không hợp lệ.');
                        return;
                    }

                    $leap = ($year % 4 === 0) && ($year % 100 !== 0 || $year % 400 === 0);
                    $days = [31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

                    if ($day > $days[$month - 1]) {
                        $fail('Ngày sinh không hợp lệ.');
                    }
                },
            ],
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
