<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamPlayerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\StreamAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\StreamsController;
use App\Http\Controllers\Client\LibraryController;
use App\Http\Controllers\Client\PremiumController;

Route::get('/', fn() => response()->json([
    'service' => 'MediaServer',
    'version' => config('app.version', '1.1.0'),
    'status' => 'running',
]));

// ── Auth ────────────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ── Admin ───────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    // Access Codes
    Route::get('access-codes/create', [AccessCodeController::class, 'create'])->name('access-codes.create');
    Route::post('access-codes', [AccessCodeController::class, 'store'])->name('access-codes.store');
    Route::get('access-codes', [AccessCodeController::class, 'index'])->name('access-codes.index');
    Route::delete('access-codes/{accessCode}', [AccessCodeController::class, 'destroy'])->name('access-codes.destroy');

    // Channels
    Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::get('channels/create', [ChannelController::class, 'create'])->name('channels.create');
    Route::post('channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    Route::get('channels/{channel}/edit', [ChannelController::class, 'edit'])->name('channels.edit');
    Route::put('channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');
    Route::delete('channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');

    // Stream Control
    Route::post('channels/{channel}/start', [StreamAdminController::class, 'start'])->name('channels.start');
    Route::post('channels/{channel}/stop', [StreamAdminController::class, 'stop'])->name('channels.stop');
    Route::post('channels/{channel}/fallback', [StreamAdminController::class, 'fallback'])->name('channels.fallback');
    Route::post('channels/{channel}/recover', [StreamAdminController::class, 'recover'])->name('channels.recover');
    Route::get('channels/{channel}/events', [StreamAdminController::class, 'events'])->name('channels.events');

    // User Management (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

// ── Client (with subscription sidebar) ──────────────────────────────────────
Route::middleware(['access_code', \App\Http\Middleware\ResolveClientSubscription::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/streams', [StreamsController::class, 'index'])->name('client.streams');
    Route::get('/library', [LibraryController::class, 'index'])->name('client.library');
    Route::get('/premium', [PremiumController::class, 'index'])->name('client.premium');
});

// ── Stream Player ───────────────────────────────────────────────────────────
Route::get('/play/{slug}/{format?}', [StreamPlayerController::class, 'play'])
    ->name('stream.play')
    ->where('format', 'hls|dash');

// ── HLS / DASH Manifests & Segments ─────────────────────────────────────────
Route::get('/streams/{slug}/playlist.m3u8', [StreamPlayerController::class, 'hlsManifest'])
    ->name('stream.hls');

Route::get('/streams/{slug}/{segment}.ts', [StreamPlayerController::class, 'hlsSegment'])
    ->name('stream.segment')
    ->where('segment', 'seg\d+');

Route::get('/streams/{slug}/manifest.mpd', [StreamPlayerController::class, 'dashManifest'])
    ->name('stream.dash');
