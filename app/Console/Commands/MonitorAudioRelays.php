<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AudioRelayService;
use Illuminate\Console\Command;

class MonitorAudioRelays extends Command
{
    protected $signature = 'audio-relay:monitor';
    protected $description = 'Monitor and auto-manage audio relay processes';

    public function handle(AudioRelayService $audioRelay): int
    {
        $this->info('Monitoring audio relays...');
        $audioRelay->monitorAudioRelays();
        $this->info('Done.');
        return 0;
    }
}
