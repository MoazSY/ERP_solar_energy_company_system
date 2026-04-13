<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterAgencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;  // يمكن تعديل حسب الحاجة
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agency_name' => 'sometimes|string|max:255',
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // التحقق من تبعية الموقع: إذا تم تحديد neighborhood، يجب تحديد area و governorate
            if (isset($data['neighborhood_id'])) {
                if (!isset($data['area_id']) || !isset($data['governorate_id'])) {
                    $validator->errors()->add('location', 'you should specify area and governorate when you specify neighborhood');
                }
            }

            // إذا تم تحديد area، يجب تحديد governorate
            if (isset($data['area_id']) && !isset($data['governorate_id'])) {
                $validator->errors()->add('location', 'you should specify governorate when you specify area');
            }

            // التحقق من تبعية المنتج: إذا تم تحديد model_number، يجب تحديد product_name
            if (isset($data['model_number']) && !isset($data['product_name'])) {
                $validator->errors()->add('product', 'you should specify product name when you specify model number');
            }

            // إذا تم تحديد product_brand أو product_name أو model_number، يجب تحديد product_type
            if ((isset($data['product_brand']) || isset($data['product_name']) || isset($data['model_number'])) && !isset($data['product_type'])) {
                $validator->errors()->add('product', 'you should specify product type when you specify product details');
            }

            // التحقق من تبعية السعر: إذا تم تحديد currency أو product_price_min أو product_price_max، يجب تحديد معلومات المنتج
            if ((isset($data['currency']) || isset($data['product_price_min']) || isset($data['product_price_max'])) &&
                    (!isset($data['product_type']) || (!isset($data['product_brand']) && !isset($data['product_name']) && !isset($data['model_number'])))) {
                $validator->errors()->add('price', 'you should specify product information when you specify currency or price');
            }

            // التحقق من تبعية الخصم: إذا تم تحديد disscount_type أو disscount_value_min أو disscount_value_max، يجب تحديد معلومات المنتج
            if ((isset($data['disscount_type']) || isset($data['disscount_value_min']) || isset($data['disscount_value_max'])) &&
                    (!isset($data['product_type']) || (!isset($data['product_brand']) && !isset($data['product_name']) && !isset($data['model_number'])))) {
                $validator->errors()->add('discount', 'you should specify product information when you specify discount type or discount value');
            }

            // إذا تم تحديد disscount_value_min أو disscount_value_max، يجب تحديد disscount_type
            if ((isset($data['disscount_value_min']) || isset($data['disscount_value_max'])) && !isset($data['disscount_type'])) {
                $validator->errors()->add('discount', 'you should specify discount type when you specify discount value');
            }
        });
    }
}
