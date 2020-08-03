<?php
namespace App\Http\Requests\Api;

use App\Http\Requests\Jsonify as Request;

class SearchRideRequest extends Request {

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
            'email.unique'    => 'Email already found in our system, please try another one.',
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
            'is_roundtrip'   => 'roundtrip',
            'desired_gender' => 'gender',
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
            'destination_latitude'  => 'required',
            'destination_longitude' => 'required',
            'expected_start_date'   => 'required|string|size:13',
            'date_returning'        => 'required_if:is_roundtrip,1|string|size:13',
            'time_range'            => 'integer|max:7',
            'is_roundtrip'          => 'in:0,1',
            'desired_gender'        => 'in:1,2,3',
            'rating'                => 'integer|min:0,max:1',
        ];

        return $rules;
    }

    /*public function all()
    {
        $data = parent::all();

        $data['desired_gender'] = $data['gender'] == 'Male' ? 1 : ($data['gender'] == 'Female' ? 2 : 3);

        $this->merge($data); // This is required since without merging, it doesn't pass modified value to controller.

        return $data;
    }*/
}
