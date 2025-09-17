<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
<<<<<<< HEAD
=======
use Illuminate\Support\Facades\Gate;
>>>>>>> 80e3dc5 (First commit)

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
<<<<<<< HEAD
     * @var array<class-string, class-string>
=======
     * @var array
>>>>>>> 80e3dc5 (First commit)
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
