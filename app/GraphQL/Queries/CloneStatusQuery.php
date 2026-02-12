<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Jobs\ProcessQueueJob;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class CloneStatusQuery extends Query
{
    protected $attributes = [
        'name' => 'cloneStatus',
        'description' => 'Trạng thái clone to staging',
    ];

    public function type(): Type
    {
        return GraphQL::type('CloneStatus');
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $data = Cache::get(ProcessQueueJob::CACHE_KEY_CLONE_LAST);
        return ['last_run' => $data];
    }
}
