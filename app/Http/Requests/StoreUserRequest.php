<?php

namespace App\Http\Requests;

use App\Rules\UniqueAcrossTables;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public $ignoreId;
    public $ignoreTable;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        if ($this->phoneNumber) {
            $this->merge([
                'phoneNumber' => $this->formatPhoneNumber($this->phoneNumber)
            ]);
        }
        if ($this->company_phone) {
            $this->merge([
                'company_phone' => $this->formatPhoneNumber($this->company_phone)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'nullable',
                'email',
                'sometimes',
                new UniqueAcrossTables('email', $this->ignoreId,
                    $this->ignoreTable)
            ],
            'phoneNumber' => [
                'nullable',
                'sometimes',
                new UniqueAcrossTables('phoneNumber', $this->ignoreId,
                    $this->ignoreTable)
            ],
            'company_email' => [
                'nullable',
                'sometimes',
                'email',
                new UniqueAcrossTables('company_email', $this->ignoreId, $this->ignoreTable)
            ],
            'company_phone' => [
                'nullable',
                new UniqueAcrossTables('company_phone', $this->ignoreId,
                    $this->ignoreTable)
            ]
        ];
    }

    public function messages(): array
    {
        return [
            // // 'email.required' => 'email required',
            'email.email' => 'email invalid',
            'company_email.email' => 'email invalid',
            // 'phoneNumber.required' => 'phoneNumber required',
            // 'company_phone.required'=>'phoneNumber required'
        ];
    }

    protected function formatPhoneNumber($phone)
    {
        if (preg_match('/^09\d{8}$/', $phone)) {
            return '963' . substr($phone, 1);
        }
        return $phone;
    }
}
