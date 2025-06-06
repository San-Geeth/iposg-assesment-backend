<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|string',
            'customer_email' => 'required|email',
            'reference_no' => 'required|string',
            'payment_date' => 'required|date',
            'currency' => 'required|string',
            'amount' => 'required|numeric',
            'exchange_rate' => 'required|numeric',
            'usd_amount' => 'required|numeric',
            'exchange_rate_id' => 'nullable|integer',
            'processed' => 'required|boolean',
            'invoice_id' => 'nullable|integer',
            'file_id' => 'nullable|uuid',
        ];
    }
}
