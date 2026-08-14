<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Shell Route
|--------------------------------------------------------------------------
|
| The frontend is a single client-side SPA served by one Blade shell.
| Real static assets (/css, /js, /storage, ...) are served by the web
| server before routing, and /api/* is mounted under routes/api.php, so
| this catch-all only handles application routes.
|
*/

Route::get('/{path?}', function () {
    return view('app');
})->where('path', '^(?!api/|storage/|build/).*$')->name('spa');
