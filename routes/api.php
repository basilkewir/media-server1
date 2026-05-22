<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ChannelController;
use App\Http\Controllers\API\StreamController;
use App\Http\Controllers\API\IcecastController;
use App\Http\Controllers\API\RelayBroadcastController;
use App\Http\Controllers\API\OutputTargetController;
use App\Http\Controllers\API\AccessCodeController;
use App\Http\Controllers\API\RtmpWebhookController;
use App\Http\Controllers\API\SrtWebhookController;

Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'service' => 'MediaServer',
    'version' => config('app.version', '1.1.0'),
    'timestamp' => now()->toIso8601String(),
    'environment' => app()->environment(),
]));

// ── RTMP Webhooks (called by nginx-rtmp or SRS, no auth needed) ────────────
Route::post('streams/start', [RtmpWebhookController::class, 'onPublish']);
Route::post('streams/stop',  [RtmpWebhookController::class, 'onPublishDone']);

// ── SRT Webhooks (called by vMix/OBS/FFmpeg, no auth needed) ─────────────
Route::post('srt/connect',    [SrtWebhookController::class, 'onConnect']);
Route::post('srt/disconnect', [SrtWebhookController::class, 'onDisconnect']);
Route::post('srt/start',      [SrtWebhookController::class, 'startStream']);
Route::post('srt/stop',       [SrtWebhookController::class, 'stopStream']);

Route::middleware(['auth.api', 'throttle.api'])->group(function () {

    // ── Channels ──────────────────────────────────────────────────────────────
    Route::apiResource('channels', ChannelController::class);
    Route::get('channels/{channel}/status',  [ChannelController::class, 'status']);
    Route::get('channels/{channel}/events',  [ChannelController::class, 'events']);

    // ── Streams ───────────────────────────────────────────────────────────────
    Route::post('streams/launch',                 [StreamController::class, 'start'])
        ->middleware('throttle:stream_start');
    Route::post('streams/halt',                   [StreamController::class, 'stop']);
    Route::post('streams/probe',                  [StreamController::class, 'probe']);
    Route::get('streams/{channel}/status',        [StreamController::class, 'status']);
    Route::post('streams/{channel}/fallback',     [StreamController::class, 'fallback']);
    Route::post('streams/{channel}/recover',      [StreamController::class, 'recover']);
    Route::get('streams/{channel}/statistics',    [StreamController::class, 'statistics']);
    Route::get('streams/{channel}/recent',        [StreamController::class, 'recent']);

    // ── Output Targets ────────────────────────────────────────────────────────
    Route::get('outputs/status',                                    [OutputTargetController::class, 'globalStatus']);
    Route::post('outputs/bulk-push',                                [OutputTargetController::class, 'bulkPush']);

    Route::get('channels/{channel}/outputs',                        [OutputTargetController::class, 'index']);
    Route::post('channels/{channel}/outputs',                       [OutputTargetController::class, 'store']);
    Route::get('channels/{channel}/outputs/{target}',               [OutputTargetController::class, 'show']);
    Route::put('channels/{channel}/outputs/{target}',               [OutputTargetController::class, 'update']);
    Route::delete('channels/{channel}/outputs/{target}',            [OutputTargetController::class, 'destroy']);

    Route::get('channels/{channel}/formats',                        [OutputTargetController::class, 'formats']);

    Route::post('channels/{channel}/outputs/start-all',             [OutputTargetController::class, 'startAll']);
    Route::post('channels/{channel}/outputs/stop-all',              [OutputTargetController::class, 'stopAll']);
    Route::post('channels/{channel}/outputs/push',                  [OutputTargetController::class, 'pushToUrls']);

    Route::post('channels/{channel}/outputs/{target}/start',        [OutputTargetController::class, 'start']);
    Route::post('channels/{channel}/outputs/{target}/stop',         [OutputTargetController::class, 'stop']);
    Route::post('channels/{channel}/outputs/{target}/restart',      [OutputTargetController::class, 'restart']);
    Route::get('channels/{channel}/outputs/{target}/logs',          [OutputTargetController::class, 'logs']);

    // ── Icecast ───────────────────────────────────────────────────────────────
    Route::post('icecast/{channel}/create',           [IcecastController::class, 'create']);
    Route::get('icecast/{channel}/url',               [IcecastController::class, 'getStreamUrl']);
    Route::get('icecast/{channel}/stats',             [IcecastController::class, 'getStats']);
    Route::delete('icecast/{channel}/disconnect',     [IcecastController::class, 'disconnect']);
    Route::put('icecast/{channel}/max-listeners',     [IcecastController::class, 'setMaxListeners']);
    Route::post('icecast/{channel}/enable',           [IcecastController::class, 'enable']);
    Route::post('icecast/{channel}/disable',          [IcecastController::class, 'disable']);

    // ── Relay servers ─────────────────────────────────────────────────────────
    Route::get('relay/servers',                       [RelayBroadcastController::class, 'getServers']);
    Route::post('relay/servers',                      [RelayBroadcastController::class, 'addServer']);
    Route::post('relay/{channel}/start',              [RelayBroadcastController::class, 'start']);
    Route::post('relay/broadcast/{relay}/stop',       [RelayBroadcastController::class, 'stop']);
    Route::get('relay/broadcast/{relay}/status',      [RelayBroadcastController::class, 'status']);
    Route::get('relay/broadcast/{relay}/logs',        [RelayBroadcastController::class, 'getLogs']);
    Route::get('relay/{channel}/broadcasts',          [RelayBroadcastController::class, 'getChannelRelays']);
    Route::post('relay/{channel}/enable',             [RelayBroadcastController::class, 'enableRelay']);
    Route::post('relay/{channel}/disable',            [RelayBroadcastController::class, 'disableRelay']);

    // ── Access Codes ──────────────────────────────────────────────────────────
    Route::post('access-codes/validate', [AccessCodeController::class, 'validateCode']);
    Route::post('access-codes/redeem',   [AccessCodeController::class, 'redeem']);
    Route::get('access-codes/status',    [AccessCodeController::class, 'status']);
});
