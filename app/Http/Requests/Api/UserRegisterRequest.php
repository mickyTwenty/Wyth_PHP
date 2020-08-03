<?php
namespace App\Http\Requests\Api;

use App\Http\Requests\Jsonify as Request;

class UserRegisterRequest extends Request {

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
            'phone.phone'    => 'Please enter your valid phone number.',
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
            'email'               => 'required|edu_email|unique:users,email',
            'password'            => 'required|string',
            // 'full_name'           => 'required|string',
            'first_name'          => 'required|string',
            'last_name'           => 'required|string',
            'user_type'           => 'required|in:normal,driver',
            'school_name'         => 'required',
            'gender'              => 'required',
            'phone'               => 'required|phone:US|unique_phone:US,users,phone',
        ];

        return $rules;
    }

    public function all()
    {
        $data = parent::all();

        if ( array_key_exists('phone', $data) ) {
            $data['phone'] = sprintf('+1%s', ltrim($data['phone'], '+1'));
        }

        if ( array_key_exists('gender', $data) ) {
            $data['gender'] = ucfirst(strtolower($data['gender']));
        }

        $this->merge($data); // This is required since without merging, it doesn't pass modified value to controller.

        return $data;
    }
}
