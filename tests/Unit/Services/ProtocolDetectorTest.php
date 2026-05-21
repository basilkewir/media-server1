<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProtocolDetector;
use PHPUnit\Framework\TestCase;

class ProtocolDetectorTest extends TestCase
{
    private ProtocolDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ProtocolDetector();
    }

    public function test_detects_rtmp(): void
    {
        $this->assertSame('rtmp', $this->detector->detect('rtmp://example.com/live/stream'));
    }

    public function test_detects_rtmps(): void
    {
        $this->assertSame('rtmps', $this->detector->detect('rtmps://example.com/live/stream'));
    }

    public function test_detects_hls(): void
    {
        $this->assertSame('hls', $this->detector->detect('https://example.com/stream.m3u8'));
    }

    public function test_detects_dash(): void
    {
        $this->assertSame('dash', $this->detector->detect('https://example.com/stream.mpd'));
    }

    public function test_detects_rtsp(): void
    {
        $this->assertSame('rtsp', $this->detector->detect('rtsp://example.com/stream'));
    }

    public function test_detects_srt(): void
    {
        $this->assertSame('srt', $this->detector->detect('srt://example.com:9000'));
    }

    public function test_detects_udp(): void
    {
        $this->assertSame('udp', $this->detector->detect('udp://239.0.0.1:1234'));
    }

    public function test_detects_file(): void
    {
        $this->assertSame('file', $this->detector->detect('/path/to/video.mp4'));
        $this->assertSame('file', $this->detector->detect('file:///path/to/video.mp4'));
    }

    public function test_detects_http(): void
    {
        $this->assertSame('http', $this->detector->detect('http://example.com/stream'));
    }

    public function test_detects_https(): void
    {
        $this->assertSame('https', $this->detector->detect('https://example.com/stream'));
    }

    public function test_returns_unknown_for_unrecognized(): void
    {
        $this->assertSame('unknown', $this->detector->detect('ftp://example.com/file'));
    }

    public function test_label_returns_uppercase(): void
    {
        $this->assertSame('RTMP', $this->detector->label('rtmp://example.com/live'));
    }

    public function test_get_input_args_for_rtsp(): void
    {
        $args = $this->detector->getInputArgs('rtsp://example.com/stream');
        $this->assertContains('-rtsp_transport', $args);
        $this->assertContains('tcp', $args);
    }

    public function test_get_input_args_for_hls(): void
    {
        $args = $this->detector->getInputArgs('https://example.com/playlist.m3u8');
        $this->assertContains('-reconnect', $args);
    }

    public function test_is_reachable_returns_false_for_unknown(): void
    {
        $this->assertFalse($this->detector->isReachable('unknown://example.com'));
    }
}
