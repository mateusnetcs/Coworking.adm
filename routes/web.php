<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ReservationConfirmationController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
// Alias para quem configurou /google/callback no Google Console por engano
Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/comprovante/{code}', [ReservationConfirmationController::class, 'verify'])
    ->name('reservation.verify');

Route::view('/', 'app');
Route::view('/{any}', 'app')->where('any', '.*');
