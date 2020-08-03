<?php

namespace App\Exceptions;

use App\Helpers\RESTAPIHelper;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ( $request->is('api/*') ) {

            logger($exception->getMessage() . ' File: ' . $exception->getFile() . ' Line: ' . $exception->getLine());

            // I'm unsure about this exception will be updated as work will progress
            if (
                $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            ) {
                return RESTAPIHelper::response( 'Object not found.', false, 'object_not_found' );
            } elseif ($exception instanceof \Illuminate\Validation\ValidationException) {

                $validationErrors = implode("\n", array_unique(array_flatten($exception->validator->messages()->getMessages())));
                return RESTAPIHelper::response($validationErrors, false, 'validation_error');

            } elseif (
                !($exception instanceof \Illuminate\Http\Exception\HttpResponseException) &&
                !($exception instanceof \Illuminate\Validation\ValidationException)
            ) {
                if ( env('APP_ENV') === 'production' ) {
                    return RESTAPIHelper::response( 'Something went wrong or you are not allowed to perform this action.', false, snake_case(class_basename(get_class($exception))) );
                } else {
                    return RESTAPIHelper::response( 'UNTRACKED: ' . $exception->getMessage(), false, snake_case(class_basename(get_class($exception))) );
                }
            }

        } elseif ( $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException ) {
            $directory = $this->getWorkingDirectory($request);

            if ( view()->exists($directory.'.errors.'.$exception->getStatusCode()) ) {
                return response()->view($directory.'.errors.'.$exception->getStatusCode(), [], $exception->getStatusCode());
            }
        } else if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $directory = $this->getWorkingDirectory($request);

            if ( view()->exists($directory.'.errors.404') ) {
                return response()->view($directory.'.errors.404', []);
            }
        } else if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return redirect($request->fullUrl())->withErrors('The form session has expired, please try again. In the future, reload the page if it has been open for several hours.');
        }

        return parent::render($request, $exception);
    }

    private function getWorkingDirectory($request)
    {
        if ( $request->is('backend/*') ) {
            $directory = 'backend';
        } else {
            $directory = 'frontend';
        }

        return $directory;
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
