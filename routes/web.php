<?php

use App\Http\Controllers\FeedController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SourcesController;
use Illuminate\Support\Facades\Route;

Route::get('/', FeedController::class)->name('home');
Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::post('locale', LocaleController::class)->name('locale.update');

// Pages written in Markdown: resources/content/pages/{page}/{locale}.md
Route::get('about', PageController::class)->defaults('page', 'about')->name('about');
Route::get('editorial-policy', PageController::class)->defaults('page', 'editorial-policy')->name('editorial-policy');
Route::get('team', PageController::class)->defaults('page', 'team')->name('team');
Route::get('how-to-help', PageController::class)->defaults('page', 'how-to-help')->name('how-to-help');
Route::get('sources', SourcesController::class)->name('sources');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
