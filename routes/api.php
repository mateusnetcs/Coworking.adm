<?php

use App\Http\Controllers\Api\BookingCalendarController;
use App\Http\Controllers\Api\Admin\AdminReservationController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\ReservationController;
use App\Support\PublicAppUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/status', function () {
    $clientId = config('services.google.client_id');
    $clientSecret = config('services.google.client_secret');
    $redirectUri = PublicAppUrl::googleCallback();

    return response()->json([
        'enabled' => is_string($clientId) && $clientId !== ''
            && is_string($clientSecret) && $clientSecret !== '',
        'redirect_uri' => $redirectUri,
        'google_console' => [
            'redirect_uri' => $redirectUri,
            'javascript_origin' => PublicAppUrl::base(),
        ],
    ]);
})->name('api.google.status');

Route::post('/register', [RegisterController::class, 'store'])->name('api.register');
Route::post('/login', [LoginController::class, 'store'])->name('api.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user()->load('roles'));
    })->name('api.user');

    Route::post('/logout', [LogoutController::class, 'store'])->name('api.logout');

    Route::get('/booking-calendar/day', [BookingCalendarController::class, 'day'])
        ->name('api.booking-calendar.day');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('api.reservations.index');

    Route::post('/reservations', [ReservationController::class, 'store'])
        ->middleware('can:create,App\Models\Reservation')
        ->name('api.reservations.store');

    Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])
        ->middleware('can:updateAsOwner,reservation')
        ->name('api.reservations.update');

    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])
        ->middleware('can:deleteAsOwner,reservation')
        ->name('api.reservations.destroy');

    Route::prefix('admin')->middleware('admin')->group(function (): void {
        Route::get('/reservations', [AdminReservationController::class, 'index'])
            ->name('api.admin.reservations.index');
        Route::post('/reservations', [AdminReservationController::class, 'store'])
            ->name('api.admin.reservations.store');
        Route::patch('/reservations/{reservation}/attendance', [AdminReservationController::class, 'updateAttendance'])
            ->name('api.admin.reservations.attendance');
        Route::delete('/reservations/{reservation}', [AdminReservationController::class, 'destroy'])
            ->name('api.admin.reservations.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('api.admin.users.index');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])
            ->name('api.admin.users.update');
    });
});
