<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\SyncLocaleFromSession;
use Illuminate\Support\Facades\Route;

Route::get('/', static fn () => redirect('/admin', 302));

Route::prefix('{locale}')
    ->middleware(SyncLocaleFromSession::class)
    ->where(['locale' => '[a-z]{2}(?:-[A-Z]{2})?'])
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');
    });
