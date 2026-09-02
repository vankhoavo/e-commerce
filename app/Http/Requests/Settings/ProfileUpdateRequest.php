<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'birth_date.date_format' => 'Ngày sinh không đúng định dạng.',
            'birth_date.before_or_equal' => 'Ngày sinh không được ở tương lai.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá 30 ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',
        ];
    }
}
