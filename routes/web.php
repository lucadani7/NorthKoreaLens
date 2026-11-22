<?php

use App\Http\Controllers\FeedController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', FeedController::class)->name('home');
Route::post('locale', LocaleController::class)->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
