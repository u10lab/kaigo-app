<?php

use Illuminate\Support\Facades\Route;

// ログインルート
Route::get('/login', function () {
    return view('app');
})->name('login');

// SPA用のルート - すべてのルートをVueアプリに委譲
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
