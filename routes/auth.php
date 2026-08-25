<?php

use App\Http\Controllers\Auth\RoleLoginController;
use Illuminate\Support\Facades\Route;

// Role-based login (preserves the original Flask login flow).
//
// The routes are wrapped in the `web` middleware group so that the session is
// started and the error bag (`$errors`) is shared with the views — the
// `auth.login` view relies on `@error(...)`, which requires
// `ShareErrorsFromSession`. Without the `web` group the `$errors` variable is
// undefined and the login page throws a 500.
//
// The `guest` middleware is intentionally NOT used: instead of Laravel's
// default `HOME` redirect we keep the original Flask behaviour — a logged-in
// user is sent to the Update Stok page (barista) or the Manager dashboard.
// This is handled by guarding the login GET with our own session check.
Route::middleware('web')->group(function () {
    Route::get('login', function () {
        if (session()->has('username')) {
            if (session('role') === 'manajemen') {
                return redirect()->route('manager.dashboard');
            }

            return redirect()->route('barista.update-stok');
        }

        return app(RoleLoginController::class)->create();
    })->name('login');

    Route::post('login', [RoleLoginController::class, 'store']);

    Route::post('logout', [RoleLoginController::class, 'destroy'])
        ->middleware('session.auth')
        ->name('logout');
});
