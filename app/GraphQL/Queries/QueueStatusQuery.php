<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class QueueStatusQuery extends Query
{
    protected $attributes = [
        'name' => 'queueStatus',
        'description' => 'Trạng thái queue',
    ];

    public function type(): Type
    {
        return GraphQL::type('QueueStatus');
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $connection = config('queue.default');
        $driver = config("queue.connections.{$connection}.driver");
        $pending = null;
        if ($driver === 'database') {
            $table = config("queue.connections.{$connection}.table", 'jobs');
            $pending = DB::table($table)->count();
        }
        return [
            'connection' => $connection,
            'driver' => $driver,
            'pending_count' => $pending,
            'message' => $driver !== 'database' ? 'Pending count only available for database driver' : null,
        ];
    }
}
