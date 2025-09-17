<?php

namespace App\Providers;

<<<<<<< HEAD
use App\Events\AccountStatusChanged;
use App\Listeners\SendAccountStatusEmailChanged;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
=======
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
>>>>>>> 80e3dc5 (First commit)

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
<<<<<<< HEAD
     * @var array<string, array<int, string>>
=======
     * @var array
>>>>>>> 80e3dc5 (First commit)
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
<<<<<<< HEAD
        AccountStatusChanged::class => [
            SendAccountStatusEmailChanged::class,
        ],
=======
>>>>>>> 80e3dc5 (First commit)
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
