<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Si el usuario está autenticado, redirigir al dashboard
    if (auth()->check()) {
        return redirect()->route('core.dashboard');
    }

    // Si no está autenticado, mostrar login
    return redirect()->route('core.auth.login');
});
