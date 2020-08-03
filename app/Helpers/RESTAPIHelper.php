<?php
namespace App\Helpers;

use stdClass;

class RESTAPIHelper {

    private static $pagination;

	public static function response($output, $status=true, $message='', $format='json') {

		$response = [
			'status' => $status ? true : false,
            'message' => $status ? $message : (is_array($output) ? implode("\n", $output) : $output),
            'paging' => self::$pagination ?: new stdClass(),
		];

		if ( !$status ) {

			$response['error_code'] = $message;
			// $response['message']    = is_array($output) ? implode("\n", $output) : $output;

		} else {

            $response['body']       = $output;

		}

	    return response()->json( $response, 200, [], JSON_PRESERVE_ZERO_FRACTION );
	}

    public static function setPagination(\Illuminate\Pagination\LengthAwarePaginator $paginator)
    {
        self::$pagination                = new stdClass();
        self::$pagination->total_records = $paginator->total();
        self::$pagination->current_page  = $paginator->currentPage();
        self::$pagination->total_pages   = $paginator->lastPage();
        self::$pagination->limit         = $paginator->perPage();

        return new static;
    }

	public static function messageResponse($message, $status=true, $dev_message='', $format='json') {

		$response = [
			'status' => $status ? true : false
		];

		if ( !$status ) {
			$response['error_code'] = $dev_message;
		}

		$response['message'] = $message;

	    return response()->json( $response );
	}

	public static function emptyResponse($status=true, $dev_message='', $format='json') {

		$response = [
			'status' => $status ? true : false
		];

		if ( !$status ) {
			$response['error_code'] = $dev_message;
		}

	    return response()->json( $response );
	}

}
