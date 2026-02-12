<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $entity,
        public ?string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('data-updates')];
    }

    public function broadcastAs(): string
    {
        return 'data.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'entity' => $this->entity,
            'action' => $this->action,
        ];
    }
}
