<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;

class GraphicsOverlayService
{
    /**
     * Build FFmpeg filter string for channel logo, watermark, and ticker.
     * Returns an array of filter strings to insert into the filter_complex chain.
     */
    public function buildFilterChains(Channel $channel): array
    {
        $filters = [];

        $logoFilter = $this->buildLogoFilter($channel);
        if ($logoFilter) {
            $filters[] = $logoFilter;
        }

        $watermarkFilter = $this->buildWatermarkFilter($channel);
        if ($watermarkFilter) {
            $filters[] = $watermarkFilter;
        }

        $tickerFilter = $this->buildTickerFilter($channel);
        if ($tickerFilter) {
            $filters[] = $tickerFilter;
        }

        return $filters;
    }

    /**
     * Build the FFmpeg video filter string for all overlays combined.
     * Returns a single filter_complex string that chains logo -> watermark -> ticker.
     */
    public function buildFilterComplex(Channel $channel): ?string
    {
        $chains = $this->buildFilterChains($channel);

        if (empty($chains)) {
            return null;
        }

        // First chain: [0:v]filter1[bg1]
        // Subsequent: [bg1]filter2[bg2]
        // Last label: [outv]
        $parts = [];
        $input = '[0:v]';

        foreach ($chains as $i => $filter) {
            $output = ($i === count($chains) - 1) ? '[outv]' : "[bg{$i}]";
            $parts[] = "{$input}{$filter}{$output}";
            $input = $output;
        }

        return implode(';', $parts);
    }

    protected function buildLogoFilter(Channel $channel): ?string
    {
        if (!$channel->logo_path) {
            return null;
        }

        $logoPath = storage_path("app/public/{$channel->logo_path}");
        if (!file_exists($logoPath)) {
            return null;
        }

        $w = $channel->logo_width ?: 150;
        $h = $channel->logo_height ?: -1; // -1 = auto scale
        $opacity = ($channel->logo_opacity ?? 80) / 100;

        $overlay = match ($channel->logo_position ?? 'top-right') {
            'top-left'     => '10:10',
            'top-right'    => 'main_w-overlay_w-10:10',
            'bottom-left'  => '10:main_h-overlay_h-10',
            'bottom-right' => 'main_w-overlay_w-10:main_h-overlay_h-10',
            default        => 'main_w-overlay_w-10:10',
        };

        // Scale logo, set opacity, then overlay
        if ($h > 0) {
            $scaleFilter = "scale={$w}:{$h},format=rgba,colorchannelmixer=aa={$opacity}[logo]";
        } else {
            $scaleFilter = "scale={$w}:-1,format=rgba,colorchannelmixer=aa={$opacity}[logo]";
        }

        return "movie='{$logoPath}',{$scaleFilter};[0:v][logo]overlay={$overlay}:format=auto";
    }

    protected function buildWatermarkFilter(Channel $channel): ?string
    {
        if (!$channel->watermark_path) {
            return null;
        }

        $wmPath = storage_path("app/public/{$channel->watermark_path}");
        if (!file_exists($wmPath)) {
            return null;
        }

        $opacity = ($channel->watermark_opacity ?? 40) / 100;

        $overlay = match ($channel->watermark_position ?? 'bottom-right') {
            'top-left'     => '10:10',
            'top-right'    => 'main_w-overlay_w-10:10',
            'bottom-left'  => '10:main_h-overlay_h-10',
            'bottom-right' => 'main_w-overlay_w-10:main_h-overlay_h-10',
            default        => 'main_w-overlay_w-10:main_h-overlay_h-10',
        };

        return "movie='{$wmPath}',format=rgba,colorchannelmixer=aa={$opacity}[wm];[0:v][wm]overlay={$overlay}:format=auto";
    }

    protected function buildTickerFilter(Channel $channel): ?string
    {
        if (!$channel->ticker_enabled || !$channel->ticker_text) {
            return null;
        }

        $text      = escapeshellarg($channel->ticker_text);
        $fontSize  = $channel->ticker_font_size ?: 24;
        $textColor = $channel->ticker_text_color ?: '#ffffff';
        $bgColor   = $channel->ticker_bg_color ?: '#000000';
        $speedPx   = $channel->ticker_speed_ms ?: 120;
        $position  = $channel->ticker_position === 'top' ? '10:10' : '10:main_h-th-10';

        $speedW = max(1, $speedPx);

        return "drawtext=text={$text}:fontsize={$fontSize}:fontcolor={$textColor}:"
            . "box=1:boxcolor={$bgColor}:boxborderw=5:"
            . "x='if(gte(t,0), -w+mod(t*{$speedW}\\,main_w+w), NAN)':"
            . "y='if(gte(th,0), main_h-th-10, 10)':"
            . "fontfile=/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf";
    }

    /**
     * Whether the channel has any overlay graphics enabled.
     */
    public function hasOverlays(Channel $channel): bool
    {
        return $channel->logo_path !== null
            || $channel->watermark_path !== null
            || ($channel->ticker_enabled && $channel->ticker_text);
    }
}
