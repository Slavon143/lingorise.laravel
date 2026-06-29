<?php

use App\Http\Controllers\Admin\AiController as AdminAiController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\PublicLibraryController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\SpeakingController;
use App\Http\Controllers\SpeechController;
use App\Http\Controllers\VocabularyController;
use App\Http\Controllers\WordTranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/create', [LibraryController::class, 'create'])->name('library.create');
    Route::post('/library', [LibraryController::class, 'store'])->name('library.store');
    Route::delete('/library/{book}', [LibraryController::class, 'destroy'])->name('library.destroy');
    Route::patch('/library/{book}/visibility', [LibraryController::class, 'toggleVisibility'])->name('library.visibility');
    Route::get('/library/public', [PublicLibraryController::class, 'index'])->name('library.public');
    Route::post('/library/public/{book}/add', [PublicLibraryController::class, 'addToMyLibrary'])->name('library.public.add');
    Route::get('/read/{book}', [ReaderController::class, 'show'])->name('reader.show');
    Route::post('/read/{book}/translate', WordTranslationController::class)->middleware('throttle:ai-translation')->name('reader.translate');
    Route::post('/read/{book}/vocabulary', [VocabularyController::class, 'store'])->name('vocabulary.store');
    Route::get('/vocabulary', [VocabularyController::class, 'index'])->name('vocabulary.index');
    Route::delete('/vocabulary/{entry}', [VocabularyController::class, 'destroy'])->name('vocabulary.destroy');
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing/subscribe', [PricingController::class, 'subscribe'])->name('pricing.subscribe');
    Route::post('/pricing/cancel', [PricingController::class, 'cancel'])->name('pricing.cancel');
    Route::get('/speaking', [SpeakingController::class, 'index'])->name('speaking.index');
    Route::post('/speech', SpeechController::class)->middleware('throttle:ai-speech')->name('speech.create');
    Route::put('/settings/languages', [DashboardController::class, 'updateLanguages'])->name('settings.languages');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard.alias');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/promote', [AdminUserController::class, 'promote'])->name('users.promote');
        Route::post('/users/{user}/demote', [AdminUserController::class, 'demote'])->name('users.demote');

        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/books', AdminBookController::class)->name('books.index');
        Route::get('/ai', AdminAiController::class)->name('ai.index');
        Route::get('/settings', AdminSettingsController::class)->name('settings.index');
    });
