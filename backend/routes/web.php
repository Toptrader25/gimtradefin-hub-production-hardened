<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| This application is API-only — the real frontend is the separate
| Next.js app. This file exists because Laravel's routing bootstrap
| requires it to be present, not because we serve any web/session-based
| pages from here.
*/

Route::get('/', function () {
    return response()->json([
        'name'    => 'GiMtradefin Hub API',
        'status'  => 'ok',
        'api_docs'=> '/api/v1/health',
    ]);
});
