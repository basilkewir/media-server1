<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\StoreRequest;
use App\Http\Requests\Channel\UpdateRequest;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\StreamEventResource;
use App\Models\Channel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $channels = Channel::with(['streams' => fn($q) => $q->latest()->limit(1)])
            ->orderBy('name')
            ->get();

        return $this->success(
            data: ChannelResource::collection($channels),
            message: 'Channels retrieved successfully.'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $channel = Channel::create($request->validated());

        return $this->success(
            data: new ChannelResource($channel),
            message: 'Channel created successfully.',
            statusCode: 201
        );
    }

    public function show(Channel $channel): JsonResponse
    {
        $channel->load(['streams' => fn($q) => $q->latest()->limit(1)]);

        return $this->success(
            data: new ChannelResource($channel),
            message: 'Channel retrieved successfully.'
        );
    }

    public function update(UpdateRequest $request, Channel $channel): JsonResponse
    {
        $channel->update($request->validated());

        return $this->success(
            data: new ChannelResource($channel->fresh()),
            message: 'Channel updated successfully.'
        );
    }

    public function destroy(Channel $channel): JsonResponse
    {
        $channel->delete();

        return $this->success(message: 'Channel deleted successfully.');
    }

    public function status(Channel $channel): JsonResponse
    {
        $channel->load(['streams' => fn($q) => $q->latest()->limit(1)]);

        return $this->success(
            data: new ChannelResource($channel),
            message: 'Channel status retrieved successfully.'
        );
    }

    public function events(Request $request, Channel $channel): JsonResponse
    {
        $events = $channel->events()
            ->recent($request->integer('minutes', 60))
            ->latest()
            ->limit($request->integer('limit', 50))
            ->get();

        return $this->success(
            data: StreamEventResource::collection($events),
            message: 'Channel events retrieved successfully.'
        );
    }
}
