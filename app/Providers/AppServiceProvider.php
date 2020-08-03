<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Debugging
        if (\App::environment('local') || request()->get('_debug') == 'true') {
            // \Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
            //     \Log::debug($query->sql . ' - ' . serialize($query->bindings));
            // });
        }

        if ( env('APP_ENV') === 'production' ) {
            \URL::forceScheme('https');
        }

        Collection::macro('pluckMultiple', function ($assoc, $mapKeys=array()) {
            return $this->map(function ($item) use ($assoc, $mapKeys) {
                $list = [];
                foreach ($assoc as $key) {
                    $remapKey = array_key_exists($key, $mapKeys) ? $mapKeys[$key] : $key;
                    $list[ $remapKey ] = data_get($item, $key);
                }
                return $list;
            }, new static);
        });

        Collection::macro('hasKeyWithValue', function($needleKey, $needleValue) {
            return !$this->every(function ($item, $itemKey) use($needleKey, $needleValue) {
                return array_key_exists($needleKey, $item) && $item->{$needleKey} != $needleValue;
            });
        });

        /**
         * Unfortunately, laravel doesn't provide parsed body in multipart/form-data via PUT http verb
         * here is the extended functionality
         */
        Request::macro('parseBody', function($merge=true) {
            $raw_data = file_get_contents('php://input');

            if ( empty($raw_data) )
                return;

            $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

            // Fetch each part
            $parts = array_slice(explode($boundary, $raw_data), 1);
            $data = array();
            $currentIndex = 0;

            foreach ($parts as $part) {
                // If this is the last part, break
                if ($part == "--\r\n") break;

                // Separate content from headers
                $part = ltrim($part, "\r\n");
                list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

                // Parse the headers list
                $raw_headers = explode("\r\n", $raw_headers);
                $headers = array();
                foreach ($raw_headers as $header) {
                    list($name, $value) = explode(':', $header);
                    $headers[strtolower($name)] = ltrim($value, ' ');
                }

                // Parse the Content-Disposition to get the field name, etc.
                if (isset($headers['content-disposition'])) {
                    $filename = null;
                    preg_match(
                        '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                        $headers['content-disposition'],
                        $matches
                    );
                    // dd($matches);
                    list(, $type, $name) = $matches;
                    isset($matches[4]) and $filename = $matches[4];

                    //Parse File
                    if( isset($matches[4]) )
                    {
                        //get filename
                        $filename = $matches[4];

                        //get tmp name
                        $filename_parts = pathinfo( $filename );
                        $tmp_name = tempnam( ini_get('upload_tmp_dir'), $filename_parts['filename']);

                        $fileArray = array(
                            'error'    => 0,
                            'name'     => $filename,
                            'tmp_name' => $tmp_name,
                            'size'     => strlen( $body ),
                            'type'     => $value
                        );

                        // If file requested in array
                        if ( false !== preg_match('%\[(.*?)\]%', $matches[2], $index) ) {
                            if ( isset($index[1]) && $index[1] !== '' ) {
                                $setIndex = intval($index[1]);
                            } else {
                                $setIndex = $currentIndex++;
                            }
                            $fileIndexName = str_replace($index[0], '', $matches[ 2 ]);
                            $_FILES[ $fileIndexName ][ $setIndex ] = $fileArray;
                        } else {

                            //if labeled the same as previous, skip
                            if( isset( $_FILES[ $matches[ 2 ] ] ) )
                            {
                                continue;
                            }

                            $_FILES[ $matches[ 2 ] ] = $fileArray;
                        }

                        // place in temporary directory
                        file_put_contents($tmp_name, $body);
                    } else {
                        $data[$name] = substr($body, 0, strlen($body) - 2);
                    }
                }

            }

            if ( $merge ) {
                $this->merge($data);
                $this->files->add($_FILES);
            }

            return $data;
        });
    }
}
