<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Exception;

class IcecastService
{
    public function createIcecastStream(Channel $channel): array
    {
        try {
            $mountPoint = "/stream/{$channel->slug}";
            $password   = bin2hex(random_bytes(16));
            $maxListeners = config('services.icecast.max_listeners_per_stream', 1000);

            // Persist config in channel metadata
            $metadata = $channel->metadata ?? [];
            $metadata['icecast'] = [
                'mount_point'  => $mountPoint,
                'password'     => $password,
                'max_listeners'=> $maxListeners,
                'created_at'   => now()->toIso8601String(),
            ];
            $channel->update(['metadata' => $metadata, 'is_icecast_enabled' => true]);

            // Write per-mount config snippet for icecast2
            $this->writeMountConfig($channel, $mountPoint, $password, $maxListeners);

            Log::info("Icecast stream created", ['channel_id' => $channel->id, 'mount' => $mountPoint]);

            return [
                'success'     => true,
                'mount_point' => $mountPoint,
                'password'    => $password,
                'stream_url'  => $this->getStreamUrl($channel),
                'source_url'  => "icecast://source:{$password}@" . config('services.icecast.host', 'localhost') . ':' . config('services.icecast.port', 8000) . $mountPoint,
            ];
        } catch (Exception $e) {
            Log::error("Failed to create Icecast stream", ['channel_id' => $channel->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function writeMountConfig(Channel $channel, string $mountPoint, string $password, int $maxListeners): void
    {
        $icecastXml = '/etc/icecast2/icecast.xml';

        if (!file_exists($icecastXml) || !is_writable($icecastXml)) {
            // Fallback: write to conf_dir file (for systems that support include)
            $confDir = config('services.icecast.conf_dir', '/etc/icecast2/mounts');
            if (!is_dir($confDir)) @mkdir($confDir, 0755, true);
            file_put_contents("{$confDir}/{$channel->slug}.xml", $this->mountXml($channel, $mountPoint, $password, $maxListeners));
            $this->reloadIcecast();
            return;
        }

        $xml     = file_get_contents($icecastXml);
        $tag     = "<!-- mount:{$channel->slug} -->";
        $block   = "\n    {$tag}\n" . $this->mountXml($channel, $mountPoint, $password, $maxListeners) . "    <!-- /mount:{$channel->slug} -->";

        // Remove existing block for this channel if present
        $xml = preg_replace("/\n\s*<!-- mount:{$channel->slug} -->.*?<!-- \/mount:{$channel->slug} -->/s", '', $xml);

        // Insert before closing </icecast> tag
        $xml = str_replace('</icecast>', $block . "\n</icecast>", $xml);

        file_put_contents($icecastXml, $xml);
        $this->reloadIcecast();
    }

    protected function removeMountConfig(Channel $channel): void
    {
        $icecastXml = '/etc/icecast2/icecast.xml';
        if (!file_exists($icecastXml)) return;

        $xml = file_get_contents($icecastXml);
        $xml = preg_replace("/\n\s*<!-- mount:{$channel->slug} -->.*?<!-- \/mount:{$channel->slug} -->/s", '', $xml);
        file_put_contents($icecastXml, $xml);
        $this->reloadIcecast();
    }

    protected function mountXml(Channel $channel, string $mountPoint, string $password, int $maxListeners): string
    {
        return <<<XML
    <mount type="normal">
        <mount-name>{$mountPoint}</mount-name>
        <password>{$password}</password>
        <max-listeners>{$maxListeners}</max-listeners>
        <stream-name>{$channel->name}</stream-name>
        <public>1</public>
    </mount>
XML;
    }

    protected function reloadIcecast(): void
    {
        $output = null; $code = 0;
        // Try with sudo first (requires /etc/sudoers.d/www-data-icecast)
        exec('sudo systemctl restart icecast2 2>&1', $output, $code);
        if ($code !== 0) {
            // Fallback: send SIGHUP to reload config without full restart
            exec('pkill -HUP icecast2 2>&1', $output, $code);
        }
        if ($code !== 0) {
            Log::warning('Failed to restart Icecast2', ['output' => implode("\n", $output)]);
        }
    }

    public function getMountPoint(Channel $channel): ?string
    {
        return $channel->metadata['icecast']['mount_point'] ?? null;
    }

    public function getPassword(Channel $channel): ?string
    {
        return $channel->metadata['icecast']['password'] ?? null;
    }

    public function getPushCredentials(Channel $channel): ?array
    {
        $mount    = $this->getMountPoint($channel);
        $password = $this->getPassword($channel);

        if (!$mount || !$password) {
            return null;
        }

        $host = config('services.icecast.host', 'localhost');
        $port = config('services.icecast.port', 8000);

        return [
            'mount_point' => $mount,
            'password'    => $password,
            'push_url'    => "icecast://source:{$password}@{$host}:{$port}{$mount}",
            'host'        => $host,
            'port'        => $port,
        ];
    }

    public function getStreamUrl(Channel $channel): string
    {
        $host  = config('services.icecast.host', 'localhost');
        $port  = config('services.icecast.port', 8000);
        $mount = $this->getMountPoint($channel) ?? "/{$channel->slug}";

        return "http://{$host}:{$port}{$mount}";
    }

    public function setMaxListeners(Channel $channel, int $maxListeners): bool
    {
        $metadata = $channel->metadata ?? [];
        $metadata['icecast']['max_listeners'] = $maxListeners;
        $channel->update(['metadata' => $metadata]);

        // Rewrite config with new limit
        $mount    = $this->getMountPoint($channel);
        $password = $this->getPassword($channel);
        if ($mount && $password) {
            $this->writeMountConfig($channel, $mount, $password, $maxListeners);
        }

        return true;
    }

    public function getStreamStats(Channel $channel): array
    {
        try {
            $mountPoint  = $this->getMountPoint($channel);
            $host        = config('services.icecast.host', 'localhost');
            $port        = config('services.icecast.port', 8000);
            $adminUser   = config('services.icecast.admin_user', 'admin');
            $adminPass   = config('services.icecast.admin_password', 'hackme');

            $ch = curl_init("http://{$host}:{$port}/admin/stats");
            curl_setopt_array($ch, [
                CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
                CURLOPT_USERPWD        => "{$adminUser}:{$adminPass}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $response = curl_exec($ch);
            $errno    = curl_errno($ch);
            curl_close($ch);

            if ($errno || !$response) {
                return ['listeners' => 0, 'bitrate' => 0, 'connected' => false];
            }

            $xml = @simplexml_load_string($response);
            if ($xml) {
                foreach ($xml->source as $source) {
                    if ((string) $source->attributes()['mount'] === $mountPoint
                        || (string) $source->mount === $mountPoint) {
                        return [
                            'listeners' => (int) $source->listeners,
                            'bitrate'   => (int) $source->bitrate,
                            'connected' => true,
                            'title'     => (string) $source->title,
                        ];
                    }
                }
            }

            return ['listeners' => 0, 'bitrate' => 0, 'connected' => false];
        } catch (Exception $e) {
            return ['listeners' => 0, 'bitrate' => 0, 'connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function disconnectStream(Channel $channel): bool
    {
        try {
            $mountPoint = $this->getMountPoint($channel);
            $host       = config('services.icecast.host', 'localhost');
            $port       = config('services.icecast.port', 8000);
            $adminUser  = config('services.icecast.admin_user', 'admin');
            $adminPass  = config('services.icecast.admin_password', 'hackme');

            if ($mountPoint) {
                $ch = curl_init("http://{$host}:{$port}/admin/killsource?mount=" . urlencode($mountPoint));
                curl_setopt_array($ch, [
                    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
                    CURLOPT_USERPWD        => "{$adminUser}:{$adminPass}",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                ]);
                curl_exec($ch);
                curl_close($ch);
            }

            return true;
        } catch (Exception $e) {
            Log::error("Failed to disconnect Icecast", ['channel_id' => $channel->id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
