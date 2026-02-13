<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RequestCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $requestId;
    public $success;
    public $resourceType;
    public $action;
    public $data;
    public $error;
    public $userId;

    public function __construct(
        string $requestId,
        bool $success,
        string $resourceType,
        string $action,
        ?array $data = null,
        ?string $error = null
    ) {
        $this->requestId = $requestId;
        $this->success = $success;
        $this->resourceType = $resourceType;
        $this->action = $action;
        $this->data = $data;
        $this->error = $error;
        $this->userId = auth()->id();

        // ✅ Add debug logging
        Log::info('RequestCompleted event created', [
            'request_id' => $requestId,
            'success' => $success,
            'user_id' => $this->userId,
            'resource_type' => $resourceType,
            'action' => $action,
        ]);
    }

    public function broadcastOn(): array
    {
        $channel = new PrivateChannel('user.' . $this->userId);

        // ✅ Add debug logging
        Log::info('Broadcasting on channel', [
            'channel' => 'user.' . $this->userId,
            'request_id' => $this->requestId,
        ]);

        return [$channel];
    }

    public function broadcastAs(): string
    {
        return 'request.completed';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'request_id' => $this->requestId,
            'success' => $this->success,
            'resource_type' => $this->resourceType,
            'action' => $this->action,
            'data' => $this->data,
            'error' => $this->error,
            'timestamp' => now()->toIso8601String(),
        ];

        // ✅ Add debug logging
        Log::info('Broadcasting payload', [
            'request_id' => $this->requestId,
            'payload' => $payload,
        ]);

        return $payload;
    }
}
