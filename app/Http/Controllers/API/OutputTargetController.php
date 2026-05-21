<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutputTarget\BulkPushRequest;
use App\Http\Requests\OutputTarget\PushToUrlsRequest;
use App\Http\Requests\OutputTarget\StoreRequest;
use App\Http\Requests\OutputTarget\UpdateRequest;
use App\Http\Resources\OutputTargetResource;
use App\Models\Channel;
use App\Models\OutputTarget;
use App\Services\OutputManager;
use App\Services\StreamingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutputTargetController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OutputManager    $outputManager,
        protected StreamingService $streaming,
    ) {}

    public function index(Channel $channel): JsonResponse
    {
        $targets = $channel->outputTargets()
            ->orderBy('name')
            ->get();

        return $this->success(
            data: OutputTargetResource::collection($targets),
            message: 'Output targets retrieved successfully.'
        );
    }

    public function store(StoreRequest $request, Channel $channel): JsonResponse
    {
        $target = $channel->outputTargets()->create($request->validated());

        return $this->success(
            data: new OutputTargetResource($target),
            message: 'Output target created successfully.',
            statusCode: 201
        );
    }

    public function show(Channel $channel, OutputTarget $target): JsonResponse
    {
        $this->authorizeTarget($channel, $target);

        return $this->success(
            data: new OutputTargetResource($target),
            message: 'Output target retrieved successfully.'
        );
    }

    public function update(UpdateRequest $request, Channel $channel, OutputTarget $target): JsonResponse
    {
        $this->authorizeTarget($channel, $target);

        $validated = $request->validated();
        $wasRunning = $target->isRunning();
        $urlChanged = isset($validated['output_url']) || isset($validated['output_protocol']);
        $codecChanged = isset($validated['video_codec']) || isset($validated['audio_codec'])
            || isset($validated['resolution']) || isset($validated['framerate']);

        if ($wasRunning && ($urlChanged || $codecChanged)) {
            $this->outputManager->stopTarget($target);
        }

        $target->update($validated);

        if ($wasRunning && ($urlChanged || $codecChanged)) {
            $target->refresh();
            $this->outputManager->startTarget($target);
        }

        return $this->success(
            data: new OutputTargetResource($target->fresh()),
            message: 'Output target updated successfully.'
        );
    }

    public function destroy(Channel $channel, OutputTarget $target): JsonResponse
    {
        $this->authorizeTarget($channel, $target);
        $this->outputManager->stopTarget($target);
        $target->delete();

        return $this->success(message: 'Output target deleted successfully.');
    }

    public function start(Channel $channel, OutputTarget $target): JsonResponse
    {
        $this->authorizeTarget($channel, $target);
        $this->outputManager->startTarget($target);

        return $this->success(
            data: new OutputTargetResource($target->fresh()),
            message: 'Output target started successfully.'
        );
    }

    public function stop(Channel $channel, OutputTarget $target): JsonResponse
    {
        $this->authorizeTarget($channel, $target);
        $this->outputManager->stopTarget($target);

        return $this->success(
            data: new OutputTargetResource($target->fresh()),
            message: 'Output target stopped successfully.'
        );
    }

    public function restart(Channel $channel, OutputTarget $target): JsonResponse
    {
        $this->authorizeTarget($channel, $target);
        $this->outputManager->stopTarget($target);
        $target->update(['reconnect_attempts' => 0]);
        $this->outputManager->startTarget($target);

        return $this->success(
            data: new OutputTargetResource($target->fresh()),
            message: 'Output target restarted successfully.'
        );
    }

    public function logs(Channel $channel, OutputTarget $target, Request $request): JsonResponse
    {
        $this->authorizeTarget($channel, $target);

        $logs = $target->logs()
            ->latest()
            ->limit($request->integer('limit', 100))
            ->get();

        return $this->success(
            data: $logs,
            message: 'Output target logs retrieved successfully.'
        );
    }

    public function formats(Channel $channel): JsonResponse
    {
        $dir  = $this->streaming->outputDir($channel);
        $pipe = $this->streaming->pipePath($channel);
        $hls  = $this->streaming->hlsPlaylistPath($channel);

        $formats = [
            [
                'format'      => 'mpeg_ts_pipe',
                'description' => 'Raw MPEG-TS named pipe — zero latency, direct passthrough',
                'available'   => file_exists($pipe),
                'path'        => $pipe,
                'latency'     => 'zero',
                'use_for'     => ['rtmp', 'rtmps', 'srt', 'mpeg_ts_udp', 'mpeg_ts_tcp', 'rtp'],
            ],
            [
                'format'      => 'hls',
                'description' => 'HLS playlist — segmented, suitable for viewer playback and CDN',
                'available'   => file_exists($hls),
                'url'         => url("/streams/{$channel->slug}/playlist.m3u8"),
                'latency'     => config('services.stream.hls_segment_duration', 2) . 's per segment',
                'use_for'     => ['hls_push', 'browser_playback'],
            ],
        ];

        $segments = [];
        if (is_dir($dir)) {
            $files = glob("{$dir}/seg*.ts");
            if ($files) {
                $segments = array_map('basename', $files);
                sort($segments);
            }
        }

        return $this->success(
            data: [
                'channel'        => $channel->slug,
                'ingest_running' => $this->streaming->isIngestRunning($channel),
                'formats'        => $formats,
                'hls_segments'   => count($segments),
                'latest_segment' => end($segments) ?: null,
            ],
            message: 'Available formats retrieved successfully.'
        );
    }

    public function startAll(Channel $channel): JsonResponse
    {
        $this->outputManager->startChannelOutputs($channel, 'live');

        return $this->success(
            data: OutputTargetResource::collection(
                $channel->outputTargets()->where('is_enabled', true)->get()
            ),
            message: 'All enabled outputs started successfully.'
        );
    }

    public function stopAll(Channel $channel): JsonResponse
    {
        $this->outputManager->stopChannelOutputs($channel);

        return $this->success(
            data: OutputTargetResource::collection($channel->outputTargets()->get()),
            message: 'All outputs stopped successfully.'
        );
    }

    public function pushToUrls(PushToUrlsRequest $request, Channel $channel): JsonResponse
    {
        $results = $this->outputManager->pushChannelToUrls($channel, $request->validated()['destinations']);

        return $this->success(
            data: $results,
            message: count($results) . ' output(s) started successfully.',
            statusCode: 201
        );
    }

    public function bulkPush(BulkPushRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $results = $this->outputManager->pushChannelsToTargets(
            $validated['channel_ids'],
            $validated['target_ids']
        );

        return $this->success(
            data: $results,
            message: 'Bulk push initiated successfully.'
        );
    }

    public function globalStatus(): JsonResponse
    {
        $targets = OutputTarget::with('channel:id,name,slug')
            ->where('is_enabled', true)
            ->orderBy('channel_id')
            ->get();

        $summary = [
            'total'        => $targets->count(),
            'connected'    => $targets->where('status', 'connected')->count(),
            'connecting'   => $targets->where('status', 'connecting')->count(),
            'reconnecting' => $targets->where('status', 'reconnecting')->count(),
            'error'        => $targets->where('status', 'error')->count(),
            'stopped'      => $targets->where('status', 'stopped')->count(),
            'passthrough'  => $targets->where('is_passthrough', true)->count(),
            'transcoding'  => $targets->where('is_passthrough', false)->count(),
        ];

        return $this->success(
            data: [
                'summary' => $summary,
                'targets' => OutputTargetResource::collection($targets),
            ],
            message: 'Global output status retrieved successfully.'
        );
    }

    private function authorizeTarget(Channel $channel, OutputTarget $target): void
    {
        if ($target->channel_id !== $channel->id) {
            abort(404, 'Output target not found for this channel');
        }
    }
}
