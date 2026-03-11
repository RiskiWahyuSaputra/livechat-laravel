<?php

namespace App\Listeners;

use App\Events\UserShouldBeLoggedOut;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Facades\DB;

class LogoutUser
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserShouldBeLoggedOut $event): void
    {
        if ($event->user) {
            DB::table('sessions')->where('user_id', $event->user->id)->delete();
        }
    }
}
