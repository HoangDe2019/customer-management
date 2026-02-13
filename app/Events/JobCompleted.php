<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $jobType,
        public string $status,
        public array $payload = []
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('job-updates')];
    }

    public function broadcastAs(): string
    {
        return 'job.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'job_type' => $this->jobType,
            'status' => $this->status,
            'payload' => $this->payload,
        ];
    }
}
