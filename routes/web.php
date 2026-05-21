<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamPlayerController;
use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\StreamsController;
use App\Http\Controllers\Client\LibraryController;
use App\Http\Controllers\Client\PremiumController;

Route::get('/', fn() => response()->json([
    'service' => 'MediaServer',
    'version' => config('app.version', '1.1.0'),
    'status' => 'running',
]));

// ── Admin ───────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('access-codes/create', [AccessCodeController::class, 'create'])->name('access-codes.create');
    Route::post('access-codes', [AccessCodeController::class, 'store'])->name('access-codes.store');
    Route::get('access-codes', [AccessCodeController::class, 'index'])->name('access-codes.index');
    Route::delete('access-codes/{accessCode}', [AccessCodeController::class, 'destroy'])->name('access-codes.destroy');
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
