<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryController;
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
    Route::get('/read/{book}', [ReaderController::class, 'show'])->name('reader.show');
    Route::post('/read/{book}/translate', WordTranslationController::class)->name('reader.translate');
    Route::post('/read/{book}/vocabulary', [VocabularyController::class, 'store'])->name('vocabulary.store');
    Route::get('/vocabulary', [VocabularyController::class, 'index'])->name('vocabulary.index');
    Route::delete('/vocabulary/{entry}', [VocabularyController::class, 'destroy'])->name('vocabulary.destroy');
    Route::get('/speaking', [SpeakingController::class, 'index'])->name('speaking.index');
    Route::post('/speech', SpeechController::class)->name('speech.create');
    Route::put('/settings/languages', [DashboardController::class, 'updateLanguages'])->name('settings.languages');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
