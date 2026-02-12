<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class AgentQuery extends Query
{
    protected $attributes = [
        'name' => 'agent',
        'description' => 'Chi tiết một đại lý theo ID',
    ];

    public function type(): Type
    {
        return GraphQL::type('Agent');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = auth()->user();

        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        // Find agent with creator relationship (matching controller show method)
        $agent = Agent::with('creator:id,name,email')->find($args['id']);

        if (!$agent) {
            throw new \Exception('Agent not found');
        }

        // Check access permission
        if (!$user->hasAgentAccess($agent)) {
            throw new \Exception('Bạn không có quyền truy cập đại lý này');
        }

        return $agent;
    }
}
