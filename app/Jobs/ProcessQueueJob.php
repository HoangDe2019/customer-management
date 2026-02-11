<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TransactionService;
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
                'cccd_scan'           => $this->handleCccdScan(),
                'export_transactions' => $this->handleExportTransactions(),
                'sync_to_staging'     => $this->handleSyncToStaging(),

                // New generic business job types
                'create_user'         => $this->handleCreateUser(),
                'create_transaction'  => $this->handleCreateTransaction(),

                default               => Log::warning('ProcessQueueJob unknown type: ' . $this->type),
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

    /**
     * Create a user asynchronously (basic example).
     *
     * Expected payload:
     * - data: array{name, email, password, role?}
     */
    protected function handleCreateUser(): void
    {
        $data = $this->payload['data'] ?? [];

        if (! isset($data['name'], $data['email'], $data['password'])) {
            Log::warning('ProcessQueueJob:create_user missing required fields', ['payload' => $this->payload]);
            return;
        }

        // Let database constraints enforce unique email, etc.
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            // Password is expected to be already hashed at the edge (controller/service)
            'password' => $data['password'],
            'role'     => $data['role'] ?? 'user',
        ]);

        Log::info('ProcessQueueJob:create_user created user', ['id' => $user->id, 'email' => $user->email]);
    }

    /**
     * Create a transaction asynchronously via TransactionService.
     *
     * Expected payload:
     * - user_id: int
     * - data: array (validated transaction input)
     */
    protected function handleCreateTransaction(): void
    {
        if (! isset($this->payload['user_id'], $this->payload['data']) || ! is_array($this->payload['data'])) {
            Log::warning('ProcessQueueJob:create_transaction missing required payload', ['payload' => $this->payload]);
            return;
        }

        $userId = (int) $this->payload['user_id'];
        $data   = $this->payload['data'];

        /** @var \App\Models\User $user */
        $user = User::find($userId);

        if (! $user) {
            Log::warning('ProcessQueueJob:create_transaction user not found', ['user_id' => $userId]);
            return;
        }

        /** @var TransactionService $service */
        $service = app(TransactionService::class);

        $transaction = $service->createTransaction($data, $user);

        Log::info('ProcessQueueJob:create_transaction created transaction', [
            'transaction_id' => $transaction->id,
            'agent_id'       => $transaction->agent_id,
        ]);
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
