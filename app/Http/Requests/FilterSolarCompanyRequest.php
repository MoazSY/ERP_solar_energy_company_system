<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterSolarCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'sometimes|string|max:255',
            'company_email' => 'sometimes|email|max:255',
            'company_phone' => 'sometimes|string|max:255',
            'company_status' => 'sometimes|string|max:255',
            'governorate_id' => 'sometimes|exists:governorates,id',
            'area_id' => 'sometimes|exists:areas,id',
            'neighborhood_id' => 'sometimes|exists:neighborhoods,id',
            'product_type' => 'sometimes|in:solar_panel,inverter,battery,accessory',
            'product_brand' => 'sometimes|string|max:255',
            'product_name' => 'sometimes|string|max:255',
            'model_number' => 'sometimes|string|max:255',
            'currency' => 'sometimes|in:USD,SY',
            'product_price_min' => 'sometimes|numeric|min:0',
            'product_price_max' => 'sometimes|numeric|min:0|gte:product_price_min',
            'disscount_type' => 'sometimes|in:percentage,fixed',
            'disscount_value_min' => 'sometimes|numeric|min:0',
            'disscount_value_max' => 'sometimes|numeric|min:0|gte:disscount_value_min',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            if (isset($data['neighborhood_id'])) {
                if (!isset($data['area_id']) || !isset($data['governorate_id'])) {
                    $validator->errors()->add('location', 'you should specify area and governorate when you specify neighborhood');
                }
            }

            if (isset($data['area_id']) && !isset($data['governorate_id'])) {
                $validator->errors()->add('location', 'you should specify governorate when you specify area');
            }

            if (isset($data['model_number']) && !isset($data['product_name'])) {
                $validator->errors()->add('product', 'you should specify product name when you specify model number');
            }

            if ((isset($data['product_brand']) || isset($data['product_name']) || isset($data['model_number'])) && !isset($data['product_type'])) {
                $validator->errors()->add('product', 'you should specify product type when you specify product details');
            }

            if ((isset($data['currency']) || isset($data['product_price_min']) || isset($data['product_price_max'])) &&
                    (!isset($data['product_type']) || (!isset($data['product_brand']) && !isset($data['product_name']) && !isset($data['model_number'])))) {
                $validator->errors()->add('price', 'you should specify product information when you specify currency or price');
            }

            if ((isset($data['disscount_type']) || isset($data['disscount_value_min']) || isset($data['disscount_value_max'])) &&
                    (!isset($data['product_type']) || (!isset($data['product_brand']) && !isset($data['product_name']) && !isset($data['model_number'])))) {
                $validator->errors()->add('discount', 'you should specify product information when you specify discount type or discount value');
            }

            if ((isset($data['disscount_value_min']) || isset($data['disscount_value_max'])) && !isset($data['disscount_type'])) {
                $validator->errors()->add('discount', 'you should specify discount type when you specify discount value');
            }
        });
    }
}
