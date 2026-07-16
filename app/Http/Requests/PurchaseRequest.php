<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PurchaseType;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_column(PurchaseType::cases(), 'value'))],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'is_recurring' => ['required', 'boolean'],
            'card_id' => ['nullable', 'exists:cards,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installments_total' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'name.max' => 'O nome não pode ter mais de 255 caracteres',
            'type.required' => 'O tipo é obrigatório',
            'type.in' => 'Tipo inválido',
            'payment_day.min' => 'O dia de pagamento deve ser entre 1 e 31',
            'payment_day.max' => 'O dia de pagamento deve ser entre 1 e 31',
            'card_id.exists' => 'Cartão não encontrado',
            'amount.required' => 'O valor é obrigatório',
            'amount.min' => 'O valor deve ser maior que zero',
            'installments_total.min' => 'O total de parcelas deve ser maior que zero',
            'start_date.required' => 'A data de início é obrigatória',
            'start_date.date' => 'Data inválida',
        ];
    }
}
