<?php

use Illuminate\Support\Facades\Route;

// SPA用のルート - すべてのルートをVueアプリに委譲
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
