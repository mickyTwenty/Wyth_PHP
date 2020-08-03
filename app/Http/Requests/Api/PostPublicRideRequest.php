<?php
namespace App\Http\Requests\Api;

use App\Http\Requests\Jsonify as Request;

class PostPublicRideRequest extends Request
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
            'email.unique' => 'Email already found in our system, please try another one.',
            'phone.phone'  => 'Please enter your valid phone number.',
        ];
    }

    /**
     * Get attributes value.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'is_roundtrip'       => 'roundtrip',
            'is_enabled_booknow' => 'booknow',
            'desired_gender'     => 'gender',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'origin_latitude'       => 'required',
            'origin_longitude'      => 'required',
            'origin_title'          => 'required|string|max:1000',
            'destination_latitude'  => 'required',
            'destination_longitude' => 'required',
            'destination_title'     => 'required|string|max:1000',
            'expected_start_date'   => 'required|string|size:13',
            'date_returning'        => 'required_if:is_roundtrip,1|string|size:13',
            'expected_distance'     => 'required',
            'expected_duration'     => 'required',
            'time_range'            => 'required|integer|min:1|max:7',
            'time_range_returning'  => 'required_if:is_roundtrip,1|integer|min:1|max:7',
            'seats_total'           => 'required|integer|min:1|max:8',
            'seats_total_returning' => 'required_if:is_roundtrip,1|integer|min:1|max:7',
            'stepped_route'         => 'required',
            'min_estimates'         => 'required|decimal:7,2',
            'max_estimates'         => 'required|decimal:7,2',
            'is_roundtrip'          => 'required|in:0,1',
            'desired_gender'        => 'required|in:1,2,3',
            'is_enabled_booknow'    => 'in:0,1',
            'booknow_price'         => 'required_if:is_enabled_booknow,1|decimal:7,2',
            'payout_type'           => 'required|in:expedited,standard',
        ];

        return $rules;
    }
}
