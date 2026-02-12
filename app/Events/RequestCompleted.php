<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $requestId,
        public bool $success,
        public string $entity,
        public string $action,
        public ?array $data = null,
        public ?string $error = null
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('request-results')];
    }

    public function broadcastAs(): string
    {
        return 'request.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->requestId,
            'success' => $this->success,
            'entity' => $this->entity,
            'action' => $this->action,
            'data' => $this->data,
            'error' => $this->error,
        ];
    }
}
