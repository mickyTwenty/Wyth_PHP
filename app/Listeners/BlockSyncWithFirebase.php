<?php

namespace App\Listeners;

use App\Classes\FirebaseHandler;
use App\Events\BlockEvent;

class BlockSyncWithFirebase
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
     * @param  BlockEvent  $event
     * @return void
     */
    public function handle(BlockEvent $event)
    {
        $blocker  = $event->blocker;
        $blockee = $event->blockee;

        // Unconditionally set blockage value to 1
        FirebaseHandler::update('/blocks/'.$blocker->prefix_uid, [
            $blockee->prefix_uid => '1',
        ]);

        $isBlockedBack = FirebaseHandler::get('/blocks/'.$blockee->prefix_uid.'/'.$blocker->prefix_uid);

        if ( $isBlockedBack != '1' ) {
            FirebaseHandler::update('/blocks/'.$blockee->prefix_uid, [
                $blocker->prefix_uid => '2',
            ]);
        }
    }
}
