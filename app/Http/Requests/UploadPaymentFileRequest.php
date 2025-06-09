<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentFileRequest extends FormRequest
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
            'file' => 'required|file|mimes:csv,txt|max:' . config('iposg.storage.s3.payments.max_file_size'),
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A CSV file is required.',
            'file.mimes' => 'Only CSV or TXT files are allowed.',
            'file.max' => 'File must not be larger than 10MB.'
        ];
    }
}
