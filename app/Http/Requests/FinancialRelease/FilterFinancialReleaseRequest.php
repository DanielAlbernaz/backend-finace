<?php

namespace App\Http\Requests\FinancialRelease;

use Illuminate\Foundation\Http\FormRequest;

class FilterFinancialReleaseRequest extends FormRequest
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
            // Filtros de data (competência)
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],

            // Filtros de vencimento
            'due_date_from' => ['nullable', 'date'],
            'due_date_to' => ['nullable', 'date'],

            // Filtros de pagamento
            'payment_date_from' => ['nullable', 'date'],
            'payment_date_to' => ['nullable', 'date'],
            'payment_date' => ['nullable', 'date'],

            // Filtros básicos
            'type' => ['nullable', 'string', 'in:expense,revenue'],
            'status' => ['nullable', 'string', 'in:pending,paid,overdue,cancelled'],
            'repetition' => ['nullable', 'string', 'in:only,installments,fixed'],
            'exclude_cancelled' => ['nullable', 'boolean'], // Se true, exclui cancelados da listagem (padrão: retorna cancelados)

            // Filtros de valor
            'value_min' => ['nullable', 'numeric', 'min:0'],
            'value_max' => ['nullable', 'numeric', 'min:0'],

            // Filtros de relacionamento
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'installment_id' => ['nullable', 'integer', 'exists:installments,id'],

            // Filtros de texto
            'descrition' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:255'],
            'portion' => ['nullable', 'string', 'max:50'],

            // Ordenação
            'order_by' => ['nullable', 'string', 'in:date,due_date,payment_date,value,created_at,updated_at'],
            'order_direction' => ['nullable', 'string', 'in:asc,desc'],

            // Paginação
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Prepare the data for validation.
     * Define valores padrão para paginação
     */
    protected function prepareForValidation()
    {
        // Define per_page padrão como 25 se não informado
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 100]);
        }

        // Define page padrão como 1 se não informado
        if (!$this->has('page')) {
            $this->merge(['page' => 1]);
        }
    }
}
