<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
<<<<<<< HEAD
     * @param \Illuminate\Http\Request $request
     *
=======
     * @param  \Illuminate\Http\Request  $request
>>>>>>> 80e3dc5 (First commit)
     * @return string|null
     */
    protected function redirectTo($request)
    {
<<<<<<< HEAD
        if (!$request->expectsJson()) {
=======
        if (! $request->expectsJson()) {
>>>>>>> 80e3dc5 (First commit)
            return route('login');
        }
    }
}
