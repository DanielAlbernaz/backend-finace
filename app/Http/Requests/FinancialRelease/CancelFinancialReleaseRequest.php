<?php

namespace App\Http\Requests\FinancialRelease;

use Illuminate\Foundation\Http\FormRequest;

class CancelFinancialReleaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // A autorização será feita no Controller via Policy
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
            // ID da parcela a cancelar (obrigatório)
            'release_id' => [
                'required',
                'integer',
                'exists:financial_releases,id'
            ],

            // Flag para cancelar todas as parcelas futuras do mesmo parcelamento
            'cancel_all_future' => [
                'nullable',
                'boolean'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'release_id.required' => 'É necessário informar o ID da parcela a cancelar.',
            'release_id.integer' => 'O ID da parcela deve ser um número inteiro.',
            'release_id.exists' => 'A parcela informada não existe.',
            'cancel_all_future.boolean' => 'O campo cancel_all_future deve ser true ou false.',
        ];
    }
}
