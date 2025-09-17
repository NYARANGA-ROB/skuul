<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
<<<<<<< HEAD
<<<<<<< HEAD

// M-Pesa Payment Routes
Route::prefix('mpesa')->group(function () {
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Initiate STK push payment
        Route::post('/stk-push', [\App\Http\Controllers\Api\MpesaController::class, 'stkPush']);
        
        // Check payment status
        Route::get('/payment-status', [\App\Http\Controllers\Api\MpesaController::class, 'checkStatus']);
        
        // Get payment receipt
        Route::get('/receipt/{id}', [\App\Http\Controllers\Api\MpesaController::class, 'getReceipt'])
            ->name('api.payments.receipt');
    });
    
    // Public callback URLs (no auth required)
    Route::post('/callback/{type?}', [\App\Http\Controllers\Api\MpesaController::class, 'handleCallback'])
        ->where('type', 'stk|b2c|c2b');
});
=======
>>>>>>> e327a8efde9094f66181c9b195fbe5df035baa20
=======
>>>>>>> 80e3dc5 (First commit)
