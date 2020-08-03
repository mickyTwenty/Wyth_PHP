<?php
namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Helpers\RESTAPIHelper;

class Jsonify extends Request
{

	public function response(array $errors)
	{
        $parseErrors = array_map(function($v) {
            return $v[0];
        }, $errors);

        return RESTAPIHelper::response( $parseErrors, false, 'validation_error' );
	}

}
