<?php

namespace App\Listeners;

use App\Classes\FirebaseHandler;
use App\Events\UnblockEvent;

class UnblockSyncWithFirebase
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UnblockEvent  $event
     * @return void
     */
    public function handle(UnblockEvent $event)
    {
        $unblocker  = $event->unblocker;
        $unblockee = $event->unblockee;

        $isBlockedBack = FirebaseHandler::get('/blocks/'.$unblockee->prefix_uid.'/'.$unblocker->prefix_uid);

        if ( $isBlockedBack == '2' ) { // 1 way blocking, only first user blocked second user
            FirebaseHandler::delete('/blocks/' . $unblocker->prefix_uid . '/' . $unblockee->prefix_uid);
            FirebaseHandler::delete('/blocks/' . $unblockee->prefix_uid . '/' . $unblocker->prefix_uid);
        } elseif ( $isBlockedBack == '1' ) { // Second user also blocked first user
            FirebaseHandler::update('/blocks/'.$unblocker->prefix_uid, [
                $unblockee->prefix_uid => '2',
            ]);
        }
    }
}
