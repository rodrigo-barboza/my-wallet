<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'start_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'repeat_count' => ['required', 'integer', 'min:1', 'max:12'],
            'group_id' => ['nullable', 'integer', 'exists:income_groups,id'],
        ];
    }
}
