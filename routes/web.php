<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The Vue SPA owns every non-API path; it talks to the backend only through /api.
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api|docs|sanctum|broadcasting|up|storage).*$')
    ->name('spa');
