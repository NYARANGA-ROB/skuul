<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
<<<<<<< HEAD
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ApplicationException::class,
=======
     * @var string[]
     */
    protected $dontReport = [
        //
>>>>>>> 80e3dc5 (First commit)
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
<<<<<<< HEAD
     * @var array<int, string>
=======
     * @var string[]
>>>>>>> 80e3dc5 (First commit)
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
<<<<<<< HEAD
    public function register(): void
=======
    public function register()
>>>>>>> 80e3dc5 (First commit)
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
