<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
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
     * Get the validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'code.unique' => 'Coupon already found in our system, please try another one.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->get('id');

        return [
            'code'           => 'required|unique:coupons,code,' . ($id ?: 'NULL') . ',id,deleted_at,NULL',
            'discount_type'  => 'required|in:1,2',
            'value'          => 'required',
            'available_from' => 'required|date_format:m/d/Y',
            'available_till' => 'required|date_format:m/d/Y|after_or_equal:available_from',
        ];
    }
}
