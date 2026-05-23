<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamPlayerController;
use App\Http\Controllers\VodManagerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\ChannelGraphicsController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\StreamAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOutputController;
use App\Http\Controllers\Admin\AdminRelayServerController;
use App\Http\Controllers\Admin\AdminIcecastController;
use App\Http\Controllers\Admin\AdminApiTokenController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\SrtStreamController;
use App\Http\Controllers\Admin\VodScheduleController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\StreamsController;
use App\Http\Controllers\Client\LibraryController;
use App\Http\Controllers\Client\PremiumController;

Route::get('/', fn() => redirect()->route('login'));

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login',    [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login',   [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout',  [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ── Registration & Public ─────────────────────────────────────────────────────
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register',[RegisterController::class, 'register'])->middleware('guest');
Route::get('/pricing',  [RegisterController::class, 'showPricing'])->name('pricing');

// ── Authenticated user profile ────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',          [RegisterController::class, 'profile'])->name('profile');
    Route::put('/profile',          [RegisterController::class, 'updateProfile'])->name('profile.update');
});

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
    Route::post('channels/{channel}/vod/youtube',           [\App\Http\Controllers\Admin\VodController::class, 'storeYoutube'])->name('vod.store-youtube');
    Route::delete('channels/{channel}/vod/{vodFile}',       [\App\Http\Controllers\Admin\VodController::class, 'destroy'])->name('vod.destroy');
    Route::post('channels/{channel}/vod/reorder',           [\App\Http\Controllers\Admin\VodController::class, 'reorder'])->name('vod.reorder');

    // VOD Scheduling
    Route::get('channels/{channel}/vod-schedules',                     [VodScheduleController::class, 'index'])->name('vod-schedules.index');
    Route::post('channels/{channel}/vod-schedules',                    [VodScheduleController::class, 'store'])->name('vod-schedules.store');
    Route::put('channels/{channel}/vod-schedules/{vodSchedule}',       [VodScheduleController::class, 'update'])->name('vod-schedules.update');
    Route::delete('channels/{channel}/vod-schedules/{vodSchedule}',    [VodScheduleController::class, 'destroy'])->name('vod-schedules.destroy');
    Route::post('channels/{channel}/vod-schedules/{vodSchedule}/toggle', [VodScheduleController::class, 'toggle'])->name('vod-schedules.toggle');

    // Channel Graphics (Logo, Watermark, Ticker)
    Route::get('channels/{channel}/graphics',                          [ChannelGraphicsController::class, 'edit'])->name('channels.graphics');
    Route::put('channels/{channel}/graphics/logo',                     [ChannelGraphicsController::class, 'updateLogo'])->name('channels.graphics.logo');
    Route::put('channels/{channel}/graphics/watermark',                [ChannelGraphicsController::class, 'updateWatermark'])->name('channels.graphics.watermark');
    Route::put('channels/{channel}/graphics/ticker',                   [ChannelGraphicsController::class, 'updateTicker'])->name('channels.graphics.ticker');

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
    Route::post('icecast/create-stream',                    [AdminIcecastController::class, 'createStream'])->name('icecast.create-stream');
    Route::post('icecast/{channel}/enable',                 [AdminIcecastController::class, 'enable'])->name('icecast.enable');
    Route::post('icecast/{channel}/disable',                [AdminIcecastController::class, 'disable'])->name('icecast.disable');
    Route::post('icecast/{channel}/audio-relay/start',      [AdminIcecastController::class, 'startAudioRelay'])->name('icecast.audio-relay.start');
    Route::post('icecast/{channel}/audio-relay/stop',       [AdminIcecastController::class, 'stopAudioRelay'])->name('icecast.audio-relay.stop');
    Route::post('icecast/{channel}/forward',                [AdminIcecastController::class, 'forwardToServer'])->name('icecast.forward');
    Route::post('icecast/relay/start',                      [AdminIcecastController::class, 'startRelay'])->name('icecast.relay.start');

    // API Tokens
    Route::get('api-tokens',                                [AdminApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('api-tokens',                               [AdminApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('api-tokens/{token}',                     [AdminApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    // Settings (media server driver)
    Route::get('settings',                                  [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings',                                 [AdminSettingsController::class, 'update'])->name('settings.update');

    // SRT Streams
    Route::get('srt-streams',                               [SrtStreamController::class, 'index'])->name('srt-streams.index');
    Route::get('srt-streams/create',                        [SrtStreamController::class, 'create'])->name('srt-streams.create');
    Route::post('srt-streams',                              [SrtStreamController::class, 'store'])->name('srt-streams.store');
    Route::get('srt-streams/{srtStream}',                   [SrtStreamController::class, 'show'])->name('srt-streams.show');
    Route::get('srt-streams/{srtStream}/edit',              [SrtStreamController::class, 'edit'])->name('srt-streams.edit');
    Route::put('srt-streams/{srtStream}',                   [SrtStreamController::class, 'update'])->name('srt-streams.update');
    Route::patch('srt-streams/{srtStream}/toggle',          [SrtStreamController::class, 'toggle'])->name('srt-streams.toggle');
    Route::delete('srt-streams/{srtStream}',                [SrtStreamController::class, 'destroy'])->name('srt-streams.destroy');
    
    // SRT Dashboard & Management
    Route::get('srt-streams/api/list',                      [\App\Http\Controllers\Admin\SrtDashboardController::class, 'widget'])->name('srt-streams.list');
    Route::get('srt-streams/api/{id}/details',              [\App\Http\Controllers\Admin\SrtDashboardController::class, 'streamDetails'])->name('srt-streams.details');
    Route::get('srt-streams/api/{id}/logs',                 [\App\Http\Controllers\Admin\SrtDashboardController::class, 'logs'])->name('srt-streams.logs');
    Route::get('srt-streams/api/status',                    [\App\Http\Controllers\Admin\SrtDashboardController::class, 'status'])->name('srt-streams.status');

    // Access Codes
    Route::get('access-codes/create',                       [AccessCodeController::class, 'create'])->name('access-codes.create');
    Route::post('access-codes',                             [AccessCodeController::class, 'store'])->name('access-codes.store');
    Route::get('access-codes',                              [AccessCodeController::class, 'index'])->name('access-codes.index');
    Route::delete('access-codes/{accessCode}',              [AccessCodeController::class, 'destroy'])->name('access-codes.destroy');

    // Subscription Plans (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('plans',                                 [PlanController::class, 'index'])->name('plans.index');
        Route::get('plans/create',                          [PlanController::class, 'create'])->name('plans.create');
        Route::post('plans',                                [PlanController::class, 'store'])->name('plans.store');
        Route::get('plans/{plan}/edit',                     [PlanController::class, 'edit'])->name('plans.edit');
        Route::put('plans/{plan}',                          [PlanController::class, 'update'])->name('plans.update');
        Route::delete('plans/{plan}',                       [PlanController::class, 'destroy'])->name('plans.destroy');
    });

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

// ── VOD Manager (channel-scoped, access code protected) ───────────────────────────
Route::prefix('vod-manager/{channel}')->name('vod-manager.')->group(function () {
    Route::get('login',                          [VodManagerController::class, 'showLogin'])->name('login');
    Route::post('login',                         [VodManagerController::class, 'login'])->name('login.post');
    Route::post('logout',                        [VodManagerController::class, 'logout'])->name('logout');
    Route::get('/',                              [VodManagerController::class, 'index'])->name('index');
    Route::post('upload',                        [VodManagerController::class, 'store'])->name('store');
    Route::post('youtube',                       [VodManagerController::class, 'storeYoutube'])->name('store-youtube');
    Route::delete('{vodFile}',                   [VodManagerController::class, 'destroy'])->name('destroy');
    Route::post('reorder',                       [VodManagerController::class, 'reorder'])->name('reorder');
});
