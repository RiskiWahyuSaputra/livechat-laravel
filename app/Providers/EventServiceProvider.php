<?php

namespace App\Providers;

use App\Events\UserShouldBeLoggedOut;
use App\Listeners\LogoutUser;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserShouldBeLoggedOut::class => [
            LogoutUser::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
