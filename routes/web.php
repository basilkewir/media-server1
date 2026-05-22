<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamPlayerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\StreamAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOutputController;
use App\Http\Controllers\Admin\AdminRelayServerController;
use App\Http\Controllers\Admin\AdminIcecastController;
use App\Http\Controllers\Admin\AdminApiTokenController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\StreamsController;
use App\Http\Controllers\Client\LibraryController;
use App\Http\Controllers\Client\PremiumController;

Route::get('/', fn() => redirect()->route('login'));

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ── Admin ─────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Channels
    Route::get('channels',                    [ChannelController::class, 'index'])->name('channels.index');
    Route::get('channels/create',             [ChannelController::class, 'create'])->name('channels.create');
    Route::post('channels',                   [ChannelController::class, 'store'])->name('channels.store');
    Route::get('channels/{channel}',          [ChannelController::class, 'show'])->name('channels.show');
    Route::get('channels/{channel}/edit',     [ChannelController::class, 'edit'])->name('channels.edit');
    Route::put('channels/{channel}',          [ChannelController::class, 'update'])->name('channels.update');
    Route::delete('channels/{channel}',       [ChannelController::class, 'destroy'])->name('channels.destroy');

    // Stream control
    Route::post('channels/{channel}/start',    [StreamAdminController::class, 'start'])->name('channels.start');
    Route::post('channels/{channel}/stop',     [StreamAdminController::class, 'stop'])->name('channels.stop');
    Route::post('channels/{channel}/fallback', [StreamAdminController::class, 'fallback'])->name('channels.fallback');
    Route::post('channels/{channel}/recover',  [StreamAdminController::class, 'recover'])->name('channels.recover');
    Route::get('channels/{channel}/events',    [StreamAdminController::class, 'events'])->name('channels.events');

    // VOD Library
    Route::get('channels/{channel}/vod',                    [\App\Http\Controllers\Admin\VodController::class, 'index'])->name('vod.index');
    Route::post('channels/{channel}/vod',                   [\App\Http\Controllers\Admin\VodController::class, 'store'])->name('vod.store');
    Route::delete('channels/{channel}/vod/{vodFile}',       [\App\Http\Controllers\Admin\VodController::class, 'destroy'])->name('vod.destroy');
    Route::post('channels/{channel}/vod/reorder',           [\App\Http\Controllers\Admin\VodController::class, 'reorder'])->name('vod.reorder');

    // Output Targets
    Route::get('outputs',                                   [AdminOutputController::class, 'index'])->name('outputs.index');
    Route::get('outputs/create',                            [AdminOutputController::class, 'create'])->name('outputs.create');
    Route::post('outputs',                                  [AdminOutputController::class, 'store'])->name('outputs.store');
    Route::get('outputs/{output}/edit',                     [AdminOutputController::class, 'edit'])->name('outputs.edit');
    Route::put('outputs/{output}',                          [AdminOutputController::class, 'update'])->name('outputs.update');
    Route::delete('outputs/{output}',                       [AdminOutputController::class, 'destroy'])->name('outputs.destroy');
    Route::post('outputs/{output}/start',                   [AdminOutputController::class, 'start'])->name('outputs.start');
    Route::post('outputs/{output}/stop',                    [AdminOutputController::class, 'stop'])->name('outputs.stop');
    Route::post('outputs/{output}/restart',                 [AdminOutputController::class, 'restart'])->name('outputs.restart');

    // Relay Servers
    Route::get('relay-servers',                             [AdminRelayServerController::class, 'index'])->name('relay-servers.index');
    Route::get('relay-servers/create',                      [AdminRelayServerController::class, 'create'])->name('relay-servers.create');
    Route::post('relay-servers',                            [AdminRelayServerController::class, 'store'])->name('relay-servers.store');
    Route::get('relay-servers/{relayServer}/edit',          [AdminRelayServerController::class, 'edit'])->name('relay-servers.edit');
    Route::put('relay-servers/{relayServer}',               [AdminRelayServerController::class, 'update'])->name('relay-servers.update');
    Route::delete('relay-servers/{relayServer}',            [AdminRelayServerController::class, 'destroy'])->name('relay-servers.destroy');
    Route::post('relay-servers/start-relay',                [AdminRelayServerController::class, 'startRelay'])->name('relay-servers.start-relay');

    // Icecast
    Route::get('icecast',                                   [AdminIcecastController::class, 'index'])->name('icecast.index');
    Route::post('icecast/{channel}/enable',                 [AdminIcecastController::class, 'enable'])->name('icecast.enable');
    Route::post('icecast/{channel}/disable',                [AdminIcecastController::class, 'disable'])->name('icecast.disable');

    // API Tokens
    Route::get('api-tokens',                                [AdminApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('api-tokens',                               [AdminApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('api-tokens/{token}',                     [AdminApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    // Settings (media server driver)
    Route::get('settings',                                  [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings',                                 [AdminSettingsController::class, 'update'])->name('settings.update');

    // Access Codes
    Route::get('access-codes/create',                       [AccessCodeController::class, 'create'])->name('access-codes.create');
    Route::post('access-codes',                             [AccessCodeController::class, 'store'])->name('access-codes.store');
    Route::get('access-codes',                              [AccessCodeController::class, 'index'])->name('access-codes.index');
    Route::delete('access-codes/{accessCode}',              [AccessCodeController::class, 'destroy'])->name('access-codes.destroy');

    // Users
    Route::middleware('admin')->group(function () {
        Route::get('users',                                 [UserController::class, 'index'])->name('users.index');
        Route::get('users/create',                          [UserController::class, 'create'])->name('users.create');
        Route::post('users',                                [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit',                     [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}',                          [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}',                       [UserController::class, 'destroy'])->name('users.destroy');
    });
});

// ── Client ────────────────────────────────────────────────────────────────────
Route::middleware(['access_code', \App\Http\Middleware\ResolveClientSubscription::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/streams',   [StreamsController::class, 'index'])->name('client.streams');
    Route::get('/library',   [LibraryController::class, 'index'])->name('client.library');
    Route::get('/premium',   [PremiumController::class, 'index'])->name('client.premium');
});

// ── Stream Player & HLS/DASH ──────────────────────────────────────────────────
Route::get('/play/{slug}/{format?}', [StreamPlayerController::class, 'play'])
    ->name('stream.play')->where('format', 'hls|dash');

Route::get('/streams/{slug}/playlist.m3u8',  [StreamPlayerController::class, 'hlsManifest'])->name('stream.hls');
Route::get('/streams/{slug}/master.m3u8',    [StreamPlayerController::class, 'hlsMaster'])->name('stream.hls.master');
Route::get('/streams/{slug}/{segment}.ts',   [StreamPlayerController::class, 'hlsSegment'])->name('stream.segment')->where('segment', 'seg\d+');
Route::get('/streams/{slug}/manifest.mpd',   [StreamPlayerController::class, 'dashManifest'])->name('stream.dash');
