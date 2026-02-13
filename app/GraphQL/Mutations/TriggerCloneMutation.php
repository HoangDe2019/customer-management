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

class TriggerCloneMutation extends Mutation
{
    protected $attributes = [
        'name' => 'triggerClone',
        'description' => 'Kích hoạt clone DB to staging. Kết quả gửi qua notification (job-updates).',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $requestId = Str::uuid()->toString();

        ProcessQueueJob::dispatch('sync_to_staging', [], 'default');

        event(new RequestCompleted($requestId, true, 'queue', 'clone_trigger', ['message' => 'Clone job added'], null));
        return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã thêm job clone; kết quả gửi qua channel job-updates.'];
    }
}
