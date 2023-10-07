<?php

namespace App\Http\Requests\revenue;

use Illuminate\Foundation\Http\FormRequest;

class CreateRevenueRequest extends FormRequest
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
            'title' => ['required', 'string'],
            'descrition' => ['string'],
            'value' => ['required', 'numeric'],
            'payment_date' => ['date'],
            'expiration_date' => ['required', 'date'],
            'type_id' => ['required', 'exists:App\Models\Type,id']
        ];
    }
}
