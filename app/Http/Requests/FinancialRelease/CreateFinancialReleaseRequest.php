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
            'type' => ['required', 'string', 'in:expense,revenue'],
            'value' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'payment_date' => ['date'],
            'descrition' => ['string'],
            'observation' => ['string'],
            'category_id' => ['required', 'exists:App\Models\Category,id'],
            'user_id' => ['required', 'exists:App\Models\User,id']
        ];
    }
}
