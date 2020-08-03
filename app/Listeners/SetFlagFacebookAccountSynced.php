<?php

namespace App\Listeners;

use App\Events\UserFacebookAccountSynced;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SetFlagFacebookAccountSynced
{
    /**
     * Handle the event.
     *
     * @param  UserFacebookAccountSynced  $event
     * @return void
     */
    public function handle(UserFacebookAccountSynced $event)
    {
        $user          = $event->user;
        $user_facebook = $event->user_facebook;

        $user->setMeta('has_facebook_integrated', $user_facebook->facebook_uid, 'application');
        $user->save();
    }
}
