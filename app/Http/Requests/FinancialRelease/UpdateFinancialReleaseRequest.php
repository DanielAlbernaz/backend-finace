<?php

namespace App\Http\Requests\FinancialRelease;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialReleaseRequest extends FormRequest
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
            'type' => ['sometimes', 'required', 'string', 'in:expense,revenue,despesa,receita'],
            'value' => ['sometimes', 'required', 'numeric'],
            'date' => ['sometimes', 'required', 'date'],
            'due_date' => ['sometimes', 'required', 'date'],
            'payment_date' => ['nullable', 'date'],
            'descrition' => ['nullable', 'string'],
            'observation' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'exists:App\Models\Category,id'],
            'repetition' => ['sometimes', 'required', 'string', 'in:only,installments,fixed,unico'],
            'status' => ['sometimes', 'string', 'in:pending,paid,overdue,cancelled,aberto'], // Permite atualizar status manualmente
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

        // Normaliza status "aberto" para "pending"
        if ($this->has('status') && $this->input('status') === 'aberto') {
            $this->merge(['status' => 'pending']);
        }
    }
}
