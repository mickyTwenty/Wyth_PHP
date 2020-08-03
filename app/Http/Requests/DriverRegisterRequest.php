<?php
namespace App\Http\Requests;

class DriverRegisterRequest extends Request
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
            'email.unique'                => 'Email already found in our system, please try another one.',
            'phone.phone'                 => 'Please enter your valid phone number.',
            'graduation_year.date_format' => 'Please enter a valid year.',
            'vehicle_year.date_format'    => 'Please enter a valid year.',
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

        if ($id) {
            $rules = [
                'email'                 => 'required|edu_email|unique:users,email,' . $id . ',id,deleted_at,NULL',
                'first_name'            => 'required|string',
                'last_name'             => 'required|string',
                'school_name'           => 'required',
                'gender'                => 'required',
                'phone'                 => 'required|phone:US|unique_phone:US,users,phone,' . $id . ',id,deleted_at,NULL',
                'address'               => 'required',
                'postal_code'           => 'required',
                'state'                 => 'required|exists:states,id',
                'city'                  => 'required|exists:cities,id',
                'graduation_year'       => 'nullable|date_format:Y',
                'vehicle_year'          => 'nullable|date_format:Y',
            ];
        } else {
            $rules = [
                'email'                 => 'required|edu_email|unique:users,email,NULL,id,deleted_at,NULL',
                'password'              => 'required|string',
                'first_name'            => 'required|string',
                'last_name'             => 'required|string',
                'school_name'           => 'required',
                'gender'                => 'required',
                'phone'                 => 'required|phone:US|unique_phone:US,users,phone,NULL,id,deleted_at,NULL',
                'address'               => 'required',
                'postal_code'           => 'required',
                'state'                 => 'required|exists:states,id',
                'city'                  => 'required|exists:cities,id',
                'graduation_year'       => 'nullable|date_format:Y',
                'vehicle_year'          => 'nullable|date_format:Y',
            ];
        }

        return $rules;
    }

    public function all()
    {
        $data = parent::all();

        if (array_key_exists('phone', $data) && $data['phone'] != '') {
            $data['phone'] = sprintf('+1%s', ltrim($data['phone'], '+1'));
        }

        if (array_key_exists('gender', $data)) {
            $data['gender'] = ucfirst(strtolower($data['gender']));
        }

        $this->merge($data); // This is required since without merging, it doesn't pass modified value to controller.

        return $data;
    }
}
