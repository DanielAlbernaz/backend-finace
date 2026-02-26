<?php

namespace App\Http\Requests\FinancialRelease;

use Illuminate\Foundation\Http\FormRequest;

class CreateFinancialReleaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'type' => ['required', 'string', 'in:expense,revenue,despesa,receita'],
            'value' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'payment_date' => ['nullable', 'date'],
            'descrition' => ['nullable', 'string'],
            'observation' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:App\Models\Category,id'],
            'payment_method_id' => ['nullable', 'exists:App\Models\PaymentMethod,id'],
            'repetition' => ['required', 'string', 'in:only,installments,fixed,unico'],
            // periodicity só é obrigatória para 'fixed' (recorrente), não para 'installments' (parcelamento)
            'periodicity' => ['nullable', 'string', 'required_if:repetition,fixed', 'in:daily,weekly,monthly,annual'],
            // Para recorrência: mínimo 1, máximo 240
            'number_repetition' => ['nullable', 'integer', 'required_if:repetition,fixed', 'min:1', 'max:240'],
            // Para parcelamento: mínimo 2 parcelas, máximo 240
            'number_installments_repetition' => ['nullable', 'integer', 'required_if:repetition,installments', 'min:2', 'max:240'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Normaliza valores do frontend para o backend
        if ($this->has('repetition')) {
            $repetition = $this->input('repetition');
            if ($repetition === 'unico') {
                $this->merge(['repetition' => 'only']);
            }
        }

        if ($this->has('type')) {
            $type = $this->input('type');
            if ($type === 'receita') {
                $this->merge(['type' => 'revenue']);
            } elseif ($type === 'despesa') {
                $this->merge(['type' => 'expense']);
            }
        }
    }
}
