<?php
namespace App\Http\Requests\Api;

use App\Http\Requests\Jsonify as Request;

class CreateUploadRequest extends Request {

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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'old_dd' => 'mimes:pdf,doc,ppt,xls,docx,pptx,xlsx|max:10000'
        ];
    }

}
