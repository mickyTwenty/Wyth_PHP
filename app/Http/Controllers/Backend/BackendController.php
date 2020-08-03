<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

class BackendController extends Controller
{
    protected function safelyRemoveFile($file)
    {
        $forbiddenToRemove = [
            'default.jpg',
        ];

        foreach ($forbiddenToRemove as $rejectedFile) {
            if ( ends_with($file, $rejectedFile) )
                return;
        }

        @unlink( $file );
    }

}
