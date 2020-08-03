<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\TerminableMiddleware;

class WebserviceLogger
{
    private static $loggerType = null;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $loggerType='json')
    {
        static::$loggerType = $loggerType;

        return $next($request);
    }

    public function terminate($request, $response)
    {
        if ( env('LOG_WEBSERVICE', false) ) {

            $disllowLoggingUrls = [
                'list/schools'
            ];

            foreach ($disllowLoggingUrls as $tempUrl) {
                if (ends_with($request->fullUrl(), $tempUrl)) {
                    return;
                }
            }

            if ( static::$loggerType == 'json' && method_exists($response, 'getData') ) {
                $output   = (array) $response->getData();

                if ( is_array($output) )
                    $output = json_encode($output);
            } else if ( static::$loggerType == 'content' ) {
                $output = $response->getContent();
            } else {
                return;
            }

            $inputExcept = [
                'password',
            ];

            $input = [];
            foreach ($request->request->all() as $key => $value) {
                $input[$key] = $value;
                if (in_array($key, $inputExcept)) {
                    $input[$key] = '********';
                }
            }

            $filename = 'webservice_' . date('d-m-y') . '.log';

            $dataToLog  = '[' . \Carbon\Carbon::now()->toDateTimeString() . "] log.DEBUG: ";
            $dataToLog .= 'URL: '    . $request->fullUrl() . "\n";
            $dataToLog .= 'Method: ' . $request->method() . "\n";
            $dataToLog .= 'Input: '  . print_r((array) $input, true) . "\n";
            $dataToLog .= 'Output: ' . $output . "\n";

            // Finally write log
            \File::append( storage_path('logs' . DIRECTORY_SEPARATOR . $filename), $dataToLog . "\n" . str_repeat("=", 20) . "\n\n");
        }
    }
}
