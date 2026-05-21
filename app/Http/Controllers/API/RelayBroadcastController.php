<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Relay\AddServerRequest;
use App\Http\Requests\Relay\StartRequest;
use App\Http\Resources\RelayBroadcastResource;
use App\Http\Resources\RelayServerResource;
use App\Models\Channel;
use App\Models\RelayBroadcast;
use App\Models\RelayServer;
use App\Services\RelayBroadcastService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelayBroadcastController extends Controller
{
    use ApiResponse;

    public function __construct(protected RelayBroadcastService $relayService) {}

    public function getServers(): JsonResponse
    {
        $servers = RelayServer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'hostname', 'port', 'server_type', 'max_listeners', 'location']);

        return $this->success(
            data: RelayServerResource::collection($servers),
            message: 'Relay servers retrieved successfully.'
        );
    }

    public function addServer(AddServerRequest $request): JsonResponse
    {
        try {
            $server = RelayServer::create($request->validated());

            return $this->success(
                data: new RelayServerResource($server),
                message: 'Relay server added successfully.',
                statusCode: 201
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to add relay server: ' . $e->getMessage(), 'RELAY_SERVER_CREATE_FAILED');
        }
    }

    public function start(StartRequest $request, Channel $channel): JsonResponse
    {
        try {
            $relayServer = RelayServer::findOrFail($request->validated()['relay_server_id']);
            $relay = $this->relayService->startRelay($channel, $relayServer);

            return $this->success(
                data: new RelayBroadcastResource($relay),
                message: 'Relay broadcast started successfully.',
                statusCode: 201
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to start relay: ' . $e->getMessage(), 'RELAY_START_FAILED');
        }
    }

    public function stop(RelayBroadcast $relay): JsonResponse
    {
        try {
            $this->relayService->stopRelay($relay);

            return $this->success(message: 'Relay broadcast stopped successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to stop relay: ' . $e->getMessage());
        }
    }

    public function status(RelayBroadcast $relay): JsonResponse
    {
        try {
            $stats = $this->relayService->getRelayStats($relay);

            return $this->success(
                data: $stats,
                message: 'Relay status retrieved successfully.'
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to get relay status: ' . $e->getMessage());
        }
    }

    public function getChannelRelays(Channel $channel): JsonResponse
    {
        try {
            $relays = $this->relayService->getChannelRelays($channel);

            return $this->success(
                data: RelayBroadcastResource::collection($relays),
                message: 'Channel relays retrieved successfully.'
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to get channel relays: ' . $e->getMessage());
        }
    }

    public function getLogs(RelayBroadcast $relay, Request $request): JsonResponse
    {
        $logs = $relay->logs()
            ->latest()
            ->limit($request->integer('limit', 50))
            ->get();

        return $this->success(
            data: $logs,
            message: 'Relay logs retrieved successfully.'
        );
    }

    public function enableRelay(Channel $channel): JsonResponse
    {
        try {
            $channel->update(['is_relay_enabled' => true]);

            return $this->success(message: 'Relay enabled for channel successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to enable relay: ' . $e->getMessage());
        }
    }

    public function disableRelay(Channel $channel): JsonResponse
    {
        try {
            $channel->relays()->where('is_active', true)->each(function ($relay) {
                $this->relayService->stopRelay($relay);
            });

            $channel->update(['is_relay_enabled' => false]);

            return $this->success(message: 'Relay disabled for channel successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to disable relay: ' . $e->getMessage());
        }
    }
}
