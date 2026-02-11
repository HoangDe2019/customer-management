<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_KEY_CLONE_LAST = 'queue:clone_to_staging_last';

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $type,
        public array $payload = [],
        ?string $queue = null
    ) {
        $this->onQueue($queue ?? 'default');
    }

    public function handle(): void
    {
        Log::info('ProcessQueueJob started', ['type' => $this->type, 'payload_keys' => array_keys($this->payload)]);

        try {
            match ($this->type) {
                'cccd_scan' => $this->handleCccdScan(),
                'export_transactions' => $this->handleExportTransactions(),
                'sync_to_staging' => $this->handleSyncToStaging(),
                default => Log::warning('ProcessQueueJob unknown type: ' . $this->type),
            };
        } catch (\Throwable $e) {
            if ($this->type === 'sync_to_staging') {
                Cache::put(self::CACHE_KEY_CLONE_LAST, [
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'finished_at' => now()->toIso8601String(),
                ], now()->addDays(7));
            }
            Log::error('ProcessQueueJob failed', ['type' => $this->type, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function handleCccdScan(): void
    {
        Log::info('ProcessQueueJob: cccd_scan processed', ['payload' => array_diff_key($this->payload, ['image' => 1])]);
    }

    protected function handleExportTransactions(): void
    {
        Log::info('ProcessQueueJob: export_transactions processed', $this->payload);
    }

    protected function handleSyncToStaging(): void
    {
        $startedAt = now()->toIso8601String();
        Cache::put(self::CACHE_KEY_CLONE_LAST, [
            'status' => 'running',
            'started_at' => $startedAt,
            'message' => 'Clone started',
        ], now()->addHours(1));

        $exitCode = \Illuminate\Support\Facades\Artisan::call('db:clone-to-staging');

        Cache::put(self::CACHE_KEY_CLONE_LAST, [
            'status' => $exitCode === 0 ? 'success' : 'failed',
            'message' => $exitCode === 0 ? 'Clone completed.' : 'Clone command returned non-zero.',
            'started_at' => $startedAt,
            'finished_at' => now()->toIso8601String(),
            'exit_code' => $exitCode,
        ], now()->addDays(7));

        Log::info('ProcessQueueJob: sync_to_staging completed', ['exit_code' => $exitCode]);
    }
}
