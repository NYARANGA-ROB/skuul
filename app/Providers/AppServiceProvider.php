<?php

namespace App\Providers;

<<<<<<< HEAD
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
=======
>>>>>>> 80e3dc5 (First commit)
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
<<<<<<< HEAD
        Schema::defaultStringLength(100);
=======
        //
>>>>>>> 80e3dc5 (First commit)
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
<<<<<<< HEAD
        // Implicitly grant "Super-Admin" role all permission checks using can()
        Gate::after(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });
=======
        //
>>>>>>> 80e3dc5 (First commit)
    }
}
