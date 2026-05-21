<?php

namespace App\Services;

class ProtocolDetector
{
    // Supported ingest protocols
    const PROTO_RTMP   = 'rtmp';
    const PROTO_RTMPS  = 'rtmps';
    const PROTO_HLS    = 'hls';
    const PROTO_DASH   = 'dash';
    const PROTO_RTSP   = 'rtsp';
    const PROTO_SRT    = 'srt';
    const PROTO_UDP    = 'udp';
    const PROTO_RTP    = 'rtp';
    const PROTO_MPEGTS = 'mpegts'; // raw TCP MPEG-TS
    const PROTO_HTTP   = 'http';
    const PROTO_HTTPS  = 'https';
    const PROTO_FILE   = 'file';
    const PROTO_UNKNOWN = 'unknown';

    public function detect(string $url): string
    {
        $lower = strtolower($url);

        return match (true) {
            str_starts_with($lower, 'rtmps://')          => self::PROTO_RTMPS,
            str_starts_with($lower, 'rtmp://')           => self::PROTO_RTMP,
            str_starts_with($lower, 'rtsp://')           => self::PROTO_RTSP,
            str_starts_with($lower, 'srt://')            => self::PROTO_SRT,
            str_starts_with($lower, 'udp://')            => self::PROTO_UDP,
            str_starts_with($lower, 'rtp://')            => self::PROTO_RTP,
            str_starts_with($lower, 'tcp://')            => self::PROTO_MPEGTS,
            str_starts_with($lower, 'https://')          => $this->detectHttp($lower, true),
            str_starts_with($lower, 'http://')           => $this->detectHttp($lower, false),
            str_starts_with($lower, '/')                 => self::PROTO_FILE,
            str_starts_with($lower, 'file://')           => self::PROTO_FILE,
            default                                      => self::PROTO_UNKNOWN,
        };
    }

    private function detectHttp(string $url, bool $secure): string
    {
        if (str_contains($url, '.m3u8')) return self::PROTO_HLS;
        if (str_contains($url, '.mpd'))  return self::PROTO_DASH;
        return $secure ? self::PROTO_HTTPS : self::PROTO_HTTP;
    }

    /**
     * Returns FFmpeg input arguments for the given source URL.
     * These go BEFORE the -i flag.
     */
    public function getInputArgs(string $url): array
    {
        $proto = $this->detect($url);

        return match ($proto) {
            self::PROTO_RTMP, self::PROTO_RTMPS => [
                '-rtmp_live', 'live',
            ],
            self::PROTO_RTSP => [
                '-rtsp_transport', 'tcp',
                '-stimeout', '5000000', // 5s timeout in microseconds
            ],
            self::PROTO_SRT => [
                '-fflags', 'nobuffer',
                '-flags', 'low_delay',
            ],
            self::PROTO_UDP, self::PROTO_RTP => [
                '-fflags', 'nobuffer',
                '-flags', 'low_delay',
                '-analyzeduration', '1000000',
                '-probesize', '1000000',
            ],
            self::PROTO_MPEGTS => [
                '-f', 'mpegts',
                '-fflags', 'nobuffer',
            ],
            self::PROTO_HLS, self::PROTO_DASH => [
                '-reconnect', '1',
                '-reconnect_at_eof', '1',
                '-reconnect_streamed', '1',
                '-reconnect_delay_max', '5',
            ],
            self::PROTO_HTTP, self::PROTO_HTTPS => [
                '-reconnect', '1',
                '-reconnect_at_eof', '1',
                '-reconnect_streamed', '1',
            ],
            default => [],
        };
    }

    /**
     * Check if a source URL is reachable using the appropriate method per protocol.
     */
    public function isReachable(string $url, int $timeout = 5): bool
    {
        $proto = $this->detect($url);

        return match ($proto) {
            self::PROTO_RTMP, self::PROTO_RTMPS => $this->checkTcp($url, 1935, $timeout),
            self::PROTO_RTSP                    => $this->checkTcp($url, 554, $timeout),
            self::PROTO_SRT                     => $this->checkUdp($url, $timeout),
            self::PROTO_UDP, self::PROTO_RTP    => $this->checkUdp($url, $timeout),
            self::PROTO_MPEGTS                  => $this->checkTcp($url, 1234, $timeout),
            self::PROTO_HLS, self::PROTO_DASH,
            self::PROTO_HTTP, self::PROTO_HTTPS => $this->checkHttp($url, $timeout),
            self::PROTO_FILE                    => file_exists(str_replace('file://', '', $url)),
            default                             => false,
        };
    }

    private function checkTcp(string $url, int $defaultPort, int $timeout): bool
    {
        $parsed = parse_url($url);
        $host   = $parsed['host'] ?? null;
        $port   = $parsed['port'] ?? $defaultPort;

        if (!$host) return false;

        $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($sock) {
            fclose($sock);
            return true;
        }
        return false;
    }

    private function checkUdp(string $url, int $timeout): bool
    {
        // UDP/SRT: we can only verify the host resolves — actual stream
        // availability requires FFmpeg probe. Return true to let FFmpeg decide.
        $parsed = parse_url($url);
        $host   = $parsed['host'] ?? null;
        if (!$host) return false;
        return (bool) @gethostbyname($host);
    }

    private function checkHttp(string $url, int $timeout): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code > 0 && $code < 500;
    }

    public function label(string $url): string
    {
        return strtoupper($this->detect($url));
    }
}
