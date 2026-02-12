<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Jobs\ProcessQueueJob;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class QueueDispatchMutation extends Mutation
{
    protected $attributes = [
        'name' => 'queueDispatch',
        'description' => 'Thêm job vào queue. Kết quả gửi qua notification (job-updates).',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'type' => ['type' => Type::nonNull(Type::string())],
            'payload' => ['type' => Type::string(), 'description' => 'JSON object'],
            'queue' => ['type' => Type::string()],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $requestId = Str::uuid()->toString();
        $payload = !empty($args['payload']) ? (json_decode($args['payload'], true) ?? []) : [];

        ProcessQueueJob::dispatch(
            $args['type'],
            $payload,
            $args['queue'] ?? null
        );

        event(new RequestCompleted($requestId, true, 'queue', 'dispatch', ['message' => 'Job added to queue'], null));
        return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Job đã thêm vào queue; kết quả xử lý gửi qua channel job-updates.'];
    }
}
