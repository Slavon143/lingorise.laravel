<?php

use App\Http\Controllers\Admin\AiController as AdminAiController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\AuthorController as AdminAuthorController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Admin\LearningController as AdminLearningController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
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
use App\Http\Controllers\ContextExplanationController;
use App\Http\Controllers\GrammarExplanationController;
use App\Http\Controllers\ShadowingAttemptController;
use App\Http\Controllers\SimplificationController;
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
    Route::post('/library/metadata', [LibraryController::class, 'metadata'])->name('library.metadata');
    Route::post('/library', [LibraryController::class, 'store'])->name('library.store');
    Route::delete('/library/{book}', [LibraryController::class, 'destroy'])->name('library.destroy');
    Route::patch('/library/{book}/visibility', [LibraryController::class, 'toggleVisibility'])->name('library.visibility');
    Route::get('/library/public', [PublicLibraryController::class, 'index'])->name('library.public');
    Route::post('/library/public/{book}/add', [PublicLibraryController::class, 'addToMyLibrary'])->name('library.public.add');
    Route::get('/read/{book}', [ReaderController::class, 'show'])->name('reader.show');
    Route::post('/read/{book}/translate', WordTranslationController::class)->middleware('throttle:ai-translation')->name('reader.translate');
    Route::post('/read/{book}/context-explain', ContextExplanationController::class)->middleware('throttle:ai-translation')->name('reader.context-explain');
    Route::post('/read/{book}/grammar-explain', GrammarExplanationController::class)->middleware('throttle:ai-translation')->name('reader.grammar-explain');
    Route::post('/read/{book}/simplify', SimplificationController::class)->middleware('throttle:ai-translation')->name('reader.simplify');
    Route::post('/read/{book}/shadowing', [ShadowingAttemptController::class, 'store'])->name('reader.shadowing');
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
    Route::put('/settings/daily-goal', [DashboardController::class, 'updateDailyGoal'])->name('settings.daily-goal');
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
        Route::get('/books', [AdminBookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [AdminBookController::class, 'create'])->name('books.create');
        Route::post('/books', [AdminBookController::class, 'store'])->name('books.store');
        Route::get('/books/{book}', [AdminBookController::class, 'show'])->name('books.show');
        Route::get('/books/{book}/edit', [AdminBookController::class, 'edit'])->name('books.edit');
        Route::patch('/books/{book}', [AdminBookController::class, 'update'])->name('books.update');
        Route::post('/books/{book}/publish', [AdminBookController::class, 'publish'])->name('books.publish');
        Route::post('/books/{book}/unpublish', [AdminBookController::class, 'unpublish'])->name('books.unpublish');
        Route::post('/books/{book}/archive', [AdminBookController::class, 'archive'])->name('books.archive');
        Route::post('/books/{book}/restore', [AdminBookController::class, 'restore'])->name('books.restore');
        Route::delete('/books/{book}', [AdminBookController::class, 'destroy'])->name('books.destroy');
        Route::resource('authors', AdminAuthorController::class)->except('show')->parameters(['authors' => 'author']);
        Route::resource('categories', AdminCategoryController::class)->except('show')->parameters(['categories' => 'category']);
        Route::resource('languages', AdminLanguageController::class)->except('show')->parameters(['languages' => 'language']);
        Route::post('/languages/{language}/toggle-active', [AdminLanguageController::class, 'toggleActive'])->name('languages.toggle-active');
        Route::prefix('learning')->name('learning.')->group(function () {
            Route::get('/', [AdminLearningController::class, 'index'])->name('index');
            Route::get('/words', [AdminLearningController::class, 'words'])->name('words');
            Route::get('/shadowing', [AdminLearningController::class, 'shadowing'])->name('shadowing');
            Route::get('/cache', [AdminLearningController::class, 'cache'])->name('cache');
            Route::get('/events', [AdminLearningController::class, 'events'])->name('events');
        });

        Route::prefix('ai')->name('ai.')->group(function () {
            Route::get('/', [AdminAiController::class, 'index'])->name('overview');
            Route::get('/usage', [AdminAiController::class, 'usage'])->name('usage.index');
            Route::get('/usage/{event}', [AdminAiController::class, 'usageShow'])->name('usage.show');
            Route::get('/users', [AdminAiController::class, 'users'])->name('users');
            Route::get('/books', [AdminAiController::class, 'books'])->name('books');
            Route::get('/cache/translations', [AdminAiController::class, 'cacheTranslations'])->name('cache.translations.index');
            Route::get('/cache/translations/{translationCache}', [AdminAiController::class, 'cacheTranslationShow'])->name('cache.translations.show');
            Route::delete('/cache/translations/{translationCache}', [AdminAiController::class, 'cacheTranslationDestroy'])->name('cache.translations.destroy');
            Route::get('/cache/explanations', [AdminAiController::class, 'cacheExplanations'])->name('cache.explanations.index');
            Route::get('/cache/explanations/{explanationCache}', [AdminAiController::class, 'cacheExplanationShow'])->name('cache.explanations.show');
            Route::delete('/cache/explanations/{explanationCache}', [AdminAiController::class, 'cacheExplanationDestroy'])->name('cache.explanations.destroy');
            Route::get('/cache/tts', [AdminAiController::class, 'cacheTts'])->name('cache.tts.index');
            Route::get('/cache/tts/{ttsCache}', [AdminAiController::class, 'cacheTtsShow'])->name('cache.tts.show');
            Route::delete('/cache/tts/{ttsCache}', [AdminAiController::class, 'cacheTtsDestroy'])->name('cache.tts.destroy');
            Route::get('/errors', [AdminAiController::class, 'errors'])->name('errors');
            Route::get('/pricing', [AdminAiController::class, 'pricing'])->name('pricing');
        });
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/{plan}', [AdminPlanController::class, 'edit'])->name('plans.edit');
        Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::post('/plans/{plan}/reset-defaults', [AdminPlanController::class, 'resetDefaults'])->name('plans.reset-defaults');

        Route::post('/users/{user}/change-plan', [AdminUserController::class, 'changePlan'])->name('users.change-plan');
        Route::post('/users/{user}/cancel-subscription', [AdminUserController::class, 'cancelSubscription'])->name('users.cancel-subscription');
        Route::post('/users/{user}/store-override', [AdminUserController::class, 'storeOverride'])->name('users.store-override');
        Route::post('/users/{user}/remove-override/{override}', [AdminUserController::class, 'removeOverride'])->name('users.remove-override');

        Route::get('/settings', AdminSettingsController::class)->name('settings.index');
        Route::patch('/settings/daily-goal', [AdminSettingsController::class, 'updateDailyGoal'])->name('settings.daily-goal.update');
    });
