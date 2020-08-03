<?php

namespace App\Exceptions;

use Exception;

class BaseException extends Exception {

    /**
     * Define default error code if not assigned or explicitly defined.
     */
    const DEFAULT_RESOLVED_ERROR_CODE = 'undefined_error_code';

    /**
     * `error_code` container for rest-api responses.
     *
     * @var string
     */
    private $error_code;

    /**
     * Extended for API but still can work as native
     *
     * @param string            $message
     * @param long|string       $error_code
     * @param long              $code
     * @param Exception|null    $previous
     */
    public function __construct($message, $error_code = 0, $code = 0, Exception $previous = null)
    {
        if ( is_long($error_code) ) {
            $code = $error_code;
        } else {
            $this->error_code = $error_code;
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Functionality to resolve error code for rest-api responses.
     *
     * @return string
     */
    public function getResolvedErrorCode()
    {
        return $this->error_code ?: self::DEFAULT_RESOLVED_ERROR_CODE;
    }
}
