<?php
namespace App\Classes;

use Mail;

class Email
{
    private static $suffixInSubject = true;

    public static function shoot($to, $subject, $body, $attachments=null)
    {
        Mail::raw( $body, function($m) use($to, $subject, $attachments) {
            $m->to( $to )->from( env('MAIL_FROM_ADDRESS') )->subject( self::makeSubject($subject) );
        });
    }

    public static function makeSubject($subject)
    {
        return $subject . (self::$suffixInSubject ? (' - ' . constants('global.site.name')) : '');
    }
}
