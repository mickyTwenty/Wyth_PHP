<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncMemberWithFirestore
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
     * @param  $event
     * @return void
     */
    public function handle($event)
    {
        $ride                 = $event->ride;

        $driver               = $ride->trip->user_id;
        $passengers           = $ride->members->pluck('user_id');

        $firestore = [
            'members' => array_unique(array_merge([$driver], $passengers->toArray())),
        ];

        FireStoreHandler::updateDocument('groups', $ride->id, $firestore);
    }
}
